# portal-app — Central Platform megakomsel.com

## Konteks Project

Portal pusat SaaS megakomsel.com: registrasi user, katalog aplikasi (Toyaa, Kasir UMKM, Laundry), langganan, pembayaran manual (siap Midtrans), dan admin panel pusat. Produk pertama: Toyaa (aplikasi air/meteran) — PRD terpisah, menyusul.

## Arsitektur

- Laravel 13, PHP 8.4, MySQL 8 (database `central_db`)
- Struktur modular: `app/Core/Modules/{Module}/`
  - Auth, Tenant, Apps, Subscription, Payment, Admin
- `ModuleServiceProvider` di `app/Core/` auto-load routes & migrations tiap modul
- Namespace: `App\Core\Modules\{Module}\Controllers`, `...Models`, dll
- Frontend: Inertia.js + Vue 3
- **Central = 1 database tanpa tenancy** (data global platform). Aplikasi produk (Toyaa dkk) punya arsitektur 1 tenant = 1 DB sendiri-sendiri (di luar scope project ini).

## Aturan Main

1. Semua fitur masuk ke dalam modul di `app/Core/Modules/` — JANGAN menaruh logic di folder global `app/` kecuali memang shared lintas modul.
2. Migrasi tiap modul: `database/migrations/` dalam folder modul (mis. `app/Core/Modules/Auth/Database/Migrations/`) — ModuleServiceProvider akan memuatnya.
3. Routes tiap modul: `app/Core/Modules/{Module}/Routes/web.php` dan/atau `api.php`.
4. Ikuti PRD di `docs/PRD_CENTRAL_URANOP.md` — itu sumber kebenaran.
5. Tabel wajib: `users`, `tenants`, `tenant_user` (pivot), `apps`, `subscriptions`, `payments`.
6. Bahasa kode: Inggris (identifier), komentar singkat Bahasa Indonesia bila perlu.
7. Jangan over-engineering: implementasi yang cukup untuk kebutuhan PRD.

## Database (central_db)

| Tabel | Kolom penting |
|-------|---------------|
| users | name, email, password, email_verified_at, phone, remember_token |
| tenants | name, slug (unique, untuk subdomain), email, phone, address, status (pending/active/suspended) |
| tenant_user | tenant_id, user_id, role (owner/member/admin) |
| apps | slug (toyaa/kasirumkm/laundry), name, description, price_monthly, status (available/coming_soon) |
| subscriptions | tenant_id, app_id, plan (monthly/yearly), status (trialing/active/past_due/canceled), trial_ends_at, starts_at, ends_at |
| payments | subscription_id, tenant_id, amount, method (manual_transfer/midtrans), status (pending/confirmed/rejected/failed), proof_image, gateway_ref, confirmed_by, confirmed_at, notes |

## Fitur Auth (Sprint 1)

- Registrasi: nama, email, password + konfirmasi → auto-create `users` + `tenants` (status pending)
- Login email+password, logout
- Email verifikasi (wajib sebelum login penuh)
- Lupa password / reset via email
- Route prefix API: `/api/v1/auth/*`
