<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Str;

class TestFillTextController extends Controller
{
    private const TARGET_ACCOUNT = '2010000005';

    private const LABEL_MAP = [
        'Potongan Koperasi' => 'Uang Titipan Koperasi',
        'Potongan Kelalaian' => 'Uang Titipan Jaminan Kelalaian',
        'Potongan Denda Sakit' => 'Uang Titipan Refund',
        'Potongan Denda Terlambat' => 'Uang Titipan Refund',
        'Potongan Denda' => 'Uang Titipan Denda',
        'Potongan Indisipliner' => 'Uang Titipan Denda',
        'Potongan Denda Indisipliner' => 'Uang Titipan Denda',
        'Denda Indisipliner' => 'Uang Titipan Denda',
        'Potongan Lain-lain' => 'Uang Titipan Talenta',
        'Potongan Lainnya' => 'Uang Titipan Lelang',
    ];

    public function showForm()
    {
        return view('fill_text.subtype_form');
    }

    public function process(Request $request)
    {
        $request->validate([
            'ledger_file' => 'required|file|mimes:xlsx,xls|max:10240',
            'target_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $ledgerPath = $this->storeUpload($request->file('ledger_file'), 'ledger');
        $targetPath = $this->storeUpload($request->file('target_file'), 'target');

        $componentNames = $this->getComponentNamesForAccount($ledgerPath, self::TARGET_ACCOUNT);

        $targetRows = $this->getTargetRowsForAccount($targetPath, self::TARGET_ACCOUNT);

        if (count($componentNames) !== count($targetRows)) {
            $warning = sprintf(
                'Perbedaan jumlah: %d entry ledger vs %d target rows untuk account %s. Positional matching mungkin tidak akurat.',
                count($componentNames),
                count($targetRows),
                self::TARGET_ACCOUNT
            );
            $request->session()->flash('subtype_warning', $warning);
        }

        $matched = [];
        foreach ($targetRows as $i => $row) {
            $comp = $componentNames[$i] ?? '';
            $matched[] = [
                'excel_row' => $row['excel_row'],
                'amount' => $row['amount'],
                'cost_center' => $row['cost_center'],
                'current_text' => $row['text'],
                'component_name' => $comp,
                'default_label' => $comp ? (self::LABEL_MAP[$comp] ?? 'Uang Titipan - ' . $comp) : '',
            ];
        }

        $request->session()->put('test_fill_matched', $matched);
        $request->session()->put('test_fill_target_orig_path', $targetPath);

        return redirect()->route('fill_text.subtype.result', [
            'account' => self::TARGET_ACCOUNT,
        ]);
    }

    public function showResult(Request $request)
    {
        $matched = $request->session()->get('test_fill_matched');

        if (!$matched) {
            return redirect()->route('fill_text.subtype.form')->with('error', 'Silakan upload file terlebih dahulu.');
        }

        return view('fill_text.subtype_result', [
            'account' => self::TARGET_ACCOUNT,
            'matched' => $matched,
        ]);
    }

    public function apply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'labels' => 'required|array',
            'labels.*' => 'nullable|string|max:200',
        ]);

        if ($validator->fails()) {
            return redirect()->route('fill_text.subtype.form')->withErrors($validator)->withInput();
        }

        $matched = $request->session()->get('test_fill_matched');
        $targetOrigPath = $request->session()->get('test_fill_target_orig_path');

        if (!$matched || !$targetOrigPath || !file_exists($targetOrigPath)) {
            return redirect()->route('fill_text.subtype.form')->with('error', 'Session expired. Silakan upload ulang.');
        }

        $labels = $request->input('labels');

        $tempPath = storage_path('app/test_fill_output_' . Str::random(12) . '.xlsx');
        copy($targetOrigPath, $tempPath);

        $spreadsheet = @IOFactory::load($tempPath);
        $sheet = $spreadsheet->getActiveSheet();

        $textCol = $this->findColumnByHeader($sheet, 'text');

        if (!$textCol) {
            @unlink($tempPath);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            return redirect()->route('fill_text.subtype.form')->with('error', 'Kolom "Text" tidak ditemukan di file target.');
        }

