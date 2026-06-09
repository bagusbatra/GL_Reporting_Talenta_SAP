@extends('layouts.app')

@section('title', 'Tambah Text Reference')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('text_references.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Tambah Text Reference</h1>
        <p class="text-sm text-slate-500 mt-1">Tambah entry baru ke knowledge base. Dipakai untuk auto-fill Text di Excel.</p>
    </div>

    <form action="{{ route('text_references.store') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Account Number *</label>
            <input type="text" name="account_number" required value="{{ old('account_number') }}" placeholder="5204000009" class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Cost Center (opsional)</label>
            <input type="text" name="cost_center" value="{{ old('cost_center') }}" placeholder="1094020002 (kosongkan kalau Aggregate)" class="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            <p class="text-xs text-slate-500 mt-1">Kosongkan untuk Aggregate type (CC kosong di Excel).</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Text Value *</label>
            <input type="text" name="text_value" required value="{{ old('text_value') }}" placeholder="Gaji KMI Sby" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            <p class="text-xs text-slate-500 mt-1">Text yang akan di-fill ke kolom Text Excel kalau ada row dengan account+CC yang sama.</p>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
            <a href="{{ route('text_references.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
            <button type="submit" class="px-5 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">Simpan</button>
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

@endsection