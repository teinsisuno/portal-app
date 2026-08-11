# PRD — Absensi (Aplikasi Absensi Karyawan)

## 1. Header Metadata

| Field | Nilai |
|-------|-------|
| Author | Sigit / Paijo |
| Project | Absensi (produk baru di dalam platform megakomsel.com) |
| Fase | MVP Web (PWA) |
| Versi | Draft v0.2 |
| Status | Draft |
| Tanggal | 2026-08-11 |
| Dependencies | Central Platform megakomsel.com (registrasi, onboarding tenancy, billing, tenant provisioning, auto-login SSO) |

---

## 2. Executive Summary

Absensi adalah aplikasi manajemen kehadiran karyawan (mirip Talenta by Mekari) yang menjadi salah satu produk di ekosistem **megakomsel.com** — berdampingan dengan Toyaa, Kasir UMKM, dan Laundry. Tenant mendaftar & berlangganan lewat Central, lalu Central melakukan provisioning database khusus untuk tenant tersebut di aplikasi Absensi (pola 1 tenant = 1 database, konsisten dengan arsitektur Toyaa).

MVP tahap pertama berbentuk **web app berbasis PWA** (installable, bisa dipakai offline-first untuk clock in/out), dibangun dengan **Laravel API** sebagai backend dan **Nuxt4** sebagai frontend terpisah. Owner/admin tenant langsung masuk (auto-login SSO dari Central — satu akun untuk semua app, redirect otomatis setelah pilih aplikasi), sedangkan karyawan (yang hanya perlu absen) login memakai **PIN/kode singkat** tanpa perlu akun email — supaya cepat dipakai di lapangan/warehouse/toko. Pengelolaan pengguna & role (owner/admin/supervisor) sepenuhnya di dalam app Absensi; Central hanya membawa identitas owner via SSO.

Fitur inti MVP: clock in/out dengan validasi GPS radius kantor, verifikasi selfie, manajemen shift dasar, serta pengajuan izin/cuti lengkap dengan alur approval atasan.

---

## 3. Problem Statement

- Tenant di megakomsel.com butuh cara mencatat kehadiran karyawan tanpa mesin fingerprint fisik, terutama untuk bisnis dengan karyawan lapangan/multi-outlet.
- Absensi manual (kertas/Excel) rawan manipulasi jam & lokasi, serta sulit direkap untuk payroll.
- Karyawan non-teknis (warehouse, kasir, kurir) butuh cara login yang sangat sederhana — bikin akun email/password untuk tiap karyawan tidak praktis dan membebani admin.
- Owner/admin butuh visibilitas real-time: siapa yang sudah absen, siapa yang terlambat, siapa yang mengajukan izin — dari satu dashboard.

---

## 4. Goals & Success Metrics

| Goal | Metric | Baseline | Target | Timeline |
|------|--------|----------|--------|----------|
| Absen cepat & anti-curang | Waktu proses clock in | — | < 10 detik, GPS+selfie tervalidasi | Sprint 1-2 |
| Onboarding karyawan mudah | Waktu admin menambah 1 karyawan | — | < 1 menit (generate PIN otomatis) | Sprint 1 |
| Approval izin/cuti cepat | Waktu rata-rata approval | — | < 24 jam | Sprint 3 |
| PWA installable | % karyawan yang install ke homescreen | — | > 60% tenant aktif | Sprint 2 |
| Terintegrasi dengan Central | Provisioning tenant otomatis saat subscription aktif | — | 0 provisioning manual | Sprint 4 |

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
[Absensi App - Nuxt4 PWA] ──► verifikasi token ke Absensi API
     │ session dibuat
     ▼
[Admin Dashboard Absensi] ──► kelola karyawan, shift, lokasi kantor
     │ generate kode PIN per karyawan
     ▼
[Karyawan] ──► buka PWA / install ke homescreen
     │ login pakai PIN + pilih nama (tanpa email)
     ▼
[Clock In] ──► ambil GPS + selfie ──► validasi radius kantor ──► attendance record
     │
     ▼
[Pengajuan Izin/Cuti] ──► approval atasan ──► status disetujui/ditolak
     │
     ▼
[Admin Dashboard] ──► rekap kehadiran, export laporan
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
| Owner/Admin Tenant | Pemilik toko/UMKM (sudah punya akun Central) | Tidak mau setup HR software rumit, tidak mau bikinkan akun email untuk tiap karyawan | Kelola absensi & approve izin cepat dari satu dashboard |
| Karyawan | Staff toko/kurir/warehouse | Tidak semua punya email, malas ribet login | Absen dalam hitungan detik pakai PIN |
| Atasan/Supervisor (opsional) | Kepala toko/shift leader | Perlu approve izin tapi bukan admin penuh | Approve/reject pengajuan izin timnya saja |

---

## 7. Functional Requirements (FR)

### FR-001: Provisioning Tenant (dari Central)
- **Priority:** Must
- **User Story:** Sebagai sistem, saya ingin membuat database tenant baru otomatis saat subscription Absensi aktif di Central, agar tenant langsung bisa pakai app.
- **Acceptance Criteria:**
  - [ ] Dipicu otomatis saat subscription Absensi dibuat di Central (status trialing) — sebelum auto-login
  - [ ] Endpoint webhook `POST /api/v1/provisioning/tenant` menerima payload dari Central (`tenant_slug`, `tenant_name`, `owner_email`, `subscription_id`)
  - [ ] Membuat database baru `tenant_absensi_{slug}` + migrasi otomatis
  - [ ] Mencatat mapping tenant di tabel pusat `tenant_meta` (di database Absensi sendiri, bukan central_db)
  - [ ] Idempotent — webhook terpanggil 2x tidak membuat DB dobel
  - [ ] Response sukses/gagal dikirim balik ke Central untuk update status provisioning

### FR-002: SSO Login Owner/Admin
- **Priority:** Must
- **User Story:** Sebagai owner tenant, saya ingin masuk ke app Absensi langsung dari dashboard Central tanpa login ulang.
- **Acceptance Criteria:**
  - [ ] Auto-login langsung setelah pilih aplikasi di Central (atau klik "Buka App") — tanpa login manual
  - [ ] Central generate signed token (JWT, expired < 60 detik, one-time) berisi `tenant_id`, `user_id`, `email`, `role`
  - [ ] Redirect ke `https://{tenant-slug}-absensi.megakomsel.com/sso?token=xxx`
  - [ ] Absensi API endpoint `POST /api/v1/auth/sso` memverifikasi signature & expiry
  - [ ] Auto-create/update user admin di DB tenant Absensi bila belum ada
  - [ ] Mapping role: Central owner → Absensi owner; Central member/admin → Absensi admin (supervisor dibuat manual di app)
  - [ ] Session/token Absensi (Sanctum) diterbitkan untuk dipakai Nuxt4
  - [ ] Token SSO ditolak jika sudah expired, terpakai, atau signature tidak valid

### FR-003: Manajemen Karyawan & PIN
- **Priority:** Must
- **User Story:** Sebagai admin, saya ingin menambah karyawan dan memberi PIN, agar karyawan bisa langsung absen.
- **Acceptance Criteria:**
  - [ ] Admin bisa tambah/edit/nonaktifkan karyawan: nama, foto, jabatan, lokasi kerja, shift
  - [ ] Sistem generate PIN unik (4-6 digit) per karyawan, bisa di-reset admin
  - [ ] PIN + nama karyawan dipakai untuk login di halaman absen (tanpa email/password)
  - [ ] Satu device bisa dipakai bergantian oleh banyak karyawan (shared device mode)

### FR-004: Clock In / Clock Out dengan GPS
- **Priority:** Must
- **User Story:** Sebagai karyawan, saya ingin absen masuk/pulang dengan validasi lokasi, agar kehadiran saya tercatat akurat.
- **Acceptance Criteria:**
  - [ ] Karyawan login PIN → tombol "Clock In" / "Clock Out" muncul sesuai status
  - [ ] Sistem ambil koordinat GPS browser, validasi terhadap radius lokasi kantor (default radius dikonfigurasi admin, mis. 100m)
  - [ ] Jika di luar radius, absen ditolak dengan pesan jelas (atau butuh approval admin untuk kasus dinas luar)
  - [ ] Setiap tenant bisa punya lebih dari satu lokasi kerja (multi-outlet)
  - [ ] Waktu absen dicatat dari server (bukan device karyawan) untuk cegah manipulasi jam

