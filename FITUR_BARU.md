# 🎉 Fitur Baru: Multi-Kegiatan

## Apa yang Baru?

Aplikasi sekarang mendukung **multiple kegiatan** dalam satu database! Anda bisa:

✅ Membuat kegiatan baru (contoh: Monitoring, Survey BDT, PODES, dll)
✅ Switch antar kegiatan dengan mudah
✅ Setiap kegiatan punya data JSON & ZIP sendiri
✅ Upload file baru hanya timpa data kegiatan yang aktif
✅ Auto-track waktu last update per kegiatan
✅ Hapus kegiatan yang tidak diperlukan

---

## Cara Menggunakan

### 1️⃣ Membuat Kegiatan Baru

**Langkah:**
1. Buka sidebar
2. Klik expander **"➕ Tambah Kegiatan Baru"**
3. Ketik nama kegiatan (contoh: "Survey BDT 2024")
4. Klik tombol **"Buat Kegiatan"**

✅ Kegiatan baru langsung aktif dan siap digunakan!

---

### 2️⃣ Memilih Kegiatan Aktif

**Langkah:**
1. Di bagian atas sidebar, lihat dropdown **"Pilih Kegiatan"**
2. Klik dropdown dan pilih kegiatan yang ingin dilihat
3. Dashboard otomatis update

💡 **Tip**: Data yang ditampilkan di dashboard akan berubah sesuai kegiatan terpilih

---

### 3️⃣ Upload Data per Kegiatan

**Langkah:**
1. Pilih kegiatan yang ingin di-update
2. Scroll ke bawah di sidebar ke bagian **"📁 Upload Files"**
3. Upload JSON (mapping PJ) atau ZIP (data CSV)
4. File akan tersimpan **hanya untuk kegiatan yang aktif**

⚠️ **Penting**: Upload file baru akan **menimpa data lama** untuk kegiatan tersebut (kegiatan lain tidak terpengaruh)

---

### 4️⃣ Melihat Info Kegiatan

Di sidebar, Anda akan melihat info kegiatan aktif:

```
📊 Info Kegiatan
- Last update: 2024-02-15 14:30
- Data monitoring: 55 records
- Data PJ: 55 records
```

---

### 5️⃣ Menghapus Kegiatan

**Langkah:**
1. Pilih kegiatan yang ingin dihapus
2. Klik expander **"🗑️ Hapus Kegiatan"**
3. Baca peringatan dengan seksama
4. Centang checkbox **"Ya, saya yakin ingin menghapus"**
5. Klik tombol **"Hapus Kegiatan Ini"**

⚠️ **PERHATIAN**: Tindakan ini akan menghapus **semua data** kegiatan tersebut secara permanen!

---

## Contoh Use Case

### Skenario 1: Monitoring Rutin + Survey Tahunan

1. Kegiatan "Monitoring Dashboard" → untuk monitoring harian/mingguan
2. Kegiatan "Survey BDT 2024" → untuk survey tahunan
3. Switch antar kegiatan sesuai kebutuhan
4. Data tidak tercampur, tetap terorganisir

### Skenario 2: Multiple Kabupaten/Wilayah

1. Kegiatan "Monitoring Jayawijaya"
2. Kegiatan "Monitoring Mambramo Tengah"
3. Kegiatan "Monitoring Yalimo"
4. Setiap kabupaten punya tracking terpisah

### Skenario 3: Historical Data

1. Kegiatan "Monitoring 2023"
2. Kegiatan "Monitoring 2024"
3. Kegiatan "Monitoring 2025"
4. Bisa compare data antar tahun dengan switch kegiatan

---

## FAQ

**Q: Apakah data lama saya hilang?**
A: Tidak! Data lama otomatis migrate ke kegiatan "Monitoring Dashboard" (ID 1). Backup juga tersedia di `monitoring_data.db.backup`.

**Q: Berapa banyak kegiatan yang bisa dibuat?**
A: Tidak ada limit. Buat sebanyak yang Anda butuhkan.

**Q: Apakah upload file di satu kegiatan akan mempengaruhi kegiatan lain?**
A: Tidak! Setiap kegiatan punya data terpisah. Upload hanya mempengaruhi kegiatan yang aktif.

**Q: Bagaimana cara restore data jika terhapus?**
A: Gunakan backup database di `monitoring_data.db.backup`. Copy file tersebut ke `monitoring_data.db`.

**Q: Apakah dashboard tetap sama?**
A: Ya! Tab-tab dashboard (Per Kabupaten, Per PJ, Detail Desa, Export) tetap sama. Yang berubah hanya data yang ditampilkan sesuai kegiatan aktif.

---

## Technical Details

### Database Schema

```sql
-- Master table (baru)
CREATE TABLE activities (
    id INTEGER PRIMARY KEY,
    name TEXT UNIQUE,              -- slug kegiatan
    display_name TEXT,             -- nama yang tampil di UI
    created_at TIMESTAMP,
    last_updated TIMESTAMP,        -- auto-update saat upload
    json_filename TEXT,            -- nama file JSON terakhir
    zip_filename TEXT              -- nama file ZIP terakhir
);

-- Data tables (per kegiatan)
CREATE TABLE monitoring_data_1 (...);  -- untuk activity ID 1
CREATE TABLE monitoring_data_2 (...);  -- untuk activity ID 2
CREATE TABLE pj_mapping_1 (...);
CREATE TABLE pj_mapping_2 (...);
```

### Migration

Saat pertama kali run aplikasi dengan fitur baru:
1. Tabel `activities` dibuat otomatis
2. Kegiatan default "Monitoring Dashboard" dibuat dengan ID 1
3. Tabel lama `monitoring_data` → `monitoring_data_1`
4. Tabel lama `pj_mapping` → `pj_mapping_1`

✅ Semua data Anda aman dan tetap tersedia!

---

## Butuh Bantuan?

Jika ada pertanyaan atau menemui masalah:
1. Periksa `CHANGELOG.md` untuk detail perubahan
2. Baca `README.md` untuk dokumentasi lengkap
3. Lihat backup database di `monitoring_data.db.backup`

Selamat menggunakan fitur baru! 🎉
