# PRD — Absensi (Aplikasi Absensi Karyawan)

## 1. Header Metadata

| Field | Nilai |
|-------|-------|
| Author | Sigit / Paijo |
| Project | Absensi (produk baru di dalam platform megakomsel.com) |
| Fase | MVP Mobile App (PWA) |
| Versi | v0.4 |
| Status | Draft — disinkronkan dengan progress aktual (2026-08-12) |
| Tanggal | 2026-08-12 |
| Dependencies | Central Platform megakomsel.com (registrasi, onboarding tenancy, billing, tenant provisioning, auto-login SSO) |

---

## 2. Executive Summary

Absensi adalah aplikasi manajemen kehadiran karyawan (mirip Talenta by Mekari) yang menjadi salah satu produk di ekosistem **megakomsel.com** — berdampingan dengan Toyaa, Kasir UMKM, dan Laundry. Tenant mendaftar & berlangganan lewat Central, lalu Central melakukan provisioning database khusus untuk tenant tersebut di aplikasi Absensi (pola 1 tenant = 1 database, konsisten dengan arsitektur Toyaa).

MVP berbentuk **mobile app berbasis PWA** (installable, offline-first untuk clock in/out), dibangun dengan **Laravel 13 API** sebagai backend dan **Nuxt4** sebagai frontend terpisah. **Semua user (termasuk karyawan) wajib punya akun email + password** — registrasi mandiri dari app, lalu **set PIN sendiri** sebagai cara login cepat untuk pemakaian harian (lapangan/warehouse/toko). Owner/admin tenant masuk via auto-login SSO dari Central (satu akun untuk semua app). **HR** mengelola data karyawan & membagikan **kode unik** untuk menautkan akun user ke data karyawan (karena tidak semua user adalah karyawan). Verifikasi kehadiran memakai **GPS radius + face recognition** (dengan liveness detection), dan **semua approval pengajuan terpusat di HR**.

Fitur inti MVP: clock in/out dengan validasi GPS radius kantor + verifikasi wajah, manajemen shift & jadwal, pengajuan izin/cuti/sakit & lembur dengan alur approval HR, kunjungan lapangan (foto selfie + koordinat), tugas, pengumuman, serta monitoring untuk level management.

### Status Progress (2026-08-12)

- **Backend**: 65 test pass. Sprint 1-3 selesai. Provisioning, SSO, auth v0.3, CRUD karyawan, clock in/out + GPS, kode unik, leave request (karyawan), manajemen shift & jadwal (WorkPattern, Shift, ScheduleSnapshot, WorkingCalendar, Holiday, EmployeeGroup), admin dashboard + roster absensi, WebAuthn, employee submodules (detail, bank, dokumen, keluarga, kontrak).
- **Frontend Nuxt4**: v0.3 berjalan. Splash → register → set-pin → setup (kode unik + scan wajah) → dashboard → clock → attendance → leave-request → calendar → profile. Admin web: dashboard, employees, invite-codes, locations, shifts, groups, calendars, work-patterns, schedules. PWA terinstall (manifest + SW). E2E flow 6/6 lulus.
- **PROD deployed**: Mini-PC `10.10.10.122`, tenant aktif: tokoa, toko-uji, uranop.

---

## 3. Problem Statement

- Tenant di megakomsel.com butuh cara mencatat kehadiran karyawan tanpa mesin fingerprint fisik, terutama untuk bisnis dengan karyawan lapangan/multi-outlet.
- Absensi manual (kertas/Excel) rawan manipulasi jam & lokasi, serta sulit direkap untuk payroll.
- Karyawan butuh cara absen yang sangat cepat — registrasi email+password sekali di awal, lalu login harian cukup pakai **PIN** (lebih cepat dari email/password tiap hari).
- Titip absen harus dicegah — verifikasi **face recognition** (bukan sekadar foto manual) dipakai saat clock in/out.
- Approval pengajuan harus konsisten & terpusat — **HR** sebagai satu-satunya pihak yang menyetujui/menolak (supervisor/atasan hanya bisa mengajukan, tidak approve).
- Owner/management butuh visibilitas real-time: siapa yang sudah absen, siapa yang terlambat, siapa yang mengajukan izin — dari satu dashboard, tanpa harus ikut absen.

---

## 4. Goals & Success Metrics

| Goal | Metric | Baseline | Target | Timeline |
|------|--------|----------|--------|----------|
| Absen cepat & anti-curang | Waktu proses clock in | — | < 10 detik, GPS+face tervalidasi | Sprint 1-2 |
| Onboarding karyawan mudah | Waktu HR menambah 1 karyawan | — | < 1 menit (generate kode unik otomatis) | Sprint 1 ✅ |
| Registrasi mandiri cepat | Waktu user selesai register → siap absen | — | < 3 menit (register → PIN → kode unik → scan wajah) | Sprint 3 ✅ (tanpa face recognition beneran) |
| Approval izin/cuti/lembur cepat | Waktu rata-rata approval HR | — | < 24 jam | Sprint 4 |
| PWA installable | % karyawan yang install ke homescreen | — | > 60% tenant aktif | Sprint 2 ✅ |
| Terintegrasi dengan Central | Provisioning tenant otomatis saat subscription aktif | — | 0 provisioning manual | Sprint 4 ✅ |

---

## 5. Data Flow Diagram (Wajib!)

```
[Central megakomsel.com] ── subscription active ──► webhook ──► [Absensi API]
                                                              │
                                                    provisioning: buat DB tenant baru
                                                              │
                                                              ▼
                                                    tenant_absensi_{slug} (DB terpisah)

[Owner/Admin Tenant] ──► pilih app / klik "Buka App" di Dashboard Central
     │ auto-login: signed SSO token (tenant_id, user_id, role, exp)
     ▼
[Absensi App - Nuxt4 PWA] ──► verifikasi token ke Absensi API ──► session

[User] buka PWA ──► splash (1-1.5 detik)
     │ belum punya akun? (pertama kali)
     ▼
[Register] email + nama + password ──► [Set PIN] (4-6 digit, dipakai login cepat)
     ▼
[Pengaturan Awal] masukkan kode unik dari HR ──► nama karyawan muncul otomatis
     │ scan wajah (video, liveness) ──► tombol Simpan aktif
     ▼
[Dashboard] (menu sesuai role)

[HR (web)] ──► kelola karyawan, lokasi, shift, group, kalender, pola kerja, jadwal
     │ generate kode unik per karyawan (one-time, expired) ──► bagikan ke karyawan
     ▼
[Karyawan] ──► buka PWA ──► login PIN (atau email+password, atau WebAuthn biometrik)
     ▼
[Clock In/Out] ──► GPS + selfie foto ──► validasi radius ──► attendance record
     │
     ▼
[Pengajuan izin/cuti/sakit/lembur] ──► approval HR (satu-satunya approver) ──► status
     │
     ▼
[Management/Direktur/Owner] ──► monitoring absensi, task giving, pengumuman
```

Alur SSO dari Central:

```
Central (Blade/Inertia) ── saat subscription dibuat / klik "Buka App":
     generate signed token (JWT, short-lived) ──►
     redirect ke https://{tenant-slug}-absensi.megakomsel.com/sso?token=xxx
     ▼
Absensi API ── verifikasi signature & expiry token ──► buat/cocokkan user admin ──► session
```

---

## 6. Target Persona

| Role | Persona | Pain Point | Goal |
|------|---------|------------|------|
| Superadmin (owner) | Pemilik tenant, login dari Central SSO atau web | Ingin kontrol penuh & visibilitas tanpa harus absen | Kelola karyawan, generate kode unik, approve semua pengajuan, lihat rekap, monitoring |
| HR Manager | Pengelola tenant via web | Ingin kontrol penuh data karyawan & approval terpusat | Kelola karyawan, generate kode unik, approve semua pengajuan, lihat rekap, kelola jadwal & shift |
| Supervisor/Mandor | Kepala toko/shift leader (PWA only) | Perlu lihat tim & mengajukan, tanpa kuasa approve | Kelola group, buat jadwal bawahan, ajukan izin/lembur, lihat jadwal tim |
| Karyawan | Staff toko/kurir/warehouse (PWA only) | Registrasi sekali, lalu absen cepat | Absen dalam hitungan detik pakai PIN + selfie |
| Management/Direktur | Pemilik/pimpinan (tidak ikut absen, PWA only) | Butuh visibilitas tanpa ikut alur kehadiran | Monitoring absensi, memberi tugas, membuat pengumuman |

