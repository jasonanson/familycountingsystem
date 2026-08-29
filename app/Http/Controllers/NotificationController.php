<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $notifications = Notification::where('user_id', $user->id)
            ->latest()
            ->get();

        $unreadCount = $notifications->whereNull('read_at')->count();

        $notificationsData = $notifications->map(function ($n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'read_at' => $n->read_at ? $n->read_at->toIso8601String() : null,
                'created_at' => $n->created_at ? $n->created_at->toIso8601String() : null,
                'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : '',
                'date_formatted' => $n->created_at ? $n->created_at->format('Y-m-d H:i') : '',
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $notificationsData,
                'unread_count' => $unreadCount,
            ]);
        }

        return view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'notificationsJson' => $notificationsData,
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification = Notification::find($id);
        if ($notification && ((int) $notification->user_id === (int) $user->id || $user->is_system_admin)) {
            if (! $notification->read_at) {
                $notification->update(['read_at' => now()]);
            }
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'id' => $id]);
        }

        return redirect()->back()->with('success', '已標記為已讀');
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', '已將所有通知標記為已讀');
    }

    public function destroy(Request $request, $id)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification = Notification::find($id);
        if ($notification && ((int) $notification->user_id === (int) $user->id || $user->is_system_admin)) {
            $notification->delete();
        }

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'id' => $id]);
        }

        return redirect()->back()->with('success', '通知已成功刪除');
    }

    private function seedSampleNotifications($userId, $familyId)
    {
        $samples = [
            [
                'type' => 'task_approval',
                'title' => '任務完成待審核',
                'body' => '成員 小明 已回報完成「整理房間與書桌」任務，請前往審核並發放零用金獎勵 $50。',
                'channel' => 'system',
                'created_at' => now()->subMinutes(15),
            ],
            [
                'type' => 'budget_alert',
                'title' => '預算警示提醒',
                'body' => '「餐飲外食」類別本月累積支出已達到設定預算上限的 85% ($8,500 / $10,000)，請注意控管！',
                'channel' => 'system',
                'created_at' => now()->subHours(2),
            ],
            [
                'type' => 'invitation',
                'title' => '家庭成員邀請成功',
                'body' => '歡迎使用 HomeSync Finance！您已成功進入「幸福家庭」共用記帳圈。',
                'channel' => 'system',
                'created_at' => now()->subHours(6),
            ],
            [
                'type' => 'bill',
                'title' => '固定支出到期提醒',
                'body' => '「Netflix 影音月費」預計將於明日扣款 NT$390，請確認連結扣款帳戶之餘額。',
                'channel' => 'system',
                'read_at' => now()->subDay(),
                'created_at' => now()->subDay(),
            ],
            [
                'type' => 'system',
                'title' => '系統升級成功通知',
                'body' => '站內通知中心 Phase 1 全新視覺與下拉選單功能已成功導入上線。',
                'channel' => 'system',
                'read_at' => now()->subDays(2),
                'created_at' => now()->subDays(2),
            ],
        ];

        foreach ($samples as $sample) {
            Notification::create(array_merge($sample, [
                'user_id' => $userId,
                'family_id' => $familyId,
            ]));
        }
    }
}
