<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification->title ?? '家庭記帳系統通知' }}</title>
</head>
@php
    $isAi = !empty($notification->related_entity['report_id']) 
            || str_contains($notification->title ?? '', 'AI') 
            || str_contains($notification->body ?? '', 'AI');
    $url = $notification->related_entity['url'] ?? url('/notifications');
    $month = $notification->related_entity['month'] ?? date('Y-m');
@endphp
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Microsoft JhengHei', Roboto, Helvetica, Arial, sans-serif; background-color: #f4f6f8; color: #1e293b; margin: 0; padding: 32px 12px; -webkit-font-smoothing: antialiased;">
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0;">
        
        <!-- Top Gradient Brand Bar -->
        <tr>
            <td style="background: {{ $isAi ? 'linear-gradient(135deg, #006b5f 0%, #0d9488 50%, #14b8a6 100%)' : '#006b5f' }}; padding: 28px 32px; text-align: left;">
                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <div style="display: inline-block; vertical-align: middle; margin-right: 10px;">
                                <span style="font-size: 28px; line-height: 1;">{{ $isAi ? '🤖' : '💰' }}</span>
                            </div>
                            <div style="display: inline-block; vertical-align: middle;">
                                <h1 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">
                                    HomeSync Finance
                                </h1>
                                <p style="color: #ccfbf1; margin: 2px 0 0 0; font-size: 12px; font-weight: 500;">
                                    {{ $isAi ? 'Google Gemini 3.5 AI 智慧家庭財務健檢' : '智慧家庭記帳與資產管理' }}
                                </p>
                            </div>
                        </td>
                        <td align="right">
                            @if($isAi)
                                <span style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: bold; padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.35); text-transform: uppercase;">
                                    Gemini 3.5 AI
                                </span>
                            @else
                                <span style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 11px; font-weight: bold; padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.35); text-transform: uppercase;">
                                    {{ strtoupper($notification->type ?? 'SYSTEM') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Main Body Content -->
        <tr>
            <td style="padding: 32px 32px 24px 32px;">
                <!-- Greeting -->
                @if(!empty($user))
                    <p style="font-size: 15px; font-weight: bold; color: #0f172a; margin: 0 0 16px 0;">
                        親愛的 {{ $user->name ?? $user->account }} 您好：
                    </p>
                @endif

                <!-- Title Banner -->
                <div style="background-color: {{ $isAi ? '#f0fdfa' : '#f8fafc' }}; border: 1px solid {{ $isAi ? '#99f6e4' : '#e2e8f0' }}; border-left: 5px solid {{ $isAi ? '#0d9488' : '#006b5f' }}; border-radius: 12px; padding: 18px 20px; margin-bottom: 24px;">
                    <h2 style="color: {{ $isAi ? '#0f766e' : '#006b5f' }}; font-size: 17px; font-weight: 800; margin: 0 0 8px 0; line-height: 1.4;">
                        {{ $notification->title }}
                    </h2>
                    <div style="font-size: 14px; line-height: 1.7; color: #334155; margin: 0; white-space: pre-line;">
                        {!! nl2br(e($notification->body)) !!}
                    </div>
                </div>

                <!-- Info Highlights Card -->
                <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 12px; padding: 16px 20px; margin-bottom: 28px; border: 1px solid #e2e8f0; font-size: 13px;">
                    @if(!empty($family))
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-weight: bold; width: 32%;">👨‍👩‍👧 所屬家庭</td>
                            <td style="padding: 6px 0; color: #0f172a; font-weight: 700;">{{ $family->name }}</td>
                        </tr>
                    @endif
                    @if($isAi)
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-weight: bold;">📅 分析月份期別</td>
                            <td style="padding: 6px 0; color: #0d9488; font-weight: 700;">{{ $month }} 月度報告</td>
                        </tr>
                        <tr>
                            <td style="padding: 6px 0; color: #64748b; font-weight: bold;">📢 通知廣播範圍</td>
                            <td style="padding: 6px 0; color: #0f172a; font-weight: 600;">{{ __('auto.0276') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding: 6px 0; color: #64748b; font-weight: bold;">🕒 發送時間</td>
                        <td style="padding: 6px 0; color: #475569; font-family: monospace;">{{ $notification->created_at ? $notification->created_at->format('Y-m-d H:i:s') : date('Y-m-d H:i:s') }}</td>
                    </tr>
                </table>

                <!-- Primary Action Button -->
                <div style="text-align: center; margin: 32px 0 20px 0;">
                    <a href="{{ $url }}" target="_blank" style="display: inline-block; background: {{ $isAi ? 'linear-gradient(135deg, #0d9488 0%, #006b5f 100%)' : '#006b5f' }}; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: bold; padding: 14px 36px; border-radius: 12px; box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35); letter-spacing: 0.3px;">
                        {{ $isAi ? '👉 立即前往查看 AI 財務分析報告' : '👉 前往 HomeSync Finance 查看' }}
                    </a>
                </div>

                @if($isAi)
                    <p style="text-align: center; font-size: 12px; color: #64748b; margin-top: 12px;">
                        💡 點擊按鈕即可登入並檢視本月份詳細收入支出比率、固定訂閱檢視與省錢建議。
                    </p>
                @endif
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 24px 32px; text-align: center; font-size: 12px; color: #94a3b8; line-height: 1.6;">
                <p style="margin: 0 0 6px 0;">此信件由 <strong>HomeSync Finance 家庭記帳管理系統</strong> 自動寄發，請勿直接回覆。</p>
                <p style="margin: 0;">© {{ date('Y') }} HomeSync Finance. All rights reserved.</p>
            </td>
        </tr>

    </table>
</body>
</html>
