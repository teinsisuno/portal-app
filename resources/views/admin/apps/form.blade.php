@extends('layouts.app')

@section('title', 'Admin: Form Aplikasi - megakomsel.com')

@section('content')
<a href="{{ route('admin.apps.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali</a>
<h1 class="text-2xl font-bold text-slate-800 mt-2 mb-6">{{ $app ? 'Edit Aplikasi' : 'Tambah Aplikasi' }}</h1>

<form method="POST"
      action="{{ $app ? route('admin.apps.update', $app) : route('admin.apps.store') }}"
      class="max-w-xl bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
    @csrf
    @if ($app) @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Nama</label>
        <input type="text" name="name" value="{{ old('name', $app->name ?? '') }}" required
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
        <textarea name="description" rows="3" required
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('description', $app->description ?? '') }}</textarea>
        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Harga / Bulan (Rp)</label>
            <input type="number" step="0.01" min="0" name="price_monthly"
                   value="{{ old('price_monthly', $app->price_monthly ?? '') }}" required
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            @error('price_monthly') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="available" @selected(($app->status ?? 'available') === 'available')>Available</option>
                <option value="coming_soon" @selected(($app->status ?? '') === 'coming_soon')>Coming Soon</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Logo URL (opsional)</label>
        <input type="url" name="logo" value="{{ old('logo', $app->logo ?? '') }}"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
        Simpan
    </button>
</form>
@endsection
