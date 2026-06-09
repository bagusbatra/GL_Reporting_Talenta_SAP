<?php

namespace App\Http\Controllers;

use App\Models\GlAccountPrefix;
use App\Models\GlRunHistory;
use App\Models\GlTextReference;
use App\Services\FillTextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class FillTextController extends Controller
{
    public function __construct(protected FillTextService $fillTextService)
    {
    }

    public function showForm(Request $request)
    {
        $preselectedHistoryId = $request->query('history_id');

        $recentRuns = GlRunHistory::where('status', 'success')
            ->whereNotNull('output_file_path')
            ->with('entity')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $stats = [
            'total_references' => GlTextReference::count(),
            'most_used' => GlTextReference::orderByDesc('use_count')->limit(5)->get(),
        ];

        return view('fill_text.form', [
            'recentRuns' => $recentRuns,
            'preselectedHistoryId' => $preselectedHistoryId,
            'stats' => $stats,
        ]);
    }

    public function runFill(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'history_id' => 'required|exists:gl_run_histories,id',
            'reference_file' => 'nullable|file|mimes:xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $history = GlRunHistory::with(['entity', 'profile'])->findOrFail($request->history_id);

        if (!$history->output_file_path || !file_exists($history->output_file_path)) {
            return back()->with('error', 'File output run tidak ditemukan. Coba run ulang.');
        }

        $manualReferences = [];
        $refStats = null;
        if ($request->hasFile('reference_file')) {
            $refFile = $request->file('reference_file');
            $refFileName = 'ref_' . Str::random(8) . '.' . $refFile->getClientOriginalExtension();
            $refPath = storage_path('app/gl_references/' . $refFileName);

            if (!is_dir(dirname($refPath))) {
                mkdir(dirname($refPath), 0755, true);
            }

            $refFile->move(storage_path('app/gl_references'), $refFileName);

            $refResult = $this->fillTextService->parseReferenceFile($refPath);

            if (!$refResult['success']) {
                @unlink($refPath);
                return back()->with('error', 'Parse file referensi gagal: ' . $refResult['error']);
            }

            $manualReferences = $refResult['references'];
            $refStats = $refResult['stats'];
        }

        $originalFilename = basename($history->output_file_path);
        $filledFilename = preg_replace('/\.xlsx$/', '_FILLED.xlsx', $originalFilename);
        $filledPath = storage_path('app/gl_filled/' . $filledFilename);

        if (!is_dir(dirname($filledPath))) {
            mkdir(dirname($filledPath), 0755, true);
        }

        $result = $this->fillTextService->fill($history->output_file_path, $filledPath, $manualReferences);

        if (!$result['success']) {
            return back()->with('error', 'Fill text gagal: ' . ($result['error'] ?? 'Unknown error'));
        }

        $history->output_filled_path = $filledPath;
        $history->save();

        $request->session()->flash('fill_result', $result);
        $request->session()->flash('fill_ref_stats', $refStats);

        return redirect()->route('fill_text.result_view', $history->id);
    }

    public function resultView(Request $request, GlRunHistory $history)
    {
        $result = $request->session()->get('fill_result');
        $refStats = $request->session()->get('fill_ref_stats');

        if (!$result) {
            return redirect()->route('fill_text.show', $history->id);
        }

        if (!empty($result['need_fill'])) {
            $accounts = array_unique(array_column($result['need_fill'], 'account'));
            $existingPrefixes = GlAccountPrefix::whereIn('account_number', $accounts)
                ->pluck('prefix', 'account_number')
                ->toArray();

            foreach ($result['need_fill'] as &$row) {
                if (!isset($row['existing_prefix']) || empty($row['existing_prefix'])) {
                    $row['existing_prefix'] = $existingPrefixes[$row['account']] ?? null;
                }

                $row['prefilled_prefix'] = $row['existing_prefix']
                    ?? ($row['extracted_prefix'] ?? null);
            }
        }

        return view('fill_text.result', [
            'history' => $history,
            'result' => $result,
            'refStats' => $refStats,
        ]);
    }

    public function saveManual(Request $request, GlRunHistory $history)
    {
        $validator = Validator::make($request->all(), [
            'inputs' => 'required|array',
            'inputs.*.account' => 'required|string|max:20',
            'inputs.*.cost_center' => 'nullable|string|max:20',
            'inputs.*.prefix' => 'required|string|max:100',
            'inputs.*.cc_description' => 'nullable|string|max:200',
        ], [
            'inputs.*.prefix.required' => 'Field Prefix harus diisi.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (!$history->output_filled_path || !file_exists($history->output_filled_path)) {
            return back()->with('error', 'File filled tidak ditemukan.');
        }

        $inputs = $request->input('inputs');

        foreach ($inputs as $idx => $input) {
            if (!empty($input['cost_center']) && empty($input['cc_description'])) {
                return back()
                    ->withErrors(['inputs' => "Row dengan CC '{$input['cost_center']}' wajib isi CC Description."])
                    ->withInput();
            }
        }

        $result = $this->fillTextService->saveManualTextsAndRegenerate($history->output_filled_path, $inputs);

        if (!$result['success']) {
            return back()->with('error', 'Save manual gagal: ' . ($result['error'] ?? 'Unknown error'));
        }

        $message = sprintf(
            'Berhasil! %d prefix akun, %d cost center, %d text reference disimpan. %d row di Excel ter-update.',
            $result['saved_prefixes'],
            $result['saved_cost_centers'],
            $result['saved_references'],
            $result['updated_in_file']
        );

        return redirect()
            ->route('fill_text.show', $history->id)
            ->with('success', $message);
    }

    public function show(GlRunHistory $history)
    {
        if (!$history->output_filled_path || !file_exists($history->output_filled_path)) {
            return redirect()->route('fill_text.form')->with('error', 'File filled tidak ada. Run Fill Text dulu.');
        }

        // [FIX] Clear stat cache - pastikan baca file fresh setelah save manual
        clearstatcache(true, $history->output_filled_path);

        $problemRows = $this->scanProblemRows($history->output_filled_path);

        $accounts = array_unique(array_column($problemRows, 'account'));
        $existingPrefixes = GlAccountPrefix::whereIn('account_number', $accounts)
            ->pluck('prefix', 'account_number')
            ->toArray();

        foreach ($problemRows as &$row) {
            $row['existing_prefix'] = $existingPrefixes[$row['account']] ?? null;
            $row['prefilled_prefix'] = $row['existing_prefix']
                ?? ($row['extracted_prefix'] ?? null);
        }

        return view('fill_text.show', [
            'history' => $history,
            'needFill' => $problemRows,
        ]);
    }

    /**
     * GET /fill-text/download/{history}
     *
     * [FIX v5] - Tambah:
     * - clearstatcache sebelum download (refresh file info)
     * - No-cache headers (cegah browser cache file lama)
     * - Last-Modified header dengan timestamp file actual
     */
    public function download(GlRunHistory $history)
    {
        if (!$history->output_filled_path || !file_exists($history->output_filled_path)) {
            return back()->with('error', 'File filled tidak ditemukan.');
        }

        $filePath = $history->output_filled_path;

        // [FIX] Clear stat cache - pastikan dapat file fresh dari disk
        clearstatcache(true, $filePath);

        $filename = basename($filePath);
        $lastModified = filemtime($filePath);

        // [FIX] Headers untuk cegah browser caching
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-cache, no-store, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'ETag' => '"' . md5_file($filePath) . '"',
        ];

        return response()->download($filePath, $filename, $headers);
    }

    private function scanProblemRows(string $path): array
    {
        try {
            // [FIX] Clear stat cache sebelum scan
            clearstatcache(true, $path);

            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();

            $colMap = [];
            $colIterator = $sheet->getColumnIterator('A', $highestCol);
            foreach ($colIterator as $col) {
                $cellValue = $sheet->getCell($col->getColumnIndex() . 1)->getValue();
                $colMap[strtolower(trim((string) $cellValue))] = $col->getColumnIndex();
            }

            $accountCol = $colMap['account'] ?? null;
            $ccCol = $colMap['cost center'] ?? null;
            $textCol = $colMap['text'] ?? null;

            if (!$accountCol || !$textCol) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                return [];
            }

            $problemRows = [];
            for ($row = 2; $row <= $highestRow; $row++) {
                $text = trim((string) $sheet->getCell($textCol . $row)->getValue());
                if (empty($text)) continue;

                $type = null;
                $extractedPrefix = null;

                if ($text === FillTextService::MARKER_NO_PREFIX) {
                    $type = 'no_prefix';
                } elseif (str_contains($text, FillTextService::MARKER_NEED_FILL_SUFFIX)) {
                    $type = 'no_cc_desc';
                } elseif (FillTextService::isLegacyFormat($text)) {
                    $type = 'legacy';
                    $extractedPrefix = FillTextService::extractPrefixFromLegacy($text);
                }

                if ($type === null) continue;

                $problemRows[] = [
                    'row' => $row,
                    'account' => preg_replace('/\.0$/', '', trim((string) $sheet->getCell($accountCol . $row)->getValue())),
                    'cost_center' => $ccCol ? preg_replace('/\.0$/', '', trim((string) $sheet->getCell($ccCol . $row)->getValue())) : '',
                    'current_text' => $text,
                    'type' => $type,
                    'extracted_prefix' => $extractedPrefix,
                ];
            }

            // [FIX] Release memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return $problemRows;
        } catch (\Exception $e) {
            return [];
        }
    }
}