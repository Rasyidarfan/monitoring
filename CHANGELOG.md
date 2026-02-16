# Changelog

## [v2.0.0] - 2026-02-15

### 🎉 Major Features: Multi-Kegiatan Support

#### Added
- **Multi-Kegiatan Management**: Aplikasi sekarang mendukung multiple kegiatan dalam satu database
  - Buat kegiatan baru melalui UI
  - Switch antar kegiatan dengan dropdown selector
  - Hapus kegiatan (dengan konfirmasi)

- **Activity Tracking**: Setiap kegiatan memiliki metadata sendiri
  - Last updated timestamp (otomatis update saat upload file)
  - Nama file JSON & ZIP yang terakhir di-upload
  - Jumlah records (data monitoring & PJ mapping)

- **Data Isolation**: Data setiap kegiatan tersimpan terpisah
  - Table schema: `monitoring_data_{activity_id}`, `pj_mapping_{activity_id}`
  - Upload file hanya affect kegiatan yang aktif
  - Dashboard menampilkan data sesuai kegiatan terpilih

#### Changed
- **Database Schema**:
  - Added `activities` master table
  - Old tables migrated to activity-specific tables (e.g., `monitoring_data_1`)
  - Backward compatible: Data lama otomatis migrate ke "Monitoring Dashboard"

- **UI Improvements**:
  - Sidebar reorganized: Activity management di atas, file upload di bawah
  - Info kegiatan menampilkan statistik real-time
  - Warning jika belum ada kegiatan

- **Functions Signature**:
  - `save_data_to_db(df, table_name, activity_id)` - tambah parameter activity_id
  - `load_data_from_db(table_name, activity_id)` - tambah parameter activity_id

#### Database Structure

```sql
-- Master table
CREATE TABLE activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,           -- slug: monitoring, survey-bdt
    display_name TEXT NOT NULL,          -- "Monitoring Dashboard"
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    json_filename TEXT,
    zip_filename TEXT
);

-- Per-activity tables (dynamic)
CREATE TABLE monitoring_data_{activity_id} (...);
CREATE TABLE pj_mapping_{activity_id} (...);
```

#### Migration Notes
- ✅ Data lama otomatis migrate saat pertama kali run
- ✅ Tabel `monitoring_data` dan `pj_mapping` lama direname ke `monitoring_data_1` dan `pj_mapping_1`
- ✅ Created default activity "Monitoring Dashboard" dengan ID 1
- ✅ Backup database tersedia di `monitoring_data.db.backup`

---

## [v1.0.0] - 2024-02-13

### Initial Release
- Dashboard monitoring per kabupaten (9702, 9705, 9706)
- Dashboard monitoring per PJ
- Detail per desa dengan search & filter
- Export to Excel
- SQLite persistence
- Multi-format CSV support (old & new format)
