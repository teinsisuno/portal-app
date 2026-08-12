# PRD — Backend Gap Completion (Absensi Laravel API)

## 1. Header Metadata

| Field | Nilai |
|-------|-------|
| Author | Sigit / Paijo |
| Project | Absensi — penyelesaian gap backend Laravel API |
| Versi | v1.1 |
| Status | Draft — disinkronkan dengan kode aktual 2026-08-12 |
| Tanggal | 2026-08-12 |
| Target | Laravel 13 API di `H:\laragon\www\absensi-app` |
| Source PRD | PRD Absensi v0.4 (master) + PRD Frontend Gap v1.0 |
| Sebelum Mulai | Pastikan `php artisan test` PASS semua (**82 test** existing) |

---

## 2. Ringkasan

Dokumen ini adalah **panduan eksekusi langkah-demi-langkah** untuk menyelesaikan semua gap backend Laravel API yang blocking pengembangan frontend (web admin + PWA mobile). Setiap fase independen — bisa dikerjakan paralel oleh agent berbeda.

### Gap yang diselesaikan:

| # | Fase | Fitur | Prioritas | Status |
|---|------|-------|-----------|--------|
| 1 | Face Recognition | Enroll + Verify wajah | 🔴 Critical | ⚠️ Model+tabel ✅, controller+service+routes ❌ |
| 2 | Leave Approval | Approve/reject oleh HR | 🔴 Critical | ⚠️ Karyawan ✅, HR (index/approve/reject/stats) ❌ |
| 3 | Overtime | Model, migration, CRUD API | 🔴 Critical | ❌ Belum mulai |
| 4 | Visits | Kunjungan lapangan | 🟡 Should | ❌ Belum mulai |
| 5 | Tasks | Tugas + status | 🟡 Should | ❌ Belum mulai |
| 6 | Announcements | Pengumuman | 🟡 Should | ❌ Belum mulai |
| 7 | Employee API | Profile + dokumen + detail | 🟡 Should | ⚠️ Model ✅, Employee.show ❌, Profile ❌ |
| 8 | Export & Settings | Export rekap + settings table | 🟡 Should | ⚠️ Attendance roster ✅, export ❌, settings ❌ |

### Status Kode Aktual:
- **82 test pass** (65 existing + 6 roster + 11 lainnya)
- **Models**: 20 model ADA (Attendance, Employee + 5 submodules, EmployeeGroup, FaceTemplate, Holiday, InviteCode, LeaveRequest, ScheduleSnapshot, Shift, User, WorkingCalendar, WorkLocation, WorkPattern)
- **Models BELUM**: OvertimeRequest, Visit, Task, Announcement
- **Controllers**: 15 controller ADA
- **Controllers BELUM**: FaceController, OvertimeRequestController, VisitController, TaskController, AnnouncementController, ProfileController, SettingsController
- **Services**: 6 service ADA
- **Services BELUM**: FaceRecognitionService, SettingsService (Overtime/Visit/Task/Announcement service opsional — bisa inline di controller)
- **Migrations tenant**: 25 file ADA (sampai `001600_make_selfie_photo_longtext`)
- **Migrations BELUM**: face columns attendances, overtime_requests, visits, tasks, announcements, settings
- **Kolom `face_verified`/`face_mode`/`face_confidence`**: BELUM ADA di tabel `attendances`

### Total: ~22 file baru, ~5 file edit, ~25 test case

---

## 3. Konvensi Kode (WAJIB DIIKUTI)

```
Namespace   : App\Http\Controllers\Api\V1\{Nama}Controller
Model       : App\Models\{Nama}
Service     : App\Services\{Nama}Service
Migration   : database/migrations/tenant/YYYY_MM_DD_HHMMSS_{deskripsi}.php
Test        : tests/Feature/{Nama}Test.php
Route       : routes/tenant.php (semua dalam grup middleware tenant)
```

