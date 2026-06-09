<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

class ValidatorService
{
    /**
     * Threshold untuk deteksi entity mismatch.
     * Kalau selisih total atau row count > threshold ini, kasih warning kuat.
     */
    const ENTITY_MISMATCH_DEBIT_PERCENT = 20;   // 20%
    const ENTITY_MISMATCH_ROW_PERCENT = 30;     // 30%

    public function validate(string $aslipath, string $genPath): array
    {
        $asli = $this->readAsliFile($aslipath);
        $gen = $this->readGenerateFile($genPath);

        if (!$asli['success']) return ['error' => $asli['error']];
        if (!$gen['success']) return ['error' => $gen['error']];

        $rowsAsli = $asli['rows'];
        $rowsGen = $gen['rows'];

        // Totals
        $totalAsli = [
            'rows' => count($rowsAsli),
            'debit' => (int) array_sum(array_map(fn($r) => $r['dc'] === 'Debit' ? $r['amount'] : 0, $rowsAsli)),
            'credit' => (int) array_sum(array_map(fn($r) => $r['dc'] === 'Credit' ? $r['amount'] : 0, $rowsAsli)),
        ];
        $totalAsli['thp'] = $totalAsli['debit'] - $totalAsli['credit'];

        $totalGen = [
            'rows' => count($rowsGen),
            'debit' => (int) array_sum(array_map(fn($r) => $r['dc'] === 'Debit' ? $r['amount'] : 0, $rowsGen)),
            'credit' => (int) array_sum(array_map(fn($r) => $r['dc'] === 'Credit' ? $r['amount'] : 0, $rowsGen)),
        ];
        $totalGen['thp'] = $totalGen['debit'] - $totalGen['credit'];

        // [IMPROVEMENT 1] Deteksi Entity Mismatch
        $entityMismatchWarning = $this->detectEntityMismatch($totalAsli, $totalGen);

        // Group by (Account + DC)
        $groupsAsli = $this->groupRows($rowsAsli);
        $groupsGen = $this->groupRows($rowsGen);

        // [IMPROVEMENT 2] Deteksi Account-level Issues
        $accountIssues = $this->detectAccountIssues($groupsAsli, $groupsGen);

        // Bandingkan tiap group
        $allKeys = array_unique(array_merge(array_keys($groupsAsli), array_keys($groupsGen)));
        sort($allKeys);

        $groupResults = [];
        $matchCount = 0;
        $mismatchCount = 0;

        foreach ($allKeys as $key) {
            [$account, $dc] = explode('|', $key);
            $gAsli = $groupsAsli[$key] ?? null;
            $gGen = $groupsGen[$key] ?? null;

            $result = [
                'account' => $account,
                'dc' => $dc,
                'asli_rows' => $gAsli ? count($gAsli) : 0,
                'gen_rows' => $gGen ? count($gGen) : 0,
                'asli_total' => $gAsli ? (int) array_sum(array_column($gAsli, 'amount')) : 0,
                'gen_total' => $gGen ? (int) array_sum(array_column($gGen, 'amount')) : 0,
                'status' => 'match',
                'details' => [],
            ];

            $sigAsli = $this->makeSig($gAsli ?: []);
            $sigGen = $this->makeSig($gGen ?: []);

            $onlyAsli = $this->multisetDiff($sigAsli, $sigGen);
            $onlyGen = $this->multisetDiff($sigGen, $sigAsli);

            if (empty($onlyAsli) && empty($onlyGen) && $result['asli_rows'] === $result['gen_rows']) {
                $result['status'] = 'match';
                $matchCount++;
            } else {
                $result['status'] = 'mismatch';
                $result['only_in_asli'] = $onlyAsli;
                $result['only_in_gen'] = $onlyGen;
                $mismatchCount++;
            }

            $groupResults[] = $result;
        }

        return [
            'summary' => [
                'total_asli' => $totalAsli,
                'total_gen' => $totalGen,
                'rows_match' => $totalAsli['rows'] === $totalGen['rows'],
                'debit_match' => $totalAsli['debit'] === $totalGen['debit'],
                'credit_match' => $totalAsli['credit'] === $totalGen['credit'],
                'thp_match' => $totalAsli['thp'] === $totalGen['thp'],
                'groups_total' => count($groupResults),
                'groups_match' => $matchCount,
                'groups_mismatch' => $mismatchCount,
                'overall_status' => $mismatchCount === 0 ? 'match' : 'mismatch',
            ],
            'entity_mismatch_warning' => $entityMismatchWarning,
            'account_issues' => $accountIssues,
            'groups' => $groupResults,
        ];
    }

