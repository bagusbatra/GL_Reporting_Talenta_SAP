@extends('layouts.app')

@section('title', 'Help & Documentation')

@section('content')

<div class="max-w-5xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Help & Documentation</h1>
        <p class="text-sm text-slate-500 mt-2">Panduan lengkap untuk sistem <strong>GL Reporting Talenta SAP</strong> — dokumentasi ini ditujukan untuk developer/IT yang mengelola sistem ini.</p>
    </div>

    <!-- Quick Navigation -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 mb-8">
        <p class="text-xs font-semibold text-slate-500 uppercase mb-2">Quick Navigation</p>
        <div class="flex flex-wrap gap-2 text-sm">
            <a href="#overview" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">1. Overview</a>
            <a href="#startup" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">2. Startup Harian</a>
            <a href="#workflow" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">3. Workflow Bulanan</a>
            <a href="#entities" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">4. Entity & Strategy</a>
            <a href="#strategies" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">5. 6 Strategy</a>
            <a href="#folders" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">6. Folder Structure</a>
            <a href="#troubleshooting" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">7. Troubleshooting</a>
            <a href="#commands" class="px-3 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100">8. Command Reference</a>
        </div>
    </div>

    <!-- 1. Overview -->
    <section id="overview" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">1. Overview</h2>

        <div class="bg-white border border-slate-200 rounded-lg p-5 space-y-3 text-sm text-slate-700">
            <p><strong>GL Reporting Talenta SAP</strong> adalah sistem yang menarik data payroll dari Talenta (HR system) dan mengkonversinya ke format Excel siap-upload SAP.</p>

            <p>Sistem ini menggantikan 13 standalone Python scripts (6 Semarang + 7 Surabaya) yang sebelumnya dijalankan manual.</p>

            <p><strong>Arsitektur:</strong></p>
            <ul class="list-disc pl-6 space-y-1">
                <li><strong>Laravel</strong> sebagai UI & business logic (workflow, mapping editor, validator, fill text)</li>
                <li><strong>2 Python Flask services</strong> sebagai engine extraction (Semarang & Surabaya)</li>
                <li><strong>MySQL</strong> sebagai database (mapping, history, knowledge base)</li>
            </ul>

            <p><strong>Fitur utama:</strong></p>
            <ul class="list-disc pl-6 space-y-1">
                <li>Run Extraction — tarik data Talenta lalu format ke SAP layout</li>
                <li>Mapping Editor — atur cara mapping account & cost center</li>
                <li>Validator — bandingkan hasil generate dengan file asli Talenta untuk verifikasi</li>
                <li>Fill Text — auto-isi kolom Text dengan deskripsi (auto-learn)</li>
                <li>Text References — knowledge base text yang tumbuh seiring pemakaian</li>
                <li>Reset Center — tool untuk cleanup data sistem (PIN-protected)</li>
            </ul>
        </div>
    </section>

    <!-- 2. Startup Harian -->
    <section id="startup" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">2. Startup Harian</h2>

        <div class="bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4 mb-4">
            <p class="text-sm text-amber-800">
                <strong>⚠️ PENTING:</strong> Sebelum pakai sistem, <strong>3 service harus jalan</strong>: Laravel + Python Semarang + Python Surabaya.
                Kalau salah satu mati, akan ada error saat Run Extraction.
            </p>
        </div>

        <ol class="space-y-3 text-sm text-slate-700">
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                <div class="flex-1">
                    <strong>Nyalakan XAMPP</strong> (Apache + MySQL) — sebagai database backbone
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                <div class="flex-1">
                    <strong>Nyalakan Python Semarang service</strong> — buka terminal pertama, navigate ke root project, lalu:
                    <pre class="bg-slate-900 text-slate-100 p-2 rounded mt-1 text-xs font-mono">python python_semarang.py</pre>
                    Harus muncul: <code class="bg-slate-100 px-1 rounded">Running on http://0.0.0.0:8091</code>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                <div class="flex-1">
                    <strong>Nyalakan Python Surabaya service</strong> — buka terminal kedua (jangan tutup terminal 1), lalu:
                    <pre class="bg-slate-900 text-slate-100 p-2 rounded mt-1 text-xs font-mono">python python_surabaya.py</pre>
                    Harus muncul: <code class="bg-slate-100 px-1 rounded">Running on http://0.0.0.0:8092</code>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                <div class="flex-1">
                    <strong>Nyalakan Laravel</strong> — buka terminal ketiga:
                    <pre class="bg-slate-900 text-slate-100 p-2 rounded mt-1 text-xs font-mono">php artisan serve</pre>
                    Harus muncul: <code class="bg-slate-100 px-1 rounded">Server running on [http://127.0.0.1:8000]</code>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center text-xs font-bold">5</span>
                <div class="flex-1">
                    <strong>Buka browser</strong>: <a href="http://localhost:8000" target="_blank" class="text-blue-600 hover:underline">http://localhost:8000</a>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-7 h-7 bg-slate-900 text-white rounded-full flex items-center justify-center text-xs font-bold">6</span>
                <div class="flex-1">
                    <strong>Verify health Python services</strong> — di Laravel, ke menu Run → klik <strong>Cek Health Services</strong> di pojok kanan. Harus tampil 2 service "OK" hijau.
                </div>
            </li>
        </ol>
    </section>

    <!-- 3. Workflow Bulanan -->
    <section id="workflow" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">3. Workflow Bulanan</h2>

        <p class="text-sm text-slate-600 mb-4">Berikut workflow standar untuk generate file GL Reporting tiap bulan, per entity:</p>

        <div class="space-y-4">

            <!-- Step A -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">A. Run Extraction</h3>
                <p class="text-sm text-slate-600 mb-2">Menu: <strong>Run</strong> → Pilih Entity → Pilih Periode → Pilih Profile (biasanya "Default") → Klik <strong>Run Extraction</strong></p>
                <p class="text-sm text-slate-600">Sistem akan: panggil Talenta API → fetch payroll data → format ke SAP layout (20 kolom) → save ke <code class="bg-slate-100 px-1 rounded">storage/app/gl_outputs/</code></p>
                <p class="text-xs text-slate-500 mt-2">⏱️ Waktu: ~10-30 detik per entity, tergantung jumlah karyawan</p>
            </div>

            <!-- Step B -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">B. Validate (Verify dengan File Asli)</h3>
                <p class="text-sm text-slate-600 mb-2">Menu: <strong>Validator</strong> → Pilih Run hasil step A → Upload file Excel <strong>asli dari Talenta</strong> → Klik <strong>Validate</strong></p>
                <p class="text-sm text-slate-600">Sistem akan: parse file asli + file generate → bandingkan per group (Account + D/C) → tampilkan match/mismatch</p>
                <p class="text-xs text-slate-500 mt-2">
                    ✅ <strong>PERFECT MATCH</strong> = lanjut ke Step C<br>
                    ⚠️ <strong>MISMATCH</strong> = lihat detail di tabel, jangan lanjut sebelum diselidiki & dibenerin
                </p>
            </div>

            <!-- Step C -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">C. Fill Text (Auto-isi Kolom Text)</h3>
                <p class="text-sm text-slate-600 mb-2">Menu: <strong>Fill Text</strong> → Pilih Run yang udah validate → (Opsional) Upload file referensi → Klik <strong>Run Fill Text</strong></p>
                <p class="text-sm text-slate-600 mb-2">Sistem akan auto-isi kolom Text dengan format: <code class="bg-slate-100 px-1 rounded">{Prefix Account} - {CC Description}</code></p>
                <p class="text-sm text-slate-600">Kalau ada row yang belum bisa di-fill otomatis, akan muncul di form Input Manual dengan 3 status:</p>
                <ul class="list-disc pl-6 mt-2 text-sm text-slate-600 space-y-1">
                    <li><span class="badge bg-amber-100 text-amber-700">NEED FILL</span> = CC code belum ada di master</li>
                    <li><span class="badge bg-orange-100 text-orange-700">LEGACY</span> = format text lama (perlu update)</li>
                    <li><span class="badge bg-red-100 text-red-700">?? Account</span> = account belum punya prefix</li>
                </ul>
                <p class="text-sm text-slate-600 mt-2">Isi Prefix Account + CC Description → klik <strong>Save & Update File</strong>. Sistem akan auto-save ke 3 tabel master dan auto-update file Excel.</p>
            </div>

            <!-- Step D -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">D. Download & Upload SAP</h3>
                <p class="text-sm text-slate-600 mb-2">Setelah semua text terisi (banner hijau "SEMUA TEXT TERISI"), klik <strong>Download Filled Excel</strong></p>
                <p class="text-sm text-slate-600">File hasil download siap di-upload ke SAP via T-Code FB60/FB70 (atau sesuai SOP perusahaan).</p>
            </div>
        </div>
    </section>

    <!-- 4. Entity Reference -->
    <section id="entities" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">4. Entity Reference</h2>

        <p class="text-sm text-slate-600 mb-3">Sistem menangani <strong>13 entity</strong> total (6 Semarang + 7 Surabaya):</p>

        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Entity Name</th>
                        <th class="px-4 py-2 text-center">Strategy</th>
                        <th class="px-4 py-2 text-center">Region</th>
                        <th class="px-4 py-2 text-center">Branch</th>
                        <th class="px-4 py-2 text-left">Multi-Ledger</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr><td class="px-4 py-2 text-slate-500">1</td><td class="px-4 py-2 font-medium">CS Semarang</td><td class="px-4 py-2 text-center"><span class="badge bg-blue-100 text-blue-700">A</span></td><td class="px-4 py-2 text-center">Semarang</td><td class="px-4 py-2 text-center font-mono">21090</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">2</td><td class="px-4 py-2 font-medium">Driver Semarang</td><td class="px-4 py-2 text-center"><span class="badge bg-blue-100 text-blue-700">A</span></td><td class="px-4 py-2 text-center">Semarang</td><td class="px-4 py-2 text-center font-mono">21090</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">3</td><td class="px-4 py-2 font-medium">Pembantu Semarang</td><td class="px-4 py-2 text-center"><span class="badge bg-blue-100 text-blue-700">A</span></td><td class="px-4 py-2 text-center">Semarang</td><td class="px-4 py-2 text-center font-mono">21090</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">4</td><td class="px-4 py-2 font-medium">Non-Staff Semarang</td><td class="px-4 py-2 text-center"><span class="badge bg-green-100 text-green-700">B</span></td><td class="px-4 py-2 text-center">Semarang</td><td class="px-4 py-2 text-center font-mono">21090</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">5</td><td class="px-4 py-2 font-medium">Staff Semarang</td><td class="px-4 py-2 text-center"><span class="badge bg-green-100 text-green-700">B</span></td><td class="px-4 py-2 text-center">Semarang</td><td class="px-4 py-2 text-center font-mono">21090</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">6</td><td class="px-4 py-2 font-medium">Produksi Semarang</td><td class="px-4 py-2 text-center"><span class="badge bg-purple-100 text-purple-700">C</span></td><td class="px-4 py-2 text-center">Semarang</td><td class="px-4 py-2 text-center font-mono">21090</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr class="bg-slate-50"><td colspan="6" class="px-4 py-1 text-xs font-semibold text-slate-600">— Surabaya —</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">7</td><td class="px-4 py-2 font-medium">Driver KMI</td><td class="px-4 py-2 text-center"><span class="badge bg-green-100 text-green-700">B</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21089</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">8</td><td class="px-4 py-2 font-medium">KMI2</td><td class="px-4 py-2 text-center"><span class="badge bg-green-100 text-green-700">B</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21089</td><td class="px-4 py-2 font-mono text-xs">900-905</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">9</td><td class="px-4 py-2 font-medium">KMI1</td><td class="px-4 py-2 text-center"><span class="badge bg-amber-100 text-amber-700">E</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21087</td><td class="px-4 py-2 font-mono text-xs">900-903</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">10</td><td class="px-4 py-2 font-medium">Pembantu KMI</td><td class="px-4 py-2 text-center"><span class="badge bg-rose-100 text-rose-700">F</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21089</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">11</td><td class="px-4 py-2 font-medium">Karyawan Harian Lepas</td><td class="px-4 py-2 text-center"><span class="badge bg-red-100 text-red-700">D</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21089</td><td class="px-4 py-2 text-slate-400">-</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">12</td><td class="px-4 py-2 font-medium">Non-Staff KMI</td><td class="px-4 py-2 text-center"><span class="badge bg-red-100 text-red-700">D</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21089</td><td class="px-4 py-2 font-mono text-xs">900-903</td></tr>
                    <tr><td class="px-4 py-2 text-slate-500">13</td><td class="px-4 py-2 font-medium">Staff KMI</td><td class="px-4 py-2 text-center"><span class="badge bg-red-100 text-red-700">D</span></td><td class="px-4 py-2 text-center">Surabaya</td><td class="px-4 py-2 text-center font-mono">21089</td><td class="px-4 py-2 font-mono text-xs">900-903</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- 5. Strategies -->
    <section id="strategies" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">5. 6 Strategy Penjelasan</h2>

        <div class="space-y-3">
            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="badge bg-blue-100 text-blue-700 mr-3 mt-0.5">A</span>
                    <div>
                        <h4 class="font-bold text-slate-900">Aggregate-only, Fixed Cost Center</h4>
                        <p class="text-sm text-slate-600 mt-1">Tipe sederhana: semua komponen di-aggregate (jumlahkan), pakai 1 cost center fixed dari mapping. Cocok untuk entity dengan struktur konstan (CS, Driver, Pembantu Semarang).</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="badge bg-green-100 text-green-700 mr-3 mt-0.5">B</span>
                    <div>
                        <h4 class="font-bold text-slate-900">Hybrid Cost-center + Aggregate (Keyword Matching)</h4>
                        <p class="text-sm text-slate-600 mt-1">Campuran: ada komponen yang per-cost-center (Gaji, Lembur), ada yang aggregate (Hutang BPJS). Sistem match keyword di nama akun untuk decide. Cocok untuk Staff/Non-Staff (Semarang + Surabaya), Driver KMI, KMI2.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="badge bg-purple-100 text-purple-700 mr-3 mt-0.5">C</span>
                    <div>
                        <h4 class="font-bold text-slate-900">Individual Per-Detail, No Aggregation</h4>
                        <p class="text-sm text-slate-600 mt-1">Semua row dari Talenta dipindah 1:1 ke output (tidak di-aggregate). Cocok untuk Produksi Semarang yang butuh detail per item.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="badge bg-red-100 text-red-700 mr-3 mt-0.5">D</span>
                    <div>
                        <h4 class="font-bold text-slate-900">Auto-Detect D/C, No Mapping</h4>
                        <p class="text-sm text-slate-600 mt-1">Tidak pakai mapping rules. Sistem auto-detect D/C dari pattern Talenta:</p>
                        <ul class="list-disc pl-5 mt-1 text-sm text-slate-600 space-y-0.5">
                            <li>Account di whitelist OR keyword "pengembalian" → <strong>Debit</strong></li>
                            <li>Selain itu → <strong>Credit</strong></li>
                        </ul>
                        <p class="text-sm text-slate-600 mt-1">Konfigurasi via <code class="bg-slate-100 px-1 rounded">gl_strategy_d_configs</code>. Cocok untuk Karyawan Harian Lepas, Non-Staff KMI, Staff KMI.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="badge bg-amber-100 text-amber-700 mr-3 mt-0.5">E</span>
                    <div>
                        <h4 class="font-bold text-slate-900">Mapping Ordered, Preserve API Sequence</h4>
                        <p class="text-sm text-slate-600 mt-1">Pakai mapping rules (mirip B), tapi urutan output mengikuti urutan API Talenta (bukan urutan mapping_key). Cocok untuk KMI1 yang sensitive terhadap urutan posting.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <div class="flex items-start">
                    <span class="badge bg-rose-100 text-rose-700 mr-3 mt-0.5">F</span>
                    <div>
                        <h4 class="font-bold text-slate-900">Per-Detail Include-Zero</h4>
                        <p class="text-sm text-slate-600 mt-1">Mirip C (per detail) tapi mempertahankan row dengan amount = 0 (tidak di-skip). Cocok untuk Pembantu KMI yang butuh placeholder rows.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Folder Structure -->
    <section id="folders" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">6. Folder Structure</h2>

        <p class="text-sm text-slate-600 mb-3">File-file penting di sistem:</p>

        <div class="bg-slate-900 text-slate-100 rounded-lg p-5 font-mono text-xs leading-relaxed">
            <div>GL_Reporting_Talenta_SAP/</div>
            <div>├── <span class="text-blue-300">app/</span>                       <span class="text-slate-500"># Laravel application code</span></div>
            <div>│   ├── Http/Controllers/      <span class="text-slate-500"># 9 controllers</span></div>
            <div>│   ├── Models/                <span class="text-slate-500"># 9 Eloquent models</span></div>
            <div>│   └── Services/              <span class="text-slate-500"># 4 services (Python client, Validator, FillText, ResetCenter)</span></div>
            <div>├── <span class="text-blue-300">database/</span></div>
            <div>│   ├── migrations/            <span class="text-slate-500"># Schema (9 tables)</span></div>
            <div>│   └── seeders/               <span class="text-slate-500"># Default data (entities, mappings, prefixes, dll)</span></div>
            <div>├── <span class="text-blue-300">resources/views/</span>           <span class="text-slate-500"># Blade templates (Tailwind CDN)</span></div>
            <div>├── <span class="text-blue-300">routes/web.php</span>             <span class="text-slate-500"># All routes</span></div>
            <div>├── <span class="text-blue-300">storage/app/</span></div>
            <div>│   ├── <span class="text-yellow-300">gl_uploads/</span>            <span class="text-slate-500"># Master files (COSTCENTER_SAP.xlsx) ⚠️ JANGAN DIHAPUS</span></div>
            <div>│   ├── <span class="text-green-300">gl_outputs/</span>            <span class="text-slate-500"># Hasil Python (raw, before fill text)</span></div>
            <div>│   ├── <span class="text-green-300">gl_filled/</span>             <span class="text-slate-500"># Hasil Fill Text (siap upload SAP)</span></div>
            <div>│   └── <span class="text-green-300">gl_references/</span>         <span class="text-slate-500"># File referensi yang di-upload user</span></div>
            <div>├── <span class="text-cyan-300">python_semarang.py</span>        <span class="text-slate-500"># Python service Semarang (port 8091)</span></div>
            <div>├── <span class="text-cyan-300">python_surabaya.py</span>        <span class="text-slate-500"># Python service Surabaya (port 8092)</span></div>
            <div>├── <span class="text-blue-300">.env</span>                       <span class="text-slate-500"># Talenta credentials, DB config</span></div>
            <div>└── <span class="text-blue-300">composer.json</span></div>
        </div>

        <div class="mt-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4 text-sm text-amber-800">
            <strong>⚠️ Catatan penting tentang folder:</strong>
            <ul class="list-disc pl-5 mt-2 space-y-1">
                <li><code class="bg-white px-1 rounded">gl_uploads/</code> berisi file master COSTCENTER_SAP.xlsx — <strong>JANGAN hapus folder atau file ini</strong>. Kalau hilang, seeder Cost Center tidak akan jalan.</li>
                <li>3 folder lain (<code class="bg-white px-1 rounded">gl_outputs</code>, <code class="bg-white px-1 rounded">gl_filled</code>, <code class="bg-white px-1 rounded">gl_references</code>) berisi data hasil pemakaian — boleh dihapus untuk cleanup.</li>
                <li>Reset Center (PIN 2026) bisa cleanup ke-3 folder ini secara otomatis.</li>
            </ul>
        </div>
    </section>

    <!-- 7. Troubleshooting -->
    <section id="troubleshooting" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">7. Troubleshooting</h2>

        <p class="text-sm text-slate-600 mb-4">Issue umum yang pernah terjadi dan cara handle-nya:</p>

        <div class="space-y-4">

            <!-- Error 1 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">❌ Error: cURL error 28 (timeout)</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Saat:</strong> Run Extraction</p>
                <p class="text-sm text-slate-600 mb-2"><strong>Penyebab:</strong> Python service gagal connect ke <code class="bg-slate-100 px-1 rounded">login.microsoftonline.com</code> (untuk authentication ke Talenta) atau ke Talenta API itu sendiri. Biasanya karena:</p>
                <ul class="list-disc pl-5 text-sm text-slate-600 space-y-1">
                    <li>Koneksi internet lambat</li>
                    <li>Firewall/proxy memblok</li>
                    <li>DNS resolution gagal</li>
                </ul>
                <p class="text-sm text-slate-700 mt-2"><strong>Solusi:</strong></p>
                <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-1">
                    <li>Cek koneksi internet (ping <code class="bg-slate-100 px-1 rounded">login.microsoftonline.com</code>)</li>
                    <li>Restart Python services kedua-duanya</li>
                    <li>Run ulang. Biasanya sukses di percobaan kedua/ketiga.</li>
                    <li>Kalau persisten, cek <code class="bg-slate-100 px-1 rounded">.env</code> apakah <code class="bg-slate-100 px-1 rounded">TALENTA_CLIENT_ID</code> dan <code class="bg-slate-100 px-1 rounded">TALENTA_CLIENT_SECRET</code> masih valid.</li>
                </ol>
            </div>

            <!-- Error 2 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">❌ Error: Connection refused / 8091 / 8092</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Saat:</strong> Run Extraction atau Cek Health</p>
                <p class="text-sm text-slate-600 mb-2"><strong>Penyebab:</strong> Python service tidak jalan.</p>
                <p class="text-sm text-slate-700 mt-2"><strong>Solusi:</strong></p>
                <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-1">
                    <li>Cek terminal Python service. Apakah masih running atau sudah ke-close?</li>
                    <li>Kalau ke-close, run ulang: <code class="bg-slate-100 px-1 rounded">python python_semarang.py</code> dan <code class="bg-slate-100 px-1 rounded">python python_surabaya.py</code></li>
                    <li>Kalau port-nya bentrok dengan aplikasi lain, ubah port di file Python (cari <code class="bg-slate-100 px-1 rounded">port=8091</code> atau <code class="bg-slate-100 px-1 rounded">port=8092</code>) + update <code class="bg-slate-100 px-1 rounded">config/services.php</code> di Laravel</li>
                </ol>
            </div>

            <!-- Error 3 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">❌ Error: 405 Method Not Allowed</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Saat:</strong> Refresh halaman di Laravel</p>
                <p class="text-sm text-slate-600 mb-2"><strong>Penyebab:</strong> User refresh halaman setelah submit form POST (browser coba GET URL yang harusnya POST-only).</p>
                <p class="text-sm text-slate-700 mt-2"><strong>Solusi:</strong> Jangan refresh setelah submit. Kalau perlu, navigasi ulang dari menu. Sistem sudah pakai PRG pattern untuk endpoint utama, tapi beberapa endpoint masih POST-only by design.</p>
            </div>

            <!-- Error 4 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">❌ Error: File COSTCENTER_SAP.xlsx tidak ditemukan</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Saat:</strong> Run seeder Cost Center atau Reset Center "Reset & Re-seed Cost Centers"</p>
                <p class="text-sm text-slate-600 mb-2"><strong>Penyebab:</strong> File master tidak ada di <code class="bg-slate-100 px-1 rounded">storage/app/gl_uploads/COSTCENTER_SAP.xlsx</code></p>
                <p class="text-sm text-slate-700 mt-2"><strong>Solusi:</strong></p>
                <ol class="list-decimal pl-5 text-sm text-slate-600 space-y-1">
                    <li>Copy file COSTCENTER_SAP.xlsx ke folder <code class="bg-slate-100 px-1 rounded">storage/app/gl_uploads/</code></li>
                    <li>Pastikan nama file persis: <strong>COSTCENTER_SAP.xlsx</strong> (case-sensitive di Linux, hati-hati dengan underscore vs spasi)</li>
                    <li>Re-run seeder: <code class="bg-slate-100 px-1 rounded">php artisan db:seed --class=GlCostCenterSeeder</code></li>
                </ol>
            </div>

            <!-- Error 5 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">❌ Validator MISMATCH (1-2 row beda)</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Saat:</strong> Validate hasil generate</p>
                <p class="text-sm text-slate-600 mb-2"><strong>Penyebab umum:</strong></p>
                <ul class="list-disc pl-5 text-sm text-slate-600 space-y-1">
                    <li>Ada komponen baru di Talenta yang belum di-mapping → cek section "Account Issues" di hasil validator</li>
                    <li>Ada account yang amount-nya 0 di gen tapi tidak di asli (pre-init mapping) — biasanya OK, abaikan</li>
                    <li>Salah upload file asli (bukan untuk entity/periode yang sama) — cek warning "PERINGATAN: KEMUNGKINAN FILE SALAH"</li>
                </ul>
                <p class="text-sm text-slate-700 mt-2"><strong>Solusi:</strong> Baca section "Account Issues" dan tabel "Per Group Comparison". Kalau emang ada mapping baru yang perlu, edit di Mapping Editor (duplicate Default profile dulu).</p>
            </div>

            <!-- Error 6 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">❌ Text di Excel masih format lama (e.g. "Lembur 1111050000")</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Saat:</strong> Lihat hasil Fill Text</p>
                <p class="text-sm text-slate-600 mb-2"><strong>Penyebab:</strong> Knowledge base masih punya entries format lama (sebelum upgrade ke format dash).</p>
                <p class="text-sm text-slate-700 mt-2"><strong>Solusi:</strong> Sistem sekarang punya <strong>auto-detect LEGACY format</strong>. Run Fill Text, lalu di form Input Manual akan muncul row dengan badge <span class="badge bg-orange-100 text-orange-700">LEGACY</span>. Prefix akan auto-fill. Tinggal isi CC Description → Save. Knowledge base akan ke-update otomatis.</p>
            </div>

            <!-- Error 7 -->
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="font-bold text-slate-900 mb-2">⚠️ Lupa PIN Reset Center</h3>
                <p class="text-sm text-slate-600 mb-2"><strong>Default PIN:</strong> <code class="bg-slate-100 px-1 rounded">2026</code></p>
                <p class="text-sm text-slate-700 mt-2"><strong>Cara ganti/cek PIN via tinker:</strong></p>
                <pre class="bg-slate-900 text-slate-100 p-3 rounded mt-1 text-xs font-mono leading-relaxed">php artisan tinker

// Cek PIN saat ini:
echo App\Models\GlSystemSetting::getValue('reset_center_pin');

// Ganti PIN:
App\Models\GlSystemSetting::setValue('reset_center_pin', 'PIN_BARU');

exit</pre>
            </div>

        </div>
    </section>

    <!-- 8. Command Reference -->
    <section id="commands" class="mb-10 scroll-mt-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-3 pb-2 border-b border-slate-200">8. Command Reference</h2>

        <p class="text-sm text-slate-600 mb-3">Command yang sering dipakai untuk maintenance sistem:</p>

        <div class="space-y-3">

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-slate-900 mb-1">Cek health sistem (tinker)</p>
                <pre class="bg-slate-900 text-slate-100 p-2 rounded text-xs font-mono">php artisan tinker</pre>
                <p class="text-xs text-slate-500 mt-1">Lalu jalankan query Eloquent untuk inspect data.</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-slate-900 mb-1">Clear cache (kalau ada perubahan kode)</p>
                <pre class="bg-slate-900 text-slate-100 p-2 rounded text-xs font-mono">php artisan view:clear
php artisan route:clear
php artisan config:clear</pre>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-slate-900 mb-1">List semua routes</p>
                <pre class="bg-slate-900 text-slate-100 p-2 rounded text-xs font-mono">php artisan route:list</pre>
                <p class="text-xs text-slate-500 mt-1">Filter spesifik: <code class="bg-slate-100 px-1 rounded">php artisan route:list | findstr fill-text</code></p>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-slate-900 mb-1">Re-seed master data</p>
                <pre class="bg-slate-900 text-slate-100 p-2 rounded text-xs font-mono">php artisan db:seed --class=GlEntitySeeder
php artisan db:seed --class=GlAccountPrefixSeeder
php artisan db:seed --class=GlCostCenterSeeder
php artisan db:seed --class=GlSemarangMappingSeeder
php artisan db:seed --class=GlSurabayaMappingSeeder
php artisan db:seed --class=GlSystemSettingSeeder</pre>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-slate-900 mb-1">Total reset database (HATI-HATI)</p>
                <pre class="bg-slate-900 text-slate-100 p-2 rounded text-xs font-mono">php artisan migrate:fresh --seed</pre>
                <p class="text-xs text-red-600 mt-1">⚠️ Drop semua tabel + re-create + re-seed. Backup database dulu!</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-4">
                <p class="text-sm font-semibold text-slate-900 mb-1">Cleanup test data (via UI)</p>
                <p class="text-sm text-slate-600">Menu <strong>⚠️ Reset</strong> di navbar → input PIN 2026 → pilih section yang mau di-reset.</p>
                <p class="text-xs text-slate-500 mt-1">Lebih aman daripada SQL manual karena auto re-seed master data.</p>
            </div>

        </div>
    </section>

    <!-- Footer Note -->
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-5 text-sm text-slate-600 mb-6">
        <p>
            <strong>Catatan akhir:</strong> Sistem ini dirancang untuk <strong>self-service maintenance</strong>. Mayoritas operasi (cleanup, reset, mapping update, knowledge base growth) bisa dilakukan via UI tanpa perlu masuk database/code.
        </p>
        <p class="mt-2">
            Kalau ada bug atau enhancement yang dibutuhkan, edit code-nya langsung — semua logic terdokumentasi di service files (<code class="bg-white px-1 rounded">app/Services/</code>) dan blade views (<code class="bg-white px-1 rounded">resources/views/</code>).
        </p>
    </div>

</div>

@endsection