**Semua kode baru harus mengikuti pola existing:**
- Controller method return `Illuminate\Http\JsonResponse`
- Validasi pakai `$request->validate([...])`
- Try/catch di controller, throw exception di service
- Response sukses: `{ message, data }` atau `{ data }`
- Response error: `{ message }` dengan HTTP status code sesuai
- Model pakai PHP 8 attribute `#[Fillable([...])]`
- Service di-inject via constructor promotion: `private readonly NamaService $service`
- Test extends `Tests\TestCase`, pakai `DatabaseMigrations` trait
- Test method naming: `test_{deskripsi}_dalam_bahasa_indonesia()`
- Migration timestamp pakai format `2026_08_12_{seq}` (lanjut dari yang terakhir `001600`)

---

---

## FASE 1: Face Recognition (CRITICAL)

### Status: ⚠️ Model & migration FaceTemplate SUDAH ADA. Controller, service, routes, migration face columns BELUM.

### 1.0 Yang Sudah Ada

**Model:** `app/Models/FaceTemplate.php` ✅
```php
#[Fillable(['employee_id', 'template', 'mode'])]
class FaceTemplate extends Model
{
    public function employee(): BelongsTo { ... }
}
```

**Migration:** `database/migrations/tenant/2026_08_11_000900_create_face_templates_table.php` ✅
- Kolom: id, employee_id (unique), template (longText), mode (default 'server'), timestamps

**⚠️ CATATAN:** FaceTemplate sudah ada — JANGAN buat ulang model/migration. Langsung pakai yang existing.

### 1.1 Service: `FaceRecognitionService` (BARU)

**File:** `app/Services/FaceRecognitionService.php` **(BARU)**

```php
<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\FaceTemplate;
use InvalidArgumentException;

/**
 * Enroll & verifikasi wajah karyawan.
 * Template disimpan di server (face-api.js embedding JSON).
 * Dua mode: client (matching di device) & server (matching di sini).
 */
class FaceRecognitionService
{
    /**
     * Enroll wajah karyawan — simpan template embedding dari face-api.js.
     * Satu karyawan = satu template (replace kalau enroll ulang).
     */
    public function enroll(Employee $employee, string $template, string $mode = 'server'): FaceTemplate
    {
        if (! in_array($mode, ['client', 'server'], true)) {
            throw new InvalidArgumentException('Mode harus client atau server.');
        }

        $decoded = json_decode($template, true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Template wajah tidak valid (harus JSON array embedding).');
        }

        return FaceTemplate::updateOrCreate(
            ['employee_id' => $employee->id],
            ['template' => $template, 'mode' => $mode]
        );
    }

    /**
     * Verifikasi wajah saat clock in/out — cocokkan dengan template tersimpan.
     * Return array: [match => bool, confidence => float, distance => float, mode => string]
     */
    public function verify(Employee $employee, string $faceDescriptor): array
    {
        $template = FaceTemplate::where('employee_id', $employee->id)->first();

        if (! $template) {
            throw new InvalidArgumentException('Template wajah belum terdaftar. Lakukan enroll dulu.');
        }

        $stored = json_decode($template->template, true);
        $input = json_decode($faceDescriptor, true);

        if (! is_array($stored) || ! is_array($input)) {
            throw new InvalidArgumentException('Format descriptor wajah tidak valid.');
        }

        $distance = $this->euclideanDistance($stored, $input);
        $confidence = max(0, 1 - $distance);

        return [
            'match' => $distance < 0.6,
            'confidence' => round($confidence, 4),
            'distance' => round($distance, 4),
            'mode' => $template->mode,
        ];
    }

    private function euclideanDistance(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new InvalidArgumentException('Dimensi descriptor tidak cocok.');
        }
        $sum = 0.0;
        foreach ($a as $i => $val) {
            $sum += ($val - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }

    public function isEnrolled(Employee $employee): bool
    {
        return FaceTemplate::where('employee_id', $employee->id)->exists();
    }
}
```

