@extends('layouts.app')

@section('title', $user ? 'Edit User - megakomsel.com' : 'Tambah User - megakomsel.com')

@section('content')
<div class="max-w-2xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-slate-800">{{ $user ? 'Edit User' : 'Tambah User' }}</h1>
        <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-blue-600">← Kembali</a>
    </div>

    @if ($errors->any())
        <div class="mb-6 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ $user ? route('admin.users.update', $user) : route('admin.users.store') }}"
          class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-5">
        @csrf
        @if ($user)
            @method('PUT')
        @endif

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">No. Telepon</label>
            <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Tipe Member</label>
            <select name="member_type" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @foreach ($memberTypes as $type)
                    <option value="{{ $type }}" @selected(old('member_type', $user->member_type ?? '') === $type)>
                        {{ ucfirst($type) }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-slate-400 mt-1">Individu (pribadi) · UMKM (usaha kecil-menengah) · Perusahaan (badan usaha)</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">
                Password {{ $user ? '(kosongkan jika tidak diganti)' : '' }}
            </label>
            <input type="password" name="password" {{ $user ? '' : 'required' }}
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Konfirmasi Password</label>
            <input type="password" name="password_confirmation" {{ $user ? '' : 'required' }}
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
            <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin ?? false))
                   class="rounded border-slate-300">
            Jadikan superadmin (akses panel /admin)
        </label>

        <div class="flex items-center gap-3 pt-2">
            <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                {{ $user ? 'Simpan Perubahan' : 'Buat User' }}
            </button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-blue-600">Batal</a>
        </div>
    </form>
</div>
@endsection
