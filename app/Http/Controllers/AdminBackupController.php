<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminBackupController extends Controller
{
    protected function checkAdmin()
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403, '存取拒絕：權限不足，只有最高系統管理員可以訪問管理介面。');
        }
    }

    /**
     * 顯示備份頁面（資料庫狀態、備份清單、建立備份按鈕）
     */
    public function index()
    {
        $this->checkAdmin();

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }

        // 列出備份檔
        $files = [];
        foreach (File::files($backupDir) as $file) {
            $files[] = [
                'name' => $file->getFilename(),
                'size' => $this->humanSize($file->getSize()),
                'bytes' => $file->getSize(),
                'mtime' => date('Y-m-d H:i:s', $file->getMTime()),
                'age_hours' => round((time() - $file->getMTime()) / 3600, 1),
            ];
        }
        // 依時間排序（新到舊）
        usort($files, fn($a, $b) => strcmp($b['mtime'], $a['mtime']));

        // 資料庫基本統計
        $dbName = DB::connection()->getDatabaseName();
        $driver = DB::connection()->getDriverName();

        $tables = DB::select('SHOW TABLE STATUS');
        $totalRows = 0;
        $totalSize = 0;
        $tableStats = [];
        foreach ($tables as $t) {
            $rows = (int) ($t->Rows ?? 0);
            $size = (int) ($t->Data_length ?? 0) + (int) ($t->Index_length ?? 0);
            $totalRows += $rows;
            $totalSize += $size;
            $tableStats[] = [
                'name' => $t->Name,
                'rows' => $rows,
                'size' => $this->humanSize($size),
                'engine' => $t->Engine ?? '-',
            ];
        }
        usort($tableStats, fn($a, $b) => $b['rows'] <=> $a['rows']);

        return view('admin.backup.index', compact(
            'files', 'dbName', 'driver', 'tables', 'totalRows', 'totalSize', 'tableStats'
        ))->with('humanTotalSize', $this->humanSize($totalSize));
    }

    /**
     * 建立 SQL 備份（mysqldump 不可用時退回 PHP 自製）
     */
    public function create(Request $request)
    {
        $this->checkAdmin();

        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $filename = 'backup_' . date('Ymd_His') . '.sql';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $ok = false;
        $method = 'native-php';

        // 優先嘗試 mysqldump
        $mysqldump = $this->findExecutable('mysqldump');
        if ($mysqldump) {
            $cmd = sprintf(
                '%s --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --default-character-set=utf8mb4 %s > %s 2>&1',
                escapeshellarg($mysqldump),
                escapeshellarg(env('DB_HOST', '127.0.0.1')),
                escapeshellarg(env('DB_PORT', '3306')),
                escapeshellarg(env('DB_USERNAME', 'root')),
                escapeshellarg(env('DB_PASSWORD', '')),
                escapeshellarg(env('DB_DATABASE', 'family_accounting')),
                escapeshellarg($filepath)
            );
            @exec($cmd, $out, $rc);
            if ($rc === 0 && file_exists($filepath) && filesize($filepath) > 0) {
                $ok = true;
                $method = 'mysqldump';
            }
        }

        // 退回 PHP 自製
        if (! $ok) {
            $ok = $this->phpDump($filepath);
            $method = 'native-php';
        }

        if (! $ok || ! file_exists($filepath)) {
            return back()->with('error', '❌ 備份建立失敗，請檢查 storage/app/backups 權限。');
        }

        AuditService::log(
            'admin_backup_created',
            'DatabaseBackup',
            null,
            ['檔名' => $filename, '方式' => $method, '大小' => filesize($filepath)],
            "管理員建立資料庫備份",
            "管理員 " . auth()->user()->name . " 建立備份：{$filename}（{$this->humanSize(filesize($filepath))}，{$method}）"
        );

        return back()->with('success', "✅ 備份完成：{$filename}（{$this->humanSize(filesize($filepath))}，{$method}）");
    }

    /**
     * 下載備份檔
     */
    public function download(Request $request, string $filename)
    {
        $this->checkAdmin();

        // 防止路徑穿越
        if (basename($filename) !== $filename || ! preg_match('/^backup_\d{8}_\d{6}\.sql$/', $filename)) {
            abort(404);
        }

        $filepath = storage_path('app/backups/' . $filename);
        if (! file_exists($filepath)) {
            abort(404, '備份檔不存在');
        }

        return response()->download($filepath, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    /**
     * 刪除備份檔
     */
    public function destroy(Request $request, string $filename)
    {
        $this->checkAdmin();

        if (basename($filename) !== $filename || ! preg_match('/^backup_\d{8}_\d{6}\.sql$/', $filename)) {
            abort(404);
        }

        $filepath = storage_path('app/backups/' . $filename);
        if (file_exists($filepath)) {
            @unlink($filepath);
            AuditService::log(
                'admin_backup_deleted',
                'DatabaseBackup', null,
                ['檔名' => $filename],
                "管理員刪除備份檔",
                "管理員 " . auth()->user()->name . " 刪除了備份：{$filename}"
            );
        }

        return back()->with('success', "✅ 已刪除備份 {$filename}");
    }

    private function phpDump(string $filepath): bool
    {
        try {
            $fh = fopen($filepath, 'w');
            if (! $fh) return false;

            fwrite($fh, "-- FamilyAccounting database backup\n");
            fwrite($fh, "-- Generated at: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fh, "-- DB driver: " . DB::connection()->getDriverName() . "\n\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = DB::select('SHOW TABLES');
            $keyColumn = 'Tables_in_' . DB::connection()->getDatabaseName();

            foreach ($tables as $row) {
                $table = $row->$keyColumn;
                $createRow = DB::selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $createRow->{'Create Table'} ?? null;
                if (! $createSql) continue;
                fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
                fwrite($fh, $createSql . ";\n\n");

                $rows = DB::table($table)->get();
                if ($rows->count() === 0) continue;

                $cols = array_keys((array) $rows->first());
                $colList = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
                foreach ($rows as $r) {
                    $vals = array_map(function ($v) use ($table) {
                        if (is_null($v)) return 'NULL';
                        if (is_bool($v)) return $v ? '1' : '0';
                        if (is_numeric($v)) return $v;
                        return "'" . addslashes($v) . "'";
                    }, (array) $r);
                    fwrite($fh, "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $vals) . ");\n");
                }
                fwrite($fh, "\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($fh);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function findExecutable(string $name): ?string
    {
        $paths = explode(PATH_SEPARATOR, getenv('PATH') ?: '');
        $candidates = ['C:\\xampp1\\mysql\\bin\\' . $name . '.exe', 'C:\\xampp\\mysql\\bin\\' . $name . '.exe'];
        foreach ($candidates as $c) {
            if (file_exists($c)) return $c;
        }
        // 退到 PATH 搜尋
        foreach ($paths as $p) {
            $full = rtrim($p, '\\/') . DIRECTORY_SEPARATOR . $name . '.exe';
            if (file_exists($full)) return $full;
        }
        // 用 where / which
        $out = @shell_exec("where $name 2>&1");
        if ($out) {
            $first = trim(explode("\n", $out)[0]);
            if (file_exists($first)) return $first;
        }
        return null;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