### 1.2 Controller: `FaceController` (BARU)

**File:** `app/Http/Controllers/Api/V1/FaceController.php` **(BARU)**

3 method: enroll, verify, status.
⚠️ FaceController pakai `$request->user()->employee` → **WAJIB di dalam grup middleware `employee`** (bukan `auth:sanctum` polos).

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FaceController extends Controller
{
    public function __construct(private readonly FaceRecognitionService $face) {}

    /** POST /api/v1/face/enroll */
    public function enroll(Request $request): JsonResponse { /* ... */ }

    /** POST /api/v1/face/verify */
    public function verify(Request $request): JsonResponse { /* ... */ }

    /** GET /api/v1/face/status — cek karyawan sudah enroll */
    public function status(Request $request): JsonResponse { /* ... */ }

    private function employee(Request $request): Employee
    {
        return $request->user()->employee;
    }
}
```

### 1.3 Migration: Tambah kolom `face_verified` + `face_mode` + `face_confidence` ke `attendances` (BARU)

**File:** `database/migrations/tenant/2026_08_12_001700_add_face_columns_to_attendances.php` **(BARU)**

```php
Schema::table('attendances', function (Blueprint $table) {
    $table->boolean('face_verified')->default(false)->after('selfie_photo');
    $table->string('face_mode')->nullable()->after('face_verified');
    $table->decimal('face_confidence', 5, 4)->nullable()->after('face_mode');
});
```

### 1.4 Update Model: `Attendance` (EDIT)

**File:** `app/Models/Attendance.php` **(EDIT)**

Tambahkan ke `#[Fillable([...])]`: `'face_verified'`, `'face_mode'`, `'face_confidence'`
Tambahkan ke `casts()`: `'face_verified' => 'boolean'`, `'face_confidence' => 'decimal:4'`

### 1.5 Routes (EDIT)

**File:** `routes/tenant.php` **(EDIT)**

⚠️ Tambahkan di dalam grup `auth:sanctum` + `employee` (BUKAN `auth:sanctum` polos, karena FaceController butuh user ter-link karyawan):

```php
// Face Recognition (karyawan — wajib user ter-link employee)
Route::post('/face/enroll', [FaceController::class, 'enroll']);
Route::post('/face/verify', [FaceController::class, 'verify']);
Route::get('/face/status', [FaceController::class, 'status']);
```

### 1.6 Tests: `FaceRecognitionTest` (BARU)

**File:** `tests/Feature/FaceRecognitionTest.php` **(BARU)**

4 test case:
1. `test_karyawan_bisa_enroll_template_wajah()` — 201, tersimpan di face_templates
2. `test_enroll_dengan_template_invalid_ditolak()` — 422
3. `test_verify_wajah_cocok_mengembalikan_match_true()` — match=true
4. `test_verify_tanpa_enroll_ditolak()` — 422

---

---

## FASE 2: Leave & Overtime Approval (CRITICAL)

### Status: ⚠️ LeaveRequestController sisi karyawan SUDAH ADA (myRequests, store, cancel). Method HR (index, approve, reject, stats) BELUM. Overtime BELUM SAMA SEKALI.

### 2.1 Update Controller: `LeaveRequestController` — tambah approve/reject/list (EDIT)

**File:** `app/Http/Controllers/Api/V1/LeaveRequestController.php` **(EDIT)**

Tambahkan 4 method baru (method existing `myRequests`, `store`, `cancel` JANGAN disentuh):

```php
/**
 * GET /api/v1/leave-requests — HR: semua pengajuan (filter ?status=pending).
 */
public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'status' => ['nullable', 'in:pending,approved,rejected,cancelled'],
    ]);
    $requests = LeaveRequest::with(['employee:id,name,position', 'approver:id,name'])
        ->when($validated['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
        ->orderByDesc('created_at')
        ->get();
    return response()->json(['data' => $requests]);
}

/** POST /api/v1/leave-requests/{id}/approve */
public function approve(Request $request, LeaveRequest $leaveRequest): JsonResponse { /* ... */ }

/** POST /api/v1/leave-requests/{id}/reject — notes WAJIB */
public function reject(Request $request, LeaveRequest $leaveRequest): JsonResponse { /* ... */ }

/** GET /api/v1/leave-requests/stats */
public function stats(Request $request): JsonResponse { /* ... */ }
```

