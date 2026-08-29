<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Models\FamilyUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function profile()
    {
        $user = Auth::user();
        return view('settings.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'locale' => 'nullable|string|in:' . implode(',', \App\Http\Middleware\SetLocale::AVAILABLE_LOCALES),
            'locale' => 'nullable|string|in:' . implode(',', \App\Http\Middleware\SetLocale::AVAILABLE_LOCALES),
            'avatar' => 'nullable|image|max:2048',
            'password' => 'nullable|string|min:6|confirmed',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->locale = $validated['locale'] ?? $user->locale;
        $user->locale = $validated['locale'] ?? $user->locale;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->boolean('remove_avatar')) {
            $user->avatar_url = null;
        } elseif ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/avatars');
            if (! file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);
            $user->avatar_url = 'uploads/avatars/' . $filename;
        }

        $user->save();

        return redirect()->route('settings.profile')->with('success', '🎉 個人資料已成功更新！');
    }

    public function family()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->current_family_id && $user->families()->exists()) {
            $user->update(['current_family_id' => $user->families()->first()->id]);
            $user->refresh();
        }

        $family = $user->currentFamily;
        if (! $family) {
            return view('settings.family', ['family' => null, 'canEdit' => false, 'members' => collect(), 'currentUserRole' => null]);
        }

        $currentUserRole = FamilyUser::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->value('role');

        $members = $family->members()->orderBy('family_user.joined_at')->get();

        return view('settings.family', compact('family', 'members', 'currentUserRole', 'user'));
    }

    public function updateFamily(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $family = $user->currentFamily;
        if (! $family) {
            return back()->withErrors(['error' => '找不到當前家庭']);
        }

        $currentUserRole = FamilyUser::where('family_id', $family->id)
            ->where('user_id', $user->id)
            ->value('role');

        if ($currentUserRole !== 'parent') {
            return back()->withErrors(['error' => '只有家長才能修改家庭設定']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency' => 'required|string|size:3|in:TWD,USD,JPY,EUR,GBP,AUD,CAD,CNY,HKD,SGD',
            'discord_webhook_url' => 'nullable|url|max:255|starts_with:https://discord.com/api/webhooks/,https://discordapp.com/api/webhooks/',
            'storage_quota_mb' => 'required|integer|min:100|max:10240',
        ], [
            'currency.in' => '只支援 TWD, USD, JPY, EUR, GBP, AUD, CAD, CNY, HKD, SGD',
            'currency.size' => '幣別代碼必須為 3 個字元',
            'discord_webhook_url.url' => 'Discord Webhook URL 格式不正確',
            'discord_webhook_url.starts_with' => 'Discord Webhook URL 必須以 https://discord.com/api/webhooks/ 開頭',
            'storage_quota_mb.min' => '附件容量下限為 100 MB',
            'storage_quota_mb.max' => '附件容量上限為 10 GB',
        ]);

        $family->update([
            'name' => $validated['name'],
            'currency' => $validated['currency'],
            'discord_webhook_url' => $validated['discord_webhook_url'] ?? null,
            'storage_quota_mb' => $validated['storage_quota_mb'],
        ]);

        return redirect()->route('settings.family')->with('success', '🎉 家庭設定已成功更新！');
    }

    public function notifications()
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $defaults = [
            'channels' => [
                'email' => true,
                'system' => true,
                'discord' => false,
            ],
            'preferences' => [
                'transaction_created' => ['system' => true, 'email' => false],
                'transaction_deleted' => ['system' => true, 'email' => false],
                'budget_alert' => ['system' => true, 'email' => true],
                'subscription_reminder' => ['system' => true, 'email' => true],
                'task_submitted' => ['system' => true, 'email' => false],
                'task_approved' => ['system' => true, 'email' => true],
                'task_rejected' => ['system' => true, 'email' => true],
                'family_invitation' => ['system' => true, 'email' => true],
                'limit_exceeded' => ['system' => true, 'email' => true],
            ],
            'budget_alert_threshold' => 80,
        ];

        $preferences = $user->notification_preferences ?? $defaults;

        $merged = array_replace_recursive($defaults, $preferences);

        return view('settings.notifications', [
            'user' => $user,
            'preferences' => $merged,
            'defaults' => $defaults,
        ]);
    }

    public function updateNotifications(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'channels' => 'required|array',
            'channels.email' => 'boolean',
            'channels.system' => 'boolean',
            'channels.discord' => 'boolean',
            'preferences' => 'required|array',
            'preferences.*' => 'array',
            'preferences.*.system' => 'boolean',
            'preferences.*.email' => 'boolean',
            'budget_alert_threshold' => 'required|integer|min:1|max:100',
        ], [
            'budget_alert_threshold.min' => '預算警示門檻至少 1%',
            'budget_alert_threshold.max' => '預算警示門檻最多 100%',
        ]);

        $notificationPreferences = [
            'channels' => [
                'email' => $request->boolean('channels.email'),
                'system' => $request->boolean('channels.system'),
                'discord' => $request->boolean('channels.discord'),
            ],
            'preferences' => [],
            'budget_alert_threshold' => (int) $validated['budget_alert_threshold'],
        ];

        foreach ($request->input('preferences', []) as $event => $channels) {
            $notificationPreferences['preferences'][$event] = [
                'system' => isset($channels['system']) && $channels['system'],
                'email' => isset($channels['email']) && $channels['email'],
            ];
        }

        $user->notification_preferences = $notificationPreferences;

        $user->save();

        return redirect()->route('settings.notifications')->with('success', '🎉 通知設定已成功更新！');
    }
}