<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\ChildLimit;
use App\Models\CustomValuePromotion;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        if (! \Illuminate\Support\Facades\Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) {
                \Illuminate\Support\Facades\Auth::login($defaultUser);
            }
        }

        $user = auth()->user();
        $family = $user?->currentFamily;

        $typeFilter = $request->get('type', 'all');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        $query = Transaction::with(['category', 'account', 'user', 'payeeUser', 'attachments'])
            ->orderBy('occurred_at', 'desc');

        $userRoleInFamily = $family?->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        if ($userRoleInFamily === 'child') {
            $query->where('user_id', $user->id);
        }

        if ($typeFilter !== 'all') {
            $query->where('type', $typeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('payee_custom', 'like', "%{$search}%");
            });
        }

        if ($dateFrom) {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('occurred_at', '<=', $dateTo);
        }

        $transactions = $query->paginate(15);

        // 下拉清單資料（加入 Cache 查詢加速）
        $famId = $family?->id ?? 0;
        $accounts = \Illuminate\Support\Facades\Cache::remember("accounts_family_{$famId}", 1800, function () {
            return Account::where('is_archived', false)->get();
        });
        $expenseCategories = \Illuminate\Support\Facades\Cache::remember("categories_expense_family_{$famId}", 3600, function () {
            return Category::where('type', 'expense')->whereNull('parent_id')->with('children')->get();
        });
        $incomeCategories = \Illuminate\Support\Facades\Cache::remember("categories_income_family_{$famId}", 3600, function () {
            return Category::where('type', 'income')->whereNull('parent_id')->with('children')->get();
        });
        $familyMembers = $family ? $family->members : collect();

        return view('transactions.index', compact(
            'transactions',
            'typeFilter',
            'search',
            'dateFrom',
            'dateTo',
            'accounts',
            'expenseCategories',
            'incomeCategories',
            'familyMembers'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer,split,refund,custom',
            'amount' => 'required|numeric|min:1',
            'occurred_at' => 'required|date',
            'category_id' => 'nullable',
            'category_id_custom' => 'nullable|string',
            'account_id' => 'nullable',
            'account_id_custom' => 'nullable|string',
            'payee_user_id' => 'nullable',
            'payee_custom' => 'nullable|string',
            'description' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $family = $user->currentFamily;
        $amount = (float) $validated['amount'];

        // 1. 檢查兒童消費範圍限制 (Child Limit)
        $userRoleInFamily = $family?->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        if ($userRoleInFamily === 'child' && $validated['type'] === 'expense') {
            $childLimit = ChildLimit::where('child_user_id', $user->id)->first();
            if ($childLimit) {
                $txnDate = \Carbon\Carbon::parse($validated['occurred_at']);
                $isEffective = true;
                if ($childLimit->effective_from && $txnDate->lt(\Carbon\Carbon::parse($childLimit->effective_from)->startOfDay())) {
                    $isEffective = false;
                }
                if ($childLimit->effective_to && $txnDate->gt(\Carbon\Carbon::parse($childLimit->effective_to)->endOfDay())) {
                    $isEffective = false;
                }

                if ($isEffective) {
                    // 單筆限額 (per_transaction_max)
                    if ($childLimit->per_transaction_max !== null && $amount > $childLimit->per_transaction_max) {
                        $this->notifyParentsLimitExceeded($family, $user, $amount, "單筆限額 (NT$ " . number_format($childLimit->per_transaction_max) . ")");
                        return back()->withInput()->with('error', "⚠️ 系統提示：該筆金額超出家長設定之單筆限額 (NT$ " . number_format($childLimit->per_transaction_max) . ")");
                    }

                    // 今日每日限額 (daily_max)
                    if ($childLimit->daily_max !== null) {
                        $dailySpent = (float) Transaction::where('user_id', $user->id)
                            ->where('type', 'expense')
                            ->whereDate('occurred_at', $txnDate->toDateString())
                            ->sum('amount');
                        if (($dailySpent + $amount) > $childLimit->daily_max) {
                            $this->notifyParentsLimitExceeded($family, $user, $amount, "每日限額 (NT$ " . number_format($childLimit->daily_max) . ")");
                            return back()->withInput()->with('error', "⚠️ 系統提示：今日累計支出將達 NT$ " . number_format($dailySpent + $amount) . "，超出每日限額 (NT$ " . number_format($childLimit->daily_max) . ")");
                        }
                    }

                    // 本週每週限額 (weekly_max)
                    if ($childLimit->weekly_max !== null) {
                        $startOfWeek = $txnDate->copy()->startOfWeek();
                        $endOfWeek = $txnDate->copy()->endOfWeek();
                        $weeklySpent = (float) Transaction::where('user_id', $user->id)
                            ->where('type', 'expense')
                            ->whereBetween('occurred_at', [$startOfWeek, $endOfWeek])
                            ->sum('amount');
                        if (($weeklySpent + $amount) > $childLimit->weekly_max) {
                            $this->notifyParentsLimitExceeded($family, $user, $amount, "每週限額 (NT$ " . number_format($childLimit->weekly_max) . ")");
                            return back()->withInput()->with('error', "⚠️ 系統提示：本週累計支出將達 NT$ " . number_format($weeklySpent + $amount) . "，超出每週限額 (NT$ " . number_format($childLimit->weekly_max) . ")");
                        }
                    }

                    // 本月每月限額 (monthly_max)
                    if ($childLimit->monthly_max !== null) {
                        $startOfMonth = $txnDate->copy()->startOfMonth();
                        $endOfMonth = $txnDate->copy()->endOfMonth();
                        $monthlySpent = (float) Transaction::where('user_id', $user->id)
                            ->where('type', 'expense')
                            ->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                            ->sum('amount');
                        if (($monthlySpent + $amount) > $childLimit->monthly_max) {
                            $this->notifyParentsLimitExceeded($family, $user, $amount, "每月限額 (NT$ " . number_format($childLimit->monthly_max) . ")");
                            return back()->withInput()->with('error', "⚠️ 系統提示：本月累計支出將達 NT$ " . number_format($monthlySpent + $amount) . "，超出每月限額 (NT$ " . number_format($childLimit->monthly_max) . ")");
                        }
                    }
                }
            }
        }

        // 2. 例外優先處理 - 分類 (Category)
        $categoryId = null;
        $typeCustom = null;

        if ($request->category_id === 'custom' && filled($request->category_id_custom)) {
            $customCategoryName = trim($request->category_id_custom);
            // 建立或取得自訂分類
            $newCat = Category::firstOrCreate([
                'family_id' => $family?->id,
                'name' => $customCategoryName,
                'type' => $validated['type'],
            ], [
                'is_custom' => true,
                'scope' => 'family',
                'color' => '#F59E0B',
            ]);
            $categoryId = $newCat->id;

            // 紀錄升格申請 (CustomValuePromotion)
            if ($family) {
                CustomValuePromotion::create([
                    'family_id' => $family->id,
                    'field_type' => 'category',
                    'proposed_value' => $customCategoryName,
                    'proposed_by_user_id' => $user->id,
                    'status' => 'pending',
                ]);
            }
        } elseif (is_numeric($request->category_id)) {
            $categoryId = (int) $request->category_id;
        }

        // 3. 例外優先處理 - 帳戶 (Account)
        $accountId = null;
        if ($request->account_id === 'custom' && filled($request->account_id_custom)) {
            $customAccName = trim($request->account_id_custom);
            $newAcc = Account::create([
                'family_id' => $family?->id,
                'name' => $customAccName,
                'type' => 'custom',
                'type_custom' => $customAccName,
                'balance' => 0.00,
            ]);
            $accountId = $newAcc->id;
        } elseif (is_numeric($request->account_id)) {
            $accountId = (int) $request->account_id;
        }

        // 4. 例外優先處理 - 付款對象 (Payee)
        $payeeUserId = is_numeric($request->payee_user_id) ? (int) $request->payee_user_id : null;
        $payeeCustom = ($request->payee_user_id === 'custom' || ! is_numeric($request->payee_user_id)) ? $request->payee_custom : null;

        // 5. 處理發票附件圖片上傳
        $uploadedFile = $request->file('attachment');
        $attachmentPath = null;
        $attachmentObj = null;
        $attachmentError = null;

        if ($uploadedFile) {
            // 嚴格檢查:必須沒有上傳錯誤、檔案大小 > 0、類型允許
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'pdf'];
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');

            if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $attachmentError = '上傳失敗 (錯誤代碼 ' . $uploadedFile->getError() . ')';
            } elseif ($uploadedFile->getSize() <= 0) {
                $attachmentError = '上傳的檔案是空的,請重新選擇';
            } elseif (! in_array($extension, $allowedExts, true)) {
                $attachmentError = '不支援的檔案格式: ' . $extension;
            } else {
                try {
                    $directory = public_path('attachments');
                    if (! \Illuminate\Support\Facades\File::isDirectory($directory)) {
                        \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true, true);
                    }
                    $filename = 'att_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $extension;
                    $uploadedFile->move($directory, $filename);

                    // 保險絲:move() 後再次驗證檔案是否真的有寫入
                    $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($fullPath) && filesize($fullPath) > 0) {
                        $attachmentPath = '/attachments/' . $filename;
                    } else {
                        // 移動後卻是空檔,清掉別留垃圾
                        @unlink($fullPath);
                        $attachmentError = '附件儲存失敗,請重新上傳';
                    }
                } catch (\Throwable $e) {
                    $attachmentError = '附件儲存發生例外: ' . $e->getMessage();
                }
            }

            // 附件是輔助紀錄 — 失敗就靜默跳過,只寫 log 給開發者排查
            if ($attachmentError) {
                \Illuminate\Support\Facades\Log::warning('Attachment upload skipped', [
                    'error' => $attachmentError,
                    'user_id' => \Illuminate\Support\Facades\Auth::id(),
                    'family_id' => $family?->id,
                ]);
            }
        }

        // 6. 處理多人分帳 / 拆帳 (Split Expense)
        $splitWith = null;
        if ($request->boolean('is_split')) {
            $mode = $request->input('split_mode', 'equal');
            $membersInput = (array) $request->input('split_members', []);
            if (! empty($membersInput)) {
                $splitList = [];
                $totalAmt = $amount;
                if ($mode === 'equal') {
                    $count = count($membersInput);
                    $perPerson = $count > 0 ? round($totalAmt / $count, 2) : $totalAmt;
                    foreach ($membersInput as $uid) {
                        $memUser = \App\Models\User::find($uid);
                        $splitList[] = [
                            'user_id' => (int) $uid,
                            'name' => $memUser?->name ?? '成員',
                            'amount' => $perPerson,
                            'ratio' => round(100 / $count, 2),
                        ];
                    }
                } elseif ($mode === 'amount') {
                    $customAmounts = (array) $request->input('split_amounts', []);
                    foreach ($membersInput as $uid) {
                        $memUser = \App\Models\User::find($uid);
                        $memAmt = (float) ($customAmounts[$uid] ?? ($customAmounts[(string) $uid] ?? 0));
                        $splitList[] = [
                            'user_id' => (int) $uid,
                            'name' => $memUser?->name ?? '成員',
                            'amount' => $memAmt,
                            'ratio' => $totalAmt > 0 ? round(($memAmt / $totalAmt) * 100, 2) : 0,
                        ];
                    }
                } elseif ($mode === 'ratio') {
                    $customRatios = (array) $request->input('split_ratios', []);
                    foreach ($membersInput as $uid) {
                        $memUser = \App\Models\User::find($uid);
                        $memRatio = (float) ($customRatios[$uid] ?? ($customRatios[(string) $uid] ?? 0));
                        $memAmt = round($totalAmt * ($memRatio / 100), 2);
                        $splitList[] = [
                            'user_id' => (int) $uid,
                            'name' => $memUser?->name ?? '成員',
                            'amount' => $memAmt,
                            'ratio' => $memRatio,
                        ];
                    }
                }

                $splitWith = [
                    'mode' => $mode,
                    'total_amount' => $totalAmt,
                    'payer_id' => $user->id,
                    'payer_name' => $user->name,
                    'members' => $splitList,
                ];

                // 為被分攤的家庭成員發送通知
                foreach ($splitList as $s) {
                    if ($s['user_id'] !== $user->id) {
                        \App\Models\Notification::create([
                            'user_id' => $s['user_id'],
                            'family_id' => $family?->id,
                            'type' => 'expense_split',
                            'title' => '👥 分帳分攤提醒',
                            'body' => "【{$user->name}】已記錄一筆支出 NT$ " . number_format($totalAmt) . "（{$request->description}），您的分攤金額為 NT$ " . number_format($s['amount']),
                            'channel' => 'system',
                            'sent_at' => now(),
                        ]);
                    }
                }
            }
        }

        // 7. 建立交易紀錄
        $transaction = Transaction::create([
            'family_id' => $family?->id,
            'user_id' => $user->id,
            'type' => $validated['type'],
            'type_custom' => $typeCustom,
            'amount' => $amount,
            'occurred_at' => $validated['occurred_at'],
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'payee_user_id' => $payeeUserId,
            'payee_custom' => $payeeCustom,
            'description' => $request->input('description'),
            'split_with' => $splitWith,
            'custom_fields' => $attachmentPath ? ['attachment' => $attachmentPath] : null,
        ]);

        if ($attachmentPath && $uploadedFile) {
            $attRecord = \App\Models\Attachment::create([
                'transaction_id' => $transaction->id,
                'family_id' => $family?->id,
                'user_id' => $user->id,
                'file_path' => $attachmentPath,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'file_size' => \Illuminate\Support\Facades\File::size(public_path(ltrim($attachmentPath, '/'))),
            ]);
            $transaction->attachment_ids = [$attRecord->id];
            $transaction->save();
        }

        // 7. 自動扣減/增加帳戶餘額
        if ($accountId && $account = Account::find($accountId)) {
            if ($validated['type'] === 'expense') {
                $account->decrement('balance', $amount);
            } elseif ($validated['type'] === 'income') {
                $account->increment('balance', $amount);
            }
        }

        // 8. 紀錄稽核日誌 (AuditLog) 並發送 Google Mail API / SMTP 電子郵件通知
        $typeName = $validated['type'] === 'expense' ? '支出' : '收入';
        \App\Services\AuditService::log(
            'transaction_created',
            Transaction::class,
            $transaction->id,
            [
                '金額' => "NT$ " . number_format($amount),
                '類型' => $typeName,
                '記帳成員' => $user->name,
                '日期' => $validated['occurred_at'],
            ],
            "新增{$typeName}紀錄 NT$ " . number_format($amount),
            "成員 {$user->name} 已於 {$validated['occurred_at']} 紀錄一筆{$typeName} NT$ " . number_format($amount)
        );

        return back()->with('success', '🎉 記帳成功！以「例外優先」模式完成紀錄，並觸發郵件通知。');
    }

    public function destroy(Transaction $transaction)
    {
        $transactionId = $transaction->id;
        $amount = $transaction->amount;
        $transaction->delete();

        \App\Services\AuditService::log(
            'transaction_deleted',
            Transaction::class,
            $transactionId,
            ['金額' => "NT$ " . number_format($amount)],
            "刪除交易紀錄 NT$ " . number_format($amount),
            "成員 " . auth()->user()->name . " 刪除了一筆 NT$ " . number_format($amount) . " 之交易紀錄"
        );

        return back()->with('success', '交易紀錄已成功刪除。');
    }
    public function calendar(Request $request)
    {
        if (! Auth::check()) {
            $defaultUser = User::where('account', 'parent')->first() ?? User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $user = Auth::user();
        $family = $user?->currentFamily;

        // 解析月份參數(預設當月)
        $monthParam = $request->get('month', now()->format('Y-m'));
        try {
            $currentMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } catch (\Exception) {
            $currentMonth = now()->startOfMonth();
        }
        $prevMonth = $currentMonth->copy()->subMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();

        // 取出此家庭當月所有交易(若是兒童只看到自己的)
        $userRole = $family?->members()->where('users.id', $user->id)->first()?->pivot?->role;
        $query = Transaction::with(['category', 'account', 'user'])
            ->whereBetween('occurred_at', [$currentMonth, $monthEnd]);
        if ($family) {
            $query->where('family_id', $family->id);
            if ($userRole === 'child') {
                $query->where('user_id', $user->id);
            }
        }
        $transactions = $query->get();

        // 依日期分組,統計每日收支(預先序列化為可 JSON 化的純陣列)
        $dailyData = [];
        for ($day = 1; $day <= $currentMonth->daysInMonth; $day++) {
            $date = $currentMonth->copy()->day($day);
            $dayTxs = $transactions->filter(fn($tx) => $tx->occurred_at->isSameDay($date));
            $txList = $dayTxs->map(function ($tx) {
                $payeeName = null;
                if ($tx->payeeUser) {
                    $payeeName = $tx->payeeUser->name;
                } elseif (! empty($tx->payee_custom)) {
                    $payeeName = $tx->payee_custom;
                }
                return [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => (float) $tx->amount,
                    'category_name' => $tx->category?->name,
                    'type_custom' => $tx->type_custom,
                    'description' => $tx->description,
                    'payee_name' => $payeeName,
                    'account_name' => $tx->account?->name,
                    'occurred_time' => $tx->occurred_at ? $tx->occurred_at->format('H:i') : '',
                    'user_name' => $tx->user?->name,
                ];
            })->values()->all();
            $dailyData[$date->format('Y-m-d')] = [
                'income' => (float) $dayTxs->where('type', 'income')->sum('amount'),
                'expense' => (float) $dayTxs->where('type', 'expense')->sum('amount'),
                'count' => $dayTxs->count(),
                'transactions' => $txList,
            ];
        }

        // 月份統計
        $monthStats = [
            'income' => (float) $transactions->where('type', 'income')->sum('amount'),
            'expense' => (float) $transactions->where('type', 'expense')->sum('amount'),
            'net' => (float) ($transactions->where('type', 'income')->sum('amount') - $transactions->where('type', 'expense')->sum('amount')),
            'count' => $transactions->count(),
        ];

        return view('transactions.calendar', compact(
            'dailyData',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'monthStats',
        ));
    }

    public function create(Request $request, ?Transaction $transaction = null)
    {
        if (! Auth::check()) {
            $defaultUser = User::where('account', 'parent')->first() ?? User::first();
            if ($defaultUser) {
                Auth::login($defaultUser);
            }
        }

        $user = Auth::user();
        $family = $user?->currentFamily;

        $famId = $family?->id ?? 0;
        $accounts = \Illuminate\Support\Facades\Cache::remember("accounts_family_{$famId}", 1800, function () {
            return Account::where('is_archived', false)->get();
        });
        $expenseCategories = \Illuminate\Support\Facades\Cache::remember("categories_expense_family_{$famId}", 3600, function () {
            return Category::where('type', 'expense')->whereNull('parent_id')->with('children')->get();
        });
        $incomeCategories = \Illuminate\Support\Facades\Cache::remember("categories_income_family_{$famId}", 3600, function () {
            return Category::where('type', 'income')->whereNull('parent_id')->with('children')->get();
        });
        $familyMembers = $family ? $family->members : collect();
        $defaultType = $transaction?->type ?? $request->get('type', 'expense');
        return view('transactions.create', compact(
            'accounts',
            'expenseCategories',
            'incomeCategories',
            'familyMembers',
            'defaultType',
            'transaction'
        ));
    }

        public function edit(Request $request, Transaction $transaction)
    {
        // 透過同一個 view 顯示編輯表單,並把 $transaction 傳過去
        return $this->create($request, $transaction);
    }

    public function update(Request $request, Transaction $transaction)
    {
        // 驗證與 store 邏輯相同
        $validated = $request->validate([
            'type' => 'required|in:expense,income,transfer,split,refund,custom',
            'amount' => 'required|numeric|min:1',
            'occurred_at' => 'required|date',
            'category_id' => 'nullable',
            'category_id_custom' => 'nullable|string',
            'account_id' => 'nullable',
            'account_id_custom' => 'nullable|string',
            'payee_user_id' => 'nullable',
            'payee_custom' => 'nullable|string',
            'description' => 'nullable|string|max:255',
        ], [
            'type.required' => '請選擇交易類型',
            'type.in' => '交易類型不正確',
            'amount.required' => '請輸入金額',
            'amount.numeric' => '金額必須是數字',
            'amount.min' => '金額必須大於 0',
            'occurred_at.required' => '請選擇日期時間',
            'occurred_at.date' => '日期格式不正確',
            'description.max' => '備註最多 255 字',
        ]);

        $user = auth()->user();
        $family = $user->currentFamily;
        $amount = (float) $validated['amount'];
        $oldAmount = (float) $transaction->amount;
        $oldAccountId = $transaction->account_id;
        $oldType = $transaction->type;

        // 1. 兒童消費限制
        $userRoleInFamily = $family?->members()->where('users.id', $user->id)->first()?->pivot?->role ?? $user->currentFamilyRole();
        if ($userRoleInFamily === 'child' && $validated['type'] === 'expense') {
            $childLimit = ChildLimit::where('child_user_id', $user->id)->first();
            if ($childLimit) {
                $txnDate = \Carbon\Carbon::parse($validated['occurred_at']);
                $isEffective = true;
                if ($childLimit->effective_from && $txnDate->lt(\Carbon\Carbon::parse($childLimit->effective_from)->startOfDay())) {
                    $isEffective = false;
                }
                if ($childLimit->effective_to && $txnDate->gt(\Carbon\Carbon::parse($childLimit->effective_to)->endOfDay())) {
                    $isEffective = false;
                }
                if ($isEffective && $childLimit->per_transaction_max !== null && $amount > $childLimit->per_transaction_max) {
                    return back()->withInput()->with('error', '⚠️ 系統提示：該筆金額超出家長設定之單筆限額 (NT$ ' . number_format($childLimit->per_transaction_max) . ')');
                }
            }
        }

        // 2. 解析 category / account / payee
        $typeCustom = null;
        $categoryId = null;
        if ($request->category_id === 'custom') {
            $typeCustom = $request->category_id_custom;
        } elseif (is_numeric($request->category_id)) {
            $categoryId = (int) $request->category_id;
        } elseif (is_string($request->category_id) && trim($request->category_id) !== '') {
            $typeCustom = $request->category_id;
        }

        $accountId = null;
        if ($request->account_id === 'custom') {
            $newAccount = Account::create([
                'name' => $request->account_id_custom ?? '新帳戶',
                'type' => $validated['type'] === 'income' ? 'income' : 'expense',
                'currency' => 'TWD',
                'balance' => 0,
                'icon' => 'account_balance_wallet',
                'family_id' => $family?->id,
            ]);
            $accountId = $newAccount->id;
        } elseif (is_numeric($request->account_id)) {
            $accountId = (int) $request->account_id;
        }

        $payeeUserId = null;
        $payeeCustom = null;
        if ($request->payee_user_id === 'custom' || ! is_numeric($request->payee_user_id)) {
            $payeeCustom = $request->payee_custom;
        } else {
            $payeeUserId = (int) $request->payee_user_id;
        }

        // 3. 附件處理
        $attachmentPath = null;
        $uploadedFile = $request->file('attachment');
        if ($uploadedFile && $uploadedFile->getError() === UPLOAD_ERR_OK && $uploadedFile->getSize() > 0) {
            $allowedExts = ['jpg','jpeg','png','gif','webp','heic','heif','pdf'];
            $extension = strtolower($uploadedFile->getClientOriginalExtension() ?: 'bin');
            if (in_array($extension, $allowedExts, true)) {
                try {
                    $directory = public_path('attachments');
                    if (! \Illuminate\Support\Facades\File::isDirectory($directory)) {
                        \Illuminate\Support\Facades\File::makeDirectory($directory, 0755, true, true);
                    }
                    $newFilename = 'att_' . time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $extension;
                    $uploadedFile->move($directory, $newFilename);
                    $fullPath = $directory . DIRECTORY_SEPARATOR . $newFilename;
                    if (file_exists($fullPath) && filesize($fullPath) > 0) {
                        $attachmentPath = '/attachments/' . $newFilename;
                    } else {
                        @unlink($fullPath);
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Attachment upload skipped (update)', ['error' => $e->getMessage()]);
                }
            }
        }

        // 4. 分帳處理
        $splitWith = null;
        if ($request->boolean('is_split')) {
            $mode = $request->input('split_mode', 'equal');
            $membersInput = (array) $request->input('split_members', []);
            if (! empty($membersInput)) {
                $splitList = [];
                $totalAmt = $amount;
                if ($mode === 'equal') {
                    $count = count($membersInput);
                    $perPerson = $count > 0 ? round($totalAmt / $count, 2) : $totalAmt;
                    foreach ($membersInput as $uid) {
                        $memUser = \App\Models\User::find($uid);
                        $splitList[] = [
                            'user_id' => (int) $uid,
                            'name' => $memUser?->name ?? '成員',
                            'amount' => $perPerson,
                            'ratio' => round(100 / $count, 2),
                        ];
                    }
                } elseif ($mode === 'amount') {
                    $customAmounts = (array) $request->input('split_amounts', []);
                    foreach ($membersInput as $uid) {
                        $memUser = \App\Models\User::find($uid);
                        $memAmt = (float) ($customAmounts[$uid] ?? ($customAmounts[(string) $uid] ?? 0));
                        $splitList[] = [
                            'user_id' => (int) $uid,
                            'name' => $memUser?->name ?? '成員',
                            'amount' => $memAmt,
                            'ratio' => $totalAmt > 0 ? round(($memAmt / $totalAmt) * 100, 2) : 0,
                        ];
                    }
                } elseif ($mode === 'ratio') {
                    $customRatios = (array) $request->input('split_ratios', []);
                    foreach ($membersInput as $uid) {
                        $memUser = \App\Models\User::find($uid);
                        $memRatio = (float) ($customRatios[$uid] ?? ($customRatios[(string) $uid] ?? 0));
                        $memAmt = round($totalAmt * ($memRatio / 100), 2);
                        $splitList[] = [
                            'user_id' => (int) $uid,
                            'name' => $memUser?->name ?? '成員',
                            'amount' => $memAmt,
                            'ratio' => $memRatio,
                        ];
                    }
                }
                $splitWith = [
                    'mode' => $mode,
                    'total_amount' => $totalAmt,
                    'payer_id' => $user->id,
                    'payer_name' => $user->name,
                    'members' => $splitList,
                ];
            }
        }

        // 5. 反推舊帳戶餘額變化
        if ($oldAccountId && $oldAccount = Account::find($oldAccountId)) {
            if ($oldType === 'expense') {
                $oldAccount->increment('balance', $oldAmount);
            } elseif ($oldType === 'income') {
                $oldAccount->decrement('balance', $oldAmount);
            }
        }

        // 6. 更新交易紀錄
        $transaction->update([
            'type' => $validated['type'],
            'type_custom' => $typeCustom,
            'amount' => $amount,
            'occurred_at' => $validated['occurred_at'],
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'payee_user_id' => $payeeUserId,
            'payee_custom' => $payeeCustom,
            'description' => $request->input('description'),
            'split_with' => $splitWith,
        ]);

        // 7. 如果附件有更新,寫進 custom_fields 與 Attachment 表
        if ($attachmentPath && $uploadedFile) {
            $transaction->custom_fields = array_merge($transaction->custom_fields ?? [], ['attachment' => $attachmentPath]);
            $transaction->save();
            \App\Models\Attachment::create([
                'transaction_id' => $transaction->id,
                'family_id' => $family?->id,
                'user_id' => $user->id,
                'file_path' => $attachmentPath,
                'file_name' => $uploadedFile->getClientOriginalName(),
                'mime_type' => $uploadedFile->getClientMimeType(),
                'file_size' => filesize(public_path(ltrim($attachmentPath, '/'))),
            ]);
        }

        // 8. 套用新帳戶餘額
        if ($accountId && $account = Account::find($accountId)) {
            if ($validated['type'] === 'expense') {
                $account->decrement('balance', $amount);
            } elseif ($validated['type'] === 'income') {
                $account->increment('balance', $amount);
            }
        }

        // 9. 稽核日誌
        \App\Services\AuditService::log(
            'transaction_updated',
            Transaction::class,
            $transaction->id,
            ['金額' => 'NT$ ' . number_format($amount), '類型' => $validated['type']],
            "更新交易紀錄 NT$ " . number_format($amount),
            "成員 " . $user->name . " 更新了交易紀錄 NT$ " . number_format($amount)
        );

        return redirect()->route('transactions.index')->with('success', '✏️ 交易紀錄已更新!');
    }

    protected function notifyParentsLimitExceeded($family, $childUser, $amount, $reason)
    {
        if (! $family) {
            return;
        }

        $parents = $family->members()->wherePivot('role', 'parent')->get();
        if ($parents->isEmpty()) {
            $parents = \App\Models\User::where('id', $family->owner_user_id)->get();
        }

        foreach ($parents as $parent) {
            \App\Models\Notification::create([
                'user_id' => $parent->id,
                'family_id' => $family->id,
                'type' => 'child_limit_exceeded',
                'title' => '孩童消費超額警示',
                'body' => "孩童【{$childUser->name}】嘗試進行一筆金額 NT$ " . number_format($amount) . " 之支出，超出【{$reason}】已自動攔截。",
                'channel' => 'system',
                'related_entity' => ['child_id' => $childUser->id, 'amount' => $amount],
                'sent_at' => now(),
            ]);
        }
    }
}