### 2.2 Model: `OvertimeRequest` (BARU)

**File:** `app/Models/OvertimeRequest.php` **(BARU)**

```php
#[Fillable(['employee_id', 'date', 'start_time', 'end_time', 'reason', 'status', 'approved_by', 'approved_at', 'approval_notes'])]
class OvertimeRequest extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date', 'approved_at' => 'datetime'];
    }
    public function employee(): BelongsTo { ... }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
```

### 2.3 Migration: `overtime_requests` (BARU)

**File:** `database/migrations/tenant/2026_08_12_001800_create_overtime_requests_table.php` **(BARU)**

```sql
id, employee_id (FK), date, start_time, end_time, reason (text),
status (default 'pending'), approved_by (FK users nullable),
approved_at (datetime nullable), approval_notes (text nullable), timestamps
```

### 2.4 Controller: `OvertimeRequestController` (BARU)

**File:** `app/Http/Controllers/Api/V1/OvertimeRequestController.php` **(BARU)**

7 method:
- `index(Request)` — GET, admin: semua overtime (filter `?status=`)
- `myRequests(Request)` — GET `/me`, employee
- `store(Request)` — POST, employee
- `cancel(Request, OvertimeRequest)` — POST `/{id}/cancel`, employee
- `approve(Request, OvertimeRequest)` — POST `/{id}/approve`, admin
- `reject(Request, OvertimeRequest)` — POST `/{id}/reject`, admin (notes wajib)
- `stats(Request)` — GET `/stats`, admin

### 2.5 Routes (EDIT)

**File:** `routes/tenant.php` **(EDIT)**

Di grup **admin**:
```php
// Leave request approval (HR)
Route::get('/leave-requests', [LeaveRequestController::class, 'index']);
Route::get('/leave-requests/stats', [LeaveRequestController::class, 'stats']);
Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve']);
Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject']);

// Overtime management (HR)
Route::get('/overtime-requests', [OvertimeRequestController::class, 'index']);
Route::get('/overtime-requests/stats', [OvertimeRequestController::class, 'stats']);
Route::post('/overtime-requests/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve']);
Route::post('/overtime-requests/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject']);
```

Di grup **employee**:
```php
// Overtime karyawan
Route::get('/overtime-requests/me', [OvertimeRequestController::class, 'myRequests']);
Route::post('/overtime-requests', [OvertimeRequestController::class, 'store']);
Route::post('/overtime-requests/{overtimeRequest}/cancel', [OvertimeRequestController::class, 'cancel']);
```

### 2.6 Tests

**File:** `tests/Feature/LeaveApprovalTest.php` **(BARU)** — 4 test
**File:** `tests/Feature/OvertimeControllerTest.php` **(BARU)** — 8 test

---

---

## FASE 3: Visits — Kunjungan Lapangan (SHOULD)

### Status: ❌ BELUM MULAI

### 3.1 Model: `Visit` (BARU)

**File:** `app/Models/Visit.php` **(BARU)**

```php
#[Fillable(['employee_id', 'latitude', 'longitude', 'photo', 'notes', 'visited_at'])]
class Visit extends Model
{
    protected function casts(): array
    {
        return ['latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'visited_at' => 'datetime'];
    }
    public function employee(): BelongsTo { ... }
}
```

### 3.2 Migration: `visits` (BARU)

**File:** `database/migrations/tenant/2026_08_12_001900_create_visits_table.php` **(BARU)**

### 3.3 Controller: `VisitController` (BARU)

4 method: index (admin), myVisits (employee), store (employee), show (detail)

