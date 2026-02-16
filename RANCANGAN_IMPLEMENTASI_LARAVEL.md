# Rancangan Implementasi Dashboard Monitoring dengan Laravel

## 📋 Executive Summary

Migrasi sistem monitoring dari Streamlit (Python) ke Laravel dengan PostgreSQL/MySQL, mencakup multi-kegiatan management, dashboard per kabupaten & PJ, export Excel, dan **SSO BPS Authentication**.

---

## 🔐 Authentication

Aplikasi menggunakan **SSO BPS (sso.bps9702.com)** untuk autentikasi dengan OAuth 2.0 flow.

### SSO Integration
- **OAuth 2.0 Flow**: Authorization code grant
- **User Management**: Auto-create/update user dari SSO
- **Role-Based Access**: Menggunakan roles dari SSO BPS
- **Session Management**: Laravel session dengan remember me
- **Callback URL**: `http://localhost:8000/auth/callback`

### SSO Endpoints
- Login: `https://sso.bps9702.com/login`
- Authorize: `https://sso.bps9702.com/api/v1/authorize`
- Token: `https://sso.bps9702.com/api/v1/token`
- Data: `https://sso.bps9702.com/api/v1/data/*`

**Dokumentasi lengkap SSO**: Lihat `IMPLEMENTASI_SSO_BPS.md`

---

## 🎯 Fitur yang Akan Diimplementasikan

Berdasarkan dokumentasi Streamlit yang ada:

### Core Features
1. **Multi-Kegiatan Management**
   - CRUD kegiatan (Create, Read, Update, Delete)
   - Switch antar kegiatan
   - Activity tracking (last updated, file history)

2. **Dashboard Per Kabupaten**
   - 3 Kabupaten: 9702, 9705, 9706
   - Metrics: Target, Open, Submitted, Approved, Rejected
   - Visualisasi stacked bar chart (persentase)
   - Detail per desa dalam kabupaten

3. **Dashboard Per PJ (Penanggung Jawab)**
   - Performance metrics per PJ
   - Horizontal stacked bar chart
   - List desa yang ditangani

4. **Detail Per Desa**
   - Search by nama atau kode desa
   - Filter by kabupaten
   - View lengkap semua metrics

5. **Import Data**
   - Upload JSON (mapping PJ)
   - Upload CSV/ZIP (data monitoring)
   - Multi-format support
   - Validasi data

6. **Export Excel**
   - Export per kabupaten
   - Export per PJ
   - Export semua data

---

## 🗄️ Database Schema

### PostgreSQL/MySQL Tables

```sql
-- Master table untuk kegiatan
CREATE TABLE activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,           -- slug: monitoring-dashboard
    display_name VARCHAR(255) NOT NULL,          -- "Monitoring Dashboard"
    description TEXT,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    last_data_upload_at TIMESTAMP NULL,
    json_filename VARCHAR(255),
    zip_filename VARCHAR(255)
);

-- Index untuk performa
CREATE INDEX idx_activities_name ON activities(name);

-- Data monitoring per desa
CREATE TABLE monitoring_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,

    -- Identifikasi desa
    village_code VARCHAR(50) NOT NULL,          -- [9702010003]
    village_name VARCHAR(255) NOT NULL,         -- WAMENA KOTA
    regency_code VARCHAR(10) NOT NULL,          -- 9702

    -- Metrics
    target INT NOT NULL DEFAULT 0,
    open INT NOT NULL DEFAULT 0,
    submitted INT NOT NULL DEFAULT 0,
    approved INT NOT NULL DEFAULT 0,
    rejected INT NOT NULL DEFAULT 0,

    -- PJ mapping
    pj_code VARCHAR(50),
    pj_name VARCHAR(255),

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Indexes untuk performa query
CREATE INDEX idx_monitoring_activity ON monitoring_data(activity_id);
CREATE INDEX idx_monitoring_regency ON monitoring_data(regency_code);
CREATE INDEX idx_monitoring_village ON monitoring_data(village_code);
CREATE INDEX idx_monitoring_pj ON monitoring_data(pj_code);

-- Mapping PJ (jika butuh tabel terpisah untuk fleksibilitas)
CREATE TABLE pj_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,
    village_code VARCHAR(50) NOT NULL,
    pj_code VARCHAR(50) NOT NULL,
    pj_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    UNIQUE KEY unique_activity_village (activity_id, village_code)
);

-- Upload history untuk tracking
CREATE TABLE upload_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_id BIGINT UNSIGNED NOT NULL,
    file_type ENUM('json', 'csv', 'zip') NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_size BIGINT,
    records_imported INT DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    uploaded_by BIGINT UNSIGNED,                -- user_id (future)
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
);

-- Users table (sudah ada di Laravel default migration)
-- Bisa digunakan untuk autentikasi nanti
```

---

## 🏗️ Struktur Laravel

### 1. Models

```
app/Models/
├── Activity.php              # Model untuk kegiatan
├── MonitoringData.php        # Model untuk data monitoring
├── PjMapping.php             # Model untuk mapping PJ
└── UploadHistory.php         # Model untuk tracking upload
```

**Relationships:**
- `Activity` hasMany `MonitoringData`
- `Activity` hasMany `PjMapping`
- `Activity` hasMany `UploadHistory`

### 2. Controllers

```
app/Http/Controllers/
├── ActivityController.php           # CRUD kegiatan
├── DashboardController.php          # Dashboard utama
├── RegencyDashboardController.php   # Dashboard per kabupaten
├── PjDashboardController.php        # Dashboard per PJ
├── VillageController.php            # Detail & search desa
├── ImportController.php             # Upload JSON/CSV/ZIP
└── ExportController.php             # Export Excel
```

### 3. Services (Business Logic)

```
app/Services/
├── ActivityService.php              # Logic kegiatan
├── DataImportService.php            # Parse & import data
│   ├── JsonImporter.php
│   ├── CsvImporter.php
│   └── ZipImporter.php
├── DashboardService.php             # Aggregasi data dashboard
└── ExcelExportService.php           # Generate Excel
```

### 4. Jobs (Async Processing)

```
app/Jobs/
├── ProcessCsvImportJob.php          # Import CSV async
├── ProcessZipImportJob.php          # Extract & import ZIP
└── GenerateExcelExportJob.php       # Generate Excel async
```

### 5. Routes

```php
// routes/web.php

use App\Http\Controllers\Auth\SsoAuthController;

// SSO Authentication Routes (Public)
Route::prefix('auth')->name('sso.')->group(function () {
    Route::get('login', [SsoAuthController::class, 'redirectToSso'])
        ->name('login');
    Route::get('callback', [SsoAuthController::class, 'handleCallback'])
        ->name('callback');
    Route::post('logout', [SsoAuthController::class, 'logout'])
        ->name('logout');
});

// Redirect /login ke SSO
Route::get('/login', fn() => redirect()->route('sso.login'))->name('login');

// Protected Routes (Require Authentication)
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Activity Management
    Route::resource('activities', ActivityController::class);
    Route::post('activities/{activity}/set-active', [ActivityController::class, 'setActive'])
        ->name('activities.set-active');

    // Dashboards
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('regency/{code}', [RegencyDashboardController::class, 'show'])
            ->name('regency');
        Route::get('pj/{code}', [PjDashboardController::class, 'show'])
            ->name('pj');
    });

    // Villages
    Route::get('villages', [VillageController::class, 'index'])->name('villages.index');
    Route::get('villages/{code}', [VillageController::class, 'show'])->name('villages.show');

    // Import (Require admin/editor role)
    Route::prefix('import')->name('import.')->middleware('can:import,App\Models\Activity')->group(function () {
        Route::post('json', [ImportController::class, 'uploadJson'])->name('json');
        Route::post('csv', [ImportController::class, 'uploadCsv'])->name('csv');
        Route::post('zip', [ImportController::class, 'uploadZip'])->name('zip');
    });

    // Export
    Route::prefix('export')->name('export.')->group(function () {
        Route::get('regency/{code}', [ExportController::class, 'regency'])->name('regency');
        Route::get('pj/{code}', [ExportController::class, 'pj'])->name('pj');
        Route::get('all', [ExportController::class, 'all'])->name('all');
    });
});
```

