# Update Fill Text — Uang Titipan (Account 2010000005)

## Status: Eksperimental Page (Test/Uji Coba)

---

## 1. Latar Belakang

Account **2010000005 (Uang Titipan)** memiliki 9 subtype berbeda (Denda Indisipliner, Potongan Denda, Potongan Lainnya, Potongan Denda Terlambat, Potongan Indisipliner, Potongan Lain-lain, Potongan Kelalaian, Potongan Koperasi, Potongan Denda Sakit) yang direpresentasikan sebagai 9 baris terpisah di file Excel GL.

Kendala:
- **Tidak boleh** mengubah Python service (extraction strategy)
- **Tidak boleh** mengubah mapping 2010000005 (tetap Aggregate, `use_profit_center: true`, cost center kosong)
- **Tidak boleh** mengubah format Excel output (20 kolom SAP)
- Informasi subtype tidak tersedia di baris Excel (cost center kosong, text = "Uang Titipan")

---

## 2. Pendekatan: Position-Based Matching via Ledger Mapping

### 2.1 Cara Kerja

1. **Ledger Mapping Export** dari Talenta berisi mapping per GL Entry ke Component ID + Component Name
2. Untuk account 2010000005, terdapat 9 entries dengan Component Name seperti "Denda Indisipliner", "Potongan Koperasi", dll
3. **Target File** (Excel GL hasil generate) memiliki 10 baris 2010000005 (9 baris + 1 baris dengan amount nol)
4. Pencocokan dilakukan secara **positional**: baris ke-1 target → entry ke-1 ledger, baris ke-2 target → entry ke-2, dst
5. Output text: `"Uang Titipan - {Component Name}"`

### 2.2 Kenapa Positional Bekerja

Strategy extraction (A dan B) membangun entries dengan iterasi `for m in mappings:` — output Excel mengikuti urutan `order_index` dari Laravel yang sama dengan urutan entries di Ledger Mapping Talenta.

---

## 3. Komponen Implementasi

### 3.1 TestFillTextController (BARU)

`app/Http/Controllers/TestFillTextController.php`

| Method | Route | Description |
|--------|-------|-------------|
| `showForm()` | `GET /test-fill-text` | Form upload 2 file |
| `process()` | `POST /test-fill-text/process` | Parse Ledger Mapping → extract component names per account; Parse Target → filter rows per account; Match by position → show preview |
| `apply()` | `POST /test-fill-text/apply` | Tulis label ke Excel → download hasil |

### 3.2 Views (BARU)

| View | Description |
|------|-------------|
| `resources/views/test_fill_text/form.blade.php` | Upload form (Ledger Mapping Export + Target File) |
| `resources/views/test_fill_text/result.blade.php` | Preview table + editable labels + download button |

### 3.3 Routes (DIUBAH)

`routes/web.php` — tambah import `TestFillTextController` + 3 route entries:

