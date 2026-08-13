@extends('layouts.app')

@section('title', 'Admin: Detail Tenant - megakomsel.com')

@section('content')
<a href="{{ route('admin.tenants.index') }}" class="text-sm text-teal-600 hover:underline">← Kembali</a>
<h1 class="text-2xl font-bold text-ink mt-2 mb-6">{{ $tenant->name }}</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-card rounded-xl shadow-sm border border-line p-6">
        <h2 class="font-semibold text-ink mb-3">Profil</h2>
        <dl class="text-sm space-y-2">
            <div class="flex justify-between"><dt class="text-muted">Slug</dt><dd class="font-mono">{{ $tenant->slug }}.megakomsel.com</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Email</dt><dd>{{ $tenant->email }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Telepon</dt><dd>{{ $tenant->phone ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Alamat</dt><dd>{{ $tenant->address ?? '-' }}</dd></div>
            <div class="flex justify-between"><dt class="text-muted">Status</dt><dd>{{ $tenant->status }}</dd></div>
        </dl>
    </div>
    <div class="bg-card rounded-xl shadow-sm border border-line p-6">
        <h2 class="font-semibold text-ink mb-3">Anggota</h2>
        @foreach ($tenant->users as $user)
            <p class="text-sm text-muted">{{ $user->name }} <span class="text-soft">· {{ $user->pivot->role }}</span></p>
        @endforeach
    </div>
</div>

<div class="bg-card rounded-xl shadow-sm border border-line overflow-hidden mb-6">
    <div class="px-6 py-4 border-b"><h2 class="font-semibold text-ink">Langganan</h2></div>
    <table class="w-full text-sm">
        <thead class="bg-elevated text-left text-muted border-b">
            <tr><th class="px-6 py-3">Aplikasi</th><th class="px-6 py-3">Plan</th><th class="px-6 py-3">Status</th><th class="px-6 py-3">Trial Sampai</th></tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($tenant->subscriptions as $sub)
                <tr>
                    <td class="px-6 py-3 font-medium">{{ $sub->app->name }}</td>
                    <td class="px-6 py-3 capitalize">{{ $sub->plan }}</td>
                    <td class="px-6 py-3">{{ $sub->status }}</td>
                    <td class="px-6 py-3">{{ $sub->trial_ends_at?->format('d M Y') ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-muted">Belum ada langganan.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-card rounded-xl shadow-sm border border-line overflow-hidden">
    <div class="px-6 py-4 border-b"><h2 class="font-semibold text-ink">Pembayaran</h2></div>
    <table class="w-full text-sm">
        <thead class="bg-elevated text-left text-muted border-b">
            <tr><th class="px-6 py-3">Tanggal</th><th class="px-6 py-3">Referensi</th><th class="px-6 py-3">Jumlah</th><th class="px-6 py-3">Status</th></tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($tenant->payments as $payment)
                <tr>
                    <td class="px-6 py-3">{{ $payment->created_at->format('d M Y H:i') }}</td>
                    <td class="px-6 py-3 font-mono text-xs">{{ $payment->gateway_ref }}</td>
                    <td class="px-6 py-3">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td class="px-6 py-3">{{ $payment->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-muted">Belum ada pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
