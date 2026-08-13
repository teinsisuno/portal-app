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
<body class="bg-surface text-ink min-h-screen font-sans antialiased">

    {{-- ============ SIDEBAR (offcanvas mobile) ============ --}}
    <div id="sidebar-backdrop" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-line bg-sidebar">
        {{-- Logo --}}
        <div class="flex h-16 items-center gap-2 border-b border-line px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-lg font-extrabold text-white">M</span>
            <span class="font-bold text-ink">megakomsel<span class="text-primary">.com</span></span>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium
                      {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('apps.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium
                      {{ request()->routeIs('apps.*') ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm3 1v4m0 4v.01M12 7v.01M12 15v.01M17 7v.01M17 15v.01"/>
                </svg>
                Aplikasi
            </a>

            <a href="{{ route('subscriptions.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium
                      {{ request()->routeIs('subscriptions.*') ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M9 8h6M5 4h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5a1 1 0 011-1z"/>
                </svg>
                Langganan
            </a>

            <a href="{{ route('payments.index') }}"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium
                      {{ request()->routeIs('payments.*') ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h4m-4 4h10a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Pembayaran
            </a>

            @auth
                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('admin.tenants.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium
                              {{ request()->routeIs('admin.*') ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.3a1 1 0 011.4 0l6 6a1 1 0 010 1.4l-6 6a1 1 0 01-1.4-1.4L14.6 12l-5.3-5.3a1 1 0 010-1.4zM4 5a1 1 0 011-1h3a1 1 0 010 2H5a1 1 0 01-1-1z"/>
                        </svg>
                        Admin Panel
                    </a>
                @else
                    <a href="{{ route('member.index') }}"
                       class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium
                              {{ request()->routeIs('member.*') ? 'bg-primary/10 text-primary' : 'text-muted hover:bg-elevated hover:text-ink' }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Area Member
                    </a>
                @endif
            @endauth
        </nav>

        {{-- Footer user --}}
        <div class="border-t border-line p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-elevated text-sm font-semibold text-primary">
                    {{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ink">{{ auth()->user()?->name }}</p>
                    <p class="truncate text-xs text-soft">
                        @if (auth()->user()?->isSuperAdmin()) superadmin @else {{ auth()->user()?->memberTypeLabel() }} @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-soft hover:text-danger" title="Keluar">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ============ MAIN AREA ============ --}}
    <div class="lg:pl-72">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-sidebar/80 px-4 backdrop-blur md:px-8">
            <button type="button" id="sidebar-toggle"
                    class="rounded-lg p-2 text-muted hover:bg-elevated hover:text-ink lg:hidden">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <h1 class="flex-1 truncate text-base font-semibold text-ink">
                @yield('page-title', config('app.name', 'megakomsel.com'))
            </h1>

            <button type="button" id="theme-toggle"
                    class="rounded-lg p-2 text-muted hover:bg-elevated hover:text-ink" title="Ganti tema">
                {{-- sun (muncul saat dark) --}}
                <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.4 6.4l-.7-.7M6.3 6.3l-.7-.7m12.1 0l-.7.7M6.3 17.7l-.7.7M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                {{-- moon (muncul saat light) --}}
                <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.4 14.5A8.5 8.5 0 019.5 3.6 8.5 8.5 0 1020.4 14.5z"/>
                </svg>
            </button>
        </header>

        <main class="mx-auto max-w-6xl p-4 md:p-8">
            @yield('content')
        </main>
    </div>

    <script>
        // Sidebar offcanvas toggle (mobile)
        const toggleBtn = document.getElementById('sidebar-toggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => document.body.classList.toggle('sidebar-open'));
        }
        const backdrop = document.getElementById('sidebar-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', () => document.body.classList.remove('sidebar-open'));
        }

        // Dark mode toggle
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