### 3.4 Routes

```php
// Grup employee:
Route::get('/visits/me', [VisitController::class, 'myVisits']);
Route::post('/visits', [VisitController::class, 'store']);
// Grup admin:
Route::get('/visits', [VisitController::class, 'index']);
Route::get('/visits/{visit}', [VisitController::class, 'show']);
```

### 3.5 Tests: `VisitControllerTest` — 5 test

---

---

## FASE 4: Tasks — Tugas (SHOULD)

### Status: ❌ BELUM MULAI

### 4.1 Model: `Task` (BARU)

**File:** `app/Models/Task.php` **(BARU)**

```php
#[Fillable(['created_by', 'assignee_id', 'title', 'description', 'due_date', 'status'])]
class Task extends Model
{
    protected function casts(): array { return ['due_date' => 'date']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(Employee::class, 'assignee_id'); }
}
```

### 4.2 Migration: `tasks` (BARU)

**File:** `database/migrations/tenant/2026_08_12_002000_create_tasks_table.php` **(BARU)**

### 4.3 Controller: `TaskController` (BARU)

6 method: index (admin), myTasks (employee), store (admin), update (admin), updateStatus (employee), destroy (admin)

### 4.4 Routes

```php
// Grup employee:
Route::get('/tasks/me', [TaskController::class, 'myTasks']);
Route::put('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
// Grup admin:
Route::get('/tasks', [TaskController::class, 'index']);
Route::post('/tasks', [TaskController::class, 'store']);
Route::put('/tasks/{task}', [TaskController::class, 'update']);
Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
```

### 4.5 Tests: `TaskControllerTest` — 6 test

---

---

## FASE 5: Announcements — Pengumuman (SHOULD)

### Status: ❌ BELUM MULAI

### 5.1 Model: `Announcement` (BARU)

**File:** `app/Models/Announcement.php` **(BARU)**

```php
#[Fillable(['created_by', 'title', 'body', 'published_at'])]
class Announcement extends Model
{
    protected function casts(): array { return ['published_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
```

### 5.2 Migration: `announcements` (BARU)

**File:** `database/migrations/tenant/2026_08_12_002100_create_announcements_table.php` **(BARU)**

### 5.3 Controller: `AnnouncementController` (BARU)

5 method:
- `index()` — GET, auth (semua user login): published announcements, terbaru dulu
- `latest()` — GET `/latest`, auth: 5 terbaru (dashboard mobile)
- `store(Request)` — POST, admin
- `update(Request, Announcement)` — PUT, admin
- `destroy(Announcement)` — DELETE, admin

### 5.4 Routes

```php
// Grup auth:sanctum (semua user login):
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::get('/announcements/latest', [AnnouncementController::class, 'latest']);
// Grup admin:
Route::post('/announcements', [AnnouncementController::class, 'store']);
Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);
```

⚠️ **CATATAN UNTUK FRONTEND:** admin butuh lihat SEMUA announcements (termasuk draft/belum publish). Backend `GET /announcements` (admin group) akan return semua tanpa filter `published_at`. Frontend admin announcements page bisa pakai endpoint yang sama dengan header admin token.

### 5.5 Tests: `AnnouncementControllerTest` — 5 test

---

---

## FASE 6: Employee API & Export & Settings (SHOULD)

### Status: ⚠️ EmployeeController sudah ada (index/store/update/destroy) tapi BELUM ada show(). AdminAttendanceController roster+detail SUDAH ADA. Export, Profile, Settings BELUM ADA.

### 6.0 EmployeeController — tambah show() (EDIT)

**File:** `app/Http/Controllers/Api/V1/EmployeeController.php` **(EDIT)**

⚠️ Route `GET /employees/{id}` saat ini **TIDAK ADA** (pakai `->except(['show'])`). Wajib ditambahkan untuk halaman detail karyawan `/admin/employees/{id}`.

