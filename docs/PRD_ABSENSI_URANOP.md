# PRD — Absensi (Aplikasi Absensi Karyawan)

## 1. Header Metadata

| Field | Nilai |
|-------|-------|
| Author | Sigit / Paijo |
| Project | Absensi (produk baru di dalam platform megakomsel.com) |
| Fase | MVP Mobile App (PWA) |
| Versi | v0.3 |
| Status | Draft |
| Tanggal | 2026-08-12 |
| Dependencies | Central Platform megakomsel.com (registrasi, onboarding tenancy, billing, tenant provisioning, auto-login SSO) |

---

## 2. Executive Summary

Absensi adalah aplikasi manajemen kehadiran karyawan (mirip Talenta by Mekari) yang menjadi salah satu produk di ekosistem **megakomsel.com** — berdampingan dengan Toyaa, Kasir UMKM, dan Laundry. Tenant mendaftar & berlangganan lewat Central, lalu Central melakukan provisioning database khusus untuk tenant tersebut di aplikasi Absensi (pola 1 tenant = 1 database, konsisten dengan arsitektur Toyaa).

MVP berbentuk **mobile app berbasis PWA** (installable, offline-first untuk clock in/out), dibangun dengan **Laravel API** sebagai backend dan **Nuxt4** sebagai frontend terpisah. **Semua user (termasuk karyawan) wajib punya akun email + password** — registrasi mandiri dari app, lalu **set PIN sendiri** sebagai cara login cepat untuk pemakaian harian (lapangan/warehouse/toko). Owner/admin tenant masuk via auto-login SSO dari Central (satu akun untuk semua app). **HR** mengelola data karyawan & membagikan **kode unik** untuk menautkan akun user ke data karyawan (karena tidak semua user adalah karyawan). Verifikasi kehadiran memakai **GPS radius + face recognition** (dengan liveness detection), dan **semua approval pengajuan terpusat di HR**.

Fitur inti MVP: clock in/out dengan validasi GPS radius kantor + verifikasi wajah, manajemen shift dasar, pengajuan izin/cuti/sakit & lembur dengan alur approval HR, kunjungan lapangan (foto selfie + koordinat), tugas, pengumuman, serta monitoring untuk level management.

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
| Onboarding karyawan mudah | Waktu HR menambah 1 karyawan | — | < 1 menit (generate kode unik otomatis) | Sprint 1 |
| Registrasi mandiri cepat | Waktu user selesai register → siap absen | — | < 3 menit (register → PIN → kode unik → scan wajah) | Sprint 3 |
| Approval izin/cuti/lembur cepat | Waktu rata-rata approval HR | — | < 24 jam | Sprint 4 |
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

[HR (web)] ──► kelola karyawan, lokasi, shift
     │ generate kode unik per karyawan (one-time, expired) ──► bagikan ke karyawan
     ▼
[Karyawan] ──► buka PWA ──► login PIN (atau email+password)
     ▼
[Clock In/Out] ──► GPS + verifikasi wajah (liveness) ──► validasi radius ──► attendance record
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
| Superadmin / HR | Pengelola tenant via web (bisa dari Central SSO atau akun web Absensi) | Ingin kontrol penuh data karyawan & approval terpusat | Kelola karyawan, generate kode unik, approve semua pengajuan, lihat rekap |
| Karyawan | Staff toko/kurir/warehouse (punya email, malas ribet login tiap hari) | Registrasi sekali, lalu absen cepat | Absen dalam hitungan detik pakai PIN + wajah |
| Supervisor/Mandor | Kepala toko/shift leader | Perlu lihat tim & mengajukan, tanpa kuasa approve | Kelola group, ajukan izin/lembur, lihat jadwal tim |
| Management/Direktur/Owner | Pemilik/pimpinan (tidak ikut absen) | Butuh visibilitas tanpa ikut alur kehadiran | Monitoring absensi, memberi tugas, membuat pengumuman |

