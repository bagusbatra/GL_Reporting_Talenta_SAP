# Update Fill Text — Uang Titipan (Account 2010000005)

**Versi: 3.1** — 10 Juni 2026

## Status: ✅ Approved — Terintegrasi ke Navbar

---

## 1. Latar Belakang

Account **2010000005 (Uang Titipan)** memiliki beberapa subtype (Denda Indisipliner, Potongan Denda, Potongan Lainnya, dll) yang direpresentasikan sebagai baris terpisah di file Excel GL. Jumlah subtype **tidak fixed** — sepenuhnya mengikuti data dari file **Ledger Mapping Export** per entity/profile.

Kendala:
- **Tidak boleh** mengubah Python service (extraction strategy)
- **Tidak boleh** mengubah mapping 2010000005 (tetap Aggregate, `use_profit_center: true`, cost center kosong)
- **Tidak boleh** mengubah format Excel output (20 kolom SAP)
- Informasi subtype tidak tersedia di baris Excel (cost center kosong, text = "Uang Titipan")

---

## 2. Pendekatan: Position-Based Matching via Ledger Mapping

### 2.1 Cara Kerja

1. **Ledger Mapping Export** dari Talenta berisi mapping per GL Entry ke Component ID + Component Name
2. Untuk account 2010000005, entries dengan Component Name diambil **dinamis** dari file (jumlah bervariasi per entity)
3. **Target File** (Excel GL hasil generate) memiliki N baris 2010000005
4. Pencocokan dilakukan secara **positional**: baris ke-N target → entry ke-N ledger
5. Jika komponen ledger kosong → label output juga **kosong** (tidak pakai fallback text lama)
6. Output text: `"Uang Titipan - {Component Name}"` (jika komponen terisi)

### 2.2 Kenapa Positional Bekerja

Strategy extraction (A dan B) membangun entries dengan iterasi `for m in mappings:` — output Excel mengikuti urutan `order_index` dari Laravel yang sama dengan urutan entries di Ledger Mapping Talenta.

---

## 3. Komponen Implementasi

### 3.1 TestFillTextController (BARU — ~300 baris)

`app/Http/Controllers/TestFillTextController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `showForm()` | `GET /fill-text/subtype` | Form upload 2 file |
| `process()` | `POST /fill-text/subtype/process` | Parse Ledger (via `getLedgerRowsForAccount`) + Target; store ke session; **redirect** ke result |
| `showResult()` | `GET /fill-text/subtype/result` | Baca session → tampilkan tabel komparasi + preview |
| `apply()` | `POST /fill-text/subtype/apply` | Validasi labels → tulis ke Excel → download hasil |
| | **Private methods** | |
| `getLedgerRowsForAccount()` | — | Parse ledger file, return [GL Entry, Description, Component ID, Component Name] per row |
| `getComponentNamesForAccount()` | — | Parse ledger, return array of component names (untuk positional matching) |
| `getTargetRowsForAccount()` | — | Parse target, filter by account, return [excel_row, amount, cost_center, text] |

### 3.2 Views (BARU — dipindahkan ke `fill_text/`)

| View | Description |
|------|-------------|
| `resources/views/fill_text/subtype_form.blade.php` | Upload form (Ledger Mapping Export + Target File) |
| `resources/views/fill_text/subtype_result.blade.php` | Preview table + editable labels + download button |

### 3.3 Routes — prefix `fill-text/subtype`

`routes/web.php`:

```php
Route::prefix('fill-text/subtype')->name('fill_text.subtype.')->group(function () {
    Route::get('/', [TestFillTextController::class, 'showForm'])->name('form');
    Route::get('/result', [TestFillTextController::class, 'showResult'])->name('result');
    Route::post('/process', [TestFillTextController::class, 'process'])->name('process');
    Route::post('/apply', [TestFillTextController::class, 'apply'])->name('apply');
});
```

### 3.4 Navbar (DIUBAAH)

`resources/views/layouts/app.blade.php` — tambah link `Subtype Fill`:

```blade
<a href="{{ route('fill_text.subtype.form') }}" ...>Subtype Fill</a>
```

### 3.4 File Test Data (BARU, tidak di-commit)

`test_FillText_UangTitipan/` — berisi 2 file Excel untuk uji coba:
- `Ledger Mapping Export 934.xlsx` — 8 kolom (GL Entry, Description, Type, GL Type, Component ID, Components, JSON Metadata, Filter)
- `Upload_GL_STAFF_KMI_2026_05.xlsx` — 20 kolom SAP, 157 rows, 10 rows 2010000005

---

## 4. Alur Detail

### 4.1 Upload & Process

```
User uploads:
  [1] Ledger Mapping Export 934.xlsx
  [2] Upload_GL_STAFF_KMI_2026_05.xlsx
        │
        ▼
StoreUpload() → simpan ke storage/app/test_fill_text/
        │
        ▼
