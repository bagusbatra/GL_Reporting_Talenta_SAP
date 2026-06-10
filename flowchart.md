# Flowchart — GL Reporting Talenta SAP

```mermaid
---
title: Arsitektur Sistem
---
graph TB
    subgraph Browser["🌐 Browser (User)"]
        UI["Web UI<br/>Blade Views + Tailwind CSS"]
    end

    subgraph Laravel["⚙️ Laravel Backend (PHP 8.x)"]
        direction TB
        Route["Routes: web.php<br/>41 Named Routes"]
        MW["Middleware: CSRF, Session, Web"]

        subgraph Controllers["Controllers"]
            DC["DashboardController"]
            RC["RunController"]
            MC["MappingController"]
            FC["FillTextController"]
            TFC["TestFillTextController"]
            VC["ValidatorController"]
            RCC["ResetCenterController"]
            TRC["TextReferenceController"]
            HC["HelpController"]
        end

        subgraph Services["Services"]
            PSC["PythonServiceClient<br/>HTTP Client ke Python"]
            FTS["FillTextService<br/>Generate + Fill Text"]
            VS["ValidatorService<br/>Compare Asli vs Generate"]
            RSC["ResetCenterService<br/>Truncate + Re-seed"]
        end

        subgraph Models["Eloquent Models"]
            M1["GlEntity"]
            M2["GlMappingProfile"]
            M3["GlAccountMapping"]
            M4["GlStrategyDConfig"]
            M5["GlRunHistory"]
            M6["GlAccountPrefix"]
            M7["GlCostCenter"]
            M8["GlTextReference"]
            M9["GlSystemSetting"]
        end

        subgraph Storage["Storage"]
            S1["gl_outputs/"]
            S2["gl_filled/"]
            S3["gl_references/"]
            S4["gl_uploads/"]
            S5["test_fill_text/"]
        end
    end

    subgraph Python["🐍 Python Flask Microservices"]
        direction TB
        Py1["Semarang (port 8091)<br/>Strategies: A, B, C"]
        Py2["Surabaya (port 8092)<br/>Strategies: B, D, E, F"]
    end

    subgraph Talenta["☁️ Talenta API (Mekari)"]
        API["REST API<br/>HMAC SHA-256 Auth"]
    end

    UI -->|"HTTP Request"| Route
    Route --> Controllers
    Controllers --> Services
    Controllers --> Models
    Services --> Storage
    PSC -->|"HTTP POST /run"| Py1
    PSC -->|"HTTP POST /run"| Py2
    Py1 -->|"GET ledger/report/..."| API
    Py2 -->|"GET ledger/report/..."| API
    FTS -->|"Read/Write Excel"| Storage
    VS -->|"Read Excel"| Storage
    TFC -->|"Read/Write Excel"| Storage

    classDef laravel fill:#4f46e5,color:#fff,stroke:#3730a3
    classDef python fill:#059669,color:#fff,stroke:#047857
    classDef external fill:#d97706,color:#fff,stroke:#b45309
    classDef storage fill:#6b7280,color:#fff,stroke:#4b5563
    class DC,RC,MC,FC,TFC,VC,RCC,TRC,HC laravel
    class PSC,FTS,VS,RSC laravel
    class M1,M2,M3,M4,M5,M6,M7,M8,M9 laravel
    class Py1,Py2 python
    class API external
    class S1,S2,S3,S4,S5 storage
```

---

## 1. Run Extraction — Alur Utama