---

## 7. Functional Requirements (FR)

### FR-001: Provisioning Tenant (dari Central)
- **Priority:** Must
- **User Story:** Sebagai sistem, saya ingin membuat database tenant baru otomatis saat subscription Absensi aktif di Central, agar tenant langsung bisa pakai app.
- **Acceptance Criteria:**
  - [x] Dipicu otomatis saat subscription Absensi dibuat di Central (status trialing) — sebelum auto-login
  - [x] Endpoint webhook `POST /api/v1/provisioning/tenant` menerima payload dari Central (`tenant_slug`, `tenant_name`, `owner_email`, `subscription_id`)
  - [x] Membuat database baru `tenant_absensi_{slug}` + migrasi otomatis
  - [x] Mencatat mapping tenant di tabel pusat `tenant_meta` (di database Absensi sendiri, bukan central_db)
  - [x] Idempotent — webhook terpanggil 2x tidak membuat DB dobel
  - [ ] Response sukses/gagal dikirim balik ke Central untuk update status provisioning (central baru log, belum ada kolom status provisioning)

### FR-002: SSO Login Owner/Admin
- **Priority:** Must
- **User Story:** Sebagai owner tenant, saya ingin masuk ke app Absensi langsung dari dashboard Central tanpa login ulang.
- **Acceptance Criteria:**
  - [x] Auto-login langsung setelah pilih aplikasi di Central (atau klik "Buka App") — tanpa login manual
  - [x] Central generate signed token (JWT, expired < 60 detik, one-time) berisi `tenant_id`, `user_id`, `email`, `role`
  - [x] Redirect ke `https://{tenant-slug}-absensi.megakomsel.com/sso?token=xxx`
  - [x] Absensi API endpoint `POST /api/v1/auth/sso` memverifikasi signature & expiry
  - [x] Auto-create/update user admin di DB tenant Absensi bila belum ada
  - [x] Mapping role: Central owner → Absensi owner/superadmin; Central member/admin → Absensi HR
  - [x] Session/token Absensi (Sanctum) diterbitkan untuk dipakai Nuxt4
  - [x] Token SSO ditolak jika sudah expired, terpakai, atau signature tidak valid

### FR-003: Registrasi, PIN & Link Karyawan (Kode Unik)
- **Priority:** Must
- **User Story:** Sebagai user baru, saya ingin daftar dengan email, set PIN, dan menautkan akun saya ke data karyawan lewat kode unik dari HR.
- **Acceptance Criteria:**
  - [ ] Registrasi mandiri: email + nama + password (tanpa Google OAuth di MVP)
  - [ ] Setelah register, user diminta **set PIN 4-6 digit** (dipakai untuk login cepat berikutnya; bisa di-reset via password)
  - [ ] Login alternatif: email+password ATAU PIN
  - [ ] HR generate **kode unik** per karyawan dari web (one-time use, expired default 48 jam, bisa di-regenerate); kode ditampilkan sekali & dibagikan manual ke karyawan
  - [ ] Saat kode unik dimasukkan di halaman pengaturan awal, **nama karyawan muncul otomatis** di bawah field (validasi bahwa kode benar)
  - [ ] Kode unik hanya bisa dipakai sekali (setelah link, `used_at` terisi); percobaan salah di-rate limit
  - [ ] Setelah link sukses + scan wajah selesai, tombol Simpan aktif → masuk dashboard
  - [ ] Tidak semua user adalah karyawan: user tanpa kode unik tetap bisa login (mis. manajemen) tapi tidak punya data kehadiran