### FR-005: Verifikasi Selfie
- **Priority:** Must
- **User Story:** Sebagai admin, saya ingin karyawan foto selfie saat absen, agar tidak ada titip absen.
- **Acceptance Criteria:**
  - [ ] Saat clock in/out, kamera device diminta ambil foto selfie
  - [ ] Foto disimpan terhubung ke record absensi (bukan verifikasi wajah otomatis di MVP — cukup foto manual untuk direview admin)
  - [ ] Admin bisa lihat foto selfie di rekap kehadiran per karyawan
  - [ ] Ukuran foto dikompres sebelum upload (hemat storage & kuota data)

### FR-006: Manajemen Shift & Jadwal
- **Priority:** Should
- **User Story:** Sebagai admin, saya ingin atur jadwal shift karyawan, agar jam kerja & keterlambatan bisa dihitung otomatis.
- **Acceptance Criteria:**
  - [ ] Admin buat shift (nama, jam mulai, jam selesai, toleransi terlambat)
  - [ ] Assign shift ke karyawan (per hari atau per periode)
  - [ ] Sistem otomatis tandai status: tepat waktu / terlambat / pulang cepat, berdasarkan shift yang di-assign

### FR-007: Pengajuan Izin/Cuti & Approval
- **Priority:** Must
- **User Story:** Sebagai karyawan, saya ingin mengajukan izin/cuti dari app, agar tidak perlu WA manual ke admin.
- **Acceptance Criteria:**
  - [ ] Karyawan ajukan izin/cuti/sakit: tanggal, jenis, alasan, lampiran (opsional, mis. surat dokter)
  - [ ] Notifikasi masuk ke admin/atasan (in-app, opsional email ke admin)
  - [ ] Admin/atasan approve/reject dengan catatan
  - [ ] Status pengajuan terlihat oleh karyawan (pending/disetujui/ditolak)
  - [ ] Izin yang disetujui otomatis mempengaruhi rekap kehadiran (tidak dihitung alpha)

### FR-008: Dashboard Admin & Rekap Kehadiran
- **Priority:** Must
- **User Story:** Sebagai admin, saya ingin melihat rekap kehadiran seluruh karyawan, agar mudah dipakai untuk payroll.
- **Acceptance Criteria:**
  - [ ] Dashboard: ringkasan hari ini (hadir/telat/izin/alpha), daftar karyawan real-time
  - [ ] Halaman rekap bulanan per karyawan (tabel + filter tanggal)
  - [ ] Export rekap ke Excel/CSV
  - [ ] Detail per record: waktu clock in/out, lokasi (map), foto selfie

### FR-009: PWA & Offline Handling
- **Priority:** Should
- **User Story:** Sebagai karyawan, saya ingin tetap bisa absen walau sinyal lemah, agar tidak gagal tercatat.
- **Acceptance Criteria:**
  - [ ] App installable ke homescreen (manifest + service worker)
  - [ ] Jika koneksi terputus saat submit absen, data disimpan sementara (queue) dan dikirim ulang otomatis saat online
  - [ ] Indikator status koneksi ditampilkan ke user

---

## 8. Non-Functional Requirements

- **Performance:** Proses clock in/out end-to-end < 3 detik (di luar waktu ambil GPS/kamera); halaman absen ringan untuk device low-end.
- **Security:** PIN karyawan di-hash; rate limit percobaan PIN salah; token SSO ditandatangani (JWT) & short-lived; foto selfie disimpan privat (bukan public URL langsung).
- **Reliability:** Waktu absen selalu diambil dari server, bukan client, untuk mencegah manipulasi; idempotent pada webhook provisioning.
- **Scalability:** Arsitektur 1 tenant = 1 database (konsisten dengan Toyaa), backend Laravel API stateless agar mudah di-scale horizontal.
- **Compatibility:** Laravel 13 / PHP 8.4 (selaras dengan stack Central); Nuxt4 + Tailwind untuk frontend; MySQL 8; PWA lewat `@vite-pwa/nuxt`.
- **Usability:** Alur login karyawan (PIN) harus bisa dituntaskan dalam ≤ 3 tap/klik dari halaman awal.

