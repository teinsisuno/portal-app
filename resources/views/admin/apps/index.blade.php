@extends('layouts.app')

@section('title', 'Admin: Aplikasi - megakomsel.com')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-ink">Kelola Aplikasi</h1>
    <div class="flex gap-3 text-sm">
        <a href="{{ route('admin.tenants.index') }}" class="text-muted hover:text-teal-600">Tenants</a>
        <a href="{{ route('admin.payments.index') }}" class="text-muted hover:text-teal-600">Pembayaran</a>
        <a href="{{ route('admin.apps.index') }}" class="text-teal-600 font-medium border-b-2 border-teal-600">Aplikasi</a>
        <a href="{{ route('admin.users.index') }}" class="text-muted hover:text-teal-600">Users</a>
    </div>
</div>

@if (session('status') == 'app-saved')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">✅ Aplikasi disimpan.</div>
@endif

<div class="mb-6">
    <a href="{{ route('admin.apps.create') }}" class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium px-4 py-2 rounded-lg">+ Tambah Aplikasi</a>
</div>

<div class="bg-card rounded-xl shadow-sm border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-elevated text-left text-muted border-b">
            <tr>
                <th class="px-6 py-3">Aplikasi</th>
                <th class="px-6 py-3">Slug</th>
                <th class="px-6 py-3">Harga</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Langganan</th>
                <th class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($apps as $app)
                <tr>
                    <td class="px-6 py-3 font-medium">{{ $app->name }}</td>
                    <td class="px-6 py-3 font-mono text-xs">{{ $app->slug }}</td>
                    <td class="px-6 py-3">Rp {{ number_format($app->price_monthly, 0, ',', '.') }}</td>
                    <td class="px-6 py-3">{{ $app->status }}</td>
                    <td class="px-6 py-3">{{ $app->subscriptions_count }}</td>
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.apps.edit', $app) }}" class="text-teal-600 hover:underline">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-8 text-center text-muted">Belum ada aplikasi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
