@extends('layouts.app')

@section('title', 'Uji Coba Fill Text')

@section('content')

<div class="max-w-3xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Uji Coba Fill Text</h1>
        <p class="text-sm text-slate-500 mt-1">Upload <strong>Ledger Mapping Export</strong> + <strong>Target File (Excel GL)</strong> untuk melihat hasil mapping subtype dan download.</p>
    </div>

    <form action="{{ route('test_fill_text.process') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-900 text-white mr-2">1</span>
                Upload Ledger Mapping Export
            </label>
            <input type="file" name="ledger_file" required accept=".xlsx,.xls" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
            <p class="text-xs text-slate-500 mt-1">File Export Ledger Mapping dari Talenta (kolom: GL Entry, Description, Type, GL Type, Component ID, Components, dll).</p>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-semibold text-slate-900 mb-2">
                <span class="badge bg-slate-100 text-slate-700 mr-2">2</span>
                Upload Target File (Excel GL)
            </label>
            <input type="file" name="target_file" required accept=".xlsx,.xls" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 file:cursor-pointer">
            <p class="text-xs text-slate-500 mt-1">File Excel hasil generate (format 20 kolom SAP) yang ingin diperbarui kolom Text-nya.</p>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali</a>
            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition font-medium">
                Proses & Lihat Mapping
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