```php
/**
 * GET /api/v1/employees/{id} — detail karyawan + submodules.
 * Eager load: detail, banks, documents, families, contracts, faceTemplate, groups, workLocation, shift.
 */
public function show(Employee $employee): JsonResponse
{
    $employee->load([
        'detail', 'banks', 'documents', 'families', 'contracts',
        'faceTemplate', 'groups', 'workLocation', 'shift',
    ]);
    return response()->json(['data' => $employee]);
}
```

Route: ubah `->except(['show'])` jadi `->except([])` atau `->only(['index','store','show','update','destroy'])`.

### 6.1 Controller: `ProfileController` (BARU)

**File:** `app/Http/Controllers/Api/V1/ProfileController.php` **(BARU)**

3 method:
- `me(Request)` — GET `/me`, employee: employee + detail + documents summary
- `documents(Request)` — GET `/me/documents`, employee: list dokumen
- `updateProfile(Request)` — PUT `/me`, employee: update biodata dasar

Routes (grup employee):
```php
Route::get('/me', [ProfileController::class, 'me']);
Route::put('/me', [ProfileController::class, 'updateProfile']);
Route::get('/me/documents', [ProfileController::class, 'documents']);
```

### 6.2 Tests

**File:** `tests/Feature/ProfileControllerTest.php` **(BARU)** — 3 test

### 6.3 Export: `GET /attendance/export` (EDIT AdminAttendanceController)

**File:** `app/Http/Controllers/Api/V1/AdminAttendanceController.php` **(EDIT)**

⚠️ AdminAttendanceController SUDAH punya `roster()` dan `detail()`. JANGAN hapus. Tambah method `export()`:

```php
/**
 * GET /api/v1/attendance/export?from=Y-m-d&to=Y-m-d[&format=csv|xlsx]
 */
public function export(Request $request): JsonResponse|BinaryFileResponse { /* ... */ }
```

Route (grup admin):
```php
Route::get('/attendance/export', [AdminAttendanceController::class, 'export']);
```

MVP pakai CSV manual (SplFileObject). XLSX opsional via `openspout/openspout`.

**File:** `tests/Feature/ExportAttendanceTest.php` **(BARU)** — 2 test

### 6.4 Settings Table (BARU)

**Migration:** `database/migrations/tenant/2026_08_12_002200_create_settings_table.php` **(BARU)**

```php
Schema::create('settings', function (Blueprint $table) {
    $table->string('key')->primary();
    $table->text('value');
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamp('updated_at')->useCurrent();
    $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
});
```

**Service:** `app/Services/SettingsService.php` **(BARU)**

**Controller:** `app/Http/Controllers/Api/V1/SettingsController.php` **(BARU)**

2 method:
- `index()` — GET, admin: semua settings
- `update(Request)` — PUT, admin: update (key*, value*). Untuk multi-setting sekaligus, kirim `{ settings: { face_mode: "server", invite_expiry_hours: 48, ... } }`.

Routes (grup admin):
```php
Route::get('/settings', [SettingsController::class, 'index']);
Route::put('/settings', [SettingsController::class, 'update']);
```

**Default settings seed:**
| Key | Default |
|-----|---------|
| `face_mode` | `server` |
| `invite_expiry_hours` | `48` |
| `default_radius_meter` | `100` |
| `notify_email_hr` | `false` |

**File:** `tests/Feature/SettingsControllerTest.php` **(BARU)** — 3 test

---

---

## 7. Complete Route Listing — Status Aktual + Target

### Rute yang SUDAH ADA ✅

