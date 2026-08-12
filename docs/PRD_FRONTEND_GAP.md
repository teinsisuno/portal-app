# PRD — Frontend Gap Completion (Nuxt4 Admin Web + PWA)

## 1. Header Metadata

| Field | Nilai |
|-------|-------|
| Author | Sigit / Paijo |
| Project | Absensi — penyelesaian gap frontend Nuxt4 |
| Versi | v1.1 |
| Status | Draft — disinkronkan dengan kode aktual 2026-08-12 |
| Tanggal | 2026-08-12 |
| Lokasi | `H:\laragon\www\absensi-app\frontend\` |
| Stack | Nuxt4 + Vue 3 + Tailwind CSS + Pinia + PWA |
| Target | **Produk jadi siap jual** (bukan MVP) |
| Backend | Semua endpoint harus sudah jadi (lihat PRD_BACKEND_GAP.md) |

---

## 2. Status Saat Ini

### Admin Web — 10 halaman sudah ada ✅
```
/admin                    Dashboard (stat cards + grid menu)
/admin/employees          CRUD karyawan + kode unik modal
/admin/invite-codes       Generate & pantau kode unik
/admin/locations          CRUD lokasi kerja
/admin/shifts             CRUD shift
/admin/groups             CRUD group karyawan
/admin/calendars          CRUD kalender kerja
/admin/work-patterns      CRUD pola kerja
/admin/schedules          CRUD jadwal snapshot
/admin/attendance         ✅ Roster matrix + filter (bulan/range/group) + modal detail (clock in/out + foto selfie) — SUDAH JADI
```

### Admin Web — Yang BELUM ADA ❌
```
/admin/leave-requests     Approve/reject izin/cuti/sakit
/admin/overtime-requests  Approve/reject lembur
/admin/visits             Lihat kunjungan karyawan
/admin/tasks              Task giving + monitoring
/admin/announcements      Buat & kelola pengumuman
/admin/employees/{id}     Detail karyawan + submodules (BUTUH backend GET /employees/{id})
/admin/settings           Pengaturan tenant
/admin/reports            Laporan & grafik
```

### PWA Mobile — 10 halaman sudah ada ✅
```
/splash                   Splash screen 1.5 detik
/register                 Registrasi email+nama+password
/set-pin                  Set PIN 4-6 digit
/setup                    Input kode unik
/setup/face               Scan wajah (UI siap, integrasi face-api.js belum)
/login                    Login admin web
/login-karyawan           Login PIN keypad + tab email
/dashboard                Dashboard + status card + riwayat
/clock                    Clock in/out + kamera + GPS
/attendance               Riwayat absensi + stat
/leave-request            Form izin/cuti/sakit + riwayat
/calendar                 Kalender jadwal bulanan
/profile                  Profil + pengaturan + logout
```

### PWA Mobile — Yang BELUM ADA ❌
```
/overtime-request         Pengajuan lembur
/visits                   Kunjungan (selfie + GPS + keterangan)
/visits/history           Riwayat kunjungan
/tasks                    Daftar tugas + update status
/announcements            Daftar pengumuman
/announcements/{id}       Detail pengumuman
/dashboard                Upgrade: statistik personal + grafik
```

### Komponen Baru Yang Sudah Ada ✅
```
SelfieThumb.vue           ✅ Thumbnail foto selfie (dipakai di modal detail absensi)
StatusBadge.vue           ✅ Badge status (valid/luar radius/flagged)
AppModal.vue              ✅ Modal dialog reusable
BiometricSetupModal.vue   ✅ Setup biometrik WebAuthn
AbsenModal.vue            ✅ Modal absen clock in/out
InfoRow.vue               ✅ Info baris label:value
MenuCard.vue              ✅ Card menu dashboard
MobileNav.vue             ✅ Bottom nav PWA
```

### Yang kurang di SEMUA halaman (Production Polish) ❌
```
- Skeleton loading states
- Pull-to-refresh
- Pagination / infinite scroll
- Search & filter di semua list
- Empty state illustrations
- Error boundary + retry button
- Toast notifications (ganti alert())
- Animasi transisi halaman
- Offline indicator
- Confirmation dialog (ganti confirm() native)
- Dark mode (opsional)
```

---

## 3. FASE 1: Admin Web — Halaman Baru

### Semua halaman admin baru mengikuti pola yang SAMA seperti `admin/employees.vue`:
- `definePageMeta({ layout: 'admin', middleware: 'guard' })`
- Gunakan `useApi<T>()` untuk GET, `api()` untuk POST/PUT/DELETE
- Gunakan `errorMessage()` helper
- Gunakan `AppModal` untuk form/modal
- Tabel pakai class `card overflow-x-auto` + `<table>` dengan styling yang sama
- Tombol: `btn-primary` (teal), `btn-secondary` (gray), `btn-danger` (red)

### 3.1 Halaman: `/admin/leave-requests`

**File:** `pages/admin/leave-requests.vue` **(BARU)**

**Fitur:**
- Tabel semua pengajuan izin/cuti/sakit (kolom: Nama Karyawan, Tipe, Tanggal, Alasan, Status, Aksi)
- Filter: tab status (Semua / Pending / Disetujui / Ditolak)
- Badge warna: Pending (amber), Disetujui (green), Ditolak (red)
- Tombol "Setujui" (hijau) dan "Tolak" (merah) untuk yang pending
- Modal tolak: textarea catatan wajib diisi
- Setelah approve/reject → refresh tabel + toast sukses

**API calls:**
- `GET /leave-requests?status=pending` — list (BUTUH backend)
- `POST /leave-requests/{id}/approve` — setujui (BUTUH backend)
- `POST /leave-requests/{id}/reject` + `{ notes }` — tolak (BUTUH backend)

### 3.2 Halaman: `/admin/overtime-requests`

**File:** `pages/admin/overtime-requests.vue` **(BARU)**

**Fitur:** Sama seperti leave-requests, beda kolom: Nama, Tanggal, Jam (start-end), Alasan, Status, Aksi.

**API calls:**
- `GET /overtime-requests?status=pending` (BUTUH backend)
- `POST /overtime-requests/{id}/approve` (BUTUH backend)
- `POST /overtime-requests/{id}/reject` + `{ notes }` (BUTUH backend)

### 3.3 Halaman: `/admin/visits`

**File:** `pages/admin/visits.vue` **(BARU)**

**Fitur:**
- List kunjungan semua karyawan (card grid, bukan tabel)
- Setiap card: nama karyawan, foto selfie (thumbnail), waktu, koordinat, keterangan
- Filter: pilih karyawan (dropdown) + tanggal
- Klik foto → lightbox fullscreen

**API calls:**
- `GET /visits?employee_id=&date=` (BUTUH backend)
- `GET /visits/{id}` (BUTUH backend)

### 3.4 Halaman: `/admin/tasks`

**File:** `pages/admin/tasks.vue` **(BARU)**

**Fitur:**
- Dua tab: "Daftar Tugas" + "Buat Tugas"
- Tab Daftar: tabel (Assignee, Judul, Deadline, Status, Aksi)
- Filter: karyawan, status
- Tab Buat: form (assignee dropdown, judul, deskripsi, due date)

**API calls:**
- `GET /tasks?assignee_id=&status=` (BUTUH backend)
- `POST /tasks` (BUTUH backend)
- `PUT /tasks/{id}` (BUTUH backend)
- `DELETE /tasks/{id}` (BUTUH backend)

### 3.5 Halaman: `/admin/announcements`

**File:** `pages/admin/announcements.vue` **(BARU)**

**Fitur:**
- List pengumuman (card: judul, preview isi, tanggal publish, author)
- Tombol "Buat Pengumuman" → modal form (judul, isi textarea, tombol publish/sekarang)
- Tombol edit & hapus
- Indikator published/draft

**API calls:**
- `GET /announcements` → dengan admin token, return termasuk draft (BUTUH backend)
- `POST /announcements` (BUTUH backend)
- `PUT /announcements/{id}` (BUTUH backend)
- `DELETE /announcements/{id}` (BUTUH backend)

### 3.6 Halaman: `/admin/employees/{id}` (Detail Karyawan)

**File:** `pages/admin/employees/[id].vue` **(BARU)**

⚠️ **BUTUH backend `GET /employees/{id}` dulu** — endpoint ini BELUM ADA.

**Fitur:**
- **Tab 1: Biodata** — nama, foto, email, jabatan, mobile_role, lokasi, shift, supervisor, status
- **Tab 2: Dokumen** — list dokumen, upload baru
- **Tab 3: Detail** — NIK, phone, gender, tempat/tgl lahir, agama, status nikah, alamat
- **Tab 4: Bank** — list rekening
- **Tab 5: Keluarga** — list anggota keluarga
- **Tab 6: Kontrak** — list kontrak kerja
- **Tab 7: Face** — status enroll, reset template
- **Tab 8: Absensi** — mini calendar heatmap 30 hari

**API calls:**
- `GET /employees/{id}` — **BELUM ADA** (lihat PRD_BACKEND_GAP.md Fase 6.0)

### 3.7 Halaman: `/admin/settings`

**File:** `pages/admin/settings.vue` **(BARU)**

**Fitur:**
- Form: Face Recognition Mode (client/server), Invite Code Expiry (jam), Default Radius (meter), Notifikasi Email HR (toggle)
- Tombol simpan → batch update

**API calls:**
- `GET /settings` (BUTUH backend)
- `PUT /settings` + `{ settings: { key: value, ... } }` (BUTUH backend)

### 3.8 Halaman: `/admin/reports`

**File:** `pages/admin/reports.vue` **(BARU)**

**Fitur:**
- Filter periode (bulan/tahun)
- Ringkasan: total hadir, izin, sakit, cuti, alpha, terlambat (stat cards)
- Grafik kehadiran (bar chart harian)
- Tabel rekap per karyawan
- Tombol export CSV

**Grafik:** Chart.js via `vue-chartjs` (ringan, populer)

**API calls:**
- `GET /admin/stats` — existing ✅
- `GET /attendance/roster?from=&to=` — existing ✅
- `GET /attendance/export?format=csv` — BUTUH backend

---

## 4. FASE 2: Admin Web — Upgrade Halaman Existing

### 4.1 Sidebar (`layouts/admin.vue`) — EDIT

**Status:** Sidebar sudah punya menu: Dashboard, Karyawan, Kode Unik, Group, Kalender Kerja, Pola Kerja, Shift, Jadwal Karyawan, **Absensi Karyawan ✅**, Lokasi Kerja.

**Tambahan yang belum:**
```html
<NuxtLink to="/admin/leave-requests" ...>
  <span>📝</span> Pengajuan Izin