```mermaid
---
title: Run Extraction — Generate Excel GL dari Talenta
---
flowchart TD
    Start(["User buka /run"]) --> Form["Form Run Extraction<br/>Pilih Entity → Profile → Year → Month"]
    Form -->|"AJAX: GET /run/profiles/{entity}"| LoadProfiles["Load profiles untuk entity<br/>(default first, then by name)"]
    LoadProfiles --> Submit["Submit: POST /run/execute"]

    Submit --> Validate{"Validasi:<br/>entity_id required<br/>profile_id required<br/>year 2020-2030<br/>month 1-12"}
    Validate -->|❌ Invalid| FormError["Tampilkan error<br/>redirect back"]
    Validate -->|✅ Valid| CreateHistory["Buat GlRunHistory<br/>status = 'running'"]

    CreateHistory --> BuildPayload["PythonServiceClient::buildPayload()<br/>- Entity config (code, region, ledger, dll)<br/>- All active account mappings<br/>- Strategy D config (jika ada)<br/>- Period (year, month)"]

    BuildPayload -->|"POST JSON ke Python"| RouteToPython{"Entity region?"}

    RouteToPython -->|"semarang"| PySem["POST http://127.0.0.1:8091/run"]
    RouteToPython -->|"surabaya"| PySby["POST http://127.0.0.1:8092/run"]

    PySem --> FetchReports["Talenta API:<br/>GET ledger/report/history<br/>?year=&month="]
    PySby --> FetchReports

    FetchReports --> FilterReport["Filter report by ledger_code<br/>Ambil yang terbaru (created_at)"]

    FilterReport --> FetchDetail["Talenta API:<br/>GET ledger/report/{id}<br/>?ledger_id={list}"]

    FetchDetail --> RunStrategy{"Pilih Strategy Sesuai Config"}

    subgraph Strategies["Python Extraction Strategies"]
        direction TB
        SA["Strategy A<br/>Single Account Mapping<br/>- Cost center: split per CC<br/>- Aggregate: sum all"]
        SB["Strategy B<br/>Component-Based Matching<br/>- Match by components array<br/>- One entry per component per CC"]
        SC["Strategy C<br/>Variant/Keyword Matching<br/>- Match by match_keywords<br/>- First match by order_index"]
        SD["Strategy D<br/>Auto-Detect D/C<br/>- debit_accounts whitelist<br/>- debit_keywords detect<br/>- default_dc fallback"]
        SE["Strategy E<br/>Aggregate by Position<br/>- order_index → fixed position<br/>- Aggregate amounts per CC"]
        SF["Strategy F<br/>Individual with Keywords<br/>- Per-keyword group<br/>- One entry per CC"]
    end

    RunStrategy --> SA
    RunStrategy --> SB
    RunStrategy --> SC
    RunStrategy --> SD
    RunStrategy --> SE
    RunStrategy --> SF

    SA & SB & SC & SD & SE & SF --> BuildEntries["Build entries array<br/>{account_number, transaction_value, amount, cost_center, profit_center}"]

    BuildEntries --> ConvertSAP["Convert ke SAP 20 kolom<br/>via convert_to_sap_format()<br/>- Document Date: tgl akhir bulan<br/>- PstKy: 40=Debit, 50=Credit<br/>- Format Excel via openpyxl"]

    ConvertSAP --> SaveExcel["Save Excel ke storage/app/gl_outputs/"]

    SaveExcel --> Response["Return JSON ke Laravel<br/>{status, output_file, total_records,<br/>total_debit, total_credit, difference}"]

    Response --> ProcessResponse{"status = success?"}

    ProcessResponse -->|"✅ Ya"| UpdateSuccess["Update GlRunHistory:<br/>status = success<br/>output_file_path = ...<br/>totals = ..."]
    ProcessResponse -->|"❌ Tidak"| UpdateFailed["Update GlRunHistory:<br/>status = failed<br/>error_message = ..."]

    UpdateSuccess --> RedirectShow["Redirect ke /run/show/{history}"]
    UpdateFailed --> RedirectShow

    RedirectShow --> View["View: run.show<br/>- Status banner<br/>- Summary cards<br/>- Download button<br/>- Error details (jika failed)"]

    View --> Download["User klik Download<br/>GET /run/download/{history}<br/>response()->download()"]

    classDef process fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    classDef endpoint fill:#10b981,color:#fff,stroke:#059669
    classDef python fill:#059669,color:#fff,stroke:#047857
    class Validate,RouteToPython,RunStrategy,ProcessResponse decision
    class Start endpoint
    class Form,Submit,BuildPayload,FetchReports,FilterReport,FetchDetail,BuildEntries,ConvertSAP,SaveExcel,Response,UpdateSuccess,UpdateFailed,RedirectShow,Download,FormError,CreateHistory,LoadProfiles process
    class SA,SB,SC,SD,SE,SF python
```

---

## 2. Mapping Editor — CRUD Profile & Mapping

```mermaid
---
title: Mapping Editor — Kelola Mapping Profiles
---
flowchart LR
    Index["GET /mapping<br/>Daftar Entity + Profile"] --> ViewProfile["GET /mapping/profile/{profile}<br/>Lihat detail profile + daftar mapping"]

    ViewProfile --> AddRow["GET /mapping/profile/{profile}/add<br/>Form tambah mapping row"]
    ViewProfile --> EditRow["GET /mapping/{mapping}/edit<br/>Form edit mapping row"]
    ViewProfile --> Duplicate["GET /mapping/profile/{profile}/duplicate<br/>Form duplikasi profile"]
    ViewProfile --> DeleteProfile["DELETE /mapping/profile/{profile}<br/>Hapus profile + semua mapping"]

    AddRow -->|"POST /mapping/profile/{profile}/add"| Store["Validasi + Simpan mapping baru<br/>auto order_index"]
    EditRow -->|"PUT /mapping/{mapping}"| Update["Validasi + Update mapping<br/>parseLinesToArray()"]
    Duplicate -->|"POST /mapping/profile/{profile}/duplicate"| DupExec["Buat profile baru<br/>Copy semua mapping + StrategyDConfig<br/>Dalam DB transaction"]
    DeleteProfile -->|"✅ is_default = false?"| Destroy["Hapus mapping + profile<br/>(cascade)"]
    DeleteProfile -->|"❌ is_default = true"| Blocked["Dicegah!<br/>Default profile tidak bisa dihapus"]

    Store & Update & DupExec & Destroy --> Index

    classDef page fill:#6366f1,color:#fff,stroke:#4f46e5
    classDef action fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    class Index page
    class ViewProfile,AddRow,EditRow,Duplicate page
    class Store,Update,DupExec,Destroy,Blocked,DeleteProfile decision
```

---

## 3. Fill Text — Generate Label dari Knowledge Base

```mermaid
---
title: Fill Text — Isi Kolom Text Otomatis
---
flowchart TD
    Start(["User buka /fill-text"]) --> Form["Form Fill Text<br/>- 50 recent successful runs<br/>- Pilih history_id<br/>- Optional: upload reference file"]

    Form --> Submit["POST /fill-text/run"]

    Submit --> ValidateFT{"Validasi:<br/>history_id exist<br/>output_file ada?"}

    ValidateFT -->|❌ Invalid| ErrBack["redirect back with error"]
    ValidateFT -->|✅ Valid| CheckRef{"Ada reference file<br/>diupload?"}

    CheckRef -->|"Ya"| ParseRef["FillTextService::parseReferenceFile()<br/>- Baca Excel<br/>- Auto-learn setiap reference<br/>- Skip problem texts"]
    CheckRef -->|"Tidak"| LoadFill

    ParseRef --> LoadFill["FillTextService::fill()"]

    LoadFill --> BuildMaps["Build lookup maps:<br/>- referenceMap → GlTextReference<br/>- prefixMap → GlAccountPrefix<br/>- ccMap → GlCostCenter<br/>- ccNameMap → GlCostCenter"]

    BuildMaps --> LoopRows["Loop setiap row di Excel Generate"]

    LoopRows --> GenText["generateText(account, cc, maps)"]

    GenText --> Priority{"Priority Chain:"}

    Priority -->|"1. Ada reference<br/>di GlTextReference?"| RefCheck{"isProblemText?"}
    RefCheck -->|"✅ Valid"| UseRef["return reference text<br/>source: 'reference'"]
    RefCheck -->|"❌ Legacy format"| UseLegacy["return text as-is<br/>source: 'legacy'"]
    RefCheck -->|"❌ No prefix marker"| UseNoPrefix

    Priority -->|"2. No reference"| CheckPrefix{"Ada prefix<br/>di GlAccountPrefix?"}
    CheckPrefix -->|"❌ Tidak"| UseNoPrefix["return '?? Account-Belum-Prefix'<br/>source: 'no_prefix'"]
    CheckPrefix -->|"✅ Ya"| CheckCC{"Cost Center<br/>di row kosong?"}

    CheckCC -->|"✅ Kosong"| UsePrefixOnly["return prefix saja<br/>source: 'prefix_only'<br/>Contoh: 'Uang Titipan'"]
    CheckCC -->|"❌ Ada isi"| FindCCDesc{"Cari ccDescription<br/>di GlCostCenter"}

    FindCCDesc -->|"✅ Ditemukan"| UseFull["return prefix + ' - ' + ccDesc<br/>source: 'prefix_with_cc'<br/>Contoh: 'Gaji - KARYAWAN TETAP'"]
    FindCCDesc -->|"❌ Tidak"| UseNeedFill["return prefix + ' (NEED FILL)'<br/>source: 'no_cc_desc'"]

    UseRef & UseLegacy & UseNoPrefix & UsePrefixOnly & UseFull & UseNeedFill --> WriteCell["setCellValue(TextCol, text)"]

    WriteCell --> LoopEnd{"Row terakhir?"}
    LoopEnd -->|"Belum"| LoopRows
    LoopEnd -->|"Ya"| SaveFilled["Save Excel ke gl_filled/<br/>Auto-learn text baru<br/>ke GlTextReference"]

    SaveFilled --> ResultView["Redirect ke<br/>/fill-text/result-view/{history}"]

    subgraph ManualFix["Manual Fix Flow"]
        ResultView --> ScanProblems["scanProblemRows()<br/>Cari problem rows:<br/>- no_prefix<br/>- no_cc_desc<br/>- legacy"]
        ScanProblems --> ShowResult["View: fill_text.result<br/>- Stats cards<br/>- Problem rows table<br/>- Input prefix + cc description"]
        ShowResult -->|"User isi manual"| SaveManual["POST /fill-text/{history}/save-manual"]
        SaveManual --> SaveData["FillTextService::saveManualTextsAndRegenerate()"]
        SaveData --> DBUpdates["1. Save/update → gl_account_prefixes<br/>2. Save/update → gl_cost_centers<br/>3. Save → gl_text_references"]
        DBUpdates --> ExcelUpdate["4. Update Excel dengan text baru"]
        ExcelUpdate --> ShowPage["Redirect ke /fill-text/show/{history}"]
    end

    ResultView --> Download["User download<br/>GET /fill-text/download/{history}<br/>response()->download()<br/>+ cache-busting headers"]

    classDef process fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    classDef endpoint fill:#10b981,color:#fff,stroke:#059669
    class Start,Download,ErrBack endpoint
    class Form,Submit,ParseRef,LoadFill,BuildMaps,LoopRows,GenText,WriteCell,LoopEnd,SaveFilled,ResultView,ScanProblems,ShowResult,SaveManual,SaveData,DBUpdates,ExcelUpdate,ShowPage process
    class ValidateFT,CheckRef,Priority,RefCheck,CheckPrefix,CheckCC,FindCCDesc decision
```

---

## 4. Subtype Fill Text — Khusus Account 2010000005 (Uang Titipan)

```mermaid
---
title: Subtype Fill Text — Position-Based Matching
---
flowchart TD
    Start(["User buka /fill-text/subtype"]) --> Form["Form Upload 2 File<br/>1. Ledger Mapping Export<br/>2. Target File (Excel GL)"]

    Form --> Submit["POST /fill-text/subtype/process"]

    Submit --> ValidateUpload{"Validasi:<br/>ledger_file.xlsx<br/>target_file.xlsx<br/>max 10MB"}

    ValidateUpload -->|❌ Invalid| ErrBack["redirect back with error"]
    ValidateUpload -->|✅ Valid| StoreFiles["StoreUpload()<br/>Simpan ke storage/app/test_fill_text/"]

    StoreFiles --> ParseLedger["getLedgerRowsForAccount('2010000005')<br/>- Baca ledger file<br/>- Filter GL Entry = 2010000005<br/>- Return: [GL Entry, Description,<br/>  Component ID, Component Name]<br/>- PRESERVE empty entries"]

    ParseLedger --> ParseTarget["getTargetRowsForAccount('2010000005')<br/>- Baca target file<br/>- Filter Account = 2010000005<br/>- Return: [excel_row, amount,<br/>  cost_center, text]"]

    ParseTarget --> CompareCount{"count(ledger) ===<br/>count(target)?"}

    CompareCount -->|"❌ Tidak"| FlashWarning["⚠️ Flash warning:<br/>Perbedaan jumlah entry"]
    CompareCount -->|"✅ Ya"| MatchPositional

    FlashWarning --> MatchPositional["Positional Matching<br/>target[i] → ledger[i]"]

    MatchPositional --> BuildMatched["Build $matched array<br/>untuk setiap posisi:"]

    BuildMatched --> CheckComp{"component_name<br/>ada isi?"}

    CheckComp -->|"✅ Ya"| LabelMap{"Ada di<br/>LABEL_MAP?"}
    LabelMap -->|"✅ Ya"| UseMap["default_label =<br/>LABEL_MAP[component]"]
    LabelMap -->|"❌ Tidak"| UseFallback["default_label =<br/>'Uang Titipan - ' + component"]
    CheckComp -->|"❌ Kosong"| UseEmpty["default_label = ''"]

    UseMap & UseFallback & UseEmpty --> Session["Store session:<br/>- test_fill_ledger_rows<br/>- test_fill_matched<br/>- test_fill_target_orig_path"]

    Session --> Redirect["Redirect ke<br/>GET /fill-text/subtype/result"]

    Redirect --> View["View: fill_text.subtype_result"]

    View --> Stats["Stats Cards:<br/>- Target Rows<br/>- Ledger Matched<br/>- Unmatched"]

    Stats --> TableCompare["📊 Tabel Komparasi<br/>Side-by-side per posisi:<br/>📄 Ledger (kiri) ↔ 🎯 Target (kanan)<br/>Indikator: ✅ ⚠️ ➕<br/>Color coding: red/amber/orange"]

    TableCompare --> TableEdit["📝 Tabel Mapping Rows<br/>- Komponen Text (read-only)<br/>- Label Output (editable input)"]

    TableEdit --> Apply["User klik 'Apply & Download'<br/>POST /fill-text/subtype/apply"]

    Apply --> ValidateLabels{"Validasi labels:<br/>required|array<br/>nullable|string|max:200"}

    ValidateLabels -->|❌ Invalid| RedirectForm["redirect ke form<br/>with errors"]
    ValidateLabels -->|✅ Valid| CheckSession{"Session valid?<br/>matched + target_path ada?"}

    CheckSession -->|❌ Expired| RedirectUpload["redirect ke form<br/>'Session expired'"]
    CheckSession -->|✅ Valid| CopyFile["Copy target file → temp"]

    CopyFile --> FindTextCol["findColumnByHeader(sheet, 'text')<br/>Cari kolom Text di Excel"]

    FindTextCol --> TextFound{"Kolom Text<br/>ditemukan?"}

    TextFound -->|"❌ Tidak"| Cleanup["Hapus temp file<br/>redirect ke form<br/>with error"]
    TextFound -->|"✅ Ya"| WriteLabels["Loop matched rows:<br/>setCellValue(TextCol.row, label)"]

    WriteLabels --> SaveTemp["Save Excel → temp file"]

    SaveTemp --> ClearSession["Session forget:<br/>test_fill_matched<br/>test_fill_target_orig_path"]

    ClearSession --> Download["response()->download(tempFile)<br/>deleteFileAfterSend(true)<br/>→ Test_Filled_*.xlsx"]

    classDef process fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    classDef endpoint fill:#10b981,color:#fff,stroke:#059669
    classDef view fill:#8b5cf6,color:#fff,stroke:#7c3aed
    class Start,Download,ErrBack,RedirectForm,RedirectUpload endpoint
    class Form,Submit,StoreFiles,ParseLedger,ParseTarget,FlashWarning,MatchPositional,BuildMatched,Session,Redirect,Stats,TableCompare,TableEdit,Apply,CopyFile,FindTextCol,Cleanup,WriteLabels,SaveTemp,ClearSession process
    class ValidateUpload,CompareCount,CheckComp,LabelMap,ValidateLabels,CheckSession,TextFound decision
    class View,Form,Apply view
```

