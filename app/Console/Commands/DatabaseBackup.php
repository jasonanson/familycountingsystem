<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * 資料庫備份 artisan 命令（P3）。
 *
 * 用 mysqldump / mariadb-dump 工具產生 .sql 檔，
 * 預設存放於 storage/app/backups/，檔名格式：
 *   family_accounting_YYYYMMDD_HHMMSS.sql
 *
 * 用法：
 *   php artisan db:backup                     # 備份到預設路徑
 *   php artisan db:backup --output=custom.sql # 指定輸出檔名
 *   php artisan db:backup --compress          # gzip 壓縮
 *   php artisan db:backup --retention=14      # 自動清理 14 天前的備份
 *   php artisan db:backup --list              # 列出所有備份
 *
 * 注意：執行需要 mysqldump 在 PATH 或 XAMPP bin 目錄下；
 * 此命令會自動從 config/database.php 讀取連線設定。
 */
class DatabaseBackup extends Command
{
    protected $signature = 'db:backup
                            {--output= : 自訂輸出檔名（不含副檔名）}
                            {--compress : gzip 壓縮}
                            {--retention= : 自動保留天數，舊備份會被刪除}
                            {--list : 列出所有備份}';

    protected $description = '匯出資料庫為 .sql 檔，支援 gzip 壓縮與自動保留天數';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        if ($this->option('list')) {
            return $this->listBackups($backupDir);
        }

        $timestamp = Carbon::now()->format('Ymd_His');
        $customName = $this->option('output');
        $filename = ($customName ? $customName : "family_accounting_{$timestamp}") . '.sql';
        $output = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $command = $this->buildMysqldumpCommand($output, (bool) $this->option('compress'));

        $this->info("[DB Backup] 開始匯出至 {$output}");
        $this->line("  Command: {$command}");

        $startTime = microtime(true);
        exec($command . ' 2>&1', $outputLines, $exitCode);
        $duration = round(microtime(true) - $startTime, 2);

        if ($exitCode !== 0) {
            $this->error('[DB Backup] 匯出失敗 (exit=' . $exitCode . ')');
            foreach ($outputLines as $line) $this->line('  ' . $line);
            return self::FAILURE;
        }

        $fileSize = filesize($output);
        $sizeStr = $this->humanFileSize($fileSize);
        $this->info("[DB Backup] 完成！耗時 {$duration}s，檔案大小 {$sizeStr}");

        // 處理 retention
        if ($retention = $this->option('retention')) {
            $this->applyRetention($backupDir, (int) $retention);
        }

        return self::SUCCESS;
    }

    /**
     * 從 config 讀取 DB 連線，組合 mysqldump 指令。
     */
    private function buildMysqldumpCommand(string $outputPath, bool $compress): string
    {
        $cfg = config('database.connections.' . config('database.default'));
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = $cfg['port'] ?? '3306';
        $db   = $cfg['database'];
        $user = $cfg['username'];
        $pass = $cfg['password'];

        // 找 mysqldump / mariadb-dump（先試 XAMPP bin，再從 PATH）
        $dump = $this->findDumpBinary();

        $cmd = sprintf(
            '"%s" --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --add-drop-table --default-character-set=utf8mb4 %s > "%s"',
            $dump,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($pass),
            escapeshellarg($db),
            $outputPath
        );

        if ($compress) {
            // Windows 上沒 gzip，改用 7z；找不到時 fallback 跳過
            $gzPath = $outputPath . '.gz';
            if (file_exists('C:\Program Files\7-Zip\7z.exe')) {
                $cmd = $cmd . ' && "C:\Program Files\7-Zip\7z.exe" a -tgzip -sdel "' . $gzPath . '" "' . $outputPath . '"';
            }
        }
        return $cmd;
    }

    private function findDumpBinary(): string
    {
        $candidates = [
            'C:\\xampp1\\mysql\\bin\\mysqldump.exe',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
            'mariadb-dump',
        ];
        foreach ($candidates as $bin) {
            if (file_exists($bin)) return $bin;
            // 從 PATH 找
            $output = [];
            $rc = 0;
            exec('where ' . escapeshellarg(basename($bin)) . ' 2>nul', $output, $rc);
            if ($rc === 0 && !empty($output)) return trim($output[0]);
        }
        return 'mysqldump';
    }

    private function applyRetention(string $dir, int $days): void
    {
        $threshold = Carbon::now()->subDays($days)->timestamp;
        $files = File::files($dir);
        $deleted = 0;
        foreach ($files as $f) {
            if ($f->getMTime() < $threshold) {
                File::delete($f->getPathname());
                $this->line('  - 清理過期: ' . $f->getFilename());
                $deleted++;
            }
        }
        $this->info("[DB Backup] 已清理 {$deleted} 個過期備份（>{$days} 天）");
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