</NuxtLink>
<NuxtLink to="/admin/overtime-requests" ...>
  <span>⏰</span> Pengajuan Lembur
</NuxtLink>
<NuxtLink to="/admin/visits" ...>
  <span>📍</span> Kunjungan
</NuxtLink>
<NuxtLink to="/admin/tasks" ...>
  <span>✅</span> Tugas
</NuxtLink>
<NuxtLink to="/admin/announcements" ...>
  <span>📢</span> Pengumuman
</NuxtLink>
<NuxtLink to="/admin/reports" ...>
  <span>📈</span> Laporan
</NuxtLink>
<NuxtLink to="/admin/settings" ...>
  <span>⚙️</span> Pengaturan
</NuxtLink>
```

Sidebar perlu jadi **collapsible** (mini mode: hanya icon) dan **mobile hamburger menu**.

### 4.2 Dashboard Admin (`admin/index.vue`) — EDIT

**Tambahan:**
1. **Grafik kehadiran 7 hari terakhir** — bar chart kecil (hadir vs tidak hadir)
2. **Widget "Butuh Aksi"** — card merah/orange: jumlah pending leave requests + overtime requests
3. **Quick actions** — tombol pintas: "Generate Kode Unik" → modal pilih karyawan
4. **Recent activity** — 10 aktivitas terakhir (clock in terbaru, pengajuan baru)

### 4.3 Halaman Employees (`admin/employees.vue`) — EDIT

**Tambahan:**
1. **Search bar** — input cari nama karyawan (client-side filter)
2. **Filter role mobile** — dropdown
3. **Pagination** — jika > 20 karyawan
4. **Avatar inisial** — warna random dari nama
5. **Klik nama karyawan → navigasi ke** `/admin/employees/{id}` (BUTUH halaman detail)
6. **Bulk actions** — checkbox + dropdown: "Assign Shift", "Assign Lokasi", "Nonaktifkan"

### 4.4 Halaman Attendance (`admin/attendance.vue`) — EDIT

**Status:** ✅ Roster matrix + filter (bulan/range/group) + modal detail (clock in/out + foto selfie) — SUDAH JADI.

**Tambahan:**
1. **Tombol Export** di pojok kanan atas → download CSV
2. **View toggle**: Matrix (existing) / List (tabel standar) / Calendar (kalender heatmap)
3. **Legend** — warna hijau (hadir), merah (alpha), kuning (izin), biru (libur)
4. **Hover tooltip** di sel — info lengkap tanpa klik
5. **Summary row** di bawah — total hadir per hari

### 4.5 Semua halaman list — EDIT

Tambahkan ke semua halaman list (employees, invite-codes, groups, locations, shifts, calendars, work-patterns, schedules):
1. **Search bar** di atas tabel
2. **Pagination** (client-side: `slice()` per halaman 20 items)
3. **Empty state** — ilustrasi + teks + tombol CTA
4. **Loading skeleton** — bukan cuma "Memuat…"

---

## 5. FASE 3: PWA Mobile — Halaman Baru

### Semua halaman PWA baru mengikuti pola:
- `definePageMeta({ layout: 'mobile', middleware: 'guard' })`
- Header teal dengan tombol back
- Card putih rounded-2xl + border + shadow-sm
- Gunakan `useApi<T>()` dan `api()`

### 5.1 Halaman: `/overtime-request`

**File:** `pages/overtime-request.vue` **(BARU)**

**Fitur:** Form (tanggal, jam mulai, jam selesai, alasan) + riwayat + batalkan. **BUTUH backend overtime.**

### 5.2 Halaman: `/visits`

**File:** `pages/visits.vue` **(BARU)**

**Fitur:** Tab kamera (foto + koordinat + keterangan) + Tab riwayat. **BUTUH backend visits.**

### 5.3 Halaman: `/tasks`

**File:** `pages/tasks.vue` **(BARU)**

**Fitur:** Tab status (Pending/In Progress/Done) + card tugas + bottom sheet update status. **BUTUH backend tasks.**

### 5.4 Halaman: `/announcements` + `/announcements/[id]`

**File:** `pages/announcements.vue` + `pages/announcements/[id].vue` **(BARU)**

**Fitur:** List card + badge BARU + detail full. **BUTUH backend announcements.**

### 5.5 Halaman: `/dashboard` (UPGRADE BESAR)

**File:** `pages/dashboard.vue` **(EDIT BESAR)**

**Tambahan:**
1. Statistik Personal Minggu Ini — mini bar chart
2. Status Face Recognition — card: "Wajah Terdaftar ✅" / "⚠️ Belum scan wajah"
3. Pengumuman Terbaru — card horizontal scroll
4. Quick Action FAB — langsung Clock In/Out
5. Swipe gesture — swipe kiri → attendance, swipe kanan → profile

### 5.6 Halaman: `/profile` (UPGRADE)

**File:** `pages/profile.vue` **(EDIT)** — lihat PRD_BACKEND_GAP.md Fase 6.1 (butuh `GET /me`).

---

## 6. FASE 4: PWA Mobile — Upgrade Halaman Existing

### 6.1 Clock Screen (`clock.vue`) — EDIT
1. Face-api.js integrasi → `POST /face/verify`
2. Offline queue → localStorage IndexedDB
3. GPS accuracy indicator
4. Haptic feedback
5. Countdown timer "Anda sudah bekerja: X jam"

### 6.2 Login Screen (`login-karyawan.vue`) — EDIT
1. Remember me → simpan email
2. Forgot PIN flow
3. Biometric icon animasi
4. Offline warning

### 6.3 Attendance Screen (`attendance.vue`) — EDIT
1. Calendar heatmap mini
2. Statistik pribadi bulan ini
3. Filter bulan
4. Swipe antar tanggal

### 6.4 Calendar Screen (`calendar.vue`) — EDIT
1. Legend shift
2. Tap tanggal → bottom sheet detail shift
3. Bulanan/year toggle

### 6.5 Leave Request Screen (`leave-request.vue`) — EDIT
1. Lampiran foto (kamera/gallery)
2. Auto-hitung durasi
3. Conflict detection

---

## 7. FASE 5: Komponen Reusable Baru

### 7.1 `ToastNotification.vue` — BARU
### 7.2 `ConfirmDialog.vue` — BARU
### 7.3 `SkeletonLoader.vue` — BARU
### 7.4 `EmptyState.vue` — UPGRADE
### 7.5 `Pagination.vue` — BARU
### 7.6 `SearchBar.vue` — BARU
### 7.7 `FilterTabs.vue` — BARU
### 7.8 `StatCard.vue` — BARU
### 7.9 `BottomSheet.vue` — BARU
### 7.10 `OfflineIndicator.vue` — BARU
### 7.11 `FaceGuide.vue` — BARU
### 7.12 `ImageLightbox.vue` — BARU

---

## 8. Composables & Store Baru

- `composables/toast.ts` — Toast notification system
- `composables/confirm.ts` — Confirmation dialog
- `composables/pagination.ts` — Pagination logic
- `composables/offline.ts` — Offline detection + queue
- `composables/search.ts` — Debounced search
- `stores/attendanceStore.ts` — Attendance state
- `stores/scheduleStore.ts` — Schedule state
- `composables/haptics.ts` — Haptic feedback

---

## 9. Production Polish

Animasi transisi, micro-interactions, skeleton loading, empty state, error handling global, dark mode, onboarding flow, audit trail.

---

## 10. Struktur File Final

```
frontend/app/
├── components/
│   ├── AbsenModal.vue                         ✅ existing
│   ├── AppModal.vue                           ✅ existing
│   ├── BiometricSetupModal.vue                ✅ existing
│   ├── InfoRow.vue                            ✅ existing
│   ├── MenuCard.vue                           ✅ existing
│   ├── MobileNav.vue                          ✅ existing — EDIT: tambah icon baru
│   ├── SelfieThumb.vue                        ✅ existing (baru)
│   ├── StatusBadge.vue                        ✅ existing (baru)
│   ├── ToastNotification.vue                  ❌ BARU
│   ├── ConfirmDialog.vue                      ❌ BARU
│   ├── SkeletonLoader.vue                     ❌ BARU
│   ├── EmptyState.vue                         ❌ BARU
│   ├── Pagination.vue                         ❌ BARU
│   ├── SearchBar.vue                          ❌ BARU
│   ├── FilterTabs.vue                         ❌ BARU
│   ├── StatCard.vue                           ❌ BARU
│   ├── BottomSheet.vue                        ❌ BARU
│   ├── OfflineIndicator.vue                   ❌ BARU
│   ├── FaceGuide.vue                          ❌ BARU
│   └── ImageLightbox.vue                      ❌ BARU
│
├── pages/
│   ├── admin/
│   │   ├── index.vue                          ✅ — EDIT: grafik + widget
│   │   ├── employees.vue                      ✅ — EDIT: search, pagination, bulk
│   │   ├── employees/
│   │   │   └── [id].vue                       ❌ BARU: detail karyawan 8 tab
│   │   ├── attendance.vue                     ✅ — EDIT: export, heatmap, legend
│   │   ├── leave-requests.vue                 ❌ BARU
│   │   ├── overtime-requests.vue              ❌ BARU
│   │   ├── visits.vue                         ❌ BARU
│   │   ├── tasks.vue                          ❌ BARU
│   │   ├── announcements.vue                  ❌ BARU
│   │   ├── reports.vue                        ❌ BARU
│   │   ├── settings.vue                       ❌ BARU
│   │   ├── activity.vue                       ❌ BARU
│   │   ├── invite-codes.vue                   ✅ — EDIT: pagination, search
│   │   ├── locations.vue                      ✅ — EDIT: pagination, search
│   │   ├── groups.vue                         ✅ — EDIT: pagination, search
│   │   ├── calendars.vue                      ✅ — EDIT: pagination, search
│   │   ├── work-patterns.vue                  ✅ — EDIT: pagination, search
│   │   ├── shifts.vue                         ✅ — EDIT: pagination, search
│   │   └── schedules.vue                      ✅ — EDIT: pagination, search
│   │
│   ├── dashboard.vue                          ✅ — EDIT BESAR
│   ├── clock.vue                              ✅ — EDIT: face-api.js + offline
│   ├── attendance.vue                         ✅ — EDIT: heatmap + statistik
│   ├── leave-request.vue                      ✅ — EDIT: lampiran + konflik
│   ├── overtime-request.vue                   ❌ BARU
│   ├── visits.vue                             ❌ BARU
│   ├── tasks.vue                              ❌ BARU
│   ├── announcements.vue                      ❌ BARU
│   ├── announcements/[id].vue                 ❌ BARU
│   ├── calendar.vue                           ✅ — EDIT: legend + detail
│   ├── profile.vue                            ✅ — EDIT: biodata + dokumen
│   └── onboarding.vue                         ❌ BARU
│
└── layouts/
    ├── admin.vue                              ✅ — EDIT: sidebar collapsible + menu baru
    ├── mobile.vue                             ✅ — EDIT: offline indicator
    ├── clock.vue                              ✅ existing
    └── default.vue                            ✅ existing