---

## 9. Scope

| ✅ In Scope (MVP Web/PWA) | ❌ Out of Scope (nanti) |
|-------------------------|------------------------|
| Provisioning tenant dari Central (webhook) | Aplikasi mobile native (Android/iOS) |
| SSO login admin dari Central | Face recognition otomatis (baru foto manual) |
| Manajemen karyawan + PIN | Anti fake-GPS/mock location detection |
| Clock in/out + GPS radius | Integrasi payroll otomatis |
| Verifikasi selfie (manual review) | Multi-bahasa |
| Manajemen shift dasar | Absen via fingerprint/RFID fisik |
| Pengajuan izin/cuti + approval | Notifikasi push native |
| Dashboard admin + export rekap | Modul lembur kompleks (perhitungan otomatis ke gaji) |
| PWA installable + offline queue | Live location tracking real-time (hanya snapshot saat absen) |

---

## 10. Tabel Database

Setiap tenant punya database sendiri: `tenant_absensi_{slug}` (dibuat saat provisioning). Skema di bawah berlaku di dalam DB per-tenant tersebut, kecuali disebutkan lain.

### tenant_meta (disimpan di DB Absensi pusat, bukan per-tenant)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| central_tenant_id | bigint | referensi tenant di central_db |
| slug | string unique | dipakai untuk nama DB & subdomain |
| db_name | string | `tenant_absensi_{slug}` |
| status | enum | provisioning / active / suspended |
| provisioned_at | timestamp nullable | |
| timestamps | | |

### users (admin/owner, per-tenant DB)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| central_user_id | bigint | referensi user di Central (dari SSO) |
| name | string | |
| email | string | |
| role | enum | owner / admin / supervisor |
| timestamps | | |

### employees (karyawan, per-tenant DB)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| name | string | |
| photo | string nullable | |
| position | string nullable | jabatan |
| pin_hash | string | PIN di-hash |
| work_location_id | FK nullable | |
| shift_id | FK nullable | |
| supervisor_id | FK employees nullable | untuk approval izin |
| status | enum | active / inactive |
| timestamps | | |

### work_locations
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| name | string | mis. "Toko Pusat" |
| latitude | decimal | |
| longitude | decimal | |
| radius_meter | integer | default 100 |
| timestamps | | |

### shifts
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| name | string | mis. "Shift Pagi" |
| start_time | time | |
| end_time | time | |
| tolerance_minutes | integer | toleransi terlambat |
| timestamps | | |

### attendances
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| work_location_id | FK | |
| type | enum | clock_in / clock_out |
| recorded_at | datetime | waktu server |
| latitude | decimal | |
| longitude | decimal | |
| distance_meter | decimal | jarak dari lokasi kantor |
| selfie_photo | string | path foto |
| status | enum | valid / out_of_radius_approved / flagged |
| notes | text nullable | |
| timestamps | | |

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
| status | enum | pending / approved / rejected |
| approved_by | FK users nullable | |
| approved_at | datetime nullable | |
| approval_notes | text nullable | |
| timestamps | | |

### sessions / cache / jobs (default Laravel)

---

## 11. API Endpoints

```
# Provisioning (dipanggil Central)
POST   /api/v1/provisioning/tenant

# Auth
POST   /api/v1/auth/sso                # login admin via token dari Central
POST   /api/v1/auth/employee-login     # login karyawan pakai PIN
POST   /api/v1/auth/logout

# Employees (admin)
GET    /api/v1/employees
POST   /api/v1/employees
PUT    /api/v1/employees/{id}
POST   /api/v1/employees/{id}/reset-pin
DELETE /api/v1/employees/{id}

# Work Locations & Shifts (admin)
GET    /api/v1/work-locations
POST   /api/v1/work-locations
PUT    /api/v1/work-locations/{id}
GET    /api/v1/shifts
POST   /api/v1/shifts
PUT    /api/v1/shifts/{id}

# Attendance
POST   /api/v1/attendance/clock-in
POST   /api/v1/attendance/clock-out
GET    /api/v1/attendance                 # rekap (admin, filter tanggal/karyawan)
GET    /api/v1/attendance/export          # export excel/csv
GET    /api/v1/attendance/me               # riwayat absen karyawan sendiri

# Leave Requests
POST   /api/v1/leave-requests
GET    /api/v1/leave-requests              # admin: semua; karyawan: milik sendiri
POST   /api/v1/leave-requests/{id}/approve
POST   /api/v1/leave-requests/{id}/reject

# Dashboard
GET    /api/v1/dashboard/summary
```

