<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'megakomsel.com')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <span class="font-bold text-slate-800">megakomsel<span class="text-blue-600">.com</span></span>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('dashboard') }}" class="text-slate-600 hover:text-blue-600">Dashboard</a>
                <a href="{{ route('apps.index') }}" class="text-slate-600 hover:text-blue-600">Aplikasi</a>
                <a href="{{ route('subscriptions.index') }}" class="text-slate-600 hover:text-blue-600">Langganan</a>
                <a href="{{ route('payments.index') }}" class="text-slate-600 hover:text-blue-600">Pembayaran</a>
                @auth
                    @if (auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.tenants.index') }}" class="text-slate-600 hover:text-blue-600">Admin</a>
                    @endif
                @endauth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-slate-500 hover:text-red-600">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @yield('content')
    </main>
</body>
</html>