getLedgerRowsForAccount('2010000005'):
  - Parse ledger, filter by GL Entry = 2010000005
  - Return per row: [GL Entry, Description, Component ID, Component Name]
  - PRESERVE empty component entries (urutan terjaga)
  → [
      {gl_entry, description, component_id, component_name: 'Denda Indisipliner'},
      {gl_entry, description, component_id, component_name: 'Potongan Denda'},
      ...
    ]
        │
        ▼
getTargetRowsForAccount('2010000005'):
  - Parse target file
  - Filter rows where Account = 2010000005 (preserve order)
  → [Row128, Row129, ..., Row136, Row157]
        │
        ▼
Compare count:
  - Jika count(ledger) !== count(target) → flash warning ke session
        │
        ▼
Match by position:
  Row128 → component[0] = 'Denda Indisipliner' → 'Uang Titipan Denda' (via LABEL_MAP)
  Row129 → component[1] = 'Potongan Denda'     → 'Uang Titipan Denda'
  ...
        │
        ▼
Session: store ledgerRows + matched + target path
        │
        ▼
Redirect ke GET /fill-text/subtype/result
        │
        ▼
View: 
  1. Stats box (target rows, matched, unmatched)
  2. ⭐ Tabel Komparasi (Ledger vs Target side-by-side per posisi)
     - Indikator: ✅ cocok, ⚠️ mismatch, ➕ extra row
     - Color coding: red/amber/orange background
  3. Tabel Mapping Rows (editable labels + Apply & Download)
```

### 4.2 Apply & Download

```
User edits labels (optional) → klik "Apply & Download"
  (form action: POST /test-fill-text/apply, origin: GET /test-fill-text/result)
        │
        ▼
Validasi labels[]: nullable|string|max:200
  (allow empty string untuk row tanpa komponen)
        │
        ▼
Copy target file → temp
        │
        ▼
For each matched row:
  sheet->setCellValue(TextCol . excelRow, label)
        │
        ▼
Session: clear matched + target path
        │
        ▼
Download → "Test_Filled_Upload_GL_STAFF_KMI_2026_05.xlsx"

Error handling:
  - Session expired → redirect ke GET /test-fill-text/form (bukan back())
  - Validasi gagal → redirect ke GET /test-fill-text/form
  - Kolom Text tidak ditemukan → redirect ke GET /test-fill-text/form
  (Semua error redirect ke GET route, hindari MethodNotAllowedHttpException)
