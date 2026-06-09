@extends('layouts.app')

@section('title', 'Mapping Editor')

@section('content')

<div class="mb-6">
    <h1 class="text-2xl font-bold text-slate-900">Mapping Editor</h1>
    <p class="text-sm text-slate-500 mt-1">Kelola mapping GL Account untuk setiap entity. Default profile tidak bisa di-edit langsung — duplicate dulu menjadi custom.</p>
</div>

<!-- Info -->
<div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-4 text-sm">
    <div class="flex items-start">
        <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <div>
            <strong>Cara pakai:</strong> Klik nama profile untuk lihat detail mapping. Untuk edit, <strong>duplicate dulu profile Default</strong> ke custom, baru bisa di-modify. Profile Default sengaja dibuat read-only untuk safety.
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
                    <div class="px-5 py-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="font-medium text-slate-900">{{ $entity->name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    <span class="font-mono">{{ $entity->code }}</span>
                                    &middot;
                                    Strategy <span class="badge bg-slate-100 text-slate-700">{{ $entity->extraction_strategy }}</span>
                                </div>
                                <!-- Profile list -->
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($entity->mappingProfiles as $profile)
                                        <a href="{{ route('mapping.profile', $profile->id) }}" class="inline-flex items-center px-3 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs hover:bg-slate-100 transition">
                                            @if($profile->is_default)
                                                <span class="badge bg-amber-100 text-amber-700 mr-2" style="font-size:9px;padding:1px 6px;">DEFAULT</span>
                                            @else
                                                <span class="badge bg-blue-100 text-blue-700 mr-2" style="font-size:9px;padding:1px 6px;">CUSTOM</span>
                                            @endif
                                            <span class="font-medium text-slate-700">{{ $profile->name }}</span>
                                            <span class="text-slate-400 ml-1">({{ $profile->account_mappings_count }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@endsection