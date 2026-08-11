@extends('layouts.app')

@section('title', 'Admin: Users - megakomsel.com')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Kelola User</h1>
    <div class="flex gap-3 text-sm">
        <a href="{{ route('admin.tenants.index') }}" class="text-slate-500 hover:text-blue-600">Tenants</a>
        <a href="{{ route('admin.payments.index') }}" class="text-slate-500 hover:text-blue-600">Pembayaran</a>
        <a href="{{ route('admin.apps.index') }}" class="text-slate-500 hover:text-blue-600">Aplikasi</a>
        <a href="{{ route('admin.users.index') }}" class="text-blue-600 font-medium border-b-2 border-blue-600">Users</a>
    </div>
</div>

<form method="GET" class="mb-6">
    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama / email"
           class="w-full md:w-96 rounded-lg border border-slate-300 px-3 py-2 text-sm">
    <button class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg ml-2">Cari</button>
</form>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500 border-b">
            <tr>
                <th class="px-6 py-3">Nama</th>
                <th class="px-6 py-3">Email</th>
                <th class="px-6 py-3">Terdaftar</th>
                <th class="px-6 py-3">Tenant</th>
                <th class="px-6 py-3">Admin</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
                <tr>
                    <td class="px-6 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-6 py-3">{{ $user->email }}</td>
                    <td class="px-6 py-3 text-slate-600">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-3 text-slate-600">{{ $user->tenants_count }}</td>
                    <td class="px-6 py-3">
                        @if ($user->is_admin)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-700">superadmin</span>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-slate-500">Tidak ada user.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
