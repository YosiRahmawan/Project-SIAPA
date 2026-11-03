<?php

namespace Tests\Feature;

use App\Enums\JenisTagihan;
use App\Enums\Role;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Tarif;
use App\Services\PembayaranService;
use App\Services\TagihanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class TagihanServiceTest extends TestCase
{
    use RefreshDatabase, SiapkanData;

    private TagihanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTarif();
        $this->service = app(TagihanService::class);
    }

    public function test_spp_mengikuti_tarif_per_kelas_dan_jatuh_tempo_tanggal_10(): void
    {
        foreach ([1, 2, 3, 6] as $kelas) {
            Siswa::factory()->kelas($kelas)->create(['nis' => "100{$kelas}"]);
        }

        $this->assertSame(4, $this->service->generateSpp('2026-11'));

        $nominal = Tagihan::pluck('nominal', 'nis');
        $this->assertSame(590000, $nominal['1001']);
        $this->assertSame(550000, $nominal['1002']);
        $this->assertSame(540000, $nominal['1003']);
        $this->assertSame(540000, $nominal['1006']);
        $this->assertSame('2026-11-10', Tagihan::first()->jatuh_tempo->toDateString());
        $this->assertSame('belum', Tagihan::first()->status);
    }

    public function test_generate_spp_aman_dipanggil_ulang(): void
    {
        Siswa::factory()->kelas(1)->create();

        $this->assertSame(1, $this->service->generateSpp('2026-11'));
        $this->assertSame(0, $this->service->generateSpp('2026-11'));
        $this->assertSame(1, Tagihan::count());
    }

    public function test_siswa_nonaktif_tidak_ditagih(): void
    {
        Siswa::factory()->kelas(1)->create(['aktif' => false]);

        $this->assertSame(0, $this->service->generateSpp('2026-11'));
    }

    public function test_tarif_tidak_di_hardcode(): void
    {
        Tarif::where('jenis', 'spp')->where('kelas', 1)->update(['nominal' => 600000]);
        Siswa::factory()->kelas(1)->create();

        $this->service->generateSpp('2026-11');

        $this->assertSame(600000, Tagihan::first()->nominal);
    }

    public function test_daftar_ulang_hanya_kelas_2_dan_6_jatuh_tempo_10_desember(): void
    {
        foreach ([1, 2, 3, 6] as $kelas) {
            Siswa::factory()->kelas($kelas)->create(['nis' => "200{$kelas}"]);
        }

        $this->assertSame(2, $this->service->generateDaftarUlang(2026));

        $t = Tagihan::where('jenis', JenisTagihan::DaftarUlang)->pluck('nominal', 'nis');
        $this->assertSame(3500000, $t['2002']);
        $this->assertSame(3200000, $t['2006']);
        $this->assertSame('2026-12-10', Tagihan::first()->jatuh_tempo->toDateString());
    }

    public function test_periode_tidak_valid_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->generateSpp('2026-13');
    }

    public function test_jemputan_nominal_manual_dan_tidak_boleh_ganda(): void
    {
        $siswa = Siswa::factory()->create();

        $t = $this->service->buatJemputan($siswa, '2026-11', 275000);

        $this->assertSame(275000, $t->nominal);
        $this->assertSame(JenisTagihan::Jemputan, $t->jenis);
        $this->expectException(ValidationException::class);
        $this->service->buatJemputan($siswa, '2026-11', 275000);
    }

    public function test_jemputan_nominal_nol_ditolak(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->buatJemputan(Siswa::factory()->create(), '2026-11', 0);
    }

    public function test_nominal_jemputan_bisa_diubah_sebelum_ada_pembayaran_saja(): void
    {
        $t = $this->service->buatJemputan(Siswa::factory()->create(), '2026-11', 100000);
        $this->assertSame(150000, $this->service->ubahNominalJemputan($t, 150000)->nominal);

        app(PembayaranService::class)->catatManual($t, 50000, $this->staf(Role::Keuangan));

        $this->expectException(ValidationException::class);
        $this->service->ubahNominalJemputan($t, 200000);
    }

    public function test_nominal_spp_tidak_boleh_diubah_lewat_ubah_nominal_jemputan(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->ubahNominalJemputan($this->tagihan(), 1000);
    }
}