### FR-004: Clock In / Clock Out dengan GPS + Wajah
- **Priority:** Must
- **User Story:** Sebagai karyawan, saya ingin absen masuk/pulang dengan validasi lokasi & wajah, agar kehadiran saya tercatat akurat.
- **Acceptance Criteria:**
  - [ ] Karyawan login PIN → tombol "Clock In" / "Clock Out" muncul sesuai status
  - [ ] Sistem ambil koordinat GPS browser, validasi terhadap radius lokasi kantor (default radius dikonfigurasi HR, mis. 100m)
  - [ ] Jika di luar radius, absen ditolak dengan pesan jelas (atau butuh approval HR untuk kasus dinas luar)
  - [ ] Setiap tenant bisa punya lebih dari satu lokasi kerja (multi-outlet)
  - [ ] Waktu absen dicatat dari server (bukan device karyawan) untuk cegah manipulasi jam

### FR-005: Verifikasi Wajah (Face Recognition)
- **Priority:** Must (masuk scope MVP — perubahan dari v0.2)
- **User Story:** Sebagai karyawan, saya ingin wajah saya diverifikasi otomatis saat absen, agar tidak ada titip absen.
- **Acceptance Criteria:**
  - [ ] Saat pengaturan awal, karyawan **scan wajah** (video singkat untuk liveness detection) → template wajah disimpan **di server** (per-tenant DB)
  - [ ] Saat clock in/out, kamera device ambil wajah → dicocokkan dengan template → wajib cocok + GPS valid baru absen tercatat
  - [ ] Liveness detection (anti foto print / foto HP): pakai video capture, bukan foto diam
  - [ ] **Dua mode implementasi: client-side dan server-side** — satu library `face-api.js` (TensorFlow.js) untuk keduanya, bisa dipilih HR di pengaturan (default: server-side)
    - Server-side: template & matching di server (Node/Nitro, face-api.js), dipanggil internal API
    - Client-side: matching di device (face-api.js di browser/PWA), template tetap dikirim & disimpan di server untuk konsistensi lintas device
    - Jika performa/akurasi Node kurang memadai → migrasi ke Python microservice (insightface/ArcFace)
  - [ ] Ganti device → karyawan cukup login, template tetap valid (tanpa enroll ulang)
  - [ ] Template wajah disimpan privat (bukan public URL), akses terbatas

### FR-006: Manajemen Shift & Jadwal
- **Priority:** Should
- **User Story:** Sebagai HR, saya ingin atur jadwal shift karyawan, agar jam kerja & keterlambatan bisa dihitung otomatis.
- **Acceptance Criteria:**
  - [ ] HR buat shift (nama, jam mulai, jam selesai, toleransi terlambat)
  - [ ] Assign shift ke karyawan (per hari atau per periode)
  - [ ] Sistem otomatis tandai status: tepat waktu / terlambat / pulang cepat, berdasarkan shift yang di-assign
  - [ ] Karyawan bisa lihat jadwal kerjanya (mobile)

### FR-007: Pengajuan Izin/Cuti/Sakit & Lembur (Approval HR)
- **Priority:** Must
- **User Story:** Sebagai karyawan, saya ingin mengajukan izin/cuti/sakit atau lembur dari app, agar tidak perlu WA manual ke HR.
- **Acceptance Criteria:**
  - [ ] Karyawan ajukan pengajuan lewat **menu dropdown**: izin / cuti / sakit (tanggal, jenis, alasan, lampiran opsional mis. surat dokter)
  - [ ] Karyawan ajukan lembur (tanggal, jam mulai, jam selesai, alasan)
  - [ ] Notifikasi masuk ke HR (in-app; opsional email)
  - [ ] **HR adalah satu-satunya approver** — supervisor/atasan hanya bisa mengajukan, tidak bisa approve
  - [ ] HR approve/reject dengan catatan; status pengajuan terlihat oleh karyawan (pending/disetujui/ditolak)
  - [ ] Izin/cuti yang disetujui otomatis mempengaruhi rekap kehadiran (tidak dihitung alpha)