### 6. Views (Blade Templates)

```
resources/views/
├── layouts/
│   ├── app.blade.php                # Master layout
│   └── partials/
│       ├── header.blade.php
│       ├── sidebar.blade.php
│       └── footer.blade.php
├── dashboard/
│   ├── index.blade.php              # Dashboard utama
│   ├── regency.blade.php            # Dashboard per kabupaten
│   └── pj.blade.php                 # Dashboard per PJ
├── activities/
│   ├── index.blade.php              # List kegiatan
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── villages/
│   ├── index.blade.php              # Search & list desa
│   └── show.blade.php               # Detail desa
├── import/
│   └── index.blade.php              # Upload form
└── components/
    ├── metric-card.blade.php        # Component untuk metric
    ├── stacked-bar-chart.blade.php  # Component chart
    └── activity-selector.blade.php  # Dropdown kegiatan
```

---

## 🎨 Frontend Stack

### CSS Framework
- **Tailwind CSS** (sudah include di Laravel 12)
- Atau **Bootstrap 5** (jika lebih familiar)

### Chart Library
- **Chart.js** atau **ApexCharts** untuk visualisasi
- Stacked bar charts untuk kabupaten & PJ

### Alpine.js / Livewire
- **Alpine.js**: Untuk interaktivitas ringan (dropdown, modal, search)
- **Livewire** (optional): Jika ingin real-time tanpa banyak JS

### Icons
- **Heroicons** atau **Font Awesome**

---

## 📦 Package yang Dibutuhkan

### Composer Packages

```bash
# Excel export/import
composer require maatwebsite/excel

# PDF export (optional)
composer require barryvdh/laravel-dompdf

# Chart library backend (optional, bisa pakai JS saja)
# composer require consoletvs/charts

# File upload handling
# composer require spatie/laravel-medialibrary (optional, untuk advanced file handling)
```

### NPM Packages

```bash
# Chart library
npm install chart.js

# Atau ApexCharts
npm install apexcharts

# File upload dengan preview (optional)
npm install filepond
```

---

## 🔄 Data Flow

### Authentication Flow

```
1. User access protected route
   ↓
2. Middleware check if authenticated
   ↓
3. If not: Redirect to SSO Login
   ↓
4. SSO redirects back with code
   ↓
5. Exchange code for user data
   ↓
6. Create/Update user in database
   ↓
7. Login user & redirect to dashboard
```

### Upload Flow

```
1. User upload CSV/ZIP via form
   ↓
2. Check user permission (import ability)
   ↓
3. Controller validate file
   ↓
4. Store file to storage/app/uploads
   ↓
5. Dispatch Job: ProcessCsvImportJob
   ↓
6. Job: Parse CSV → Normalize format
   ↓
7. Job: Insert/Update MonitoringData
   ↓
8. Update Activity.last_data_upload_at
   ↓
9. Log to UploadHistory
   ↓
10. Return success/fail notification
```

### Dashboard Data Flow

```
1. User select activity dari dropdown
   ↓
2. Store active activity in session
   ↓
3. Controller query MonitoringData by activity_id
   ↓
4. DashboardService aggregate data:
   - Group by regency_code
   - Calculate SUM(target, open, submitted, etc)
   - Calculate percentages
   ↓
5. Cache results for 5 minutes
   ↓
6. Pass data to Blade view
   ↓
7. Render chart dengan Chart.js
```

---

## 🎬 Implementation Phases

### Phase 0: Authentication (Week 1) 🔐
✅ Setup SSO BPS configuration
✅ Create SSO Service & Auth Controller
✅ Database migration untuk SSO fields
✅ Implement OAuth flow
✅ Login/Logout functionality
✅ Middleware & guards
✅ Role-based access control

### Phase 1: Foundation (Week 1)
✅ Setup database migrations (activities, monitoring_data, dll)
✅ Create models dengan relationships
✅ Seed sample data untuk testing
✅ Setup basic routes & controllers

