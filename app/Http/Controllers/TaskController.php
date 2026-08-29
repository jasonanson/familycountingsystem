<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index()
    {
        if (! Auth::check()) {
            $defaultUser = \App\Models\User::where('account', 'parent')->first() ?? \App\Models\User::first();
            if ($defaultUser) Auth::login($defaultUser);
        }

        $user = Auth::user();
        $family = $user->currentFamily;

        $tasks = Task::with(['assignee', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        $familyMembers = $family ? $family->members : collect();

        $currentRole = $user->currentFamilyRole();
        $isChildUser = ($currentRole === 'child' || $user->registration_role === 'child');
        $isParentOrAdminUser = (! $isChildUser || (bool) $user->is_system_admin);

        return view('tasks.index', compact('tasks', 'familyMembers', 'user', 'isChildUser', 'isParentOrAdminUser'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        // 小孩不能發布家事任務 — 只有家長/管理員可以
        $role = $user->currentFamilyRole();
        $isChildUser = ($role === 'child' || $user->registration_role === 'child');
        if ($isChildUser) {
            return redirect()->back()->with('error', '⚠️ 家事任務只能由家長發布,小孩不能建立任務喔！');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'reward_amount' => 'required|numeric|min:1',
            'assignee_user_id' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
        ]);

        $family = $user->currentFamily;

        Task::create([
            'family_id' => $family?->id,
            'name' => $validated['name'],
            'reward_type' => 'fixed',
            'reward_amount' => $validated['reward_amount'],
            'assignee_user_id' => $validated['assignee_user_id'],
            'due_date' => $validated['due_date'],
            'status' => 'pending',
        ]);

        return back()->with('success', '🎉 已成功發布家事獎勵任務！');
    }

    public function report(Task $task)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = $user->currentFamilyRole();
        $isChild = ($userRole === 'child' || $user->registration_role === 'child');

        // 若為管理員或大人（非被指派者且非小孩角色），不可回報任務
        if (! $isChild && ($task->assignee_user_id && $task->assignee_user_id !== $user->id)) {
            return back()->with('error', '⚠️ 系統管理員與大人無法以小孩身分回報任務，家事任務須由孩童本人回報！');
        }
        if (! $isChild && $user->is_system_admin) {
            return back()->with('error', '⚠️ 系統管理員為大人身分，無法回報家事任務，請由孩童帳號回報！');
        }

        $task->update([
            'status' => 'reported',
            'reported_at' => now(),
        ]);

        $family = $task->family ?: $user?->currentFamily;

        // 當孩童回報任務完成時，自動為家庭內所有家長建立通知
        $childName = $task->assignee?->name ?? $user?->name ?? '孩童';
        $parents = $family ? $family->members()->wherePivot('role', 'parent')->get() : collect();
        if ($parents->isEmpty() && $family) {
            $parents = User::where('id', $family->owner_user_id)->get();
            if ($parents->isEmpty()) {
                $parents = $family->members;
            }
        }

        foreach ($parents as $parent) {
            Notification::create([
                'user_id' => $parent->id,
                'family_id' => $family?->id,
                'type' => 'task_reported',
                'title' => '任務回報審核',
                'body' => "孩童 {$childName} 已回報完成任務 {$task->name}",
                'channel' => 'system',
                'related_entity' => ['task_id' => $task->id],
                'sent_at' => now(),
            ]);
        }

        return back()->with('success', '👏 已成功回報完成任務，等待家長審核！');
    }

    public function approve(Task $task)
    {
        $user = Auth::user();
        $family = $user?->currentFamily ?: $task->family;

        $task->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_user_id' => $user?->id,
        ]);

        // 如果指派給兒童或成員，自動記錄獎金發放為收入
        if ($task->assignee_user_id && $task->reward_amount > 0) {
            $allowanceCat = Category::where('name', '零用錢/禮金')->first() ?? Category::first();
            $account = Account::first();

            Transaction::create([
                'family_id' => $family?->id,
                'user_id' => $task->assignee_user_id,
                'type' => 'income',
                'amount' => $task->reward_amount,
                'occurred_at' => now(),
                'account_id' => $account?->id,
                'category_id' => $allowanceCat?->id,
                'description' => "【家事獎勵金】{$task->name}",
            ]);

            if ($account) {
                $account->increment('balance', $task->reward_amount);
            }
        }

        // 當家長核准時，自動為該孩童建立通知
        if ($task->assignee_user_id) {
            Notification::create([
                'user_id' => $task->assignee_user_id,
                'family_id' => $family?->id ?: $task->family_id,
                'type' => 'task_approved',
                'title' => '任務審核通過',
                'body' => "您的任務「{$task->name}」已獲得審核通過！",
                'channel' => 'system',
                'related_entity' => ['task_id' => $task->id],
                'sent_at' => now(),
            ]);
        }

        return back()->with('success', '✅ 審核通過！獎金已撥發至成員賬戶。');
    }

    public function reject(Request $request, Task $task)
    {
        $validated = $request->validate([
            'reject_reason' => 'nullable|string|max:255',
        ]);

        $reason = $validated['reject_reason'] ?? '未符合完成標準';

        $task->update([
            'status' => 'rejected',
            'reject_reason' => $reason,
        ]);

        $user = Auth::user();
        $family = $user?->currentFamily ?: $task->family;

        // 當家長駁回時，自動為該孩童建立通知
        if ($task->assignee_user_id) {
            Notification::create([
                'user_id' => $task->assignee_user_id,
                'family_id' => $family?->id ?: $task->family_id,
                'type' => 'task_rejected',
                'title' => '任務回報駁回',
                'body' => "您的任務「{$task->name}」回報已被駁回。原因：{$reason}",
                'channel' => 'system',
                'related_entity' => ['task_id' => $task->id],
                'sent_at' => now(),
            ]);
        }

        return back()->with('success', '已駁回該任務回報。');
    }
}