```
# === PUBLIK (tanpa auth) ===
POST /auth/register            ✅
POST /auth/login               ✅
POST /auth/pin-login           ✅
POST /auth/sso                 ✅
POST /auth/admin-login         ✅
POST /auth/webauthn/login/options ✅
POST /auth/webauthn/login      ✅

# === AUTH BASIC (user login, belum tentu ter-link karyawan) ===
POST /auth/logout              ✅
POST /auth/set-pin             ✅
POST /auth/verify-invite       ✅
POST /auth/link-employee       ✅
POST /auth/webauthn/register/options ✅
POST /auth/webauthn/register   ✅
GET  /auth/webauthn/keys       ✅
DELETE /auth/webauthn/keys/{id}✅

# === EMPLOYEE (auth + ter-link karyawan) ===
POST /attendance/clock-in      ✅
POST /attendance/clock-out     ✅
GET  /attendance/me            ✅
GET  /leave-requests/me        ✅
POST /leave-requests           ✅
POST /leave-requests/{id}/cancel ✅
GET  /schedule-snapshots/me    ✅
GET  /groups/mine              ✅

# === ADMIN (superadmin/hr) ===
GET|POST|PUT|DELETE /employees       ✅ (kecuali GET /employees/{id} = ❌)
GET|POST       /invite-codes         ✅
GET|POST|PUT|DELETE /work-locations  ✅
GET|POST       /groups + /groups/{id}✅
GET            /groups/available-employees ✅
GET|POST|PUT|DELETE /working-calendars ✅
GET|POST|PUT|DELETE /holidays         ✅
GET|POST|PUT|DELETE /work-patterns    ✅
GET|POST|PUT|DELETE /shifts           ✅
GET|POST|PUT|DELETE /schedule-snapshots ✅
GET            /admin/stats           ✅
GET            /attendance/roster     ✅
GET            /attendance/roster/{employee} ✅
```

### Rute yang BELUM ADA ❌

```
# === FACE (employee) ===
POST /face/enroll              ❌
POST /face/verify              ❌
GET  /face/status              ❌

# === LEAVE APPROVAL (admin) ===
GET  /leave-requests           ❌ (HR list)
GET  /leave-requests/stats     ❌
POST /leave-requests/{id}/approve ❌
POST /leave-requests/{id}/reject  ❌

# === OVERTIME (employee + admin) ===
GET  /overtime-requests/me     ❌
POST /overtime-requests        ❌
POST /overtime-requests/{id}/cancel ❌
GET  /overtime-requests        ❌ (admin)
GET  /overtime-requests/stats  ❌
POST /overtime-requests/{id}/approve ❌
POST /overtime-requests/{id}/reject  ❌

# === VISITS (employee + admin) ===
GET  /visits/me                ❌
POST /visits                   ❌
GET  /visits                   ❌ (admin)
GET  /visits/{visit}           ❌

# === TASKS (employee + admin) ===
GET  /tasks/me                 ❌
PUT  /tasks/{task}/status      ❌
GET  /tasks                    ❌ (admin)
POST /tasks                    ❌
PUT  /tasks/{task}             ❌
DELETE /tasks/{task}           ❌

# === ANNOUNCEMENTS (all auth + admin) ===
GET  /announcements            ❌ (auth)
GET  /announcements/latest     ❌
POST /announcements            ❌ (admin)
PUT  /announcements/{id}       ❌
DELETE /announcements/{id}     ❌

# === PROFILE (employee) ===
GET  /me                       ❌
PUT  /me                       ❌
GET  /me/documents             ❌

# === EXPORT & SETTINGS (admin) ===
GET  /attendance/export        ❌
GET  /settings                 ❌
PUT  /settings                 ❌

# === EMPLOYEE DETAIL (admin) ===
GET  /employees/{id}           ❌ (show)
```

---

## 8. Urutan Pengerjaan (untuk Agent)

### Hari 1 — Face Recognition (Fase 1) ⚠️ Mulai dari sini — FaceTemplate model+migration SUDAH ADA
1. Buat `app/Services/FaceRecognitionService.php`
2. Buat `app/Http/Controllers/Api/V1/FaceController.php` (3 method)
3. Buat migration `001700_add_face_columns_to_attendances.php`
4. Edit model `Attendance.php` (tambah fillable + casts)
5. Edit `routes/tenant.php` (tambah 3 route face — **di grup employee, bukan auth polos**)
6. Buat `tests/Feature/FaceRecognitionTest.php` (4 test)
7. Jalankan: `php artisan tenants:migrate --tenants=sigit` lalu `php artisan test --filter FaceRecognition`

