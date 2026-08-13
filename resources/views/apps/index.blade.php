@extends('layouts.app')

@section('title', 'Aplikasi - megakomsel.com')

@section('content')
<h1 class="text-2xl font-bold text-ink mb-2">Katalog Aplikasi</h1>
<p class="text-muted mb-6">Pilih aplikasi untuk bisnis kamu, lalu mulai langganan.</p>

@if (session('status') == 'subscription-created')
    <div class="mb-6 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg p-3">
        ✅ Langganan berhasil dibuat! Masa trial 7 hari langsung aktif.
    </div>
@endif

@if (session('status') == 'subscription-exists')
    <div class="mb-6 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
        ⚠️ Kamu sudah berlangganan aplikasi ini (status trialing/active).
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg p-3">
        @foreach ($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    @forelse ($apps as $app)
        <div class="bg-card rounded-xl shadow-sm border border-line p-6 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-semibold text-ink">{{ $app->name }}</h2>
                @if ($app->status === 'available')
                    <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">Tersedia</span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs bg-elevated text-muted">Segera</span>
                @endif
            </div>
            <p class="text-sm text-muted flex-1">{{ $app->description }}</p>
            <p class="text-ink font-semibold mt-4">Rp {{ number_format($app->price_monthly, 0, ',', '.') }}<span class="text-sm font-normal text-muted">/bulan</span></p>

            @if ($app->status === 'available')
                <form method="POST" action="{{ route('subscriptions.store') }}" class="mt-4 space-y-3">
                    @csrf
                    <input type="hidden" name="app_id" value="{{ $app->id }}">
                    <select name="plan" class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm">
                        <option value="monthly">Bulanan</option>
                        <option value="yearly">Tahunan</option>
                    </select>
                    <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 rounded-lg transition">
                        Langganan
                    </button>
                </form>
            @else
                <button disabled class="mt-4 w-full bg-elevated text-soft font-medium py-2.5 rounded-lg cursor-not-allowed">
                    Segera Hadir
                </button>
            @endif
        </div>
    @empty
        <p class="text-sm text-muted col-span-3">Belum ada aplikasi.</p>
    @endforelse
</div>
@endsection
