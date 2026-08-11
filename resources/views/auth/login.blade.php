@extends('layouts.auth')

@section('title', 'Masuk - megakomsel.com')

@section('content')
<h2 class="text-xl font-semibold text-slate-800 mb-6">Masuk ke Akun</h2>

<form method="POST" action="{{ route('login') }}" class="space-y-4">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input id="password" type="password" name="password" required autocomplete="current-password"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div class="flex items-center justify-between">
        <label class="flex items-center text-sm text-slate-600">
            <input type="checkbox" name="remember" class="rounded border-slate-300 mr-2">
            Ingat saya
        </label>
        <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:underline">Lupa password?</a>
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
        Masuk
    </button>

    <p class="text-sm text-slate-500 text-center mt-4">
        Belum punya akun?
        <a href="{{ route('register') }}" class="text-blue-600 hover:underline">Daftar gratis</a>
    </p>
</form>
@endsection
