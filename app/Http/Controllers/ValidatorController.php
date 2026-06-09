<?php

namespace App\Http\Controllers;

use App\Models\GlRunHistory;
use App\Services\ValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;

class ValidatorController extends Controller
{
    public function __construct(protected ValidatorService $validatorService)
    {
    }

    /**
     * GET /validator - tampilkan form upload
     */
    public function showForm(Request $request)
    {
        $preselectedHistoryId = $request->query('history_id');
        $preselectedHistory = null;

        if ($preselectedHistoryId) {
            $preselectedHistory = GlRunHistory::with('entity')->find($preselectedHistoryId);
        }

        // Tampilkan history yang status success untuk pilihan
        $recentRuns = GlRunHistory::where('status', 'success')
            ->with('entity')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('validator.form', [
            'recentRuns' => $recentRuns,
            'preselectedHistory' => $preselectedHistory,
        ]);
    }

    /**
     * POST /validator/run - eksekusi validasi
     */
    public function runValidation(Request $request)
    {
        $validator = ValidatorFacade::make($request->all(), [
            'asli_file' => 'required|file|mimes:xlsx,xls|max:10240',
            'history_id' => 'required|exists:gl_run_histories,id',
        ], [
            'asli_file.required' => 'File asli Talenta harus di-upload.',
            'asli_file.mimes' => 'File harus berformat Excel (.xlsx atau .xls).',
            'asli_file.max' => 'Ukuran file maksimal 10MB.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $history = GlRunHistory::with(['entity', 'profile'])->findOrFail($request->history_id);

        if (!$history->output_file_path || !file_exists($history->output_file_path)) {
            return back()->with('error', 'File output run tidak ditemukan. Coba run ulang.');
        }

        // Simpan file upload sementara
        $aslifile = $request->file('asli_file');
        $aslifileName = 'asli_' . Str::random(8) . '.' . $aslifile->getClientOriginalExtension();
        $aslipath = storage_path('app/gl_uploads/' . $aslifileName);
        $aslifile->move(storage_path('app/gl_uploads'), $aslifileName);

        // Run validasi
        $result = $this->validatorService->validate($aslipath, $history->output_file_path);

        // Hapus file upload temp setelah dipakai
        if (file_exists($aslipath)) {
            @unlink($aslipath);
        }

        if (isset($result['error'])) {
            return back()->with('error', 'Validasi gagal: ' . $result['error']);
        }

        // Update history dengan status validasi
        $history->validation_status = $result['summary']['overall_status'] === 'match' ? 'match' : 'mismatch';
        $history->validation_details = $result['summary'];
        $history->save();

        return view('validator.result', [
            'history' => $history,
            'result' => $result,
            'asli_filename' => $aslifile->getClientOriginalName(),
        ]);
    }
}