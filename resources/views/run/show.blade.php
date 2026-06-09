@extends('layouts.app')

@section('title', 'Detail Run')

@section('content')

<div class="max-w-4xl mx-auto">

    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('run.history') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali ke Riwayat</a>
            <h1 class="text-2xl font-bold text-slate-900 mt-2">Detail Run #{{ $history->id }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $history->entity->name }} &middot; {{ $history->period_label }}
            </p>
        </div>
        <div>
            @if($history->status === 'success')
                <span class="badge bg-green-100 text-green-700 text-sm">SUCCESS</span>
            @elseif($history->status === 'failed')
                <span class="badge bg-red-100 text-red-700 text-sm">FAILED</span>
            @elseif($history->status === 'running')
                <span class="badge bg-blue-100 text-blue-700 text-sm">RUNNING</span>
            @else
                <span class="badge bg-slate-100 text-slate-700 text-sm">{{ strtoupper($history->status) }}</span>
            @endif
        </div>
    </div>

    <!-- Status Banner -->
    @if($history->status === 'success')
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-r-lg p-5">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold text-green-900">Extraction Berhasil!</h3>
                    <p class="text-sm text-green-700 mt-1">File Excel telah berhasil di-generate dan siap untuk di-download.</p>
                </div>
                <a href="{{ route('run.download', $history->id) }}" class="ml-4 inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Excel
                </a>
            </div>
        </div>
    @elseif($history->status === 'failed')
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-lg p-5">
            <div class="flex items-start">
                <svg class="w-6 h-6 text-red-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h3 class="font-semibold text-red-900">Extraction Gagal</h3>
                    <p class="text-sm text-red-700 mt-1 font-mono">{{ $history->error_message ?: 'Unknown error' }}</p>
                    <p class="text-xs text-red-600 mt-2">
                        Tips: Pastikan Talenta sudah trigger report untuk entity ini, dan Python service berjalan.
                    </p>
                </div>
            </div>
        </div>
    @elseif($history->status === 'running')
        <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg p-5">
            <div class="flex items-center">
                <span class="spinner mr-3" style="border-color: rgba(59,130,246,0.3); border-top-color: #3b82f6;"></span>
                <div>
                    <h3 class="font-semibold text-blue-900">Sedang Berjalan...</h3>
                    <p class="text-sm text-blue-700 mt-1">Refresh halaman untuk update status.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Summary Stats (kalau success) -->
    @if($history->status === 'success' && $history->total_records !== null)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
                <div class="text-2xl font-bold text-slate-900">{{ number_format($history->total_records) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total Records</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
                <div class="text-2xl font-bold text-green-600 font-mono">{{ number_format($history->total_debit) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total Debit (Rp)</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
                <div class="text-2xl font-bold text-red-600 font-mono">{{ number_format($history->total_credit) }}</div>
                <div class="text-xs text-slate-500 mt-1">Total Credit (Rp)</div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
                <div class="text-2xl font-bold font-mono {{ $history->difference == 0 ? 'text-green-600' : 'text-amber-600' }}">{{ number_format($history->difference) }}</div>
                <div class="text-xs text-slate-500 mt-1">THP / Selisih (Rp)</div>
            </div>
        </div>
    @endif

    <!-- Run Detail -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50">
            <h2 class="font-semibold text-slate-900">Detail Run</h2>
        </div>
        <dl class="divide-y divide-slate-100">
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Entity</dt>
                <dd class="col-span-2 font-medium">{{ $history->entity->name }} <span class="text-slate-400 font-mono text-xs">({{ $history->entity->code }})</span></dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Region</dt>
                <dd class="col-span-2">{{ ucfirst($history->entity->region) }}</dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Ledger Code</dt>
                <dd class="col-span-2 font-mono">{{ $history->entity->ledger_code }}</dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Strategy</dt>
                <dd class="col-span-2"><span class="badge bg-slate-100 text-slate-700">{{ $history->entity->extraction_strategy }}</span></dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Profile</dt>
                <dd class="col-span-2">{{ $history->profile->name }}</dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Periode</dt>
                <dd class="col-span-2">{{ $history->period_label }}</dd>
            </div>
            @if($history->output_file_path)
                <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                    <dt class="text-slate-500">Output File</dt>
                    <dd class="col-span-2 font-mono text-xs break-all">{{ basename($history->output_file_path) }}</dd>
                </div>
            @endif
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Run By</dt>
                <dd class="col-span-2">{{ $history->run_by ?: '-' }}</dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Started At</dt>
                <dd class="col-span-2 font-mono text-xs">{{ $history->started_at?->format('d M Y H:i:s') ?: '-' }}</dd>
            </div>
            <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                <dt class="text-slate-500">Completed At</dt>
                <dd class="col-span-2 font-mono text-xs">{{ $history->completed_at?->format('d M Y H:i:s') ?: '-' }}</dd>
            </div>
            @if($history->started_at && $history->completed_at)
                <div class="px-5 py-3 grid grid-cols-3 gap-4 text-sm">
                    <dt class="text-slate-500">Duration</dt>
                    <dd class="col-span-2 font-mono">{{ $history->started_at->diffInSeconds($history->completed_at) }}s</dd>
                </div>
            @endif
        </dl>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between">
        <a href="{{ route('run.form', ['entity_id' => $history->entity_id]) }}" class="text-sm text-slate-600 hover:text-slate-900 font-medium">
            &larr; Run Lagi
        </a>
        <div class="space-x-3">
        @if($history->status === 'success')
    <a href="{{ route('validator.form', ['history_id' => $history->id]) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">
        Validasi
    </a>
    <a href="{{ route('fill_text.form', ['history_id' => $history->id]) }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium text-sm">
        Fill Text
    </a>
    <a href="{{ route('run.download', $history->id) }}" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium text-sm">
        Download Excel
    </a>
@endif
        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition font-medium text-sm">
            Dashboard
        </a>
    </div>
    </div>

</div>

@endsection