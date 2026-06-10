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
