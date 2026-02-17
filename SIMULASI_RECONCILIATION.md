# Simulasi Reconciliation Data: Target Real vs File Upload

## Konteks Problem

**Issue:** File CSV/ZIP kadang memberikan nilai target yang kurang atau bahkan 0, karena assignment bypass tahap "Open" dan langsung ke "Submitted" atau "Approved".

**Contoh Nyata:**
```
Assignment sebenarnya = 10 forms
- File CSV menunjukkan target = 2 (hanya 2 form yang terinput)
- Status: open=0, submit=2, approve=0

Permasalahan:
- Target di file = 2, tapi target real = 10
- Dari 10 assignments, 2 berhasil submit, 8 belum dimulai (tidak terbaca di file)
- Metrik total = open=0 + submit=2 + approve=0 = 2 (kurang dari 10)
```

**Solusi:** Pisahkan `target` dari metrics. Target disimpan terpisah di `pj_mappings` dan tidak berubah kecuali di-edit manual atau di-update dengan nilai yang lebih besar.

---

## Data Structure Baru

### Tabel `pj_mappings` - Menyimpan Target REAL
```
id | activity_id | village_code | desa_nama    | pj_name | pj_code | target (REAL)
---|-------------|--------------|--------------|---------|---------|---------------
1  | 1           | 9705010005   | Kobakma      | Athiya  | PJ-ATH  | 10 ← TARGET REAL
2  | 1           | 9705010006   | Ilugwa       | Athiya  | PJ-ATH  | 15 ← TARGET REAL
```

### Tabel `monitoring_data` - Hanya Metrics (tanpa target)
```
id | activity_id | village_code | village_name | open | submitted | approved | rejected
---|-------------|--------------|--------------|------|-----------|----------|----------
1  | 1           | 9705010005   | Kobakma      | 0    | 2         | 0        | 0        ← Hanya metrics
2  | 1           | 9705010006   | Ilugwa       | 0    | 2         | 2        | 0        ← Hanya metrics
```

### Dashboard Query (JOIN)
```sql
SELECT
  md.village_code,
  md.village_name,
  pj.target as target_real,
  md.open,
  md.submitted,
  md.approved,
  md.rejected,
  pj.desa_nama,
  pj.pj_name,
  -- Hitung sisa yang belum ter-assign
  (pj.target - (md.open + md.submitted + md.approved + md.rejected)) as not_assigned
FROM monitoring_data md
LEFT JOIN pj_mappings pj ON md.activity_id = pj.activity_id
                           AND md.village_code = pj.village_code
```

Hasil:
```
village_code | target_real | open | submitted | approved | rejected | not_assigned
-------------|-------------|------|-----------|----------|----------|---------------
9705010005   | 10          | 0    | 2         | 0        | 0        | 8
9705010006   | 15          | 0    | 2         | 2        | 0        | 11
```

---

## Scenario: Update dengan Reconciliation Logic

### State 1: Upload Pertama (2026-02-16)

**File CSV upload:**
```
village_code | desa_nama | target | open | submitted | approved | rejected
-------------|-----------|--------|------|-----------|----------|----------
9705010005   | Kobakma   | 2      | 0    | 2         | 0        | 0
9705010006   | Ilugwa    | 4      | 0    | 2         | 2        | 0
```

**Proses Upload (Mode REPLACE):**

1. Delete all monitoring_data dan pj_mappings untuk activity_id = 1
2. Insert ke monitoring_data dengan metrics dari file:
```sql
INSERT INTO monitoring_data
(activity_id, village_code, village_name, open, submitted, approved, rejected)
VALUES
(1, '9705010005', 'Kobakma', 0, 2, 0, 0),
(1, '9705010006', 'Ilugwa', 0, 2, 2, 0)
```

3. **KEY LOGIC: Insert ke pj_mappings dengan target = MAX(database_lama, file_baru)**

**Database Lama (jika ada):**
```
9705010005: target = 10 (sudah ada history)
9705010006: target = 15 (sudah ada history)
```

**File Baru:**
```
9705010005: target = 2
9705010006: target = 4
```

**Apply MAX Logic:**
```
9705010005: target = MAX(10, 2) = 10 ✓ (gunakan yang lebih besar)
9705010006: target = MAX(15, 4) = 15 ✓ (gunakan yang lebih besar)
```

```sql
INSERT INTO pj_mappings
(activity_id, village_code, desa_nama, pj_code, pj_name, target)
VALUES
(1, '9705010005', 'Kobakma', NULL, NULL, 10),   ← MAX(10, 2) = 10
(1, '9705010006', 'Ilugwa', NULL, NULL, 15)     ← MAX(15, 4) = 15
```

