<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="megakomsel.com — platform SaaS untuk UMKM: daftar sekali, langsung pakai aplikasi bisnis seperti Absensi, Toyaa, Kasir UMKM & Laundry.">
    <title>megakomsel.com — Platform Aplikasi Bisnis untuk UMKM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-700 font-landing antialiased">

    {{-- ================= NAVBAR ================= --}}
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur border-b border-slate-100">
        <nav class="max-w-6xl mx-auto px-4 sm:px-6 flex items-center justify-between h-16">
            <a href="#beranda" class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center text-lg font-extrabold">M</span>
                <span class="font-extrabold text-slate-800 text-lg">megakomsel<span class="text-teal-600">.com</span></span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-600">
                <a href="#aplikasi" class="hover:text-teal-600 transition">Aplikasi</a>
                <a href="#fitur" class="hover:text-teal-600 transition">Fitur</a>
                <a href="#cara-kerja" class="hover:text-teal-600 transition">Cara Kerja</a>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 hover:text-teal-600 transition px-3 py-2">Masuk</a>
                <a href="{{ route('register') }}" class="text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 transition px-4 py-2 rounded-lg shadow-sm shadow-teal-600/20">Daftar Gratis</a>
            </div>
        </nav>
    </header>

    {{-- ================= HERO ================= --}}
    <section id="beranda" class="relative overflow-hidden bg-gradient-to-b from-teal-600 via-teal-600 to-indigo-700 text-white">
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 20% 30%, rgba(255,255,255,.35) 0, transparent 40%), radial-gradient(circle at 80% 70%, rgba(255,255,255,.25) 0, transparent 40%);"></div>
        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 pt-20 pb-24 text-center">
            <span class="inline-flex items-center gap-2 text-xs font-semibold bg-white/10 border border-white/20 rounded-full px-4 py-1.5 mb-6">
                🚀 Produk pertama: <span class="text-amber-300">Absensi</span> — coba gratis 7 hari
            </span>
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight">
                Satu platform untuk<br class="hidden sm:block"> semua aplikasi bisnis kamu
            </h1>
            <p class="mt-6 max-w-2xl mx-auto text-lg text-teal-100">
                Daftar sekali, langsung akses aplikasi UMKM — absensi karyawan, pencatatan air,
                kasir, dan laundry. Tanpa ribet, tanpa install, siap pakai hari ini.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="w-full sm:w-auto bg-white text-teal-700 font-bold px-8 py-3.5 rounded-xl hover:bg-teal-50 transition shadow-lg">
                    Daftar Gratis Sekarang
                </a>
                <a href="#aplikasi" class="w-full sm:w-auto border border-white/30 text-white font-semibold px-8 py-3.5 rounded-xl hover:bg-white/10 transition">
                    Lihat Aplikasi ↓
                </a>
            </div>
            <div class="mt-14 grid grid-cols-3 gap-4 max-w-2xl mx-auto">
                <div class="rounded-2xl bg-white/10 border border-white/15 p-4">
                    <p class="text-2xl sm:text-3xl font-extrabold">{{ $apps->count() }}</p>
                    <p class="text-xs sm:text-sm text-teal-100 mt-1">Aplikasi UMKM</p>
                </div>
                <div class="rounded-2xl bg-white/10 border border-white/15 p-4">
                    <p class="text-2xl sm:text-3xl font-extrabold">7 Hari</p>
                    <p class="text-xs sm:text-sm text-teal-100 mt-1">Masa Trial</p>
                </div>
                <div class="rounded-2xl bg-white/10 border border-white/15 p-4">
                    <p class="text-2xl sm:text-3xl font-extrabold">1 Akun</p>
                    <p class="text-xs sm:text-sm text-teal-100 mt-1">Semua Aplikasi</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= KATALOG APLIKASI ================= --}}
    <section id="aplikasi" class="py-20 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <span class="text-sm font-semibold text-teal-600 uppercase tracking-wider">Katalog Aplikasi</span>
                <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900">Aplikasi untuk Bisnis Kamu</h2>
                <p class="mt-3 text-slate-500 max-w-xl mx-auto">Pilih aplikasi yang sesuai kebutuhan, berlangganan, dan langsung pakai — semua dari satu akun.</p>
            </div>

            @php
                $icons = [
                    'absensi'   => '🕐',
                    'toyaa'     => '💧',
                    'kasirumkm' => '🛒',
                    'laundry'   => '🧺',
                ];
                $colors = [
                    'absensi'   => 'from-amber-400 to-orange-500',
                    'toyaa'     => 'from-cyan-400 to-teal-500',
                    'kasirumkm' => 'from-emerald-400 to-teal-500',
                    'laundry'   => 'from-violet-400 to-purple-500',
                ];
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @forelse ($apps as $app)
                    @php
                        $icon = $icons[$app->slug] ?? '📦';
                        $color = $colors[$app->slug] ?? 'from-teal-400 to-indigo-500';
                    @endphp
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:-translate-y-1 transition p-6 flex flex-col">
                        <div class="flex items-start justify-between">
                            <span class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $color }} text-white text-2xl flex items-center justify-center shadow">{{ $icon }}</span>
                            @if ($app->status === 'available')
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">Tersedia</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">Segera</span>
                            @endif
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-slate-900">{{ $app->name }}</h3>
                        <p class="mt-2 text-sm text-slate-500 flex-1">{{ $app->description }}</p>
                        <p class="mt-4 font-extrabold text-slate-900">
                            Rp {{ number_format($app->price_monthly, 0, ',', '.') }}
                            <span class="text-sm font-normal text-slate-400">/bulan</span>
                        </p>
                        @if ($app->status === 'available')
                            <a href="{{ route('register') }}" class="mt-4 w-full text-center bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 rounded-lg transition">
                                Coba Gratis 7 Hari
                            </a>
                        @else
                            <button disabled class="mt-4 w-full bg-slate-100 text-slate-400 font-semibold py-2.5 rounded-lg cursor-not-allowed">
                                Segera Hadir
                            </button>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500 col-span-full text-center py-10">Katalog aplikasi sedang disiapkan.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ================= FITUR ================= --}}
    <section id="fitur" class="py-20 bg-white">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <span class="text-sm font-semibold text-teal-600 uppercase tracking-wider">Kenapa megakomsel?</span>
                <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900">Semua Kebutuhan Digital Bisnis, Satu Tempat</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="rounded-2xl border border-slate-200 p-6">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center text-xl">🔑</div>
                    <h3 class="mt-4 font-bold text-slate-900">Satu Akun Semua App</h3>
                    <p class="mt-2 text-sm text-slate-500">Registrasi sekali, auto-login SSO ke semua aplikasi yang kamu langgan — tanpa login ulang.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-6">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">🎁</div>
                    <h3 class="mt-4 font-bold text-slate-900">Trial 7 Hari</h3>
                    <p class="mt-2 text-sm text-slate-500">Setiap aplikasi bisa dicoba gratis 7 hari penuh. Cocok dulu, bayar kemudian.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-6">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">💳</div>
                    <h3 class="mt-4 font-bold text-slate-900">Billing Terpusat</h3>
                    <p class="mt-2 text-sm text-slate-500">Semua langganan & pembayaran dalam satu dashboard. Transfer manual sekarang, otomatis menyusul.</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-6">
                    <div class="w-10 h-10 rounded-lg bg-violet-100 text-violet-600 flex items-center justify-center text-xl">🛡️</div>
                    <h3 class="mt-4 font-bold text-slate-900">Aman & Terisolasi</h3>
                    <p class="mt-2 text-sm text-slate-500">Setiap tenant punya data & database sendiri. Verifikasi email + token SSO untuk keamanan akses.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= CARA KERJA ================= --}}
    <section id="cara-kerja" class="py-20 bg-slate-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <span class="text-sm font-semibold text-teal-600 uppercase tracking-wider">Cara Kerja</span>
                <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900">Mulai Pakai dalam 3 Langkah</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="relative text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-teal-600 text-white font-extrabold text-xl flex items-center justify-center shadow-lg shadow-teal-600/30">1</div>
                    <h3 class="mt-4 font-bold text-slate-900">Daftar Akun</h3>
                    <p class="mt-2 text-sm text-slate-500">Buat akun dengan nama, email & password. Verifikasi email untuk mengaktifkan.</p>
                </div>
                <div class="relative text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-teal-600 text-white font-extrabold text-xl flex items-center justify-center shadow-lg shadow-teal-600/30">2</div>
                    <h3 class="mt-4 font-bold text-slate-900">Lengkapi Data Perusahaan</h3>
                    <p class="mt-2 text-sm text-slate-500">Isi nama perusahaan, alamat & nomor HP di dashboard. Tenant kamu langsung aktif.</p>
                </div>
                <div class="relative text-center">
                    <div class="mx-auto w-14 h-14 rounded-full bg-teal-600 text-white font-extrabold text-xl flex items-center justify-center shadow-lg shadow-teal-600/30">3</div>
                    <h3 class="mt-4 font-bold text-slate-900">Pilih App & Langsung Pakai</h3>
                    <p class="mt-2 text-sm text-slate-500">Pilih aplikasi, trial 7 hari otomatis, dan kamu langsung diarahkan ke dashboard aplikasinya.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= CTA ================= --}}
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-600 to-indigo-700 text-white text-center px-6 py-14">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle at 85% 20%, rgba(255,255,255,.4) 0, transparent 45%);"></div>
                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl font-extrabold">Siap Digitalisasi Bisnis Kamu?</h2>
                    <p class="mt-3 text-teal-100 max-w-xl mx-auto">Gabung gratis, coba aplikasi selama 7 hari, dan rasakan kemudahan mengelola bisnis dalam satu platform.</p>
                    <a href="{{ route('register') }}" class="inline-block mt-8 bg-white text-teal-700 font-bold px-10 py-4 rounded-xl hover:bg-teal-50 transition shadow-lg">
                        Daftar Gratis Sekarang →
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ================= FOOTER ================= --}}
    <footer class="bg-slate-900 text-slate-400">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-teal-600 text-white flex items-center justify-center text-sm font-extrabold">M</span>
                <span class="font-bold text-white">megakomsel<span class="text-teal-400">.com</span></span>
            </div>
            <p class="text-sm">© {{ date('Y') }} megakomsel.com — Platform aplikasi bisnis untuk UMKM Indonesia.</p>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('login') }}" class="hover:text-white transition">Masuk</a>
                <a href="{{ route('register') }}" class="hover:text-white transition">Daftar</a>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll untuk anchor link
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    </script>
</body>
</html>
