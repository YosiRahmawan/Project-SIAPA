<?php

namespace App\Services;

use App\Enums\Metode;
use App\Enums\Role;
use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Semua perubahan uang lewat sini. Setiap aksi: satu transaksi database, baris tagihan
 * dikunci, hanya Petugas Keuangan, dan dicatat di audit log.
 */
class PembayaranService
{
    public function __construct(private TagihanService $tagihan) {}

    public function catatManual(Tagihan $tagihan, int $jumlah, User $petugas): Pembayaran
    {
        return $this->simpan($tagihan, Metode::Manual, $jumlah, StatusPembayaran::Diterima, null, $petugas);
    }

    /** Bukti QRIS diunggah petugas, menunggu diperiksa. Belum dihitung lunas sebelum diterima. */
    public function ajukanQris(Tagihan $tagihan, int $jumlah, UploadedFile $bukti, User $petugas): Pembayaran
    {
        $disk = Storage::disk(config('siapa.disk_bukti'));
        $path = $disk->putFileAs('bukti', $bukti, Str::random(40).'.'.$bukti->guessExtension());

        try {
            return $this->simpan($tagihan, Metode::Qris, $jumlah, StatusPembayaran::Menunggu, $path, $petugas);
        } catch (Throwable $e) {
            $disk->delete($path);
            throw $e;
        }
    }

    /** Periksa bukti QRIS: terima atau tolak. */
    public function putuskan(Pembayaran $pembayaran, bool $terima, User $petugas, ?string $alasan = null): Pembayaran
    {
        $this->pastikanPetugas($petugas);

        return DB::transaction(function () use ($pembayaran, $terima, $alasan) {
            $tagihan = Tagihan::whereKey($pembayaran->tagihan_id)->lockForUpdate()->firstOrFail();
            $pembayaran = Pembayaran::findOrFail($pembayaran->id);
            if ($pembayaran->status !== StatusPembayaran::Menunggu) {
                $this->gagal('Hanya pembayaran yang menunggu verifikasi yang bisa diterima atau ditolak.');
            }
            $baru = $terima ? StatusPembayaran::Diterima : StatusPembayaran::Ditolak;
            $pembayaran->update(['status' => $baru, 'alasan' => $terima ? null : $alasan]);
            $this->tagihan->hitungUlang($tagihan);
            Audit::catat($terima ? 'pembayaran.terima' : 'pembayaran.tolak', 'pembayaran', $pembayaran->id,
                ['status' => StatusPembayaran::Menunggu->value], ['status' => $baru->value, 'alasan' => $alasan]);

            return $pembayaran;
        });
    }

    /** Salah catat: status jadi dibatalkan dengan alasan, data lama tetap tersimpan. */
    public function batalkan(Pembayaran $pembayaran, string $alasan, User $petugas): Pembayaran
    {
        $this->pastikanPetugas($petugas);
        if (trim($alasan) === '') {
            $this->gagal('Alasan pembatalan wajib diisi.');
        }

        return DB::transaction(function () use ($pembayaran, $alasan) {
            $tagihan = Tagihan::whereKey($pembayaran->tagihan_id)->lockForUpdate()->firstOrFail();
            $pembayaran = Pembayaran::findOrFail($pembayaran->id);
            if ($pembayaran->status !== StatusPembayaran::Diterima) {
                $this->gagal('Hanya pembayaran yang sudah diterima yang bisa dibatalkan.');
            }
            $pembayaran->update(['status' => StatusPembayaran::Dibatalkan, 'alasan' => $alasan]);
            $this->tagihan->hitungUlang($tagihan);
            // Aksi ini ditampilkan ke Yayasan sebagai peringatan pembatalan.
            Audit::catat('pembayaran.batal', 'pembayaran', $pembayaran->id,
                ['status' => StatusPembayaran::Diterima->value, 'jumlah' => $pembayaran->jumlah, 'tagihan_id' => $tagihan->id],
                ['status' => StatusPembayaran::Dibatalkan->value, 'alasan' => $alasan]);

            return $pembayaran;
        });
    }

    private function simpan(Tagihan $tagihan, Metode $metode, int $jumlah, StatusPembayaran $status, ?string $bukti, User $petugas): Pembayaran
    {
        $this->pastikanPetugas($petugas);

        return DB::transaction(function () use ($tagihan, $metode, $jumlah, $status, $bukti, $petugas) {
            // Kunci baris tagihan supaya dua petugas tidak mencatat pembayaran yang sama bersamaan.
            $tagihan = Tagihan::whereKey($tagihan->id)->lockForUpdate()->firstOrFail();
            if ($tagihan->lunas()) {
                $this->gagal('Tagihan ini sudah lunas.');
            }
            if ($jumlah <= 0) {
                $this->gagal('Jumlah pembayaran harus lebih dari nol.');
            }
            if ($jumlah > $tagihan->sisa()) {
                $this->gagal('Jumlah melebihi sisa tagihan ('.$tagihan->sisa().').');
            }
            $p = Pembayaran::create([
                'tagihan_id' => $tagihan->id,
                'metode' => $metode,
                'jumlah' => $jumlah,
                'bukti' => $bukti,
                'status' => $status,
                'dicatat_oleh' => $petugas->id,
                'tanggal' => today(),
            ]);
            $this->tagihan->hitungUlang($tagihan);
            Audit::catat('pembayaran.catat', 'pembayaran', $p->id, null,
                ['tagihan_id' => $tagihan->id, 'metode' => $metode->value, 'jumlah' => $jumlah, 'status' => $status->value]);

            return $p;
        });
    }

    /** Pencatat bukan pengelola pengguna: Admin tidak boleh mencatat pembayaran. */
    private function pastikanPetugas(User $user): void
    {
        abort_unless($user->adalah(Role::Keuangan), 403, 'Hanya Petugas Keuangan yang boleh mengubah pembayaran.');
    }

    private function gagal(string $pesan): never
    {
        throw ValidationException::withMessages(['pembayaran' => $pesan]);
    }
}
