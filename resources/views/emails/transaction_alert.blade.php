<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: 'Microsoft JhengHei', Arial, sans-serif; background-color: #fff8f2; color: #1e1b17; padding: 20px;">
    <div style="max-w: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px border #e7e5e4; padding: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.05);">
        <div style="border-bottom: 2px solid #006b5f; padding-bottom: 12px; margin-bottom: 16px;">
            <h1 style="color: #006b5f; font-size: 20px; margin: 0;">💰 家庭記帳通知：{{ $title }}</h1>
        </div>
        <p style="font-size: 14px; line-height: 1.6; color: #3c4947;">
            {{ $content }}
        </p>

        @if(!empty($details))
            <table style="width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px;">
                @foreach($details as $key => $val)
                    <tr style="border-bottom: 1px solid #f4ede6;">
                        <td style="padding: 8px; font-weight: bold; color: #6c7a77; width: 35%;">{{ $key }}</td>
                        <td style="padding: 8px; color: #1e1b17;">{{ $val }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        <div style="margin-top: 24px; padding-top: 12px; border-top: 1px solid #e8e1db; text-align: center; font-size: 11px; color: #6c7a77;">
            此郵件由 HomeSync Finance 家庭記帳系統自動發送 (Google Mail API / SMTP)
        </div>
    </div>
</body>
</html>
