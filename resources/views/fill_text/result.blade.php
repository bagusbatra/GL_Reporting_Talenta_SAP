@extends('layouts.app')

@section('title', 'Hasil Fill Text')

@section('content')

<div class="max-w-6xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('fill_text.form') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Fill Text Lain</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Hasil Fill Text</h1>
        <p class="text-sm text-slate-500 mt-1">
            {{ $history->entity->name }} &middot; {{ $history->period_label }} &middot; Profile: {{ $history->profile->name }}
        </p>
    </div>

    @php
        $totalRows = $result['total_rows'] ?? 0;
        $filledCount = $result['filled_count'] ?? 0;
        $fromRef = $result['from_reference'] ?? 0;
        $fromPrefix = $result['from_prefix'] ?? 0;
        $needFill = $result['need_fill'] ?? [];
        $needFillCount = count($needFill);
        $fillPercent = $totalRows > 0 ? round(($filledCount / $totalRows) * 100, 1) : 0;
        $allFilled = $needFillCount === 0;

        // Count by type
        $countNoCc = collect($needFill)->where('type', 'no_cc_desc')->count();
        $countNoPrefix = collect($needFill)->where('type', 'no_prefix')->count();
        $countLegacy = collect($needFill)->where('type', 'legacy')->count();
    @endphp

    @if($allFilled)
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-r-lg p-5">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h2 class="font-bold text-green-900 text-lg">SEMUA TEXT TERISI DENGAN BENAR! ✅</h2>
                    <p class="text-sm text-green-700 mt-1">Semua {{ number_format($totalRows) }} baris terisi dengan format yang benar. File siap upload ke SAP.</p>
                </div>
            </div>
        </div>
    @else
        <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-5">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-amber-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h2 class="font-bold text-amber-900 text-lg">Ada {{ $needFillCount }} Row Perlu Perhatian ⚠️</h2>
                    <p class="text-sm text-amber-800 mt-1">
                        {{ number_format($filledCount) }}/{{ number_format($totalRows) }} ter-fill otomatis ({{ $fillPercent }}%).
                        @if($countNoCc) <span class="font-medium">{{ $countNoCc }} NEED FILL</span>, @endif
                        @if($countLegacy) <span class="font-medium">{{ $countLegacy }} LEGACY</span>, @endif
                        @if($countNoPrefix) <span class="font-medium">{{ $countNoPrefix }} ?? Account</span> @endif
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-slate-900">{{ number_format($totalRows) }}</div>
            <div class="text-xs text-slate-500 mt-1">Total Rows</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-green-600">{{ number_format($filledCount) }}</div>
            <div class="text-xs text-slate-500 mt-1">Filled ({{ $fillPercent }}%)</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-blue-600">{{ number_format($fromRef) }}</div>
            <div class="text-xs text-slate-500 mt-1">Dari Knowledge Base</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-purple-600">{{ number_format($fromPrefix) }}</div>
            <div class="text-xs text-slate-500 mt-1">Auto-Generated</div>
        </div>
    </div>

    @if($refStats)
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm">
            <strong class="text-blue-900">File Referensi Diproses:</strong>
            {{ $refStats['total'] }} reference, {{ $refStats['learned'] }} disimpan.
            @if(($refStats['skipped_marker'] ?? 0) > 0) ({{ $refStats['skipped_marker'] }} skip karena format problem) @endif
        </div>
    @endif

    <!-- Legenda 3 Status -->
    @if(!$allFilled)
        <div class="mb-4 bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs">
            <strong class="text-slate-700 mr-2">Legend Status:</strong>
            <span class="badge bg-amber-100 text-amber-700 mr-2">NEED FILL</span> CC code belum ada di master &middot;
            <span class="badge bg-orange-100 text-orange-700 mr-2">LEGACY</span> Format lama (perlu update) &middot;
            <span class="badge bg-red-100 text-red-700 mr-2">?? Account</span> Account belum punya prefix
        </div>

        <form action="{{ route('fill_text.save_manual', $history->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-amber-200 overflow-hidden mb-6" id="fillForm">
            @csrf

            <div class="px-5 py-3 border-b border-amber-200 bg-amber-50">
                <h3 class="font-semibold text-amber-900">Input Text Manual ({{ $needFillCount }} row)</h3>
                <p class="text-xs text-amber-700 mt-1">
                    Format text final: <code class="bg-white px-1 rounded">{Prefix} - {CC Description}</code>.
                    Save → tersimpan permanen di knowledge base untuk auto-fill ke depan.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-2 text-left">Row</th>
                            <th class="px-3 py-2 text-left">Account</th>
                            <th class="px-3 py-2 text-left">Cost Center</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-left">Prefix Account *</th>
                            <th class="px-3 py-2 text-left">CC Description</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($needFill as $idx => $row)
                            @php
                                // Determine badge color & label by type
                                $badgeClass = match($row['type']) {
                                    'no_cc_desc' => 'bg-amber-100 text-amber-700',
                                    'legacy' => 'bg-orange-100 text-orange-700',
                                    'no_prefix' => 'bg-red-100 text-red-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $badgeLabel = match($row['type']) {
                                    'no_cc_desc' => 'NEED FILL',
                                    'legacy' => 'LEGACY',
                                    'no_prefix' => '?? Account',
                                    default => 'Problem',
                                };
                                $rowBg = match($row['type']) {
                                    'legacy' => 'bg-orange-50/30',
                                    default => '',
                                };
                            @endphp
                            <tr class="{{ $rowBg }}">
                                <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $row['row'] }}</td>
                                <td class="px-3 py-2 font-mono">
                                    {{ $row['account'] }}
                                    <input type="hidden" name="inputs[{{ $idx }}][account]" value="{{ $row['account'] }}">
                                </td>
                                <td class="px-3 py-2 font-mono">
                                    {{ $row['cost_center'] ?: '-' }}
                                    <input type="hidden" name="inputs[{{ $idx }}][cost_center]" value="{{ $row['cost_center'] }}">
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span class="badge {{ $badgeClass }} font-mono">{{ $badgeLabel }}</span>
                                    <div class="text-xs text-slate-400 mt-1 font-mono break-all">
                                        {{ $row['current_text'] }}
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="inputs[{{ $idx }}][prefix]" required
                                        data-account="{{ $row['account'] }}"
                                        value="{{ $row['prefilled_prefix'] ?? '' }}"
                                        placeholder="Contoh: Lembur" 
                                        class="prefix-input w-full border border-slate-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                                    @if($row['type'] === 'legacy' && !empty($row['prefilled_prefix']))
                                        <div class="text-xs text-orange-600 mt-0.5">
                                            @if(!empty($row['existing_prefix']))
                                                ✓ dari DB
                                            @else
                                                ✓ auto-extract
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($row['cost_center'])
                                        <input type="text" name="inputs[{{ $idx }}][cc_description]" required
                                            placeholder="Contoh: Logistic SD KMI Sby" 
                                            class="w-full border border-slate-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                                    @else
                                        <span class="text-xs text-slate-400">(Aggregate)</span>
                                        <input type="hidden" name="inputs[{{ $idx }}][cc_description]" value="">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <p class="text-xs text-slate-500">💡 Prefix yang sama untuk account yang sama auto-sync di semua row.</p>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                    Save & Update File
                </button>
            </div>

            @if($errors->any())
                <div class="mx-5 mb-4 bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </form>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const inputs = document.querySelectorAll('.prefix-input');
                inputs.forEach(input => {
                    input.addEventListener('input', function() {
                        const account = this.dataset.account;
                        const newValue = this.value;
                        document.querySelectorAll(`.prefix-input[data-account="${account}"]`).forEach(other => {
                            if (other !== this) other.value = newValue;
                        });
                    });
                });
            });
        </script>
        @endpush
    @endif

    <!-- Action -->
    <div class="flex items-center justify-between">
        <a href="{{ route('run.show', $history->id) }}" class="text-sm text-slate-600 hover:text-slate-900 font-medium">&larr; Detail Run</a>
        <div class="space-x-2">
            <a href="{{ route('fill_text.form') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition font-medium text-sm">Fill Text Lain</a>
            <a href="{{ route('fill_text.download', $history->id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                Download Filled Excel
            </a>
        </div>
    </div>

</div>

@endsection