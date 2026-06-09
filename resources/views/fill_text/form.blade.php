@extends('layouts.app')

@section('title', 'Fill Text')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Fill Text</h1>
        <p class="text-sm text-slate-500 mt-1">Auto-isi kolom "Text" di file Excel hasil generate dengan deskripsi sesuai mapping prefix + cost center.</p>
    </div>

    <!-- Stats Knowledge Base -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-slate-900">{{ number_format($stats['total_references']) }}</div>
            <div class="text-xs text-slate-500 mt-1">Text References di DB</div>
            <div class="text-xs text-slate-400 mt-1">Knowledge base yang tumbuh tiap pemakaian</div>
        </div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-xs text-slate-500 font-semibold mb-2">5 Reference Paling Sering Dipakai</div>
            @if($stats['most_used']->isEmpty())
                <div class="text-xs text-slate-400">Belum ada reference yang dipakai.</div>
            @else
                <div class="space-y-1">
                    @foreach($stats['most_used'] as $ref)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-700">
                                <code class="font-mono text-slate-500">{{ $ref->account_number }}</code>
                                @if($ref->cost_center) / <code class="font-mono text-slate-500">{{ $ref->cost_center }}</code> @endif
                                &rarr; {{ $ref->text_value }}
                            </span>
                            <span class="badge bg-slate-100 text-slate-700">{{ $ref->use_count }}x</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Info Box -->
    <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4 text-sm">
        <div class="flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <strong>Cara kerja Fill Text:</strong>
                <ol class="list-decimal pl-5 mt-1 space-y-0.5">
                    <li>Pilih run yang sudah Validator-passed</li>
                    <li><strong>Opsional</strong>: Upload file referensi (file FILLED bulan-bulan sebelumnya) untuk memperkaya knowledge base</li>
                    <li>Sistem auto-isi kolom Text berdasarkan: <em>knowledge base</em> &rarr; <em>prefix + cost center description</em></li>
                    <li>Setiap text yang berhasil di-fill, otomatis tersimpan untuk pemakaian berikutnya (auto-learn)</li>
                </ol>
            </div>
        </div>
    </div>

    <form action="{{ route('fill_text.run') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <!-- Step 1: Pilih Run -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">1</span>
                Pilih Run untuk di-Fill Text
            </label>
            <select name="history_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">-- Pilih dari riwayat run --</option>
                @foreach($recentRuns as $run)
                    <option value="{{ $run->id }}" {{ (string) $preselectedHistoryId === (string) $run->id ? 'selected' : '' }}>
                        [{{ $run->created_at->format('d M H:i') }}]
                        {{ $run->entity->name }} - {{ $run->period_label }}
                        ({{ number_format($run->total_records ?? 0) }} rows)
                        @if($run->output_filled_path) [ALREADY FILLED] @endif
                    </option>
                @endforeach
            </select>
            @if($recentRuns->isEmpty())
                <p class="text-xs text-slate-500 mt-1">Belum ada run dengan status SUCCESS.</p>
            @endif
        </div>

        <!-- Step 2: Upload Reference (optional) -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-100 text-slate-700 mr-2">2</span>
                Upload File Referensi (Opsional)
            </label>
            <input type="file" name="reference_file" accept=".xlsx,.xls" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
            <p class="text-xs text-slate-500 mt-1">
                File Excel bulan sebelumnya yang sudah ada kolom Text-nya (FILLED). Akan dipakai untuk match exact (account+CC) sebelum fallback ke prefix.
                Kalau gak upload, sistem pakai knowledge base yang sudah ada + auto-generate dari prefix.
            </p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali</a>
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">
                Run Fill Text
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