<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataExchangeController extends Controller
{
    /**
     * 顯示 CSV 匯出/匯入控制介面
     */
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $user = Auth::user();
        $family = $user->currentFamily;

        $totalTransactions = Transaction::count();
        $latestTransaction = Transaction::orderBy('occurred_at', 'desc')->first();
        $accounts = Account::where('is_archived', false)->get();
        $categories = Category::all();

        return view('data_exchange.index', compact(
            'totalTransactions',
            'latestTransaction',
            'accounts',
            'categories'
        ));
    }

    /**
     * 匯出當前家庭交易明細為 CSV 檔案
     * (包含欄位: 日期, 類型, 分類, 帳戶, 金額, 交易人, 備註)
     */
    public function exportCsv(Request $request)
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $query = Transaction::with(['category', 'account', 'user'])
            ->orderBy('occurred_at', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->date_to);
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $transactions = $query->get();
        $fileName = 'transactions_export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // 寫入 UTF-8 BOM，確保 Excel 開啟不會亂碼
            fwrite($file, "\xEF\xBB\xBF");

            // CSV 標頭: 日期, 類型, 分類, 帳戶, 金額, 交易人, 備註
            fputcsv($file, ['日期', '類型', '分類', '帳戶', '金額', '交易人', '備註']);

            foreach ($transactions as $tx) {
                $typeName = match ($tx->type) {
                    'expense' => '支出',
                    'income' => '收入',
                    'transfer' => '轉帳',
                    default => $tx->type,
                };

                $categoryName = $tx->category?->name ?? ($tx->type_custom ?: '未分類');
                $accountName = $tx->account?->name ?? '無';
                $userName = $tx->user?->name ?? '系統';
                $note = $tx->description ?: ($tx->payee_custom ?: $tx->note);

                fputcsv($file, [
                    $tx->occurred_at ? $tx->occurred_at->format('Y-m-d H:i:s') : '',
                    $typeName,
                    $categoryName,
                    $accountName,
                    $tx->amount,
                    $userName,
                    $note,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * CSV 匯入對帳單精靈
     * (讀取 CSV 檔案、對映欄位、過濾重複紀錄、批次新增交易紀錄並回傳成功比數)
     */
    public function importCsv(Request $request)
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt,excel|max:5120',
        ], [
            'csv_file.required' => '請選擇要匯入的 CSV 對帳單檔案。',
            'csv_file.mimes' => '檔案格式必須為 CSV 檔 (.csv)。',
            'csv_file.max' => '檔案大小不得超過 5MB。',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        // 讀取檔案內容並轉碼成 UTF-8 (若包含 Big5 / GBK 轉碼處理)
        $rawContent = file_get_contents($path);
        if (! mb_check_encoding($rawContent, 'UTF-8')) {
            $rawContent = mb_convert_encoding($rawContent, 'UTF-8', 'BIG-5,GBK,GB2312,SJIS,auto');
        }
        // 去除 UTF-8 BOM
        $rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent);

        $tempFile = fopen('php://memory', 'r+');
        fwrite($tempFile, $rawContent);
        rewind($tempFile);

        $rows = [];
        while (($data = fgetcsv($tempFile, 2000, ',')) !== false) {
            if (! empty(array_filter($data))) {
                $rows[] = array_map('trim', $data);
            }
        }
        fclose($tempFile);

        if (count($rows) < 2) {
            return back()->with('error', '⚠️ 匯入失敗：CSV 檔案內容為空或缺少的資料列。');
        }

        // 解析第一列 Header 作為欄位對映 Mapping
        $headerRow = array_map(fn($col) => mb_strtolower($col), $rows[0]);
        
        $dateIdx = $this->findHeaderIndex($headerRow, ['日期', '時間', 'occurred_at', 'date'], 0);
        $typeIdx = $this->findHeaderIndex($headerRow, ['類型', 'type', '收支', '交易類型'], 1);
        $categoryIdx = $this->findHeaderIndex($headerRow, ['分類', 'category', '項目'], 2);
        $accountIdx = $this->findHeaderIndex($headerRow, ['帳戶', 'account', '扣款帳戶', '入帳帳戶'], 3);
        $amountIdx = $this->findHeaderIndex($headerRow, ['金額', 'amount', '數額', 'price'], 4);
        $userIdx = $this->findHeaderIndex($headerRow, ['交易人', '成員', 'user', '記帳者'], 5);
        $noteIdx = $this->findHeaderIndex($headerRow, ['備註', '描述', '說明', 'note', 'description'], 6);

        $user = Auth::user();
        $family = $user->currentFamily;

        $insertedCount = 0;
        $skippedCount = 0;

        // 讀取第 2 列起之數據
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $rawDate = $row[$dateIdx] ?? null;
            $rawType = $row[$typeIdx] ?? '支出';
            $categoryName = $row[$categoryIdx] ?? null;
            $accountName = $row[$accountIdx] ?? null;
            $rawAmount = $row[$amountIdx] ?? null;
            $userName = $row[$userIdx] ?? null;
            $note = $row[$noteIdx] ?? null;

            // 清理金額 (移除逗號, 轉為 float)
            $cleanedAmount = preg_replace('/[^\d.]/', '', (string) $rawAmount);
            if (! is_numeric($cleanedAmount) || (float) $cleanedAmount <= 0) {
                continue;
            }
            $amount = (float) $cleanedAmount;

            // 解析日期時間
            try {
                $occurredAt = $rawDate ? Carbon::parse($rawDate) : now();
            } catch (\Exception $e) {
                $occurredAt = now();
            }

            // 解析交易類型 (支出 / 收入 / 轉帳)
            $type = 'expense';
            if (str_contains($rawType, '收') || strtolower($rawType) === 'income') {
                $type = 'income';
            } elseif (str_contains($rawType, '轉') || strtolower($rawType) === 'transfer') {
                $type = 'transfer';
            }

            // 過濾重複紀錄檢查：相同金額、相同日期(以日或精確時間)、相同類型的交易
            $duplicateExists = Transaction::whereDate('occurred_at', $occurredAt->toDateString())
                ->where('type', $type)
                ->where('amount', $amount)
                ->where(function ($q) use ($note) {
                    if ($note) {
                        $q->where('description', 'like', "%{$note}%")
                          ->orWhere('note', 'like', "%{$note}%");
                    }
                })
                ->exists();

            if ($duplicateExists) {
                $skippedCount++;
                continue;
            }

            // 自動對映或建立分類 Category
            $categoryId = null;
            if ($categoryName) {
                $category = Category::where('name', $categoryName)
                    ->where(function ($q) use ($family) {
                        $q->whereNull('family_id')->orWhere('family_id', $family?->id);
                    })
                    ->first();

                if (! $category) {
                    $category = Category::create([
                        'family_id' => $family?->id,
                        'name' => $categoryName,
                        'type' => $type,
                        'is_custom' => true,
                        'color' => '#10B981',
                    ]);
                }
                $categoryId = $category->id;
            }

            // 自動對映或建立帳戶 Account
            $accountId = null;
            if ($accountName && $accountName !== '無') {
                $account = Account::where('name', $accountName)->first();
                if (! $account) {
                    $account = Account::create([
                        'family_id' => $family?->id,
                        'name' => $accountName,
                        'type' => 'custom',
                        'balance' => 0,
                    ]);
                }
                $accountId = $account->id;
            } else {
                $firstAccount = Account::where('is_archived', false)->first();
                $accountId = $firstAccount?->id;
            }

            // 新增交易紀錄
            $transaction = Transaction::create([
                'family_id' => $family?->id,
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'occurred_at' => $occurredAt,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'description' => $note,
                'note' => $note,
            ]);

            // 自動更新帳戶餘額
            if ($accountId && $acc = Account::find($accountId)) {
                if ($type === 'expense') {
                    $acc->decrement('balance', $amount);
                } elseif ($type === 'income') {
                    $acc->increment('balance', $amount);
                }
            }

            $insertedCount++;
        }

        // 紀錄稽核日誌
        AuditService::log(
            'csv_imported',
            Transaction::class,
            null,
            ['成功筆數' => $insertedCount, '重複略過' => $skippedCount],
            "完成 CSV 對帳單匯入：新增 {$insertedCount} 筆",
            "使用者 {$user->name} 已使用 CSV 對帳單精靈成功匯入 {$insertedCount} 筆紀錄 (自動過濾重複 {$skippedCount} 筆)"
        );

        return back()->with('success', "🎉 CSV 對帳單匯入成功！共新增 {$insertedCount} 筆交易紀錄（已自動過濾 {$skippedCount} 筆重複紀錄）。");
    }

    /**
     * 尋找 Header 比對索引
     */
    private function findHeaderIndex(array $headerRow, array $keywords, int $defaultIdx): int
    {
        foreach ($headerRow as $idx => $colName) {
            foreach ($keywords as $kw) {
                if (str_contains($colName, strtolower($kw))) {
                    return $idx;
                }
            }
        }
        return $defaultIdx;
    }
}
