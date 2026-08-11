@extends('layouts.app')

@section('title', 'Admin: Tenants - megakomsel.com')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Kelola Tenant</h1>
    <div class="flex gap-3 text-sm">
        <a href="{{ route('admin.tenants.index') }}" class="text-blue-600 font-medium border-b-2 border-blue-600">Tenants</a>
        <a href="{{ route('admin.payments.index') }}" class="text-slate-500 hover:text-blue-600">Pembayaran</a>
        <a href="{{ route('admin.apps.index') }}" class="text-slate-500 hover:text-blue-600">Aplikasi</a>
        <a href="{{ route('admin.users.index') }}" class="text-slate-500 hover:text-blue-600">Users</a>
    </div>
</div>

@if (session('status'))
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        {{ session('status') }}
    </div>
@endif

<form method="GET" class="mb-6 flex gap-3">
    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama / slug / email"
           class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua status</option>
        @foreach (['pending', 'active', 'suspended'] as $s)
            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg">Cari</button>
</form>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500 border-b">
            <tr>
                <th class="px-6 py-3">Tenant</th>
                <th class="px-6 py-3">Slug</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Langganan</th>
                <th class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($tenants as $tenant)
                <tr>
                    <td class="px-6 py-3">
                        <p class="font-medium text-slate-800">{{ $tenant->name }}</p>
                        <p class="text-xs text-slate-500">{{ $tenant->email }}</p>
                    </td>
                    <td class="px-6 py-3 text-slate-600 font-mono text-xs">{{ $tenant->slug }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs
                            @if ($tenant->status === 'active') bg-emerald-100 text-emerald-700
                            @elseif ($tenant->status === 'pending') bg-amber-100 text-amber-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ $tenant->status }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-slate-600">{{ $tenant->subscriptions_count }}</td>
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-blue-600 hover:underline">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Tidak ada tenant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $tenants->links() }}</div>
@endsection