### FR-008: Dashboard & Rekap Kehadiran
- **Priority:** Must
- **User Story:** Sebagai HR, saya ingin melihat rekap kehadiran seluruh karyawan, agar mudah dipakai untuk payroll.
- **Acceptance Criteria:**
  - [ ] Dashboard: ringkasan hari ini (hadir/telat/izin/alpha), daftar karyawan real-time
  - [ ] Halaman rekap bulanan per karyawan (tabel + filter tanggal)
  - [ ] Export rekap ke Excel/CSV
  - [ ] Detail per record: waktu clock in/out, lokasi (map), verifikasi wajah (berhasil/gagal/flag)

### FR-009: Monitoring Management (Direktur/Owner)
- **Priority:** Should
- **User Story:** Sebagai management, saya ingin melihat monitoring absensi tanpa ikut absen.
- **Acceptance Criteria:**
  - [ ] Role management/direktur/owner (dari jabatan karyawan) mendapat menu: monitoring absensi, task giving, pengumuman
  - [ ] Management TIDAK ikut alur clock in/out (observer murni)
  - [ ] Monitoring: ringkasan kehadiran tim, tanpa akses kelola karyawan/approval

### FR-010: Kunjungan (Field Visit)
- **Priority:** Should
- **User Story:** Sebagai karyawan, saya ingin mencatat kunjungan dengan foto selfie + koordinat + keterangan.
- **Acceptance Criteria:**
  - [ ] Karyawan buat kunjungan: foto selfie, koordinat GPS otomatis, keterangan, waktu
  - [ ] HR bisa lihat daftar kunjungan karyawan (web)

### FR-011: Tugas (Task Giving)
- **Priority:** Should
- **User Story:** Sebagai HR/management, saya ingin memberi tugas ke karyawan dan memantau statusnya.
- **Acceptance Criteria:**
  - [ ] HR/management buat tugas (judul, deskripsi, assignee, due date)
  - [ ] Karyawan lihat daftar tugas & update status (pending/in_progress/done)

### FR-012: Pengumuman
- **Priority:** Should
- **User Story:** Sebagai HR/management, saya ingin membuat pengumuman yang terlihat semua karyawan.
- **Acceptance Criteria:**
  - [ ] HR/management buat pengumuman (judul, isi, publish)
  - [ ] Karyawan lihat daftar pengumuman di mobile

### FR-013: Biodata & Dokumen
- **Priority:** Should
- **User Story:** Sebagai karyawan, saya ingin melihat biodata & dokumen saya.
- **Acceptance Criteria:**
  - [ ] Karyawan lihat biodata (nama, jabatan, lokasi, shift) & dokumen yang diunggah HR
  - [ ] Upload/edit dokumen oleh HR dari web

### FR-014: PWA & Offline Handling
- **Priority:** Should
- **User Story:** Sebagai karyawan, saya ingin tetap bisa absen walau sinyal lemah, agar tidak gagal tercatat.
- **Acceptance Criteria:**
  - [ ] App installable ke homescreen (manifest + service worker)
  - [ ] Jika koneksi terputus saat submit absen, data disimpan sementara (queue) dan dikirim ulang otomatis saat online
  - [ ] Indikator status koneksi ditampilkan ke user
  - [ ] Splash screen 1-1.5 detik (logo center + loading circle, lalu scale-in hilang)

---

## 8. Non-Functional Requirements

- **Performance:** Proses clock in/out end-to-end < 3 detik (di luar waktu ambil GPS/kamera); halaman absen ringan untuk device low-end.
- **Security:** PIN di-hash; rate limit percobaan PIN & kode unik salah; token SSO ditandatangani (JWT) & short-lived; **template wajah disimpan privat & terenkripsi (bukan public URL)**; kode unik one-time + expiry.
- **Reliability:** Waktu absen selalu diambil dari server, bukan client; idempotent pada webhook provisioning.
- **Privacy:** Template wajah hanya dipakai untuk verifikasi absensi; retensi & penghapusan data wajah diatur HR (pertanyaan retensi masih terbuka).
- **Scalability:** Arsitektur 1 tenant = 1 database (konsisten dengan Toyaa), backend Laravel API stateless agar mudah di-scale horizontal.
- **Compatibility:** Laravel 13 / PHP 8.4 (selaras dengan stack Central); Nuxt4 + Tailwind untuk frontend; MySQL 8; PWA lewat `@vite-pwa/nuxt`.
- **Usability:** Login harian karyawan (PIN) harus bisa dituntaskan dalam ≤ 3 tap/klik dari halaman awal.

