# GL Reporting Talenta → SAP

Sistem otomasi ekstraksi data **General Ledger (GL)** dari **Talenta (Mekari) API** dan konversi ke format **Excel SAP 20 kolom** (.xlsx), lengkap dengan validasi, post-processing "Fill Text", dan manajemen mapping akun.

---

## Daftar Isi

- [Arsitektur Sistem](#arsitektur-sistem)
- [Tech Stack](#tech-stack)
- [Struktur Database](#struktur-database)
- [Alur Sistem / Flowchart](#alur-sistem--flowchart)
- [Extraction Strategy (Logika Python)](#extraction-strategy-logika-python)
- [Halaman & Fungsionalitas Web](#halaman--fungsionalitas-web)
- [Services (Laravel)](#services-laravel)
- [Models (Eloquent ORM)](#models-eloquent-orm)
- [TestFillTextController](#testfilltextcontroller-baru)
- [Cara Install & Run](#cara-install--run)
- [API Endpoints (Python)](#api-endpoints-python)
- [Format File Excel Output (20 Kolom SAP)](#format-file-excel-output-20-kolom-sap)
- [Catatan Penting](#catatan-penting)

---

## Arsitektur Sistem

```
┌──────────────────────────────────────────────────────────────────┐
│                       BROWSER (User)                             │
└──────────────┬───────────────────────────────────────┬───────────┘
               │                                       │
               ▼                                       ▼
┌──────────────────────────────┐        ┌──────────────────────────────┐
│   LARAVEL 12 (PHP 8.2+)      │        │   LARAVEL 12 (PHP 8.2+)      │
│   Web UI + Controllers       │        │   Services Layer             │
│   - Dashboard                │        │   - PythonServiceClient      │
│   - Run Extraction           │─────▶ │    - FillTextService          │
│   - Mapping Editor           │        │   - ValidatorService         │
│   - Validator                │        │   - ResetCenterService       │
│   - Fill Text                │        └───────────┬──────────────────┘
│   - Text References          │                    │
│   - Reset Center             │                    │ HTTP POST /run
│   - Help                     │                    │
└──────────────────────────────┘                    ▼
                                        ┌───────────────────┐
                                        │  PYTHON (Flask)   │
                                        │  Port 8091/8092   │
                                        │                   │
                                        │  HMAC-SHA256 Auth │
                                        │  ➔ Talenta API   │
                                        │                   │
                                        │  Strategi A/B/C   │
                                        │  (Semarang)       │
                                        │  Strategi B/D/E/F │
                                        │  (Surabaya)       │
                                        │                   │
                                        │  Generate .xlsx   │
                                        └────────┬──────────┘
                                                 │
                                                 ▼
                                        ┌───────────────────┐
                                        │  storage/app/     │
                                        │  gl_outputs/      │
                                        │  gl_filled/       │
                                        │  gl_references/   │
                                        │  gl_uploads/      │
                                        └───────────────────┘
```

### Komponen Utama

| Komponen | Teknologi | Port | Tugas |
|----------|-----------|------|-------|
| Laravel App | PHP 8.2, Laravel 12 | 80/8000 | Web UI, routing, orkestrasi |
| Python Service Semarang | Flask, Python 3 | 8091 | Ekstraksi strategi A, B, C |
| Python Service Surabaya | Flask, Python 3 | 8092 | Ekstraksi strategi B, D, E, F |
| Database | SQLite (default) / MySQL / PostgreSQL | - | Menyimpan semua data |

### Python Services → Talenta API

Kedua Python service menggunakan **HMAC-SHA256** untuk autentikasi ke Talenta API:

```
signing_string = "date: {date}\nGET {path}?{query} HTTP/1.1"
digest = HMAC-SHA256(secret, signing_string)
signature = Base64(digest)
Header:
  Authorization: hmac username="{client_id}", algorithm="hmac-sha256", headers="date request-line", signature="{signature}"
  Date: {GMT date}
```

---

## Tech Stack

### Backend
- **PHP** 8.2+
- **Laravel** 12.x
- **Python** 3.x (Flask microservices)
- **PhpSpreadsheet** (baca/tulis Excel)
- **Maatwebsite/Laravel-Excel** (import Excel)
- **pandas** + **openpyxl** (Python Excel writer)

### Frontend
- **Blade** templating engine
- **TailwindCSS** 4.x
- **Vite** 6.x (build tool)

### Database
- **SQLite** (`database/database.sqlite`) — default
- MySQL, PostgreSQL, SQL Server — alternatif

---

## Struktur Database

### 9 Tabel Kustom

#### 1. `gl_entities` — Konfigurasi Entity/Branch
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `code` | string(50) | **UNIQUE** — `cs_semarang`, `staff_kmi`, dll |
| `name` | string(100) | Nama entity |
| `region` | enum | `semarang` atau `surabaya` — menentukan Python service |
| `ledger_code` | string(100) | Kode G/L di Talenta |
| `branch_id` | string(20) | ID branch Talenta (`21087`, `21089`, `21090`) |
| `ledger_id_strategy` | enum | `single` / `multi_try` — cara ambil ledger_id |
| `ledger_id_list` | json | Array ledger_id yang dicoba, misal `[900,901,902,903]` |
| `doc_header_template` | string(200) | Template header dokumen SAP |
| `output_filename_template` | string(200) | Template nama file output |
| `extraction_strategy` | enum | `A`, `B`, `C`, `D`, `E`, `F` |
| `company_code` | string(10) | Default `KMI` |
| `is_active` | boolean | Soft enable/disable |
| `notes` | text | Catatan |

#### 2. `gl_mapping_profiles` — Profile Mapping
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `entity_id` | FK → `gl_entities` | Entity owner |
| `name` | string(100) | Nama profile |
| `is_default` | boolean | Default profile (read-only via UI) |
| `description` | text | Deskripsi |
| `created_by` | string(100) | User pembuat |

#### 3. `gl_account_mappings` — Rule Mapping Akun
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `profile_id` | FK → `gl_mapping_profiles` | Profile owner |
| `mapping_key` | string(100) | Key unik dalam profile |
| `account_number` | string(20) | Nomor akun SAP tujuan |
| `account_name` | string(200) | Nama akun (untuk fallback matching) |
| `account_type` | enum | `Cost center`, `Aggregate`, `Individual` |
| `transaction_value` | enum | `Debit` atau `Credit` |
| `cost_center` | string(20) nullable | Fixed cost center |
| `profit_center` | string(20) nullable | Fixed profit center |
| `use_profit_center` | boolean | Pakai profit center instead of cost center |
| `components` | json | Nama komponen payroll (unused di code) |
| `match_account_name` | string(200) nullable | Untuk multi-variant matching (Surabaya) |
| `match_keywords` | json nullable | Keyword matching, misal `["denda sakit"]` |
| `order_index` | integer | Urutan output |
| `is_active` | boolean | Aktif/nonaktif |

#### 4. `gl_strategy_d_configs` — Konfigurasi Strategy D
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `profile_id` | FK → `gl_mapping_profiles` | **UNIQUE** |
| `debit_accounts` | json | Whitelist nomor akun yang harus Debit |
| `debit_keywords` | json nullable | Keyword trigger Debit, e.g. `["pengembalian"]` |
| `default_dc` | enum | `Debit` / `Credit` default |

#### 5. `gl_run_histories` — Riwayat Eksekusi
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `entity_id` | FK → `gl_entities` | Entity yang di-run |
| `profile_id` | FK → `gl_mapping_profiles` | Profile yang dipakai |
| `period_year` | int | Tahun periode |
| `period_month` | tinyint | Bulan periode (1-12) |
| `status` | enum | `pending`, `running`, `success`, `failed`, `validated` |
| `total_records` | int nullable | Jumlah rows di output |
| `total_debit` | bigint nullable | Total debit |
| `total_credit` | bigint nullable | Total credit |
| `output_file_path` | string(500) nullable | Path file Excel hasil generate |
| `output_filled_path` | string(500) nullable | Path file setelah Fill Text |
| `validation_status` | enum | `not_validated`, `match`, `mismatch` |
| `validation_details` | json nullable | Detail hasil validasi |
| `error_message` | text nullable | Pesan error jika gagal |
| `run_by` | string(100) nullable | User yang menjalankan |
| `started_at` | timestamp | Waktu mulai |
| `completed_at` | timestamp | Waktu selesai |

#### 6. `gl_cost_centers` — Katalog Cost Center SAP
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `cost_center_code` | string(20) | **UNIQUE** — Kode CC |
| `name` | string(200) nullable | Nama |
| `description` | string(200) nullable | Deskripsi (dipakai di Fill Text) |
| `short_text` | string(50) nullable | Teks pendek |
| `is_active` | boolean | Aktif/nonaktif |

#### 7. `gl_account_prefixes` — Prefix per No. Akun
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `account_number` | string(20) | **UNIQUE** — Nomor akun |
| `prefix` | string(100) | Prefix text, misal `"Gaji"`, `"Hutang BPJS"` |

#### 8. `gl_text_references` — Referensi Text (Learning)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `account_number` | string(20) | Nomor akun |
| `cost_center` | string(20) nullable | Cost center (bisa null) |
| `text_value` | string(255) | Full text yang sudah benar |
| `learned_from` | string(255) nullable | Sumber: `manual_input`, `auto_fill_2026_05_10`, dll |
| `use_count` | int | Frekuensi pemakaian |
| `last_used_at` | timestamp | Terakhir dipakai |
| **UNIQUE** | | `(account_number, cost_center)` |

#### 9. `gl_system_settings` — Key-Value Settings
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint | PK |
| `key` | string(100) | **UNIQUE** |
| `value` | text nullable | Value |
| `description` | text nullable | Deskripsi |

---

## Alur Sistem / Flowchart

### Alur Utama: Run Extraction

```
User pilih Entity + Profile + Periode
              │
              ▼
    ┌─────────────────────┐
    │ POST /run/execute   │
    │ (RunController)     │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────┐
    │ Buat GlRunHistory   │
    │ status = 'running'  │
    └─────────┬───────────┘
              │
              ▼
    ┌──────────────────────────────────────────┐
    │ PythonServiceClient::run()               │
    │                                          │
    │  1. BuildPayload:                        │
    │     - Data entity + profile + mapping    │
    │     - Credentials Talenta                │
    │     - Strategy D config (jika ada)       │
    │                                          │
    │  2. HTTP POST ke Python Service:         │
    │     Semarang: http://127.0.0.1:8091/run  │
    │     Surabaya: http://127.0.0.1:8092/run  │
    └─────────────────────┬────────────────────┘
                          │
                          ▼
    ┌──────────────────────────────────────────┐
    │ PYTHON: execute_extraction()             │
    │                                          │
    │  1. GET ledger/report/history            │
    │     (year, month)                        │
    │                                          │
    │  2. Filter by ledger_code                │
    │     (cocokkan code/name)                 │
    │                                          │
    │  3. GET report/{id}?ledger_id=...        │
    │     (single atau multi_try)              │
    │     Ambil detail paling banyak rows      │
    │                                          │
    │  4. Jalankan strategi ekstraksi          │
    │     A/B/C/D/E/F sesuai config            │
    │                                          │
    │  5. Convert ke SAP 20 kolom:             │
    │     - PstKy: 40 (Debit) / 50 (Credit)    │
    │     - Amount, Account, Cost Center, dll  │
    │                                          │
    │  6. Save .xlsx ke storage/app/gl_outputs/│
    │                                          │
    │  7. Return {status, output_file, totals} │
    └─────────────────────┬────────────────────┘
                          │
                          ▼
    ┌──────────────────────────────────────────┐
    │ Update GlRunHistory:                     │
    │                                         │
    │ Success → status='success'               │
    │           + total_records, debit, credit │
    │           + output_file_path             │
    │                                         │
    │ Failed  → status='failed'               │
    │           + error_message                │
    └─────────────────────┬───────────────────┘
                          │
                          ▼
    Redirect ke /run/show/{id}
    (detail hasil run)
```

### Alur Validasi

```
User upload file Excel asli dari Talenta
         + pilih history run yang mau divalidasi
                      │
                      ▼
    ┌─────────────────────────────────────┐
    │ ValidatorController::runValidation  │
    └──────────────┬──────────────────────┘
                   │
                   ▼
    ┌─────────────────────────────────────┐
    │ ValidatorService::validate()        │
    │                                     │
    │ 1. Baca file ASLI (Talenta format): │
    │    - GL Account, Debit/Credit,       │
    │      Amount, Cost Center            │
    │                                     │
    │ 2. Baca file GENERATE (SAP format): │
    │    - Account, PstKy → Debit/Credit, │
    │      Amount, Cost Center            │
    │                                     │
    │ 3. Buat summary totals:             │
    │    - Rows, Debit, Credit, THP       │
    │    - Bandingkan ASLI vs GENERATE    │
    │                                     │
    │ 4. Entity Mismatch Detection:       │
    │    - Jika selisih Debit > 20% atau  │
    │      selisih Row > 30% → WARNING    │
    │                                     │
    │ 5. Group by (Account + DC):         │
    │    - Multiset comparison pakai      │
    │      signature amount@cost_center   │
    │    - Flag match/mismatch per group  │
    │                                     │
    │ 6. Account-level Issues:            │
    │    - Account BARU di asli (blm di-  │
    │      mapping) → high severity       │
    │    - Account hanya di gen (typo/    │
    │      dihapus) → medium/info         │
    └──────────────┬──────────────────────┘
                   │
                   ▼
    Tampilkan halaman result dengan:
    - Summary table (totals)
    - Entity mismatch warning (jika ada)
    - Account issues list
    - Per-group comparison table
```

### Alur Fill Text

```
User pilih history run + (opsional) file referensi
                      │
                      ▼
    ┌──────────────────────────────────────┐
    │ FillTextController::runFill          │
    └──────────────┬───────────────────────┘
                   │
                   ▼
    ┌──────────────────────────────────────┐
    │ 1. Load reference file (optional):   │
    │    Parse file Excel, baca kolom      │
    │    Account, Cost Center, Text        │
    │    ➔ Simpan ke gl_text_references    │
    │                                      │
    │ 2. FillTextService::fill()           │
    │    a. Build lookup maps:             │
    │       - referenceMap (text_refs)     │
    │       - prefixMap (account_prefixes) │
    │       - ccMap (cost_centers)         │
    │                                      │
    │    b. Iterasi setiap row di Excel:   │
    │       generateText(account, cc):     │
    │                                      │
    │       Ada reference?  ──→ pakai itu  │
    │            │                         │
    │            ▼(tidak)                  │
    │       Ada prefix?                    │
    │            │                         │
    │       ┌────┴────┐                    │
    │       ▼         ▼                    │
    │    Ada CC?   prefix_only             │
    │       │                              │
    │   ┌──┴──┐                            │
    │   ▼     ▼                            │
    │ Ada  No CCDesc                       │
    │ CCDesc  → prefix + MARKER            │
    │   │     (NEED FILL)                  │
    │   ▼                                  │
    │ prefix + " - " + ccDesc              │
    │                                      │
    │ 3. Simpan hasil:                     │
    │    - Output: _FILLED.xlsx            │
    │    - Belajar dari prefix_only /      │
    │      prefix_with_cc → auto learn     │
    │      ke gl_text_references           │
    │    - Flag rows yang masih perlu      │
    │      diisi manual (need_fill)        │
    │                                      │
    │ 4. Manual Input (saveManual):        │
    │    User isi prefix + cc_description  │
    │    ➔ Auto-save ke:                   │
    │       - gl_account_prefixes          │
    │       - gl_cost_centers              │
    │       - gl_text_references           │
    │    ➔ Update file Excel               │
    └──────────────────────────────────────┘
```

### Alur Mapping Editor

```
Index (/mapping)
  │
  ├─ Tampilkan semua entity grouped by region
  │   Masing-masing entity: list profiles
  │
  ├─ Profile detail (/mapping/profile/{id})
  │   ├─ List account mappings
  │   ├─ Strategy D config (jika strategy D)
  │   ├─ Tombol duplicate, delete, tambah row
  │   └─ Edit/hapus per mapping row
  │
  ├─ Duplicate profile → copy semua mapping + strategy D config
  │   (hanya profile non-default bisa diedit)
  │
  └─ CRUD account mapping:
      mapping_key, account_number, account_name,
      account_type (Cost center/Aggregate/Individual),
      transaction_value (Debit/Credit), cost_center,
      profit_center, components, match_account_name,
      match_keywords, order_index
```

---

## Extraction Strategy (Logika Python)

### Semarang Service (Port 8091)

| Strategy | Nama | Logika |
|----------|------|--------|
| **A** | Aggregate-only with fixed CC | Pre-init semua mapping amount=0. Match report items via keyword di account_name. Sum semua detail per matched_key. Output 1 baris per mapping. Cost center dari mapping, atau profit_center. |
| **B** | Hybrid Cost Center + Aggregate | Mapping type 'Cost center' → pisahkan per cost_center_code dari detail API. Mapping type 'Aggregate' → sum semua detail. Match by account_number dulu, lalu keywords. |
| **C** | Individual per-detail | Setiap detail item API = 1 baris output. Match by account_number exact. Tidak ada agregasi. Sorting: Debit dulu, Credit kemudian, by account_number. |

### Surabaya Service (Port 8092)

| Strategy | Nama | Logika |
|----------|------|--------|
| **B** | Hybrid Cost Center + Aggregate (Surabaya variant) | Mirip strategy B Semarang, tapi menggunakan `match_account_name` untuk multi-variant matching (khusus account 2010000005 yang punya banyak variant name). |
| **D** | Auto-detect D/C (tanpa mapping eksplisit) | Tidak pakai mapping. D/C ditentukan oleh: (1) Keyword di account_name → Debit, (2) Whitelist account_number → Debit, (3) Default → Credit. Account_type: ada cost_center di details → Cost center, else Aggregate. |
| **E** | Mapping with preserved API order (KMI1) | Sama seperti strategy B, tapi urutan output **preserve dari API** (tidak di-reorder). Setiap detail item di-emit langsung. Aggregate type → 1 detail = 1 entry (preserve count). |
| **F** | Per-detail include-zero (Pembantu KMI) | Setiap detail item = 1 baris, **termasuk amount=0**. Match by account_number exact. Sorting: Debit dulu, Credit kemudian. Cost center → pakai CC dari detail. Aggregate → profit_center 200301. |

### Format Output SAP (20 Kolom)

Setelah ekstraksi, entries dikonversi ke DataFrame 20 kolom:

```
Document Date | Posting Date | Doc. Type | Company Code | Curr | Reference |
Doc. Header | PstKy (40/50) | Account | Sp.G/L | Amount | Due On | Tax Code |
Value Date | Cost Center | Profit Center | Assignment | Text | Reason Code | House Bank
```

- **PstKy**: 40 = Debit, 50 = Credit
- **Doc. Header**: template dari entity, `{MONTH}` dan `{YEAR}` di-replace
- **Tanggal**: hari terakhir bulan periode

---

## Halaman & Fungsionalitas Web

### 1. Dashboard (`/`)
- Status Python service (Semarang & Surabaya)
- Statistik: total entity, per region, run bulan ini, sukses bulan ini
- Tombol "Run Extraction Baru"
- List entity per region + shortcut Run
- Tabel run terbaru (10 terakhir)

### 2. Run Extraction (`/run`)
- **Form**: Pilih entity → (AJAX load profiles) → pilih profile → pilih tahun/bulan
- **Execute**: POST → call Python service → record history
- **Show**: Detail hasil run (status, records, debit, credit, download)
- **History**: List semua run (filter by entity/status/year/month), pagination 20
- **Health**: Cek status kedua Python service

### 3. Mapping Editor (`/mapping`)
- **Index**: List entity + profiles per region
- **Profile Detail**: List account mappings + strategy D config (jika ada)
- **Duplicate Profile**: Copy semua mapping + strategy D config ke profile baru
- **CRUD Mapping Row**: mapping_key, account_number, account_type, D/C, cost_center, profit_center, keywords, dll
- Proteksi: profile **default** tidak bisa diedit/dihapus (harus di-duplicate dulu)

### 4. Validator (`/validator`)
- **Form**: Upload file Excel asli Talenta + pilih history run
- **Result**: Tabel summary (rows, debit, credit, THP), comparison per-group, entity mismatch warning, account-level issues (new/missing accounts)

### 5. Fill Text (`/fill-text`)
- **Form**: Pilih history run + (opsional) upload file referensi Excel
- **Result**: Summary fill (filled from reference, from prefix, need manual fill)
- **Manual Input**: User isi prefix + cc_description untuk rows yang belum terisi
- **Show**: Lihat kondisi file filled terbaru
- **Download**: Download file _FILLED.xlsx (no-cache headers)

### 6. Subtype Fill Text (`/fill-text/subtype`)

Khusus untuk **Account 2010000005 (Uang Titipan)** yang memiliki beberapa subtype (Denda, Koperasi, Kelalaian, dll).

Feature ini membutuhkan **2 file upload**:
1. **Ledger Mapping Export** dari Talenta (berisi GL Entry → Component ID → Component Name)
2. **Target File** (Excel GL hasil extraction, format 20 kolom SAP)

Cara kerja **position-based matching**:
- Parse ledger file → filter GL Entry = 2010000005 → ambil semua component names (preserve empty entries)
- Parse target file → filter account 2010000005 → ambil rows sesuai urutan Excel
- Cocokkan berdasarkan **posisi/index** (row ke-N target = component ke-N ledger)
- Output label: mapping via `LABEL_MAP` (e.g. `"Denda Indisipliner"` → `"Uang Titipan Denda"`), fallback ke `"Uang Titipan - {Component}"`

**Tampilan setelah proses:**
- Stats cards (target rows, ledger matched, unmatched)
- Tabel komparasi side-by-side (Ledger vs Target per posisi) dengan ✅ ⚠️ ➕ indikator + color coding
- Tabel mapping rows dengan input editable labels + tombol "Apply & Download"

**Controller**: `TestFillTextController` — 4 public methods: showForm, process, showResult, apply

**Keterbatasan**: Karena `use_profit_center: true` di mapping 2010000005, cost center selalu kosong di Excel → positional matching adalah satu-satunya pendekatan yang viable.

### 7. Text References (`/text-references`)
- **Index**: List semua text reference (search/filter by account), pagination 30
- **CRUD**: Tambah/edit/hapus reference

### 8. Reset Center (`/reset-center`)
- **PIN Gate**: Form input PIN untuk akses
- **Reset Sections**:
  - Run Histories → truncate + hapus file gl_outputs
  - Text References → truncate + hapus file gl_filled + gl_references
  - Account Prefixes → truncate + re-seed dari GlAccountPrefixSeeder
  - Cost Centers → truncate + re-seed dari COSTCENTER_SAP.xlsx
- **Reset All**: Semua section di-reset berurutan

### 9. Help (`/help`)
- Halaman dokumentasi statis

---

## Services (Laravel)

### `TestFillTextController` (Baru)

- `showForm()` → `GET /fill-text/subtype` — Form upload 2 file (Ledger Mapping + Target Excel)
- `process()` → `POST /fill-text/subtype/process` — Parse kedua file, positional matching, store session, redirect ke result
- `showResult()` → `GET /fill-text/subtype/result` — Tampilkan tabel komparasi + editable labels
- `apply()` → `POST /fill-text/subtype/apply` — Validasi labels, tulis ke Excel, return download
- `getLedgerRowsForAccount()` — Parse ledger file, filter GL Entry = 2010000005, return [GL Entry, Description, Component ID, Component Name]
- `getTargetRowsForAccount()` — Parse target file, filter account = 2010000005, return [excel_row, amount, cost_center, text]
- Menggunakan posisional matching karena cost center selalu kosong (use_profit_center: true)

### `PythonServiceClient`
- `run(GlEntity, GlMappingProfile, year, month)` → call Python service, return result
- `health()` → cek status kedua service
- `buildPayload()` → compose JSON payload dengan data entity, profile, mappings, credentials Talenta, dan strategy D config

### `FillTextService`
- `fill(inputPath, outputPath, manualReferences)` → isi kolom Text di Excel
- `generateText(account, cc, ...)` → logic penentuan text (reference > prefix+cc > prefix-only > marker)
- `parseReferenceFile(path)` → parse Excel referensi, simpan ke DB
- `saveManualTextsAndRegenerate(path, inputs)` → simpan manual inputs ke 3 tabel + update Excel

### `ValidatorService`
- `validate(asliPath, genPath)` → bandingkan file asli Talenta vs generate SAP
- `detectEntityMismatch()` → threshold 20% debit, 30% rows
- `detectAccountIssues()` → new/missing account detection
- `readAsliFile()` → parse kolom GL Account, Debit/Credit, Amount, Cost Center
- `readGenerateFile()` → parse kolom Account, PstKy → Debit/Credit, Amount, Cost Center
- `groupRows()` → group by (account + DC)
- `makeSig()` → signature `amount@cost_center` untuk multiset comparison
- `multisetDiff()` → beda multiset antara dua group

### `ResetCenterService`
- `getStats()` → count semua tabel + file
- `resetRunHistories()` → truncate gl_run_histories + delete gl_outputs
- `resetTextReferences()` → truncate gl_text_references + delete gl_filled + gl_references
- `resetAccountPrefixes()` → truncate + re-seed (dengan silent fail detection)
- `resetCostCenters()` → truncate + re-seed dari COSTCENTER_SAP.xlsx (dengan pre-check file)
- `resetAll()` → semua reset berurutan

---

## Models (Eloquent ORM)

| Model | Table | Relasi |
|-------|-------|--------|
| `GlEntity` | `gl_entities` | HasMany: `mappingProfiles`, `runHistories`. HasOne: `defaultProfile` |
| `GlMappingProfile` | `gl_mapping_profiles` | BelongsTo: `entity`. HasMany: `accountMappings`, `runHistories`. HasOne: `strategyDConfig` |
| `GlAccountMapping` | `gl_account_mappings` | BelongsTo: `profile` |
| `GlRunHistory` | `gl_run_histories` | BelongsTo: `entity`, `profile` |
| `GlCostCenter` | `gl_cost_centers` | - |
| `GlAccountPrefix` | `gl_account_prefixes` | - |
| `GlStrategyDConfig` | `gl_strategy_d_configs` | BelongsTo: `profile` |
| `GlTextReference` | `gl_text_references` | - |
| `GlSystemSetting` | `gl_system_settings` | - |

### Key Model Methods

- **GlEntity**: `getPythonServiceUrl()` → return URL berdasarkan region
- **GlRunHistory**: `getPeriodLabelAttribute()` → "Mei 2026", `getDifferenceAttribute()` → debit - credit
- **GlCostCenter**: `findByCode()`, `getDisplayDescriptionAttribute()`
- **GlAccountPrefix**: `getPrefix()`, `allAsMap()`
- **GlTextReference**: `findReference()`, `learnOrUpdate()`, `buildLookupMap()`
- **GlSystemSetting**: `getValue()`, `setValue()`

---

## Cara Install & Run

### Prerequisites
- PHP 8.2+
- Python 3.x
- Composer
- Node.js + npm

### 1. Clone & Install Dependencies

```bash
git clone <repo-url>
cd GL_Reporting_Talenta_SAP
composer install
npm install
```

### 2. Environment

```bash
cp .env.example .env
# Edit .env untuk konfigurasi database, Talenta API credentials
```

### 3. Database

```bash
# SQLite (default) — sudah include file database.sqlite
touch database/database.sqlite

# Atau setup MySQL/PostgreSQL di .env

# Run migration + seeder
php artisan migrate
php artisan db:seed
```

### 4. Build Frontend

```bash
npm run build
# atau untuk development:
npm run dev
```

### 5. Jalankan Python Services

```bash
# Terminal 1 - Semarang (strategi A, B, C)
python python_semarang.py

# Terminal 2 - Surabaya (strategi B, D, E, F)
python python_surabaya.py
```

### 6. Jalankan Laravel

```bash
php artisan serve
```

Akses di `http://127.0.0.1:8000`

---

## API Endpoints (Python)

### Semarang (Port 8091)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Cek status service + supported strategies |
| POST | `/run` | Eksekusi extraction (body: JSON config) |

### Surabaya (Port 8092)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/health` | Cek status service + supported strategies |
| POST | `/run` | Eksekusi extraction (body: JSON config) |

### Request Body `/run`

```json
{
  "entity_code": "cs_semarang",
  "entity_name": "CS Semarang",
  "ledger_code": "G_L CS Semarang",
  "branch_id": "21087",
  "ledger_id_strategy": "multi_try",
  "ledger_id_list": [900, 901, 902],
  "doc_header_template": "BEBAN GAJI CS {MONTH} {YEAR}",
  "output_filename_template": "GL_CS_SEMARANG_{YEAR}_{MONTH}.xlsx",
  "extraction_strategy": "B",
  "company_code": "KMI",
  "year": 2026,
  "month": 5,
  "mappings": [...],
  "strategy_d_config": { ... },
  "talenta": {
    "client_id": "...",
    "client_secret": "..."
  }
}
```

### Response `/run`

```json
{
  "status": "success",
  "output_file": "D:/DEV/.../storage/app/gl_outputs/GL_CS_SEMARANG_2026_05.xlsx",
  "output_filename": "GL_CS_SEMARANG_2026_05.xlsx",
  "total_records": 42,
  "total_debit": 250000000,
  "total_credit": 250000000,
  "difference": 0,
  "report_id": 12345,
  "ledger_name": "G_L CS Semarang",
  "created_at": "2026-05-01T10:00:00.000Z"
}
```

---

## Format File Excel Output (20 Kolom SAP)

| Kolom | Tipe | Contoh |
|-------|------|--------|
| Document Date | string | `31.05.2026` |
| Posting Date | string | `31.05.2026` |
| Doc. Type | string | `KA` |
| Company Code | string | `KMI` |
| Curr | string | `IDR` |
| Reference | string | `TALENTA` |
| Doc. Header | string | `BEBAN GAJI CS MEI 2026` |
| PstKy | int | `40` (Debit) / `50` (Credit) |
| Account | int | `5204000009` |
| Sp.G/L | string | (kosong) |
| Amount | int | `50000000` |
| Due On | string | (kosong) |
| Tax Code | string | (kosong) |
| Value Date | string | (kosong) |
| Cost Center | string | `21087` atau kosong |
| Profit Center | string | `200301` atau kosong |
| Assignment | string | (kosong) |
| Text | string | `Gaji - KARYAWAN TETAP` (setelah Fill Text) |
| Reason Code | string | (kosong) |
| House Bank | string | (kosong) |

---

## Catatan Penting

### Marker Text
- `?? Account-Belum-Prefix` — Akun belum punya prefix di `gl_account_prefixes`
- `{prefix} (NEED FILL)` — Prefix ada tapi cost center belum punya description di `gl_cost_centers`
- Format Legacy: `Gaji 12345678` — Format lama yang belum di-fill

### Threshold Validasi
- **Entity Mismatch**: selisih debit > 20% atau selisih rows > 30% → warning
- **Account Issues**: account baru di Talenta (high severity), account typo/dihapus (medium/info)

### Keamanan
- PIN gate untuk Reset Center (disimpan di `gl_system_settings` key `reset_pin`)
- Profile default tidak bisa di-edit/dihapus via UI (hanya bisa di-duplicate)
- Foreign key checks dimatikan sementara saat truncate (reset)

### Storage
```
storage/app/
├── gl_outputs/       → File Excel hasil extraction (*.xlsx)
├── gl_filled/        → File Excel setelah Fill Text (*_FILLED.xlsx)
├── gl_references/    → File referensi yang di-upload untuk Fill Text
├── gl_uploads/       → Temporary upload (file asli Talenta untuk validasi, COSTCENTER_SAP.xlsx)
└── test_fill_text/   → Upload sementara untuk Subtype Fill Text (ledger + target files)
```
