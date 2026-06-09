{{-- Partial: form fields untuk add/edit mapping row --}}
{{-- Variables: $mapping (atau null untuk add), $profile --}}

@php
    $m = $mapping ?? null;
    $editing = $m !== null;
@endphp

<!-- Mapping Key -->
<div class="mb-4">
    <label class="block text-sm font-semibold text-slate-900 mb-2">Mapping Key *</label>
    <input type="text" name="mapping_key" required value="{{ old('mapping_key', $m?->mapping_key) }}" placeholder="5204000009 atau 2010000005_DENDA_SAKIT" class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
    <p class="text-xs text-slate-500 mt-1">Identifier unik di profile ini. Bisa sama dengan account number, atau pakai suffix untuk multi-variant (e.g. <code>2010000005_DENDA_SAKIT</code>).</p>
</div>

<!-- Account Number & Name -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="md:col-span-1">
        <label class="block text-sm font-semibold text-slate-900 mb-2">Account Number *</label>
        <input type="text" name="account_number" required value="{{ old('account_number', $m?->account_number) }}" placeholder="5204000009" class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-slate-900 mb-2">Account Name *</label>
        <input type="text" name="account_name" required value="{{ old('account_name', $m?->account_name) }}" placeholder="Gaji Karyawan Staff Prod Semarang" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
    </div>
</div>

<!-- Account Type & Trans Value -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">Account Type *</label>
        <select name="account_type" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            @foreach(['Cost center', 'Aggregate', 'Individual'] as $t)
                <option value="{{ $t }}" {{ old('account_type', $m?->account_type) === $t ? 'selected' : '' }}>{{ $t }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">D/C *</label>
        <select name="transaction_value" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            <option value="Debit" {{ old('transaction_value', $m?->transaction_value) === 'Debit' ? 'selected' : '' }}>Debit (PstKy 40)</option>
            <option value="Credit" {{ old('transaction_value', $m?->transaction_value) === 'Credit' ? 'selected' : '' }}>Credit (PstKy 50)</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">Order Index</label>
        <input type="number" name="order_index" min="1" value="{{ old('order_index', $m?->order_index ?? '') }}" placeholder="auto" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        <p class="text-xs text-slate-500 mt-1">Urutan eksekusi.</p>
    </div>
</div>

<!-- CC & PC -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">Cost Center (fixed)</label>
        <input type="text" name="cost_center" value="{{ old('cost_center', $m?->cost_center) }}" placeholder="1094020002 (opsional)" class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        <p class="text-xs text-slate-500 mt-1">Kosongkan jika CC dinamis dari API.</p>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">Profit Center</label>
        <input type="text" name="profit_center" value="{{ old('profit_center', $m?->profit_center ?? '200301') }}" placeholder="200301" class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
    </div>
    <div class="flex items-center pt-7">
        <label class="flex items-center cursor-pointer">
            <input type="hidden" name="use_profit_center" value="0">
            <input type="checkbox" name="use_profit_center" value="1" {{ old('use_profit_center', $m?->use_profit_center) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
            <span class="ml-2 text-sm text-slate-700">Gunakan Profit Center</span>
        </label>
    </div>
</div>

<!-- Components -->
<div class="mb-4">
    <label class="block text-sm font-semibold text-slate-900 mb-2">Components (1 per baris)</label>
    <textarea name="components" rows="3" placeholder="Basic Salary&#10;Tunjangan Jabatan&#10;Tunjangan Transport" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">{{ old('components', is_array($m?->components) ? implode("\n", $m->components) : '') }}</textarea>
    <p class="text-xs text-slate-500 mt-1">Daftar komponen yang akan di-aggregate (untuk strategy A & B). Pisahkan dengan baris baru.</p>
</div>

<!-- Match Strategy -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">Match Account Name</label>
        <input type="text" name="match_account_name" value="{{ old('match_account_name', $m?->match_account_name) }}" placeholder="potongan denda sakit (opsional)" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        <p class="text-xs text-slate-500 mt-1">Untuk Strategy B Surabaya yang match via field ini.</p>
    </div>
    <div>
        <label class="block text-sm font-semibold text-slate-900 mb-2">Match Keywords (1 per baris)</label>
        <textarea name="match_keywords" rows="3" placeholder="denda sakit&#10;keterlambatan" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">{{ old('match_keywords', is_array($m?->match_keywords) ? implode("\n", $m->match_keywords) : '') }}</textarea>
        <p class="text-xs text-slate-500 mt-1">Keyword di account_name. Pisahkan baris baru.</p>
    </div>
</div>

@if($editing)
    <div class="mb-4">
        <label class="flex items-center cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $m?->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
            <span class="ml-2 text-sm text-slate-700">Aktif</span>
        </label>
    </div>
@endif