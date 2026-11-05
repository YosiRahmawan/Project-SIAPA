<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Siswa;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class HalamanPortalTest extends TestCase
{
    use RefreshDatabase, SiapkanData;

    public function test_pencarian_nis_laporan_tidak_meninggalkan_pengguna_di_404(): void
    {
        $this->withoutVite();
        $this->actingAs($this->staf(Role::Yayasan))->followingRedirects()->get('/laporan/rekap?nis=0000&periode=2026-11')
            ->assertOk()->assertSee('Siswa dengan NIS tersebut tidak ditemukan.')->assertSee('id="lookup-error"', false);
        $siswa = Siswa::factory()->create();
        $this->get('/laporan/rekap?nis='.$siswa->nis)->assertRedirect('/laporan/siswa/'.$siswa->nis);
    }

    public function test_alamat_admin_mengarah_ke_pengguna_dengan_hak_akses_yang_sama(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->actingAs($this->staf(Role::Admin))->get('/admin')->assertRedirect('/admin/pengguna');
        $this->actingAs($this->staf(Role::Siswa))->get('/admin')->assertForbidden();
    }

    public function test_halaman_role_lain_menampilkan_html_dan_logo(): void
    {
        $this->withoutVite();
        foreach ([
            [Role::Admin, ['/admin/pengguna', '/admin/siswa']],
            [Role::Yayasan, ['/laporan/rekap', '/laporan/tunggakan', '/laporan/pembayaran', '/yayasan/pembatalan']],
            [Role::Siswa, ['/siswa/tagihan', '/siswa/riwayat', '/siswa/peringatan']],
        ] as [$role, $urls]) {
            $this->actingAs($this->staf($role));
            foreach ($urls as $url) {
                $this->get($url)->assertOk()->assertViewIs('portal.index')->assertSee('images/siapa-logo.png')->assertSee('Keluar');
                $this->getJson($url)->assertOk()->assertHeader('Content-Type', 'application/json');
            }
        }
    }

    public function test_admin_melihat_data_dan_pencarian_siswa(): void
    {
        $this->withoutVite();
        $siswa = Siswa::factory()->create(['nama' => "Aisyah O'Neil"]);
        Siswa::factory()->create(['nama' => 'Siswa Lain']);
        $this->actingAs($this->staf(Role::Admin));
        $this->get('/admin/pengguna')->assertOk()->assertSee('data-edit', false);
        $this->get('/admin/siswa?q=Aisyah')->assertOk()->assertSee($siswa->nama)->assertDontSee('Siswa Lain')->assertSee('Buat akun');
    }

    public function test_html_siswa_tetap_membatasi_data_ke_pemilik(): void
    {
        $this->withoutVite();
        [$user, $siswa] = $this->akunSiswa();
        $own = $this->tagihan(['nis' => $siswa->nis, 'nominal' => 123456, 'jatuh_tempo' => today()->subDay()]);
        $this->tagihan(['nominal' => 987654]);
        app(PembayaranService::class)->catatManual($own, 1000, $this->staf(Role::Keuangan));
        $this->actingAs($user);
        $this->get('/siswa/tagihan')->assertOk()->assertSee('Rp123.456')->assertDontSee('Rp987.654');
        $this->get('/siswa/peringatan')->assertOk()->assertSee('Rp123.456')->assertDontSee('Rp987.654');
        $this->get('/siswa/riwayat')->assertOk()->assertSee('Rp1.000');
        $this->get('/laporan/rekap')->assertForbidden();
        $this->get('/admin/pengguna')->assertForbidden();
    }

    public function test_laporan_html_dengan_data_dan_pembatalan(): void
    {
        $this->withoutVite();
        $staf = $this->staf(Role::Keuangan);
        $this->actingAs($staf);
        $tagihan = $this->tagihan(['nominal' => 55000, 'jatuh_tempo' => today()->subDay()]);
        $service = app(PembayaranService::class);
        $p = $service->catatManual($tagihan, 1000, $staf);
        $service->batalkan($p, 'Salah catat pengujian', $staf);
        $this->actingAs($this->staf(Role::Yayasan));
        $this->get('/laporan/rekap')->assertOk()->assertSee('Rp55.000');
        $this->get('/laporan/tunggakan')->assertOk()->assertSee($tagihan->siswa->nama);
        $this->get('/laporan/pembayaran?status=dibatalkan')->assertOk()->assertSee('Salah catat pengujian');
        $this->get('/laporan/siswa/'.$tagihan->nis)->assertOk()->assertSee($tagihan->siswa->nama);
        $this->get('/yayasan/pembatalan')->assertOk()->assertSee('Salah catat pengujian');
        $this->get('/laporan/rekap?periode=invalid')->assertSessionHasErrors('periode');
    }
}
