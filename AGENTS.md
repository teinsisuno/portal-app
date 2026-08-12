# portal-app — Central Platform megakomsel.com

## Konteks Project

Portal pusat SaaS megakomsel.com: registrasi user, katalog aplikasi (Toyaa, Kasir UMKM, Laundry, **Absensi**), langganan, pembayaran manual (siap Midtrans), dan admin panel pusat. Produk pertama yang dibangun: **Absensi** (PRD docs/PRD_ABSENSI_URANOP.md) — Laravel API + Nuxt4 PWA terpisah, arsitektur 1 tenant = 1 DB (tenant_absensi_{slug}).

## Deploy (mini-pc 10.10.10.122)

- Docker compose `portal-app` (port **8080**) + `mysql:8` (central_db). pepepe tetap di port 80.
- Domain: root `megakomsel.com` → central; wildcard `*.megakomsel.com` → central (tenant subdomain); `tein.my.id` → pepepe.
- Tunnel Zero Trust megakomsel (systemd cloudflared) → service URL `http://10.10.10.122:8080` (bukan localhost — dari dalam container cloudflared, localhost = container sendiri).
- Deploy: `scp`/`tar` ke `~/portal-app`, lalu `docker compose up -d --build`. Migrasi + seed apps otomatis di entrypoint.
- Pola subdomain tenant-app: `{tenant-slug}-{app-slug}.megakomsel.com` (mis. pdam-xyz-absensi).
- ⚠️ JANGAN kirim `public/hot` ke image produksi (Vite dev signal → asset URLs broken). Sudah di `.dockerignore`.
- Project ini GIT LOCAL (master) — belum ada remote.

## Deploy Absensi-app (produk, mini-pc sama)

- Repo: `H:\laragon\www\absensi-app` (Laravel API + stancl tenancy 1 tenant = 1 DB `tenant_absensi_{slug}` + frontend Nuxt4 SSG di `public/` Laravel = 1 origin).
- Docker: `docker compose --env-file docker/.env up -d --build` di `~/absensi-app` — app **port 8081** + mysql + redis (stancl butuh redis utk cache tags). `.env.production` di-mount (DB_* hardcode, bukan ${VAR}).
- `.htaccess` khusus: `DirectoryIndex index.html` + `/api/*` & `/up` → index.php, non-file → index.html (SPA fallback). Wajib — image php-apache default DirectoryIndex index.php dulu.
- MySQL: user app HARUS `GRANT ALL PRIVILEGES ON *.*` (stancl CREATE DATABASE tenant gagal 1044 tanpa itu).
- Secret `ABSENSI_SSO_SECRET` + `ABSENSI_WEBHOOK_SECRET` di absensi-app WAJIB sama dengan `.env` portal-app (webhook + SSO signed token).
- Portal-app `.env` prod: `ABSENSI_BASE_URL=http://10.10.10.122:8081` (webhook server-to-server), `ABSENSI_TENANT_DOMAIN_PATTERN=https://{slug}-absensi.megakomsel.com` (SSO redirect).
- Tunnel Zero Trust butuh hostname `*.absensi.megakomsel.com` → HTTP `10.10.10.122:8081` (wildcard `*.megakomsel.com` → 8080 cuma central).

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
4. Ikuti PRD di `docs/PRD_CENTRAL_URANOP.md` (Central v0.3) dan `docs/PRD_ABSENSI_URANOP.md` (produk Absensi v0.3) — itu sumber kebenaran.
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