        foreach ($matched as $idx => $row) {
            if (isset($labels[$idx])) {
                $sheet->setCellValue($textCol . $row['excel_row'], trim($labels[$idx]));
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        $request->session()->forget(['test_fill_matched', 'test_fill_target_orig_path']);

        return response()->download($tempPath, 'Test_Filled_' . basename($targetOrigPath))->deleteFileAfterSend(true);
    }

    private function loadSpreadsheet(string $path)
    {
        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $spreadsheet = @$reader->load($path);
        return $spreadsheet;
    }

    private function storeUpload($file, string $prefix): string
    {
        $dir = storage_path('app/test_fill_text');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filename = $prefix . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);
        return $dir . DIRECTORY_SEPARATOR . $filename;
    }

    private function findColumnByHeader(Worksheet $sheet, string $headerName): ?string
    {
        $col = 'A';
        $maxCol = 'ZZ';
        $emptyCount = 0;
        while ($col !== $maxCol && $emptyCount < 5) {
            $val = trim((string) $sheet->getCell($col . '1')->getValue());
            if ($val === '') {
                $emptyCount++;
            } else {
                $emptyCount = 0;
                if (strtolower($val) === strtolower($headerName)) {
                    return $col;
                }
            }
            $col++;
        }
        return null;
    }

    private function readHeaderMap(Worksheet $sheet): array
    {
        $map = [];
        $col = 'A';
        $maxCol = 'ZZ';
        $emptyCount = 0;
        while ($col !== $maxCol && $emptyCount < 5) {
            $val = trim((string) $sheet->getCell($col . '1')->getValue());
            if ($val === '') {
                $emptyCount++;
            } else {
                $emptyCount = 0;
                $map[strtolower($val)] = $col;
            }
            $col++;
        }
        return $map;
    }

    private function findHeader(array $headerMap, string $search): ?string
    {
        $searchLower = strtolower($search);
        if (isset($headerMap[$searchLower])) {
            return $headerMap[$searchLower];
        }
        foreach ($headerMap as $key => $col) {
            if (str_contains($key, $searchLower)) {
                return $col;
            }
        }
        return null;
    }

    private function getComponentNamesForAccount(string $path, string $account): array
    {
        $spreadsheet = $this->loadSpreadsheet($path);
        $sheet = $spreadsheet->getActiveSheet();

        $header = $this->readHeaderMap($sheet);

        $glEntryCol = $this->findHeader($header, 'gl entry');
        $compCol = $this->findHeader($header, 'components');

        $names = [];
        $highestRow = $sheet->getHighestDataRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $entry = $glEntryCol ? trim((string) $sheet->getCell($glEntryCol . $row)->getValue()) : '';
            if (preg_replace('/\.0$/', '', $entry) !== $account) continue;

            $name = $compCol ? trim((string) $sheet->getCell($compCol . $row)->getValue()) : '';
            $name = trim($name, " -");
            $names[] = $name;
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $names;
    }

    private function getTargetRowsForAccount(string $path, string $account): array
    {
        $spreadsheet = $this->loadSpreadsheet($path);
        $sheet = $spreadsheet->getActiveSheet();

        $header = $this->readHeaderMap($sheet);

        $accountCol = $this->findHeader($header, 'account');
        $ccCol = $this->findHeader($header, 'cost center');
        $textCol = $this->findHeader($header, 'text');
        $amountCol = $this->findHeader($header, 'amount');

        $rows = [];
        $highestRow = $sheet->getHighestDataRow();
        for ($row = 2; $row <= $highestRow; $row++) {
            $acct = $accountCol ? trim((string) $sheet->getCell($accountCol . $row)->getValue()) : '';
            if (preg_replace('/\.0$/', '', $acct) !== $account) continue;

            $amount = 0;
            if ($amountCol) {
                $raw = $sheet->getCell($amountCol . $row)->getValue();
                $amount = is_numeric($raw) ? (float) $raw : 0;
            }

            $rows[] = [
                'excel_row' => $row,
                'amount' => $amount,
                'cost_center' => $ccCol ? trim((string) $sheet->getCell($ccCol . $row)->getValue()) : '',
                'text' => $textCol ? trim((string) $sheet->getCell($textCol . $row)->getValue()) : '',
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $rows;
    }
}
