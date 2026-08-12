@extends('layouts.app')

@section('title', 'Dashboard - megakomsel.com')

@section('content')
@if (session('status') == 'verification-notice')
    <div class="mb-6 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
        ⚠️ Kamu belum verifikasi email. Cek inbox untuk link verifikasi, atau
        <a href="{{ route('verification.notice') }}" class="underline">klik di sini</a>.
    </div>
@endif

@if (session('status') == 'email-verified')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ Email kamu sudah terverifikasi. Terima kasih!
    </div>
@endif

<h1 class="text-2xl font-bold text-slate-800 mb-2">Halo, {{ Auth::user()->name }}! 👋</h1>
<p class="text-slate-500 mb-6">Selamat datang di platform megakomsel.com</p>

<div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
    <h2 class="font-semibold text-slate-800 mb-4">Tenant Kamu</h2>

    @if ($tenant)
        <div class="flex items-center justify-between border border-slate-200 rounded-lg p-4">
            <div>
                <p class="font-medium text-slate-800">{{ $tenant->name }}</p>
                <p class="text-sm text-slate-500">
                    Slug: <code class="bg-slate-100 px-1 rounded">{{ $tenant->slug }}.megakomsel.com</code>
                    · Status:
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs
                        @if ($tenant->status === 'active') bg-emerald-100 text-emerald-700
                        @elseif ($tenant->status === 'pending') bg-amber-100 text-amber-700
                        @else bg-red-100 text-red-700 @endif">
                        {{ $tenant->status }}
                    </span>
                </p>
            </div>
        </div>
    @else
        <p class="text-sm text-slate-500">Belum ada tenant. Hubungi admin.</p>
    @endif
</div>

@if ($tenant)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b flex items-center justify-between">
            <h2 class="font-semibold text-slate-800">Langganan Aktif</h2>
            <a href="{{ route('apps.index') }}" class="text-sm text-blue-600 hover:underline">+ Langganan baru</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500 border-b">
                <tr>
                    <th class="px-6 py-3">Aplikasi</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($subscriptions as $sub)
                    <tr>
                        <td class="px-6 py-3 font-medium text-slate-800">{{ $sub->app->name }}</td>
                        <td class="px-6 py-3 capitalize text-slate-600">{{ $sub->plan }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs
                                @if ($sub->status === 'active') bg-emerald-100 text-emerald-700
                                @elseif ($sub->status === 'trialing') bg-blue-100 text-blue-700
                                @elseif ($sub->status === 'past_due') bg-amber-100 text-amber-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ $sub->status }}
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            @if (in_array($sub->status, ['trialing', 'active']))
                                <a href="{{ route('apps.open', ['slug' => $sub->app->slug]) }}" target="_blank"
                                   class="text-blue-600 hover:underline">Buka App ↗</a>
                            @else
                                <a href="{{ route('payments.create', $sub->id) }}"
                                   class="text-blue-600 hover:underline">Bayar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-500">
                            Belum ada langganan. Mulai dari
                            <a href="{{ route('apps.index') }}" class="text-blue-600 hover:underline">katalog aplikasi</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b">
            <h2 class="font-semibold text-slate-800">Riwayat Pembayaran</h2>
        </div>
        @php
            $payments = $subscriptions->flatMap(fn ($sub) => $sub->payments)->sortByDesc('created_at');
        @endphp
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500 border-b">
                <tr>
                    <th class="px-6 py-3">Tanggal</th>
                    <th class="px-6 py-3">Aplikasi</th>
                    <th class="px-6 py-3">Jumlah</th>
                    <th class="px-6 py-3">Metode</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $payment)
                    <tr>
                        <td class="px-6 py-3 text-slate-600">{{ $payment->created_at->format('d M Y H:i') }}</td>
                        <td class="px-6 py-3 font-medium text-slate-800">{{ $payment->subscription->app->name }}</td>
                        <td class="px-6 py-3 text-slate-800">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $payment->method }}</td>
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
                        <td colspan="5" class="px-6 py-8 text-center text-slate-500">Belum ada pembayaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection
