@extends('layouts.app')

@section('title', 'Bayar Langganan - megakomsel.com')

@section('content')
<a href="{{ route('payments.index') }}" class="text-sm text-teal-600 hover:underline">← Kembali</a>
<h1 class="text-2xl font-bold text-ink mt-2 mb-6">Bayar Langganan {{ $subscription->app->name }}</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-card rounded-xl shadow-sm border border-line p-6">
        <h2 class="font-semibold text-ink mb-4">1. Transfer ke Rekening</h2>
        <div class="space-y-2 text-sm text-ink">
            <div class="flex justify-between border-b border-line pb-2">
                <span class="text-muted">Bank</span>
                <span class="font-medium">{{ $bank['bank_name'] }}</span>
            </div>
            <div class="flex justify-between border-b border-line pb-2">
                <span class="text-muted">No. Rekening</span>
                <span class="font-medium">{{ $bank['account_number'] }}</span>
            </div>
            <div class="flex justify-between border-b border-line pb-2">
                <span class="text-muted">Atas Nama</span>
                <span class="font-medium">{{ $bank['account_name'] }}</span>
            </div>
            <div class="flex justify-between pb-2">
                <span class="text-muted">Nominal</span>
                <span class="font-semibold text-ink">Rp {{ number_format($amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="bg-card rounded-xl shadow-sm border border-line p-6">
        <h2 class="font-semibold text-ink mb-4">2. Upload Bukti Transfer</h2>
        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Foto Bukti Transfer</label>
                <input type="file" name="proof_image" accept="image/*" required
                       class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm">
                @error('proof_image')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1">Catatan (opsional)</label>
                <textarea name="notes" rows="2" placeholder="Contoh: sudah transfer dari BCA a.n. Budi"
                          class="w-full rounded-lg border border-line-strong px-3 py-2 text-sm"></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2.5 rounded-lg transition">
                Kirim Bukti Transfer
            </button>
        </form>
    </div>
</div>
@endsection
