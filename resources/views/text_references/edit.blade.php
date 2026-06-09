@extends('layouts.app')

@section('title', 'Edit Text Reference')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('text_references.index') }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Edit Text Reference</h1>
    </div>

    <form action="{{ route('text_references.update', $reference->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Account Number</label>
            <input type="text" value="{{ $reference->account_number }}" disabled class="w-full border border-slate-300 bg-slate-100 rounded-lg px-3 py-2 font-mono text-sm">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Cost Center</label>
            <input type="text" value="{{ $reference->cost_center ?: '(kosong - Aggregate)' }}" disabled class="w-full border border-slate-300 bg-slate-100 rounded-lg px-3 py-2 font-mono text-sm">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Text Value *</label>
            <input type="text" name="text_value" required value="{{ old('text_value', $reference->text_value) }}" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
        </div>

        <div class="bg-slate-50 rounded-lg p-3 mb-4 text-xs text-slate-600">
            <strong>Stats:</strong> Sudah dipakai {{ $reference->use_count }}x.
            Terakhir: {{ $reference->last_used_at?->format('d M Y H:i') ?: '-' }}.
            Sumber: <code class="font-mono">{{ $reference->learned_from ?: '-' }}</code>.
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
            <a href="{{ route('text_references.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
            <button type="submit" class="px-5 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">Update</button>
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