@extends('layouts.auth')

@section('title', 'Lupa Password - megakomsel.com')

@section('content')
<h2 class="text-xl font-semibold text-slate-800 mb-4">Reset Password</h2>

<p class="text-sm text-slate-600 mb-6">
    Masukkan email kamu, kami akan kirim link untuk reset password.
</p>

<form method="POST" action="{{ route('password.email') }}" class="space-y-4">
    @csrf

    <div>
        <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
    </div>

    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
        Kirim Link Reset
    </button>

    <p class="text-sm text-slate-500 text-center mt-4">
        <a href="{{ route('login') }}" class="text-blue-600 hover:underline">Kembali ke login</a>
    </p>
</form>
@endsection
