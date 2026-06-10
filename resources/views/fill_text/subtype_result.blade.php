@extends('layouts.app')

@section('title', 'Hasil Mapping Subtype Fill Text')

@section('content')

<div class="max-w-6xl mx-auto">

    <div class="mb-6">
        <a href="{{ route('fill_text.subtype.form') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Upload Ulang</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Hasil Mapping</h1>
        <p class="text-sm text-slate-500 mt-1">Position-based matching Account <code class="font-mono bg-slate-100 px-1 rounded">{{ $account }}</code> &mdash; {{ count($matched) }} row ditemukan di target.</p>

        @if(session('subtype_warning'))
            <div class="mt-3 bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-3 rounded text-sm">
                <strong>⚠️ Warning:</strong> {{ session('subtype_warning') }}
            </div>
        @endif
    </div>

    @php
        $ledgerCount = count(array_filter($matched, fn($r) => !empty($r['component_name'])));
    @endphp

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-slate-900">{{ count($matched) }}</div>
            <div class="text-xs text-slate-500 mt-1">Target Rows ({{ $account }})</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-blue-600">{{ $ledgerCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Ledger Entries Matched</div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-2xl font-bold text-purple-600">{{ count($matched) - $ledgerCount }}</div>
            <div class="text-xs text-slate-500 mt-1">Unmatched</div>
        </div>
    </div>

    @if(count($ledgerRows))
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50">
            <h3 class="font-semibold text-slate-900">Komparasi Ledger Mapping vs Target File</h3>
            <p class="text-xs text-slate-500 mt-0.5">Perbandingan data per posisi — Ledger (kiri) vs Target Excel (kanan)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left border-r border-slate-200" colspan="4">📄 Ledger Mapping</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200" colspan="2"></th>
                        <th class="px-3 py-2 text-left" colspan="5">🎯 Target File (Excel GL)</th>
                    </tr>
                    <tr class="bg-slate-50">
                        <th class="px-3 py-2 text-left border-r border-slate-200">#</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200">GL Entry</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200">Description</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200">Component</th>
                        <th class="px-3 py-2 text-center border-r border-slate-200" colspan="2">↔</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200">Row</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200">CC</th>
                        <th class="px-3 py-2 text-right border-r border-slate-200">Amount</th>
                        <th class="px-3 py-2 text-left border-r border-slate-200">Text (Current)</th>
                        <th class="px-3 py-2 text-left">Label Output</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php $max = max(count($ledgerRows), count($matched)); @endphp
                    @for ($i = 0; $i < $max; $i++)
                        @php
                            $lr = $ledgerRows[$i] ?? null;
                            $mr = $matched[$i] ?? null;
                            $mismatch = $lr && $mr && empty($mr['component_name']) && !empty($lr['component_name']);
                            $extraTarget = !$lr && $mr;
                            $extraLedger = $lr && !$mr;
                        @endphp
                        <tr class="{{ $mismatch ? 'bg-red-50/30' : ($extraTarget ? 'bg-amber-50/30' : ($extraLedger ? 'bg-orange-50/30' : '')) }}">
                            <td class="px-3 py-2 font-mono text-xs text-slate-500 border-r border-slate-200">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 font-mono text-xs border-r border-slate-200">{{ $lr['gl_entry'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs border-r border-slate-200">{{ $lr['description'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs border-r border-slate-200">
                                @if($lr && $lr['component_name'])
                                    <span class="text-slate-700">{{ $lr['component_name'] }}</span>
                                @elseif($lr)
                                    <span class="text-slate-400 italic">(kosong)</span>
                                @else
                                    <span class="text-orange-500">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center text-xs text-slate-400 border-r border-slate-200">→</td>
                            <td class="px-2 py-2 text-center text-xs border-r border-slate-200">
                                @if($mismatch)
                                    <span class="text-red-500" title="Ledger ada komponen, target tidak">⚠️</span>
                                @elseif($extraTarget)
                                    <span class="text-amber-500" title="Extra row di target">+</span>
                                @elseif($extraLedger)
                                    <span class="text-orange-500" title="Extra row di ledger">+</span>
                                @else
                                    <span class="text-green-500">✓</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-mono text-xs border-r border-slate-200">{{ $mr['excel_row'] ?? '-' }}</td>
                            <td class="px-3 py-2 font-mono text-xs border-r border-slate-200">{{ $mr['cost_center'] ?: '-' }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-right border-r border-slate-200">{{ $mr ? number_format($mr['amount'], 0) : '-' }}</td>
                            <td class="px-3 py-2 text-xs border-r border-slate-200 max-w-[120px] truncate" title="{{ $mr['current_text'] ?? '' }}">{{ $mr['current_text'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs max-w-[160px] truncate" title="{{ $mr['default_label'] ?? '' }}">{{ $mr['default_label'] ?? '-' }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <form action="{{ route('fill_text.subtype.apply') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden mb-6">
        @csrf

        <div class="px-5 py-3 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-900">Mapping Rows</h3>
                <p class="text-xs text-slate-500 mt-0.5">
                    Edit label text sesuai kebutuhan, lalu klik Apply & Download.
                </p>
            </div>
            <span class="badge bg-green-100 text-green-700 text-xs">Match Positional</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Row</th>
                        <th class="px-3 py-2 text-left">CC</th>
                        <th class="px-3 py-2 text-right">Amount</th>
                        <th class="px-3 py-2 text-left">Komponen Text</th>
                        <th class="px-3 py-2 text-left w-72">Label Output</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($matched as $idx => $row)
                        @php
                            $isUnmatched = empty($row['component_name']);
                        @endphp
                        <tr class="{{ $isUnmatched ? 'bg-red-50/30' : '' }}">
                            <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $idx + 1 }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-slate-500">{{ $row['excel_row'] }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $row['cost_center'] ?: '-' }}</td>
                            <td class="px-3 py-2 font-mono text-xs text-right">{{ number_format($row['amount'], 0) }}</td>
                            <td class="px-3 py-2 text-xs">
                                @if($row['component_name'])
                                    <span class="text-slate-700">{{ $row['component_name'] }}</span>
                                @else
                                    <span class="text-red-500">⚠️ Tidak ada komponen</span>
                                    <div class="text-slate-400 mt-0.5">Text saat ini: {{ $row['current_text'] }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="labels[{{ $idx }}]" value="{{ $row['default_label'] }}"
                                    class="w-full border border-slate-300 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
            <a href="{{ route('fill_text.subtype.form') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Upload Ulang</a>
            <div class="space-x-2">
                <button type="button" onclick="resetLabels()" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition font-medium text-sm">
                    Reset ke Default
                </button>
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                    Apply & Download
                </button>
            </div>
        </div>

        @if(session('error'))
            <div class="m-5 bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm">{{ session('error') }}</div>
        @endif
    </form>

</div>

@push('scripts')
<script>
    let defaultLabels = {};
    document.querySelectorAll('input[name^="labels["]').forEach(input => {
        const key = input.name.match(/labels\[(.*)\]/)[1];
        defaultLabels[key] = input.value;
    });
    function resetLabels() {
        document.querySelectorAll('input[name^="labels["]').forEach(input => {
            const key = input.name.match(/labels\[(.*)\]/)[1];
            if (defaultLabels[key] !== undefined) input.value = defaultLabels[key];
        });
    }
</script>
@endpush

@endsection
