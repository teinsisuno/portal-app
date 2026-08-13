<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'megakomsel.com')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Dark mode init — cegah flash sebelum CSS render
        (function () {
            const saved = localStorage.getItem('theme');
            const forced = new URLSearchParams(location.search).get('dark');
            const wantsDark = forced !== null
                ? forced === '1'
                : saved === 'dark' || (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (wantsDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-surface text-ink min-h-screen flex items-center justify-center py-12 font-sans antialiased">
    <div class="w-full max-w-md px-4">
        <div class="mb-6 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary text-xl font-extrabold text-white">M</span>
            <h1 class="mt-3 text-2xl font-bold text-ink">megakomsel<span class="text-primary">.com</span></h1>
            <p class="mt-1 text-sm text-muted">Platform aplikasi untuk bisnis kamu</p>
        </div>

        <div class="card p-8">
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-line bg-elevated p-3 text-sm text-ink">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-lg border border-danger/30 bg-danger/10 p-3 text-sm text-danger">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>

        <p class="mt-6 text-center text-xs text-soft">© {{ date('Y') }} megakomsel.com</p>
    </div>

    <button type="button" id="theme-toggle"
            class="fixed right-4 top-4 rounded-lg p-2 text-muted hover:bg-elevated" title="Ganti tema">
        <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.4 6.4l-.7-.7M6.3 6.3l-.7-.7m12.1 0l-.7.7M6.3 17.7l-.7.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.4 14.5A8.5 8.5 0 019.5 3.6 8.5 8.5 0 1020.4 14.5z"/>
        </svg>
    </button>

    <script>
        const themeBtn = document.getElementById('theme-toggle');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        }
    </script>
</body>
</html>
