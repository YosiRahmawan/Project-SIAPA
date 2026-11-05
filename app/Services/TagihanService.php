<?php

namespace App\Services;

use App\Enums\JenisTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\Tarif;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TagihanService
{
    /** Buat tagihan SPP bulan $periode (YYYY-MM) untuk semua siswa aktif. Aman dipanggil ulang. */
    public function generateSpp(string $periode): int
    {
        $bulan = $this->bulan($periode);

        return $this->generate(JenisTagihan::Spp, $periode, $bulan->day((int) config('siapa.tanggal_jatuh_tempo')));
    }

    /** Buat tagihan daftar ulang tahun $tahun. Hanya kelas yang punya tarif (kelas 2 sampai 6). */
    public function generateDaftarUlang(int $tahun): int
    {
        $bulan = (int) config('siapa.bulan_daftar_ulang');
        $periode = sprintf('%04d-%02d', $tahun, $bulan);

        return $this->generate(JenisTagihan::DaftarUlang, $periode, $this->bulan($periode)->day((int) config('siapa.tanggal_jatuh_tempo')));
    }

    /** Jemputan: nominal diinput manual oleh Petugas Keuangan, tidak dari tabel tarif. */
    public function buatJemputan(Siswa $siswa, string $periode, int $nominal): Tagihan
    {
        $this->pastikanNominal($nominal);
        $jatuhTempo = $this->bulan($periode)->day((int) config('siapa.tanggal_jatuh_tempo'));

        $tagihan = Tagihan::firstOrCreate(
            ['nis' => $siswa->nis, 'jenis' => JenisTagihan::Jemputan, 'periode' => $periode],
            ['nominal' => $nominal, 'jatuh_tempo' => $jatuhTempo, 'status' => Tagihan::BELUM],
        );
        if (! $tagihan->wasRecentlyCreated) {
            $this->gagal('Tagihan jemputan periode ini sudah ada untuk siswa tersebut.');
        }
        Audit::catat('tagihan.buat', 'tagihan', $tagihan->id, null, $tagihan->only('nis', 'jenis', 'periode', 'nominal'));

        return $tagihan;
    }

    /** Ubah nominal jemputan, hanya selama belum ada pembayaran yang masuk atau menunggu. */
    public function ubahNominalJemputan(Tagihan $tagihan, int $nominal): Tagihan
    {
        $this->pastikanNominal($nominal);
        if ($tagihan->jenis !== JenisTagihan::Jemputan) {
            $this->gagal('Hanya nominal jemputan yang boleh diubah.');
        }

        return DB::transaction(function () use ($tagihan, $nominal) {
            $tagihan = Tagihan::whereKey($tagihan->id)->lockForUpdate()->firstOrFail();
            if ($tagihan->pembayaran()->exists()) {
                $this->gagal('Nominal tidak bisa diubah karena sudah ada pembayaran.');
            }
            $sebelum = ['nominal' => $tagihan->nominal];
            $tagihan->update(['nominal' => $nominal]);
            Audit::catat('tagihan.ubah_nominal', 'tagihan', $tagihan->id, $sebelum, ['nominal' => $nominal]);

            return $tagihan;
        });
    }

    /** Lunas bila jumlah pembayaran yang diterima sama dengan nominal. */
    public function hitungUlang(Tagihan $tagihan): Tagihan
    {
        $status = $tagihan->dibayar() >= $tagihan->nominal ? Tagihan::LUNAS : Tagihan::BELUM;
        if ($tagihan->status !== $status) {
            $sebelum = ['status' => $tagihan->status];
            $tagihan->update(['status' => $status]);
            Audit::catat('tagihan.status', 'tagihan', $tagihan->id, $sebelum, ['status' => $status]);
        }

        return $tagihan;
    }

    private function generate(JenisTagihan $jenis, string $periode, CarbonImmutable $jatuhTempo): int
    {
        return DB::transaction(function () use ($jenis, $periode, $jatuhTempo) {
            $dibuat = 0;
            foreach (Siswa::where('aktif', true)->get() as $siswa) {
                $nominal = Tarif::nominal($jenis->value, $siswa->kelas);
                if ($nominal === null) {
                    continue;
                }
                $t = Tagihan::firstOrCreate(
                    ['nis' => $siswa->nis, 'jenis' => $jenis, 'periode' => $periode],
                    ['nominal' => $nominal, 'jatuh_tempo' => $jatuhTempo, 'status' => Tagihan::BELUM],
                );
                $dibuat += $t->wasRecentlyCreated ? 1 : 0;
            }
            Audit::catat('tagihan.generate', 'tagihan', null, null, ['jenis' => $jenis->value, 'periode' => $periode, 'dibuat' => $dibuat]);

            return $dibuat;
        });
    }

    private function bulan(string $periode): CarbonImmutable
    {
        $bulan = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periode) ? CarbonImmutable::createFromFormat('!Y-m', $periode) : null;
        if (! $bulan) {
            $this->gagal('Periode harus berformat YYYY-MM, misalnya 2026-11.');
        }

        return $bulan;
    }

    private function pastikanNominal(int $nominal): void
    {
        if ($nominal <= 0) {
            $this->gagal('Nominal harus lebih dari nol.');
        }
    }

    private function gagal(string $pesan): never
    {
        throw ValidationException::withMessages(['tagihan' => $pesan]);
    }
}
