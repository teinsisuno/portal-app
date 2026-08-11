# PRD — Central Platform megakomsel.com

## 1. Header Metadata

| Field | Nilai |
|-------|-------|
| Author | Sigit / Paijo |
| Project | megakomsel.com (Central Platform) |
| Fase | MVP Central |
| Versi | Draft v0.3 |
| Status | Draft |
| Tanggal | 2026-08-11 |
| Dependencies | — (produk pertama: Toyaa, PRD menyusul) |

---

## 2. Executive Summary

megakomsel.com adalah platform SaaS induk tempat UMKM/bisnis mendaftar, melengkapi data tenancy, memilih aplikasi (Toyaa, Kasir UMKM, Laundry, Absensi), berlangganan, dan membayar. Alur utama: registrasi → dashboard → lengkapi tenancy (nama perusahaan, alamat, nomor HP) → pilih aplikasi → **auto-login SSO** ke dashboard aplikasi (provisioning 1 tenant = 1 DB otomatis) → kelola pengguna & role di dalam aplikasi masing-masing. Central berfungsi sebagai pusat registrasi, billing, onboarding, dan administrasi tenant — data bisnis tiap tenant hidup di aplikasi produk masing-masing dengan arsitektur 1 tenant = 1 database. MVP central berfokus: registrasi + login, onboarding tenancy, katalog app, langganan, pembayaran manual (siap Midtrans), auto-login SSO, dashboard tenant, dan admin panel pusat.

---

## 3. Problem Statement

- Saat ini belum ada platform induk; tiap produk harus bikin sendiri auth, billing, dan manajemen tenant — kerja berulang.
- Tanpa central, tidak ada satu pintu registrasi & pembayaran untuk semua produk.
- UMKM target butuh cara daftar & bayar yang sederhana; admin platform butuh satu panel untuk mengelola semua tenant & pembayaran.

---

## 4. Goals & Success Metrics

| Goal | Metric | Baseline | Target | Timeline |
|------|--------|----------|--------|----------|
| Satu pintu registrasi | Waktu daftar → aktif | — | < 5 menit | Sprint 1-2 |
| Langganan aktif | % tenant bayar | — | 80% tenant aktif bayar | Sprint 3-4 |
| Kelola tenant terpusat | Admin bisa lihat semua tenant | — | 1 panel, filter & search | Sprint 4 |
| Siap scale payment | Gateway abstraction | — | Manual → Midtrans tanpa rombak | Sprint 3+ |

---

## 5. Data Flow Diagram (Wajib!)

```
[User UMKM] ──► megakomsel.com (landing)
     │ register
     ▼
[Daftar] ──► users + tenants dibuat (status: pending)
     │ lengkapi tenancy (nama perusahaan, alamat, no HP)
     ▼
[Dashboard] ──► tenant status: active
     │ pilih app (Toyaa/Kasir/Laundry/Absensi)
     ▼
[Langganan] ──► subscriptions (status: trialing 7 hari otomatis)
     │ auto-login: Central generate signed JWT
     ▼
[App Dashboard] ──► redirect ke {slug}-{app}.megakomsel.com/sso?token=xxx
     │ provisioning async → app bikin DB tenant
     ▼
[Pengaturan App] ──► app punya user & role sendiri (owner/admin/supervisor/karyawan)
     │ pilih metode bayar
     ▼
[Pembayaran Manual] ──► payments (status: pending)
     │ upload bukti transfer
     ▼
[Admin Platform] ──► konfirmasi pembayaran
     ▼
[Subscription ACTIVE] ──► tenant lanjut pakai app
```

Alur subdomain:

```
pdam-xyz-toyaa.megakomsel.com       (pola: {tenant-slug}-{app-slug})
pdam-xyz-absensi.megakomsel.com
     │ DNS wildcard *.megakomsel.com → server (1 record, cover semua tenant & app)
     ▼
Apache/Laragon vhost / balancer ── match suffix app slug dari hostname
     ▼
App produk (Laravel) ── parse hostname: suffix app slug → sisanya tenant slug
     ▼
Database tenant_{slug} (1 tenant = 1 DB)
```

---

## 6. Target Persona

| Role | Persona | Pain Point | Goal |
|------|---------|------------|------|
| Pemilik UMKM | Pemilik PDAM kecil / pengelola air | Ribet daftar ke banyak aplikasi | Satu akun, langsung pakai app |
| Admin Platform | Sigit (superadmin) | Nggak ada panel kelola tenant & pembayaran | Satu dashboard untuk semua tenant |
| Tenant Member | Karyawan tenant | Akses app tanpa tahu billing | Login lewat central → masuk app |

