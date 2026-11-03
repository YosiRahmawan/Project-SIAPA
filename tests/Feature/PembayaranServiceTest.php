<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusPembayaran;
use App\Models\AuditLog;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class PembayaranServiceTest extends TestCase
{
    use RefreshDatabase, SiapkanData;

    private PembayaranService $service;

    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PembayaranService::class);
        $this->petugas = $this->staf(Role::Keuangan);
    }

    public function test_pembayaran_penuh_membuat_tagihan_lunas_dan_tercatat_di_audit(): void
    {
        $t = $this->tagihan(['nominal' => 540000]);

        $p = $this->service->catatManual($t, 540000, $this->petugas);

        $this->assertSame(StatusPembayaran::Diterima, $p->status);
        $this->assertSame('lunas', $t->fresh()->status);
        $this->assertTrue(AuditLog::where('aksi', 'pembayaran.catat')->where('baris_id', $p->id)->exists());
        $this->assertTrue(AuditLog::where('aksi', 'tagihan.status')->where('baris_id', $t->id)->exists());
    }

    public function test_lunas_hanya_bila_total_diterima_sama_dengan_nominal(): void
    {
        $t = $this->tagihan(['nominal' => 540000]);

        $this->service->catatManual($t, 300000, $this->petugas);
        $this->assertSame('belum', $t->fresh()->status);

        $this->service->catatManual($t, 240000, $this->petugas);
        $this->assertSame('lunas', $t->fresh()->status);
    }

    public function test_pembayaran_melebihi_sisa_ditolak_dan_tidak_tersimpan(): void
    {
        $t = $this->tagihan(['nominal' => 540000]);
        $this->service->catatManual($t, 500000, $this->petugas);

        try {
            $this->service->catatManual($t, 100000, $this->petugas);
            $this->fail('Seharusnya ditolak');
        } catch (ValidationException) {
            $this->assertSame(1, Pembayaran::count());
        }
    }

    public function test_tagihan_lunas_tidak_bisa_dibayar_lagi(): void
    {
        $t = $this->tagihan(['nominal' => 540000]);
        $this->service->catatManual($t, 540000, $this->petugas);

        $this->expectException(ValidationException::class);
        $this->service->catatManual($t, 1, $this->petugas);
    }

    public function test_jumlah_nol_atau_negatif_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->catatManual($this->tagihan(), 0, $this->petugas);
    }

    public function test_admin_dan_peran_lain_tidak_boleh_mencatat_pembayaran(): void
    {
        $t = $this->tagihan();

        foreach ([Role::Admin, Role::Yayasan, Role::Siswa] as $role) {
            try {
                $this->service->catatManual($t, 1000, $this->staf($role));
                $this->fail("{$role->value} seharusnya ditolak");
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertSame(0, Pembayaran::count());
    }

    public function test_qris_menunggu_verifikasi_belum_melunasi_tapi_menahan_sisa(): void
    {
        Storage::fake('local');
        $t = $this->tagihan(['nominal' => 540000]);

        $p = $this->service->ajukanQris($t, 540000, UploadedFile::fake()->image('struk-asli.png'), $this->petugas);

        $this->assertSame(StatusPembayaran::Menunggu, $p->status);
        $this->assertSame('belum', $t->fresh()->status);
        $this->assertSame('menunggu_verifikasi', $t->fresh()->statusTampil(today()));
        // Sisa sudah ditahan oleh bukti yang menunggu, jadi tidak bisa dibayar ganda.
        $this->expectException(ValidationException::class);
        $this->service->catatManual($t, 1000, $this->petugas);
    }

    public function test_bukti_qris_disimpan_dengan_nama_acak(): void
    {
        Storage::fake('local');
        $p = $this->service->ajukanQris($this->tagihan(), 1000, UploadedFile::fake()->image('struk-asli.png'), $this->petugas);

        $this->assertStringNotContainsString('struk-asli', $p->bukti);
        Storage::disk('local')->assertExists($p->bukti);
    }

    public function test_qris_diterima_melunasi_dan_ditolak_tidak(): void
    {
        Storage::fake('local');
        $t = $this->tagihan(['nominal' => 1000]);
        $a = $this->service->ajukanQris($t, 1000, UploadedFile::fake()->image('a.png'), $this->petugas);

        $this->service->putuskan($a, false, $this->petugas, 'Bukti buram');
        $this->assertSame(StatusPembayaran::Ditolak, $a->fresh()->status);
        $this->assertSame('belum', $t->fresh()->status);

        $b = $this->service->ajukanQris($t, 1000, UploadedFile::fake()->image('b.png'), $this->petugas);
        $this->service->putuskan($b, true, $this->petugas);
        $this->assertSame('lunas', $t->fresh()->status);
    }

    public function test_hanya_yang_menunggu_bisa_diputuskan(): void
    {
        $p = $this->service->catatManual($this->tagihan(), 1000, $this->petugas);

        $this->expectException(ValidationException::class);
        $this->service->putuskan($p, true, $this->petugas);
    }

    public function test_pembatalan_mengembalikan_status_dan_data_lama_tetap_ada(): void
    {
        $t = $this->tagihan(['nominal' => 540000]);
        $p = $this->service->catatManual($t, 540000, $this->petugas);
        $this->assertSame('lunas', $t->fresh()->status);

        $this->service->batalkan($p, 'Salah input nominal', $this->petugas);

        $baris = Pembayaran::find($p->id);
        $this->assertNotNull($baris);
        $this->assertSame(StatusPembayaran::Dibatalkan, $baris->status);
        $this->assertSame('Salah input nominal', $baris->alasan);
        $this->assertSame(540000, $baris->jumlah);
        $this->assertSame('belum', $t->fresh()->status);
        $this->assertTrue(AuditLog::where('aksi', 'pembayaran.batal')->exists());

        // Input ulang yang benar bisa dilakukan setelah pembatalan.
        $this->service->catatManual($t, 540000, $this->petugas);
        $this->assertSame('lunas', $t->fresh()->status);
    }

    public function test_pembatalan_wajib_alasan_dan_tidak_bisa_dua_kali(): void
    {
        $p = $this->service->catatManual($this->tagihan(), 1000, $this->petugas);

        try {
            $this->service->batalkan($p, '   ', $this->petugas);
            $this->fail('Alasan kosong seharusnya ditolak');
        } catch (ValidationException) {
        }

        $this->service->batalkan($p, 'Salah', $this->petugas);
        $this->expectException(ValidationException::class);
        $this->service->batalkan($p, 'Lagi', $this->petugas);
    }

    public function test_pembayaran_tidak_boleh_diubah_nominalnya_atau_dihapus(): void
    {
        $p = $this->service->catatManual($this->tagihan(), 1000, $this->petugas);

        try {
            $p->update(['jumlah' => 1]);
            $this->fail('Ubah jumlah seharusnya ditolak');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $p->delete();
    }

    public function test_audit_log_hanya_tambah(): void
    {
        $this->service->catatManual($this->tagihan(), 1000, $this->petugas);
        $log = AuditLog::first();

        try {
            $log->update(['aksi' => 'diubah']);
            $this->fail('Ubah log seharusnya ditolak');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_status_tagihan_ikut_dihitung_dari_pembayaran_diterima_saja(): void
    {
        $t = $this->tagihan(['nominal' => 1000]);
        $p = $this->service->catatManual($t, 1000, $this->petugas);
        $this->service->batalkan($p, 'Salah', $this->petugas);

        $this->assertSame(0, $t->fresh()->dibayar());
        $this->assertInstanceOf(Tagihan::class, $t->fresh());
    }
}