---

## 9. Scope

| ✅ In Scope (MVP Mobile App/PWA) | ❌ Out of Scope (nanti) |
|-------------------------|------------------------|
| Provisioning tenant dari Central (webhook) | Aplikasi mobile native (Android/iOS) |
| SSO login admin dari Central | Google/Apple OAuth login |
| Registrasi email+password + set PIN | Anti fake-GPS/mock location detection |
| Kode unik link user↔karyawan (dari HR) | Integrasi payroll otomatis |
| Clock in/out + GPS radius | Multi-bahasa |
| **Face recognition (enroll + verify saat absen, liveness, mode client/server)** | Absen via fingerprint/RFID fisik |
| Manajemen shift dasar | Notifikasi push native |
| Pengajuan izin/cuti/sakit + lembur, approval HR | Live location tracking real-time (hanya snapshot saat absen) |
| Dashboard HR + export rekap | Perhitungan lembur otomatis ke gaji (baru pengajuan) |
| Kunjungan (selfie + GPS + keterangan) | |
| Tugas (task giving + status) | |
| Pengumuman | |
| Biodata & dokumen karyawan | |
| PWA installable + offline queue | |

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

### users (semua yang bisa login — karyawan & non-karyawan)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| central_user_id | bigint nullable | referensi user di Central (dari SSO) |
| name | string | |
| email | string unique | wajib (registrasi mandiri) |
| password_hash | string | |
| pin_hash | string nullable | PIN 4-6 digit, di-set user sendiri |
| role | enum | superadmin / hr / employee (employee = ter-link ke data karyawan) |
| timestamps | | |

### employees (karyawan, per-tenant DB)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| user_id | bigint FK users nullable | terisi saat kode unik dipakai (link akun) |
| name | string | |
| photo | string nullable | |
| position | string nullable | jabatan (display) |
| mobile_role | enum nullable | karyawan / supervisor / management — dari mapping jabatan |
| work_location_id | FK nullable | |
| shift_id | FK nullable | |
| supervisor_id | FK employees nullable | untuk group supervisor |
| status | enum | active / inactive |
| timestamps | | |

### invite_codes (kode unik dari HR)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK employees | kode melekat ke karyawan tertentu |
| code | string unique | 8 karakter acak |
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
| selfie_photo | string | path foto (fallback/arsip) |
| face_verified | boolean | hasil verifikasi wajah |
| face_mode | enum nullable | client / server |
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
| approved_by | FK users nullable | HR |
| approved_at | datetime nullable | |
| approval_notes | text nullable | |
| timestamps | | |

### overtime_requests
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

### visits (kunjungan)
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

### tasks
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

### announcements
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| created_by | FK users | HR/management |
| title | string | |
| body | text | |
| published_at | datetime nullable | |
| timestamps | | |

### employee_documents
| Kolom | Tipe | Catatan |
|-------|------|---------|
| id | bigint PK | |
| employee_id | FK | |
| name | string | nama dokumen |
| type | string nullable | mis. KTP, KK, ijazah |
| file_path | string | |
| timestamps | | |

### settings (per-tenant)
| Kolom | Tipe | Catatan |
|-------|------|---------|
| key | string PK | mis. `face_mode` (client/server), `invite_expiry_hours` |
| value | text | |
| updated_by | FK users nullable | |
| updated_at | datetime | |

### sessions / cache / jobs (default Laravel)

---

## 11. API Endpoints