---

## 5. Validator — Komparasi File Asli vs Generate

```mermaid
---
title: Validator — Verifikasi Keakuratan GL
---
flowchart LR
    Start(["User buka /validator"]) --> Form["Form Validator<br/>- Pilih history run<br/>- Upload file Asli dari Talenta"]

    Form --> Submit["POST /validator/run"]

    Submit --> ValidateVal{"Validasi:<br/>history_id exist<br/>asli_file xlsx/xls"}

    ValidateVal -->|❌ Invalid| ErrBack["redirect back"]
    ValidateVal -->|✅ Valid| StoreTemp["Store upload ke temp"]

    StoreTemp --> LoadFiles["ValidatorService::validate()<br/>Baca 2 file Excel:"]

    LoadFiles --> ParseAsli["Parse File Asli:<br/>- GL Account<br/>- Debit/Credit<br/>- Amount<br/>- Cost Center"]

    LoadFiles --> ParseGen["Parse File Generate:<br/>- Account<br/>- PstKy (40=Debit, 50=Credit)<br/>- Amount<br/>- Cost Center"]

    ParseAsli & ParseGen --> ComputeTHP["Compute Totals + THP"]

    ComputeTHP -->     EntityCheck{"detectEntityMismatch()<br/>Baik debit ≤ 20% DAN rows ≤ 30%?"}

    EntityCheck -->|"⚠️ Tidak (salah satu > threshold)"| HighSeverity["High-severity warning"]
    EntityCheck -->|"✅ Ya (keduanya wajar)"| AccountCheck

    HighSeverity --> AccountCheck["detectAccountIssues()<br/>Cari akun:"]

    AccountCheck --> Issues["- Hanya di Asli (new/unmapped)<br/>- Hanya di Generate (typo/deprecated)<br/>= new_in_asli (high)<br/>  misconfig (medium)<br/>  unused_in_asli (info)"]

    Issues --> GroupRows["groupRows() + makeSig()<br/>Buat signature amount@cc<br/>multisetDiff() untuk unmatched"]

    GroupRows --> UpdateHistory["Update GlRunHistory:<br/>validation_status = match/mismatch<br/>validation_details = full report"]

    UpdateHistory --> Result["View: validator.result<br/>- Status banner (match/mismatch)<br/>- Entity mismatch warning<br/>- Summary comparison table<br/>- Per-account breakdown<br/>  dengan severity badges"]

    classDef process fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    classDef endpoint fill:#10b981,color:#fff,stroke:#059669
    class Start,ErrBack endpoint
    class Form,Submit,StoreTemp,LoadFiles,ParseAsli,ParseGen,ComputeTHP,HighSeverity,AccountCheck,Issues,GroupRows,UpdateHistory,Result process
    class ValidateVal,EntityCheck decision
```