    /**
     * [IMPROVEMENT 1] Deteksi Entity Mismatch
     *
     * Kalau selisih total Debit > 20% atau row count > 30%,
     * kemungkinan besar file yang di-upload bukan untuk entity yang dipilih.
     */
    private function detectEntityMismatch(array $totalAsli, array $totalGen): ?array
    {
        if ($totalGen['debit'] == 0 && $totalAsli['debit'] == 0) {
            return null; // Both empty, no warning
        }

        // Hitung selisih %
        $baseDebit = max($totalGen['debit'], 1);
        $diffDebit = abs($totalAsli['debit'] - $totalGen['debit']);
        $debitPercent = ($diffDebit / $baseDebit) * 100;

        $baseRows = max($totalGen['rows'], 1);
        $diffRows = abs($totalAsli['rows'] - $totalGen['rows']);
        $rowsPercent = ($diffRows / $baseRows) * 100;

        if ($debitPercent < self::ENTITY_MISMATCH_DEBIT_PERCENT && $rowsPercent < self::ENTITY_MISMATCH_ROW_PERCENT) {
            return null; // Selisih masih wajar
        }

        // Selisih besar — kemungkinan entity berbeda
        return [
            'severity' => 'high',
            'debit_diff' => $diffDebit,
            'debit_diff_percent' => round($debitPercent, 1),
            'rows_diff' => $diffRows,
            'rows_diff_percent' => round($rowsPercent, 1),
            'message' => "Selisih sangat besar antara file asli dan hasil generate. "
                       . "Selisih Debit: " . number_format($diffDebit) . " (" . round($debitPercent, 1) . "%), "
                       . "Selisih Rows: " . $diffRows . " (" . round($rowsPercent, 1) . "%). "
                       . "Kemungkinan file yang di-upload BUKAN untuk entity/periode yang dipilih. "
                       . "Pastikan file yang lo upload sesuai dengan run yang dipilih sebelum lanjut.",
        ];
    }

