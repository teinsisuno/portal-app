@extends('layouts.app')

@section('title', 'Langganan - megakomsel.com')

@section('content')
<h1 class="text-2xl font-bold text-slate-800 mb-2">Langganan</h1>
<p class="text-slate-500 mb-6">Status langganan aplikasi tenant kamu.</p>

@if (session('status') == 'subscription-created')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ Langganan berhasil dibuat! Masa trial 7 hari langsung aktif.
    </div>
@endif

@if ($tenant)
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500 border-b">
                <tr>
                    <th class="px-4 py-3">Aplikasi</th>
                    <th class="px-4 py-3">Plan</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Trial Sampai</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($subscriptions as $sub)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $sub->app->name }}</td>
                        <td class="px-4 py-3 capitalize text-slate-600">{{ $sub->plan }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs
                                @if ($sub->status === 'active') bg-emerald-100 text-emerald-700
                                @elseif ($sub->status === 'trialing') bg-blue-100 text-blue-700
                                @elseif ($sub->status === 'past_due') bg-amber-100 text-amber-700
                                @else bg-red-100 text-red-700 @endif">
                                {{ $sub->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $sub->trial_ends_at?->format('d M Y') ?? '-' }}
                        </td>
                        <td class="px-4 py-3">
                            @if (in_array($sub->status, ['trialing', 'active']))
                                @if ($sub->app->slug === 'absensi')
                                    <a href="{{ route('apps.open', $sub->app->slug) }}"
                                       class="text-blue-600 hover:underline">Buka App ↗</a>
                                @else
                                    <a href="https://{{ $tenant->slug }}.megakomsel.com" target="_blank"
                                       class="text-blue-600 hover:underline">Buka App ↗</a>
                                @endif
                            @else
                                <a href="{{ route('payments.create', $sub->id) }}"
                                   class="text-blue-600 hover:underline">Bayar</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                            Belum ada langganan.
                            <a href="{{ route('apps.index') }}" class="text-blue-600 hover:underline">Lihat katalog aplikasi</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 text-sm text-slate-500">
        Belum ada tenant. Hubungi admin.
    </div>
@endif
@endsection
