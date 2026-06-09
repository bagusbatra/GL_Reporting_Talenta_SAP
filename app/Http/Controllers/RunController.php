<?php

namespace App\Http\Controllers;

use App\Models\GlEntity;
use App\Models\GlMappingProfile;
use App\Models\GlRunHistory;
use App\Services\PythonServiceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class RunController extends Controller
{
    public function __construct(protected PythonServiceClient $pythonClient)
    {
    }

    /**
     * GET /run - tampilkan form pilih entity/profile/bulan/tahun
     */
    public function showForm(Request $request)
    {
        $entities = GlEntity::active()
            ->with('defaultProfile')
            ->orderBy('region')
            ->orderBy('name')
            ->get();

        $preselectedEntityId = $request->query('entity_id');

        return view('run.form', [
            'entities' => $entities,
            'preselectedEntityId' => $preselectedEntityId,
            'currentYear' => Carbon::now()->year,
            'currentMonth' => Carbon::now()->month,
        ]);
    }

    /**
     * GET /run/profiles/{entity}  -> JSON list profile untuk entity (untuk dropdown dinamis)
     */
    public function getProfiles(GlEntity $entity)
    {
        $profiles = $entity->mappingProfiles()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'description']);

        return response()->json([
            'entity' => $entity->only(['id', 'code', 'name', 'extraction_strategy']),
            'profiles' => $profiles,
        ]);
    }

    /**
     * POST /run/execute - eksekusi extraction
     */
    public function execute(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'entity_id' => 'required|exists:gl_entities,id',
            'profile_id' => 'required|exists:gl_mapping_profiles,id',
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $entity = GlEntity::findOrFail($request->entity_id);
        $profile = GlMappingProfile::findOrFail($request->profile_id);

        if ($profile->entity_id !== $entity->id) {
            return back()->with('error', 'Profile tidak cocok dengan entity yang dipilih.');
        }

        // Buat history record terlebih dulu
        $history = GlRunHistory::create([
            'entity_id' => $entity->id,
            'profile_id' => $profile->id,
            'period_year' => $request->year,
            'period_month' => $request->month,
            'status' => 'running',
            'run_by' => $request->user()?->name ?? 'system',
            'started_at' => Carbon::now(),
        ]);

        // Call Python service
        $result = $this->pythonClient->run($entity, $profile, $request->year, $request->month);

        // Update history berdasarkan hasil
        $history->completed_at = Carbon::now();

        if ($result['status'] === 'success') {
            $history->status = 'success';
            $history->total_records = $result['total_records'] ?? null;
            $history->total_debit = $result['total_debit'] ?? null;
            $history->total_credit = $result['total_credit'] ?? null;
            $history->output_file_path = $result['output_file'] ?? null;
            $history->save();

            return redirect()
                ->route('run.show', $history->id)
                ->with('success', 'Generate Excel berhasil!');
        }

        // Failed
        $history->status = 'failed';
        $history->error_message = $result['error'] ?? 'Unknown error';
        $history->save();

        return redirect()
            ->route('run.show', $history->id)
            ->with('error', 'Generate gagal: ' . ($result['error'] ?? 'Unknown'));
    }

    /**
     * GET /run/show/{history} - tampilkan detail run history
     */
    public function show(GlRunHistory $history)
    {
        $history->load(['entity', 'profile']);
        return view('run.show', ['history' => $history]);
    }

    /**
     * GET /run/download/{history} - download file output Excel
     */
    public function download(GlRunHistory $history)
    {
        if (!$history->output_file_path || !file_exists($history->output_file_path)) {
            return back()->with('error', 'File output tidak ditemukan.');
        }

        $filename = basename($history->output_file_path);
        return response()->download($history->output_file_path, $filename);
    }

    /**
     * GET /run/history - list semua run history
     */
    public function history(Request $request)
    {
        $query = GlRunHistory::with(['entity', 'profile'])
            ->orderByDesc('created_at');

        if ($request->filled('entity_id')) {
            $query->where('entity_id', $request->entity_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('year')) {
            $query->where('period_year', $request->year);
        }
        if ($request->filled('month')) {
            $query->where('period_month', $request->month);
        }

        $histories = $query->paginate(20);
        $entities = GlEntity::active()->orderBy('name')->get();

        return view('run.history', [
            'histories' => $histories,
            'entities' => $entities,
            'filters' => $request->only(['entity_id', 'status', 'year', 'month']),
        ]);
    }

    /**
     * GET /run/health - cek kesehatan Python service
     */
    public function checkHealth()
    {
        $health = $this->pythonClient->health();
        return view('run.health', ['health' => $health]);
    }
}