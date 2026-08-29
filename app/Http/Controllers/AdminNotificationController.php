<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Models\Family;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNotificationController extends Controller
{
    protected function checkAdmin()
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403, '存取拒絕：權限不足，只有最高系統管理員可以訪問管理介面。');
        }
    }

    /**
     * 廣播通知：建立表單頁
     */
    public function create()
    {
        $this->checkAdmin();

        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::where('is_system_admin', true)->count(),
            'total_families' => Family::count(),
            'recent_sent' => Notification::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $families = Family::orderBy('name')->get(['id', 'name']);

        return view('admin.notifications.create', compact('stats', 'families'));
    }

    /**
     * 執行廣播：依 target 對使用者發送通知
     */
    public function broadcast(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'title'   => 'required|string|max:120',
            'body'    => 'required|string|max:1000',
            'type'    => 'required|in:info,success,warning,danger',
            'target'  => 'required|in:all,admins,family,none_in_family,no_family',
            'family_id' => 'required_if:target,family,none_in_family|nullable|exists:families,id',
            'channel' => 'required|in:database,email,database_email',
        ]);

        $title = $validated['title'];
        $body  = $validated['body'];
        $type  = $validated['type'];
        $target = $validated['target'];
        $familyId = $validated['family_id'] ?? null;

        // 依 target 選擇使用者
        $query = User::query();
        switch ($target) {
            case 'all':
                // 全部使用者
                break;
            case 'admins':
                $query->where('is_system_admin', true);
                break;
            case 'family':
                $query->whereHas('families', function ($q) use ($familyId) {
                    $q->where('families.id', $familyId);
                });
                break;
            case 'none_in_family':
                $query->whereDoesntHave('families', function ($q) use ($familyId) {
                    $q->where('families.id', $familyId);
                });
                break;
            case 'no_family':
                $query->whereDoesntHave('families');
                break;
        }

        $users = $query->get();
        $sentCount = 0;

        DB::transaction(function () use ($users, $title, $body, $type, $target, $familyId, &$sentCount) {
            foreach ($users as $user) {
                // channel 對應：'database' 只寫 DB；'email' 不寫 DB 直接發信；'database_email' 兩者
                $channel = request()->input('channel', 'database_email');
                if ($channel === 'email') {
                    // 透過 AuditService 風格但發 email；這裡直接用 Mail facade
                    \Illuminate\Support\Facades\Mail::to($user->email)->send(
                        new \App\Mail\NotificationAlertMail(
                            new Notification([
                                'user_id' => $user->id,
                                'family_id' => $familyId && $target === 'family' ? $familyId : null,
                                'type' => $type,
                                'title' => $title,
                                'body' => $body,
                            ])
                        )
                    );
                    $sentCount++;
                } else {
                    Notification::create([
                        'user_id' => $user->id,
                        'family_id' => $familyId && $target === 'family' ? $familyId : null,
                        'type' => $type,
                        'title' => $title,
                        'body' => $body,
                        'channel' => $channel === 'database_email' ? 'email' : 'system',
                    ]);
                    $sentCount++;
                }
            }
        });

        AuditService::log(
            'admin_notification_broadcast',
            Notification::class,
            null,
            [
                '標題' => $title,
                '類型' => $type,
                '對象' => $target,
                '家庭ID' => $familyId,
                '發送數量' => $sentCount,
            ],
            "管理員廣播通知：{$title}",
            "管理員 " . auth()->user()->name . " 對 {$target} 發送了「{$title}」，共 {$sentCount} 個接收者"
        );

        return redirect()
            ->route('admin.notifications.create')
            ->with('success', "✅ 通知已成功發送給 {$sentCount} 位使用者！");
    }
}
