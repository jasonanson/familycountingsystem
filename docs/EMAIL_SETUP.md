# Email SMTP / Gmail OAuth2 設定指南

家庭記帳系統寄信走 **Gmail REST API + OAuth2**（自訂 transport `App\Mail\Transport\GmailApiTransport`），
不需要儲存 Gmail 密碼，授權一次就能永久寄信。

## 為什麼不用 SMTP？

本機網路環境封鎖 SMTP port 465/587，但 HTTPS port 443 通，
所以改走 Gmail REST API（`gmail.users.messages.send`）。
所有 SMTP 相關套件（iagofelicio/laravel-gmail-oauth2、PHPMailer）已退場，
目前只有 `league/oauth2-google` 留著提供 `Google` OAuth provider 給 OAuth controller 使用。

---

## 1. 一次性 OAuth 授權流程

### 1.1 確認 Google Cloud Console 的 Authorized redirect URI

登入 [Google Cloud Console → APIs & Services → Credentials](https://console.cloud.google.com/apis/credentials)，
找到對應的 OAuth 2.0 Client ID（client_id 是 `671898036015-...`），
按下「Edit OAuth client」，
在 **Authorized redirect URIs** 加入以下 URL：

```
http://localhost/familyaccounting/public/oauth/gmail/callback
```

> 註：`credentials.json` 預設的 redirect 是 `http://localhost/oauth2callback`，
> 那個不會被我們用到，所以加上面那條就好，不要刪舊的。

### 1.2 觸發授權

開瀏覽器訪問：

```
http://localhost/familyaccounting/public/oauth/gmail
```

會跳轉到 Google 登入畫面。登入你要用來寄信的 Gmail 帳號，同意授權。

### 1.3 取得 refresh_token

授權完成後 Google 會 redirect 回 `/oauth/gmail/callback`，
控制器會：

1. 用 authorization code 換 `access_token` + `refresh_token`
2. 寫到 `storage/app/gmail-oauth-token.json`
3. **順手把 `refresh_token` 與 `email` 寫進 `.env`**
4. 顯示成功頁（家長把 refresh_token 抄起來備用）

### 1.4 把 MAIL_MAILER 改成 gmail

`.env`：

```dotenv
MAIL_MAILER=gmail
```

---

## 2. .env 完整金鑰

```dotenv
# === Mail ===
MAIL_MAILER=gmail

# === Gmail OAuth2 ===
GMAIL_API_CLIENT_ID=<your-client-id>.apps.googleusercontent.com
GMAIL_API_CLIENT_SECRET=<your-client-secret>
GMAIL_API_CLIENT_MAIL=<authorized-gmail-address>
GMAIL_API_CLIENT_REFRESH_TOKEN=<obtained-from-oauth-flow>
GMAIL_OAUTH_REDIRECT_URI=http://localhost/familyaccounting/public/oauth/gmail/callback
```

---

## 3. 驗證寄信

### 3.1 從網頁 UI 觸發

到 `/members/invite-child` 或 `/invitations`，輸入 email 發邀請，
確認對方信箱收到正式信件。

### 3.2 從 CLI 觸發

```powershell
cd C:\xampp1\htdocs\familyaccounting
C:\xampp1\php\php.exe regtest_email_send.php
```

### 3.3 從 Tinker 觸發

```powershell
C:\xampp1\php\php.exe artisan tinker
```

```php
Mail::raw('Hello from HomeSync Finance!', function ($m) {
    $m->to('your-email@example.com')->subject('Test');
});
```

---

## 4. 退回方案（不建議，僅 debug 用）

若只想驗證 mailable 流程但不想真的寄信，把 `.env` 改成：

```dotenv
MAIL_MAILER=log
```

所有信件會寫到 `storage/logs/laravel.log`。

或用 `array` driver（純記憶體，常用於測試）：

```dotenv
MAIL_MAILER=array
```

---

## 5. 疑難排解

| 症狀 | 解法 |
|------|------|
| `redirect_uri_mismatch` | Google Cloud Console 的 Authorized redirect URI 沒有 `http://localhost/familyaccounting/public/oauth/gmail/callback` |
| 拿到 code 但 callback 顯示 `invalid_grant` | authorization code 只能用一次，且 10 分鐘內有效。重新從 `/oauth/gmail` 走一次。 |
| 拿到 refresh_token 但寄信失敗 | 確認 `.env` 的 `GMAIL_API_CLIENT_MAIL` 是授權的那個 Gmail，不是別的 |
| 想撤銷 refresh token | 到 [Google 帳戶 → 第三方應用程式](https://myaccount.google.com/permissions) 移除授權，然後重新走 `/oauth/gmail` |
| `MAIL_MAILER=gmail` 還是沒寄出 | `php artisan config:clear` 然後重啟 web server |

---

## 6. 套件資訊

| 套件 | 版本 | 用途 |
|------|------|------|
| league/oauth2-google | ^4.2 | OAuth2 controller 取得 refresh_token 用 |

自訂程式碼：
| 檔案 | 用途 |
|------|------|
| `app/Mail/Transport/GmailApiTransport.php` | Symfony Mailer transport，呼叫 Gmail REST API |
| `app/Providers/GmailApiTransportServiceProvider.php` | 註冊 `gmail-api` mailer |
| `app/Http/Controllers/GmailOAuthController.php` | `/oauth/gmail` 一次性授權 |

> 註：SMTP 套件（iagofelicio/laravel-gmail-oauth2、phpmailer）已從 composer.json 移除。
> 改走 HTTPS port 443（gmail.googleapis.com），不受 SMTP port 限制。