### Phase 2: Activity Management (Week 1-2)
✅ CRUD kegiatan (create, read, update, delete)
✅ Activity selector component
✅ Session management untuk active activity
✅ Permission check (admin only)

### Phase 3: Data Import (Week 2-3)
✅ Upload JSON (PJ mapping)
✅ Upload CSV dengan format normalization
✅ Upload ZIP dengan extraction
✅ Validation & error handling
✅ Progress tracking
✅ Upload history logging

### Phase 4: Dashboard Per Kabupaten (Week 3-4)
✅ List kabupaten (9702, 9705, 9706)
✅ Aggregate metrics per kabupaten
✅ Stacked bar chart (persentase)
✅ Detail desa dalam kabupaten
✅ Caching untuk performance

### Phase 5: Dashboard Per PJ (Week 4)
✅ List PJ
✅ Aggregate metrics per PJ
✅ Horizontal bar chart
✅ List desa yang ditangani

### Phase 6: Detail & Search (Week 5)
✅ Search desa by nama/kode
✅ Filter by kabupaten
✅ Detail view per desa
✅ Pagination

### Phase 7: Export Excel (Week 5)
✅ Export per kabupaten
✅ Export per PJ
✅ Export all data
✅ Async job untuk large datasets

### Phase 8: Polish & Testing (Week 6)
✅ UI/UX improvements
✅ Responsive design
✅ Error handling & logging
✅ Unit & feature tests
✅ SSO integration tests
✅ Documentation
✅ Deployment preparation

---

## 🚀 Migration Strategy

### Data Migration dari Streamlit

Jika sudah ada data di Supabase PostgreSQL:

1. **Export data dari Supabase**
   ```sql
   -- Export activities
   COPY activities TO '/tmp/activities.csv' CSV HEADER;

   -- Export monitoring data
   COPY monitoring_data_1 TO '/tmp/monitoring_data.csv' CSV HEADER;
   ```

2. **Import ke Laravel database**
   - Gunakan Laravel Seeder
   - Atau import langsung via SQL

3. **Script konversi**
   ```php
   // database/seeders/MigrateFromStreamlitSeeder.php

   // Read CSV → Insert into new schema
   ```

---

## 📊 Dashboard Mockup Structure

### Main Dashboard (/)

```
┌─────────────────────────────────────────────────────────┐
│  🏠 Dashboard Monitoring                    👤 User     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  📁 Kegiatan: [Monitoring Dashboard ▼]                 │
│                                                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │
│  │ 🎯 Target│ │ 📂 Open  │ │ 📝 Submit│ │ ✅ Approve│ │
│  │   500    │ │   150    │ │   200    │ │   150    │ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ │
│                                                         │
│  🏛️ Per Kabupaten │ 👤 Per PJ │ 🏘️ Per Desa │ 📥 Export│
│  ─────────────────────────────────────────────────────│
│                                                         │
│  [Tab Content: Charts & Tables]                        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Sidebar (Collapsed by default)

```
┌──────────────────┐
│  ☰ Menu          │
├──────────────────┤
│  🏠 Dashboard    │
│  📊 Kegiatan     │
│  📁 Import Data  │
│  📥 Export       │
│  ⚙️  Settings    │
└──────────────────┘
```

---

## 🔧 Configuration

### Environment Variables

```env
# .env additions

# Monitoring App Config
MONITORING_DEFAULT_ACTIVITY=1
MONITORING_UPLOAD_MAX_SIZE=10240  # KB
MONITORING_ALLOWED_EXTENSIONS=csv,zip,json