**Catatan role aktual di kode** (`users.role`):
- `superadmin` — owner tenant (SSO Central), akses web + PWA monitoring, tanpa relasi karyawan
- `hr` — admin/HR, akses web full + PWA approval
- `employee` — user mobile/PWA (bisa karyawan, supervisor, atau management tergantung `employees.mobile_role`)

**`employees.mobile_role`**: `karyawan` / `supervisor` / `management`

---

## 7. Functional Requirements (FR)

### FR-001: Provisioning Tenant (dari Central)
- **Priority:** Must
- **Status:** ✅ SELESAI (65 test pass)
- **User Story:** Sebagai sistem, saya ingin membuat database tenant baru otomatis saat subscription Absensi aktif di Central, agar tenant langsung bisa pakai app.
- **Acceptance Criteria:**
  - [x] Dipicu otomatis saat subscription Absensi dibuat di Central (status trialing) — sebelum auto-login
  - [x] Endpoint webhook `POST /api/v1/provisioning/tenant` menerima payload dari Central (`tenant_slug`, `tenant_name`, `owner_email`, `subscription_id`, `central_tenant_id`)
  - [x] Membuat database baru `tenant_absensi_{slug}` + migrasi otomatis
  - [x] Mencatat mapping tenant di tabel pusat `tenant_meta` (di DB Absensi sendiri, bukan central_db)
  - [x] Idempotent — webhook terpanggil 2x tidak membuat DB dobel
  - [x] Response sukses/gagal dikirim balik ke Central (status `queued`, HTTP 202)
  - [x] Diproses async via `ProvisionTenantJob`
  - [x] Diproteksi secret header `X-Absensi-Webhook-Secret`

### FR-002: SSO Login Owner/Admin
- **Priority:** Must
- **Status:** ✅ SELESAI (5 test)
- **User Story:** Sebagai owner tenant, saya ingin masuk ke app Absensi langsung dari dashboard Central tanpa login ulang.
- **Acceptance Criteria:**
  - [x] Auto-login langsung setelah pilih aplikasi di Central (atau klik "Buka App") — tanpa login manual
  - [x] Central generate signed token (HMAC, expired < 60 detik, one-time) berisi `tenant_slug`, `central_user_id`, `email`, `name`, `role`
  - [x] Redirect ke `https://{tenant-slug}-absensi.megakomsel.com/sso?token=xxx`
  - [x] Absensi API endpoint `POST /api/v1/auth/sso` memverifikasi signature & expiry
  - [x] Auto-create/update user admin di DB tenant Absensi bila belum ada
  - [x] Mapping role: Central owner → Absensi `superadmin`; Central member/admin → Absensi `hr`
  - [x] Session/token Absensi (Sanctum) diterbitkan untuk dipakai Nuxt4
  - [x] Token SSO ditolak jika sudah expired, terpakai, atau signature tidak valid
  - [x] Admin juga bisa login langsung dari subdomain dengan email+password akun Central (`POST /api/v1/auth/admin-login`)

### FR-003: Registrasi, PIN & Link Karyawan (Kode Unik)
- **Priority:** Must
- **Status:** ✅ SELESAI (14 test)
- **User Story:** Sebagai user baru, saya ingin daftar dengan email, set PIN, dan menautkan akun saya ke data karyawan lewat kode unik dari HR.
- **Acceptance Criteria:**
  - [x] Registrasi mandiri: email + nama + password (tanpa Google OAuth di MVP)
  - [x] Setelah register, user diminta **set PIN 4-6 digit** (dipakai untuk login cepat berikutnya; bisa di-reset via password)
  - [x] Login alternatif: email+password ATAU PIN
  - [x] HR generate **kode unik** per karyawan dari web (one-time use, expired default 48 jam, bisa di-regenerate); kode ditampilkan sekali & dibagikan manual ke karyawan
  - [x] Saat kode unik dimasukkan di halaman pengaturan awal, **nama karyawan muncul otomatis** di bawah field (validasi bahwa kode benar)
  - [x] Kode unik hanya bisa dipakai sekali (setelah link, `used_at` terisi); percobaan salah di-rate limit
  - [x] Setelah link sukses + scan wajah selesai, tombol Simpan aktif → masuk dashboard
  - [x] Tidak semua user adalah karyawan: user tanpa kode unik tetap bisa login (mis. manajemen) tapi tidak punya data kehadiran
  - [x] Rate limit PIN: 5x salah → lock 15 menit

### FR-004: Clock In / Clock Out dengan GPS
- **Priority:** Must
- **Status:** ✅ SELESAI (10 test)
- **User Story:** Sebagai karyawan, saya ingin absen masuk/pulang dengan validasi lokasi, agar kehadiran saya tercatat akurat.
- **Acceptance Criteria:**
  - [x] Karyawan login PIN → tombol "Clock In" / "Clock Out" muncul sesuai status
  - [x] Sistem ambil koordinat GPS browser, validasi terhadap radius lokasi kantor (default radius dikonfigurasi HR, mis. 100m)
  - [x] Jika di luar radius, absen ditolak dengan pesan jelas
  - [x] Setiap tenant bisa punya lebih dari satu lokasi kerja (multi-outlet) — `is_active` flag
  - [x] Waktu absen dicatat dari server (`recorded_at`) untuk cegah manipulasi jam
  - [x] Force mode: clock in/out ulang untuk menambah riwayat
  - [x] Selfie photo (base64) bisa disertakan → disimpan di `selfie_photo` (LONGTEXT)
  - [x] Formula haversine untuk hitung jarak GPS

### FR-005: Verifikasi Wajah (Face Recognition)
- **Priority:** Must (masuk scope MVP — perubahan dari v0.2)
- **Status:** ⚠️ BELUM DIKERJAKAN — hanya model & migrasi `face_templates` sudah ada
- **User Story:** Sebagai karyawan, saya ingin wajah saya diverifikasi otomatis saat absen, agar tidak ada titip absen.
- **Acceptance Criteria:**
  - [x] Tabel `face_templates` + model `FaceTemplate` sudah dibuat (employee_id, template, mode)
  - [x] Halaman setup face di frontend (`/setup/face`) — UI siap, tapi belum ada integrasi face-api.js
  - [ ] **Controller & Service face recognition** — `POST /api/v1/face/enroll` dan `POST /api/v1/face/verify` belum ada
  - [ ] Saat pengaturan awal, karyawan **scan wajah** (video singkat untuk liveness detection) → template wajah disimpan **di server** (per-tenant DB)
  - [ ] Saat clock in/out, kamera device ambil wajah → dicocokkan dengan template → wajib cocok + GPS valid baru absen tercatat
  - [ ] Liveness detection (anti foto print / foto HP): pakai video capture, bukan foto diam
  - [ ] **Dua mode implementasi: client-side dan server-side** — satu library `face-api.js` (TensorFlow.js) untuk keduanya, bisa dipilih HR di pengaturan (default: server-side)
    - Server-side: template & matching di server (Node/Nitro, face-api.js), dipanggil internal API
    - Client-side: matching di device (face-api.js di browser/PWA), template tetap dikirim & disimpan di server untuk konsistensi lintas device
    - Jika performa/akurasi Node kurang memadai → migrasi ke Python microservice (insightface/ArcFace)
  - [ ] Ganti device → karyawan cukup login, template tetap valid (tanpa enroll ulang)
  - [ ] Template wajah disimpan privat (bukan public URL), akses terbatas
  - [ ] Kolom `face_verified` dan `face_mode` perlu ditambahkan ke tabel `attendances`