---

## 12. Service Layer

| Service | Prioritas | Fungsi |
|---------|-----------|--------|
| `TenantProvisioningService` | Must | terima webhook Central, buat DB tenant baru + migrasi |
| `SsoService` | Must | verifikasi token dari Central, auto-create/update user admin |
| `EmployeeAuthService` | Must | verifikasi PIN, rate limit percobaan gagal |
| `AttendanceService` | Must | validasi radius GPS, simpan record, hitung status telat/tepat waktu berdasar shift |
| `LeaveRequestService` | Must | create, approve, reject, sinkron ke rekap kehadiran |
| `AttendanceReportService` | Should | agregasi rekap bulanan, export Excel/CSV |
| `OfflineSyncService` (frontend, Nuxt4) | Should | queue absen saat offline, retry saat online kembali |

Jobs:
- `ProvisionTenantJob` — dijalankan async setelah webhook diterima, agar response ke Central cepat.
- `CompressSelfiePhotoJob` — kompres foto selfie setelah upload.

---

## 13. Risiko & Dependensi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| GPS device tidak akurat (indoor) | Karyawan gagal absen padahal di kantor | Radius bisa dikonfigurasi lebih longgar per lokasi; opsi "minta approval" jika sedikit di luar radius |
| Karyawan tanpa akun email (PIN saja) | Risiko PIN dipakai orang lain | Wajib selfie tiap absen sebagai verifikasi tambahan; rate limit & lock setelah beberapa kali salah PIN |
| Token SSO dicegat (replay attack) | Akun admin bisa diambil alih | Token short-lived (<60 detik) + one-time use, dicatat di cache agar tidak bisa dipakai ulang |
| Nuxt4 masih baru dipelajari tim | Potensi delay development | Mulai dari fitur paling sederhana (auth + clock in/out) dulu sebagai proof of concept |
| Foto selfie & GPS menambah beban data karyawan | Pengalaman lambat di sinyal lemah | Kompres foto sebelum upload; PWA offline queue |
| Provisioning gagal (koneksi Central-Absensi terputus) | Tenant baru tidak bisa pakai app | Webhook idempotent + retry job; endpoint status provisioning bisa dicek manual dari admin platform |

---

## 14. Timeline

### Sprint 1 — Foundation & Provisioning (Minggu 1-2)
- [ ] Setup project Laravel API (Absensi) + Nuxt4 (terpisah)
- [ ] `TenantProvisioningService` + endpoint webhook dari Central
- [ ] Model dasar: tenant_meta, users, employees
- [ ] SSO auto-login admin dari Central (setelah pilih app di dashboard Central)

### Sprint 2 — Karyawan & Absen Dasar (Minggu 3-4)
- [x] CRUD karyawan + generate/reset PIN (backend: `GET/POST/PUT /api/v1/employees`, `POST .../reset-pin`, `DELETE` = nonaktifkan; PIN unik 6 digit, ditampilkan sekali)
- [ ] Login karyawan via PIN (Nuxt4 page — endpoint API `POST /api/v1/auth/employee-login` sudah ada sejak Sprint 1)
- [x] Clock in/out + validasi GPS radius (backend: `POST /api/v1/attendance/clock-in|clock-out`, haversine, sesi terbuka, waktu dari server; `GET /api/v1/attendance/me` riwayat sendiri)
- [x] Work locations (backend: `GET/POST/PUT/DELETE /api/v1/work-locations`, multi-outlet, radius configurable, default 100m)

### Sprint 3 — Selfie, Shift & PWA (Minggu 5-6)
- [ ] Verifikasi selfie saat absen
- [ ] Manajemen shift + status telat/tepat waktu
- [ ] PWA setup (manifest, service worker, offline queue)

### Sprint 4 — Izin/Cuti & Approval (Minggu 7-8)
- [ ] Pengajuan izin/cuti (karyawan)
- [ ] Approval flow (admin/supervisor)
- [ ] Sinkronisasi ke rekap kehadiran