# Excel Export
EXCEL_EXPORT_TIMEOUT=300
EXCEL_EXPORT_MEMORY_LIMIT=512M
```

---

## 🧪 Testing Strategy

### Unit Tests
- Model relationships
- Service methods (import, export, aggregation)
- SSO Service methods

### Feature Tests
- **SSO Authentication**: Login flow, callback, user creation/update
- Upload CSV/JSON/ZIP
- Dashboard data accuracy
- Export Excel integrity
- Activity CRUD
- Role-based access control

### Browser Tests (Dusk - optional)
- End-to-end user flows
- SSO login integration
- Chart rendering

---

## 📈 Performance Considerations

### Database
- Index pada kolom yang sering di-query (regency_code, pj_code, activity_id)
- Use `chunk()` untuk large datasets
- Cache aggregate results

### File Upload
- Queue untuk processing CSV/ZIP
- Validate file size & type
- Store di `storage/app` dengan proper permissions

### Charts
- Limit data points (pagination/lazy loading)
- Use server-side aggregation, bukan client-side

### Caching
```php
// Cache dashboard metrics (5 menit)
Cache::remember('dashboard.metrics.' . $activityId, 300, function() {
    return $this->aggregateMetrics();
});
```

---

## 🔐 Security

### Authentication & Authorization
- **SSO Integration**: OAuth 2.0 dengan state parameter (CSRF protection)
- **Session Security**: Secure, httponly cookies
- **HTTPS Required**: Di production untuk OAuth
- **Role-Based Access**: Policies & gates berdasarkan SSO roles

### Application Security
- **File Upload Validation**: MIME type, extension, size
- **SQL Injection**: Gunakan Eloquent ORM
- **XSS**: Blade auto-escaping
- **CSRF**: Token di semua form
- **Rate Limiting**: Auth routes & API endpoints
- **Logging**: Semua auth attempts & critical actions

### Data Security
- **Environment Variables**: Sensitive data di .env
- **Database Encryption**: Untuk data sensitif (optional)
- **Audit Trail**: Log perubahan data via UploadHistory

---

## 📚 Documentation Plan

1. **README.md**: Setup, instalasi, deployment
2. **IMPLEMENTASI_SSO_BPS.md**: SSO integration guide ✅
3. **RANCANGAN_IMPLEMENTASI_LARAVEL.md**: Architecture & implementation plan ✅
4. **API.md**: Endpoint documentation (jika ada API)
5. **DATABASE.md**: Schema & relationships
6. **DEPLOYMENT.md**: Cara deploy ke production
7. **USER_GUIDE.md**: Panduan penggunaan untuk end-user

---

## 🎯 Success Criteria

✅ Semua fitur dari Streamlit ter-implementasi
✅ Dashboard responsif (mobile-friendly)
✅ Import CSV/ZIP berhasil tanpa error
✅ Export Excel sesuai format
✅ Performance: Dashboard load < 2 detik
✅ Code coverage > 70%
✅ Zero security vulnerabilities

---

## 🚀 Deployment Options

### 1. Traditional Hosting (Shared/VPS)
- Upload via FTP/Git
- Setup database
- Run `php artisan migrate`
- Configure web server (Apache/Nginx)

### 2. Laravel Forge
- One-click deployment
- Auto SSL
- Easy database management

### 3. Heroku
- Git push deployment
- Add PostgreSQL addon
- Config env vars

### 4. Docker
- `Dockerfile` + `docker-compose.yml`
- Nginx + PHP-FPM + PostgreSQL

---

## 📝 Next Steps

1. **Review & approval** rancangan ini
2. **Setup development environment**
3. **Create migrations** sesuai schema
4. **Implement Phase 1** (Foundation)
5. **Iterative development** sesuai phases

---

## 💡 Rekomendasi Tambahan

### Optional Features (Future)
- **Authentication & Authorization**: Laravel Breeze/Jetstream
- **Multi-tenancy**: Jika ada multiple organisasi
- **Real-time updates**: Laravel Echo + Pusher
- **API**: RESTful API untuk mobile/integration
- **Notifications**: Email/SMS saat upload selesai
- **Audit Log**: Track siapa upload apa & kapan
- **Data Versioning**: Keep history saat upload ulang

### Best Practices
- Follow **PSR-12** coding standard
- Use **Laravel Pint** untuk formatting
- Write **PHPDoc** untuk setiap method
- Implement **Repository Pattern** jika complex
- Use **Form Requests** untuk validation
- **Service Layer** untuk business logic

---

## 📞 Support & Maintenance

- **Bug tracking**: GitHub Issues
- **Version control**: Git (semantic versioning)
- **CI/CD**: GitHub Actions / GitLab CI
- **Monitoring**: Laravel Telescope (dev), Sentry (prod)

---

**Dibuat**: 2026-02-16
**Versi**: 1.0.0
**Author**: Development Team

---

## Appendix A: Sample Code Snippets

### Migration Example

```php
// database/migrations/xxxx_create_activities_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamp('last_data_upload_at')->nullable();
            $table->string('json_filename')->nullable();
            $table->string('zip_filename')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