### FR-006: Manajemen Shift & Jadwal
- **Priority:** Should → dinaikkan jadi **Must** (backend sudah selesai)
- **Status:** ✅ SELESAI (backend, 7 test) — implementasi lebih kaya dari spesifikasi PRD awal
- **User Story:** Sebagai HR, saya ingin atur jadwal shift karyawan, agar jam kerja & keterlambatan bisa dihitung otomatis.
- **Acceptance Criteria:**
  - [x] HR buat **Work Pattern** (pola kerja: nama, jam kerja per hari termasuk istirahat, weekend hari apa, is_active)
  - [x] HR buat **Shift** terkait Work Pattern (nama, kode, jam kerja, check-in/out window, is_overnight, toleransi terlambat, min_work_hours, has_overtime)
  - [x] **Working Calendar** — template kalender kerja per tahun/bulan (hari kerja/libur/weekend)
  - [x] **Holiday** — daftar hari libur/tanggal merah per tahun
  - [x] HR assign jadwal via **Schedule Snapshot** (employee_id + date unique, shift, status, is_holiday/leave/permit, metadata)
  - [x] Karyawan bisa lihat jadwal kerjanya (mobile `/calendar` + `GET /schedule-snapshots/me`)
  - [x] Supervisor bisa lihat jadwal group yang dia pimpin (`GET /schedule-snapshots/me?group_id=N`)
  - [x] **Employee Group** — many-to-many dengan supervisor (kepala group)
  - [x] Sistem otomatis tandai status: tepat waktu / terlambat / pulang cepat, berdasarkan shift yang di-assign
  - [x] UUID + external_code + synced_at di semua entitas (siap integrasi HRIS sync/pull)

> **Catatan**: Spesifikasi PRD awal (v0.2/v0.3) hanya menyebut shift sederhana (`name, start_time, end_time, tolerance_minutes`). Implementasi aktual sudah jauh lebih kaya dengan WorkPattern → Shift → ScheduleSnapshot + WorkingCalendar + Holiday + EmployeeGroup, mengikuti pola HRIS.

### FR-007: Pengajuan Izin/Cuti/Sakit & Lembur (Approval HR)
- **Priority:** Must
- **Status:** ⚠️ 60% — sisi karyawan selesai, sisi HR belum
- **User Story:** Sebagai karyawan, saya ingin mengajukan izin/cuti/sakit atau lembur dari app, agar tidak perlu WA manual ke HR.
- **Acceptance Criteria:**
  - [x] Karyawan ajukan pengajuan lewat **menu dropdown**: izin / cuti / sakit (tanggal, jenis, alasan, lampiran opsional)
  - [x] Karyawan bisa lihat riwayat pengajuan sendiri (`GET /leave-requests/me`)
  - [x] Karyawan bisa batalkan pengajuan yang masih pending (`POST /leave-requests/{id}/cancel`)
  - [ ] **Notifikasi masuk ke HR** (in-app; opsional email) — belum ada
  - [ ] **HR adalah satu-satunya approver** — endpoint `POST /leave-requests/{id}/approve` dan `/{id}/reject` belum ada
  - [ ] HR approve/reject dengan catatan; status pengajuan terlihat oleh karyawan (pending/disetujui/ditolak)
  - [ ] Izin/cuti yang disetujui otomatis mempengaruhi rekap kehadiran (tidak dihitung alpha)
  - [ ] **Pengajuan lembur** — tabel `overtime_requests` belum dibuat
  - [ ] **Halaman web HR** untuk approve/reject leave & overtime — belum ada

### FR-008: Dashboard & Rekap Kehadiran
- **Priority:** Must
- **Status:** ⚠️ 70% — roster + detail + stats sudah, export belum
- **Acceptance Criteria:**
  - [x] Dashboard admin: ringkasan (employees_active/total, groups, shifts, patterns, holidays, snapshots, clock_in/out_today) — `GET /admin/stats`
  - [x] Roster absensi: grid karyawan × tanggal dengan ringkasan clock in/out per sel — `GET /attendance/roster?from=&to=&group_id=`
  - [x] Detail harian per karyawan: semua record + foto selfie — `GET /attendance/roster/{employee}?date=`
  - [x] Dashboard mobile: status hari ini, riwayat absensi, pengumuman, menu grid
  - [x] Halaman riwayat absensi mobile (`/attendance` + `GET /attendance/me`)
  - [x] Halaman kalender mobile (`/calendar` + `GET /schedule-snapshots/me`)
  - [ ] **Export rekap ke Excel/CSV** — endpoint `GET /attendance/export` belum ada
  - [ ] Halaman rekap bulanan per karyawan (tabel + filter tanggal) di web admin

### FR-009: Monitoring Management (Direktur/Owner)
- **Priority:** Should
- **Status:** ❌ BELUM DIKERJAKAN
- **User Story:** Sebagai management, saya ingin melihat monitoring absensi tanpa ikut absen.
- **Acceptance Criteria:**
  - [ ] Role management/direktur/owner (dari `employees.mobile_role = management`) mendapat menu: monitoring absensi, task giving, pengumuman
  - [ ] Management TIDAK ikut alur clock in/out (observer murni)
  - [ ] Monitoring: ringkasan kehadiran tim, tanpa akses kelola karyawan/approval

### FR-010: Kunjungan (Field Visit)
- **Priority:** Should
- **Status:** ❌ BELUM DIKERJAKAN
- **User Story:** Sebagai karyawan, saya ingin mencatat kunjungan dengan foto selfie + koordinat + keterangan.
- **Acceptance Criteria:**
  - [ ] Tabel `visits` belum dibuat (model, migration, controller)
  - [ ] Karyawan buat kunjungan: foto selfie, koordinat GPS otomatis, keterangan, waktu
  - [ ] HR bisa lihat daftar kunjungan karyawan (web)

### FR-011: Tugas (Task Giving)
- **Priority:** Should
- **Status:** ❌ BELUM DIKERJAKAN
- **User Story:** Sebagai HR/management, saya ingin memberi tugas ke karyawan dan memantau statusnya.
- **Acceptance Criteria:**
  - [ ] Tabel `tasks` belum dibuat (model, migration, controller)
  - [ ] HR/management buat tugas (judul, deskripsi, assignee, due date)
  - [ ] Karyawan lihat daftar tugas & update status (pending/in_progress/done)

### FR-012: Pengumuman
- **Priority:** Should
- **Status:** ❌ BELUM DIKERJAKAN
- **User Story:** Sebagai HR/management, saya ingin membuat pengumuman yang terlihat semua karyawan.
- **Acceptance Criteria:**
  - [ ] Tabel `announcements` belum dibuat (model, migration, controller)
  - [ ] HR/management buat pengumuman (judul, isi, publish)
  - [ ] Karyawan lihat daftar pengumuman di mobile

### FR-013: Biodata & Dokumen
- **Priority:** Should
- **Status:** ⚠️ 50% — model & migrasi sudah, API endpoint belum
- **User Story:** Sebagai karyawan, saya ingin melihat biodata & dokumen saya.
- **Acceptance Criteria:**
  - [x] Tabel `employee_details` sudah dibuat (nik, phone, address, gender, birth_date, dll — 1:1 ke employee)
  - [x] Tabel `employee_banks` sudah dibuat (bank name, account number, dll — 1:N ke employee)
  - [x] Tabel `employee_documents` sudah dibuat (name, type, file_path — 1:N ke employee)
  - [x] Tabel `employee_families` sudah dibuat (nama, hubungan, phone — 1:N ke employee)
  - [x] Tabel `employee_contracts` sudah dibuat (contract number, start/end date, type — 1:N ke employee)
  - [ ] **API endpoint `GET /me` dan `GET /me/documents`** belum dibuat
  - [ ] Upload/edit dokumen oleh HR dari web — belum ada halaman admin
  - [ ] Karyawan lihat biodata & dokumen di mobile — halaman `/profile` sudah ada tapi belum menampilkan data submodule

