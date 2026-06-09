@extends('layouts.app')

@section('title', 'Profile: ' . $profile->name)

@section('content')

<!-- Header -->
<div class="mb-6">
    <a href="{{ route('mapping.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali ke Daftar Mapping</a>
    <div class="flex items-center justify-between mt-2">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center">
                {{ $profile->name }}
                @if($profile->is_default)
                    <span class="badge bg-amber-100 text-amber-700 ml-3">DEFAULT (Read-only)</span>
                @else
                    <span class="badge bg-blue-100 text-blue-700 ml-3">CUSTOM</span>
                @endif
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $profile->entity->name }}
                &middot; Strategy <span class="badge bg-slate-100 text-slate-700">{{ $profile->entity->extraction_strategy }}</span>
                &middot; {{ count($profile->accountMappings) }} mapping rows
            </p>
            @if($profile->description)
                <p class="text-xs text-slate-500 mt-2 italic">{{ $profile->description }}</p>
            @endif
        </div>
        <div class="space-x-2">
            <a href="{{ route('mapping.duplicate.form', $profile->id) }}" class="inline-flex items-center px-4 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium text-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Duplicate
            </a>
            @if(!$profile->is_default)
                <a href="{{ route('mapping.row.create', $profile->id) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium text-sm">
                    + Tambah Mapping
                </a>
                <form action="{{ route('mapping.profile.destroy', $profile->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus profile ini beserta semua mapping-nya?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition font-medium text-sm">Hapus Profile</button>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- Strategy D Config (kalau ada) -->
@if($profile->strategyDConfig)
    <div class="mb-6 bg-purple-50 border border-purple-200 rounded-lg p-4">
        <h3 class="font-semibold text-purple-900 mb-2 text-sm">Konfigurasi Strategy D</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
            <div>
                <div class="text-xs text-purple-700 font-medium mb-1">Debit Accounts (whitelist)</div>
                <div class="flex flex-wrap gap-1">
                    @foreach($profile->strategyDConfig->debit_accounts ?: [] as $acc)
                        <span class="badge bg-white border border-purple-200 text-purple-700 font-mono text-xs">{{ $acc }}</span>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="text-xs text-purple-700 font-medium mb-1">Debit Keywords</div>
                <div class="flex flex-wrap gap-1">
                    @foreach($profile->strategyDConfig->debit_keywords ?: [] as $kw)
                        <span class="badge bg-white border border-purple-200 text-purple-700 text-xs">{{ $kw }}</span>
                    @endforeach
                </div>
            </div>
            <div>
                <div class="text-xs text-purple-700 font-medium mb-1">Default D/C</div>
                <span class="badge bg-white border border-purple-200 text-purple-700">{{ $profile->strategyDConfig->default_dc }}</span>
            </div>
        </div>
    </div>
@endif

<!-- Mapping List -->
<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
    @if($profile->accountMappings->isEmpty())
        <div class="px-5 py-12 text-center text-sm text-slate-500">
            Belum ada mapping di profile ini.
            @if(!$profile->is_default)
                <br><a href="{{ route('mapping.row.create', $profile->id) }}" class="text-slate-900 font-medium hover:underline mt-2 inline-block">+ Tambah mapping pertama</a>
            @endif
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left w-12">#</th>
                        <th class="px-4 py-3 text-left">Mapping Key</th>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-center">D/C</th>
                        <th class="px-4 py-3 text-left">CC / PC</th>
                        <th class="px-4 py-3 text-left">Components</th>
                        <th class="px-4 py-3 text-left">Match</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($profile->accountMappings as $m)
                        <tr class="hover:bg-slate-50 {{ !$m->is_active ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3 text-slate-400 font-mono text-xs">{{ $m->order_index }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $m->mapping_key }}</td>
                            <td class="px-4 py-3">
                                <div class="font-mono text-xs">{{ $m->account_number }}</div>
                                <div class="text-xs text-slate-500">{{ $m->account_name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($m->account_type === 'Cost center')
                                    <span class="badge bg-blue-100 text-blue-700">Cost center</span>
                                @elseif($m->account_type === 'Aggregate')
                                    <span class="badge bg-purple-100 text-purple-700">Aggregate</span>
                                @else
                                    <span class="badge bg-slate-100 text-slate-700">Individual</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($m->transaction_value === 'Debit')
                                    <span class="badge bg-green-100 text-green-700">D</span>
                                @else
                                    <span class="badge bg-red-100 text-red-700">C</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                @if($m->cost_center)
                                    CC: {{ $m->cost_center }}
                                @endif
                                @if($m->use_profit_center && $m->profit_center)
                                    PC: {{ $m->profit_center }}
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($m->components)
                                    <div class="text-xs text-slate-600">
                                        @foreach(array_slice($m->components, 0, 2) as $c)
                                            <div class="truncate max-w-[200px]" title="{{ $c }}">{{ $c }}</div>
                                        @endforeach
                                        @if(count($m->components) > 2)
                                            <span class="text-slate-400">+{{ count($m->components) - 2 }} more</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($m->match_keywords)
                                    <div class="text-xs text-slate-600 italic">
                                        {{ collect($m->match_keywords)->take(2)->implode(', ') }}
                                        @if(count($m->match_keywords) > 2)
                                            <span class="text-slate-400">+{{ count($m->match_keywords) - 2 }}</span>
                                        @endif
                                    </div>
                                @elseif($m->match_account_name)
                                    <div class="text-xs text-slate-600 italic">{{ $m->match_account_name }}</div>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if(!$profile->is_default)
                                    <a href="{{ route('mapping.row.edit', $m->id) }}" class="text-xs text-slate-600 hover:text-slate-900 font-medium mr-2">Edit</a>
                                    <form action="{{ route('mapping.row.destroy', $m->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus mapping ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 hover:text-red-700 font-medium">Hapus</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-400">read-only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@endsection