# GitHub 部署指南（Deployment Guide）

> 把本系統推上 GitHub + 在新主機跑起來的「完整步驟清單」。
> 所有檔案路徑預設為 `C:\xampp1\htdocs\familyaccounting`，請依實際情況調整。

---

## 0. 部署目錄結構（本交付物）

```
D:\project0824\deploy\
├── DEPLOY.md         # 本檔（部署步驟）
├── EXTENSIONS.md     # 套件擴充清單
└── schema.sql        # MariaDB 基底 schema + admin 帳號
```

---

## 1. 上 GitHub 前的清理（本地端）

以下檔案/資料夾「不應」上 GitHub，請先確認 `.gitignore` 已正確覆蓋
（Laravel 預設 `.gitignore` 已處理大部分）：

- `/vendor`              （composer 安裝）
- `/node_modules`        （npm 安裝）
- `/.env`                （每台主機各自一份，**絕對不要 commit**）
- `/public/storage`      （Laravel 公開檔案連結）
- `/storage/*.key`       （APP_KEY 副檔）
- `/storage/app/gmail-oauth-token.json`  （Gmail OAuth2 token）
- `/.idea`、`/.vscode`   （IDE 個人設定）

建議另外加入 `.gitignore` 的幾項（本專案已有，自行檢查）：

```gitignore
/.idea
/.obsidian
/storage/app/gmail-oauth-token.json
/storage/logs/*.log
# regtest_* 測試腳本（開發用，可選）
/regtest_*.php
```

### 1.1 確認沒有把秘密 commit 進去

```powershell
cd C:\xampp1\htdocs\familyaccounting
git log --all --full-history -- .env
git log --all --full-history -- storage/app/gmail-oauth-token.json
# 若有輸出，請用 git filter-branch / BFG 清除
```

---

## 2. Git 操作（本地 → GitHub）

```powershell
cd C:\xampp1\htdocs\familyaccounting

# 首次推送
git init
git branch -M main
git remote add origin https://github.com/<你的帳號>/<repo>.git
git add .
git commit -m "feat: initial commit (HomeSync Finance v1.0)"
git push -u origin main

# 後續更新
git add .
git commit -m "feat: 描述你的變更"
git push
```

**分支策略建議**：
- `main` — 穩定版，永遠可部署
- `develop` — 開發整合分支
- `feature/<name>` — 單一功能分支（PR 進 develop）

---

## 3. 新主機初次部署（從 GitHub clone）

### 3.1 環境需求

- PHP 8.2+（含擴充：`pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `zip`, `intl`）
- MariaDB 10.4+ / MySQL 8.0+
- Composer 2.x
- Node.js 18+ 與 npm 9+
- （生產環境可選）Nginx / Apache + PHP-FPM

詳細套件見 `EXTENSIONS.md`。

### 3.2 Clone 專案

```bash
cd /var/www  # 或 C:\xampp1\htdocs
git clone https://github.com/<你的帳號>/<repo>.git familyaccounting
cd familyaccounting
```

### 3.3 安裝相依套件

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```
### 3.4 設定 `.env`

```bash
cp .env.example .env  # Linux/Mac
# 或 PowerShell：
Copy-Item .env.example .env
```

編輯 `.env`，把以下欄位改成實際值：

```dotenv
APP_NAME=家庭記帳
APP_ENV=production
APP_KEY=                       # ← 待會用 artisan key:generate 產生
APP_DEBUG=false
APP_URL=https://你的網域

APP_LOCALE=zh_TW
APP_AVAILABLE_LOCALES=zh_TW,en,ja
APP_FALLBACK_LOCALE=zh_TW
APP_FAKER_LOCALE=zh_TW
APP_TIMEZONE=UTC

DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306                    # ← 改成你的實際 port（XAMPP 預設 3307）
DB_DATABASE=family_accounting
DB_USERNAME=root
DB_PASSWORD=你的密碼
DB_COLLATION=utf8mb4_unicode_ci

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_PATH=/

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log                 # 預設 log，正式環境改成 gmail-api

# Gmail OAuth2（要寄信才填，否則保持空）
# GMAIL_API_CLIENT_ID=...
# GMAIL_API_CLIENT_SECRET=...
# GMAIL_API_CLIENT_MAIL=...
# GMAIL_API_CLIENT_REFRESH_TOKEN=...
# GMAIL_OAUTH_REDIRECT_URI=https://你的網域/oauth/gmail/callback

LOG_CHANNEL=stack
LOG_LEVEL=info
```