---

## 7. Functional Requirements (FR)

### FR-001: Registrasi & Login
- **Priority:** Must
- **User Story:** Sebagai pemilik UMKM, saya ingin mendaftar & login, agar bisa memilih aplikasi dan berlangganan.
- **Acceptance Criteria:**
  - [ ] Registrasi: nama, email, password (konfirmasi)
  - [ ] Email verifikasi wajib sebelum login aktif
  - [ ] Login dengan email + password
  - [ ] Lupa password (reset via email)
  - [ ] Auto-create `users` + `tenants` (tenant status: pending) saat registrasi

### FR-002: Katalog Aplikasi
- **Priority:** Must
- **User Story:** Sebagai user, saya ingin melihat daftar aplikasi yang tersedia, agar bisa memilih.
- **Acceptance Criteria:**
  - [ ] Halaman `/apps` menampilkan app: Toyaa, Kasir UMKM, Laundry, Absensi
  - [ ] Tiap app punya deskripsi, harga (plan), status (available/coming soon)
  - [ ] Tombol "Langganan" hanya aktif untuk app available

### FR-003: Langganan (Subscription)
- **Priority:** Must
- **User Story:** Sebagai user, saya ingin berlangganan aplikasi, agar bisa mulai pakai.
- **Acceptance Criteria:**
  - [ ] User pilih app → buat subscription (status: trialing 7 hari otomatis)
  - [ ] Saat subscription dibuat, Central auto-trigger provisioning ke app (async) + langsung auto-login SSO ke dashboard app
  - [ ] Satu tenant bisa punya banyak subscription (per app)
  - [ ] Status: trialing / active / past_due / canceled
  - [ ] Dashboard tenant menampilkan status tiap subscription

### FR-004: Pembayaran Manual (transfer)
- **Priority:** Must
- **User Story:** Sebagai user, saya ingin membayar via transfer & upload bukti, agar langganan aktif.
- **Acceptance Criteria:**
  - [ ] Sistem menampilkan rekening tujuan (nomor rekening, atas nama)
  - [ ] User upload bukti transfer (image) → payment status: pending
  - [ ] Admin konfirmasi → payment status: confirmed → subscription menjadi active
  - [ ] Riwayat pembayaran tampil di dashboard tenant

### FR-005: Gateway Payment Abstraction (persiapan Midtrans)
- **Priority:** Should
- **User Story:** Sebagai developer, saya ingin payment gateway ter-abstraksi, agar nanti bisa tambah Midtrans tanpa rombak.
- **Acceptance Criteria:**
  - [ ] Interface `PaymentGatewayInterface`: `createPayment()`, `checkStatus()`, `handleWebhook()`
  - [ ] Implementasi `ManualTransferGateway` (rekening + konfirmasi admin)
  - [ ] Konfigurasi gateway via config (`config/payment.php`) + env
  - [ ] Nanti `MidtransGateway` tinggal implement interface yang sama

### FR-006: Admin Panel Pusat
- **Priority:** Must
- **User Story:** Sebagai admin platform, saya ingin satu panel untuk kelola semua tenant & pembayaran.
- **Acceptance Criteria:**
  - [ ] `/admin/tenants`: list, search, filter status, lihat detail (subscription, payments)
  - [ ] `/admin/payments`: list payment pending → konfirmasi/reject
  - [ ] `/admin/apps`: kelola katalog app (nama, harga, status)
  - [ ] `/admin/users`: kelola user platform
  - [ ] Hanya role `superadmin` yang bisa akses

### FR-007: Dashboard Tenant
- **Priority:** Must
- **User Story:** Sebagai user, saya ingin melihat status langganan & akses ke app.
- **Acceptance Criteria:**
  - [ ] Dashboard menampilkan: profil tenant, subscription list, status, tombol "Buka App"
  - [ ] Tombol "Buka App" = auto-login SSO: generate signed token → redirect ke `{slug}-{app}.megakomsel.com/sso?token=xxx`
  - [ ] Menampilkan riwayat pembayaran

### FR-008: Subdomain Tenant
- **Priority:** Should
- **User Story:** Sebagai tenant, saya ingin akses app lewat subdomain sendiri, agar branding & isolasi jelas.
- **Acceptance Criteria:**
  - [ ] Pola subdomain: `{tenant-slug}-{app-slug}.megakomsel.com` (mis. `pdam-xyz-toyaa`, `pdam-xyz-absensi`)
  - [ ] DNS wildcard `*.megakomsel.com` (1 record) mengarah ke server — cover semua tenant & app tanpa setup per-tenant
  - [ ] Balancer/edge mem-route berdasarkan suffix app slug; app produk mem-parse tenant slug dari hostname
  - [ ] Aturan slug tenant: lowercase + angka + dash (tanpa dash dobel); parsing pakai suffix match dari daftar app terdaftar
  - [ ] Subdomain unik (validasi saat tenant dibuat)