    /**
     * [IMPROVEMENT 2] Deteksi Account-level Issues
     *
     * Identifikasi:
     * - Account yang HANYA di asli (kemungkinan baru di Talenta, belum di-mapping)
     * - Account yang HANYA di gen (kemungkinan typo mapping atau account dihapus)
     */
    private function detectAccountIssues(array $groupsAsli, array $groupsGen): array
    {
        $issues = [];

        // Set of all account+DC keys
        $aslikeys = array_keys($groupsAsli);
        $genKeys = array_keys($groupsGen);

        // Accounts (just numbers) yang muncul di asli
        $aslíAccounts = array_unique(array_map(fn($k) => explode('|', $k)[0], $aslikeys));
        $genAccounts = array_unique(array_map(fn($k) => explode('|', $k)[0], $genKeys));

        // Account yang HANYA di asli (bukan di gen sama sekali)
        $onlyInAsli = array_diff($aslíAccounts, $genAccounts);
        foreach ($onlyInAsli as $acc) {
            // Sum total untuk account ini di asli
            $total = 0;
            $rowCount = 0;
            $dcSeen = [];
            $sampleCcs = [];
            foreach ($groupsAsli as $key => $rows) {
                [$kAcc, $kDc] = explode('|', $key);
                if ($kAcc === $acc) {
                    $total += array_sum(array_column($rows, 'amount'));
                    $rowCount += count($rows);
                    $dcSeen[] = $kDc;
                    foreach ($rows as $r) {
                        if ($r['cost_center']) $sampleCcs[] = $r['cost_center'];
                    }
                }
            }

            $issues[] = [
                'type' => 'new_in_asli',
                'severity' => 'high',
                'account' => $acc,
                'total' => (int) $total,
                'row_count' => $rowCount,
                'dc' => implode(', ', array_unique($dcSeen)),
                'sample_ccs' => array_slice(array_unique($sampleCcs), 0, 5),
                'title' => "Account {$acc} BARU di Talenta",
                'message' => "Account {$acc} muncul di file asli Talenta dengan total " . number_format($total)
                           . " (" . $rowCount . " rows), TAPI tidak ada di hasil generate. "
                           . "Kemungkinan account ini belum di-mapping di profile.",
                'action' => "Koordinasi dengan tim payroll untuk dapat info account ini (nama, D/C, type), lalu tambah mapping baru di Mapping Editor.",
            ];
        }

        // Account yang HANYA di gen (bukan di asli sama sekali)
        $onlyInGen = array_diff($genAccounts, $aslíAccounts);
        foreach ($onlyInGen as $acc) {
            $total = 0;
            $rowCount = 0;
            $dcSeen = [];
            foreach ($groupsGen as $key => $rows) {
                [$kAcc, $kDc] = explode('|', $key);
                if ($kAcc === $acc) {
                    $total += array_sum(array_column($rows, 'amount'));
                    $rowCount += count($rows);
                    $dcSeen[] = $kDc;
                }
            }

            // Klasifikasi: kalau total 0 = info (mungkin gak dipakai), kalau total > 0 = warning (typo)
            $isZero = $total == 0;
            $issues[] = [
                'type' => $isZero ? 'unused_in_asli' : 'misconfig',
                'severity' => $isZero ? 'info' : 'medium',
                'account' => $acc,
                'total' => (int) $total,
                'row_count' => $rowCount,
                'dc' => implode(', ', array_unique($dcSeen)),
                'title' => $isZero
                    ? "Account {$acc} TIDAK DIPAKAI bulan ini"
                    : "Account {$acc} hanya di Generate (kemungkinan typo)",
                'message' => $isZero
                    ? "Account {$acc} ada di hasil generate (amount 0) tapi tidak ada di file asli. "
                      . "Ini OK kalau komponen ini sudah tidak dipakai bulan ini."
                    : "Account {$acc} muncul di hasil generate dengan total " . number_format($total)
                      . " (" . $rowCount . " rows), TAPI tidak ada di file asli Talenta. "
                      . "Kemungkinan mapping lo punya account number yang salah/typo, atau account ini sudah dihapus dari payroll.",
                'action' => $isZero
                    ? "Kalau permanen dihapus, edit mapping untuk remove account ini."
                    : "Cek mapping untuk account {$acc} di profile lo - pastikan account number benar.",
            ];
        }

        // Sort by severity: high > medium > info
        usort($issues, fn($a, $b) => $this->severityWeight($b['severity']) - $this->severityWeight($a['severity']));

        return $issues;
    }

    private function severityWeight(string $severity): int
    {
        return match ($severity) {
            'high' => 3,
            'medium' => 2,
            'info' => 1,
            default => 0,
        };
    }