---

## 6. Text References — Knowledge Base Management

```mermaid
---
title: Text References — CRUD Knowledge Base
---
flowchart LR
    Index["GET /text-references<br/>- Search by account/cc/text<br/>- Filter by account<br/>- Paginated 30/page<br/>- Sorted by use_count desc"] --> Create["GET /text-references/create<br/>Form tambah reference"]

    Index --> Edit["GET /text-references/{ref}/edit<br/>Form edit (account+cc read-only)"]
    Index --> Delete["DELETE /text-references/{ref}<br/>Hapus reference"]

    Create -->|"POST /text-references"| Store["Validasi + Simpan<br/>learnOrUpdate()<br/>source: manual_input_..."]

    Edit -->|"PUT /text-references/{ref}"| Update["Validasi + Update<br/>Hanya text_value saja<br/>account+cc tidak bisa diubah"]

    Store & Update & Delete --> Index

    classDef page fill:#6366f1,color:#fff,stroke:#4f46e5
    classDef action fill:#3b82f6,color:#fff,stroke:#2563eb
    class Index,Create,Edit,Delete page
    class Store,Update action
```

---

## 7. Reset Center — Administratif

```mermaid
---
title: Reset Center — Hapus Data dengan PIN Gate
---
flowchart TD
    Start(["User buka /reset-center"]) --> PinGate{"Session<br/>reset_pin_verified?"}

    PinGate -->|"❌ Belum"| PinForm["View: PIN Gate<br/>Input password"]
    PinForm -->|"POST /reset-center/verify-pin"| CheckPin{"PIN cocok<br/>dengan gl_system_settings?"}

    CheckPin -->|"❌ Salah"| PinError["redirect back<br/>with error"]
    CheckPin -->|"✅ Benar"| SetSession["Session: reset_pin_verified = true"]

    SetSession --> Dashboard["View: Reset Center<br/>- Stats cards<br/>- Per-section reset buttons<br/>- RESET ALL nuclear option"]

    PinGate -->|"✅ Sudah"| Dashboard

    Dashboard --> ResetSection["POST /reset-center/reset/{section}"]

    ResetSection --> Section{"Section?"}

    Section -->|"run_histories"| ResetRH["Truncate gl_run_histories<br/>Delete all files in gl_outputs/"]
    Section -->|"text_references"| ResetTR["Truncate gl_text_references<br/>Delete files in gl_filled/ + gl_references/"]
    Section -->|"account_prefixes"| ResetAP["Truncate gl_account_prefixes<br/>Re-run GlAccountPrefixSeeder"]
    Section -->|"cost_centers"| ResetCC["Truncate gl_cost_centers<br/>Re-run GlCostCenterSeeder"]

    ResetRH & ResetTR & ResetAP & ResetCC --> Flash["Flash success message"]

    Dashboard --> ResetAll["POST /reset-center/reset-all<br/>Semua section di-reset"]

    ResetAll --> Flash

    Flash --> Dashboard

    Dashboard --> Logout["POST /reset-center/logout<br/>Session forget<br/>redirect ke PIN Gate"]

    classDef process fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    classDef endpoint fill:#10b981,color:#fff,stroke:#059669
    classDef danger fill:#ef4444,color:#fff,stroke:#dc2626
    class Start,Logout endpoint
    class PinForm,Dashboard,ResetSection,ResetAll,Flash process
    class PinGate,Section,CheckPin decision
    class ResetRH,ResetTR,ResetAP,ResetCC danger
```

---

## 8. Data Model Relationships