### FR-014: PWA & Offline Handling
- **Priority:** Should
- **Status:** ⚠️ 60% — PWA terinstall, offline queue belum
- **Acceptance Criteria:**
  - [x] App installable ke homescreen (manifest + service worker `sw.js`)
  - [x] Icons 192x192 + 512x512
  - [x] Splash screen 1-1.5 detik (logo center + loading circle, lalu scale-in hilang)
  - [x] Tema teal (#0f766e) — konsisten di seluruh PWA
  - [x] Halaman mobile dengan layout `mobile` + `MobileNav.vue` bottom nav
  - [ ] **Offline queue** — jika koneksi terputus saat submit absen, data disimpan sementara (queue) dan dikirim ulang otomatis saat online — belum diimplementasikan
  - [ ] Indikator status koneksi ditampilkan ke user — belum ada

### FR-015: WebAuthn / Biometrik Login (TAMBAHAN — tidak di PRD awal)
- **Priority:** Should
- **Status:** ✅ SELESAI (6 test)
- **User Story:** Sebagai karyawan, saya ingin login dengan sidik jari / face ID device, agar lebih cepat dari PIN.
- **Acceptance Criteria:**
  - [x] Login WebAuthn publik (userless — server cari user dari credential)
  - [x] Register biometrik: wajib login PIN dulu, lalu daftarkan kunci
  - [x] Kelola kunci biometrik (list, delete)
  - [x] Frontend: tombol "Login dengan Biometrik" di halaman login karyawan
  - [x] Library: `laravel-webauthn` server-side, `@simplewebauthn/browser` client-side

---

## 8. Non-Functional Requirements

- **Performance:** Proses clock in/out end-to-end < 3 detik (di luar waktu ambil GPS/kamera); halaman absen ringan untuk device low-end.
- **Security:** PIN di-hash; rate limit percobaan PIN & kode unik salah; token SSO ditandatangani (HMAC) & short-lived; **template wajah disimpan privat & terenkripsi (bukan public URL)**; kode unik one-time + expiry.
- **Reliability:** Waktu absen selalu diambil dari server, bukan client; idempotent pada webhook provisioning.
- **Privacy:** Template wajah hanya dipakai untuk verifikasi absensi; retensi & penghapusan data wajah diatur HR (pertanyaan retensi masih terbuka).
- **Scalability:** Arsitektur 1 tenant = 1 database (konsisten dengan Toyaa), backend Laravel API stateless agar mudah di-scale horizontal.
- **Compatibility:** Laravel 13 / PHP 8.4 (selaras dengan stack Central); Nuxt4 + Tailwind untuk frontend; MySQL 8; PWA lewat `@vite-pwa/nuxt`.
- **Usability:** Login harian karyawan (PIN) harus bisa dituntaskan dalam ≤ 3 tap/klik dari halaman awal.

---

## 9. Scope

| ✅ In Scope (MVP Mobile App/PWA) | ❌ Out of Scope (nanti) |
|-------------------------|------------------------|
| Provisioning tenant dari Central (webhook) ✅ | Aplikasi mobile native (Android/iOS) |
| SSO login admin dari Central ✅ | Google/Apple OAuth login |
| Registrasi email+password + set PIN ✅ | Anti fake-GPS/mock location detection |
| Kode unik link user↔karyawan (dari HR) ✅ | Integrasi payroll otomatis |
| Clock in/out + GPS radius ✅ | Multi-bahasa |
| Face recognition (enroll + verify saat absen, liveness, mode client/server) ⚠️ | Absen via fingerprint/RFID fisik |
| Manajemen shift & jadwal (WorkPattern, Shift, ScheduleSnapshot) ✅ | Notifikasi push native |
| Pengajuan izin/cuti/sakit (karyawan) ✅, approval HR ❌ | Live location tracking real-time (hanya snapshot saat absen) |
| Pengajuan lembur ❌ | Perhitungan lembur otomatis ke gaji (baru pengajuan) |
| Dashboard HR + roster absensi ✅, export ❌ | |
| Kunjungan (selfie + GPS + keterangan) ❌ | |
| Tugas (task giving + status) ❌ | |
| Pengumuman ❌ | |
| Biodata & dokumen karyawan (model & migrasi ✅, API ❌) | |
| PWA installable ✅, offline queue ❌ | |
| WebAuthn / biometrik login ✅ | |
| Employee Groups ✅ | |
| Working Calendar & Holiday ✅ | |

---

## 10. Tabel Database

Setiap tenant punya database sendiri: `tenant_absensi_{slug}` (dibuat saat provisioning). Skema di bawah berlaku di dalam DB per-tenant tersebut, kecuali disebutkan lain.

### tenant_meta (disimpan di DB Absensi pusat / connection `central`, bukan per-tenant)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| central_tenant_id | bigint | referensi tenant di central_db |
| slug | string unique | dipakai untuk nama DB & subdomain |
| db_name | string | `tenant_absensi_{slug}` |
| status | enum | provisioning / active / suspended |
| provisioned_at | timestamp nullable | |
| timestamps | | |

### users (semua yang bisa login — karyawan & non-karyawan)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| central_user_id | bigint nullable, index | referensi user di Central (dari SSO) |
| name | string | |
| email | string unique | wajib (registrasi mandiri) |
| password_hash | string nullable | null = user SSO Central |
| pin_hash | string nullable | PIN 4-6 digit, di-set user sendiri setelah register |
| role | string default 'employee' | `superadmin` / `hr` / `employee` |
| timestamps | | |

> **Catatan**: Role di `users.role` berbeda dengan `employees.mobile_role`. `users.role` menentukan akses web vs PWA. `employees.mobile_role` menentukan menu yang tampil di PWA (`karyawan` / `supervisor` / `management`).

### employees (karyawan, per-tenant DB)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| user_id | bigint FK users nullable unique | terisi saat kode unik dipakai (link akun) |
| name | string | |
| photo | string nullable | |
| position | string nullable | jabatan (display) |
| mobile_role | string default 'karyawan' | `karyawan` / `supervisor` / `management` |
| work_location_id | FK nullable | |
| shift_id | FK nullable | |
| supervisor_id | FK employees nullable | untuk group supervisor |
| status | string default 'active' | active / inactive |
| timestamps | | |

### employee_details (1:1 ke employee) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK unique | |
| uuid | uuid unique | |
| nik | string nullable | nomor induk kependudukan |
| phone | string nullable | |
| gender | string nullable | |
| birth_place | string nullable | |
| birth_date | date nullable | |
| religion | string nullable | |
| marital_status | string nullable | |
| address | text nullable | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps | | |

### employee_banks (1:N ke employee) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| uuid | uuid unique | |
| bank_name | string | |
| account_number | string | |
| account_holder | string | |
| is_primary | boolean | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps | | |

### employee_documents (1:N ke employee)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| uuid | uuid unique | |
| name | string | nama dokumen |
| type | string nullable | mis. KTP, KK, ijazah |
| file_path | string | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps | | |

### employee_families (1:N ke employee) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| uuid | uuid unique | |
| name | string | |
| relation | string | suami/istri/anak/orangtua |
| phone | string nullable | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps | | |

### employee_contracts (1:N ke employee) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| uuid | uuid unique | |
| contract_number | string | |
| start_date | date | |
| end_date | date nullable | |
| type | string | pkwt / pkwtl / magang |
| file_path | string nullable | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps | | |

### employee_groups (many-to-many ke employees) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| uuid | uuid unique | |
| name | string | |
| description | text nullable | |
| supervisor_id | FK employees nullable | kepala group |
| external_code | string nullable | |
| is_active | boolean default true | |
| created_by | FK users nullable | |
| updated_by | FK users nullable | |
| synced_at | timestamp nullable | |
| timestamps, softDeletes | | |

### employee_group_members (pivot) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| group_id | FK employee_groups | |
| employee_id | FK employees | |
| timestamps | | |

### invite_codes (kode unik dari HR)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK employees | kode melekat ke karyawan tertentu |
| code | string unique | 8 karakter acak (tanpa I/O/0/1) |
| created_by | FK users | HR yang generate |
| expires_at | datetime | default now + 48 jam |
| used_at | datetime nullable | one-time use |
| used_by | FK users nullable | user yang memakai kode |
| timestamps | | |

### face_templates
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK employees unique | satu template per karyawan |
| template | text | embedding/descriptor wajah |
| mode | enum | client / server (cara enroll) |
| created_at | datetime | |
| updated_at | datetime | |

### work_locations
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| name | string | mis. "Toko Pusat" |
| latitude | decimal(10,7) | |
| longitude | decimal(10,7) | |
| radius_meter | integer | default 100 |
| is_active | boolean | |
| timestamps | | |

### work_patterns (pola kerja) — TAMBAHAN (tidak di PRD awal)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| uuid | uuid unique | |
| name | string | mis. "Senin-Jumat 8 jam" |
| code | string nullable | |
| work_day_hours | json | jam kerja per hari (termasuk istirahat) |
| weekend_days | json | hari weekend (default Sabtu-Minggu) |
| is_active | boolean | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps, softDeletes | | |

### shifts (upgrade dari PRD awal — kolom start_time/end_time lama DI-DROP)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| uuid | uuid unique | |
| work_pattern_id | FK work_patterns nullable | |
| name | string | mis. "Shift Pagi" |
| code | string nullable | |
| external_code | string nullable | |
| work_hour_start | time | jam mulai kerja |
| work_hour_end | time | jam selesai kerja |
| shift_checkin_options | json nullable | |
| check_in_start | time | awal window check-in |
| check_in_end | time | akhir window check-in |
| check_out_start | time | awal window check-out |
| check_out_end | time | akhir window check-out |
| is_overnight | boolean | shift lewat tengah malam |
| check_out_overnight_start | time nullable | |
| check_out_overnight_end | time nullable | |
| tolerance_minutes | integer | toleransi terlambat |
| min_work_hours | integer | minimal jam kerja |
| has_overtime | boolean | |
| is_active | boolean | |
| synced_at | timestamp nullable | |
| timestamps | | |

### working_calendars (template kalender kerja) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| uuid | uuid unique | |
| name | string | mis. "Kalender 2026" |
| year | integer | |
| month | integer | 1-12 |
| total_working_days | integer | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps, softDeletes | | |

### holidays (hari libur) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| uuid | uuid unique | |
| name | string | mis. "Idul Fitri" |
| date | date | |
| is_recurring_yearly | boolean | |
| external_code | string nullable | |
| synced_at | timestamp nullable | |
| timestamps, softDeletes | | |

### schedule_snapshots (jadwal per karyawan per tanggal) — TAMBAHAN
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| uuid | uuid unique | |
| employee_id | FK employees | |
| shift_id | FK shifts nullable | |
| work_pattern_id | FK work_patterns nullable | |
| date | date | unique per (employee_id, date) |
| shift_code | string nullable | |
| work_pattern_type | string nullable | |
| external_code | string nullable | |
| is_holiday | boolean default false | |
| is_sat | boolean default false | |
| is_sun | boolean default false | |
| is_half_day | boolean default false | |
| is_leave | boolean default false | |
| is_permit | boolean default false | |
| leave_id | FK nullable | |
| status | string default 'scheduled' | scheduled / confirmed / cancelled |
| source | string nullable | manual / work_pattern / holiday / hris_pull |
| notes | text nullable | |
| metadata | json nullable | |
| created_by, updated_by | FK users nullable | |
| synced_at | timestamp nullable | |
| timestamps, softDeletes | | |

### attendances
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| work_location_id | FK nullable | |
| type | string | clock_in / clock_out |
| recorded_at | datetime | waktu server |
| latitude | decimal(10,7) nullable | |
| longitude | decimal(10,7) nullable | |
| distance_meter | decimal(10,2) nullable | jarak dari lokasi kantor |
| selfie_photo | longtext nullable | foto selfie (base64 / path) |
| status | string default 'valid' | valid / out_of_radius_approved / flagged |
| notes | text nullable | |
| timestamps | | |
| **face_verified** | **(belum ada — perlu ditambahkan)** | hasil verifikasi wajah |
| **face_mode** | **(belum ada — perlu ditambahkan)** | client / server |

### leave_requests
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| type | enum | izin / cuti / sakit |
| start_date | date | |
| end_date | date | |
| reason | text | |
| attachment | string nullable | |
| status | string | pending / approved / rejected / cancelled |
| approved_by | FK users nullable | HR |
| approved_at | datetime nullable | |
| approval_notes | text nullable | |
| timestamps | | |

### overtime_requests (BELUM DIBUAT — perlu migration baru)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| date | date | |
| start_time | time | |
| end_time | time | |
| reason | text | |
| status | enum | pending / approved / rejected |
| approved_by | FK users nullable | HR |
| approved_at | datetime nullable | |
| approval_notes | text nullable | |
| timestamps | | |

### visits (BELUM DIBUAT — perlu migration baru)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| latitude | decimal | |
| longitude | decimal | |
| photo | string | foto selfie |
| notes | text nullable | keterangan |
| visited_at | datetime | |
| timestamps | | |

### tasks (BELUM DIBUAT — perlu migration baru)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| created_by | FK users | HR/management |
| assignee_id | FK employees | |
| title | string | |
| description | text nullable | |
| due_date | date nullable | |
| status | enum | pending / in_progress / done |
| timestamps | | |

### announcements (BELUM DIBUAT — perlu migration baru)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| created_by | FK users | HR/management |
| title | string | |
| body | text | |
| published_at | datetime nullable | |
| timestamps | | |

### settings (BELUM DIBUAT — per-tenant)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| key | string PK | mis. `face_mode` (client/server), `invite_expiry_hours` |
| value | text | |
| updated_by | FK users nullable | |
| updated_at | datetime | |

### webauthn_keys (TAMBAHAN — tidak di PRD awal)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| user_id | FK users | |
| name | string | nama device (mis. "iPhone Saya") |
| credential_id | string | |
| public_key | text | |
| counter | integer | |
| timestamps | | |

### sessions / cache / jobs (default Laravel)

---

## 11. API Endpoints

```
# Provisioning (dipanggil Central)
POST   /api/v1/provisioning/tenant          ✅

# Auth
POST   /api/v1/auth/register                ✅ email + nama + password
POST   /api/v1/auth/set-pin                 ✅ set PIN 4-6 digit setelah register
POST   /api/v1/auth/login                   ✅ email + password
POST   /api/v1/auth/pin-login               ✅ login cepat pakai PIN
POST   /api/v1/auth/sso                     ✅ login admin via token dari Central
POST   /api/v1/auth/admin-login             ✅ login admin langsung email+password (validasi ke Central)
POST   /api/v1/auth/logout                  ✅
POST   /api/v1/auth/verify-invite           ✅ cek kode unik → nama karyawan
POST   /api/v1/auth/link-employee           ✅ pakai kode unik + user_id → link user↔karyawan

# WebAuthn (TAMBAHAN — tidak di PRD awal)
POST   /api/v1/auth/webauthn/login/options  ✅
POST   /api/v1/auth/webauthn/login          ✅
POST   /api/v1/auth/webauthn/register/options ✅
POST   /api/v1/auth/webauthn/register       ✅
GET    /api/v1/auth/webauthn/keys           ✅
DELETE /api/v1/auth/webauthn/keys/{id}      ✅

# Face Recognition
POST   /api/v1/face/enroll                  ❌ BELUM — upload template wajah (video → embedding)
POST   /api/v1/face/verify                  ❌ BELUM — verifikasi wajah saat check-in
GET    /api/v1/face/settings                ❌ BELUM — mode client/server

# Attendance
POST   /api/v1/attendance/clock-in          ✅ GPS + selfie
POST   /api/v1/attendance/clock-out         ✅
GET    /api/v1/attendance/me                ✅ riwayat sendiri (filter ?date=YYYY-MM-DD)
GET    /api/v1/attendance/roster            ✅ admin: roster per tanggal (?from=&to=&group_id=)
GET    /api/v1/attendance/roster/{employee} ✅ admin: detail harian (?date=)
GET    /api/v1/attendance/export            ❌ BELUM — export excel/csv

# Leave & Overtime
POST   /api/v1/leave-requests               ✅ karyawan: buat pengajuan
GET    /api/v1/leave-requests/me            ✅ karyawan: lihat milik sendiri
POST   /api/v1/leave-requests/{id}/cancel   ✅ karyawan: batalkan yang pending
POST   /api/v1/leave-requests/{id}/approve   ❌ BELUM — HR only
POST   /api/v1/leave-requests/{id}/reject    ❌ BELUM — HR only
POST   /api/v1/overtime-requests            ❌ BELUM
GET    /api/v1/overtime-requests            ❌ BELUM
POST   /api/v1/overtime-requests/{id}/approve ❌ BELUM
POST   /api/v1/overtime-requests/{id}/reject  ❌ BELUM

# Visits
POST   /api/v1/visits                       ❌ BELUM
GET    /api/v1/visits                       ❌ BELUM

# Tasks
GET    /api/v1/tasks                        ❌ BELUM
POST   /api/v1/tasks                        ❌ BELUM
PUT    /api/v1/tasks/{id}                   ❌ BELUM
PUT    /api/v1/tasks/{id}/status            ❌ BELUM

# Announcements
GET    /api/v1/announcements                ❌ BELUM
POST   /api/v1/announcements                ❌ BELUM

# Profile
GET    /api/v1/me                           ❌ BELUM — biodata (dari linked employee + submodules)
GET    /api/v1/me/documents                 ❌ BELUM

# Admin (HR/superadmin)
GET    /api/v1/employees                    ✅ + filter ?status=active|inactive
POST   /api/v1/employees                    ✅
PUT    /api/v1/employees/{id}               ✅
DELETE /api/v1/employees/{id}               ✅ (soft → status inactive)
GET    /api/v1/invite-codes                 ✅
POST   /api/v1/invite-codes                 ✅ generate kode unik (HR)
GET    /api/v1/work-locations               ✅
POST   /api/v1/work-locations               ✅
PUT    /api/v1/work-locations/{id}          ✅
DELETE /api/v1/work-locations/{id}          ✅
GET    /api/v1/groups                       ✅
GET    /api/v1/groups/available-employees   ✅
POST   /api/v1/groups                       ✅
GET    /api/v1/groups/{id}                  ✅
PUT    /api/v1/groups/{id}                  ✅
DELETE /api/v1/groups/{id}                  ✅
GET    /api/v1/groups/mine                  ✅ (supervisor: yang dipimpin; karyawan: tempat bergabung)
GET    /api/v1/working-calendars            ✅
POST   /api/v1/working-calendars            ✅
PUT    /api/v1/working-calendars/{id}       ✅
DELETE /api/v1/working-calendars/{id}       ✅
GET    /api/v1/holidays                     ✅
POST   /api/v1/holidays                     ✅
PUT    /api/v1/holidays/{id}                ✅
DELETE /api/v1/holidays/{id}                ✅
GET    /api/v1/work-patterns                ✅
POST   /api/v1/work-patterns                ✅
PUT    /api/v1/work-patterns/{id}           ✅
DELETE /api/v1/work-patterns/{id}           ✅
GET    /api/v1/shifts                       ✅
POST   /api/v1/shifts                       ✅
PUT    /api/v1/shifts/{id}                  ✅
DELETE /api/v1/shifts/{id}                  ✅
GET    /api/v1/schedule-snapshots           ✅ admin: list jadwal
POST   /api/v1/schedule-snapshots           ✅ admin: bulk upsert
PUT    /api/v1/schedule-snapshots/{id}      ✅
DELETE /api/v1/schedule-snapshots/{id}      ✅
GET    /api/v1/schedule-snapshots/me        ✅ karyawan: jadwal sendiri (?from=&to=&group_id=)
GET    /api/v1/admin/stats                  ✅ dashboard admin: ringkasan angka
```

---

## 12. Service Layer

| Service | Prioritas | Status | Fungsi |
|---------|-----------|--------|--------|
| `TenantProvisioningService` | Must | ✅ | terima webhook Central, buat DB tenant baru + migrasi |
| `SsoService` | Must | ✅ | verifikasi token dari Central, auto-create/update user admin |
| `AdminAuthService` | Must | ✅ | login admin langsung dengan validasi kredensial ke Central |
| `UserAuthService` | Must | ✅ | register, set-pin, login, pin-login, rate limit |
| `InviteCodeService` | Must | ✅ | generate kode unik, validasi one-time + expiry, link user↔karyawan |
| `AttendanceService` | Must | ✅ | validasi radius GPS, simpan clock in/out, riwayat |
| `FaceRecognitionService` | Must | ❌ | enroll & verify wajah; dukungan mode client & server; liveness |
| `LeaveRequestService` | Must | ⚠️ | create, cancel (karyawan). Approve/reject (HR) — belum |
| `OvertimeRequestService` | Should | ❌ | create, approve (HR), reject — belum |
| `VisitService` | Should | ❌ | catat kunjungan + GPS + foto — belum |
| `TaskService` | Should | ❌ | task giving + status update — belum |
| `AnnouncementService` | Should | ❌ | publish & list pengumuman — belum |
| `AttendanceReportService` | Should | ⚠️ | roster & detail (✅), export Excel/CSV (❌) |
| `OfflineSyncService` (frontend, Nuxt4) | Should | ❌ | queue absen saat offline, retry saat online kembali — belum |

Jobs:
- `ProvisionTenantJob` ✅ — dijalankan async setelah webhook diterima, agar response ke Central cepat.
- `CompressSelfiePhotoJob` ❌ — kompres foto selfie/visit setelah upload.
- `CleanupExpiredInviteCodesJob` ❌ — hapus/tandai kode unik yang expired.

---

## 13. Risiko & Dependensi

| Risiko | Dampak | Mitigasi | Status |
|--------|--------|----------|--------|
| GPS device tidak akurat (indoor) | Karyawan gagal absen padahal di kantor | Radius bisa dikonfigurasi lebih longgar per lokasi; opsi "force" untuk tambah riwayat | ✅ Force mode sudah ada |
| Template wajah bocor (privasi) | Data biometrik sensitif terekspos | Template disimpan privat/terenkripsi, akses hanya via API, retensi bisa diatur HR | ⚠️ Face rec belum implementasi |
| Liveness detection gagal di device low-end / kamera jelek | Karyawan gagal verify | Fallback: foto selfie + flag untuk review manual HR (status flagged) | ⚠️ Saat ini selfie selalu dikirim, tanpa verifikasi |
| face-api.js (Node) berat/kurang akurat di device low-end | Verifikasi lambat / gagal | Fallback foto selfie + flag review HR; migrasi ke Python microservice (insightface) bila performa kurang | ⚠️ Belum implementasi |
| Karyawan tanpa email | Tidak bisa registrasi mandiri | HR bisa buatkan akun (email kantor/temp) dari web | — |
| Face mode client vs server tidak konsisten | Perbedaan hasil verify | Mode ditetapkan di pengaturan tenant (satu mode untuk semua device tenant) | ⚠️ Belum ada settings table |
| Nuxt4 masih baru dipelajari tim | Potensi delay development | Sudah berjalan — frontend v0.3 aktif, 9+ halaman, build pass | ✅ Teratasi |
| Foto & video wajah menambah beban data | Pengalaman lambat di sinyal lemah | Kompres sebelum upload; PWA offline queue | ⚠️ Offline queue belum |
| Provisioning gagal (koneksi Central-Absensi terputus) | Tenant baru tidak bisa pakai app | Webhook idempotent + retry job; endpoint status provisioning bisa dicek manual dari admin platform | ✅ |
| iOS Safari getUserMedia butuh user gesture | Kamera tidak bisa diakses dari onMounted | enableCameraFromGesture() dipanggil saat user klik tombol Clock In/Out | ✅ Sudah diatasi |

---

## 14. Timeline

### Sprint 1 — Foundation & Provisioning (Minggu 1-2) ✅ SELESAI
- [x] Setup project Laravel API (Absensi) + Nuxt4 (terpisah)
- [x] `TenantProvisioningService` + endpoint webhook dari Central
- [x] Model dasar: tenant_meta, users, employees
- [x] SSO auto-login admin dari Central (setelah pilih app di dashboard Central)
- [x] Admin login langsung (email+password akun Central)

### Sprint 2 — Karyawan & Absen Dasar (Minggu 3-4) ✅ SELESAI
- [x] CRUD karyawan + generate kode unik (backend: `GET/POST/PUT /api/v1/employees`, `DELETE` = nonaktifkan; kode unik 8 karakter, ditampilkan sekali)
- [x] Login karyawan via PIN + email/password (endpoint `POST /api/v1/auth/pin-login` dan `POST /api/v1/auth/login`)
- [x] Clock in/out + validasi GPS radius (backend: `POST /api/v1/attendance/clock-in|clock-out`, haversine, sesi terbuka, waktu dari server; `GET /api/v1/attendance/me` riwayat sendiri)
- [x] Work locations (backend: `GET/POST/PUT/DELETE /api/v1/work-locations`, multi-outlet, radius configurable, default 100m)

### Sprint 3 — Auth Mobile, Face & PWA (Minggu 5-6) ✅ 80% SELESAI
- [x] Registrasi email+password + set PIN (mobile)
- [x] Kode unik (generate HR + verify/link di app) + halaman pengaturan awal
- [x] Halaman setup face di frontend (`/setup/face`) — UI siap
- [ ] Face enrollment (video/liveness) + verifikasi wajah saat clock in/out — **BACKEND BELUM**
- [x] PWA setup (manifest, service worker, splash screen)
- [ ] Offline queue — **BELUM**

### Sprint 4 — Pengajuan & Approval (Minggu 7-8) ⚠️ 50% SELESAI
- [x] Pengajuan izin/cuti/sakit (dropdown) — sisi karyawan
- [x] Karyawan bisa lihat riwayat & batalkan pengajuan
- [ ] Approval HR (approve/reject) — **BELUM**
- [ ] Pengajuan lembur + approval HR — **BELUM**
- [ ] Notifikasi jadwal (in-app; web push opsional) — **BELUM**
- [ ] Sinkronisasi pengajuan disetujui ke rekap kehadiran — **BELUM**

### Sprint 5 — Dashboard, Laporan & Fitur Pendukung (Minggu 9-10) ⚠️ 40% SELESAI
- [x] Dashboard HR (ringkasan harian) + roster absensi (grid karyawan × tanggal)
- [x] Manajemen shift & jadwal (WorkPattern, Shift, ScheduleSnapshot, WorkingCalendar, Holiday, EmployeeGroup) — **lebih kaya dari PRD**
- [x] WebAuthn login biometrik — **tambahan di luar PRD**
- [x] Employee submodules (detail, bank, dokumen, keluarga, kontrak) — model & migrasi
- [ ] Dashboard management (monitoring) — **BELUM**
- [ ] Rekap bulanan + filter + export Excel/CSV — **BELUM**
- [ ] Kunjungan, tugas, pengumuman, biodata API & UI — **BELUM**
- [ ] QA end-to-end + persiapan rencana app mobile native (fase berikutnya)

---

## 15. Frontend Pages (Nuxt4)

### Mobile (PWA)
| Route | Page | Priority | Status |
|-------|------|----------|--------|
| `/sso` | Handler SSO dari Central (redirect target) | Must | ✅ |
| `/splash` | Splash screen (logo center + loading circle, 1-1.5 detik) | Must | ✅ |
| `/register` | Registrasi (email, nama, password) | Must | ✅ |
| `/set-pin` | Setting PIN 4-6 digit setelah register | Must | ✅ |
| `/setup` | Pengaturan awal: kode unik | Must | ✅ |
| `/setup/face` | Scan wajah (UI siap, integrasi face-api.js belum) | Must | ⚠️ |
| `/login` | Login admin web (email+password) | Must | ✅ |
| `/login-karyawan` | Login karyawan (PIN keypad 6 digit + tab email) | Must | ✅ |
| `/dashboard` | Dashboard utama, menu sesuai role | Must | ✅ |
| `/clock` | Halaman absen (clock in/out, selfie kamera, GPS) | Must | ✅ |
| `/attendance` | Riwayat absensi sendiri + stat | Must | ✅ |
| `/calendar` | Kalender jadwal (per role: karyawan sendiri / supervisor group) | Should | ✅ |
| `/leave-request` | Form pengajuan (dropdown izin/cuti/sakit) + riwayat | Must | ✅ |
| `/profile` | Biodata & pengaturan + logout | Should | ✅ |
| `/schedule` | Jadwal kerja | Should | ❌ (merge ke /calendar) |
| `/leave/history` | Riwayat pengajuan | Should | ✅ (di /leave-request) |
| `/overtime/request` | Pengajuan lembur | Should | ❌ BELUM |
| `/visits` | Kunjungan (selfie + GPS + keterangan) | Should | ❌ BELUM |
| `/announcements` | Daftar pengumuman | Should | ❌ BELUM |
| `/tasks` | Daftar tugas + update status | Should | ❌ BELUM |

### Web (HR/Superadmin)
| Route | Page | Priority | Status |
|-------|------|----------|--------|
| `/admin` | Dashboard admin (stat cards + grid menu + sidebar) | Must | ✅ |
| `/admin/employees` | Kelola karyawan | Must | ✅ |
| `/admin/invite-codes` | Generate & kelola kode unik | Must | ✅ |
| `/admin/locations` | Kelola lokasi kerja | Must | ✅ |
| `/admin/shifts` | Kelola shift | Should | ✅ |
| `/admin/groups` | Kelola group karyawan | Should | ✅ |
| `/admin/calendars` | Kelola kalender kerja | Should | ✅ |
| `/admin/work-patterns` | Kelola pola kerja | Should | ✅ |
| `/admin/schedules` | Kelola jadwal snapshot | Should | ✅ |
| `/admin/attendance` | Rekap kehadiran (roster + detail) | Must | ✅ |
| `/admin/leave-requests` | Approve/reject izin/cuti/sakit | Must | ❌ BELUM |
| `/admin/overtime-requests` | Approve/reject lembur | Should | ❌ BELUM |
| `/admin/visits` | Lihat kunjungan karyawan | Should | ❌ BELUM |
| `/admin/tasks` | Task giving | Should | ❌ BELUM |
| `/admin/announcements` | Buat pengumuman | Should | ❌ BELUM |
| `/admin/settings` | Pengaturan (face mode client/server, expiry kode unik) | Should | ❌ BELUM |

---

## 16. Permission Mapping

| Permission | superadmin/HR (web) | management (mobile) | supervisor (mobile) | karyawan (mobile) |
|------------|-------------|------------|----------|----------|
| login web (SSO/email) | ✅ | ❌ | ❌ | ❌ |
| login PWA (PIN/email/WebAuthn) | ✅ | ✅ | ✅ | ✅ |
| kelola karyawan, kode unik, lokasi, shift | ✅ | ❌ | ❌ | ❌ |
| kelola group, kalender, pola kerja, jadwal | ✅ | ❌ | ❌ | ❌ |
| clock in/out | ❌* | ❌ | ✅ | ✅ |
| riwayat absensi | ✅ (semua) | ✅ (semua, monitoring) | ✅ (tim sendiri) | ✅ (sendiri) |
| jadwal kerja | ✅ | ✅ (lihat) | ✅ | ✅ |
| ajukan izin/cuti/sakit & lembur | ❌ | ❌ | ✅ | ✅ |
| approve/reject pengajuan | ✅ (HR saja) | ❌ | ❌ | ❌ |
| management group | ✅ | ❌ | ✅ (kelola group sendiri) | ❌ |
| monitoring absensi | ✅ | ✅ | ✅ (tim sendiri) | ❌ |
| task giving | ✅ | ✅ | ❌ | ❌ |
| buat pengumuman | ✅ | ✅ | ❌ | ❌ |
| kunjungan (buat) | ❌ | ❌ | ✅ | ✅ |
| lihat kunjungan | ✅ | ✅ | ✅ (tim sendiri) | ❌ |
| biodata & dokumen | ✅ (semua) | ✅ (sendiri) | ✅ (sendiri) | ✅ (sendiri) |
| export laporan | ✅ | ❌ | ❌ | ❌ |

\*HR/superadmin tidak wajib ikut absen; bila perlu ikut absen, buatkan juga record karyawan untuk user tsb (mobile_role karyawan).

---

## 17. Daftar Pekerjaan Tersisa (Gap Analysis)

### 🔴 Critical — Harus diselesaikan sebelum rilis MVP
1. **Face Recognition Service** — `FaceRecognitionService` + controller + API endpoint `POST /face/enroll` dan `POST /face/verify`
2. **Approval HR** — endpoint approve/reject untuk leave requests + halaman web admin
3. **Overtime** — model, migration, controller lengkap (create, list, approve, reject)
4. **Export Excel/CSV** — endpoint `GET /attendance/export`
5. **Tambahkan kolom `face_verified` + `face_mode` ke tabel `attendances`** — migration baru

### 🟡 Should — Perlu sebelum rilis penuh
6. **Kunjungan (Visits)** — model, migration, controller, API, UI mobile
7. **Tugas (Tasks)** — model, migration, controller, API, UI mobile + admin
8. **Pengumuman (Announcements)** — model, migration, controller, API, UI mobile + admin
9. **Management Monitoring** — endpoint khusus management, UI mobile
10. **Biodata API endpoints** — `GET /me`, `GET /me/documents`
11. **Settings table** — untuk face_mode, invite_expiry_hours
12. **Notifikasi in-app** — untuk approval, jadwal, pengumuman

### 🟢 Nice to have
13. **Offline Queue** — indexedDB untuk simpan absen offline, sync saat online
14. **CompressSelfiePhotoJob** — kompres foto setelah upload
15. **CleanupExpiredInviteCodesJob** — hapus kode expired

---

## 18. Pertanyaan yang Belum Terjawab

| # | Pertanyaan | Keputusan |
|--|-----------|-----------|
| 1 | Subdomain Absensi pakai pola apa? | ✅ `{tenant-slug}-absensi.megakomsel.com` (pola global `{slug}-{app}.megakomsel.com`, konsisten dengan Central FR-008) |
| 2 | Foto selfie/template wajah disimpan berapa lama (retensi storage)? | — (perlu keputusan HR; default sementara: ikut umur akun) |
| 3 | Apakah owner/admin juga perlu absen (jadi employee juga) atau murni observer? | ✅ Management/direktur/owner = observer murni (tidak absen). HR/superadmin bisa dibuatkan record karyawan terpisah bila perlu ikut absen |
| 4 | Approval izin: satu level (langsung admin) atau berjenjang (supervisor → admin)? | ✅ Satu level: HR adalah satu-satunya approver |
| 5 | Nama & slug final aplikasi ini untuk didaftarkan ke katalog `apps` Central? | ✅ Nama: Absensi; slug: `absensi`; harga sementara Rp25.000/bln (edit via `/admin/apps`) |
| 6 | Integrasi payroll: apakah rekap kehadiran perlu diekspos lewat API ke aplikasi lain di ekosistem megakomsel.com? | — |
| 7 | Library face recognition client-side & server-side? | ✅ `face-api.js` (TensorFlow.js) untuk kedua mode — satu library jalan di browser (client) & Node/Nitro (server). Jika berat/akurasi kurang → migrasi ke Python microservice (insightface/ArcFace) |
| 8 | Kode unik: panjang karakter & format (8 karakter alfanumerik?) | ✅ 8 karakter acak, huruf besar + angka, tanpa I/O/0/1 |
| 9 | WebAuthn/biometrik: apakah masuk scope MVP? | ✅ Sudah diimplementasikan sebagai login alternatif (tambahan di luar PRD awal) |
| 10 | Employee submodules (detail, bank, family, contract): apakah perlu API? | ✅ Model & migrasi sudah siap mengikuti pola HRIS (uuid + external_code + synced_at). API endpoint masih perlu dibuat |
| 11 | WorkPattern + ScheduleSnapshot: apakah menggantikan shift assignment sederhana? | ✅ Implementasi aktual sudah lebih kaya dari PRD awal. Shift sederhana (start_time/end_time) sudah di-drop dan diganti WorkPattern → Shift → ScheduleSnapshot |

---

## Perubahan dari PRD Sebelumnya

- **v0.4 (2026-08-12):** Sinkronisasi dengan progress aktual.
  - **Status FR** diperbarui: FR-001, 002, 003, 004 = ✅ SELESAI. FR-006 = ✅ SELESAI (lebih kaya dari spesifikasi).
  - **FR-005 (Face Recognition)** = ⚠️ 0% — hanya model & migrasi `face_templates`, belum ada controller/service.
  - **FR-007 (Leave/Overtime)** = ⚠️ 60% — sisi karyawan selesai, approval HR & overtime BELUM.
  - **FR-008 (Dashboard/Rekap)** = ⚠️ 70% — roster & detail & stats selesai, export BELUM.
  - **FR-009 s/d 012** = ❌ 0% — kunjungan, tugas, pengumuman, monitoring management belum dikerjakan.
  - **FR-013** = ⚠️ 50% — model & migrasi employee submodules selesai, API belum.
  - **FR-014** = ⚠️ 60% — PWA terinstall, offline queue belum.
  - **FR-015 (WebAuthn)** = ✅ tambahan baru — login biometrik selesai.
  - **Tabel database** diperbarui sesuai migrasi aktual: `employee_details`, `employee_banks`, `employee_families`, `employee_contracts`, `employee_groups`, `employee_group_members`, `work_patterns`, `working_calendars`, `holidays`, `schedule_snapshots`, `webauthn_keys` ditambahkan. Skema `shifts` diperbarui (kolom lama `start_time`/`end_time` sudah di-DROP). Kolom `face_verified` dan `face_mode` belum ada di `attendances`.
  - **API endpoints** diperbarui: tambah endpoint aktual (admin-login, WebAuthn, roster, groups, calendars, holidays, work-patterns, schedule-snapshots, admin/stats) dan tandai yang belum (face, overtime, visits, tasks, announcements, me, export).
  - **Timeline** diperbarui: Sprint 1-3 = ✅, Sprint 4-5 = ⚠️ parsial.
  - **Pertanyaan baru**: #9 (WebAuthn), #10 (employee submodules), #11 (WorkPattern vs shift sederhana).

- **v0.3 (2026-08-12):** Keputusan diskusi desain mobile app.
  - Model auth berubah: **semua user wajib email+password** (registrasi mandiri) + **set PIN sendiri** untuk login cepat; tanpa Google OAuth di MVP. Karyawan di-link ke akun user via **kode unik dari HR** (one-time, expired, rate limit) — menggantikan model PIN-generated-by-admin di v0.2.
  - **Face recognition masuk scope MVP**: enroll wajah saat pengaturan awal (video/liveness), template di server, verifikasi wajah saat clock in/out, dua mode implementasi (client-side & server-side) bisa dipilih HR di pengaturan.
  - **Approval terpusat di HR** — supervisor/atasan hanya bisa mengajukan (menggantikan supervisor-approve di v0.2).
  - **Management/direktur/owner = observer murni** (monitoring absensi, task giving, pengumuman; tanpa clock in/out) — menjawab pertanyaan #3.
  - Fitur baru: kunjungan (selfie+GPS+keterangan), tugas (task giving), pengumuman, pengajuan lembur, biodata & dokumen, dropdown pengajuan (izin/cuti/sakit).
  - Splash screen 1-1.5 detik (logo + loading circle).
  - Tabel baru: `invite_codes`, `face_templates`, `overtime_requests`, `visits`, `tasks`, `announcements`, `employee_documents`, `settings`.
- **v0.2 (2026-08-11):** Alur SSO mengikuti keputusan Central v0.3 — auto-login setelah pilih aplikasi, subdomain `{slug}-absensi.megakomsel.com`, provisioning dipicu saat subscription dibuat (trialing). Mapping role SSO: owner → owner, member/admin → admin; supervisor dibuat manual di app. Fix inkonsistensi nama tabel mapping tenant → `tenant_meta`.
- **v0.1 (2026-08-11):** Draft awal PRD Absensi, disusun mengikuti arsitektur Central megakomsel.com (1 tenant = 1 DB, provisioning via webhook, SSO admin). Keputusan stack: Laravel API + Nuxt4 (terpisah dari Central yang pakai Blade/Inertia). Login karyawan pakai PIN (bukan SSO) untuk kemudahan akses lapangan. Scope MVP mencakup modul izin/cuti + approval sejak awal.
