<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>家庭記帳邀請</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .header {
            background-color: #006b5f;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            color: #333333;
            line-height: 1.6;
        }
        .content p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .highlight {
            color: #006b5f;
            font-weight: bold;
        }
        .btn-container {
            text-align: center;
            margin: 35px 0;
        }
        .btn {
            display: inline-block;
            background-color: #006b5f;
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(0, 107, 95, 0.2);
        }
        .info-box {
            background-color: #f9fafa;
            border-left: 4px solid #006b5f;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .info-box p {
            margin: 0 0 10px 0;
            font-size: 15px;
        }
        .info-box p:last-child {
            margin: 0;
        }
        .footer {
            background-color: #f9fafa;
            padding: 20px 30px;
            text-align: center;
            color: #888888;
            font-size: 13px;
            border-top: 1px solid #eeeeee;
        }
    </style>
</head>
<body>
    <table width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color: #f4f7f6;">
        <tr>
            <td align="center">
                <table class="container" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width: 600px;">
                    <tr>
                        <td class="header">
                            <h1>HomeSync Finance</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content">
                            <p>{{ __('auto.0332') }}</p>
                            
                            <p><span class="highlight">{{ $inviterName }}</span> 邀請您加入「<span class="highlight">{{ $familyName }}</span>」的家庭記帳！</p>
                            
                            <div class="info-box">
                                <p><strong>邀請身份：</strong>{{ $role }}</p>
                                <p><strong>有效期限：</strong>7天內有效 (至 {{ $invitation->expires_at->format('Y-m-d H:i') }})</p>
                            </div>
                            
                            <p>{{ __('auto.0209') }}</p>
                            
                            <div class="btn-container">
                                <a href="{{ $inviteUrl }}" class="btn">{{ __('auto.0351') }}</a>
                            </div>
                            
                            <p style="font-size: 14px; color: #666;">如果上方的按鈕無法點擊，請複製以下連結並貼上至瀏覽器：<br>
                            <a href="{{ $inviteUrl }}" style="color: #006b5f; word-break: break-all;">{{ $inviteUrl }}</a></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer">
                            <p>{{ __('auto.0259') }}</p>
                            <p>&copy; {{ date('Y') }} HomeSync Finance. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