    private function readAsliFile(string $path): array
    {
        try {
            $rows = Excel::toArray(new class implements ToArray {
                public function array(array $array) { return $array; }
            }, $path);

            if (empty($rows) || empty($rows[0])) {
                return ['success' => false, 'error' => 'File asli kosong atau tidak terbaca.'];
            }

            $sheet = $rows[0];
            $header = array_map(fn($h) => trim(strtolower($h ?? '')), $sheet[0]);

            $idxAcc = array_search('gl account', $header);
            $idxDc = array_search('debit/credit', $header);
            $idxAmt = array_search('amount', $header);
            $idxCc = array_search('cost center', $header);

            if ($idxAcc === false || $idxDc === false || $idxAmt === false) {
                return ['success' => false, 'error' => 'Kolom wajib (GL Account, Debit/Credit, Amount) tidak ditemukan di file asli. Pastikan ini file dari Talenta.'];
            }

            $parsed = [];
            for ($i = 1; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $acc = trim((string) ($row[$idxAcc] ?? ''));
                $dc = trim((string) ($row[$idxDc] ?? ''));
                $amt = $row[$idxAmt] ?? 0;
                $cc = $idxCc !== false ? trim((string) ($row[$idxCc] ?? '')) : '';

                if (empty($acc)) continue;

                $amt = is_string($amt) ? (float) str_replace(',', '', $amt) : (float) $amt;
                if ($cc === '-' || $cc === '') $cc = '';

                $parsed[] = [
                    'account' => preg_replace('/\.0$/', '', $acc),
                    'dc' => $dc,
                    'amount' => (int) round($amt),
                    'cost_center' => preg_replace('/\.0$/', '', $cc),
                ];
            }

            return ['success' => true, 'rows' => $parsed];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error baca file asli: ' . $e->getMessage()];
        }
    }

    private function readGenerateFile(string $path): array
    {
        try {
            $rows = Excel::toArray(new class implements ToArray {
                public function array(array $array) { return $array; }
            }, $path);

            if (empty($rows) || empty($rows[0])) {
                return ['success' => false, 'error' => 'File generate kosong atau tidak terbaca.'];
            }

            $sheet = $rows[0];
            $header = array_map(fn($h) => trim(strtolower($h ?? '')), $sheet[0]);

            $idxAcc = array_search('account', $header);
            $idxPst = array_search('pstky', $header);
            $idxAmt = array_search('amount', $header);
            $idxCc = array_search('cost center', $header);

            if ($idxAcc === false || $idxPst === false || $idxAmt === false) {
                return ['success' => false, 'error' => 'Kolom wajib (Account, PstKy, Amount) tidak ditemukan di file generate.'];
            }

            $parsed = [];
            for ($i = 1; $i < count($sheet); $i++) {
                $row = $sheet[$i];
                $acc = trim((string) ($row[$idxAcc] ?? ''));
                $pst = $row[$idxPst] ?? 0;
                $amt = $row[$idxAmt] ?? 0;
                $cc = $idxCc !== false ? trim((string) ($row[$idxCc] ?? '')) : '';

                if (empty($acc)) continue;

                $dc = ((int) $pst) === 40 ? 'Debit' : 'Credit';
                $amt = is_string($amt) ? (float) str_replace(',', '', $amt) : (float) $amt;
                if ($cc === '-' || $cc === '') $cc = '';

                $parsed[] = [
                    'account' => preg_replace('/\.0$/', '', $acc),
                    'dc' => $dc,
                    'amount' => (int) round($amt),
                    'cost_center' => preg_replace('/\.0$/', '', $cc),
                ];
            }

            return ['success' => true, 'rows' => $parsed];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Error baca file generate: ' . $e->getMessage()];
        }
    }

    private function groupRows(array $rows): array
    {
        $groups = [];
        foreach ($rows as $r) {
            $key = $r['account'] . '|' . $r['dc'];
            $groups[$key][] = $r;
        }
        return $groups;
    }

    private function makeSig(array $rows): array
    {
        $sig = [];
        foreach ($rows as $r) {
            $sig[] = $r['amount'] . '@' . ($r['cost_center'] ?: '-');
        }
        sort($sig);
        return $sig;
    }

    private function multisetDiff(array $a, array $b): array
    {
        $countA = array_count_values($a);
        $countB = array_count_values($b);
        $diff = [];
        foreach ($countA as $val => $cnt) {
            $cntB = $countB[$val] ?? 0;
            if ($cnt > $cntB) {
                for ($i = 0; $i < $cnt - $cntB; $i++) {
                    [$amt, $cc] = explode('@', $val);
                    $diff[] = ['amount' => (int) $amt, 'cost_center' => $cc === '-' ? '' : $cc];
                }
            }
        }
        return $diff;
    }
}