```
# Provisioning (dipanggil Central)
POST   /api/v1/provisioning/tenant

# Auth
POST   /api/v1/auth/register              # email + nama + password
POST   /api/v1/auth/set-pin               # set PIN 4-6 digit setelah register
POST   /api/v1/auth/login                 # email + password
POST   /api/v1/auth/pin-login             # login cepat pakai PIN
POST   /api/v1/auth/sso                   # login admin via token dari Central
POST   /api/v1/auth/logout
POST   /api/v1/auth/verify-invite         # cek kode unik → nama karyawan
POST   /api/v1/auth/link-employee         # pakai kode unik + user_id → link user↔karyawan

# Face Recognition
POST   /api/v1/face/enroll                # upload template wajah (video → embedding)
POST   /api/v1/face/verify                # verifikasi wajah saat check-in
GET    /api/v1/face/settings              # mode client/server

# Attendance
POST   /api/v1/attendance/clock-in        # GPS + face verify
POST   /api/v1/attendance/clock-out
GET    /api/v1/attendance                 # rekap (HR, filter tanggal/karyawan)
GET    /api/v1/attendance/export          # export excel/csv
GET    /api/v1/attendance/me              # riwayat absen karyawan sendiri

# Leave & Overtime
POST   /api/v1/leave-requests
GET    /api/v1/leave-requests             # HR: semua; karyawan: milik sendiri
POST   /api/v1/leave-requests/{id}/approve   # HR only
POST   /api/v1/leave-requests/{id}/reject    # HR only
POST   /api/v1/overtime-requests
GET    /api/v1/overtime-requests
POST   /api/v1/overtime-requests/{id}/approve # HR only
POST   /api/v1/overtime-requests/{id}/reject  # HR only

# Visits
POST   /api/v1/visits
GET    /api/v1/visits                     # HR: semua kunjungan

# Tasks
GET    /api/v1/tasks                      # karyawan: tugas saya
POST   /api/v1/tasks                      # HR/management
PUT    /api/v1/tasks/{id}                 # HR/management (edit)
PUT    /api/v1/tasks/{id}/status          # karyawan (update status)

# Announcements
GET    /api/v1/announcements
POST   /api/v1/announcements              # HR/management

# Profile
GET    /api/v1/me                         # biodata (dari linked employee)
GET    /api/v1/me/documents

# Admin (HR/superadmin)
GET    /api/v1/employees
POST   /api/v1/employees
PUT    /api/v1/employees/{id}
POST   /api/v1/employees/{id}/reset-pin   # reset PIN user (via password)
DELETE /api/v1/employees/{id}
GET    /api/v1/invite-codes               # list kode unik
POST   /api/v1/invite-codes               # generate kode unik (HR)
GET    /api/v1/work-locations
POST   /api/v1/work-locations
PUT    /api/v1/work-locations/{id}
GET    /api/v1/shifts
POST   /api/v1/shifts
PUT    /api/v1/shifts/{id}
PUT    /api/v1/settings/face-mode         # pilih client/server
GET    /api/v1/dashboard/summary
```

---

## 12. Service Layer

| Service | Prioritas | Fungsi |
|---------|-----------|--------|
| `TenantProvisioningService` | Must | terima webhook Central, buat DB tenant baru + migrasi |
| `SsoService` | Must | verifikasi token dari Central, auto-create/update user admin |
| `AuthService` | Must | register, set-pin, login, pin-login, rate limit |
| `InviteCodeService` | Must | generate kode unik, validasi one-time + expiry, link user↔karyawan |
| `FaceRecognitionService` | Must | enroll & verify wajah; dukungan mode client & server; liveness |
| `AttendanceService` | Must | validasi radius GPS + verifikasi wajah, simpan record, hitung status |
| `LeaveRequestService` | Must | create, approve (HR), reject, sinkron ke rekap kehadiran |
| `OvertimeRequestService` | Should | create, approve (HR), reject |
| `VisitService` | Should | catat kunjungan + GPS + foto |
| `TaskService` | Should | task giving + status update |
| `AnnouncementService` | Should | publish & list pengumuman |
| `AttendanceReportService` | Should | agregasi rekap bulanan, export Excel/CSV |
| `OfflineSyncService` (frontend, Nuxt4) | Should | queue absen saat offline, retry saat online kembali |

