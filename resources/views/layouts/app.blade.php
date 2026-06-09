<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'GL Reporting') &mdash; {{ config('app.name', 'GL Reporting Talenta SAP') }}</title>

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        * { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .gradient-bg {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }
        .card-hover { transition: all 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            animation: spin 0.8s linear infinite;
            display: inline-block;
            vertical-align: middle;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .badge { display: inline-flex; align-items: center; padding: 2px 10px; border-radius: 9999px; font-size: 11px; font-weight: 600; letter-spacing: 0.02em; }
    </style>

    @stack('head')
</head>
<body class="bg-slate-50 min-h-screen">

    <!-- Navbar -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="font-bold text-lg">GL Reporting</span>
                        <span class="text-xs text-slate-400 hidden sm:inline">Talenta &rarr; SAP</span>
                    </a>
                    <div class="hidden md:flex items-center space-x-1">
                        <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('dashboard') ? 'bg-slate-700' : '' }}">Dashboard</a>
                        <a href="{{ route('run.form') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('run.form') ? 'bg-slate-700' : '' }}">Run Extraction</a>
                        <a href="{{ route('run.history') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('run.history') ? 'bg-slate-700' : '' }}">Riwayat</a>
                        <a href="{{ route('mapping.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('mapping.*') ? 'bg-slate-700' : '' }}">Mapping</a>
                        <a href="{{ route('validator.form') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('validator.*') ? 'bg-slate-700' : '' }}">Validator</a>
                        <a href="{{ route('fill_text.form') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('fill_text.*') ? 'bg-slate-700' : '' }}">Fill Text</a>
                        <a href="{{ route('text_references.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('text_references.*') ? 'bg-slate-700' : '' }}">Text Refs</a>
                        <a href="{{ route('run.health') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('run.health') ? 'bg-slate-700' : '' }}">Health Check</a>
                        <a href="{{ route('reset_center.index') }}" class="px-3 py-2 rounded-md text-sm font-medium text-red-300 hover:bg-red-900 hover:text-white transition {{ request()->routeIs('reset_center.*') ? 'bg-red-900 text-white' : '' }}">⚠️ Reset</a>
                        <a href="{{ route('help.index') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-slate-700 transition {{ request()->routeIs('help.*') ? 'bg-slate-700' : '' }}">📖 Help</a>
                    </div>
                </div>
                <div class="text-xs text-slate-400 font-mono">
                    {{ now()->format('d M Y, H:i') }}
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash messages -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    {{ session('success') }}
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            </div>
        </div>
    @endif

    <!-- Main content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-12 py-6 text-center text-xs text-slate-400 border-t border-slate-200">
        <p>GL Reporting Talenta SAP &middot; KMI Internal Tools</p>
    </footer>

    @stack('scripts')
</body>
</html>