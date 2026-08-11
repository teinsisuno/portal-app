@extends('layouts.app')

@section('title', 'Bayar Langganan - megakomsel.com')

@section('content')
<a href="{{ route('payments.index') }}" class="text-sm text-blue-600 hover:underline">← Kembali</a>
<h1 class="text-2xl font-bold text-slate-800 mt-2 mb-6">Bayar Langganan {{ $subscription->app->name }}</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">1. Transfer ke Rekening</h2>
        <div class="space-y-2 text-sm text-slate-700">
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">Bank</span>
                <span class="font-medium">{{ $bank['bank_name'] }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">No. Rekening</span>
                <span class="font-medium">{{ $bank['account_number'] }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <span class="text-slate-500">Atas Nama</span>
                <span class="font-medium">{{ $bank['account_name'] }}</span>
            </div>
            <div class="flex justify-between pb-2">
                <span class="text-slate-500">Nominal</span>
                <span class="font-semibold text-slate-800">Rp {{ number_format($amount, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">2. Upload Bukti Transfer</h2>
        <form method="POST" action="{{ route('payments.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="subscription_id" value="{{ $subscription->id }}">

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Foto Bukti Transfer</label>
                <input type="file" name="proof_image" accept="image/*" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                @error('proof_image')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Catatan (opsional)</label>
                <textarea name="notes" rows="2" placeholder="Contoh: sudah transfer dari BCA a.n. Budi"
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition">
                Kirim Bukti Transfer
            </button>
        </form>
    </div>
</div>
@endsection