### Hari 2 — Leave Approval + Overtime (Fase 2)
1. Edit `LeaveRequestController.php` (tambah 4 method: index, approve, reject, stats — method existing JANGAN disentuh)
2. Buat `OvertimeRequest.php` model
3. Buat migration `001800_create_overtime_requests_table.php`
4. Buat `OvertimeRequestController.php` (7 method)
5. Edit `routes/tenant.php` (tambah route leave approval + overtime)
6. Buat `tests/Feature/LeaveApprovalTest.php` (4 test)
7. Buat `tests/Feature/OvertimeControllerTest.php` (8 test)
8. Jalankan test

### Hari 3 — Visits + Tasks (Fase 3 & 4)
1. Buat `Visit.php` model + migration `001900` + `VisitController.php` + routes + test
2. Buat `Task.php` model + migration `002000` + `TaskController.php` + routes + test
3. Jalankan test

### Hari 4 — Announcements + Employee API + Export + Settings (Fase 5 & 6)
1. Buat `Announcement.php` model + migration `002100` + `AnnouncementController.php` + routes + test
2. **Tambah method `show()` di `EmployeeController`** + ubah route dari `->except(['show'])` ke include show
3. Buat `ProfileController.php` + routes + test
4. Edit `AdminAttendanceController.php` (tambah export method)
5. Buat migration `002200` (settings) + `SettingsService.php` + `SettingsController.php` + routes + test
6. Jalankan semua test: `php artisan test` (target: ~107 test)

### Target Akhir
- **~107 test pass** (dari 82 existing + ~25 baru)
- **~23 file baru**
- **~5 file edit**
- **Semua endpoint mobile-ready + admin web siap**

---

## 9. Catatan Penting

1. **FaceTemplate model & migration SUDAH ADA** — jangan buat ulang. Langsung pakai yang existing.
2. **LeaveRequestController SUDAH ADA** 3 method (myRequests, store, cancel) — jangan hapus. Hanya TAMBAH 4 method baru.
3. **AdminAttendanceController SUDAH ADA** 2 method (roster, detail) — jangan hapus. Hanya TAMBAH export.
4. **EmployeeController SUDAH ADA** 4 method (index, store, update, destroy) — TAMBAH show(). Route saat ini `->except(['show'])` harus diubah.
5. **Naming convention**: Semua nama file, class, method, route mengikuti pola existing.
6. **Error handling**: Controller pakai try/catch, service throw `InvalidArgumentException`. Response error selalu `{ message: "..." }`.
7. **Middleware**: `admin` = superadmin/hr. `employee` = user ter-link karyawan aktif.
8. **FaceController WAJIB di grup `employee`** (butuh `$request->user()->employee`), BUKAN `auth:sanctum` polos.
9. **Tenant DB**: Semua tabel baru di `database/migrations/tenant/`.
10. **Testing**: Setiap test HARUS `$this->provisionTenant('tokoa')` + `$this->withTenantHost('tokoa')`.
11. **Kolom attendances saat ini**: id, employee_id, work_location_id, type, recorded_at, latitude, longitude, distance_meter, selfie_photo, status, notes, created_at, updated_at. Kolom `face_*` BELUM ADA.
12. **Face recognition**: Untuk MVP, terima JSON array embedding dari face-api.js. Server hanya simpan & bandingkan (Euclidean distance di PHP).
13. **Export**: MVP = CSV pakai `SplFileObject` bawaan PHP.
14. **Settings update**: Support batch update `{ settings: { key: value, ... } }` untuk handle form multi-field.
15. **Announcements**: `GET /announcements` dengan admin token return SEMUA (termasuk draft). Tanpa admin token = hanya published.