**Hasil State 1:**
```
pj_mappings:
village_code | desa_nama | target
-------------|-----------|--------
9705010005   | Kobakma   | 10    ← Disimpan target REAL
9705010006   | Ilugwa    | 15    ← Disimpan target REAL

monitoring_data:
village_code | open | submitted | approved | rejected
-------------|------|-----------|----------|----------
9705010005   | 0    | 2         | 0        | 0        ← Hanya metrics dari file
9705010006   | 0    | 2         | 2        | 0        ← Hanya metrics dari file

Dashboard View (JOIN):
9705010005: target=10, open=0, submit=2, appr=0, not_assigned=8
9705010006: target=15, open=0, submit=2, appr=2, not_assigned=11
```

---

### State 2: Update Kedua (2026-02-17)

**File CSV upload baru:**
```
village_code | desa_nama | target | open | submitted | approved | rejected
-------------|-----------|--------|------|-----------|----------|----------
9705010005   | Kobakma   | 4      | 0    | 2         | 2        | 0
9705010006   | Ilugwa    | 6      | 0    | 3         | 3        | 0
```

**Proses Upload (Mode REPLACE):**

1. Delete all monitoring_data dan pj_mappings untuk activity_id = 1
2. Cek database lama:
```
9705010005: target = 10
9705010006: target = 15
```

3. Insert ke monitoring_data dengan metrics BARU:
```sql
INSERT INTO monitoring_data
(activity_id, village_code, village_name, open, submitted, approved, rejected)
VALUES
(1, '9705010005', 'Kobakma', 0, 2, 2, 0),   ← BARU dari file
(1, '9705010006', 'Ilugwa', 0, 3, 3, 0)     ← BARU dari file
```

4. **Apply MAX Logic lagi:**
```
9705010005: target = MAX(10, 4) = 10 ✓ (tetap 10, tidak berubah)
9705010006: target = MAX(15, 6) = 15 ✓ (tetap 15, tidak berubah)
```

```sql
INSERT INTO pj_mappings
(activity_id, village_code, desa_nama, pj_code, pj_name, target)
VALUES
(1, '9705010005', 'Kobakma', NULL, NULL, 10),   ← MAX(10, 4) = 10
(1, '9705010006', 'Ilugwa', NULL, NULL, 15)     ← MAX(15, 6) = 15
```

**Hasil State 2:**
```
pj_mappings:
village_code | desa_nama | target
-------------|-----------|--------
9705010005   | Kobakma   | 10    ← TETAP 10 (tidak berubah)
9705010006   | Ilugwa    | 15    ← TETAP 15 (tidak berubah)

monitoring_data:
village_code | open | submitted | approved | rejected
-------------|------|-----------|----------|----------
9705010005   | 0    | 2         | 2        | 0        ← UPDATE metrics baru
9705010006   | 0    | 3         | 3        | 0        ← UPDATE metrics baru

Dashboard View (JOIN):
9705010005: target=10, open=0, submit=2, appr=2, not_assigned=6
9705010006: target=15, open=0, submit=3, appr=3, not_assigned=9
```

---

### State 3: Update dengan Target Lebih Besar

**File CSV upload (adjustment dari admin):**
```
village_code | desa_nama | target | open | submitted | approved | rejected
-------------|-----------|--------|------|-----------|----------|----------
9705010005   | Kobakma   | 12     | 0    | 2         | 2        | 0        ← TARGET BARU 12
9705010006   | Ilugwa    | 20     | 0    | 3         | 3        | 0        ← TARGET BARU 20
```

**Proses Upload:**

1. Cek database lama:
```
9705010005: target = 10
9705010006: target = 15
```

2. **Apply MAX Logic:**
```
9705010005: target = MAX(10, 12) = 12 ✓ (NAIK ke 12)
9705010006: target = MAX(15, 20) = 20 ✓ (NAIK ke 20)
```

```sql
INSERT INTO pj_mappings
(activity_id, village_code, desa_nama, pj_code, pj_name, target)
VALUES
(1, '9705010005', 'Kobakma', NULL, NULL, 12),   ← MAX(10, 12) = 12
(1, '9705010006', 'Ilugwa', NULL, NULL, 20)     ← MAX(15, 20) = 20
```