### FR-009: Onboarding Lengkapi Tenancy
- **Priority:** Must
- **User Story:** Sebagai user baru, saya ingin melengkapi data tenancy setelah registrasi, agar tenant siap dipakai.
- **Acceptance Criteria:**
  - [ ] Setelah registrasi, dashboard menampilkan prompt/wizard "Lengkapi Data Perusahaan"
  - [ ] Field: nama perusahaan, alamat, nomor HP (email sudah dari registrasi)
  - [ ] Tenant status `pending` → `active` setelah data lengkap
  - [ ] Pilih aplikasi diblokir sampai data tenancy lengkap

### FR-010: SSO Auto-Login ke Aplikasi
- **Priority:** Must
- **User Story:** Sebagai user, saya ingin otomatis masuk ke dashboard aplikasi setelah memilih aplikasi, tanpa login ulang.
- **Acceptance Criteria:**
  - [ ] Saat subscription dibuat (trialing), Central generate signed token (JWT, expired < 60 detik, one-time)
  - [ ] Redirect ke `https://{slug}-{app}.megakomsel.com/sso?token=xxx`
  - [ ] App produk verifikasi signature & expiry, buat/cocokkan user owner, terbitkan session/token app
  - [ ] Token ditolak jika expired / sudah terpakai / signature invalid
  - [ ] Tombol "Buka App" di dashboard tenant = auto-login yang sama (token baru setiap klik)
  - [ ] Mapping role Central → app: owner → owner, member/admin → admin (role lain diatur manual di dalam app)

---

## 8. Non-Functional Requirements

- **Performance:** Halaman central ringan (< 2s first load); admin panel pakai pagination.
- **Security:** Password di-hash (bcrypt); email verification; rate limit login; role middleware.
- **Reliability:** Upload bukti transfer tersimpan rapi; status payment idempotent (konfirmasi 2x tidak dobel).
- **Scalability:** Struktur modular `app/Core/Modules/*`; payment gateway abstraction; central tidak pegang data bisnis.
- **Compatibility:** Laravel 13 / PHP 8.3+ (dev aktual: PHP 8.4); MySQL 8; MVP auth pakai Blade; Inertia.js + Vue 3 untuk halaman apps/dashboard (Sprint 2+).

---

## 9. Scope

| ✅ In Scope (MVP) | ❌ Out of Scope (nanti) |
|-------------------|------------------------|
| Registrasi, login, verify email | Midtrans integrasi aktif (cuma abstraction) |
| Katalog app + langganan | Fitur detail Toyaa (PRD terpisah) |
| Pembayaran manual + upload bukti | Mobile app |
| Admin panel pusat | Multi-language |
| Subdomain routing | Invoice/PDF otomatis |
| Dashboard tenant | Notifikasi email marketing |

---

## 10. Tabel Database

Semua di `central_db` (1 database, tanpa tenancy — data global platform).

### users
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| name | string | |
| email | string unique | |
| password | string | bcrypt |
| email_verified_at | timestamp nullable | |
| phone | string nullable | |
| is_admin | boolean | superadmin platform (default false) |
| remember_token | string nullable | |
| timestamps | | |

### tenants
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| name | string | nama bisnis/tenant |
| slug | string unique | `pdam-xyz` → subdomain |
| email | string | kontak tenant |
| phone | string nullable | |
| address | text nullable | |
| status | enum | pending / active / suspended |
| created_by | FK users nullable | |
| timestamps | | |

### tenant_user (pivot)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| tenant_id | FK | |
| user_id | FK | |
| role | string | owner / member / admin |

### apps
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| slug | string unique | toyaa / kasirumkm / laundry / absensi |
| name | string | |
| description | text | |
| price_monthly | decimal | |
| status | enum | available / coming_soon |
| logo | string nullable | |

### subscriptions
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| tenant_id | FK | |
| app_id | FK | |
| plan | string | monthly / yearly |
| status | enum | trialing / active / past_due / canceled |
| trial_ends_at | datetime nullable | |
| starts_at | datetime | |
| ends_at | datetime nullable | |
| timestamps | | |