### Sprint 5 — Dashboard & Laporan (Minggu 9-10)
- [ ] Dashboard admin (ringkasan harian)
- [ ] Rekap bulanan + filter
- [ ] Export Excel/CSV
- [ ] QA end-to-end + persiapan rencana app mobile (fase berikutnya)

---

## 15. Frontend Pages (Nuxt4)

| Route | Page | Priority |
|-------|------|----------|
| `/sso` | Handler SSO dari Central (redirect target) | Must |
| `/login` | Login karyawan (pilih nama + PIN) | Must |
| `/clock` | Halaman utama absen (clock in/out, kamera, GPS) | Must |
| `/leave/request` | Form pengajuan izin/cuti (karyawan) | Must |
| `/leave/history` | Riwayat pengajuan izin karyawan | Should |
| `/admin/dashboard` | Ringkasan kehadiran hari ini | Must |
| `/admin/employees` | Kelola karyawan + PIN | Must |
| `/admin/locations` | Kelola lokasi kerja | Must |
| `/admin/shifts` | Kelola shift | Should |
| `/admin/attendance` | Rekap kehadiran + export | Must |
| `/admin/leave-requests` | Approve/reject pengajuan izin | Must |

---

## 16. Permission Mapping

| Permission | owner/admin | supervisor | karyawan |
|------------|-------------|------------|----------|
| login SSO (Central) | ✅ | ✅ | ❌ |
| login PIN | ❌ | ❌ | ✅ |
| clock in/out | ❌* | ❌* | ✅ |
| kelola karyawan & PIN | ✅ | ❌ | ❌ |
| kelola lokasi & shift | ✅ | ❌ | ❌ |
| ajukan izin/cuti | ❌ | ❌ | ✅ |
| approve/reject izin | ✅ | ✅ (tim sendiri) | ❌ |
| lihat dashboard & rekap | ✅ | ✅ (tim sendiri) | ❌ (hanya riwayat sendiri) |
| export laporan | ✅ | ❌ | ❌ |

*owner/admin bukan role untuk absen harian — bisa ditambahkan sebagai employee terpisah jika perlu ikut absen.

---

## Pertanyaan yang Belum Terjawab

| # | Pertanyaan | Keputusan |
|---|-----------|-----------|
| 1 | Subdomain Absensi pakai pola apa? | ✅ `{tenant-slug}-absensi.megakomsel.com` (pola global `{slug}-{app}.megakomsel.com`, konsisten dengan Central FR-008) |
| 2 | Foto selfie disimpan berapa lama (retensi storage)? | — |
| 3 | Apakah owner/admin juga perlu absen (jadi employee juga) atau murni observer? | — |
| 4 | Approval izin: satu level (langsung admin) atau berjenjang (supervisor → admin)? | — |
| 5 | Nama & slug final aplikasi ini untuk didaftarkan ke katalog `apps` Central? | ✅ Nama: Absensi; slug: `absensi`; harga sementara Rp25.000/bln (edit via `/admin/apps`) |
| 6 | Integrasi payroll: apakah rekap kehadiran perlu diekspos lewat API ke aplikasi lain di ekosistem megakomsel.com? | — |

---

## Perubahan dari PRD Sebelumnya

- **v0.2 (2026-08-11):** Alur SSO mengikuti keputusan Central v0.3 — auto-login setelah pilih aplikasi, subdomain `{slug}-absensi.megakomsel.com`, provisioning dipicu saat subscription dibuat (trialing). Mapping role SSO: owner → owner, member/admin → admin; supervisor dibuat manual di app. Fix inkonsistensi nama tabel mapping tenant → `tenant_meta`.
- **v0.1 (2026-08-11):** Draft awal PRD Absensi, disusun mengikuti arsitektur Central megakomsel.com (1 tenant = 1 DB, provisioning via webhook, SSO admin). Keputusan stack: Laravel API + Nuxt4 (terpisah dari Central yang pakai Blade/Inertia). Login karyawan pakai PIN (bukan SSO) untuk kemudahan akses lapangan. Scope MVP mencakup modul izin/cuti + approval sejak awal.
