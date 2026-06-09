@extends('layouts.app')

@section('title', 'Riwayat Run')

@section('content')

<!-- Header -->
<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Riwayat Run</h1>
    <p class="text-sm text-slate-500 mt-1">Semua history extraction yang pernah dijalankan.</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
    <form action="{{ route('run.history') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
        <div>
            <label class="text-xs text-slate-500">Entity</label>
            <select name="entity_id" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">Semua</option>
                @foreach($entities as $e)
                    <option value="{{ $e->id }}" {{ ($filters['entity_id'] ?? null) == $e->id ? 'selected' : '' }}>
                        {{ $e->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Status</label>
            <select name="status" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">Semua</option>
                <option value="success" {{ ($filters['status'] ?? null) === 'success' ? 'selected' : '' }}>Success</option>
                <option value="failed" {{ ($filters['status'] ?? null) === 'failed' ? 'selected' : '' }}>Failed</option>
                <option value="running" {{ ($filters['status'] ?? null) === 'running' ? 'selected' : '' }}>Running</option>
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Tahun</label>
            <select name="year" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">Semua</option>
                @for($y = now()->year + 1; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}" {{ ($filters['year'] ?? null) == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="text-xs text-slate-500">Bulan</label>
            <select name="month" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">Semua</option>
                @php $monthNames = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; @endphp
                @for($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" {{ ($filters['month'] ?? null) == $m ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                @endfor
            </select>
        </div>
        <div class="flex items-end space-x-2">
            <button type="submit" class="flex-1 bg-slate-900 text-white px-4 py-2 rounded-lg hover:bg-slate-800 transition font-medium text-sm">Filter</button>
            <a href="{{ route('run.history') }}" class="px-3 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm">Reset</a>
        </div>
    </form>
</div>

<!-- History Table -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    @if($histories->isEmpty())
        <div class="px-5 py-12 text-center">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-sm text-slate-500">Tidak ada riwayat run yang sesuai filter.</p>
            <a href="{{ route('run.form') }}" class="mt-3 inline-block text-sm text-slate-900 font-medium hover:underline">Run Extraction Baru &rarr;</a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Waktu</th>
                        <th class="px-5 py-3 text-left">Entity</th>
                        <th class="px-5 py-3 text-left">Profile</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-right">Records</th>
                        <th class="px-5 py-3 text-right">Total Debit</th>
                        <th class="px-5 py-3 text-right">Total Credit</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($histories as $run)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500 font-mono text-xs whitespace-nowrap">{{ $run->created_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">{{ $run->entity->name }}</div>
                                <div class="text-xs text-slate-400 font-mono">{{ $run->entity->code }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $run->profile->name }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $run->period_label }}</td>
                            <td class="px-5 py-3 text-right font-mono">{{ $run->total_records !== null ? number_format($run->total_records) : '-' }}</td>
                            <td class="px-5 py-3 text-right font-mono text-xs">{{ $run->total_debit !== null ? number_format($run->total_debit) : '-' }}</td>
                            <td class="px-5 py-3 text-right font-mono text-xs">{{ $run->total_credit !== null ? number_format($run->total_credit) : '-' }}</td>
                            <td class="px-5 py-3 text-center whitespace-nowrap">
                                @if($run->status === 'success')
                                    <span class="badge bg-green-100 text-green-700">SUCCESS</span>
                                @elseif($run->status === 'failed')
                                    <span class="badge bg-red-100 text-red-700">FAILED</span>
                                @elseif($run->status === 'running')
                                    <span class="badge bg-blue-100 text-blue-700">RUNNING</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-700">{{ strtoupper($run->status) }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('run.show', $run->id) }}" class="text-xs text-slate-600 hover:text-slate-900 font-medium mr-2">Detail</a>
                                @if($run->status === 'success' && $run->output_file_path)
                                    <a href="{{ route('run.download', $run->id) }}" class="text-xs text-green-600 hover:text-green-700 font-medium">Download</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
            {{ $histories->links() }}
        </div>
    @endif
</div>

@endsection