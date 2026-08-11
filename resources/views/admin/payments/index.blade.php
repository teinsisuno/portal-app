@extends('layouts.app')

@section('title', 'Admin: Pembayaran - megakomsel.com')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-slate-800">Konfirmasi Pembayaran</h1>
    <div class="flex gap-3 text-sm">
        <a href="{{ route('admin.tenants.index') }}" class="text-slate-500 hover:text-blue-600">Tenants</a>
        <a href="{{ route('admin.payments.index') }}" class="text-blue-600 font-medium border-b-2 border-blue-600">Pembayaran</a>
        <a href="{{ route('admin.apps.index') }}" class="text-slate-500 hover:text-blue-600">Aplikasi</a>
        <a href="{{ route('admin.users.index') }}" class="text-slate-500 hover:text-blue-600">Users</a>
    </div>
</div>

@if (session('status') == 'payment-confirmed')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">✅ Pembayaran dikonfirmasi, subscription aktif.</div>
@endif
@if (session('status') == 'payment-rejected')
    <div class="mb-6 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">Pembayaran ditolak.</div>
@endif

<form method="GET" class="mb-6">
    <select name="status" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">Semua status</option>
        @foreach (['pending', 'confirmed', 'rejected', 'failed'] as $s)
            <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
</form>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500 border-b">
            <tr>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Tenant</th>
                <th class="px-6 py-3">Aplikasi</th>
                <th class="px-6 py-3">Referensi</th>
                <th class="px-6 py-3">Jumlah</th>
                <th class="px-6 py-3">Bukti</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($payments as $payment)
                <tr>
                    <td class="px-6 py-3 text-slate-600">{{ $payment->created_at->format('d M H:i') }}</td>
                    <td class="px-6 py-3 font-medium">{{ $payment->tenant->name }}</td>
                    <td class="px-6 py-3">{{ $payment->subscription->app->name }}</td>
                    <td class="px-6 py-3 font-mono text-xs">{{ $payment->gateway_ref }}</td>
                    <td class="px-6 py-3">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td class="px-6 py-3">
                        @if ($payment->proof_image)
                            <a href="{{ asset('storage/' . $payment->proof_image) }}" target="_blank" class="text-blue-600 hover:underline">Lihat</a>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs
                            @if ($payment->status === 'confirmed') bg-emerald-100 text-emerald-700
                            @elseif ($payment->status === 'pending') bg-amber-100 text-amber-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ $payment->status }}
                        </span>
                    </td>
                    <td class="px-6 py-3">
                        @if ($payment->status === 'pending')
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('admin.payments.confirm', $payment) }}">
                                    @csrf
                                    <button class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg">Konfirmasi</button>
                                </form>
                                <form method="POST" action="{{ route('admin.payments.reject', $payment) }}">
                                    @csrf
                                    <button class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg">Tolak</button>
                                </form>
                            </div>
                        @else
                            <span class="text-slate-400 text-xs">{{ $payment->confirmed_at?->format('d M H:i') }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-6 py-8 text-center text-slate-500">Tidak ada pembayaran.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $payments->links() }}</div>
@endsection