```mermaid
---
title: Entity Relationship Diagram
---
erDiagram
    gl_entities {
        bigint id PK
        string code
        string name
        string region
        string ledger_code
        string branch_id
        string ledger_id_strategy
        string ledger_id_list
        string doc_header_template
        string output_filename_template
        string extraction_strategy
        string company_code
        boolean is_active
        string notes
    }

    gl_mapping_profiles {
        bigint id PK
        bigint entity_id FK
        string name
        boolean is_default
        string description
        string created_by
    }

    gl_account_mappings {
        bigint id PK
        bigint profile_id FK
        string mapping_key
        string account_number
        string account_name
        string account_type
        string transaction_value
        string cost_center
        string profit_center
        boolean use_profit_center
        string components
        string match_account_name
        string match_keywords
        int order_index
        boolean is_active
    }

    gl_strategy_d_configs {
        bigint id PK
        bigint profile_id FK
        string debit_accounts
        string debit_keywords
        string default_dc
    }

    gl_run_histories {
        bigint id PK
        bigint entity_id FK
        bigint profile_id FK
        int period_year
        int period_month
        string status
        int total_records
        bigint total_debit
        bigint total_credit
        string output_file_path
        string output_filled_path
        string validation_status
        string validation_details
        string error_message
        string run_by
        timestamp started_at
        timestamp completed_at
    }

    gl_account_prefixes {
        bigint id PK
        string account_number
        string prefix
    }

    gl_cost_centers {
        bigint id PK
        string cost_center_code
        string name
        string description
        string short_text
        boolean is_active
    }

    gl_text_references {
        bigint id PK
        string account_number
        string cost_center
        string text_value
        string learned_from
        int use_count
        timestamp last_used_at
    }

    gl_system_settings {
        bigint id PK
        string key
        string value
        string description
    }

    gl_entities ||--o{ gl_mapping_profiles : has
    gl_entities ||--o{ gl_run_histories : has
    gl_mapping_profiles ||--o{ gl_account_mappings : has
    gl_mapping_profiles ||--o| gl_strategy_d_configs : has
    gl_mapping_profiles ||--o{ gl_run_histories : has
    gl_run_histories ||--|| gl_entities : belongs_to
    gl_run_histories ||--|| gl_mapping_profiles : belongs_to
```

---

## 9. Extraction Strategy Decision Tree

```mermaid
---
title: Python Strategy Decision Tree
---
flowchart TD
    Start(["Python Service Menerima Config"]) --> ReadStrategy{"extraction_strategy?"}

    ReadStrategy -->|"A"| SA["Strategy A<br/>Single Account Mapping<br/>Entity: CS Semarang, Driver, Pembantu<br/>Engine: Semarang (port 8091)"]

    ReadStrategy -->|"B"| SB["Strategy B<br/>Component-Based Matching<br/>Entity: Non-Staff, Staff Semarang,<br/>Driver KMI<br/>Engine: Both (8091 & 8092)"]

    ReadStrategy -->|"C"| SC["Strategy C<br/>Variant/Keyword Matching<br/>Entity: Produksi Semarang<br/>Engine: Semarang (8091)"]

    ReadStrategy -->|"D"| SD["Strategy D<br/>Auto-Detect D/C<br/>Entity: Surabaya entities<br/>Engine: Surabaya (8092)"]

    ReadStrategy -->|"E"| SE["Strategy E<br/>Aggregate by Position<br/>Entity: KMI 1, KMI 2, Pembantu KMI,<br/>Karyawan Harian Lepas, Staff KMI<br/>Engine: Surabaya (8092)"]

    ReadStrategy -->|"F"| SF["Strategy F<br/>Individual with Keywords<br/>Entity: Non-Staff KMI<br/>Engine: Surabaya (8092)"]

    SA --> SA_Logic["for m in mappings:<br/>  if account_type == 'Cost center':<br/>    split per CC from API detail<br/>  else: # Aggregate<br/>    sum all → 1 row<br/>  cost_center = mapping.cost_center<br/>  profit_center = mapping.profit_center<br/><br/>Pre-init semua mapping dgn amount=0"]

    SB --> SB_Logic["for report_item in detail:<br/>  match by account_number<br/>  if multi-variant:<br/>    match by keyword<br/>  if account_type == 'Cost center':<br/>    per-detail per CC<br/>  else: # Aggregate<br/>    sum semua<br/><br/>Surabaya variant:<br/>  match via match_account_name"]

    SC --> SC_Logic["mapping_by_account = {...}<br/>for report_item in detail:<br/>  if account_number in mapping:<br/>    for d in report_item.details:<br/>      amount = parse_amount(d.amount)<br/>      cc = d.cost_center<br/>      if cc == '-': cc = ''<br/>      entry = {amount, cc, ...}<br/><br/>Sort: Debit dulu, Credit kemudian"]

    SD --> SD_Logic["debit_accounts = config[...]<br/>debit_keywords = config[...]<br/>default_dc = 'Credit'<br/>for report_item in detail:<br/>  if keyword in account_name:<br/>    trans_value = 'Debit'<br/>  elif account in whitelist:<br/>    trans_value = 'Debit'<br/>  else:<br/>    trans_value = default_dc<br/>  has_cc? → Cost center / Aggregate<br/><br/>TANPA mapping eksplisit"]

    SE --> SE_Logic["mapping_by_key = {...}<br/>entries = []<br/>for report_item in detail (urutan API):<br/>  match by account_number + keyword<br/>  if Aggregate:<br/>    setiap detail = 1 entry<br/>  else: # Cost center<br/>    setiap detail per CC<br/><br/>PRESERVE urutan API (no reorder)"]

    SF --> SF_Logic["mapping_by_account = {...}<br/>for report_item in detail:<br/>  if account_number in mapping:<br/>    for d in report_item.details:<br/>      entries.append({...})<br/>      TERMASUK amount = 0<br/><br/>Sort: Debit dulu, Credit kemudian"]

    SA_Logic & SB_Logic & SC_Logic & SD_Logic & SE_Logic & SF_Logic --> BuildSAP["convert_to_sap_format()<br/>20 kolom SAP:<br/>Document Date, Posting Date,<br/>Doc. Type, Company Code,<br/>Curr, Reference, Doc. Header,<br/>PstKy (40/50), Account,<br/>Sp.G/L, Amount, Due On,<br/>Tax Code, Value Date,<br/>Cost Center, Profit Center,<br/>Assignment, Text,<br/>Reason Code, House Bank"]

    BuildSAP --> SaveExcel["Save Excel via openpyxl<br/>→ storage/app/gl_outputs/"]

    classDef python fill:#059669,color:#fff,stroke:#047857
    classDef process fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef decision fill:#f59e0b,color:#1e293b,stroke:#d97706
    classDef endpoint fill:#10b981,color:#fff,stroke:#059669
    class Start,SaveExcel endpoint
    class SA,SB,SC,SD,SE,SF python
    class SA_Logic,SB_Logic,SC_Logic,SD_Logic,SE_Logic,SF_Logic,BuildSAP process
    class ReadStrategy decision
```

