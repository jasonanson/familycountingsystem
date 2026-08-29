# 功能狀態與決策紀錄

最後更新：2026-08-27

---

## 🎨 Google Stitch 設計色票規格

本系統的色彩嚴格遵循 Google Stitch Design System 規範，**集中定義在 `resources/views/layouts/app.blade.php` 的 `tailwind.config`** 中。
任何頁面直接使用 utility classes（如 `bg-primary`、`bg-success`、`text-danger`），不要在 view 內硬編 hex。

### 1. 主要品牌色（Primary / Teal）
| Token | 值 | 用途 |
|-------|-----|------|
| `primary` | `#006b5f` | 主品牌色（深 teal） |
| `primary-container` | `#14b8a6` | 容器強調色 |
| `primary-10/15/20` | `#006b5f` 1a/26/33 透明度 | 背景疊加 |
| `on-primary` | `#ffffff` | 在 primary 上的文字 |

### 2. Surface 表面色階（Stone 暖灰系列）
| Token | 值 | 用途 |
|-------|-----|------|
| `surface` / `surface-bright` | `#fafaf9` | 預設頁面背景 |
| `surface-pure` | `#ffffff` | 卡片背景 |
| `surface-dim` | `#0c0a09` | 深色模式背景 |
| `surface-container-low` | `#fafaf9` | 最低容器 |
| `surface-container` | `#f5f5f4` | 一般容器 |
| `surface-container-high/highest` | `#e7e5e4` | 高亮容器 |
| `surface-variant` | `#e7e5e4` | 容器變體 |
| `on-surface` | `#1c1917` | 主要文字 |
| `on-surface-variant` | `#57534e` | 次要文字 |
| `background-warm` | `#fafaf9` | 全頁底色 |
| `border-base` | `#e7e5e4` | 全站邊框線 |

### 3. 狀態色
| Token | 值 | 用途 |
|-------|-----|------|
| `success` / `success-container` | `#10b981` / `#d1fae5` | 成功訊息 |
| `danger` / `danger-container` | `#ef4444` / `#fee2e2` | 危險 / 錯誤 |
| `warning` / `warning-container` | `#f59e0b` / `#fef3c7` | 警示 |
| `error` / `error-container` | `#ba1a1a` / `#ffdad6` | M3 錯誤色 |

### 4. Google Stitch 8 色分類色票
對應 `app/Http/Controllers/ReportController.php` 的 `$stitchCategories`，全站統一使用：

| 分類 | Token | 值 | 對應支出類別 |
|------|-------|-----|-------------|
| 餐飲 | `category-rose` | `#FB7185` | 餐飲食品 |
| 交通 | `category-sky` | `#60A5FA` | 交通出行 |
| 居家 | `category-mint` | `#34D399` | 居住家居 |
| 醫療 | `category-pink` | `#F472B6` | 醫療保健 |
| 教育 | `category-lavender` | `#A78BFA` | 育兒教育 |
| 娛樂 | `category-amber` | `#FBBF24` | 休閒娛樂 |
| 購物 | `category-orange` | `#F97316` | 購物消費 |
| 其他 | `category-slate` | `#94A3B8` | 其他 / 未分類 |

### 5. 使用方式（嚴格規定）

✅ **正確範例**：
```blade
<div class="bg-primary text-on-primary rounded-2xl p-6">
    <span class="bg-category-mint text-category-mint">居家</span>
</div>
```

❌ **禁止**：
```blade
<div style="background-color: #006b5f;">不要用 inline style</div>
<div class="bg-[#006b5f]">不要用 arbitrary value，請用 bg-primary</div>
```

### 6. 變更流程

若要新增自訂色票：
1. 修改 `resources/views/layouts/app.blade.php` 的 `tailwind.config.theme.extend.colors`
2. 加上清楚的中文註解說明用途
3. 同步更新 `docs/STATUS.md` 此章節
4. 跑 `php regtest_ui_tailwind_color_tokens.php` 驗證


---

## ✅ 已實作 (2026-08-27 新增 P2/P3 項目)

| 功能 | 狀態 | 備註 |
|------|------|------|
| **預算超支 / 訂閱提醒排程觸發** | ✅ | `budgets:check-overspend` + `subscriptions:remind` 兩支 artisan 命令，已註冊到 Console Kernel；每日 09:00 / 09:30 (Asia/Taipei) 自動執行；含 7 天 dedup_key 防重複寄送 |
| **[P2] PDF 月報 / 預算報告** | ✅ | 介面化 `PdfRendererInterface`，預設走 `BrowserPrintPdfRenderer`（瀏覽器內建「列印成 PDF」），`DomPdfRenderer` 介面已預留；網路解封後 `composer require barryvdh/laravel-dompdf` 並改 config 即切換 |
| **[P2] 兩步驟驗證 (2FA)** | ✅ | 純 PHP 實作 TOTP (RFC 6238，HMAC-SHA1)，通過全部標準測試向量；含啟用 / 確認 / 關閉 / 重新產生恢復碼 / OTP 驗證 / 恢復碼消耗 / reuseLock 防重放 |
| **[P2] 深色模式** | ✅ | 三段式切換：auto / light / dark；localStorage 持久化；`window.HomeSyncTheme.cycle()` 與 system preference 自動跟隨；layout 已加入切換按鈕 |
| **[P3] 多家庭切換 UI** | ✅ | Alpine.js dropdown 取代原生 `<select>`，自動顯示「系統檢視」標記；當前家庭顯示 check icon；同 session 仍走 `family.switch` POST |
| **[P3] 管理員使用者排序強化** | ✅ | 多欄位可排序（name / email / is_system_admin / created_at），白名單防 SQL injection；排序狀態保留其他篩選條件 |
| **[P3] 兒童專屬簡化視圖** | ✅ | `ChildDashboardController` + `dashboard.child` view；大字體 + 大按鈕 + emoji；自動從 `/dashboard` 分流；資料完全隔離（只顯示自己的消費） |
| **[P3] 備份還原** | ✅ | `db:backup` (mysqldump) + `db:restore` (mysql import)；含 `--list` / `--retention=7` / `--compress`；還原前自動 safety_backup 備份 |
| **[P3] 測試覆蓋率** | ✅ | 8 個 regtest 腳本，總耗時 25 秒，0 失敗 |

---

## 📊 測試覆蓋總覽

`C:\xampp1\php\php.exe run_all_regtests.php`

```
✅ unit_twopfactor_totp              PASS (7.24s)
✅ regtest_p2_scheduled_reminders    PASS (0.88s)
✅ regtest_p2_pdf_export             PASS (0.64s)
✅ regtest_p2_2fa                    PASS (10.01s)
✅ regtest_p2_dark_mode              PASS (0.46s)
✅ regtest_p3_admin_user_sort        PASS (1.20s)
✅ regtest_p3_child_dashboard        PASS (0.92s)
✅ regtest_p3_db_backup_restore      PASS (3.96s)
總計：8 通過 / 0 失敗 / 25.32s
```

---

## 📁 新增 / 修改檔案總覽

### 新增
- `app/Console/Commands/BudgetOverspendCheck.php` — 預算超支掃描
- `app/Console/Commands/SubscriptionReminder.php` — 訂閱提醒
- `app/Console/Commands/DatabaseBackup.php` — DB 備份
- `app/Console/Commands/DatabaseRestore.php` — DB 還原
- `app/Http/Controllers/PdfExportController.php` — PDF 月報匯出
- `app/Http/Controllers/TwoFactorController.php` — 2FA Controller
- `app/Http/Controllers/ChildDashboardController.php` — 兒童儀表板
- `app/Services/Pdf/PdfRendererInterface.php` — PDF 介面
- `app/Services/Pdf/BrowserPrintPdfRenderer.php` — 瀏覽器列印實作
- `app/Services/Pdf/DomPdfRenderer.php` — DomPDF 預留實作
- `app/Services/TwoFactor/TotpService.php` — 純 PHP TOTP
- `app/Providers/PdfServiceProvider.php` — PDF 服務提供者
- `app/Http/Controllers/PdfExportController.php`
- `resources/views/reports/pdf/layout.blade.php`
- `resources/views/reports/pdf/monthly.blade.php`
- `resources/views/reports/pdf/budget.blade.php`
- `resources/views/settings/two_factor.blade.php`
- `resources/views/auth/2fa/verify.blade.php`
- `resources/views/dashboard/child.blade.php`
- `config/pdf.php` — PDF Driver 設定
- `database/migrations/2026_08_27_010000_add_2fa_fields_to_users_table.php`
- `database/migrations/2026_08_27_011500_extend_two_factor_secret_column.php`

### 新增測試
- `unit_twopfactor_totp.php` — RFC 6238 測試向量
- `regtest_p2_scheduled_reminders.php`
- `regtest_p2_pdf_export.php`
- `regtest_p2_2fa.php`
- `regtest_p2_dark_mode.php`
- `regtest_p3_admin_user_sort.php`
- `regtest_p3_child_dashboard.php`
- `regtest_p3_db_backup_restore.php`
- `run_all_regtests.php` — 整合入口

### 修改
- `routes/console.php` — 註冊兩個排程任務
- `routes/web.php` — 註冊 2FA / PDF / 兒童儀表板路由
- `app/Http/Controllers/AuthController.php` — 登入流程串接 2FA
- `app/Http/Controllers/DashboardController.php` — 兒童角色自動分流
- `app/Http/Controllers/AdminUserController.php` — 多欄位排序
- `app/Models/User.php` — 2FA 欄位 casts
- `resources/views/layouts/app.blade.php` — 主題管理 + 2FA 連結 + 家庭切換 dropdown + 主題切換按鈕
- `resources/views/admin/users/index.blade.php` — 排序欄位標題
- `resources/views/reports/monthly.blade.php` — PDF 按鈕

---

## 環境變數

```dotenv
# PDF Driver 設定
PDF_DRIVER=browser       # browser | dompdf
PDF_PAPER_SIZE=A4
PDF_PAPER_ORIENTATION=portrait
```

---

## 排程時段（Asia/Taipei）

| 指令 | 時段 | 用途 |
|------|------|------|
| `budgets:check-overspend` | 每日 09:00 | 掃描所有家庭，超支 / 警示自動通知 |
| `subscriptions:remind` | 每日 09:30 | 提前 1/3/7 天提醒訂閱扣款 |
| `inspire` | 每小時 | Laravel 預設（無害） |

排程觸發：`php artisan schedule:run`（Production 需註冊到 OS cron / Task Scheduler）
