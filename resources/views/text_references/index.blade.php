@extends('layouts.app')

@section('title', 'Text References')

@section('content')

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Text References</h1>
        <p class="text-sm text-slate-500 mt-1">Knowledge base text yang dipakai untuk auto-fill kolom Text di Excel.</p>
    </div>
    <a href="{{ route('text_references.create') }}" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium text-sm">
        + Tambah Reference
    </a>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
        <div class="text-3xl font-bold text-slate-900">{{ number_format($stats['total']) }}</div>
        <div class="text-xs text-slate-500 mt-1">Total Text References</div>
    </div>
    @if($stats['most_used'])
        <div class="bg-white rounded-lg shadow-sm p-4 border border-slate-200">
            <div class="text-xs text-slate-500 font-semibold mb-1">Paling Sering Dipakai</div>
            <div class="text-sm font-medium text-slate-700">{{ $stats['most_used']->text_value }}</div>
            <div class="text-xs text-slate-400 mt-1 font-mono">
                {{ $stats['most_used']->account_number }} {{ $stats['most_used']->cost_center ? '/ ' . $stats['most_used']->cost_center : '' }}
                ({{ $stats['most_used']->use_count }}x)
            </div>
        </div>
    @endif
</div>

<!-- Filter -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
    <form action="{{ route('text_references.index') }}" method="GET" class="flex gap-3">
        <div class="flex-1">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari account, CC, atau text..." class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        </div>
        <button type="submit" class="px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium text-sm">Cari</button>
        <a href="{{ route('text_references.index') }}" class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm">Reset</a>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    @if($references->isEmpty())
        <div class="px-5 py-12 text-center text-sm text-slate-500">
            Belum ada text reference. Knowledge base akan tumbuh tiap kali Fill Text dijalankan.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Account</th>
                        <th class="px-5 py-3 text-left">Cost Center</th>
                        <th class="px-5 py-3 text-left">Text</th>
                        <th class="px-5 py-3 text-center">Use Count</th>
                        <th class="px-5 py-3 text-left">Sumber</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($references as $ref)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-mono">{{ $ref->account_number }}</td>
                            <td class="px-5 py-3 font-mono">{{ $ref->cost_center ?: '-' }}</td>
                            <td class="px-5 py-3">{{ $ref->text_value }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="badge bg-slate-100 text-slate-700">{{ $ref->use_count }}x</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $ref->learned_from ?: '-' }}</td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('text_references.edit', $ref->id) }}" class="text-xs text-slate-600 hover:text-slate-900 font-medium mr-2">Edit</a>
                                <form action="{{ route('text_references.destroy', $ref->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus reference ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-medium">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50">
            {{ $references->links() }}
        </div>
    @endif
</div>

@endsection