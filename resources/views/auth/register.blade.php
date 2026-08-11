@extends('layouts.auth')

@section('title', 'Daftar - megakomsel.com')

@section('content')
<h2 class="text-xl font-semibold text-slate-800 mb-6">Buat Akun Baru</h2>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-slate-700 mb-1">Nama / Nama Bisnis</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
        Daftar
    </button>

    <p class="text-sm text-slate-500 text-center mt-4">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Masuk</a>
    </p>
</form>
@endsection