---

## 10. Complete User Journey Map

```mermaid
---
title: User Journey — End-to-End Flow
---
flowchart LR
    subgraph Setup["🔧 Setup & Konfigurasi"]
        A1["Mapping Editor<br/>/mapping/*"] --> A2["Edit/Add mapping rows<br/>per account per profile"]
        A2 --> A3["Text References<br/>/text-references/*"]
        A3 --> A4["Manual entry knowledge base<br/>untuk Fill Text"]
    end

    subgraph Extraction["⚡ Extraction"]
        B1["Run Extraction<br/>/run"] --> B2["Pilih Entity → Profile<br/>→ Tahun → Bulan"]
        B2 --> B3["Python Service<br/>fetch Talenta API"]
        B3 --> B4["Generate Excel SAP<br/>20 kolom"]
    end

    subgraph PostProcess["📝 Post-Processing"]
        C1["Fill Text<br/>/fill-text"] --> C2["Isi kolom Text<br/>dari knowledge base"]
        C1 --> C3["Subtype Fill<br/>/fill-text/subtype"]
        C3 --> C4["Khusus 2010000005<br/>Position-based matching"]
        C5["Validator<br/>/validator"] --> C6["Bandingkan Asli vs Generate"]
    end

    subgraph Output["📥 Output & Download"]
        D1["Download Excel GL<br/>/run/download/{id}"]
        D2["Download Filled Excel<br/>/fill-text/download/{id}"]
        D3["Download Subtype Filled<br/>/fill-text/subtype/apply"]
    end

    subgraph Maintenance["🔨 Maintenance"]
        E1["Reset Center<br/>/reset-center"]
        E1 --> E2["Hapus run histories"]
        E1 --> E3["Reset text references"]
        E1 --> E4["Reset account prefixes"]
        E1 --> E5["Reset cost centers"]
    end

    A2 --> B2
    A4 --> C2
    B4 --> C2
    B4 --> C4
    B4 --> C6
    C2 --> D2
    C4 --> D3
    B4 --> D1

    classDef setup fill:#6366f1,color:#fff,stroke:#4f46e5
    classDef extraction fill:#3b82f6,color:#fff,stroke:#2563eb
    classDef postproc fill:#8b5cf6,color:#fff,stroke:#7c3aed
    classDef output fill:#10b981,color:#fff,stroke:#059669
    classDef maintenance fill:#ef4444,color:#fff,stroke:#dc2626
    class A1,A2,A3,A4 setup
    class B1,B2,B3,B4 extraction
    class C1,C2,C3,C4,C5,C6 postproc
    class D1,D2,D3 output
    class E1,E2,E3,E4,E5 maintenance
```

---

> **Dibuat:** 10 Juni 2026 — Berdasarkan analisis source code aktual.
