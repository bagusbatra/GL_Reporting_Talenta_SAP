@extends('layouts.app')

@section('title', 'Health Check')

@section('content')

<div class="max-w-3xl mx-auto">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Health Check</h1>
        <p class="text-sm text-slate-500 mt-1">Cek status koneksi ke Python service yang menjalankan extraction.</p>
    </div>

    <!-- Refresh button -->
    <div class="mb-4 flex items-center justify-between">
        <p class="text-xs text-slate-500 font-mono">Last checked: {{ now()->format('d M Y H:i:s') }}</p>
        <a href="{{ route('run.health') }}" class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-white rounded-lg hover:bg-slate-800 transition text-sm font-medium">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Refresh
        </a>
    </div>

    <!-- Service Cards -->
    <div class="space-y-4">
        @foreach($health as $region => $status)
            <div class="bg-white rounded-lg shadow-sm border {{ $status['online'] ? 'border-green-200' : 'border-red-200' }} overflow-hidden">
                <div class="px-5 py-4 {{ $status['online'] ? 'bg-green-50' : 'bg-red-50' }} border-b {{ $status['online'] ? 'border-green-200' : 'border-red-200' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            @if($status['online'])
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                            @else
                                <span class="w-3 h-3 bg-red-500 rounded-full mr-3"></span>
                            @endif
                            <h2 class="font-bold text-slate-900">Python Service - {{ ucfirst($region) }}</h2>
                        </div>
                        @if($status['online'])
                            <span class="badge bg-green-100 text-green-700">ONLINE</span>
                        @else
                            <span class="badge bg-red-100 text-red-700">OFFLINE</span>
                        @endif
                    </div>
                </div>

                <div class="px-5 py-4">
                    <dl class="space-y-2 text-sm">
                        <div class="grid grid-cols-3 gap-3">
                            <dt class="text-slate-500">URL</dt>
                            <dd class="col-span-2 font-mono text-xs">{{ $status['url'] }}</dd>
                        </div>

                        @if($status['online'] && $status['data'])
                            <div class="grid grid-cols-3 gap-3">
                                <dt class="text-slate-500">Service</dt>
                                <dd class="col-span-2">{{ $status['data']['service'] ?? '-' }}</dd>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <dt class="text-slate-500">Port</dt>
                                <dd class="col-span-2 font-mono">{{ $status['data']['port'] ?? '-' }}</dd>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <dt class="text-slate-500">Strategies</dt>
                                <dd class="col-span-2">
                                    @foreach($status['data']['strategies_supported'] ?? [] as $s)
                                        <span class="badge bg-blue-100 text-blue-700 mr-1">{{ $s }}</span>
                                    @endforeach
                                </dd>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <dt class="text-slate-500">Timestamp</dt>
                                <dd class="col-span-2 font-mono text-xs">{{ $status['data']['timestamp'] ?? '-' }}</dd>
                            </div>
                        @else
                            <div class="bg-red-50 rounded p-3 mt-2">
                                <p class="text-sm text-red-700 font-medium mb-1">Service tidak bisa diakses</p>
                                <p class="text-xs text-red-600 font-mono break-all">{{ $status['error'] ?: 'Connection refused' }}</p>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        @endforeach
    </div>

    <!-- How to Start Service -->
    <div class="mt-8 bg-slate-50 border border-slate-200 rounded-lg p-5">
        <h3 class="font-semibold text-slate-900 mb-3">Cara Menjalankan Python Service</h3>
        <p class="text-sm text-slate-600 mb-3">Buka 2 terminal dari root project, lalu jalankan:</p>
        <div class="space-y-2">
            <div class="bg-slate-900 text-slate-100 rounded p-3 font-mono text-xs">
                <span class="text-slate-500"># Terminal 1 - Semarang (port 8091)</span><br>
                python python_semarang.py
            </div>
            <div class="bg-slate-900 text-slate-100 rounded p-3 font-mono text-xs">
                <span class="text-slate-500"># Terminal 2 - Surabaya (port 8092)</span><br>
                python python_surabaya.py
            </div>
        </div>
        <p class="text-xs text-slate-500 mt-3">
            Catatan: Biarkan kedua terminal tetap berjalan. Untuk stop service, tekan <code class="font-mono bg-slate-200 px-1 rounded">Ctrl+C</code>.
        </p>
    </div>

</div>

@endsection