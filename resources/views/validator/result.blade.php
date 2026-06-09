@extends('layouts.app')

@section('title', 'Hasil Validasi')

@section('content')

@php
    $sum = $result['summary'];
    $isMatch = $sum['overall_status'] === 'match';
    $entityWarning = $result['entity_mismatch_warning'] ?? null;
    $accountIssues = $result['account_issues'] ?? [];
    $highIssues = collect($accountIssues)->where('severity', 'high')->values();
    $mediumIssues = collect($accountIssues)->where('severity', 'medium')->values();
    $infoIssues = collect($accountIssues)->where('severity', 'info')->values();
@endphp

<div class="max-w-5xl mx-auto">

    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('validator.form') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Validasi Lain</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Hasil Validasi</h1>
        <p class="text-sm text-slate-500 mt-1">
            {{ $history->entity->name }} &middot; {{ $history->period_label }} &middot; Profile: {{ $history->profile->name }}
            <br>
            <span class="text-xs">File asli: <code class="font-mono bg-slate-100 px-1 rounded">{{ $asli_filename }}</code></span>
        </p>
    </div>

    {{-- [IMPROVEMENT 1] Entity Mismatch Warning - paling atas, paling penting --}}
    @if($entityWarning)
        <div class="mb-6 bg-red-100 border-l-4 border-red-600 rounded-r-lg p-5 shadow-md">
            <div class="flex items-start">
                <svg class="w-8 h-8 text-red-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h2 class="font-bold text-red-900 text-lg">⚠️ PERINGATAN: KEMUNGKINAN FILE SALAH!</h2>
                    <p class="text-sm text-red-800 mt-2 leading-relaxed">
                        {{ $entityWarning['message'] }}
                    </p>
                    <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-white rounded p-3 border border-red-200">
                            <div class="text-xs text-red-600 font-semibold">Selisih Total Debit</div>
                            <div class="font-mono font-bold text-lg text-red-900">{{ number_format($entityWarning['debit_diff']) }}</div>
                            <div class="text-xs text-red-600">({{ $entityWarning['debit_diff_percent'] }}%)</div>
                        </div>
                        <div class="bg-white rounded p-3 border border-red-200">
                            <div class="text-xs text-red-600 font-semibold">Selisih Jumlah Rows</div>
                            <div class="font-mono font-bold text-lg text-red-900">{{ number_format($entityWarning['rows_diff']) }}</div>
                            <div class="text-xs text-red-600">({{ $entityWarning['rows_diff_percent'] }}%)</div>
                        </div>
                    </div>
                    <p class="text-xs text-red-700 mt-3 font-medium">
                        💡 Pastikan: (1) file asli yang lo upload adalah untuk entity yang sama dengan run yang dipilih, (2) periode-nya juga sama.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Overall Status Banner -->
    @if($isMatch)
        <div class="mb-6 bg-green-50 border-l-4 border-green-500 rounded-r-lg p-5">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-green-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h2 class="font-bold text-green-900 text-lg">PERFECT MATCH! ✅</h2>
                    <p class="text-sm text-green-700 mt-1">
                        Semua {{ $sum['groups_total'] }} group match. File generate identik dengan file asli Talenta. Aman untuk lanjut ke fill text dan upload SAP.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-r-lg p-5">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-red-600 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <h2 class="font-bold text-red-900 text-lg">MISMATCH ⚠️</h2>
                    <p class="text-sm text-red-700 mt-1">
                        Ada {{ $sum['groups_mismatch'] }} dari {{ $sum['groups_total'] }} group yang tidak match. <strong>Jangan upload ke SAP dulu</strong> - cek detail di bawah dan koordinasi dengan tim payroll.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- [IMPROVEMENT 2] Account-level Issues --}}
    @if(count($accountIssues) > 0)
        <div class="mb-6">
            <h3 class="font-bold text-slate-900 mb-3 flex items-center">
                <svg class="w-5 h-5 mr-2 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Account Issues Detected ({{ count($accountIssues) }})
            </h3>

            <div class="space-y-3">
                {{-- HIGH priority issues --}}
                @foreach($highIssues as $issue)
                    <div class="bg-red-50 border-l-4 border-red-500 rounded-r-lg p-4">
                        <div class="flex items-start">
                            <span class="badge bg-red-600 text-white mr-3 mt-0.5">CRITICAL</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-red-900">{{ $issue['title'] }}</h4>
                                <p class="text-sm text-red-800 mt-1">{{ $issue['message'] }}</p>
                                <div class="mt-2 text-xs text-red-700">
                                    <span class="font-semibold">D/C:</span> {{ $issue['dc'] }}
                                    @if(!empty($issue['sample_ccs']))
                                        &middot; <span class="font-semibold">Sample CC:</span>
                                        @foreach($issue['sample_ccs'] as $cc)
                                            <code class="font-mono bg-white px-1 mx-0.5 rounded">{{ $cc }}</code>
                                        @endforeach
                                    @endif
                                </div>
                                <div class="mt-2 text-xs bg-white border border-red-200 rounded p-2">
                                    <span class="font-bold text-red-700">🎯 Action:</span>
                                    <span class="text-red-700">{{ $issue['action'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- MEDIUM priority issues --}}
                @foreach($mediumIssues as $issue)
                    <div class="bg-amber-50 border-l-4 border-amber-500 rounded-r-lg p-4">
                        <div class="flex items-start">
                            <span class="badge bg-amber-600 text-white mr-3 mt-0.5">WARNING</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-amber-900">{{ $issue['title'] }}</h4>
                                <p class="text-sm text-amber-800 mt-1">{{ $issue['message'] }}</p>
                                <div class="mt-2 text-xs text-amber-700">
                                    <span class="font-semibold">D/C:</span> {{ $issue['dc'] }}
                                </div>
                                <div class="mt-2 text-xs bg-white border border-amber-200 rounded p-2">
                                    <span class="font-bold text-amber-700">🎯 Action:</span>
                                    <span class="text-amber-700">{{ $issue['action'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- INFO priority issues --}}
                @foreach($infoIssues as $issue)
                    <div class="bg-blue-50 border-l-4 border-blue-400 rounded-r-lg p-4">
                        <div class="flex items-start">
                            <span class="badge bg-blue-500 text-white mr-3 mt-0.5">INFO</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-blue-900">{{ $issue['title'] }}</h4>
                                <p class="text-sm text-blue-800 mt-1">{{ $issue['message'] }}</p>
                                <div class="mt-2 text-xs text-blue-700">
                                    <span class="font-semibold">D/C:</span> {{ $issue['dc'] }}
                                </div>
                                <div class="mt-2 text-xs bg-white border border-blue-200 rounded p-2">
                                    <span class="font-bold text-blue-700">🎯 Action:</span>
                                    <span class="text-blue-700">{{ $issue['action'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Summary -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50">
            <h3 class="font-semibold text-slate-900">Summary Comparison</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-2 text-left">Metric</th>
                        <th class="px-5 py-2 text-right">Asli Talenta</th>
                        <th class="px-5 py-2 text-right">Generate Sistem</th>
                        <th class="px-5 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-5 py-2 font-medium">Total Rows</td>
                        <td class="px-5 py-2 text-right font-mono">{{ number_format($sum['total_asli']['rows']) }}</td>
                        <td class="px-5 py-2 text-right font-mono">{{ number_format($sum['total_gen']['rows']) }}</td>
                        <td class="px-5 py-2 text-center">
                            @if($sum['rows_match']) <span class="badge bg-green-100 text-green-700">MATCH</span>
                            @else <span class="badge bg-red-100 text-red-700">BEDA {{ number_format(abs($sum['total_asli']['rows'] - $sum['total_gen']['rows'])) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="px-5 py-2 font-medium">Total Debit</td>
                        <td class="px-5 py-2 text-right font-mono">{{ number_format($sum['total_asli']['debit']) }}</td>
                        <td class="px-5 py-2 text-right font-mono">{{ number_format($sum['total_gen']['debit']) }}</td>
                        <td class="px-5 py-2 text-center">
                            @if($sum['debit_match']) <span class="badge bg-green-100 text-green-700">MATCH</span>
                            @else <span class="badge bg-red-100 text-red-700">BEDA {{ number_format(abs($sum['total_asli']['debit'] - $sum['total_gen']['debit'])) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="px-5 py-2 font-medium">Total Credit</td>
                        <td class="px-5 py-2 text-right font-mono">{{ number_format($sum['total_asli']['credit']) }}</td>
                        <td class="px-5 py-2 text-right font-mono">{{ number_format($sum['total_gen']['credit']) }}</td>
                        <td class="px-5 py-2 text-center">
                            @if($sum['credit_match']) <span class="badge bg-green-100 text-green-700">MATCH</span>
                            @else <span class="badge bg-red-100 text-red-700">BEDA {{ number_format(abs($sum['total_asli']['credit'] - $sum['total_gen']['credit'])) }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td class="px-5 py-2 font-bold">THP (Selisih)</td>
                        <td class="px-5 py-2 text-right font-mono font-bold">{{ number_format($sum['total_asli']['thp']) }}</td>
                        <td class="px-5 py-2 text-right font-mono font-bold">{{ number_format($sum['total_gen']['thp']) }}</td>
                        <td class="px-5 py-2 text-center">
                            @if($sum['thp_match']) <span class="badge bg-green-100 text-green-700">MATCH</span>
                            @else <span class="badge bg-red-100 text-red-700">BEDA</span>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Per Group Detail -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50">
            <h3 class="font-semibold text-slate-900">
                Per Group Comparison
                <span class="text-sm text-slate-500 font-normal">({{ $sum['groups_match'] }} match / {{ $sum['groups_mismatch'] }} mismatch)</span>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2 text-left">Account</th>
                        <th class="px-4 py-2 text-center">D/C</th>
                        <th class="px-4 py-2 text-right">Asli Rows</th>
                        <th class="px-4 py-2 text-right">Gen Rows</th>
                        <th class="px-4 py-2 text-right">Asli Total</th>
                        <th class="px-4 py-2 text-right">Gen Total</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($result['groups'] as $g)
                        <tr class="{{ $g['status'] === 'mismatch' ? 'bg-red-50' : '' }}">
                            <td class="px-4 py-2 font-mono">{{ $g['account'] }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($g['dc'] === 'Debit')
                                    <span class="badge bg-green-100 text-green-700">D</span>
                                @else
                                    <span class="badge bg-red-100 text-red-700">C</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($g['asli_rows']) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($g['gen_rows']) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($g['asli_total']) }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($g['gen_total']) }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($g['status'] === 'match')
                                    <span class="badge bg-green-100 text-green-700">MATCH</span>
                                @else
                                    <span class="badge bg-red-100 text-red-700">MISMATCH</span>
                                @endif
                            </td>
                        </tr>
                        @if($g['status'] === 'mismatch')
                            <tr class="bg-red-50">
                                <td colspan="7" class="px-4 py-3">
                                    <div class="bg-white border border-red-200 rounded p-3 text-xs">
                                        @if(!empty($g['only_in_asli']))
                                            <div class="mb-2">
                                                <strong class="text-red-700">Hanya di file ASLI Talenta (gak ada di Generate):</strong>
                                                <div class="mt-1 space-y-0.5">
                                                    @foreach($g['only_in_asli'] as $row)
                                                        <div class="font-mono text-slate-700">
                                                            Amount: {{ number_format($row['amount']) }}
                                                            @if($row['cost_center']) | CC: {{ $row['cost_center'] }}
                                                            @else | <span class="text-slate-400">(no CC)</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                        @if(!empty($g['only_in_gen']))
                                            <div>
                                                <strong class="text-amber-700">Hanya di file GENERATE (gak ada di Asli):</strong>
                                                <div class="mt-1 space-y-0.5">
                                                    @foreach($g['only_in_gen'] as $row)
                                                        <div class="font-mono text-slate-700">
                                                            Amount: {{ number_format($row['amount']) }}
                                                            @if($row['cost_center']) | CC: {{ $row['cost_center'] }}
                                                            @else | <span class="text-slate-400">(no CC)</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tips Saat MISMATCH -->
    @if(!$isMatch)
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 mb-6">
            <h3 class="font-bold text-amber-900 mb-2">Apa yang harus dilakukan saat MISMATCH?</h3>
            <ol class="text-sm text-amber-800 space-y-1 list-decimal pl-5">
                <li>Cek "Account Issues" di atas - ini biasanya pintu masuk masalah</li>
                <li>Cek group yang mismatch di tabel (highlight merah)</li>
                <li>Lihat detail "Hanya di Asli" - data dari Talenta yang gak ke-detect oleh mapping kita</li>
                <li>Koordinasi dengan tim payroll: tanya account, D/C, dan komponen baru</li>
                <li>Ke menu <a href="{{ route('mapping.index') }}" class="font-semibold underline">Mapping Editor</a> - duplicate profile Default, edit/tambah mapping yang dibutuhkan</li>
                <li>Run ulang pakai custom profile baru, lalu validasi lagi</li>
            </ol>
        </div>
    @endif

    <!-- Action -->
    <div class="flex items-center justify-between">
        <a href="{{ route('run.show', $history->id) }}" class="text-sm text-slate-600 hover:text-slate-900 font-medium">&larr; Detail Run</a>
        <div class="space-x-2">
            <a href="{{ route('validator.form') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition font-medium text-sm">Validasi Lain</a>
            @if($isMatch && !$entityWarning)
                <a href="{{ route('run.download', $history->id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">Download Excel</a>
            @endif
        </div>
    </div>

</div>

@endsection