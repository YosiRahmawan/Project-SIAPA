<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SIAPA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page">
    <main class="login-shell">
        <section class="login-content" aria-labelledby="login-title">
            <a href="/login" class="login-logo" aria-label="SIAPA — halaman masuk"><img src="{{ asset('images/siapa-logo.png') }}" width="199" height="69" alt="SIAPA — Administrasi Pembayaran Sekolah"></a>
            <div class="login-form-area">
                <p class="login-eyebrow">PORTAL PEMBAYARAN SEKOLAH</p>
                <h1 id="login-title">Selamat datang</h1>
                <p class="login-intro">Masuk dengan akun sekolah untuk melanjutkan.</p>
                @if($errors->any())
                    <div class="login-error" id="login-error" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    <label for="username">Username atau NIS</label>
                    <input id="username" name="username" value="{{ old('username') }}" required autocomplete="username" placeholder="Masukkan username atau NIS" @if($errors->has('username')) aria-invalid="true" aria-describedby="login-error" @endif>
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" @if($errors->has('password')) aria-invalid="true" aria-describedby="login-error" @endif>
                    <button type="submit">Masuk</button>
                </form>
                <p class="login-help">Siswa dan wali menggunakan NIS siswa. Jika kesulitan masuk, hubungi admin sekolah.</p>
            </div>
            <footer class="login-footer">SDIT Abu Bakar Ash Shiddiq Rajapolah</footer>
        </section>
        <aside class="login-art" aria-label="Layanan sekolah SIAPA">
            <div class="login-art-heading"><p class="login-eyebrow">SIAPA <span>/</span> LAYANAN SEKOLAH</p><h2>Keuangan sekolah dalam satu portal.</h2></div>
            <img src="{{ asset('images/login-school.png') }}" alt="" width="1448" height="1086" fetchpriority="high">
        </aside>
    </main>
</body>
</html>
