@extends('layouts.app')

@section('title', 'Run Extraction')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Run Extraction</h1>
        <p class="text-sm text-slate-500 mt-1">Pilih entity, profile mapping, dan periode untuk generate Excel SAP.</p>
    </div>

    <!-- Info Reminder -->
    <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
        <div class="flex items-start">
            <svg class="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <strong>Penting:</strong> Sebelum klik Run, pastikan report sudah di-trigger di Talenta untuk entity yang sama. Python akan ambil report <em>terbaru</em> dari history Talenta yang match dengan <code class="font-mono bg-amber-100 px-1 rounded">ledger_code</code>.
            </div>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('run.execute') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <!-- Step 1: Entity -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">1</span>
                Pilih Entity
            </label>
            <select name="entity_id" id="entity_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                <option value="">-- Pilih entity --</option>
                @foreach($entities->groupBy('region') as $region => $entitiesInRegion)
                    <optgroup label="{{ ucfirst($region) }}">
                        @foreach($entitiesInRegion as $entity)
                            <option value="{{ $entity->id }}"
                                data-strategy="{{ $entity->extraction_strategy }}"
                                data-ledger-code="{{ $entity->ledger_code }}"
                                data-branch="{{ $entity->branch_id }}"
                                {{ (string) $preselectedEntityId === (string) $entity->id ? 'selected' : '' }}>
                                {{ $entity->name }} ({{ $entity->code }})
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <div id="entity-info" class="mt-2 text-xs text-slate-500 hidden">
                <span class="font-mono">Strategy: <span id="info-strategy" class="font-semibold"></span></span>
                &middot;
                <span class="font-mono">Ledger: <span id="info-ledger"></span></span>
                &middot;
                <span class="font-mono">Branch: <span id="info-branch"></span></span>
            </div>
        </div>

        <!-- Step 2: Profile -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">2</span>
                Pilih Mapping Profile
            </label>
            <select name="profile_id" id="profile_id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900" disabled>
                <option value="">-- Pilih entity dulu --</option>
            </select>
            <p class="text-xs text-slate-500 mt-1">Default profile berisi mapping bawaan dari script Python original.</p>
        </div>

        <!-- Step 3: Periode -->
        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">3</span>
                Pilih Periode
            </label>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-slate-500">Tahun</label>
                    <select name="year" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                        @for($y = $currentYear + 1; $y >= $currentYear - 3; $y--)
                            <option value="{{ $y }}" {{ $y === $currentYear ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="text-xs text-slate-500">Bulan</label>
                    <select name="month" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
                        @php
                            $monthNames = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                            $prevMonth = $currentMonth === 1 ? 12 : $currentMonth - 1;
                        @endphp
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $m === $prevMonth ? 'selected' : '' }}>{{ $monthNames[$m] }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-1">Default: bulan lalu (karena GL biasanya diproses awal bulan untuk data bulan sebelumnya).</p>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali</a>
            <button type="submit" id="submit-btn" class="inline-flex items-center px-5 py-2.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="btn-text">Run Extraction</span>
                <span id="btn-spinner" class="hidden ml-2"><span class="spinner"></span></span>
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

@push('scripts')
<script>
    const entitySelect = document.getElementById('entity_id');
    const profileSelect = document.getElementById('profile_id');
    const entityInfo = document.getElementById('entity-info');
    const infoStrategy = document.getElementById('info-strategy');
    const infoLedger = document.getElementById('info-ledger');
    const infoBranch = document.getElementById('info-branch');
    const form = entitySelect.closest('form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');

    async function loadProfiles(entityId) {
        if (!entityId) {
            profileSelect.innerHTML = '<option value="">-- Pilih entity dulu --</option>';
            profileSelect.disabled = true;
            entityInfo.classList.add('hidden');
            return;
        }

        // Show entity info
        const selectedOption = entitySelect.options[entitySelect.selectedIndex];
        infoStrategy.textContent = selectedOption.dataset.strategy;
        infoLedger.textContent = selectedOption.dataset.ledgerCode;
        infoBranch.textContent = selectedOption.dataset.branch;
        entityInfo.classList.remove('hidden');

        // Load profiles
        profileSelect.innerHTML = '<option value="">Loading...</option>';
        profileSelect.disabled = true;
        try {
            const res = await fetch(`{{ url('/run/profiles') }}/${entityId}`);
            const data = await res.json();
            profileSelect.innerHTML = '';
            data.profiles.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name + (p.is_default ? ' (default)' : '');
                if (p.is_default) opt.selected = true;
                profileSelect.appendChild(opt);
            });
            profileSelect.disabled = false;
        } catch (e) {
            profileSelect.innerHTML = '<option value="">Error loading profiles</option>';
        }
    }

    entitySelect.addEventListener('change', e => loadProfiles(e.target.value));

    // Auto-load if preselected
    if (entitySelect.value) {
        loadProfiles(entitySelect.value);
    }

    // Show spinner on submit
    form.addEventListener('submit', () => {
        submitBtn.disabled = true;
        btnText.textContent = 'Sedang Memproses...';
        btnSpinner.classList.remove('hidden');
    });
</script>
@endpush

@endsection