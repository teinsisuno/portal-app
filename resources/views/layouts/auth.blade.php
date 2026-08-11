<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'megakomsel.com')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-slate-800">megakomsel<span class="text-blue-600">.com</span></h1>
            <p class="text-slate-500 mt-1">Platform aplikasi untuk bisnis kamu</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
            @if (session('status'))
                <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">© {{ date('Y') }} megakomsel.com</p>
    </div>
</body>
</html>
