<?php

namespace App\Http\Controllers;

use App\Services\GmailConnectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 後台 → Gmail 連線管理
 *
 * 路由：/admin/gmail  (GET/POST)
 *
 * 流程：
 *   1. 管理員填 OAuth Client ID / Secret / Redirect URI → POST /admin/gmail/credentials
 *   2. 按「連接 Gmail」按鈕 → 走 OAuth → 回 /admin/gmail (顯示成功)
 *   3. 「寄測試信」→ POST /admin/gmail/test
 *   4. 「斷開連線」→ POST /admin/gmail/disconnect
 */
class AdminGmailController extends Controller
{
    /**
     * 顯示 Gmail 連線設定頁
     */
    public function index(Request $request, GmailConnectionService $service)
    {
        $status = $service->getStatus();

        // 把服務的 redirect URI 帶進 view（給表單預設用）
        return view('admin.gmail.index', [
            'status' => $status,
            'authUrl' => $status['is_connected'] || $status['client_id_full'] ? route('admin.gmail.connect') : null,
        ]);
    }

    /**
     * 儲存 OAuth Client 憑證（Client ID / Secret / Redirect URI）
     */
    public function saveCredentials(Request $request, GmailConnectionService $service)
    {
        $data = $request->validate([
            'client_id'     => 'required|string|max:255',
            'client_secret' => 'required|string|max:255',
            'redirect_uri'  => 'required|string|max:255',
        ]);

        $service->saveClientCredentials(
            $data['client_id'],
            $data['client_secret'],
            $data['redirect_uri']
        );

        return redirect()
            ->route('admin.gmail.index')
            ->with('success', '已儲存 OAuth 憑證。點「連接 Gmail」按鈕完成 Google 授權。');
    }

    /**
     * 觸發 Google OAuth 授權流程
     */
    public function connect(GmailConnectionService $service)
    {
        try {
            $url = $service->buildAuthorizationUrl();
            return redirect()->away($url);
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.gmail.index')
                ->with('error', '無法建立授權 URL：' . $e->getMessage());
        }
    }

    /**
     * 寄一封測試信
     */
    public function testSend(Request $request, GmailConnectionService $service)
    {
        $data = $request->validate([
            'to' => 'required|email',
        ]);

        try {
            $result = $service->sendTestEmail($data['to']);
            return redirect()
                ->route('admin.gmail.index')
                ->with('success', "✅ 測試信已寄到 {$result['to']}（Gmail Message ID: {$result['message_id']}）");
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.gmail.index')
                ->with('error', '❌ 寄信失敗：' . $e->getMessage());
        }
    }

    /**
     * 斷開 Gmail 連線
     */
    public function disconnect(GmailConnectionService $service)
    {
        $service->disconnect();
        return redirect()
            ->route('admin.gmail.index')
            ->with('success', '已斷開 Gmail 連線，所有相關設定已清除。');
    }
}
