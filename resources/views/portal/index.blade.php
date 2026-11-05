@php
    $role = auth()->user()->role->value;
    $roleLabel = ['admin'=>'Administrator','yayasan'=>'Yayasan','siswa'=>'Siswa / Wali','keuangan'=>'Petugas Keuangan'][$role];
    $menus = match($role) {
        'admin' => [['/admin/pengguna','Pengguna','users','pengguna'],['/admin/siswa','Data siswa','users','siswa']],
        'siswa' => [['/siswa/tagihan','Tagihan saya','receipt','tagihan'],['/siswa/riwayat','Riwayat pembayaran','credit-card','riwayat'],['/siswa/peringatan','Pengingat tagihan','bell','peringatan']],
        default => [['/laporan/rekap','Ringkasan laporan','layout-dashboard','rekap'],['/laporan/pembayaran','Riwayat pembayaran','credit-card','pembayaran'],['/laporan/tunggakan','Tunggakan siswa','alert-circle','tunggakan'], ...($role === 'yayasan' ? [['/yayasan/pembatalan','Pembatalan','receipt','pembatalan']] : [])],
    };
    $judul = ['pengguna'=>'Pengguna sistem','siswa'=>'Data siswa','tagihan'=>'Tagihan saya','riwayat'=>'Riwayat pembayaran','peringatan'=>'Pengingat tagihan','rekap'=>'Ringkasan laporan','pembayaran'=>'Riwayat pembayaran','tunggakan'=>'Tunggakan siswa','pembatalan'=>'Pembatalan pembayaran','detail-siswa'=>'Rincian siswa'][$halaman];
    $deskripsi = ['pengguna'=>'Kelola akun dan akses staf sekolah.','siswa'=>'Kelola identitas siswa dan buat akun portal siswa.','tagihan'=>'Pantau tagihan sekolah dan pembayaran yang sudah diterima.','riwayat'=>'Catatan pembayaran untuk tagihan sekolah Anda.','peringatan'=>'Tagihan yang mendekati atau melewati jatuh tempo.','rekap'=>'Pantau penerimaan dan sisa tagihan sekolah.','pembayaran'=>'Telusuri pembayaran berdasarkan siswa, tanggal, dan status.','tunggakan'=>'Tagihan belum lunas yang sudah melewati jatuh tempo.','pembatalan'=>'Jejak pembatalan beserta alasan dan petugas yang mencatat.','detail-siswa'=>'Rincian tagihan dan pembayaran siswa.'][$halaman];
    $uang = fn($n) => 'Rp'.number_format($n ?? 0,0,',','.');
    $jenis = ['spp'=>'SPP','daftar_ulang'=>'Daftar ulang','jemputan'=>'Jemputan'];
    $status = ['lunas'=>'Lunas','belum'=>'Belum lunas','belum_bayar'=>'Belum bayar','menunggu_verifikasi'=>'Menunggu verifikasi','hampir_jatuh_tempo'=>'Segera jatuh tempo','menunggak'=>'Menunggak','diterima'=>'Diterima','menunggu'=>'Menunggu verifikasi','ditolak'=>'Ditolak','dibatalkan'=>'Dibatalkan'];
    $awalTA = today()->month >= 7 ? today()->year : today()->year-1;
@endphp
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>{{ $judul }} · SIAPA</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="finance portal-page">
<a class="skip-link" href="#konten">Lewati navigasi</a>
<aside class="sidebar" id="sidebar">
    @include('partials.sidebar-brand', ['beranda'=>auth()->user()->role->beranda()])
    <div class="sidebar-content" id="sidebar-content">
        <p class="nav-label">{{ $roleLabel }}</p>
        <nav aria-label="Menu {{ $roleLabel }}">
        @foreach($menus as [$href,$label,$icon,$key])
            <a href="{{ $href }}" class="nav-item {{ $halaman === $key ? 'active' : '' }}" title="{{ $label }}" @if($halaman === $key) aria-current="page" @endif><i class="ico" style="--icon:url('/icons/{{ $icon }}.svg')"></i><span>{{ $label }}</span></a>
        @endforeach
        </nav>
        <div class="sidebar-bottom"><div class="school"><strong>SDIT Abu Bakar</strong><small>Tahun ajaran {{ $awalTA }}/{{ $awalTA+1 }}</small></div><form method="post" action="/logout">@csrf<button class="nav-item logout" title="Keluar" aria-label="Keluar"><i class="ico" style="--icon:url('/icons/logout.svg')"></i><span>Keluar</span></button></form></div>
    </div>
</aside>
<div class="workspace">
<header class="topbar"><button type="button" class="icon-button mobile-menu" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false"><i class="ico" style="--icon:url('/icons/menu-2.svg')"></i></button><span class="portal-context">Portal {{ $roleLabel }}</span><span class="year-chip">TA {{ $awalTA }}/{{ $awalTA+1 }}</span><a href="/ganti-password" class="profile" title="Ganti kata sandi"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->nama,0,1)) }}</span><span><strong>{{ auth()->user()->nama }}</strong><small>{{ $roleLabel }}</small></span></a></header>
<main id="konten" class="main-content">
    <div class="page-heading"><div><h1>{{ $judul }}</h1><p>{{ $deskripsi }}</p></div>@if($role === 'admin')<button class="button primary" type="button" data-new="{{ $halaman }}">+ Tambah {{ $halaman === 'pengguna' ? 'pengguna' : 'siswa' }}</button>@endif</div>
    @if($errors->any())<div class="portal-form-error" role="alert">{{ $errors->first() }}</div>@endif
    <div id="portal-feedback" role="status" aria-live="polite" hidden></div>
    @if($role === 'admin') @include('portal.admin')
    @elseif($role === 'siswa') @include('portal.siswa')
    @else @include('portal.laporan') @endif
    @if($data instanceof \Illuminate\Contracts\Pagination\Paginator)
        <nav class="portal-pagination" aria-label="Halaman data"><span>{{ $data->firstItem() ?? 0 }}–{{ $data->lastItem() ?? 0 }} dari {{ $data->total() }} data</span><div>@if($data->previousPageUrl())<a class="button" href="{{ $data->previousPageUrl() }}">Sebelumnya</a>@endif @if($data->nextPageUrl())<a class="button" href="{{ $data->nextPageUrl() }}">Berikutnya</a>@endif</div></nav>
    @endif
    <footer class="page-footer">SIAPA · Administrasi Pembayaran Sekolah</footer>
</main></div>
</body></html>