**Hasil State 3:**
```
pj_mappings:
village_code | desa_nama | target
-------------|-----------|--------
9705010005   | Kobakma   | 12    ← NAIK dari 10 ke 12
9705010006   | Ilugwa    | 20    ← NAIK dari 15 ke 20

monitoring_data:
village_code | open | submitted | approved | rejected
-------------|------|-----------|----------|----------
9705010005   | 0    | 2         | 2        | 0        ← Same metrics
9705010006   | 0    | 3         | 3        | 0        ← Same metrics

Dashboard View (JOIN):
9705010005: target=12, open=0, submit=2, appr=2, not_assigned=8
9705010006: target=20, open=0, submit=3, appr=3, not_assigned=14
```

---

### State 4: Edit Manual via Admin

Admin ingin mengubah target untuk 9705010005 dari 12 menjadi 15:

**Route: POST /admin/kegiatan/1/edit-pj**

```
Request body:
{
  "pj_mapping_id": 1,
  "pj_name": "Athiya",
  "desa_nama": "Kobakma",
  "target": 15
}
```

**Proses:**
```sql
UPDATE pj_mappings
SET pj_name = 'Athiya',
    desa_nama = 'Kobakma',
    target = 15,
    updated_at = NOW()
WHERE id = 1 AND activity_id = 1
```

**Hasil State 4:**
```
pj_mappings:
village_code | desa_nama | target | updated_at
-------------|-----------|--------|----------
9705010005   | Kobakma   | 15     | 2026-02-17 10:30:00 ← UBAH manual
9705010006   | Ilugwa    | 20     | 2026-02-17 09:00:00

Dashboard View (JOIN):
9705010005: target=15, open=0, submit=2, appr=2, not_assigned=11
9705010006: target=20, open=0, submit=3, appr=3, not_assigned=14
```

---

## Perbandingan: Tanpa vs Dengan Reconciliation

### TANPA Reconciliation (Struktur Lama - MASALAH)
```
File CSV: 9705010005, target=2, open=0, submit=2, appr=0
monitoring_data: target=2, open=0, submit=2, appr=0
TOTAL METRICS = 2 (SALAH! Seharusnya 10)

Masalah:
- Target berubah setiap upload
- Metrics tidak konsisten
- Tidak bisa tracking "assignment tidak ter-assign"
```

### DENGAN Reconciliation (Struktur Baru - BENAR)
```
File CSV: 9705010005, target_file=2, open=0, submit=2, appr=0
pj_mappings: target=MAX(lama, 2) = 10
monitoring_data: open=0, submit=2, appr=0
TOTAL ASSIGNMENT = 10 (BENAR!)
NOT ASSIGNED = 10 - 2 = 8

Keuntungan:
- Target tetap konsisten
- Hanya metrics yang berubah
- Bisa tracking "assignment tidak ter-assign"
- Admin bisa adjust target manual jika diperlukan
```

---

## Perubahan Database Schema

### Tabel `monitoring_data` (Perubahan)
```sql
-- SEBELUM (ada target)
CREATE TABLE monitoring_data (
  id BIGINT PRIMARY KEY,
  activity_id BIGINT,
  village_code VARCHAR(50),
  village_name VARCHAR(255),
  target INTEGER DEFAULT 0,        ← DIHAPUS
  open INTEGER DEFAULT 0,
  submitted INTEGER DEFAULT 0,
  approved INTEGER DEFAULT 0,
  rejected INTEGER DEFAULT 0
);

-- SESUDAH (hanya metrics, target di pj_mappings)
CREATE TABLE monitoring_data (
  id BIGINT PRIMARY KEY,
  activity_id BIGINT,
  village_code VARCHAR(50),
  village_name VARCHAR(255),
  open INTEGER DEFAULT 0,
  submitted INTEGER DEFAULT 0,
  approved INTEGER DEFAULT 0,
  rejected INTEGER DEFAULT 0
);
```

