<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti password · SIAPA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg font-sans text-ink flex items-center justify-center p-4">
    <form method="POST" action="/ganti-password" class="w-full max-w-sm bg-surface rounded-[--radius-hero] shadow-card p-8 space-y-5">
        @csrf
        <div>
            <h1 class="text-xl font-bold text-navy-900">Ganti password</h1>
            <p class="text-sm text-muted">Minimal 8 karakter, memuat huruf dan angka.</p>
        </div>

        @foreach ([['password_lama', 'Password saat ini'], ['password', 'Password baru'], ['password_confirmation', 'Ulangi password baru']] as [$nama, $label])
            <label class="block space-y-1.5">
                <span class="text-sm font-medium text-ink-2">{{ $label }}</span>
                <input name="{{ $nama }}" type="password" required
                       class="w-full h-11 rounded-full border border-line px-4 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            </label>
        @endforeach

        @if ($errors->any())
            <p class="text-sm text-tunggak bg-tunggak-bg rounded-xl px-4 py-2" role="alert">{{ $errors->first() }}</p>
        @endif

        <button class="w-full h-11 rounded-full bg-primary text-white font-semibold hover:bg-navy-700 transition">Simpan</button>
    </form>
</body>
</html>
