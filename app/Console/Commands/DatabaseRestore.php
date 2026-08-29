<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 資料庫還原 artisan 命令（P3）。
 *
 * ⚠️ 危險操作：還原會先 DROP 所有資料表再重灌。
 *
 * 用法：
 *   php artisan db:restore --file=family_accounting_20260827_120000.sql
 *   php artisan db:restore --file=backup.sql.gz        # 自動解壓縮
 *   php artisan db:restore --list                       # 列出可用備份
 *   php artisan db:restore --yes                        # 跳過互動確認（CI 用）
 *
 * 安全機制：
 *   - 預設要求 --yes 之外還會要求互動確認 "yes"
 *   - 還原前先備份當前 DB（避免操作錯誤時無法回滾）
 *   - 若壓縮檔不存在（.gz）會自動找未壓縮版本
 */
class DatabaseRestore extends Command
{
    protected $signature = 'db:restore
                            {--file= : 備份檔名（相對於 storage/app/backups/）}
                            {--list : 列出可用備份}
                            {--yes : 跳過互動確認}';

    protected $description = '⚠️ 從 .sql 備份檔還原資料庫（會先 DROP 所有資料表）';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');

        if ($this->option('list')) {
            return $this->listBackups($backupDir);
        }

        $fileOpt = $this->option('file');
        if (! $fileOpt) {
            $this->error('請指定 --file=xxx.sql 或 --list 查看可用備份');
            return self::FAILURE;
        }

        // 自動找檔案（含 .gz fallback）
        $candidates = [$fileOpt, $fileOpt . '.gz', preg_replace('/\.gz$/', '', $fileOpt)];
        $actualPath = null;
        foreach ($candidates as $c) {
            if (function_exists("is_absolute")) { $path = \is_absolute($c) ? $c : $backupDir . DIRECTORY_SEPARATOR . $c; } else { $path = (substr($c, 1, 2) === ":/" || substr($c, 0, 1) === "/" || substr($c, 0, 2) === "\\") ? $c : $backupDir . DIRECTORY_SEPARATOR . $c; }
            if (file_exists($path)) {
                $actualPath = $path;
                break;
            }
        }

        if (! $actualPath) {
            $this->error('找不到備份檔：' . $fileOpt);
            $this->line('請用 php artisan db:restore --list 查看');
            return self::FAILURE;
        }

        $this->warn('⚠️  危險操作：即將還原資料庫，將丟失所有現有資料！');
        $this->line('備份檔：' . $actualPath);
        $this->line('檔案大小：' . $this->humanFileSize(filesize($actualPath)));
        $this->line('修改時間：' . Carbon::createFromTimestamp(filemtime($actualPath))->format('Y-m-d H:i:s'));

        // 互動確認
        if (! $this->option('yes')) {
            if (! $this->confirm('確定要還原嗎？（此操作無法復原）', false)) {
                $this->info('已取消還原');
                return self::SUCCESS;
            }
        }

        // 還原前先備份當前 DB（保險絲）
        $this->info('[DB Restore] 還原前先備份當前 DB 到 safety_backup_*.sql ...');
        $this->call('db:backup', [
            '--output' => 'safety_backup_' . Carbon::now()->format('Ymd_His'),
            '--retention' => 7,
        ]);

        return $this->doRestore($actualPath);
    }

    private function doRestore(string $sqlPath): int
    {
        $cfg = config('database.connections.' . config('database.default'));
        $host = $cfg['host'];
        $port = $cfg['port'];
        $db = $cfg['database'];
        $user = $cfg['username'];
        $pass = $cfg['password'];

        $client = $this->findMysqlClient();
        $isGz = str_ends_with($sqlPath, '.gz');

        // 解壓縮（如需要）
        $sqlToImport = $sqlPath;
        $tmpFile = null;
        if ($isGz) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'restore_');
            $gz = gzopen($sqlPath, 'rb');
            $out = fopen($tmpFile, 'wb');
            while (! gzeof($gz)) fwrite($out, gzread($gz, 8192));
            fclose($out);
            gzclose($gz);
            $sqlToImport = $tmpFile;
            $this->info('[DB Restore] 已解壓縮暫存於 ' . $tmpFile);
        }

        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s %s < "%s"',
            $client,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($db),
            $sqlToImport
        );

        $this->info('[DB Restore] 開始匯入...');
        $start = microtime(true);
        exec($cmd . ' 2>&1', $out, $exitCode);
        $duration = round(microtime(true) - $start, 2);

        if ($tmpFile) @unlink($tmpFile);

        if ($exitCode !== 0) {
            $this->error('[DB Restore] 匯入失敗 (exit=' . $exitCode . ')');
            foreach ($out as $line) $this->line('  ' . $line);
            return self::FAILURE;
        }

        $this->info("[DB Restore] 完成！耗時 {$duration}s");
        return self::SUCCESS;
    }

    private function findMysqlClient(): string
    {
        $candidates = [
            'C:\\xampp1\\mysql\\bin\\mysql.exe',
            'C:\\xampp\\mysql\\bin\\mysql.exe',
            'mysql',
            'mariadb',
        ];
        foreach ($candidates as $bin) {
            if (file_exists($bin)) return $bin;
        }
        return 'mysql';
    }

    private function listBackups(string $dir): int
    {
        $files = File::files($dir);
        if (empty($files)) {
            $this->warn("備份目錄為空：{$dir}");
            return self::SUCCESS;
        }
        $this->info("備份清單（共 " . count($files) . " 個）：");
        $rows = [];
        foreach ($files as $f) {
            $rows[] = [
                $f->getFilename(),
                $this->humanFileSize($f->getSize()),
                Carbon::createFromTimestamp($f->getMTime())->format('Y-m-d H:i:s'),
            ];
        }
        $this->table(['檔名', '大小', '修改時間'], $rows);
        return self::SUCCESS;
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
