<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PembayaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SiapkanData;
use Tests\TestCase;

class AksesTest extends TestCase
{
    use RefreshDatabase, SiapkanData;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/siswa/tagihan')->assertRedirect('/login');
        $this->getJson('/siswa/tagihan')->assertUnauthorized();
    }

    public function test_halaman_login_tampil(): void
    {
        $this->withoutVite();

        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    #[DataProvider('peran')]
    public function test_login_mengarah_ke_beranda_sesuai_role(Role $role): void
    {
        $user = User::factory()->role($role)->create(['username' => 'uji', 'password' => 'Rahasia123']);

        $this->post('/login', ['username' => 'uji', 'password' => 'Rahasia123'])->assertRedirect($role->beranda());
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(AuditLog::where('aksi', 'login')->exists());
    }

    public static function peran(): array
    {
        return array_map(fn (Role $r) => [$r], Role::cases());
    }

    public function test_login_tidak_kembali_ke_alamat_role_lain_yang_tersimpan(): void
    {
        User::factory()->role(Role::Keuangan)->create(['username' => 'uji', 'password' => 'Rahasia123']);
        $this->get('/admin/pengguna')->assertRedirect('/login');
        $this->post('/login', ['username' => 'uji', 'password' => 'Rahasia123'])->assertRedirect('/keuangan');
        $this->assertFalse(session()->has('url.intended'));
    }

    public function test_halaman_403_memberi_jalan_kembali_dan_ganti_akun(): void
    {
        $this->withoutVite();
        $this->actingAs($this->staf(Role::Keuangan))->get('/admin/pengguna')
            ->assertForbidden()->assertSee('Petugas Keuangan')->assertSee('Keluar dan ganti akun')->assertSee('href="/keuangan"', false);
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_gagal_dicatat_dan_akun_nonaktif_ditolak(): void
    {
        User::factory()->create(['username' => 'uji', 'password' => 'Rahasia123']);
        User::factory()->create(['username' => 'mati', 'password' => 'Rahasia123', 'aktif' => false]);

        $this->post('/login', ['username' => 'uji', 'password' => 'salah'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'mati', 'password' => 'Rahasia123'])->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->assertSame(2, AuditLog::where('aksi', 'login.gagal')->count());
    }

    public function test_percobaan_login_dibatasi(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'x', 'password' => 'y']);
        }

        $this->post('/login', ['username' => 'x', 'password' => 'y'])->assertStatus(429);
    }

    public function test_password_awal_wajib_diganti_sebelum_memakai_fitur_lain(): void
    {
        $this->withoutVite();
        $user = User::factory()->role(Role::Keuangan)->create(['password' => 'Awal12345', 'harus_ganti_password' => true]);

        $this->actingAs($user)->get('/laporan/rekap')->assertRedirect('/ganti-password');
        $this->actingAs($user)->getJson('/laporan/rekap')->assertForbidden();
        $this->actingAs($user)->get('/ganti-password')->assertOk();

        $this->actingAs($user)->post('/ganti-password', [
            'password_lama' => 'Awal12345', 'password' => 'pendek', 'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->post('/ganti-password', [
            'password_lama' => 'Awal12345', 'password' => 'Baru12345', 'password_confirmation' => 'Baru12345',
        ])->assertRedirect(Role::Keuangan->beranda());

        $this->assertFalse($user->fresh()->harus_ganti_password);
        $this->actingAs($user->fresh())->getJson('/laporan/rekap')->assertOk();
    }

    public function test_matriks_hak_akses_per_role(): void
    {
        $t = $this->tagihan();
        $admin = $this->staf(Role::Admin);
        $yayasan = $this->staf(Role::Yayasan);
        $keuangan = $this->staf(Role::Keuangan);
        [$siswa] = $this->akunSiswa();

        // Admin tidak bisa mencatat pembayaran.
        $this->actingAs($admin)->postJson("/keuangan/tagihan/{$t->id}/pembayaran", ['metode' => 'manual', 'jumlah' => 1000])->assertForbidden();
        // Yayasan hanya membaca.
        $this->actingAs($yayasan)->postJson("/keuangan/tagihan/{$t->id}/pembayaran", ['metode' => 'manual', 'jumlah' => 1000])->assertForbidden();
        $this->actingAs($yayasan)->getJson('/laporan/rekap')->assertOk();
        // Keuangan tidak mengelola pengguna.
        $this->actingAs($keuangan)->getJson('/admin/pengguna')->assertForbidden();
        // Siswa tidak melihat laporan, riwayat orang lain, atau menu staf.
        $this->actingAs($siswa)->getJson('/laporan/rekap')->assertForbidden();
        $this->actingAs($siswa)->getJson("/laporan/siswa/{$t->nis}")->assertForbidden();
        $this->actingAs($siswa)->getJson('/keuangan/peringatan')->assertForbidden();
        $this->actingAs($siswa)->getJson('/admin/siswa')->assertForbidden();
        // Hanya Yayasan melihat peringatan pembatalan.
        $this->actingAs($keuangan)->getJson('/yayasan/pembatalan')->assertForbidden();
        $this->actingAs($yayasan)->getJson('/yayasan/pembatalan')->assertOk();
    }

    public function test_siswa_hanya_melihat_data_miliknya(): void
    {
        $this->travelTo('2026-11-06'); // dalam jendela peringatan jatuh tempo 10 November
        [$userA, $siswaA] = $this->akunSiswa();
        [, $siswaB] = $this->akunSiswa();
        $milikA = $this->tagihan(['nis' => $siswaA->nis, 'periode' => '2026-11']);
        $milikB = $this->tagihan(['nis' => $siswaB->nis, 'periode' => '2026-11']);
        $petugas = $this->staf(Role::Keuangan);
        $service = app(PembayaranService::class);
        $service->catatManual($milikA, 1000, $petugas);
        $service->catatManual($milikB, 2000, $petugas);

        $tagihan = $this->actingAs($userA)->getJson('/siswa/tagihan')->assertOk()->json();
        $this->assertCount(1, $tagihan);
        $this->assertSame($milikA->id, $tagihan[0]['id']);

        $riwayat = $this->actingAs($userA)->getJson('/siswa/riwayat')->assertOk()->json();
        $this->assertCount(1, $riwayat);
        $this->assertSame(1000, $riwayat[0]['jumlah']);
        $this->assertArrayNotHasKey('bukti', $riwayat[0]);

        $peringatan = $this->actingAs($userA)->getJson('/siswa/peringatan')->assertOk()->json();
        $this->assertSame([$milikA->id], array_column($peringatan, 'id'));
    }

    public function test_akun_siswa_tanpa_data_siswa_tidak_melihat_apa_pun(): void
    {
        $user = User::factory()->role(Role::Siswa)->create();
        $this->tagihan();

        $this->actingAs($user)->getJson('/siswa/tagihan')->assertOk()->assertExactJson([]);
    }

    public function test_alur_admin_siswa_dan_akun_login_pertama(): void
    {
        $admin = $this->staf(Role::Admin);

        $this->actingAs($admin)->postJson('/admin/siswa', ['nis' => '2410123', 'nama' => 'Aisyah', 'kelas' => 1])->assertCreated();
        $this->actingAs($admin)->postJson('/admin/siswa', ['nis' => '2410123', 'nama' => 'Kembar', 'kelas' => 2])
            ->assertUnprocessable()->assertJsonValidationErrors('nis');

        $akun = $this->actingAs($admin)->postJson('/admin/siswa/2410123/akun')->assertCreated()->json();
        $this->assertSame('2410123', $akun['username']);
        $this->assertSame(12, strlen($akun['password_awal']));
        $this->actingAs($admin)->postJson('/admin/siswa/2410123/akun')->assertUnprocessable();

        $this->app['auth']->forgetGuards();
        $this->post('/login', ['username' => '2410123', 'password' => $akun['password_awal']])->assertRedirect('/ganti-password');
        $this->assertNotSame($akun['password_awal'], User::where('username', '2410123')->value('password'));
    }

    public function test_admin_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $admin = $this->staf(Role::Admin);

        $this->actingAs($admin)->patchJson("/admin/pengguna/{$admin->id}", ['aktif' => false])->assertUnprocessable();
    }

    public function test_alur_pembayaran_lewat_http(): void
    {
        Storage::fake('local');
        $t = $this->tagihan(['nominal' => 540000]);
        $petugas = $this->staf(Role::Keuangan);

        $this->actingAs($petugas)->getJson('/keuangan/siswa/00000')->assertNotFound()->assertJson(['message' => 'Data siswa tidak ditemukan']);
        $this->actingAs($petugas)->getJson("/keuangan/siswa/{$t->nis}")->assertOk()->assertJsonPath('tagihan.0.id', $t->id);

        $this->actingAs($petugas)->postJson("/keuangan/tagihan/{$t->id}/pembayaran", ['metode' => 'qris', 'jumlah' => 540000])
            ->assertUnprocessable()->assertJsonValidationErrors('bukti');
        $this->actingAs($petugas)->postJson("/keuangan/tagihan/{$t->id}/pembayaran", ['metode' => 'manual', 'jumlah' => 600000])
            ->assertUnprocessable();
        $this->actingAs($petugas)->postJson("/keuangan/tagihan/{$t->id}/pembayaran", [
            'metode' => 'qris', 'jumlah' => 540000, 'bukti' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertUnprocessable()->assertJsonValidationErrors('bukti');

        $r = $this->actingAs($petugas)->postJson("/keuangan/tagihan/{$t->id}/pembayaran", [
            'metode' => 'qris', 'jumlah' => 540000, 'bukti' => UploadedFile::fake()->image('b.png'),
        ])->assertCreated()->assertJsonMissingPath('bukti')->assertJsonPath('tanggal', today()->toDateString());

        $this->actingAs($petugas)->postJson("/keuangan/pembayaran/{$r->json('id')}/verifikasi", ['terima' => true])->assertOk();
        $this->assertSame('lunas', $t->fresh()->status);

        $this->actingAs($petugas)->postJson("/keuangan/pembayaran/{$r->json('id')}/batal", [])->assertUnprocessable();
        $this->actingAs($petugas)->postJson("/keuangan/pembayaran/{$r->json('id')}/batal", ['alasan' => 'Salah catat'])->assertOk();
        $this->assertSame('belum', $t->fresh()->status);
        $this->assertSame(1, Pembayaran::count());
    }

    public function test_nis_siswa_tidak_ditemukan_di_laporan(): void
    {
        $this->actingAs($this->staf(Role::Yayasan))->getJson('/laporan/siswa/000')->assertNotFound();
        $this->assertSame(0, Siswa::count());
    }
}