### payments
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| subscription_id | FK | |
| tenant_id | FK | |
| amount | decimal | |
| method | enum | manual_transfer / midtrans (siap) |
| status | enum | pending / confirmed / rejected / failed |
| proof_image | string nullable | path bukti transfer |
| gateway_ref | string nullable | transaksi id dari gateway |
| confirmed_by | FK users nullable | admin yang konfirmasi |
| confirmed_at | datetime nullable | |
| notes | text nullable | |
| timestamps | | |

### password_reset_tokens (default Laravel)
### jobs / cache / sessions (default)

---

## 11. API Endpoints

```
# Auth
POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/verify-email
POST   /api/v1/auth/resend-verification
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password

# Apps
GET    /api/v1/apps

# Tenant
GET    /api/v1/tenant
PUT    /api/v1/tenant

# Subscription
GET    /api/v1/subscriptions
POST   /api/v1/subscriptions          # pilih app → buat langganan
GET    /api/v1/subscriptions/{id}

# SSO Auto-Login
GET    /api/v1/apps/{slug}/sso        # generate signed token → redirect ke app produk

# Payment
GET    /api/v1/payments
POST   /api/v1/payments               # upload bukti transfer
GET    /api/v1/payments/{id}

# Admin
GET    /api/v1/admin/tenants
GET    /api/v1/admin/tenants/{id}
POST   /api/v1/admin/payments/{id}/confirm
POST   /api/v1/admin/payments/{id}/reject
GET    /api/v1/admin/apps
POST   /api/v1/admin/apps
PUT    /api/v1/admin/apps/{id}
GET    /api/v1/admin/users
```

---

## 12. Service Layer

| Service | Prioritas | Fungsi |
|---------|-----------|--------|
| `AuthService` | Must | register (auto-create tenant), verify, reset |
| `SubscriptionService` | Must | create, activate, cancel, status check |
| `PaymentService` | Must | create payment, konfirmasi, reject, idempotency |
| `PaymentGatewayInterface` | Should | abstraction gateway (ManualTransfer, nanti Midtrans) |
| `TenantProvisioningService` | Must | saat subscription dibuat → trigger provisioning ke app produk (panggil webhook app, mis. `POST /api/v1/provisioning/tenant` di Absensi) |
| `SsoService` | Must | generate signed JWT (short-lived, one-time) untuk auto-login ke app produk |

Jobs:
- `ProvisionTenantJob` — dipanggil saat subscription dibuat (trialing); memanggil webhook app produk untuk membuat database tenant; idempotent + retry bila gagal.

---

## 13. Risiko & Dependensi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| Wildcard DNS & SSL | Subdomain tidak bisa diakses | Setup `*.megakomsel.com` di Cloudflare + wildcard cert; validasi di awal |
| Privilege MySQL (CREATE DATABASE) | Tenancy 1-tenant-1-DB gagal di app produk | Pastikan user MySQL punya GRANT CREATE DATABASE |
| Pembayaran manual rawan telat | Tenant menunggu lama | Notifikasi ke admin saat ada payment pending |
| Konfirmasi payment dobel | Subscription salah status | Idempotent: cek status sebelum update |
| Email belum terkonfigurasi | Verify/reset email gagal | SMTP lokal (Mailpit) saat dev; SMTP produksi di env |
| Midtrans belum aktif | Tidak bisa bayar otomatis | Manual gateway dulu; abstraction siap |

---

## 14. Timeline

### Sprint 1 — Foundation (Minggu 1-2)
- [ ] Setup project Laravel `portal-app` + folder `app/Core/Modules/`
- [ ] ModuleServiceProvider (auto-load routes/migrations per modul)
- [ ] Auth module: register, login, verify email, reset password
- [ ] Model User, Tenant, tenant_user pivot
- [ ] Onboarding tenancy: lengkapi nama perusahaan, alamat, no HP (tenant pending → active)

### Sprint 2 — Katalog & Langganan (Minggu 3-4)
- [ ] App catalog (apps table + `/apps` page)
- [ ] Subscription module: create, status, dashboard tenant
- [ ] Seeder: apps (Toyaa, Kasir UMKM, Laundry, Absensi)
- [ ] `SsoService`: generate signed JWT + auto-login redirect setelah pilih app

### Sprint 3 — Pembayaran (Minggu 5-6)
- [ ] Payment module: manual transfer, upload bukti
- [ ] `PaymentGatewayInterface` + `ManualTransferGateway`
- [ ] Konfirmasi admin + auto-activate subscription

