@extends('layouts.app')

@section('title', 'Admin: Users - megakomsel.com')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-ink">Kelola User</h1>
    <div class="flex gap-3 text-sm items-center">
        <a href="{{ route('admin.tenants.index') }}" class="text-muted hover:text-teal-600">Tenants</a>
        <a href="{{ route('admin.payments.index') }}" class="text-muted hover:text-teal-600">Pembayaran</a>
        <a href="{{ route('admin.apps.index') }}" class="text-muted hover:text-teal-600">Aplikasi</a>
        <a href="{{ route('admin.users.index') }}" class="text-teal-600 font-medium border-b-2 border-teal-600">Users</a>
        <a href="{{ route('admin.users.create') }}"
           class="bg-teal-600 hover:bg-teal-700 text-white font-medium px-4 py-2 rounded-lg">+ Tambah User</a>
    </div>
</div>

@if (session('status'))
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="GET" class="mb-6 flex flex-wrap gap-2">
    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari nama / email / telepon"
           class="rounded-lg border border-line-strong px-3 py-2 text-sm w-full md:w-72">
    <select name="member_type" class="rounded-lg border border-line-strong px-3 py-2 text-sm">
        <option value="">Semua tipe</option>
        @foreach ($memberTypes as $type)
            <option value="{{ $type }}" @selected(($filters['member_type'] ?? '') === $type)>
                {{ ucfirst($type) }}
            </option>
        @endforeach
    </select>
    <button class="bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium px-4 py-2 rounded-lg">Cari</button>
    @if (($filters['search'] ?? '') || ($filters['member_type'] ?? ''))
        <a href="{{ route('admin.users.index') }}" class="text-sm text-muted hover:text-teal-600 self-center">Reset</a>
    @endif
</form>

<div class="bg-card rounded-xl shadow-sm border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-elevated text-left text-muted border-b">
            <tr>
                <th class="px-6 py-3">Nama</th>
                <th class="px-6 py-3">Email</th>
                <th class="px-6 py-3">Tipe Member</th>
                <th class="px-6 py-3">Terdaftar</th>
                <th class="px-6 py-3">Tenant</th>
                <th class="px-6 py-3">Role</th>
                <th class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($users as $user)
                <tr>
                    <td class="px-6 py-3 font-medium">{{ $user->name }}</td>
                    <td class="px-6 py-3">{{ $user->email }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs
                            @if ($user->member_type === 'perusahaan') bg-indigo-100 text-indigo-700
                            @elseif ($user->member_type === 'umkm') bg-amber-100 text-amber-700
                            @else bg-elevated text-muted @endif">
                            {{ $user->memberTypeLabel() }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-muted">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-6 py-3 text-muted">{{ $user->tenants_count }}</td>
                    <td class="px-6 py-3">
                        @if ($user->is_admin)
                            <span class="px-2 py-0.5 rounded-full text-xs bg-purple-100 text-purple-700">superadmin</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs bg-teal-100 text-teal-700">member</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-teal-600 hover:underline">Edit</a>
                        @unless ($user->is(auth()->user()))
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                  onsubmit="return confirm('Hapus user {{ addslashes($user->name) }}?');">
                                @csrf
                                @method('DELETE')
                                <button class="text-red-600 hover:underline ml-3">Hapus</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-8 text-center text-muted">Tidak ada user.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $users->links() }}</div>
@endsection
