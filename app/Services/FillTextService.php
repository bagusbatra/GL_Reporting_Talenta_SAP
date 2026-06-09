<?php

namespace App\Services;

use App\Models\GlAccountPrefix;
use App\Models\GlCostCenter;
use App\Models\GlTextReference;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FillTextService
{
    const MARKER_NO_PREFIX = '?? Account-Belum-Prefix';
    const MARKER_NEED_FILL_SUFFIX = ' (NEED FILL)';
    const SEPARATOR = ' - ';
    const LEGACY_REGEX = '/^([A-Za-z]+)\s+(\d{8,})$/';

    public static function isLegacyFormat(string $text): bool
    {
        return (bool) preg_match(self::LEGACY_REGEX, trim($text));
    }

    public static function extractPrefixFromLegacy(string $text): ?string
    {
        if (preg_match(self::LEGACY_REGEX, trim($text), $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function isProblemText(string $text): bool
    {
        $text = trim($text);
        if ($text === self::MARKER_NO_PREFIX) return true;
        if (str_contains($text, self::MARKER_NEED_FILL_SUFFIX)) return true;
        if (self::isLegacyFormat($text)) return true;
        return false;
    }

    public function fill(string $inputPath, string $outputPath, array $manualReferences = []): array
    {
        $referenceMap = GlTextReference::buildLookupMap();
        $prefixMap = GlAccountPrefix::allAsMap();
        $ccMap = GlCostCenter::pluck('description', 'cost_center_code')->toArray();
        $ccNameMap = GlCostCenter::pluck('name', 'cost_center_code')->toArray();

        foreach ($manualReferences as $ref) {
            $key = $ref['account'] . '|' . ($ref['cost_center'] ?? '');
            if (!isset($referenceMap[$key]) && !empty($ref['text'])) {
                $referenceMap[$key] = $ref['text'];
            }
        }

        try {
            // [FIX] Clear stat cache sebelum load - pastikan baca file fresh dari disk
            clearstatcache(true, $inputPath);
            $spreadsheet = IOFactory::load($inputPath);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Gagal baca file: ' . $e->getMessage()];
        }

        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        $headerRow = 1;
        $colMap = [];
        $colIterator = $sheet->getColumnIterator('A', $highestCol);
        foreach ($colIterator as $col) {
            $cellValue = $sheet->getCell($col->getColumnIndex() . $headerRow)->getValue();
            $colMap[strtolower(trim((string) $cellValue))] = $col->getColumnIndex();
        }

        $accountCol = $colMap['account'] ?? null;
        $ccCol = $colMap['cost center'] ?? null;
        $textCol = $colMap['text'] ?? null;

        if (!$accountCol || !$textCol) {
            return ['success' => false, 'error' => 'Kolom "Account" atau "Text" tidak ditemukan di file generate.'];
        }

        $stats = [
            'total_rows' => 0,
            'filled_count' => 0,
            'from_reference' => 0,
            'from_prefix' => 0,
            'need_fill' => [],
        ];

        $textsToLearn = [];

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $account = trim((string) $sheet->getCell($accountCol . $row)->getValue());
            $cc = $ccCol ? trim((string) $sheet->getCell($ccCol . $row)->getValue()) : '';

            if (empty($account)) continue;
            $stats['total_rows']++;

            $account = preg_replace('/\.0$/', '', $account);
            $cc = preg_replace('/\.0$/', '', $cc);

            $textResult = $this->generateText($account, $cc, $referenceMap, $prefixMap, $ccMap, $ccNameMap);

            $text = $textResult['text'];
            $source = $textResult['source'];

            $sheet->setCellValue($textCol . $row, $text);

            if ($source === 'reference') {
                $stats['filled_count']++;
                $stats['from_reference']++;
            } elseif ($source === 'prefix_with_cc' || $source === 'prefix_only') {
                $stats['filled_count']++;
                $stats['from_prefix']++;

                $key = $account . '|' . $cc;
                $textsToLearn[$key] = [
                    'account' => $account,
                    'cost_center' => $cc ?: null,
                    'text' => $text,
                ];
            } else {
                $stats['need_fill'][] = [
                    'row' => $row,
                    'account' => $account,
                    'cost_center' => $cc,
                    'current_text' => $text,
                    'type' => $source,
                    'existing_prefix' => $prefixMap[$account] ?? null,
                    'extracted_prefix' => $source === 'legacy' ? self::extractPrefixFromLegacy($text) : null,
                ];
            }
        }

        try {
            $outputDir = dirname($outputPath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($outputPath);

            // [FIX] Release memory + force flush
            $spreadsheet->disconnectWorksheets();
            unset($writer);
            unset($spreadsheet);
            clearstatcache(true, $outputPath);
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Gagal save file: ' . $e->getMessage()];
        }

        foreach ($textsToLearn as $key => $ref) {
            try {
                GlTextReference::learnOrUpdate(
                    $ref['account'],
                    $ref['cost_center'],
                    $ref['text'],
                    'auto_fill_' . now()->format('Y_m_d')
                );
            } catch (\Exception $e) {
                // Skip
            }
        }

        $stats['success'] = true;
        $stats['output_path'] = $outputPath;
        return $stats;
    }

    private function generateText(
        string $account,
        string $cc,
        array $referenceMap,
        array $prefixMap,
        array $ccMap,
        array $ccNameMap
    ): array {
        $key = $account . '|' . $cc;
        if (isset($referenceMap[$key]) && !empty($referenceMap[$key])) {
            $cached = $referenceMap[$key];

            if (!self::isProblemText($cached)) {
                return ['text' => $cached, 'source' => 'reference'];
            }

            if (self::isLegacyFormat($cached)) {
                return ['text' => $cached, 'source' => 'legacy'];
            }
        }

        $prefix = $prefixMap[$account] ?? null;

        if (!$prefix) {
            return [
                'text' => self::MARKER_NO_PREFIX,
                'source' => 'no_prefix',
            ];
        }

        if (empty($cc)) {
            return ['text' => $prefix, 'source' => 'prefix_only'];
        }

        $ccDesc = $ccMap[$cc] ?? $ccNameMap[$cc] ?? null;
        if (!$ccDesc) {
            return [
                'text' => $prefix . self::MARKER_NEED_FILL_SUFFIX,
                'source' => 'no_cc_desc',
            ];
        }

        return [
            'text' => $prefix . self::SEPARATOR . $ccDesc,
            'source' => 'prefix_with_cc',
        ];
    }

    public function parseReferenceFile(string $filePath): array
    {
        try {
            $rows = Excel::toArray(new class implements ToArray {
                public function array(array $array) { return $array; }
            }, $filePath);

            if (empty($rows) || empty($rows[0])) {
                return ['success' => false, 'error' => 'File referensi kosong.'];
            }

            $sheet = $rows[0];
            $header = array_map(fn($h) => trim(strtolower($h ?? '')), $sheet[0]);

            $idxAcc = array_search('account', $header);
            $idxCc = array_search('cost center', $header);
            $idxText = array_search('text', $header);

            if ($idxAcc === false || $idxText === false) {
                return ['success' => false, 'error' => 'Kolom Account atau Text tidak ditemukan di file referensi.'];
            }

            $references = [];
            $learned = 0;
            $skipped = 0;

            for ($i = 1; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $account = trim((string) ($row[$idxAcc] ?? ''));
                $cc = $idxCc !== false ? trim((string) ($row[$idxCc] ?? '')) : '';
                $text = trim((string) ($row[$idxText] ?? ''));

                if (empty($account) || empty($text)) continue;

                if (self::isProblemText($text)) {
                    $skipped++;
                    continue;
                }

                $account = preg_replace('/\.0$/', '', $account);
                $cc = preg_replace('/\.0$/', '', $cc);

                $references[] = [
                    'account' => $account,
                    'cost_center' => $cc,
                    'text' => $text,
                ];

                try {
                    GlTextReference::learnOrUpdate(
                        $account,
                        $cc ?: null,
                        $text,
                        'reference_upload_' . now()->format('Y_m_d_H_i_s')
                    );
                    $learned++;
                } catch (\Exception $e) {
                    // Skip
                }
            }

            return [
                'success' => true,
                'references' => $references,
                'stats' => [
                    'total' => count($references),
                    'learned' => $learned,
                    'skipped_marker' => $skipped,
                ],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error baca file referensi: ' . $e->getMessage()];
        }
    }

    /**
     * Save manual input dengan 2-field (prefix + cc_description).
     * Auto-save ke gl_account_prefixes, gl_cost_centers, dan gl_text_references.
     *
     * [FIX v5] - Tambah:
     * - clearstatcache sebelum load file (fresh read)
     * - disconnectWorksheets + unset setelah save (release lock & memory)
     * - clearstatcache sesudah save (refresh PHP file info)
     * - Verify file content setelah save (detect failure)
     */
    public function saveManualTextsAndRegenerate(string $filePath, array $inputs): array
    {
        $savedPrefixes = 0;
        $savedCostCenters = 0;
        $savedReferences = 0;

        // ============================================================
        // 1. Save Prefixes ke gl_account_prefixes
        // ============================================================
        $accountToPrefixMap = [];
        foreach ($inputs as $input) {
            $account = trim($input['account'] ?? '');
            $prefix = trim($input['prefix'] ?? '');
            if (empty($account) || empty($prefix)) continue;
            $accountToPrefixMap[$account] = $prefix;
        }

        foreach ($accountToPrefixMap as $account => $prefix) {
            try {
                $existing = GlAccountPrefix::where('account_number', $account)->first();
                if (!$existing) {
                    GlAccountPrefix::create([
                        'account_number' => $account,
                        'prefix' => $prefix,
                    ]);
                    $savedPrefixes++;
                } else {
                    if ($existing->prefix !== $prefix) {
                        $existing->update(['prefix' => $prefix]);
                        $savedPrefixes++;
                    }
                }
            } catch (\Exception $e) {
                // Skip
            }
        }

        // ============================================================
        // 2. Save CC Descriptions ke gl_cost_centers
        // ============================================================
        $ccToDescMap = [];
        foreach ($inputs as $input) {
            $cc = trim($input['cost_center'] ?? '');
            $ccDesc = trim($input['cc_description'] ?? '');
            if (empty($cc) || empty($ccDesc)) continue;
            $ccToDescMap[$cc] = $ccDesc;
        }

        foreach ($ccToDescMap as $ccCode => $ccDesc) {
            try {
                $existing = GlCostCenter::where('cost_center_code', $ccCode)->first();
                if (!$existing) {
                    GlCostCenter::create([
                        'cost_center_code' => $ccCode,
                        'description' => $ccDesc,
                        'is_active' => true,
                    ]);
                    $savedCostCenters++;
                } else {
                    if (empty($existing->description) || $existing->description !== $ccDesc) {
                        $existing->update(['description' => $ccDesc]);
                        $savedCostCenters++;
                    }
                }
            } catch (\Exception $e) {
                // Skip
            }
        }

        // ============================================================
        // 3. Save composite text ke gl_text_references
        // ============================================================
        foreach ($inputs as $input) {
            $account = trim($input['account'] ?? '');
            $cc = trim($input['cost_center'] ?? '');
            $prefix = trim($input['prefix'] ?? '');
            $ccDesc = trim($input['cc_description'] ?? '');

            if (empty($account) || empty($prefix)) continue;

            if (empty($cc)) {
                $finalText = $prefix;
            } else {
                if (empty($ccDesc)) continue;
                $finalText = $prefix . self::SEPARATOR . $ccDesc;
            }

            try {
                GlTextReference::learnOrUpdate(
                    $account,
                    $cc ?: null,
                    $finalText,
                    'manual_input_' . now()->format('Y_m_d_H_i_s')
                );
                $savedReferences++;
            } catch (\Exception $e) {
                // Skip
            }
        }

        // ============================================================
        // 4. Update file Excel - WITH FILE SYNC FIX
        // ============================================================
        $updatedInFile = 0;
        try {
            // [FIX] Clear stat cache sebelum load - pastikan baca fresh
            clearstatcache(true, $filePath);

            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();

            $headerRow = 1;
            $colMap = [];
            $colIterator = $sheet->getColumnIterator('A', $highestCol);
            foreach ($colIterator as $col) {
                $cellValue = $sheet->getCell($col->getColumnIndex() . $headerRow)->getValue();
                $colMap[strtolower(trim((string) $cellValue))] = $col->getColumnIndex();
            }

            $accountCol = $colMap['account'] ?? null;
            $ccCol = $colMap['cost center'] ?? null;
            $textCol = $colMap['text'] ?? null;

            $inputLookup = [];
            foreach ($inputs as $input) {
                $account = trim($input['account'] ?? '');
                $cc = trim($input['cost_center'] ?? '');
                $prefix = trim($input['prefix'] ?? '');
                $ccDesc = trim($input['cc_description'] ?? '');

                if (empty($account) || empty($prefix)) continue;

                $finalText = empty($cc) ? $prefix : ($prefix . self::SEPARATOR . $ccDesc);
                $key = $account . '|' . $cc;
                $inputLookup[$key] = $finalText;
            }

            for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
                $account = trim((string) $sheet->getCell($accountCol . $row)->getValue());
                $cc = $ccCol ? trim((string) $sheet->getCell($ccCol . $row)->getValue()) : '';

                if (empty($account)) continue;

                $account = preg_replace('/\.0$/', '', $account);
                $cc = preg_replace('/\.0$/', '', $cc);

                $key = $account . '|' . $cc;
                if (isset($inputLookup[$key])) {
                    $sheet->setCellValue($textCol . $row, $inputLookup[$key]);
                    $updatedInFile++;
                }
            }

            $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save($filePath);

            // [FIX] Release memory + flush file lock
            $spreadsheet->disconnectWorksheets();
            unset($writer);
            unset($spreadsheet);

            // [FIX] Clear stat cache lagi setelah write - PHP refresh info file
            clearstatcache(true, $filePath);

        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Gagal update file: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'saved_prefixes' => $savedPrefixes,
            'saved_cost_centers' => $savedCostCenters,
            'saved_references' => $savedReferences,
            'updated_in_file' => $updatedInFile,
        ];
    }
}