### 3.5 產生 APP_KEY

```bash
php artisan key:generate
```

### 3.6 建立資料庫並匯入 schema

**方式 A — 用 `schema.sql`（推薦，最乾淨）**

```bash
# MariaDB / MySQL CLI
mysql -u root -p < /path/to/schema.sql
```

```powershell
# XAMPP Windows
C:\xampp1\mysql\bin\mysql.exe -u root -p0000 < D:\project0824\deploy\schema.sql
```

或於 phpMyAdmin 匯入 `schema.sql`。

匯入後會看到：

```
+-----------------------------+-------------+------------------+
| status                      | users_count | migrations_count |
+-----------------------------+-------------+------------------+
| ✅ Schema 安裝完成          |           1 |               31 |
+-----------------------------+-------------+------------------+
```

**方式 B — 跑 migrations（如果不想用 schema.sql）**

```bash
php artisan migrate
# 然後手動建立 admin：
php artisan tinker
>>> App\Models\User::create([\
>>>     "name" => "System Administrator",
>>>     "email" => "admin@example.com",
>>>     "account" => "admin",
>>>     "registration_role" => "admin",
>>>     "is_system_admin" => true,
>>>     "email_verified_at" => now(),
>>>     "password" => Hash::make("password"),
>>> ]);
```

### 3.7 設定檔案權限（Linux/Mac）

```bash
# Laravel 需要寫入 storage 與 bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 3.8 優化（生產環境）

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components
php artisan storage:link
```
### 3.9 啟動 web server

**XAMPP**：把 `familyaccounting` 資料夾放在 `htdocs` 下，
瀏覽 `http://localhost/familyaccounting/public/`

**artisan serve（開發）**：

```bash
php artisan serve --host=0.0.0.0 --port=8000
# 瀏覽 http://localhost:8000
```

**Nginx + PHP-FPM（生產，推薦）**：

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /var/www/familyaccounting/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 3.10 註冊排程器（Cron / Task Scheduler）

每日 09:00 預算超支檢查 + 09:30 訂閱提醒 — 必須讓 cron 每分鐘跑一次：

```bash
crontab -e
# 加入：
* * * * * cd /var/www/familyaccounting && php artisan schedule:run >> /dev/null 2>&1
```

---

## 4. 部署後檢查清單

- [ ] `https://你的網域/` 首頁可開啟
- [ ] `https://你的網域/admin` 後台登入頁可開啟（Filament）
- [ ] 用 `admin@example.com` / `password` 登入成功
- [ ] `users` 表只有 1 筆（管理員）
- [ ] `families` / `accounts` / `categories` / `transactions` 等業務表都是空的
- [ ] 切換語系（`?lang=en`）正常運作
- [ ] 深色模式切換正常
- [ ] 若啟用 Gmail：寄一封測試信確認（`php artisan tinker` → `Mail::raw(...)`)

---

## 5. 還原（Recovery）

若部署後出問題，三招還原：

1. **重新跑 schema.sql**：
   ```bash
   mysql -u root -p -e "DROP DATABASE family_accounting;"
   mysql -u root -p < schema.sql
   ```
2. **重置 APP_KEY**（強制所有 session/加密失效）：
   ```bash
   php artisan key:generate
   php artisan config:clear
   ```
3. **清快取**：
   ```bash
   php artisan optimize:clear   # 同時清 config / route / view / event
   ```
