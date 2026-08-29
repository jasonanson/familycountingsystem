# 套件擴充清單（Deployment Extensions）

> 本檔列出「在全新環境部署本系統時，必須額外安裝的套件」。
> 凡是 Laravel / Filament 透過 composer / npm 自動帶入的**遞移相依**套件
> （例如 symfony/*、guzzlehttp/*、spatie/*）**不在此列**，直接
> `composer install` / `npm install` 即可取得。

---

## 1. 後端 PHP 套件（Composer）

### 1.1 已寫入 composer.json 的直接相依（直接 composer install 即可）

| 套件 | 版本 | 用途 |
|------|------|------|
| php | ^8.2 | PHP 執行環境需求 |
| laravel/framework | ^11.0 | Laravel 11 主框架 |
| laravel/tinker | ^2.9 | REPL 工具（artisan tinker） |
| filament/filament | 3.3 | 後台管理介面（/admin 路由） |

### 1.2 dev 相依（測試 / 重構用）

| 套件 | 版本 | 用途 |
|------|------|------|
| fakerphp/faker | ^1.23 | 假資料產生 |
| laravel/pint | ^1.13 | 程式碼風格自動修正 |
| laravel/sail | ^1.26 | Docker 開發環境（可選） |
| mockery/mockery | ^1.6 | 測試 mock 框架 |
| nunomaduro/collision | ^8.0 | PHPUnit 漂亮錯誤輸出 |
| phpunit/phpunit | ^10.5 | 單元測試 |
| spatie/laravel-ignition | ^2.4 | 開發期錯誤頁 |
### 1.3 額外建議安裝（**未寫入 composer.json**，未來若啟用對應功能再裝）

| 套件 | 版本 | 用途 | 觸發時機 |
|------|------|------|----------|
| barryvdh/laravel-dompdf | ^2.x | PDF 匯出（取代內建瀏覽器列印） | 切換 config/pdf.php 的 driver 為 dompdf 時 |
| maatwebsite/excel | ^3.1 | 更完整的 Excel 匯入匯出 | 取代目前的 OpenSpout CSV 流程時 |

> ⚠️ 本專案的 PDF 預設驅動為 browser（config/pdf.php），**不裝 dompdf 也能跑**。
> 若要啟用真正的 PDF 檔輸出，再裝 barryvdh/laravel-dompdf。

### 1.4 安裝指令範例（全新環境）

```powershell
cd C:\xampp1\htdocs\familyaccounting
C:\xampp1\php\php.exe composer install --no-dev --optimize-autoloader
# 含 dev 套件（測試 / 開發用）
C:\xampp1\php\php.exe composer install
```

---

## 2. 前端 Node.js 套件（NPM）

### 2.1 已寫入 package.json 的相依

| 套件 | 版本 | 用途 |
|------|------|------|
| alpinejs | ^3.16.3 | 互動元件（多家庭切換、深色模式、即時通知） |
| material-symbols | ^0.47.0 | Google Material Symbols 圖示字型 |
| axios | ^1.6.4 | HTTP client |
| @tailwindcss/container-queries | ^0.1.1 | Tailwind 容器查詢 |
| @tailwindcss/forms | ^0.5.11 | Tailwind 表單樣式 |
| autoprefixer | ^10.5.4 | CSS 前綴自動補 |
| laravel-vite-plugin | ^1.0 | Vite 與 Laravel 整合 |
| postcss | ^8.5.26 | CSS 後處理 |
| tailwindcss | ^3.4.17 | Utility-first CSS |
| vite | ^5.0 | 前端建置工具 |

### 2.2 安裝指令

```powershell
cd C:\xampp1\htdocs\familyaccounting
npm install
npm run build      # 生產環境打包
# 或
npm run dev        # 開發模式（hot reload）
```
---

## 3. PHP 擴充需求（php.ini 必須啟用）

```powershell
php -m
```

需看到以下擴充：

| 擴充 | 用途 |
|------|------|
| pdo_mysql | 連線 MariaDB / MySQL |
| mbstring | 多位元組字串處理（中文必備） |
| openssl | 加密 / Gmail OAuth2 HTTPS 連線 |
| tokenizer | Laravel 字彙解析 |
| xml / dom / xmlwriter | 設定檔 / 套件需求 |
| ctype / fileinfo / filter / hash / json | Laravel 基本需求 |
| bcmath | 金額計算（推薦） |
| gd 或 imagick | 附件縮圖（推薦） |
| curl | HTTP client（Gmail API） |
| zip | composer / 套件安裝 |
| intl | Filament 數字格式化（缺則會自動 polyfill） |

XAMPP 預設路徑：C:\xampp1\php\php.ini

---

## 4. 外部服務（選用，依部署需求決定）

### 4.1 MariaDB / MySQL

- 本機：XAMPP 內建（預設 port 3307）
- 雲端：PlanetScale / AWS RDS / DigitalOcean Managed Database / 自架
- 最低版本：MariaDB 10.4 或 MySQL 8.0

### 4.2 Gmail API OAuth2（寄信用，選用）

本系統**預設不寄信也能跑**（通知全走站內 system 頻道）。
若要啟用 Email 通知，需準備：

1. Google Cloud Console 建立 OAuth 2.0 Client ID
2. 設定 Authorized redirect URI：
   `http://你的網域/oauth/gmail/callback`
3. 把 `client_id` / `client_secret` / `refresh_token` / `email` 寫進 `.env`
   （見 `docs/EMAIL_SETUP.md`）

### 4.3 排程器（Cron / Task Scheduler）

若需要每日預算超支 / 訂閱提醒，需註冊 cron：

```cron
* * * * * cd /path/to/familyaccounting && php artisan schedule:run >> /dev/null 2>&1
```

或 Windows 工作排程器（每分鐘）：

```
程式: C:\xampp1\php\php.exe
引數: C:\xampp1\htdocs\familyaccounting\artisan schedule:run
```
---

## 5. 不需要裝的東西（明確排除）

| 不需要 | 原因 |
|--------|------|
| iagofelicio/laravel-gmail-oauth2 | 已退場，改走自訂 GmailApiTransport |
| phpmailer/phpmailer | 同上，改用 Gmail REST API（gmail.googleapis.com） |
| sentry/sentry-laravel | 沒有裝（錯誤走 Laravel log） |
| laravel/socialite | 沒有裝（OAuth 走自訂 controller） |
| laravel/horizon | 沒有裝（queue 走 database driver） |
| laravel/scout | 沒有裝（搜尋走 SQL LIKE） |
| Redis / Memcached | 沒有裝（cache / session / queue 全走 database driver） |
| Discord Webhook | 已於 2026-08-26 棄置 |