```php
Route::prefix('test-fill-text')->name('test_fill_text.')->group(function () {
    Route::get('/', [TestFillTextController::class, 'showForm'])->name('form');
    Route::post('/process', [TestFillTextController::class, 'process'])->name('process');
    Route::post('/apply', [TestFillTextController::class, 'apply'])->name('apply');
});
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
getComponentNamesForAccount('2010000005'):
  - Parse ledger file (XlsxReader, readDataOnly=true)
  - Filter rows where GL Entry = 2010000005
  - Extract Component text, bersihkan leading dash
  → ['Denda Indisipliner', 'Potongan Denda', 'Potongan Lainnya', ...]
        │
        ▼
getTargetRowsForAccount('2010000005'):
  - Parse target file
  - Filter rows where Account = 2010000005 (preserve order)
  → [Row128, Row129, ..., Row136, Row157]
        │
        ▼
Match by position:
  Row128 → component[0] = 'Denda Indisipliner'       → 'Uang Titipan - Denda Indisipliner'
  Row129 → component[1] = 'Potongan Denda'            → 'Uang Titipan - Potongan Denda'
  Row130 → component[2] = 'Potongan Lainnya'          → 'Uang Titipan - Potongan Lainnya'
  Row131 → component[3] = 'Potongan Denda Terlambat'  → 'Uang Titipan - Potongan Denda Terlambat'
  Row132 → component[4] = 'Potongan Indisipliner'     → 'Uang Titipan - Potongan Indisipliner'
  Row133 → component[5] = 'Potongan Lain-lain'        → 'Uang Titipan - Potongan Lain-lain'
  Row134 → component[6] = 'Potongan Kelalaian'        → 'Uang Titipan - Potongan Kelalaian'
  Row135 → component[7] = 'Potongan Koperasi'         → 'Uang Titipan - Potongan Koperasi'
  Row136 → component[8] = 'Potongan Denda Sakit'      → 'Uang Titipan - Potongan Denda Sakit'
  Row157 → component[9] = (none)                      → (manual edit)
        │
        ▼
Session: store matched + target path
        │
        ▼
View: tabel preview (9 rows matched + 1 unmatched, editable text inputs)
```

### 4.2 Apply & Download

```
User edits labels (optional) → klik "Apply & Download"
        │
        ▼
Copy target file → temp
        │
        ▼
For each matched row:
  sheet->setCellValue(TextCol . excelRow, label)
        │
        ▼
Save & download → "Test_Filled_Upload_GL_STAFF_KMI_2026_05.xlsx"
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

### 5.2 PhpSpreadsheet Workarounds

- File target menghasilkan `Undefined array key 141` warning internal → gunakan `@` supresi
- Gunakan `setReadDataOnly(true)` untuk skip shared string processing
- Gunakan `getHighestDataRow()` bukan `getHighestRow()` untuk hindari loop ke jutaan baris
- Component name dari ledger file memiliki leading `" - "` → `trim($name, " -")`

---

## 6. File yang Diubah/Dibuat

| File | Status | Keterangan |
|------|--------|------------|
| `app/Http/Controllers/TestFillTextController.php` | **BARU** | 240 baris, 4 public + 6 private methods |
| `resources/views/test_fill_text/form.blade.php` | **BARU** | Form upload dengan 2 file input |
| `resources/views/test_fill_text/result.blade.php` | **BARU** | Preview + edit + download |
| `routes/web.php` | **DIUBAH** | +1 import, +3 route entries |
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
  Row 128 → 'Uang Titipan - Denda Indisipliner'
  Row 129 → 'Uang Titipan - Potongan Denda'
  Row 130 → 'Uang Titipan - Potongan Lainnya'
  Row 131 → 'Uang Titipan - Potongan Denda Terlambat'
  Row 132 → 'Uang Titipan - Potongan Indisipliner'
  Row 133 → 'Uang Titipan - Potongan Lain-lain'
  Row 134 → 'Uang Titipan - Potongan Kelalaian'
  Row 135 → 'Uang Titipan - Potongan Koperasi'
  Row 136 → 'Uang Titipan - Potongan Denda Sakit'
  Row 157 → (unmatched, needs manual edit)
```

---

## 8. Rencana Selanjutnya

### 8.1 Jangka Pendek
- Uji coba dengan berbagai file Ledger Mapping (entity/profile berbeda)
- Verifikasi akurasi positional matching untuk berbagai strategy extraction (A, B, C, D, E, F)
- Handle edge case: jumlah target rows ≠ jumlah ledger entries

### 8.2 Jangka Panjang (jika test page disetujui jadi fitur permanen)
- Integrasi ke Flow Fill Text yang sudah ada (FillTextService + FillTextController)
- Buat migration + model `gl_subtype_labels` untuk persistent storage
- Seeder data untuk semua account yang perlu subtype labeling
- Auto-detect strategy extraction: hanya terapkan untuk Strategy A & B
