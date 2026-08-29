<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class ExportController extends Controller
{
    public function exportCsv(\Illuminate\Http\Request $request)
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $user = Auth::user();
        $family = $user->currentFamily;

        $fileName = 'transactions_' . now()->format('Ymd_His') . '.csv';

        $query = Transaction::with(['category', 'account', 'user'])
            ->orderBy('occurred_at', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->date_to);
        }

        $transactions = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // 寫入 UTF-8 BOM，確保 Excel 開啟繁體中文不會亂碼
            fwrite($file, "\xEF\xBB\xBF");

            // CSV 標頭
            fputcsv($file, ['編號', '日期時間', '交易類型', '分類', '金額 (TWD)', '扣款/入帳帳戶', '記帳成員', '對象/備註']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->occurred_at->format('Y-m-d H:i:s'),
                    $tx->type === 'expense' ? '支出' : ($tx->type === 'income' ? '收入' : '轉帳'),
                    $tx->category?->name ?? ($tx->type_custom ?: '未分類自訂'),
                    $tx->amount,
                    $tx->account?->name ?? '無',
                    $tx->user?->name ?? '系統',
                    $tx->payee_custom ?: $tx->description,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
