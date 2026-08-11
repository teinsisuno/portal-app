@extends('layouts.auth')

@section('title', 'Reset Password - megakomsel.com')

@section('content')
<h2 class="text-xl font-semibold text-slate-800 mb-6">Buat Password Baru</h2>

<form method="POST" action="{{ route('password.store') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <div>
        <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password Baru</label>
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
        Simpan Password Baru
    </button>
</form>
@endsection
