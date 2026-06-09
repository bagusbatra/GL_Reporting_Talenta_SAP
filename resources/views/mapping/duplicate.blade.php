@extends('layouts.app')

@section('title', 'Duplicate Profile')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('mapping.profile', $profile->id) }}" class="text-sm text-slate-500 hover:text-slate-900">&larr; Kembali</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Duplicate Profile</h1>
        <p class="text-sm text-slate-500 mt-1">Buat copy dari profile <strong>{{ $profile->name }}</strong> untuk entity <strong>{{ $profile->entity->name }}</strong>.</p>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-800">
        <strong>Catatan:</strong> Profile baru akan punya {{ count($profile->accountMappings) }} mapping yang di-copy dari profile asal.
        @if($profile->strategyDConfig)
            Konfigurasi Strategy D juga akan di-copy.
        @endif
        Setelah dibuat, lo bebas edit/tambah/hapus mapping di profile custom ini.
    </div>

    <form action="{{ route('mapping.duplicate', $profile->id) }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Nama Profile Baru *</label>
            <input type="text" name="name" required value="{{ old('name', 'Custom-' . now()->format('Y-m')) }}" placeholder="Custom-2026-05" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">
            <p class="text-xs text-slate-500 mt-1">Contoh: "Custom-2026-05", "Revisi-April", dst</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Deskripsi (opsional)</label>
            <textarea name="description" rows="3" placeholder="Catatan tentang perubahan di profile ini..." class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900">{{ old('description') }}</textarea>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
            <a href="{{ route('mapping.profile', $profile->id) }}" class="text-sm text-slate-600 hover:text-slate-900">Batal</a>
            <button type="submit" class="inline-flex items-center px-5 py-2 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">
                Duplicate Profile
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

@endsection