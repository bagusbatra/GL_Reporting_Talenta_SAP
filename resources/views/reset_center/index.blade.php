@extends('layouts.app')

@section('title', 'Reset Center')

@section('content')

<div class="max-w-4xl mx-auto">

    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Reset Center</h1>
            <p class="text-sm text-slate-500 mt-1">Tools untuk reset data sistem ke kondisi bersih. Gunakan dengan hati-hati.</p>
        </div>
        <form action="{{ route('reset_center.logout') }}" method="POST">
            @csrf
            <button type="submit" class="text-sm text-slate-600 hover:text-slate-900 px-3 py-1.5 border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                Logout
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-r-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-green-600 mr-2 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-green-800">{{ session('success') }}</div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-lg p-4">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-600 mr-2 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-red-800">{{ session('error') }}</div>
            </div>
        </div>
    @endif

    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
        <strong>⚠️ Perhatian:</strong> Aksi reset di halaman ini PERMANEN dan tidak bisa di-undo. Pastikan backup database dulu sebelum reset.
    </div>

    <!-- Reset Per Section -->
    <h2 class="text-lg font-semibold text-slate-900 mb-3">Reset Per Section</h2>
    <div class="space-y-4 mb-8">

        <!-- Run Histories -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">Run Histories</h3>
                    <p class="text-sm text-slate-500 mt-1">Hapus semua riwayat run extraction + file Excel di <code class="font-mono bg-slate-100 px-1 rounded text-xs">gl_outputs/</code></p>
                    <div class="mt-2 flex items-center gap-3 text-xs">
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['run_histories']) }} rows di DB</span>
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['outputs_files']) }} file Excel</span>
                    </div>
                </div>
                <form action="{{ route('reset_center.reset_section', 'run-histories') }}" method="POST" onsubmit="return confirm('Yakin hapus semua Run Histories? Aksi tidak bisa di-undo.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium text-sm">
                        Reset
                    </button>
                </form>
            </div>
        </div>

        <!-- Text References -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">Text References (Knowledge Base)</h3>
                    <p class="text-sm text-slate-500 mt-1">Hapus knowledge base text + file di <code class="font-mono bg-slate-100 px-1 rounded text-xs">gl_filled/</code> dan <code class="font-mono bg-slate-100 px-1 rounded text-xs">gl_references/</code></p>
                    <div class="mt-2 flex items-center gap-3 text-xs">
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['text_references']) }} rows di DB</span>
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['filled_files']) }} filled files</span>
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['references_files']) }} reference files</span>
                    </div>
                </div>
                <form action="{{ route('reset_center.reset_section', 'text-references') }}" method="POST" onsubmit="return confirm('Yakin hapus semua Text References dan file Excel? Aksi tidak bisa di-undo.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium text-sm">
                        Reset
                    </button>
                </form>
            </div>
        </div>

        <!-- Account Prefixes -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">Account Prefixes</h3>
                    <p class="text-sm text-slate-500 mt-1">Reset prefix per account, lalu auto re-seed ke 20 default prefix.</p>
                    <div class="mt-2 flex items-center gap-3 text-xs">
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['account_prefixes']) }} rows di DB</span>
                    </div>
                </div>
                <form action="{{ route('reset_center.reset_section', 'account-prefixes') }}" method="POST" onsubmit="return confirm('Yakin reset Account Prefixes lalu re-seed?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-medium text-sm">
                        Reset & Re-seed
                    </button>
                </form>
            </div>
        </div>

        <!-- Cost Centers -->
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-900">Cost Centers</h3>
                    <p class="text-sm text-slate-500 mt-1">Reset cost centers, lalu auto re-seed dari <code class="font-mono bg-slate-100 px-1 rounded text-xs">COSTCENTER_SAP.xlsx</code>. Butuh ~30-60 detik.</p>
                    <div class="mt-2 flex items-center gap-3 text-xs">
                        <span class="badge bg-slate-100 text-slate-700">{{ number_format($stats['cost_centers']) }} rows di DB</span>
                    </div>
                </div>
                <form action="{{ route('reset_center.reset_section', 'cost-centers') }}" method="POST" onsubmit="return confirm('Yakin reset Cost Centers lalu re-seed dari file Excel? Proses lambat (~30-60 detik).')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition font-medium text-sm">
                        Reset & Re-seed
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Reset All -->
    <h2 class="text-lg font-semibold text-slate-900 mb-3">Reset All Sections</h2>
    <div class="bg-red-50 border-2 border-red-300 rounded-lg p-5">
        <div class="flex items-start">
            <svg class="w-8 h-8 text-red-600 mr-3 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <div class="flex-1">
                <h3 class="font-bold text-red-900">⚠️ Reset All - Nuclear Option</h3>
                <p class="text-sm text-red-800 mt-1">
                    Aksi ini akan jalankan SEMUA reset di atas secara berurutan: Run Histories + Text References + Account Prefixes (re-seed) + Cost Centers (re-seed) + hapus semua file Excel. Butuh ~1-2 menit.
                </p>
                <p class="text-xs text-red-700 mt-2 font-semibold">
                    Yang TIDAK dihapus: Entities, Mapping Profiles, Account Mappings, Strategy D Configs, System Settings (PIN).
                </p>
                <form action="{{ route('reset_center.reset_all') }}" method="POST" class="mt-3" onsubmit="return confirm('YAKIN MAU RESET ALL? Aksi ini PERMANEN dan butuh waktu ~1-2 menit. Ketik OK untuk lanjut.')">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-red-700 text-white rounded-lg hover:bg-red-800 transition font-bold text-sm">
                        🔥 RESET ALL SECTIONS
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

@endsection