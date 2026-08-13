@extends('layouts.app')

@section('title', 'Area Member - megakomsel.com')

@section('content')
@if (session('status'))
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ {{ session('status') }}
    </div>
@endif

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-ink">Area Member</h1>
    <span class="px-3 py-1 rounded-full text-xs
        @if ($user->member_type === 'perusahaan') bg-indigo-100 text-indigo-700
        @elseif ($user->member_type === 'umkm') bg-amber-100 text-amber-700
        @else bg-elevated text-muted @endif">
        Member {{ $user->memberTypeLabel() }}
    </span>
</div>

<div class="bg-card rounded-xl shadow-sm border border-line p-6 mb-6">
    <h2 class="font-semibold text-ink mb-4">Profil</h2>

    @if ($errors->any())
        <div class="mb-4 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('member.profile.update') }}" class="space-y-4 max-w-lg">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                   class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Email</label>
            <input type="email" value="{{ $user->email }}" disabled
                   class="w-full rounded-lg border border-line bg-elevated px-3 py-2 text-sm text-muted">
            <p class="text-xs text-soft mt-1">Email tidak bisa diubah sendiri — hubungi admin.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">No. Telepon</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                   class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Tipe Member</label>
            <select name="member_type" required
                    class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                @foreach (['individu', 'umkm', 'perusahaan'] as $type)
                    <option value="{{ $type }}" @selected(old('member_type', $user->member_type) === $type)>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-soft mt-1">Individu (pribadi) · UMKM (usaha kecil-menengah) · Perusahaan (badan usaha)</p>
        </div>

        <button class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
            Simpan Profil
        </button>
    </form>
</div>

<div class="bg-card rounded-xl shadow-sm border border-line p-6">
    <h2 class="font-semibold text-ink mb-4">Ganti Password</h2>

    <form method="POST" action="{{ route('member.password.update') }}" class="space-y-4 max-w-lg">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Password Saat Ini</label>
            <input type="password" name="current_password" required
                   class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Password Baru</label>
            <input type="password" name="password" required
                   class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-ink mb-1">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation" required
                   class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
        </div>

        <button class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
            Ganti Password
        </button>
    </form>
</div>
@endsection
