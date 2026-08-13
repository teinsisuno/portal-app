@extends('layouts.app')

@section('title', 'Langganan - megakomsel.com')

@section('content')
<h1 class="text-2xl font-bold text-ink mb-2">Langganan</h1>
<p class="text-muted mb-6">Status langganan aplikasi tenant kamu.</p>

@if (session('status') == 'subscription-created')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ Langganan berhasil dibuat! Masa trial 7 hari langsung aktif.
    </div>
@endif

@if ($tenant)
    <div class="bg-card rounded-xl shadow-sm border border-line overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-elevated text-left text-muted border-b">
                <tr>
                    <th class="px-4 py-3">Aplikasi</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Trial Sampai</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($subscriptions as $sub)
                    <tr>
                        <td class="px-4 py-3 font-medium text-ink">{{ $sub->app->name }}</td>
                        <td class="px-4 py-3 capitalize text-muted">{{ $sub->plan }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs
                                @if ($sub->status === 'active') bg-emerald-100 text-emerald-700
                                @elseif ($sub->status === 'trialing') bg-teal-100 text-teal-700
                                @elseif ($sub->status === 'past_due') bg-amber-100 text-amber-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ $sub->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            {{ $sub->trial_ends_at?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @if (in_array($sub->status, ['trialing', 'active']))
                                @if ($sub->app->slug === 'absensi')
                                    <a href="{{ route('apps.open', $sub->app->slug) }}"
                                       class="text-teal-600 hover:underline">Buka App ↗</a>
                                @else
                                    <span class="text-soft text-xs">Menyusul</span>
                                @endif
                            @else
                                <a href="{{ route('payments.create', $sub->id) }}"
                                   class="text-teal-600 hover:underline">Bayar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-muted">
                            Belum ada langganan.
                            <a href="{{ route('apps.index') }}" class="text-teal-600 hover:underline">Lihat katalog aplikasi</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="bg-card rounded-xl shadow-sm border border-line p-6 text-sm text-muted">
        Belum ada tenant. Hubungi admin.
    </div>
@endif
@endsection
