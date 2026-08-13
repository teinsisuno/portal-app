@extends('layouts.app')

@section('title', 'Pembayaran - megakomsel.com')

@section('content')
<h1 class="text-2xl font-bold text-ink mb-2">Pembayaran</h1>
<p class="text-muted mb-6">Riwayat pembayaran langganan kamu.</p>

@if (session('status') == 'payment-submitted')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ Bukti transfer diterima! Admin akan konfirmasi segera.
    </div>
@endif

<div class="bg-card rounded-xl shadow-sm border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-elevated text-left text-muted border-b">
            <tr>
                <th class="px-6 py-3">Tanggal</th>
                <th class="px-6 py-3">Aplikasi</th>
                <th class="px-6 py-3">Referensi</th>
                <th class="px-6 py-3">Jumlah</th>
                <th class="px-6 py-3">Metode</th>
                <th class="px-6 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($payments as $payment)
                <tr>
                    <td class="px-6 py-3 text-muted">{{ $payment->created_at->format('d M Y H:i') }}</td>
                    <td class="px-6 py-3 font-medium text-ink">{{ $payment->subscription->app->name }}</td>
                    <td class="px-6 py-3 text-muted font-mono text-xs">{{ $payment->gateway_ref }}</td>
                    <td class="px-6 py-3 text-ink">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td class="px-6 py-3 text-muted">{{ $payment->method }}</td>
                    <td class="px-6 py-3">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs
                            @if ($payment->status === 'confirmed') bg-emerald-100 text-emerald-700
                            @elseif ($payment->status === 'pending') bg-amber-100 text-amber-700
                            @else bg-red-100 text-red-700 @endif">
                            {{ $payment->status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-muted">Belum ada pembayaran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($tenant)
    <div class="mt-6 bg-card rounded-xl shadow-sm border border-line p-6">
        <h2 class="font-semibold text-ink mb-2">Langganan yang Perlu Dibayar</h2>
        @php
            $dueSubs = $tenant->subscriptions()->with('app')->whereIn('status', ['past_due', 'canceled'])->get();
        @endphp
        @forelse ($dueSubs as $sub)
            <div class="flex items-center justify-between border border-line rounded-lg p-4 mb-3">
                <div>
                    <p class="font-medium text-ink">{{ $sub->app->name }}</p>
                    <p class="text-sm text-muted capitalize">Plan: {{ $sub->plan }} · Status: {{ $sub->status }}</p>
                </div>
                <a href="{{ route('payments.create', $sub->id) }}" class="text-teal-600 hover:underline text-sm">Bayar Sekarang</a>
            </div>
        @empty
            <p class="text-sm text-muted">Tidak ada tagihan menunggu pembayaran. 🎉</p>
        @endforelse
    </div>
@endif
@endsection
