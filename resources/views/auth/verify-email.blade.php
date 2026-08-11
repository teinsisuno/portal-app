@extends('layouts.auth')

@section('title', 'Verifikasi Email - megakomsel.com')

@section('content')
<h2 class="text-xl font-semibold text-slate-800 mb-4">Verifikasi Email Kamu</h2>

@if (session('status') == 'verification-link-sent')
    <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        Link verifikasi baru sudah dikirim ke email kamu.
    </div>
@endif

<div class="text-sm text-slate-600 space-y-3">
    <p>
        Sebelum lanjut, cek email kamu untuk link verifikasi.
        Belum terima? Klik tombol di bawah untuk kirim ulang.
    </p>
</div>

<form method="POST" action="{{ route('verification.send') }}" class="mt-6">
    @csrf
    <button type="submit"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
        Kirim Ulang Link Verifikasi
    </button>
</form>

<form method="POST" action="{{ route('logout') }}" class="mt-4">
    @csrf
    <button type="submit" class="w-full text-sm text-slate-500 hover:text-slate-700">
        Keluar
    </button>
</form>
@endsection