### Sprint 4 — Admin Panel (Minggu 7-8)
- [ ] `/admin/tenants` (list, search, filter, detail)
- [ ] `/admin/payments` (konfirmasi/reject)
- [ ] `/admin/apps` + `/admin/users`
- [ ] Role middleware (superadmin)

### Sprint 5 — Integrasi Subdomain & Provisioning (Minggu 9-10)
- [ ] Wildcard DNS `*.megakomsel.com` + SSL
- [ ] Pola subdomain `{tenant-slug}-{app-slug}.megakomsel.com` + parsing di edge/balancer
- [ ] `TenantProvisioningService` + `ProvisionTenantJob` → panggil webhook app produk (Toyaa, Absensi, dll)
- [ ] Mapping role SSO Central → app produk

---

## 15. Frontend Pages

| Route | Page | Priority |
|-------|------|----------|
| `/` | Landing page | Must |
| `/register` | Registrasi | Must |
| `/login` | Login | Must |
| `/verify-email` | Verifikasi email | Must |
| `/apps` | Katalog aplikasi | Must |
| `/dashboard` | Dashboard tenant | Must |
| `/subscriptions` | Detail langganan | Must |
| `/payments` | Riwayat + upload bukti | Must |
| `/admin/tenants` | Admin: daftar tenant | Must |
| `/admin/tenants/{id}` | Admin: detail tenant | Should |
| `/admin/payments` | Admin: konfirmasi payment | Must |
| `/admin/apps` | Admin: katalog app | Should |
| `/admin/users` | Admin: user platform | Should |

---

## 16. Permission Mapping

| Permission | superadmin | tenant owner | tenant member | guest |
|------------|------------|--------------|---------------|-------|
| register/login | ✅ | ✅ | ✅ | ✅ |
| lihat katalog apps | ✅ | ✅ | ✅ | ✅ |
| buat subscription | ✅ | ✅ | ✅ | ❌ |
| upload bukti bayar | ✅ | ✅ | ✅ | ❌ |
| dashboard tenant | ✅ | ✅ | ✅ | ❌ |
| admin: kelola tenant | ✅ | ❌ | ❌ | ❌ |
| admin: konfirmasi payment | ✅ | ❌ | ❌ | ❌ |
| admin: kelola apps | ✅ | ❌ | ❌ | ❌ |

---

## Pertanyaan yang Belum Terjawab

| # | Pertanyaan | Keputusan |
|---|-----------|-----------|
| 1 | Trial 7 hari otomatis atau langsung wajib bayar? | ✅ Trial 7 hari OTOMATIS (status `trialing`, `trial_ends_at` = starts_at + 7 hari) |
| 2 | Harga plan tiap app berapa? (Toyaa, Kasir, Laundry) | Sementara: Toyaa Rp50.000/bln; Kasir UMKM & Laundry Rp25.000/bln — editable via `/admin/apps` |
| 3 | Tenant provisioning: central panggil API Toyaa, atau Toyaa yang polling central? | ✅ Central panggil webhook app produk saat subscription dibuat (trialing); app balas status provisioning |
| 4 | Detail fitur Toyaa (pelanggan, tarif, catat meter) — PRD terpisah, kapan? | — |
| 5 | SMTP produksi pakai apa? | — |

---

## Perubahan dari PRD Sebelumnya

- **v0.3 (2026-08-11):** Alur utama baru — registrasi → dashboard → lengkapi tenancy (FR-009) → pilih aplikasi → auto-login SSO (FR-010). Pola subdomain berubah ke `{tenant-slug}-{app-slug}.megakomsel.com` (FR-008). Absensi ditambahkan ke katalog apps. Provisioning dipicu saat subscription dibuat (trialing), bukan setelah payment. Role app dikelola di dalam app masing-masing.
- **v0.2 (2026-08-11):** Update stack compatibility — Laravel 11+ / PHP 8.2+ → **Laravel 13 / PHP 8.3+** (sesuai kondisi aktual project: Laravel 13.24, PHP 8.4.6).
- **v0.2 (2026-08-11):** Keputusan frontend — MVP auth memakai **Blade** (sudah terimplementasi); Inertia.js + Vue 3 dijadwalkan untuk halaman apps/dashboard mulai Sprint 2.
- **v0.2 (2026-08-11):** Implementasi central selesai — katalog apps + subscription (trial 7 hari) + payment manual (upload bukti, gateway abstraction) + admin panel (superadmin) + API auth `/api/v1/auth/*` (Sanctum token). Sprint 5 (provisioning ke app produk) ditunda — menunggu PRD Toyaa & infra DNS wildcard. Kolom `users.is_admin` ditambahkan untuk role superadmin platform.
