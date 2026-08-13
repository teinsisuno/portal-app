@extends('layouts.auth')

@section('title', 'Daftar - megakomsel.com')

@section('content')
<h2 class="text-xl font-semibold text-ink mb-6">Buat Akun Baru</h2>

<form method="POST" action="{{ route('register') }}" class="space-y-4">
    @csrf

    <div>
        <label for="name" class="block text-sm font-medium text-ink mb-1">Nama / Nama Bisnis</label>
        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
               class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
    </div>

    <div>
        <label for="email" class="block text-sm font-medium text-ink mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required
               class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-ink mb-1">Password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
               class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
    </div>

    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-ink mb-1">Konfirmasi Password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required
               class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
    </div>

    <button type="submit"
            class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 rounded-lg transition">
        Daftar
    </button>

    <p class="text-sm text-muted text-center mt-4">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-teal-600 hover:underline">Masuk</a>
    </p>
</form>
@endsection