Jobs:
- `ProvisionTenantJob` — dijalankan async setelah webhook diterima, agar response ke Central cepat.
- `CompressSelfiePhotoJob` — kompres foto selfie/visit setelah upload.
- `CleanupExpiredInviteCodesJob` — hapus/tandai kode unik yang expired.

---

## 13. Risiko & Dependensi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| GPS device tidak akurat (indoor) | Karyawan gagal absen padahal di kantor | Radius bisa dikonfigurasi lebih longgar per lokasi; opsi "minta approval" jika sedikit di luar radius |
| Template wajah bocor (privasi) | Data biometrik sensitif terekspos | Template disimpan privat/terenkripsi, akses hanya via API, retensi bisa diatur HR |
| Liveness detection gagal di device low-end / kamera jelek | Karyawan gagal verify | Fallback: foto selfie + flag untuk review manual HR (status flagged) |
| face-api.js (Node) berat/kurang akurat di device low-end | Verifikasi lambat / gagal | Fallback foto selfie + flag review HR; migrasi ke Python microservice (insightface) bila performa kurang |
| Karyawan tanpa email | Tidak bisa registrasi mandiri | HR bisa buatkan akun (email kantor/temp) dari web |
| Face mode client vs server tidak konsisten | Perbedaan hasil verify | Mode ditetapkan di pengaturan tenant (satu mode untuk semua device tenant) |
| Nuxt4 masih baru dipelajari tim | Potensi delay development | Mulai dari fitur paling sederhana (auth + clock in/out) dulu sebagai proof of concept |
| Foto & video wajah menambah beban data | Pengalaman lambat di sinyal lemah | Kompres sebelum upload; PWA offline queue |
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

### Sprint 3 — Auth Mobile, Face & PWA (Minggu 5-6)
- [ ] Registrasi email+password + set PIN (mobile)
- [ ] Kode unik (generate HR + verify/link di app) + halaman pengaturan awal
- [ ] Face enrollment (video/liveness) + verifikasi wajah saat clock in/out — mode client & server
- [ ] PWA setup (manifest, service worker, offline queue, splash screen)

### Sprint 4 — Pengajuan & Approval (Minggu 7-8)
- [ ] Pengajuan izin/cuti/sakit (dropdown) + approval HR
- [ ] Pengajuan lembur + approval HR
- [ ] Notifikasi jadwal (in-app; web push opsional)
- [ ] Sinkronisasi pengajuan disetujui ke rekap kehadiran

### Sprint 5 — Dashboard, Laporan & Fitur Pendukung (Minggu 9-10)
- [ ] Dashboard HR (ringkasan harian) + dashboard management (monitoring)
- [ ] Rekap bulanan + filter + export Excel/CSV
- [ ] Kunjungan, tugas, pengumuman, biodata & dokumen
- [ ] QA end-to-end + persiapan rencana app mobile native (fase berikutnya)

---

## 15. Frontend Pages (Nuxt4)

### Mobile (PWA)
| Route | Page | Priority |
|-------|------|----------|
| `/sso` | Handler SSO dari Central (redirect target) | Must |
| `/splash` | Splash screen (logo center + loading circle, 1-1.5 detik) | Must |
| `/register` | Registrasi (email, nama, password) | Must |
| `/set-pin` | Setting PIN 4-6 digit setelah register | Must |
| `/setup` | Pengaturan awal: kode unik + scan wajah (pengaturan awal 1) | Must |
| `/login` | Login (email+password atau PIN) | Must |
| `/dashboard` | Dashboard utama, menu sesuai role | Must |
| `/clock` | Halaman absen (clock in/out, kamera face, GPS) | Must |
| `/attendance` | Riwayat absensi sendiri | Must |
| `/schedule` | Jadwal kerja | Should |
| `/leave/request` | Form pengajuan (dropdown izin/cuti/sakit) | Must |
| `/leave/history` | Riwayat pengajuan | Should |
| `/overtime/request` | Pengajuan lembur | Should |
| `/visits` | Kunjungan (selfie + GPS + keterangan) | Should |
| `/announcements` | Daftar pengumuman | Should |
| `/tasks` | Daftar tugas + update status | Should |
| `/profile` | Biodata & dokumen | Should |

