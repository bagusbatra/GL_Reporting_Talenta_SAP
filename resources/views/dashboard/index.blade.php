@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<!-- Header -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
    <p class="text-sm text-slate-500 mt-1">Sistem otomasi pengambilan data GL dari Talenta ke format SAP upload.</p>
</div>

<!-- Python Service Health -->
<div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
    @foreach($health as $region => $status)
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs text-slate-500 uppercase font-semibold tracking-wide">Python Service</div>
                    <div class="text-lg font-bold mt-1">{{ ucfirst($region) }}</div>
                    <div class="text-xs font-mono text-slate-400 mt-1">{{ $status['url'] }}</div>
                </div>
                <div>
                    @if($status['online'])
                        <span class="badge bg-green-100 text-green-700">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1.5"></span>
                            ONLINE
                        </span>
                    @else
                        <span class="badge bg-red-100 text-red-700">
                            <span class="w-2 h-2 bg-red-500 rounded-full mr-1.5"></span>
                            OFFLINE
                        </span>
                    @endif
                </div>
            </div>
            @if($status['online'] && isset($status['data']['strategies_supported']))
                <div class="mt-2 text-xs text-slate-500">
                    Strategies:
                    @foreach($status['data']['strategies_supported'] as $s)
                        <span class="badge bg-blue-50 text-blue-700 mr-1">{{ $s }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>

<!-- Stats -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <div class="text-3xl font-bold text-slate-900">{{ $stats['total_entities'] }}</div>
        <div class="text-xs text-slate-500 mt-1">Total Entity</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <div class="text-3xl font-bold text-blue-600">{{ $stats['semarang_count'] }}</div>
        <div class="text-xs text-slate-500 mt-1">Semarang</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <div class="text-3xl font-bold text-emerald-600">{{ $stats['surabaya_count'] }}</div>
        <div class="text-xs text-slate-500 mt-1">Surabaya</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <div class="text-3xl font-bold text-amber-600">{{ $stats['runs_this_month'] }}</div>
        <div class="text-xs text-slate-500 mt-1">Run Bulan Ini</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <div class="text-3xl font-bold text-green-600">{{ $stats['success_this_month'] }}</div>
        <div class="text-xs text-slate-500 mt-1">Sukses Bulan Ini</div>
    </div>
</div>

<!-- Quick Action -->
<div class="mb-8">
    <a href="{{ route('run.form') }}" class="inline-flex items-center px-5 py-3 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Run Extraction Baru
    </a>
</div>

<!-- Entity List -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    @foreach($entitiesByRegion as $region => $entities)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="font-bold text-slate-900 flex items-center">
                    @if($region === 'semarang')
                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                    @else
                        <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2"></span>
                    @endif
                    {{ ucfirst($region) }}
                    <span class="text-sm text-slate-500 font-normal ml-2">({{ count($entities) }} entity)</span>
                </h2>
            </div>
            <div class="divide-y divide-slate-100">
                @foreach($entities as $entity)
                    <div class="px-5 py-3 flex items-center justify-between card-hover">
                        <div>
                            <div class="font-medium text-slate-900">{{ $entity->name }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                <span class="font-mono">{{ $entity->code }}</span>
                                &middot;
                                Strategy <span class="badge bg-slate-100 text-slate-700">{{ $entity->extraction_strategy }}</span>
                                &middot;
                                Branch {{ $entity->branch_id }}
                            </div>
                        </div>
                        <a href="{{ route('run.form', ['entity_id' => $entity->id]) }}" class="text-xs text-slate-600 hover:text-slate-900 font-medium">
                            Run &rarr;
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<!-- Recent Runs -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
        <h2 class="font-bold text-slate-900">Run Terbaru</h2>
        <a href="{{ route('run.history') }}" class="text-xs text-slate-600 hover:text-slate-900">Lihat semua &rarr;</a>
    </div>
    @if($recentRuns->isEmpty())
        <div class="px-5 py-8 text-center text-sm text-slate-500">
            Belum ada riwayat run.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Waktu</th>
                        <th class="px-5 py-3 text-left">Entity</th>
                        <th class="px-5 py-3 text-left">Periode</th>
                        <th class="px-5 py-3 text-right">Records</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentRuns as $run)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-500 font-mono text-xs">{{ $run->created_at->format('d M H:i') }}</td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900">{{ $run->entity->name }}</div>
                                <div class="text-xs text-slate-500">{{ $run->profile->name }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-700">{{ $run->period_label }}</td>
                            <td class="px-5 py-3 text-right font-mono">{{ $run->total_records ?? '-' }}</td>
                            <td class="px-5 py-3 text-center">
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
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('run.show', $run->id) }}" class="text-xs text-slate-600 hover:text-slate-900 font-medium">Detail &rarr;</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection