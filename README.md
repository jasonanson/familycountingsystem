<div align="center">

# 🏡 HomeSync Finance — 家庭記帳與智慧財務管理系統

**專為家庭打造的現代化、多角色、AI 賦能家庭記帳與資產管理平台**

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Gemini AI](https://img.shields.io/badge/Google_Gemini-3.5_Flash_Lite-4285F4?style=flat-square&logo=google&logoColor=white)](https://ai.google.dev/)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

[功能特色](#-核心特色) • [系統需求](#-環境需求) • [快速開始](#-快速安裝與建置) • [預設帳號](#-預設登入資訊) • [AI 與郵件設定](#-進階整合設定) • [伺服器配置](#-伺服器部署說明)

</div>

---

## 📖 專案簡介

**HomeSync Finance** 是一套專為現代家庭設計的開源智慧記帳系統。系統以「家庭」為核心單位，兼顧家長集中管理與子女財務教育需求，並結合 **Google Gemini 3.5 AI** 智慧財務分析與 **Gmail OAuth 2.0** 郵件推播通知，提供完整且直覺的家庭財務解決方案。

---

## ✨ 核心特色

### 👨‍👩‍👧‍👦 1. 完整多家庭架構與分級權限
* **多家庭切換**：使用者可同時加入多個家庭（如原生家庭、小家庭），隨時無縫切換作用中家庭。
* **分眾角色權限**：
  * **系統管理員 (Admin)**：全站使用者管理、系統 AI 設定、Gmail 串接、資料庫備份還原。
  * **家長 (Parent)**：家庭主理人，可審批交易、設定預算、管理子女零用錢與支出上限、邀請成員。
  * **子女 (Child)**：專屬簡化「兒童錢包」視圖、零用錢目標、自訂支出申請與消費額度限制。

### 💰 2. 靈活的帳戶與收支管理
* **多帳戶類型**：支援現金、銀行帳戶、信用卡、電子支付（LINE Pay / 街口 / 悠遊卡等）。
* **階層式分類**：內建豐富的預設收支分類，支援家庭自訂多層級子分類與色票圖標。
* **多維度檢視**：提供流水帳列表、日曆檢視模式、週期性固定收支排程。

### 📊 3. 智慧預算與定期訂閱管理
* **預算超支即時預警**：月度/分類預算進度條，接近上限 (80%) 或超支 (100%+) 自動發送站內與 Email 警示通知。
* **訂閱服務追蹤 (Subscriptions)**：追蹤串流平台、雲端軟體等定期扣款，扣款日前自動提醒。

### 🤖 4. Google Gemini 3.5 AI 財務顧問
* **AI 智慧收支月報分析**：由 Gemini 3.5 模型自動彙整全家月度收支，產出財務健康評分與專業節流建議。
* **一鍵全體家長廣播**：產生 AI 財務分析後，系統支援一鍵同步推播站內通知與寄送 Email 給全體家長。

### ✉️ 5. Gmail API OAuth 2.0 系統郵件推播
* **OAuth 2.0 安全驗證**：後台提供直覺介面，上傳 Google OAuth 憑證即可一鍵授權串接 Gmail API，無需使用不安全的應用程式密碼。
* **通知類型齊全**：家庭邀請信、預算超支告警、訂閱扣款提醒、AI 財務月報廣播。

### 🔐 6. 企業級安全與 2FA 雙層驗證
* **TOTP 雙因素驗證**：支援 Google Authenticator 等標準 2FA App，並提供緊急備用還原碼。
* **細緻審計日誌 (Audit Log)**：完整記錄關鍵操作（登入、權限變更、大額異動）。

### 📱 7. 現代化 UI / UX 與 PWA
* **響應式體驗**：手機、平板、桌面端完美適配。
* **深色模式 (Dark Mode)**：支援 Auto / Light / Dark 自由切換。
* **PWA 支援**：可直接安裝至手機桌面，如同原生 App 般流暢操作。
* **報表匯出**：支援專業 PDF 月度財務報表與 CSV 資料匯出。

---

## 🛠️ 環境需求

在開始安裝前，請確認您的伺服器或本機開發環境符合以下需求：

| 元件 | 最低版本需求 | 備註 |
| :--- | :--- | :--- |
| **PHP** | `>= 8.2.0` | 需啟用 `openssl`, `pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `gd` 或 `imagick`, `bcmath` |
| **資料庫** | **MariaDB `>= 10.4`** 或 **MySQL `>= 8.0`** | 字元集推薦 `utf8mb4` |
| **Composer** | `>= 2.2.0` | PHP 套件依賴管理工具 |
| **Node.js & NPM** | `>= 18.0.0` (選用) | 前端編譯工具（專案已預先內建編譯好的靜態資源） |
| **Web 伺服器** | **Apache** 或 **Nginx** | 需啟用 `mod_rewrite` 模組 |

---

## 🚀 快速安裝與建置

### 步驟 1：下載或 Clone 專案
```bash
git clone https://github.com/jasonanson/familycountingsystem.git familyaccounting
cd familyaccounting
```

### 步驟 2：安裝後端 PHP 依賴套件
```bash
composer install --no-dev --optimize-autoloader
```
*(若為本機開發除錯，可直接執行 `composer install`)*

### 步驟 3：設定環境變數檔 (.env)
複製 `.env.example` 為 `.env`：
```bash
# Windows PowerShell
Copy-Item .env.example .env

# Linux / macOS
cp .env.example .env
```

編輯 `.env` 檔案，配置您的資料庫連線與站點網址：
```env
APP_NAME="HomeSync Finance"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/familyaccounting

# 資料庫連線配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=family_accounting
DB_USERNAME=root
DB_PASSWORD=
```

### 步驟 4：產生應用程式金鑰 (APP_KEY)
```bash
php artisan key:generate
```

### 步驟 5：建立資料庫與執行遷移 (Migration & Seed)
請先在 MySQL / MariaDB 中建立對應的資料庫（例如 `family_accounting`）：
```sql
CREATE DATABASE IF NOT EXISTS `family_accounting` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

接著執行資料庫遷移與初始資料植入：
```bash
php artisan migrate --seed
```
*(此指令會自動建立所有資料表，並初始化預設分類與管理員帳號)*

> 💡 **替代方案**：您也可以直接將 `deploy/schema.sql` 匯入至您的 MySQL / phpMyAdmin 資料庫中完成初始建置。

### 步驟 6：（選用）編譯前端資源
本專案已在 `public/build/` 中內建編譯好的 CSS 與 JS 檔案。若需要自行客製化樣式，可執行：
```bash
npm install
npm run build
```

### 步驟 7：啟動應用程式
* **使用 Laravel 內建開發伺服器**：
  ```bash
  php artisan serve
  ```
  瀏覽器開啟：`http://localhost:8000`

* **使用 XAMPP / WAMP 環境**：
  將專案資料夾放置於 `htdocs/familyaccounting`，瀏覽器直接開啟：
  `http://localhost/familyaccounting`

---

## 🔑 預設登入資訊

系統安裝並執行 Seed 後，提供一組預設的系統管理員帳號：

| 欄位 | 預設值 |
| :--- | :--- |
| **登入網址** | `http://localhost/familyaccounting/login` |
| **管理員帳號** | `admin` |
| **管理員密碼** | `admin` |
| **預設角色** | `系統管理員 (System Admin)` |

> ⚠️ **安全建議**：首次登入後，請務必至「個人設定」修改密碼，並建議啟用 Google Authenticator 雙層驗證 (2FA) 以保障系統安全。

---

## ⚙️ 進階整合設定

### 1. Google Gemini AI 智慧顧問設定
1. 前往 [Google AI Studio](https://aistudio.google.com/) 取得免費 API Key。
2. 透過以下兩種方式之一完成設定：
   * **方式 A（推薦）**：以管理員登入系統 ➡️ 點擊左側「系統管理」➡️「AI 設定」➡️ 輸入 API Key 並測試連線儲存。
   * **方式 B**：在 `.env` 中填入：
     ```env
     GEMINI_API_KEY=AIzaSyYourActualKeyHere
     GEMINI_MODEL=gemini-2.5-flash-lite
     ```

### 2. Gmail OAuth 2.0 系統郵件推播設定
1. 前往 [Google Cloud Console](https://console.cloud.google.com/) 建立專案並啟用 **Gmail API**。
2. 建立 **OAuth 2.0 用戶端 ID**（應用程式類型選擇「網路應用程式」），新增重新導向 URI：
   ```
   http://localhost/familyaccounting/admin/gmail/callback
   ```
3. 下載憑證 JSON 檔案。
4. 以管理員登入系統 ➡️ 點擊「系統管理」➡️「Gmail 整合」➡️ 上傳憑證 JSON（或貼上 Client ID 與 Client Secret）➡️ 點擊「立即授權連線」完成 Google 帳號綁定。

---

## 🌐 伺服器部署說明

### Apache / XAMPP 設定
專案 `public/.htaccess` 已內建完整的 URL 重寫規則與瀏覽器靜態資源快取標頭。
請確認 Apache 的 `httpd.conf` 已啟用以下模組：
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
LoadModule expires_module modules/mod_expires.so
```
並確保 `DocumentRoot` 目錄設定了 `AllowOverride All`。

### Nginx 設定範例
```nginx
server {
    listen 80;
    server_name family.example.com;
    root /var/www/familyaccounting/public;

    index index.php index.html;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 📁 專案目錄結構

```text
familyaccounting/
├── app/
│   ├── Http/
│   │   ├── Controllers/     # 核心控制器（收支、預算、會員、AI、Gmail、2FA）
│   │   └── Middleware/      # 中介層（身分驗證、家庭作用域隔離、權限檢查）
│   ├── Models/              # Eloquent 資料模型與關聯定義
│   └── Services/            # 商業邏輯服務（Gemini AI、Gmail OAuth、TOTP 2FA）
├── bootstrap/               # 框架啟動設定
├── config/                  # 應用程式配置
├── database/
│   ├── migrations/          # 資料庫結構遷移檔
│   └── seeders/             # 初始資料與預設分類填充
├── deploy/                  # 部署說明文件與完整 schema.sql
├── docs/                    # 系統架構與說明文件
├── lang/                    # 多國語系翻譯檔 (zh_TW, en, ja)
├── public/                  # Web 根目錄（靜態資源、PWA Manifest、.htaccess）
│   └── build/               # 編譯後之 CSS / JS 前端資源
├── resources/
│   └── views/               # Blade 視圖模板與 UI 元件
├── routes/
│   └── web.php              # Web 路由定義
├── storage/                 # 框架快取、檔案上傳與日誌儲存
└── tests/                   # 功能與單元測試套件
```

---

## 🔒 資安與隱私防護

* 🛡️ **純淨開源承諾**：本倉庫不包含任何真實使用者的個人隱私數據、測試 Cookie、連線 Token 或私人 API Key。
* 🔐 **密碼雜湊**：所有使用者密碼皆採用嚴格的 `bcrypt` 演算法（cost 12）進行單向加鹽雜湊。
* 🛡️ **CSRF & XSS 防護**：全面啟用 Laravel 內建 CSRF Token 防護與 Blade 自動轉義機制。

---

## 📄 開源授權協議 (License)

本專案採用 [MIT License](LICENSE) 開源授權，歡迎自由使用、修改與分享、禁止商用。

<div align="center">
  <sub>Made with ❤️ for smart family financial management.</sub>
</div>
