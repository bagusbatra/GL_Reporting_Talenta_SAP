@extends('layouts.app')

@section('title', 'Validator')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Validator</h1>
        <p class="text-sm text-slate-500 mt-1">Bandingkan file asli Talenta dengan file hasil generate sistem untuk memastikan match sebelum upload ke SAP.</p>
    </div>

    <!-- Info -->
    <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4 text-sm">
        <div class="flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <strong>Cara pakai:</strong>
                <ol class="list-decimal pl-5 mt-1 space-y-0.5">
                    <li>Pilih riwayat run yang mau divalidasi (yang status SUCCESS)</li>
                    <li>Upload file asli dari Talenta (.xlsx, kolom: GL Account, Description, Debit/Credit, Amount, Cost Center)</li>
                    <li>Klik Run Validation</li>
                    <li>Sistem akan compare per group (Account + D/C) dan kasih laporan match/mismatch</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('validator.run') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <!-- Step 1: Pilih Run History -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">1</span>
                Pilih Run yang Mau Divalidasi
            </label>
            <select name="history_id" id="history_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">-- Pilih dari riwayat run --</option>
                @foreach($recentRuns as $run)
                    <option value="{{ $run->id }}" {{ ($preselectedHistory && $preselectedHistory->id === $run->id) ? 'selected' : '' }}>
                        [{{ $run->created_at->format('d M H:i') }}]
                        {{ $run->entity->name }} - {{ $run->period_label }}
                        ({{ number_format($run->total_records ?? 0) }} rows)
                    </option>
                @endforeach
            </select>
            @if($recentRuns->isEmpty())
                <p class="text-xs text-slate-500 mt-1">Belum ada run dengan status SUCCESS. <a href="{{ route('run.form') }}" class="text-slate-900 font-medium">Run dulu &rarr;</a></p>
            @endif
        </div>

        <!-- Step 2: Upload file asli -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">2</span>
                Upload File Asli dari Talenta
            </label>
            <input type="file" name="asli_file" accept=".xlsx,.xls" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-900 file:text-white hover:file:bg-slate-800 file:cursor-pointer">
            <p class="text-xs text-slate-500 mt-1">Format: Excel (.xlsx) yang lo download dari Talenta setelah trigger Payroll Allocation.</p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali</a>
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">
                Run Validation
            </button>
        </div>

        @if($errors->any())
            <div class="mt-4 bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </form>

</div>

@endsection