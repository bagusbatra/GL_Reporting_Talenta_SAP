@extends('layouts.app')

@section('title', 'Reset Center - PIN Required')

@section('content')

<div class="max-w-md mx-auto">

    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-3">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Reset Center</h1>
        <p class="text-sm text-slate-500 mt-2">Halaman ini berisi tools untuk reset data sistem. Akses terbatas.</p>
    </div>

    <div class="bg-red-50 border-l-4 border-red-500 rounded-r-lg p-4 mb-6">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-red-600 mr-2 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-red-800">
                <strong>WARNING:</strong> Tools di halaman ini akan menghapus data secara permanen. Hanya gunakan untuk testing atau cleanup. Pastikan backup database sudah dilakukan sebelum reset.
            </div>
        </div>
    </div>

    <form action="{{ route('reset_center.verify_pin') }}" method="POST" class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-semibold text-slate-900 mb-2">Masukkan PIN</label>
            <input type="password" name="pin" required autofocus
                placeholder="••••" 
                class="w-full border border-slate-300 rounded-lg px-3 py-3 text-center text-lg font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500">
            <p class="text-xs text-slate-500 mt-2">Hubungi IT admin kalau lo gak tau PIN-nya.</p>
        </div>

        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm">
                <ul class="list-disc pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Kembali ke Dashboard</a>
            <button type="submit" class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium">
                Verify PIN
            </button>
        </div>
    </form>

</div>

@endsection