```

### Model Example

```php
// app/Models/Activity.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'last_data_upload_at',
        'json_filename',
        'zip_filename',
    ];

    protected $casts = [
        'last_data_upload_at' => 'datetime',
    ];

    public function monitoringData(): HasMany
    {
        return $this->hasMany(MonitoringData::class);
    }

    public function pjMappings(): HasMany
    {
        return $this->hasMany(PjMapping::class);
    }

    public function uploadHistories(): HasMany
    {
        return $this->hasMany(UploadHistory::class);
    }

    // Aggregations
    public function getTotalTargetAttribute(): int
    {
        return $this->monitoringData()->sum('target');
    }

    public function getRegenciesAttribute(): array
    {
        return $this->monitoringData()
            ->distinct('regency_code')
            ->pluck('regency_code')
            ->toArray();
    }
}
```

### Service Example

```php
// app/Services/DashboardService.php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getRegencyMetrics(Activity $activity): Collection
    {
        return Cache::remember(
            "dashboard.regency.{$activity->id}",
            now()->addMinutes(5),
            fn() => $this->calculateRegencyMetrics($activity)
        );
    }

    protected function calculateRegencyMetrics(Activity $activity): Collection
    {
        return $activity->monitoringData()
            ->selectRaw('
                regency_code,
                SUM(target) as total_target,
                SUM(open) as total_open,
                SUM(submitted) as total_submitted,
                SUM(approved) as total_approved,
                SUM(rejected) as total_rejected
            ')
            ->groupBy('regency_code')
            ->get()
            ->map(function ($regency) {
                $total = $regency->total_target;
                return [
                    'code' => $regency->regency_code,
                    'metrics' => [
                        'target' => $regency->total_target,
                        'open' => $regency->total_open,
                        'submitted' => $regency->total_submitted,
                        'approved' => $regency->total_approved,
                        'rejected' => $regency->total_rejected,
                    ],
                    'percentages' => [
                        'open' => $total > 0 ? ($regency->total_open / $total) * 100 : 0,
                        'submitted' => $total > 0 ? ($regency->total_submitted / $total) * 100 : 0,
                        'approved' => $total > 0 ? ($regency->total_approved / $total) * 100 : 0,
                        'rejected' => $total > 0 ? ($regency->total_rejected / $total) * 100 : 0,
                    ],
                ];
            });
    }
}
```

### Controller Example

```php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        // Get active activity from session or default
        $activityId = $request->session()->get('active_activity_id', 1);
        $activity = Activity::findOrFail($activityId);

        // Get all activities for selector
        $activities = Activity::orderBy('display_name')->get();

        // Get dashboard data
        $regencyMetrics = $this->dashboardService->getRegencyMetrics($activity);

        return view('dashboard.index', [
            'activity' => $activity,
            'activities' => $activities,
            'regencyMetrics' => $regencyMetrics,
        ]);
    }
}
```

---

## Appendix B: CSV Format Examples

### Format 1 (Old)
```csv
Desa,Target,Open,Submitted by PPL,Approved by PML,Rejected by PML
[9702010003] WAMENA KOTA,10,5,3,2,0
```

### Format 2 (New)
```csv
village_code,village_name,target,open,submitted,approved,rejected
9702010003,WAMENA KOTA,10,5,3,2,0
```

### JSON Format (PJ Mapping)
```json
{
  "9702010003": {
    "code": "PJ001",
    "name": "John Doe"
  },
  "9702010004": {
    "code": "PJ002",
    "name": "Jane Smith"
  }
}
```

---

**End of Document**
