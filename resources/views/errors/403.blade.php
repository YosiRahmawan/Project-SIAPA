<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Akses halaman · SIAPA</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
<main class="panel" style="width:100%;max-width:540px;padding:32px">
    <a class="login-logo" href="/" aria-label="Beranda SIAPA"><img src="{{ asset('images/siapa-logo.png') }}" width="199" height="69" alt="SIAPA"></a>
    <p class="login-eyebrow">AKSES HALAMAN</p>
    <h1>Halaman ini untuk role lain</h1>
    @auth
        @php $labelRole = ['admin'=>'Administrator','keuangan'=>'Petugas Keuangan','yayasan'=>'Yayasan','siswa'=>'Siswa / Wali'][auth()->user()->role->value]; @endphp
        <p class="login-intro">Anda masuk sebagai <strong>{{ auth()->user()->nama }}</strong> ({{ $labelRole }}). Akun ini tidak memiliki akses ke halaman yang diminta.</p>
        <a class="button primary" href="{{ auth()->user()->role->beranda() }}">Buka beranda saya</a>
        <form method="post" action="{{ route('logout') }}" style="margin-top:12px">@csrf<button type="submit" class="button">Keluar dan ganti akun</button></form>
    @else
        <p class="login-intro">Silakan masuk dengan akun yang memiliki akses.</p>
        <a class="button primary" href="{{ route('login') }}">Masuk</a>
    @endauth
</main>
</body></html>
