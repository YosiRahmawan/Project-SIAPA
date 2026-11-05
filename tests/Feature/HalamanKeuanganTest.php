<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Siswa;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class HalamanKeuanganTest extends TestCase
{
    use RefreshDatabase, SiapkanData;

    public function test_dashboard_memisahkan_tunggakan_dan_pengingat_dekat(): void
    {
        $this->withoutVite();
        $this->travelTo('2026-11-06');
        $lewat = Siswa::factory()->create(['nama' => 'Contoh Lewat']);
        $dekat = Siswa::factory()->create(['nama' => 'Contoh Dekat']);
        $jauh = Siswa::factory()->create(['nama' => 'Contoh Jauh']);
        $this->tagihan(['nis' => $lewat->nis, 'jatuh_tempo' => '2026-11-01']);
        $this->tagihan(['nis' => $dekat->nis, 'jatuh_tempo' => '2026-11-10']);
        $this->tagihan(['nis' => $jauh->nis, 'jatuh_tempo' => '2026-12-10']);
        $this->actingAs($this->staf(Role::Keuangan))->get('/keuangan')->assertOk()
            ->assertViewHas('jumlahDekat', 1)->assertViewHas('jumlahMenunggak', 1)
            ->assertSee('1 tagihan sudah melewati jatuh tempo')->assertSee('1 tagihan segera jatuh tempo');
        $this->get('/keuangan/halaman/tagihan?peringatan=dekat')->assertOk()
            ->assertSee('Contoh Dekat')->assertDontSee('Contoh Lewat')->assertDontSee('Contoh Jauh');
    }

    public function test_qris_menunggu_tampil_konsisten_di_daftar_tagihan(): void
    {
        $this->withoutVite();
        Storage::fake('local');
        $petugas = $this->staf(Role::Keuangan);
        $tagihan = $this->tagihan(['jatuh_tempo' => today()->subDay()]);
        app(PembayaranService::class)->ajukanQris($tagihan, 1000, UploadedFile::fake()->image('bukti.png'), $petugas);
        $this->actingAs($petugas)->get('/keuangan/halaman/tagihan')->assertOk()
            ->assertSee('Menunggu verifikasi')->assertDontSee('>Menunggak<', false);
    }

    public function test_aksi_keuangan_menyisakan_pesan_sukses_di_halaman_tujuan(): void
    {
        $this->withoutVite();
        Storage::fake('local');
        $petugas = $this->staf(Role::Keuangan);
        $service = app(PembayaranService::class);
        $tagihan = $this->tagihan();
        $qris = $service->ajukanQris($tagihan, 1000, UploadedFile::fake()->image('terima.png'), $petugas);
        $this->actingAs($petugas)->postJson('/keuangan/pembayaran/'.$qris->id.'/verifikasi', ['terima' => true])->assertOk();
        $this->get('/keuangan/halaman/verifikasi')->assertSee('Pembayaran QRIS berhasil diterima.');
        $this->get('/keuangan/halaman/verifikasi')->assertDontSee('Pembayaran QRIS berhasil diterima.');
        $this->postJson('/keuangan/pembayaran/'.$qris->id.'/batal', ['alasan' => 'Salah catat'])->assertOk();
        $this->get('/keuangan/halaman/laporan')->assertSee('Pembayaran berhasil dibatalkan.');
        $jemputan = $this->tagihan(['jenis' => \App\Enums\JenisTagihan::Jemputan]);
        $this->patchJson('/keuangan/tagihan/'.$jemputan->id.'/nominal', ['nominal' => 200000])->assertOk();
        $this->get('/keuangan/halaman/tagihan')->assertSee('Nominal jemputan berhasil diperbarui.');
        $ditolak = $service->ajukanQris($tagihan, 1000, UploadedFile::fake()->image('tolak.png'), $petugas);
        $this->postJson('/keuangan/pembayaran/'.$ditolak->id.'/verifikasi', ['terima' => false, 'alasan' => 'Bukti tidak sesuai'])->assertOk();
        $this->get('/keuangan/halaman/verifikasi')->assertSee('Bukti QRIS berhasil ditolak.');
    }

    public function test_filter_tidak_valid_memberi_pesan_di_halaman(): void
    {
        $this->withoutVite();
        $this->actingAs($this->staf(Role::Keuangan))->from('/keuangan/halaman/laporan')->followingRedirects()
            ->get('/keuangan/halaman/laporan?periode=salah')->assertOk()->assertSee('Periode harus berformat YYYY-MM')->assertSee('role="alert"', false);
    }

    public function test_sisa_tagihan_dapat_dibayar_saat_qris_sebagian_menunggu(): void
    {
        Storage::fake('local');
        $petugas = $this->staf(Role::Keuangan);
        $tagihan = $this->tagihan(['nominal' => 590000]);
        app(PembayaranService::class)->ajukanQris($tagihan, 100000, UploadedFile::fake()->image('bukti.png'), $petugas);
        $this->actingAs($petugas)->getJson('/keuangan/siswa/'.$tagihan->nis)
            ->assertOk()->assertJsonPath('tagihan.0.sisa_bisa_dibayar', 490000)->assertJsonPath('tagihan.0.status_tampil', 'menunggu_verifikasi');
        $this->postJson('/keuangan/tagihan/'.$tagihan->id.'/pembayaran', ['metode' => 'manual', 'jumlah' => 490001])->assertUnprocessable();
        $this->postJson('/keuangan/tagihan/'.$tagihan->id.'/pembayaran', ['metode' => 'manual', 'jumlah' => 490000])->assertCreated();
        $this->getJson('/keuangan/siswa/'.$tagihan->nis)->assertJsonPath('tagihan.0.sisa_bisa_dibayar', 0);
    }

    public function test_ubah_nominal_jemputan_hanya_ditawarkan_sebelum_ada_pembayaran(): void
    {
        $this->withoutVite();
        $petugas = $this->staf(Role::Keuangan);
        $tagihan = $this->tagihan(['jenis' => \App\Enums\JenisTagihan::Jemputan, 'nominal' => 100000]);
        $this->actingAs($petugas)->get('/keuangan/halaman/tagihan')->assertOk()->assertSee('Ubah nominal');
        $this->patchJson('/keuangan/tagihan/'.$tagihan->id.'/nominal', ['nominal' => 150000])->assertOk()->assertJsonPath('nominal', 150000);
        app(PembayaranService::class)->catatManual($tagihan->fresh(), 1000, $petugas);
        $this->get('/keuangan/halaman/tagihan')->assertOk()->assertDontSee('Ubah nominal');
        $this->patchJson('/keuangan/tagihan/'.$tagihan->id.'/nominal', ['nominal' => 200000])->assertUnprocessable();
    }

    public function test_semua_halaman_keuangan_dapat_dibuka_dengan_data_kosong(): void
    {
        $this->actingAs($this->staf(Role::Keuangan));
        foreach (['/keuangan', ...array_map(fn ($h) => '/keuangan/halaman/'.$h, ['pembayaran', 'tagihan', 'buat-tagihan', 'verifikasi', 'tunggakan', 'data-siswa', 'laporan', 'penerimaan'])] as $url) {
            $this->get($url)->assertOk()->assertSee('Petugas Keuangan');
        }
    }

    public function test_dashboard_dan_kuitansi_hanya_menghitung_pembayaran_diterima(): void
    {
        Storage::fake('local');
        $petugas = $this->staf(Role::Keuangan);
        $t = $this->tagihan(['nominal' => 590000, 'periode' => today()->format('Y-m')]);
        $service = app(PembayaranService::class);
        $manual = $service->catatManual($t, 200000, $petugas);
        $qris = $service->ajukanQris($t, 100000, UploadedFile::fake()->image('qris.jpg'), $petugas);
        $this->actingAs($petugas)->get('/keuangan')->assertOk()->assertViewHas('rekap', fn ($r) => $r['total']['diterima'] === 200000 && $r['total']['sisa'] === 390000);
        $this->get('/keuangan/kuitansi/'.$manual->id)->assertOk()->assertSee('Rp200.000');
        $this->get('/keuangan/kuitansi/'.$qris->id)->assertNotFound();
        $this->get('/keuangan/bukti/'.$qris->id)->assertOk();
        $this->get('/keuangan/halaman/verifikasi')->assertOk()->assertSee($t->siswa->nama);
        foreach (['tagihan', 'tunggakan', 'laporan', 'penerimaan'] as $page) {
            $this->get('/keuangan/halaman/'.$page)->assertOk();
        }
    }

    public function test_akses_halaman_dan_bukti_dibatasi_ke_petugas_keuangan(): void
    {
        $p = app(PembayaranService::class)->catatManual($this->tagihan(), 10000, $this->staf(Role::Keuangan));
        $this->get('/keuangan')->assertRedirect('/login');
        foreach ([Role::Admin, Role::Yayasan, Role::Siswa] as $role) {
            $this->actingAs($this->staf($role))->get('/keuangan')->assertForbidden();
            $this->get('/keuangan/bukti/'.$p->id)->assertForbidden();
            $this->get('/keuangan/kuitansi/'.$p->id)->assertForbidden();
        }
    }

    public function test_pencarian_siswa_dan_filter_tagihan(): void
    {
        $a = Siswa::factory()->create(['nama' => 'Aisyah Referensi', 'kelas' => 1]);
        $b = Siswa::factory()->create(['nama' => 'Bagas Referensi', 'kelas' => 2]);
        $this->tagihan(['nis' => $a->nis]);
        $this->tagihan(['nis' => $b->nis]);
        $this->actingAs($this->staf(Role::Keuangan));
        $this->get('/keuangan/halaman/data-siswa?q=Aisyah')->assertOk()->assertSee('Aisyah Referensi')->assertDontSee('Bagas Referensi');
        $this->get('/keuangan/halaman/tagihan?kelas=2')->assertOk()->assertSee('Bagas Referensi')->assertDontSee('Aisyah Referensi');
        $this->getJson('/keuangan/halaman/laporan?periode=invalid')->assertUnprocessable();
    }
}