```

---

## 11. Urutan Pengerjaan (4-5 Hari)

### Hari 1 — Admin CRUD Pages (Fase 1)
1. `admin/leave-requests.vue` — full CRUD + approve/reject (BUTUH backend leave approval)
2. `admin/overtime-requests.vue` — full CRUD + approve/reject (BUTUH backend overtime)
3. `admin/visits.vue` — list + filter + lightbox (BUTUH backend visits)
4. `admin/tasks.vue` — CRUD + tab (BUTUH backend tasks)
5. `admin/announcements.vue` — CRUD (BUTUH backend announcements)
6. `admin/settings.vue` — form settings (BUTUH backend settings)
7. Edit `layouts/admin.vue` — tambah 7 menu sidebar + collapsible

### Hari 2 — Admin Detail + Reports (Fase 1-2)
1. `admin/employees/[id].vue` — 8 tab detail karyawan (BUTUH backend `GET /employees/{id}`)
2. `admin/reports.vue` — grafik + tabel + export
3. `admin/activity.vue` — log aktivitas (BUTUH backend activity log — nice to have)
4. Upgrade `admin/index.vue` — grafik + widget + quick actions
5. Upgrade `admin/employees.vue` — search + pagination + bulk
6. Upgrade `admin/attendance.vue` — export + heatmap + legend (roster matrix SUDAH JADI)

### Hari 3 — PWA New Pages (Fase 3)
1. `overtime-request.vue` — form + riwayat (BUTUH backend overtime)
2. `visits.vue` — tab kamera + riwayat (BUTUH backend visits)
3. `tasks.vue` — tab status + bottom sheet (BUTUH backend tasks)
4. `announcements.vue` + `announcements/[id].vue` (BUTUH backend announcements)
5. Upgrade `dashboard.vue` — statistik + grafik + FAB
6. Upgrade `profile.vue` — biodata lengkap + dokumen (BUTUH backend `GET /me`)

### Hari 4 — Components & Composables (Fase 5-6)
1. `ToastNotification.vue` + `composables/toast.ts`
2. `ConfirmDialog.vue` + `composables/confirm.ts`
3. `SkeletonLoader.vue` + `EmptyState.vue`
4. `Pagination.vue` + `composables/pagination.ts`
5. `SearchBar.vue` + `composables/search.ts`
6. `FilterTabs.vue` + `BottomSheet.vue` + `StatCard.vue`
7. `OfflineIndicator.vue` + `composables/offline.ts`
8. `FaceGuide.vue` + `ImageLightbox.vue`

### Hari 5 — Production Polish (Fase 7)
1. Animasi transisi + micro-interactions
2. Skeleton loading + empty state di semua halaman
3. Error handling global
4. Upgrade `clock.vue` — face-api.js + offline queue
5. Upgrade `MobileNav.vue` — tambah icon
6. `npm run generate` — pastikan build sukses

---

## 12. Estimasi Final

| Metrik | Saat Ini | Target |
|--------|---------|--------|
| Halaman admin | **10** | 18 |
| Halaman PWA | 13 | 20 |
| Komponen reusable | **8** (termasuk SelfieThumb + StatusBadge) | 23 |
| Composables | 2 | 8 |
| Stores | 1 | 3 |

---

## 13. Catatan untuk Agent

1. **Pola existing** — setiap halaman baru copy struktur dari halaman mirip (leave-requests tiru employees, visits tiru attendance)
2. **Jangan over-engineer** — grafik pakai Chart.js, bukan D3.js
3. **Tailwind CSS** sudah dikonfigurasi — tema teal: `primary-600` = #0d9488
4. **API base URL** — sudah di-handle `apiBase()`. Langsung pakai `useApi()` dan `api()`
5. **Auth token** — otomatis attach dari store
6. **PWA** — sudah aktif via `@vite-pwa/nuxt`
7. **Icons** — gunakan SVG inline, BUKAN icon library
8. **Modal** — gunakan `AppModal.vue` existing untuk admin
9. **Form** — class `input` dan `label` sudah ada di `main.css`
10. **SelfieThumb + StatusBadge** — SUDAH ADA di `components/`, bisa langsung dipakai
11. **Halaman attendance** — roster matrix + detail SUDAH JADI. Jangan buat ulang.
12. **Halaman detail karyawan** (`/admin/employees/{id}`) — BLOCKED sampai backend `GET /employees/{id}` dibuat
13. **Halaman approve leave/overtime** — BLOCKED sampai backend approval dibuat
14. **Semua halaman baru PWA** — BLOCKED sampai backend masing-masing selesai
