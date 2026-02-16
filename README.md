# Dashboard Monitoring BPS - Laravel Implementation

Dashboard monitoring untuk BPS Kabupaten Jayawijaya dengan SSO BPS authentication.

## ✨ Features

- ✅ **Public Dashboard** - Semua orang bisa lihat monitoring tanpa login
- ✅ **SSO BPS Authentication** - Login untuk upload data
- ✅ **Multi-Activity Support** - Kelola multiple kegiatan
- ✅ **Dashboard Per Kabupaten** - Visualisasi data 3 kabupaten (9702, 9705, 9706)
- ✅ **Metrics Tracking** - Target, Open, Submitted, Approved, Rejected
- ✅ **Interactive Charts** - Stacked bar chart dengan Chart.js
- ✅ **Responsive Design** - Tailwind CSS

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+
- MySQL (MAMP di port 8889)
- Composer

### Installation

```bash
# 1. Clone & Install
git clone <repo-url>
cd monitoring
composer install

# 2. Setup Environment
cp .env.example .env
php artisan key:generate

# 3. Configure Database (.env sudah di-setup)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=8889
DB_DATABASE=monitoring
DB_USERNAME=root
DB_PASSWORD=root
DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock

# 4. Run Migrations
php artisan migrate

# 5. Seed Demo Data
php artisan db:seed --class=DemoDataSeeder

# 6. Run Server
php artisan serve
```

Visit: http://localhost:8000

## 📊 Demo Data

Setelah seeding, Anda akan memiliki:
- 1 Activity: "Monitoring Dashboard 2024"
- 13 Villages dengan data monitoring
- 3 Kabupaten: Jayawijaya, Mamberamo Tengah, Yalimo

## 🔐 SSO Authentication

### Configuration (.env sudah lengkap)

```env
SSO_BASE_URL=https://sso.bps9702.com/v1/
SSO_DATA_URL=https://sso.bps9702.com/api/v1/
SSO_CLIENT_ID=advjqihy
SSO_CLIENT_SECRET=Y2fJD6T1ycJSbheLFYHaNMsDZhFTkWBG
SSO_AUTHORIZE_URL=https://sso.bps9702.com/api/v1/authorize
SSO_TOKEN_URL=https://sso.bps9702.com/api/v1/token
SSO_CALLBACK_URL=http://localhost:8000/auth/callback
```

### Login Flow

1. Klik **Login** di navigation bar
2. Redirect ke SSO BPS
3. Login dengan credentials BPS
4. Redirect kembali ke dashboard
5. Sekarang bisa akses fitur upload data

### Roles & Permissions

- **Admin/Superadmin** - Bisa manage activities & upload data
- **Editor/PPL/PML** - Bisa upload data
- **User/Viewer** - Hanya bisa lihat dashboard (sama seperti publik)

## 📁 Struktur Project

```
monitoring/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/SsoAuthController.php
│   │   ├── DashboardController.php
│   │   └── ActivityController.php
│   ├── Models/
│   │   ├── Activity.php
│   │   ├── MonitoringData.php
│   │   ├── PjMapping.php
│   │   ├── UploadHistory.php
│   │   └── User.php
│   └── Services/
│       └── Sso/SsoService.php
├── config/
│   └── sso.php
├── database/
│   ├── migrations/
│   │   ├── 2026_02_16_055029_add_sso_fields_to_users_table.php
│   │   ├── 2026_02_16_055029_create_activities_table.php
│   │   ├── 2026_02_16_055030_create_monitoring_data_table.php
│   │   ├── 2026_02_16_055030_create_pj_mappings_table.php
│   │   └── 2026_02_16_055030_create_upload_histories_table.php
│   └── seeders/
│       └── DemoDataSeeder.php
├── resources/views/
│   ├── layouts/
│   │   └── app.blade.php
│   └── dashboard/
│       └── index.blade.php
└── routes/
    └── web.php
```

## 🗄️ Database Schema

### Activities
- id, name, display_name, description
- last_data_upload_at, json_filename, zip_filename

### Monitoring Data
- id, activity_id, village_code, village_name, regency_code
- target, open, submitted, approved, rejected
- pj_code, pj_name

### Users (with SSO fields)
- id, name, email, password (nullable)
- sso_user_id, nip_9, nip_18, sso_roles
- last_sso_sync_at

## 📖 API Routes

### Public Routes
- `GET /` - Dashboard (public access)

### Auth Routes
- `GET /auth/login` - Redirect to SSO
- `GET /auth/callback` - SSO callback
- `POST /auth/logout` - Logout

### Protected Routes (require auth)
- `Resource /activities` - CRUD activities
- `POST /activities/{id}/set-active` - Set active activity

## 🎯 Next Steps

### Phase 1: Current (MVP) ✅
- [x] Database setup
- [x] SSO authentication
- [x] Public dashboard view
- [x] Basic metrics & charts

### Phase 2: Upload & Import
- [ ] Import JSON (PJ mapping)
- [ ] Import CSV (monitoring data)
- [ ] Import ZIP (batch upload)
- [ ] Upload history tracking
- [ ] Async job processing

### Phase 3: Advanced Dashboard
- [ ] Dashboard per PJ
- [ ] Detail per desa
- [ ] Search & filter
- [ ] Export Excel

### Phase 4: Activity Management
- [ ] CRUD activities (UI)
- [ ] Switch between activities
- [ ] Activity selector dropdown

## 📝 Development Notes

### Tailwind CSS
Menggunakan CDN untuk development. Untuk production, gunakan Vite build:

```bash
npm install
npm run build
```

Kemudian ubah di `layouts/app.blade.php`:

```blade
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Testing SSO
Untuk testing tanpa SSO production, comment middleware auth di routes:

```php
// Route::middleware(['auth'])->group(function () {
    // Routes here
// });
```

### Adding More Data
Edit `DemoDataSeeder.php` dan tambahkan data sesuai kebutuhan, lalu:

```bash
php artisan migrate:fresh --seed
```

## 🐛 Troubleshooting

### Database Connection Error
```bash
# Pastikan MAMP MySQL running di port 8889
# Cek di MAMP preferences
```

### SSO Error
```bash
# Cek .env SSO configuration
# Pastikan callback URL terdaftar di SSO BPS
```

### Vite Manifest Error
```bash
# Gunakan Tailwind CDN (sudah di-setup)
# Atau run: npm install && npm run build
```

## 📚 Documentation

- [IMPLEMENTASI_SSO_BPS.md](IMPLEMENTASI_SSO_BPS.md) - SSO integration guide
- [RANCANGAN_IMPLEMENTASI_LARAVEL.md](RANCANGAN_IMPLEMENTASI_LARAVEL.md) - Architecture & planning

## 🤝 Contributing

1. Fork the project
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## 📄 License

MIT License

## 👥 Team

BPS Kabupaten Jayawijaya

---

**Status**: MVP Ready ✅
**Version**: 1.0.0
**Last Updated**: 2026-02-16
