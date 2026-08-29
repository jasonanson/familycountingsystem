<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Services\Ai\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAiSettingController extends Controller
{
    /**
     * 顯示 AI 智能設定頁（僅限最大管理員 is_system_admin）
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->is_system_admin) {
            abort(403, '僅限系統最高管理員（is_system_admin）可以存取 AI 智能設定。');
        }

        $apiKey = SystemSetting::get('gemini_api_key', '');
        $model = SystemSetting::get('gemini_model', 'gemini-3.5-flash-lite');
        $aiEnabled = SystemSetting::get('ai_enabled', '1');

        // 模型選單選項
        $availableModels = [
            'gemini-3.5-flash-lite' => 'Gemini 3.5 Flash Lite（推薦：極速且經濟）',
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash Lite',
            'gemini-2.0-flash'      => 'Gemini 2.0 Flash',
            'gemini-1.5-flash'      => 'Gemini 1.5 Flash',
            'gemini-1.5-pro'        => 'Gemini 1.5 Pro',
        ];

        return view('admin.ai.index', compact('apiKey', 'model', 'aiEnabled', 'availableModels'));
    }

    /**
     * 更新 AI 設定（最大管理員可修改）
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_system_admin) {
            abort(403, '權限不足');
        }

        $request->validate([
            'gemini_api_key' => 'nullable|string|max:255',
            'gemini_model'   => 'required|string|max:100',
            'ai_enabled'     => 'nullable|in:0,1',
        ]);

        SystemSetting::set('gemini_api_key', trim($request->gemini_api_key ?? ''), 'Google Gemini API Key');
        SystemSetting::set('gemini_model', trim($request->gemini_model), 'Google Gemini 模型名稱');
        SystemSetting::set('ai_enabled', $request->has('ai_enabled') ? '1' : '0', 'AI 功能啟用開關');

        return redirect()->route('admin.ai.index')->with('success', 'AI 智能設定已成功更新！所有最高管理員均可同步套用。');
    }

    /**
     * 測試 API 連線
     */
    public function testConnection(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_system_admin) {
            return response()->json(['success' => false, 'message' => '權限不足'], 403);
        }

        $apiKey = $request->input('api_key') ?: SystemSetting::get('gemini_api_key');
        $model = $request->input('model') ?: SystemSetting::get('gemini_model', 'gemini-3.5-flash-lite');

        $result = GeminiService::testConnection($apiKey, $model);
        return response()->json($result);
    }

    /**
     * 系統廣播 AI 生成小幫手 API
     */
    public function generateBroadcast(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_system_admin) {
            return response()->json(['success' => false, 'message' => '權限不足'], 403);
        }

        $topic = $request->input('topic');
        if (empty($topic)) {
            return response()->json(['success' => false, 'message' => '請輸入公告主題'], 422);
        }

        try {
            $content = GeminiService::generateBroadcastContent($topic);
            return response()->json([
                'success' => true,
                'data' => $content,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI 生成失敗: ' . $e->getMessage(),
            ], 500);
        }
    }
}
