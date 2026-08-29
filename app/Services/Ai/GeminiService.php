<?php

namespace App\Services\Ai;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * 取得設定的 API Key
     */
    public static function getApiKey(): ?string
    {
        return SystemSetting::get('gemini_api_key') ?: config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
    }

    /**
     * 取得設定的模型名稱（預設 Gemini 3.5 Flash Lite）
     */
    public static function getModel(): string
    {
        return SystemSetting::get('gemini_model', 'gemini-3.5-flash-lite');
    }

    /**
     * 檢查 AI 是否已啟用且已設定 Token
     */
    public static function isEnabled(): bool
    {
        $enabled = SystemSetting::get('ai_enabled', '1');
        return ($enabled === '1' || $enabled === 'true' || $enabled === true) && !empty(static::getApiKey());
    }

    /**
     * 呼叫 Google Gemini API 生成內容
     */
    public static function generateText(string $prompt, ?string $systemInstruction = null): string
    {
        $apiKey = static::getApiKey();
        if (empty($apiKey)) {
            throw new \Exception('尚未設定 Google Gemini API Token，請由系統管理員於「AI 智能設定」中填寫。');
        }

        $model = static::getModel();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ]
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if ($response->failed()) {
                $errorMsg = $response->json('error.message') ?? $response->body();
                Log::error("Gemini API 回傳錯誤 ({$response->status()}): {$errorMsg}");
                throw new \Exception("Gemini API 請求失敗 [{$response->status()}]: {$errorMsg}");
            }

            $candidates = $response->json('candidates');
            if (!empty($candidates[0]['content']['parts'][0]['text'])) {
                return trim($candidates[0]['content']['parts'][0]['text']);
            }

            throw new \Exception('Gemini API 未回傳有效文字內容。');
        } catch (\Throwable $e) {
            Log::error("GeminiService 呼叫異常: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * 測試 API 連線
     */
    public static function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $key = $apiKey ?: static::getApiKey();
        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Token 不能為空',
            ];
        }

        $modelName = $model ?: static::getModel();
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$key}";

        try {
            $response = Http::timeout(15)->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [['text' => '請以繁體中文回答簡短一句話：系統連線測試成功。']]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 60,
                ]
            ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text') ?? '連線成功';
                return [
                    'success' => true,
                    'model' => $modelName,
                    'message' => '連線成功！Gemini 回應：' . trim($reply),
                ];
            }

            $errorMsg = $response->json('error.message') ?? $response->body();
            return [
                'success' => false,
                'model' => $modelName,
                'message' => "連線失敗 ({$response->status()}): {$errorMsg}",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'model' => $modelName,
                'message' => '連線異常: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * AI 月報財務健檢分析
     */
    public static function analyzeMonthlyReport(array $reportData): string
    {
        $yearMonth = $reportData['yearMonth'] ?? date('Y-m');
        $income = number_format($reportData['totalIncome'] ?? 0, 2);
        $expense = number_format($reportData['totalExpense'] ?? 0, 2);
        $balance = number_format($reportData['netBalance'] ?? 0, 2);
        $topCategories = json_encode($reportData['topCategories'] ?? [], JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
你是一位親切、專業的家庭財務規劃顧問。請針對以下 {$yearMonth} 月份的家庭財務報表數據進行「智慧財務健檢與分析」：
- 總收入：NT$ {$income}
- 總支出：NT$ {$expense}
- 本月結餘：NT$ {$balance}
- 各分類支出排行：{$topCategories}

請以繁體中文提供以下 3 點結構化分析（請善用 Markdown 粗體、清單，排版清爽）：
1. 💡 **本月收支結構總評**（簡評結餘與健康度）
2. ⚠️ **值得注意的支出漏洞與潛在風險**
3. 🎯 **下月份可執行的 2~3 個具體省錢/優化建議**
PROMPT;

        return static::generateText($prompt, '你是一位專精於家庭記帳與個人理財的智慧分析師，回答語氣溫暖、具建設性且繁體中文格式優雅。');
    }

    /**
     * AI 預算超支溫馨提醒信件內容生成
     */
    public static function generateOverspendAdvice(string $categoryName, float $overspentAmount, float $budgetAmount): string
    {
        $prompt = <<<PROMPT
家庭記帳系統偵測到「{$categoryName}」分類支出已超出預算！
- 原設定預算：NT$ {$budgetAmount}
- 超支金額：NT$ {$overspentAmount}

請以繁體中文撰寫一段適合放在提醒通知信中的「溫馨叮嚀與調整小撇步」（約 80~120 字），語氣體貼幽默、不給人壓力，並提供 1 個實用的當月應對小訣竅。
PROMPT;

        return static::generateText($prompt, '你是一位友善的家庭記帳理財小幫手。');
    }

    /**
     * AI 系統廣播通知生成小幫手
     */
    public static function generateBroadcastContent(string $topic): array
    {
        $prompt = <<<PROMPT
身為系統管理員，我需要向全體家庭記帳系統的使用者發送一則系統公告。
公告主題：{$topic}

請依據此主題，產生：
1. 標題 (Title)：簡潔吸引人，不超過 25 字。
2. 內文 (Body)：溫暖親切且清晰明瞭，約 100~200 字。

請嚴格輸出 JSON 格式（不要有額外 markdown 區塊）：
{"title": "...", "body": "..."}
PROMPT;

        $raw = static::generateText($prompt, '請輸出標準 JSON 格式');
        // 清理可能的 markdown 標記
        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($raw));
        $data = json_decode($cleaned, true);

        if (json_last_error() === JSON_ERROR_NONE && !empty($data['title']) && !empty($data['body'])) {
            return $data;
        }

        return [
            'title' => "【系統公告】{$topic}",
            'body' => $raw,
        ];
    }

    /**
     * AI 家庭整體財務與訂閱狀態簡易分析
     */
    public static function analyzeFamilyFinanceSummary(array $familyData): string
    {
        $familyName = $familyData['family_name'] ?? '家庭';
        $month = $familyData['month'] ?? date('Y-m');
        $income = number_format($familyData['total_income'] ?? 0);
        $expense = number_format($familyData['total_expense'] ?? 0);
        $balance = number_format($familyData['net_balance'] ?? 0);
        $savingsRate = $familyData['savings_rate'] ?? 0;
        $subCount = $familyData['subscription_count'] ?? 0;
        $subTotal = number_format($familyData['subscription_total'] ?? 0);
        $subList = json_encode($familyData['subscriptions'] ?? [], JSON_UNESCAPED_UNICODE);
        $topCategories = json_encode($familyData['top_categories'] ?? [], JSON_UNESCAPED_UNICODE);
        $budgetAlerts = json_encode($familyData['budget_alerts'] ?? [], JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
你是一位資深家庭財務分析與理財顧問，請針對「{$familyName}」家庭在 {$month} 月份的財務與訂閱數據進行深度且親切的「AI 家庭財務狀態簡易分析報告」：

【數據摘要】：
- 當月總收入：NT$ {$income}
- 當月總支出：NT$ {$expense}
- 當月淨結餘：NT$ {$balance}（儲蓄率：{$savingsRate}%）
- 固定訂閱與經常性扣款：共 {$subCount} 筆，月花費合計 NT$ {$subTotal}
- 訂閱項目清單：{$subList}
- 主要支出分類排行：{$topCategories}
- 預算執行警示：{$budgetAlerts}

請以繁體中文撰寫一份給全體家長共讀的精簡分析報告，包含以下 4 個部分（格式請使用 Markdown，重點加粗，語氣清晰且溫暖）：
1. 🏡 **家庭收支與儲蓄健康度評估**（收支平衡分析）
2. 🔄 **固定訂閱與週期性支出審視**（是否有閒置或可合併之訂閱）
3. ⚠️ **家庭預算與潛在開銷風險**（是否有超支或需關注的分類）
4. 🎯 **給全體家長的 3 個智慧理財與省錢行動建議**
PROMPT;

        return static::generateText($prompt, '你是一位專業、親切的家庭財務顧問，回答以繁體中文格式化 Markdown 呈現。');
    }
}
