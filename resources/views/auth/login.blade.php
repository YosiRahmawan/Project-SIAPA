<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · SIAPA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-bg font-sans text-ink flex items-center justify-center p-4">
    <form method="POST" action="/login" class="w-full max-w-sm bg-surface rounded-[--radius-hero] shadow-card p-8 space-y-5">
        @csrf
        <div class="flex items-center gap-3">
            <div class="size-10 rounded-xl bg-primary text-gold grid place-items-center text-xl font-bold">S</div>
            <div>
                <h1 class="text-xl font-bold text-navy-900">SIAPA</h1>
                <p class="text-xs text-muted">Pembayaran sekolah</p>
            </div>
        </div>

        <label class="block space-y-1.5">
            <span class="text-sm font-medium text-ink-2">Username (NIS untuk siswa)</span>
            <input name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                   class="w-full h-11 rounded-full border border-line px-4 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
        </label>
        <label class="block space-y-1.5">
            <span class="text-sm font-medium text-ink-2">Password</span>
            <input name="password" type="password" required autocomplete="current-password"
                   class="w-full h-11 rounded-full border border-line px-4 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
        </label>

        @error('username')
            <p class="text-sm text-tunggak bg-tunggak-bg rounded-xl px-4 py-2" role="alert">{{ $message }}</p>
        @enderror

        <button class="w-full h-11 rounded-full bg-primary text-white font-semibold hover:bg-navy-700 transition">Masuk</button>
    </form>
</body>
</html>