### Web (HR/Superadmin)
| Route | Page | Priority |
|-------|------|----------|
| `/admin/dashboard` | Ringkasan kehadiran hari ini | Must |
| `/admin/employees` | Kelola karyawan + kode unik + dokumen | Must |
| `/admin/invite-codes` | Generate & kelola kode unik | Must |
| `/admin/locations` | Kelola lokasi kerja | Must |
| `/admin/shifts` | Kelola shift | Should |
| `/admin/attendance` | Rekap kehadiran + export + review face flag | Must |
| `/admin/leave-requests` | Approve/reject izin/cuti/sakit | Must |
| `/admin/overtime-requests` | Approve/reject lembur | Should |
| `/admin/visits` | Lihat kunjungan karyawan | Should |
| `/admin/tasks` | Task giving | Should |
| `/admin/announcements` | Buat pengumuman | Should |
| `/admin/settings` | Pengaturan (face mode client/server, expiry kode unik) | Should |

---

## 16. Permission Mapping

| Permission | superadmin/HR (web) | management (mobile) | supervisor (mobile) | karyawan (mobile) |
|------------|-------------|------------|----------|----------|
| login web (SSO/email) | ✅ | ❌ | ❌ | ❌ |
| kelola karyawan, kode unik, lokasi, shift | ✅ | ❌ | ❌ | ❌ |
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

*HR/superadmin tidak wajib ikut absen; bila perlu ikut absen, buatkan juga record karyawan untuk user tsb (mobile_role karyawan).

---

## Pertanyaan yang Belum Terjawab

| # | Pertanyaan | Keputusan |
|--|-----------|-----------|
| 1 | Subdomain Absensi pakai pola apa? | ✅ `{tenant-slug}-absensi.megakomsel.com` (pola global `{slug}-{app}.megakomsel.com`, konsisten dengan Central FR-008) |
| 2 | Foto selfie/template wajah disimpan berapa lama (retensi storage)? | — (perlu keputusan HR; default sementara: ikut umur akun) |
| 3 | Apakah owner/admin juga perlu absen (jadi employee juga) atau murni observer? | ✅ Management/direktur/owner = observer murni (tidak absen). HR/superadmin bisa dibuatkan record karyawan terpisah bila perlu ikut absen |
| 4 | Approval izin: satu level (langsung admin) atau berjenjang (supervisor → admin)? | ✅ Satu level: HR adalah satu-satunya approver |
| 5 | Nama & slug final aplikasi ini untuk didaftarkan ke katalog `apps` Central? | ✅ Nama: Absensi; slug: `absensi`; harga sementara Rp25.000/bln (edit via `/admin/apps`) |
| 6 | Integrasi payroll: apakah rekap kehadiran perlu diekspos lewat API ke aplikasi lain di ekosistem megakomsel.com? | — |
| 7 | Library face recognition client-side & server-side? | ✅ `face-api.js` (TensorFlow.js) untuk kedua mode — satu library jalan di browser (client) & Node/Nitro (server). Jika berat/akurasi kurang → migrasi ke Python microservice (insightface/ArcFace) |
| 8 | Kode unik: panjang karakter & format (8 karakter alfanumerik?) | — (default: 8 karakter acak, huruf besar + angka, hindari karakter ambigu) |

---

## Perubahan dari PRD Sebelumnya

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
