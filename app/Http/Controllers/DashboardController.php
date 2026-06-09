<?php

namespace App\Http\Controllers;

use App\Models\GlEntity;
use App\Models\GlRunHistory;
use App\Services\PythonServiceClient;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(protected PythonServiceClient $pythonClient)
    {
    }

    public function index()
    {
        $entities = GlEntity::active()
            ->with('defaultProfile')
            ->orderBy('region')
            ->orderBy('name')
            ->get();

        // Group by region
        $entitiesByRegion = $entities->groupBy('region');

        // Statistik
        $stats = [
            'total_entities' => $entities->count(),
            'semarang_count' => $entities->where('region', 'semarang')->count(),
            'surabaya_count' => $entities->where('region', 'surabaya')->count(),
            'runs_this_month' => GlRunHistory::whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),
            'success_this_month' => GlRunHistory::where('status', 'success')
                ->whereYear('created_at', Carbon::now()->year)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),
        ];

        // Recent runs
        $recentRuns = GlRunHistory::with(['entity', 'profile'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Python service health (quick check)
        $health = $this->pythonClient->health();

        return view('dashboard.index', [
            'entitiesByRegion' => $entitiesByRegion,
            'stats' => $stats,
            'recentRuns' => $recentRuns,
            'health' => $health,
        ]);
    }
}