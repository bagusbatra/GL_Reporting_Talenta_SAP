# Fitur Fill Text — Breakdown Perilaku Account 2010000005 vs Account Lain

## 1. Akar Masalah: Prefix di Database

File `database/seeders/GlAccountPrefixSeeder.php:14-15` mendefinisikan:

```php
'2010000004' => 'Hutang BPJS',
'2010000005' => 'Uang Titipan',   // <— prefix untuk 2010000005
'2010000006' => 'Uang Pengembalian Jaminan Tools',
'5204000001' => 'Gaji',
'5204000009' => 'Gaji',
// ... dst
```

Semua akun punya prefix masing-masing. Khusus `2010000005` prefix-nya **"Uang Titipan"**.

---

## 2. Logika `generateText()` — `app/Services/FillTextService.php:172-218`

```
generateText(account, cc, referenceMap, prefixMap, ccMap, ccNameMap):

  1. Ada reference di GlTextReference untuk (account + cc)?
     ├─ Ya → apakah isProblemText?    → Ya: return legacy/no_prefix
     │                                 → Tidak: return reference text (source: 'reference')
     └─ Tidak → lanjut

  2. Cari prefix dari GlAccountPrefix
     ├─ Tidak ditemukan → return "?? Account-Belum-Prefix"  (source: 'no_prefix')
     └─ Ditemukan → lanjut

  3. Apakah cost_center (cc) kosong?
     ├─ Ya (empty $cc) → return prefix saja           (source: 'prefix_only')
     │                  Contoh: "Uang Titipan"
     └─ Tidak → lanjut

  4. Cari ccDescription dari GlCostCenter
     ├─ Ditemukan → return "prefix - ccDesc"           (source: 'prefix_with_cc')
     │              Contoh: "Gaji - KARYAWAN TETAP"
     └─ Tidak ditemukan → return "prefix (NEED FILL)"  (source: 'no_cc_desc')
                          Contoh: "Gaji (NEED FILL)"
```

**Keputusan final ditentukan oleh ada/tidaknya cost center di baris Excel.**

---

## 3. Kenapa 2010000005 Hanya Menghasilkan "Uang Titipan"?

Jawabannya: **di extraction strategy, mapping 2010000005 tidak pernah menghasilkan cost center di Excel.**

### a. Strategy A (Aggregate-only with fixed CC) — CS Semarang, Driver

Lihat `python_semarang.py:150-163`:

```python
# Strategy A: entry builder
entry = {
    'cost_center': m['cost_center'] if not m['use_profit_center'] else '',
    'profit_center': m['profit_center'] if m['use_profit_center'] else '',
}
```

Mapping dengan `use_profit_center: true` → cost_center dikosongkan.

Di seeder `GlSemarangMappingSeeder.php`, semua mapping `2010000005` punya:
```php
'use_profit_center' => true,    // → cost_center = ''
'profit_center' => '200301',
```

**Hasil di Excel → kolom Cost Center = kosong** → `generateText()` masuk ke **step 3 (cc empty)** → return `"Uang Titipan"` (prefix_only).

### b. Strategy B (Hybrid Cost Center + Aggregate) — Non Staff, Staff

Lihat `python_semarang.py:254-261`:

```python
# Strategy B: Aggregate type
if m['account_type'] == 'Aggregate':
    ...
    entries.append({
        'cost_center': '',                          # ← selalu kosong
        'profit_center': m['profit_center'] if m['use_profit_center'] else '',
    })
```

Semua mapping `2010000005` bertipe **Aggregate**. Cost center diisi string kosong.

Sementara mapping Debit (5204000001, 5204000009) bertipe **Cost center**:

```python
# Strategy B: Cost center type
for r in rows:
    entries.append({
        'cost_center': r['cost_center_code'],       # ← ada isi dari API
    })
```

Mereka mendapat cost center asli dari Talenta (misal `1094020002`).

### c. Strategy C (Individual) — Produksi Semarang

Lihat `python_semarang.py:333-339`:

```python
# Strategy C
if cc:
    cost_center_display = cc
else:
    cost_center_display = ''           # ← ketika API tidak return CC
    profit_center = '200301'
```

Mapping `2010000005` di produksi (baris 197): semua detail tidak punya cost center → `cost_center_display = ''`.

### d. Strategy E (Preserved API order) — Surabaya

`python_surabaya.py:348-358` — Aggregate type di strategy E:

```python
entries.append({
    'cost_center': '',
    'profit_center': '200301',
})
```

Sama: cost center selalu kosong.

### e. Strategy D (Auto-detect) — Surabaya

`python_surabaya.py:293-303` — Aggregate auto-detect:

```python
entries.append({
    'cost_center': '',
    'profit_center': '200301',
})
```

Cost center kosong.

---

## 4. Perbandingan Langsung

| Aspek | Account 2010000005 | Account Lain (5204000001, 5204000009, dll) |
|-------|-------------------|-------------------------------------------|
| **Prefix** | `"Uang Titipan"` | `"Gaji"`, `"Lembur"`, `"Jamsostek"`, dll |
| **Account Type di Mapping** | Hampir selalu **Aggregate** atau **Individual** | Banyak yang **Cost center** (terutama di Strategy B) |
| **use_profit_center** | `true` — cost center dikosongkan | `false` — cost center dari mapping atau dari API |
| **Cost Center di Excel** | **Kosong** (kolom Cost Center = blank) | **Terisi** (misal `1094020002`, `1042010002`) |
| **Alur generateText()** | cc empty → step 3 → return **prefix saja** | cc terisi → step 4 → lookup ccDesc |
| **Text Output (jika ccDesc ada)** | `"Uang Titipan"` | `"Gaji - KARYAWAN TETAP"`, `"Lembur - DRIVER"` |
| **Text Output (jika ccDesc tdk ada)** | (tidak terjadi — cc empty) | `"Gaji (NEED FILL)"` |
| **Auto-learn ke TextReference** | **Ya** (source: `auto_fill_*`) | **Ya** (source: `auto_fill_*`) |

---

## 5. Akibatnya

- **2010000005** → output selalu `"Uang Titipan"` (prefix doang, tanpa separator & cc description)
- **Akun Cost center type** → output `"Gaji - NAMA COST CENTER"` (ada separator dan deskripsi)
- **Akun Aggregate dengan fixed CC** (jarang) → mirip cost center type, ada separator

Ini bukan bug — ini desain. Karena 2010000005 menampung berbagai jenis potongan (denda, koperasi, kelalaian, dll) yang semuanya di-aggregate tanpa cost center, maka satu prefix `"Uang Titipan"` sudah cukup sebagai text. Tidak ada informasi cost center yang perlu ditambahkan.

Jika ingin text yang lebih deskriptif (misal `"Uang Titipan - Denda Sakit"`, `"Uang Titipan - Koperasi"`), perlu perubahan struktural pada:
1. Mapping strategy (mengubah tipe ke **Cost center** agar cost center dari API dipakai)
2. Atau menambahkan logika di Python strategy untuk menyisipkan informasi variant ke kolom Text
3. Atau membuat mapping 2010000005 dipisah per cost center di Talenta
