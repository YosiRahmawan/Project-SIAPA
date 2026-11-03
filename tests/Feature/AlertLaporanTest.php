<?php

namespace Tests\Feature;

use App\Enums\JenisTagihan;
use App\Enums\Role;
use App\Models\Siswa;
use App\Services\AlertService;
use App\Services\PembayaranService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class AlertLaporanTest extends TestCase
{
    use RefreshDatabase, SiapkanData;

    private function idPeringatan(string $hari): array
    {
        return app(AlertService::class)->peringatan(CarbonImmutable::parse($hari))->pluck('id')->all();
    }

    public function test_peringatan_spp_muncul_5_hari_sebelum_tanggal_10(): void
    {
        $t = $this->tagihan(['jatuh_tempo' => '2026-11-10']);

        $this->assertSame([], $this->idPeringatan('2026-11-04'));
        $this->assertSame([$t->id], $this->idPeringatan('2026-11-05'));
        $this->assertSame([$t->id], $this->idPeringatan('2026-11-20')); // lewat jatuh tempo, tetap tampil
    }

    public function test_peringatan_hilang_setelah_lunas(): void
    {
        $t = $this->tagihan(['nominal' => 1000, 'jatuh_tempo' => '2026-11-10']);
        app(PembayaranService::class)->catatManual($t, 1000, $this->staf(Role::Keuangan));

        $this->assertSame([], $this->idPeringatan('2026-11-12'));
    }

    public function test_peringatan_daftar_ulang_menjelang_10_desember(): void
    {
        $t = $this->tagihan(['jenis' => JenisTagihan::DaftarUlang, 'periode' => '2026-12', 'jatuh_tempo' => '2026-12-10', 'nominal' => 3500000]);

        $this->assertSame([], $this->idPeringatan('2026-11-09'));
        $this->assertSame([$t->id], $this->idPeringatan('2026-11-10'));
    }

    public function test_status_tampil_mengikuti_tanggal(): void
    {
        $t = $this->tagihan(['jatuh_tempo' => '2026-11-10']);

        $this->assertSame('belum_bayar', $t->statusTampil(CarbonImmutable::parse('2026-11-01')));
        $this->assertSame('hampir_jatuh_tempo', $t->statusTampil(CarbonImmutable::parse('2026-11-07')));
        $this->assertSame('hampir_jatuh_tempo', $t->statusTampil(CarbonImmutable::parse('2026-11-10')));
        $this->assertSame('menunggak', $t->statusTampil(CarbonImmutable::parse('2026-11-11')));
    }

    public function test_tunggakan_hanya_yang_belum_lunas_dan_lewat_jatuh_tempo(): void
    {
        $this->travelTo('2026-11-11');
        $lewat = $this->tagihan(['jatuh_tempo' => '2026-11-10', 'periode' => '2026-11']);
        $this->tagihan(['jatuh_tempo' => '2026-12-10', 'periode' => '2026-12']); // belum jatuh tempo
        $lunas = $this->tagihan(['jatuh_tempo' => '2026-10-10', 'periode' => '2026-10', 'nominal' => 1000]);
        app(PembayaranService::class)->catatManual($lunas, 1000, $this->staf(Role::Keuangan));

        $data = $this->actingAs($this->staf(Role::Keuangan))->getJson('/laporan/tunggakan')->assertOk()->json('data');

        $this->assertSame([$lewat->id], array_column($data, 'id'));
        $this->assertSame('menunggak', $data[0]['status_tampil']);
    }

    public function test_rekap_menjumlah_tagihan_dan_pembayaran_diterima_per_jenis(): void
    {
        $petugas = $this->staf(Role::Keuangan);
        $service = app(PembayaranService::class);
        $a = $this->tagihan(['nominal' => 540000]);
        $b = $this->tagihan(['nominal' => 540000]);
        $service->catatManual($a, 540000, $petugas);
        $service->catatManual($b, 200000, $petugas);
        $batal = $service->catatManual($b, 100000, $petugas);
        $service->batalkan($batal, 'Salah', $petugas); // tidak ikut dihitung

        $r = $this->actingAs($this->staf(Role::Yayasan))->getJson('/laporan/rekap?periode=2026-11')->assertOk()->json();

        $this->assertSame(['spp'], array_column($r['per_jenis'], 'jenis'));
        $this->assertSame(2, $r['per_jenis'][0]['jumlah_tagihan']);
        $this->assertSame(1080000, $r['total']['total_nominal']);
        $this->assertSame(740000, $r['total']['diterima']);
        $this->assertSame(340000, $r['total']['sisa']);
    }

    public function test_laporan_pembayaran_bisa_difilter_dan_diekspor_csv(): void
    {
        $petugas = $this->staf(Role::Keuangan);
        $service = app(PembayaranService::class);
        $siswa = Siswa::factory()->create(['nama' => 'Aisyah']);
        $t = $this->tagihan(['nis' => $siswa->nis, 'nominal' => 1000]);
        $p = $service->catatManual($t, 1000, $petugas);
        $service->batalkan($p, '=HYPERLINK("http://jahat")', $petugas);
        $this->tagihan(['nominal' => 5000]);

        $json = $this->actingAs($petugas)->getJson("/laporan/pembayaran?nis={$siswa->nis}&status=dibatalkan")->assertOk()->json('data');
        $this->assertCount(1, $json);

        $csv = $this->actingAs($petugas)->get('/laporan/pembayaran?format=csv')->assertOk()->streamedContent();
        $this->assertStringContainsString('tanggal,nis,nama,jenis,periode,metode,jumlah,status,alasan', $csv);
        $this->assertStringContainsString('Aisyah', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv); // rumus Excel dinetralkan
    }

    public function test_yayasan_melihat_peringatan_pembatalan_beserta_alasannya(): void
    {
        $petugas = $this->staf(Role::Keuangan);
        $service = app(PembayaranService::class);
        $p = $service->catatManual($this->tagihan(), 1000, $petugas);
        $this->actingAs($petugas);
        $service->batalkan($p, 'Salah input', $petugas);

        $data = $this->actingAs($this->staf(Role::Yayasan))->getJson('/yayasan/pembatalan')->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('Salah input', $data[0]['sesudah']['alasan']);
        $this->assertSame($petugas->nama, $data[0]['pelaku']['nama']);
    }
}
