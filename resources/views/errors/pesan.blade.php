<!doctype html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $judul }} · SIAPA</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="login-page">
<main class="panel" style="width:100%;max-width:540px;padding:32px">
    <a class="login-logo" href="/" aria-label="Beranda SIAPA"><img src="{{ asset('images/siapa-logo.png') }}" width="199" height="69" alt="SIAPA"></a>
    <p class="login-eyebrow">{{ $kode }}</p>
    <h1>{{ $judul }}</h1>
    <p class="login-intro">{{ $teks }}</p>
    <a class="button primary" href="{{ auth()->check() ? auth()->user()->role->beranda() : route('login') }}">{{ auth()->check() ? 'Buka beranda saya' : 'Masuk' }}</a>
</main>
</body></html>