### Tabel `pj_mappings` (Perubahan)
```sql
-- SEBELUM (hanya PJ mapping)
CREATE TABLE pj_mappings (
  id BIGINT PRIMARY KEY,
  activity_id BIGINT,
  village_code VARCHAR(50),
  pj_code VARCHAR(50),
  pj_name VARCHAR(255)
);

-- SESUDAH (ditambah desa_nama dan target)
CREATE TABLE pj_mappings (
  id BIGINT PRIMARY KEY,
  activity_id BIGINT,
  village_code VARCHAR(50),
  desa_nama VARCHAR(255),          ← DITAMBAH
  pj_code VARCHAR(50),
  pj_name VARCHAR(255),
  target INTEGER DEFAULT 0,        ← DITAMBAH (dari monitoring_data)
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

---

## Algorithm Pseudocode untuk Upload CSV/ZIP

```python
def upload_csv(activity_id, csv_file):
    # Step 1: Parse CSV
    csv_data = parse_csv(csv_file)  # List of {village_code, desa_nama, target, open, submit, appr, reject}

    # Step 2: Get old target from database
    old_targets = {}
    for pj in PjMapping.where(activity_id=activity_id):
        old_targets[pj.village_code] = pj.target

    # Step 3: Begin transaction
    transaction.start()
    try:
        # Step 4: Delete all old data for this activity
        MonitoringData.where(activity_id=activity_id).delete()
        PjMapping.where(activity_id=activity_id).delete()

        # Step 5: Insert new data with MAX logic
        for row in csv_data:
            village_code = row['village_code']
            old_target = old_targets.get(village_code, 0)
            new_target = row['target']

            # Apply MAX logic: gunakan nilai terbesar
            final_target = max(old_target, new_target)

            # Insert ke monitoring_data (TANPA target)
            MonitoringData.create(
                activity_id=activity_id,
                village_code=village_code,
                village_name=row['desa_nama'],
                open=row['open'],
                submitted=row['submitted'],
                approved=row['approved'],
                rejected=row['rejected']
            )

            # Insert ke pj_mappings (DENGAN target reconciliation)
            PjMapping.create(
                activity_id=activity_id,
                village_code=village_code,
                desa_nama=row['desa_nama'],
                pj_code=None,  # Dari JSON jika ada
                pj_name=None,  # Dari JSON jika ada
                target=final_target  # ← KEY: MAX(old, new)
            )

        transaction.commit()
        return { 'status': 'success', 'records': len(csv_data) }

    except Exception as e:
        transaction.rollback()
        return { 'status': 'error', 'message': str(e) }
```

---

## Algorithm Pseudocode untuk Upload JSON (Sync Mode)

```python
def upload_json(activity_id, json_file):
    # Step 1: Parse JSON
    json_data = parse_json(json_file)  # List of {Id, PJ, ...}

    # Step 2: Get old data from database
    old_mappings = {}
    for pj in PjMapping.where(activity_id=activity_id):
        old_mappings[pj.village_code] = {
            'target': pj.target,
            'pj_code': pj.pj_code,
            'dj_id': pj.id
        }

    # Step 3: Get desa names from monitoring_data
    desa_names = {}
    for md in MonitoringData.where(activity_id=activity_id):
        desa_names[md.village_code] = md.village_name

    # Step 4: Begin transaction
    transaction.start()
    try:
        json_villages = set()

        # Step 5: Process each JSON entry (INSERT or UPDATE)
        for row in json_data:
            village_code = row['Id']
            pj_name = row['PJ']
            json_villages.add(village_code)

            desa_nama = desa_names.get(village_code, '')  # Get from monitoring_data
            old_data = old_mappings.get(village_code)

            if old_data:
                # UPDATE existing
                old_target = old_data['target']
                PjMapping.where(id=old_data['pj_id']).update({
                    'pj_name': pj_name,
                    'desa_nama': desa_nama,
                    'updated_at': NOW()
                    # target TIDAK berubah dari JSON
                })
            else:
                # INSERT baru
                PjMapping.create(
                    activity_id=activity_id,
                    village_code=village_code,
                    desa_nama=desa_nama,
                    pj_code=None,
                    pj_name=pj_name,
                    target=0  # Default 0 untuk entry baru
                )

        # Step 6: DELETE entries yang di database tapi tidak di JSON
        for village_code, old_data in old_mappings.items():
            if village_code not in json_villages:
                PjMapping.where(id=old_data['pj_id']).delete()

        transaction.commit()
        return { 'status': 'success', 'processed': len(json_data) }

    except Exception as e:
        transaction.rollback()
        return { 'status': 'error', 'message': str(e) }
```

---

## Summary

### Tujuan Perubahan:
1. ✅ **Pisahkan target dari metrics** - Target di `pj_mappings`, metrics di `monitoring_data`
2. ✅ **Reconciliation logic** - Gunakan MAX(old_target, new_target) saat upload
3. ✅ **Tracking assignment tidak ter-assign** - `not_assigned = target - total_metrics`
4. ✅ **Admin dapat adjust manual** - Edit target di `/admin/kegiatan/{id}/edit`
5. ✅ **Konsistensi data** - Target tetap sesuai reality, tidak berubah setiap upload

### Key Points:
- `pj_mappings.target` = Target REAL (reconciled)
- `monitoring_data.open/submit/appr/reject` = Metrics dari file
- `monitoring_data` TIDAK ada kolom target
- MAX logic diterapkan saat upload CSV/ZIP
- JSON upload tidak mengubah target
- Admin edit hanya touch `pj_mappings`, bukan `monitoring_data`