```

---

## 5. Teknis Parsing

### 5.1 Column Detection

`readHeaderMap(Worksheet $sheet): array`
- Iterasi kolom dari A sampai ZZ
- Berhenti setelah 5 kolom kosong berturut-turut
- Simpan `strtolower(headerValue) → columnLetter`

`findHeader(array $headerMap, string $search): ?string`
- Coba exact match dulu (`$headerMap[$searchLower]`)
- Fallback ke partial match (`str_contains(key, searchLower)`)
- Contoh: "Components (Cannot be Imported)" cocok dengan search "components"

### 5.2 Label Mapping (LABEL_MAP)

Output label tidak menggunakan format generic. Setiap component name memiliki mapping spesifik:

| Component Name | Output Label |
|----------------|-------------|
| Potongan Koperasi | Uang Titipan Koperasi |
| Potongan Kelalaian | Uang Titipan Jaminan Kelalaian |
| Potongan Denda Sakit | Uang Titipan Refund |
| Potongan Denda Terlambat | Uang Titipan Refund |
| Potongan Denda | Uang Titipan Denda |
| Potongan Denda Indisipliner | Uang Titipan Denda |
| Denda Indisipliner | Uang Titipan Denda |
| Potongan Lain-lain | Uang Titipan Talenta |
| Potongan Lainnya | Uang Titipan Lelang |

Jika component name tidak ada di mapping → fallback ke format **`"Uang Titipan - {Component Name}"`**.

### 5.3 Handling Empty Component Names

- Entry ledger dengan component name kosong **tidak di-skip** — tetap dimasukkan ke array `$names` sebagai string kosong
- Ini penting agar urutan positional antara ledger entries dan target rows tetap sinkron
- Saat component name kosong, `default_label` di-set ke `''` (bukan fallback `$row['text']`)

### 5.4 PhpSpreadsheet Workarounds

- File target menghasilkan `Undefined array key 141` warning internal → gunakan `@` supresi
- Gunakan `setReadDataOnly(true)` untuk skip shared string processing
- Gunakan `getHighestDataRow()` bukan `getHighestRow()` untuk hindari loop ke jutaan baris
- Component name dari ledger file memiliki leading `" - "` → `trim($name, " -")`

---

## 6. File yang Diubah/Dibuat

| File | Status | Keterangan |
|------|--------|------------|
| `app/Http/Controllers/TestFillTextController.php` | **BARU** | ~300 baris, 4 public + 4 private methods |
| `resources/views/fill_text/subtype_form.blade.php` | **BARU** | Form upload (moved from `test_fill_text/form.blade.php`) |
| `resources/views/fill_text/subtype_result.blade.php` | **BARU** | Tabel komparasi + mapping + download (moved from `test_fill_text/result.blade.php`) |
| `routes/web.php` | **DIUBAH** | `test-fill-text/*` → `fill-text/subtype/*` |
| `resources/views/layouts/app.blade.php` | **DIUBAH** | + navbar link `Subtype Fill` |
| `updateFillText.md` | **DIUBAH** | Dokumentasi ini |

**Tidak ada perubahan pada:** Python service, model, migration, seeder, FillTextService.php, FillTextController.php yang sudah ada.

---

## 7. Hasil Test (dengan file dari test_FillText_UangTitipan/)

```
Ledger entries 2010000005 (9):
  [0] Denda Indisipliner
  [1] Potongan Denda
  [2] Potongan Lainnya
  [3] Potongan Denda Terlambat
  [4] Potongan Indisipliner
  [5] Potongan Lain-lain
  [6] Potongan Kelalaian
  [7] Potongan Koperasi
  [8] Potongan Denda Sakit

Target rows 2010000005 (10):
  Row 128 → 'Uang Titipan Denda'             (Denda Indisipliner)
  Row 129 → 'Uang Titipan Denda'             (Potongan Denda)
  Row 130 → 'Uang Titipan Lelang'            (Potongan Lainnya)
  Row 131 → 'Uang Titipan Refund'            (Potongan Denda Terlambat)
  Row 132 → 'Uang Titipan - Potongan Indisipliner'  (Potongan Indisipliner — fallback generic)
  Row 133 → 'Uang Titipan Talenta'           (Potongan Lain-lain)
  Row 134 → 'Uang Titipan Jaminan Kelalaian' (Potongan Kelalaian)
  Row 135 → 'Uang Titipan Koperasi'          (Potongan Koperasi)
  Row 136 → 'Uang Titipan Refund'            (Potongan Denda Sakit)
  Row 157 → ''                                (kosong — tidak ada komponen ledger)
```

---

## 8. Changelog

### v2.0 (10 Juni 2026)

| Perubahan | Detail |
|-----------|--------|
| **Empty component names** | `getComponentNamesForAccount()` sekarang preserve entry kosong — tidak di-skip, urutan positional terjaga |
| **Empty label output** | `default_label` jadi `''` saat component name kosong (bukan fallback `$row['text']`) |
| **Redirect after process** | `process()` redirect ke `GET /test-fill-text/result` (bukan return view langsung) — solve download issue |
| **New GET route** | `GET /test-fill-text/result` — `showResult()` baca session, tampilkan result view di URL GET |
| **Manual validation** | `apply()` pakai `Validator::make()` + `redirect()->route('test_fill_text.form')` (bukan `back()`) — solve MethodNotAllowedHttpException |
| **Nullable labels** | Validasi `labels.*` jadi `nullable\|string\|max:200` — allow empty string untuk row tanpa komponen |
| **Jumlah subtype dinamis** | Tidak hardcode 9, mengikuti data di file Ledger Mapping Export per entity |
| **LABEL_MAP** | Mapping manual component name → output label spesifik (bukan format generic `"Uang Titipan - ..."`) |
| **Fix Kelalaian** | Potongan Kelalaian → `Uang Titipan Jaminan Kelalaian` (dengan prefix "Uang Titipan") |
| **Dipindahkan ke fill-text/subtype** | Route dari `/test-fill-text` → `/fill-text/subtype` |
| **Views pindah** | Dari `test_fill_text/` → `fill_text/subtype_*.blade.php` |
| **Navbar** | Tombol `Subtype Fill` ditambahkan di navbar |
| **Disetujui** | Status berubah dari Experimental → Approved |

### v3.1 (10 Juni 2026)

| Perubahan | Detail |
|-----------|--------|
| **Tabel Komparasi** | Halaman result menampilkan side-by-side Ledger Mapping (GL Entry, Description, Component) vs Target File (Row, CC, Amount, Text) per posisi |
| **Indikator kecocokan** | ✅ cocok, ⚠️ mismatch (ledger ada komponen, target tidak matching), ➕ extra row di salah satu file |
| **Color coding** | Red/amber/orange background untuk baris yang bermasalah |
| **getLedgerRowsForAccount()** | Method baru — return data ledger lengkap (GL Entry, Description, Component ID, Component Name) |
| **Warning count mismatch** | Flash warning jika jumlah ledger entries ≠ target rows |

### Rencana Selanjutnya

#### Jangka Pendek
- Uji coba dengan berbagai file Ledger Mapping (entity/profile berbeda)
- Verifikasi akurasi positional matching untuk berbagai strategy extraction (A, B, C, D, E, F)
- Handle edge case: jumlah target rows ≠ jumlah ledger entries

#### Jangka Panjang (jika test page disetujui jadi fitur permanen)
- Integrasi ke Flow Fill Text yang sudah ada (FillTextService + FillTextController)
- Buat migration + model `gl_subtype_labels` untuk persistent storage
- Seeder data untuk semua account yang perlu subtype labeling
- Auto-detect strategy extraction: hanya terapkan untuk Strategy A & B
