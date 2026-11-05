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
