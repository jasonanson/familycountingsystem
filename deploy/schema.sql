-- =====================================================================
-- HomeSync Finance / 家庭記帳 — MariaDB 基底資料庫腳本
-- ---------------------------------------------------------------------
-- 用途：在全新的 MariaDB / MySQL 執行個體建立本系統所需的全部資料表，
--       並植入唯一一筆系統管理員帳號 (admin / admin)。
--
-- 重要：本檔「不會」異動任何已存在的資料庫（皆使用 IF NOT EXISTS 與
--       嚴格的 INSERT 條件）；若資料表已存在則略過建立動作。
--
-- 適用環境：
--   - MariaDB 10.4 以上（XAMPP 預設內建）
--   - MySQL 8.0 以上（語法相容）
--   - 字元集：utf8mb4，校對：utf8mb4_unicode_ci（與 .env 的
--     DB_COLLATION 對齊）
--
-- 執行方式（XAMPP 範例）：
--   C:\xampp1\mysql\bin\mysql.exe -u root -p0000 < schema.sql
--   或在 phpMyAdmin 匯入 schema.sql
--
-- 對應的 .env 設定（務必與本檔的 DB_DATABASE 名稱一致）：
--   DB_CONNECTION=mariadb
--   DB_HOST=127.0.0.1
--   DB_PORT=3307
--   DB_DATABASE=family_accounting
--   DB_USERNAME=root
--   DB_PASSWORD=0000
--   DB_COLLATION=utf8mb4_unicode_ci
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `family_accounting`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `family_accounting`;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 1) users（含所有後續新增欄位）
-- =====================================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`                          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                        VARCHAR(255) NOT NULL,
    `email`                       VARCHAR(255) NOT NULL,
    `account`                     VARCHAR(255) NULL,
    `registration_role`           ENUM('admin','parent','member','child','guest') NULL,
    `phone`                       VARCHAR(255) NULL,
    `avatar_url`                  VARCHAR(255) NULL,
    `two_factor_secret`           TEXT NULL,
    `two_factor_enabled`          TINYINT(1) NOT NULL DEFAULT 0,
    `two_factor_confirmed_at`     TIMESTAMP NULL,
    `two_factor_recovery_codes`   JSON NULL,
    `failed_login_count`          INT NOT NULL DEFAULT 0,
    `locked_until`                TIMESTAMP NULL,
    `is_system_admin`             TINYINT(1) NOT NULL DEFAULT 0,
    `current_family_id`           BIGINT UNSIGNED NULL,
    `notification_preferences`    JSON NULL,
    `locale`                      VARCHAR(8) NULL COMMENT '使用者偏好語系',
    `email_verified_at`           TIMESTAMP NULL,
    `password`                    VARCHAR(255) NOT NULL,
    `remember_token`              VARCHAR(100) NULL,
    `created_at`                  TIMESTAMP NULL,
    `updated_at`                  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_account_unique` (`account`),
    KEY `users_current_family_id_index` (`current_family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2) password_reset_tokens / sessions
-- =====================================================================
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `email`       VARCHAR(255) NOT NULL,
    `token`       VARCHAR(255) NOT NULL,
    `created_at`  TIMESTAMP NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sessions` (
    `id`            VARCHAR(255) NOT NULL,
    `user_id`       BIGINT UNSIGNED NULL,
    `ip_address`    VARCHAR(45) NULL,
    `user_agent`    TEXT NULL,
    `payload`       LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    PRIMARY KEY (`id`),
    KEY `sessions_user_id_index` (`user_id`),
    KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3) cache / cache_locks
-- =====================================================================
CREATE TABLE IF NOT EXISTS `cache` (
    `key`         VARCHAR(255) NOT NULL,
    `value`       MEDIUMTEXT NOT NULL,
    `expiration`  INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cache_locks` (
    `key`         VARCHAR(255) NOT NULL,
    `owner`       VARCHAR(255) NOT NULL,
    `expiration`  INT NOT NULL,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4) jobs / job_batches / failed_jobs
-- =====================================================================
CREATE TABLE IF NOT EXISTS `jobs` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `queue`          VARCHAR(255) NOT NULL,
    `payload`        LONGTEXT NOT NULL,
    `attempts`       TINYINT UNSIGNED NOT NULL,
    `reserved_at`    INT UNSIGNED NULL,
    `available_at`   INT UNSIGNED NOT NULL,
    `created_at`     INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_batches` (
    `id`              VARCHAR(255) NOT NULL,
    `name`            VARCHAR(255) NOT NULL,
    `total_jobs`      INT NOT NULL,
    `pending_jobs`    INT NOT NULL,
    `failed_jobs`     INT NOT NULL,
    `failed_job_ids`  LONGTEXT NOT NULL,
    `options`         MEDIUMTEXT NULL,
    `cancelled_at`    INT NULL,
    `created_at`      INT NOT NULL,
    `finished_at`     INT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `failed_jobs` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`       VARCHAR(255) NOT NULL,
    `connection` TEXT NOT NULL,
    `queue`      TEXT NOT NULL,
    `payload`    LONGTEXT NOT NULL,
    `exception`  LONGTEXT NOT NULL,
    `failed_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5) families（含後續新增的 invite_code / currency）
-- =====================================================================
CREATE TABLE IF NOT EXISTS `families` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`                  VARCHAR(255) NOT NULL,
    `invite_code`           VARCHAR(12) NULL,
    `currency`              VARCHAR(10) NOT NULL DEFAULT 'TWD',
    `created_by_user_id`    BIGINT UNSIGNED NULL,
    `owner_user_id`         BIGINT UNSIGNED NULL,
    `total_pool_amount`     DECIMAL(15,2) NOT NULL DEFAULT 0,
    `pool_currency`         VARCHAR(10) NOT NULL DEFAULT 'TWD',
    `settings`              JSON NULL,
    `discord_webhook_url`   VARCHAR(255) NULL,
    `storage_quota_mb`      INT NOT NULL DEFAULT 500,
    `is_archived`           TINYINT(1) NOT NULL DEFAULT 0,
    `archived_at`           TIMESTAMP NULL,
    `created_at`            TIMESTAMP NULL,
    `updated_at`            TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `families_invite_code_unique` (`invite_code`),
    KEY `families_created_by_user_id_foreign` (`created_by_user_id`),
    KEY `families_owner_user_id_foreign` (`owner_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6) family_user（樞紐表，含 admin 角色）
-- =====================================================================
CREATE TABLE IF NOT EXISTS `family_user` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`    BIGINT UNSIGNED NOT NULL,
    `user_id`      BIGINT UNSIGNED NOT NULL,
    `role`         ENUM('admin','parent','child','guest') NOT NULL DEFAULT 'parent',
    `display_name` VARCHAR(255) NULL,
    `joined_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `family_user_family_id_user_id_unique` (`family_id`,`user_id`),
    KEY `family_user_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7) accounts
-- =====================================================================
CREATE TABLE IF NOT EXISTS `accounts` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`     BIGINT UNSIGNED NOT NULL,
    `name`          VARCHAR(255) NOT NULL,
    `type`          ENUM('cash','bank','credit','ewallet','custom') NOT NULL DEFAULT 'cash',
    `type_custom`   VARCHAR(255) NULL,
    `balance`       DECIMAL(15,2) NOT NULL DEFAULT 0,
    `currency`      VARCHAR(10) NOT NULL DEFAULT 'TWD',
    `color`         VARCHAR(20) NULL,
    `icon`          VARCHAR(50) NULL,
    `is_archived`   TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`    TIMESTAMP NULL,
    `updated_at`    TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `accounts_family_id_foreign` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8) categories
-- =====================================================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`    BIGINT UNSIGNED NULL,
    `parent_id`    BIGINT UNSIGNED NULL,
    `name`         VARCHAR(255) NOT NULL,
    `icon`         VARCHAR(50) NULL,
    `color`        VARCHAR(20) NULL,
    `sort_order`   INT NOT NULL DEFAULT 0,
    `is_custom`    TINYINT(1) NOT NULL DEFAULT 0,
    `scope`        ENUM('family','personal') NOT NULL DEFAULT 'family',
    `type`         ENUM('expense','income','both') NOT NULL DEFAULT 'expense',
    `is_archived`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`   TIMESTAMP NULL,
    `updated_at`   TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `categories_family_id_foreign` (`family_id`),
    KEY `categories_parent_id_foreign` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9) tags
-- =====================================================================
CREATE TABLE IF NOT EXISTS `tags` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`  BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(255) NOT NULL,
    `color`      VARCHAR(20) NULL,
    `is_custom`  TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `tags_family_id_foreign` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 10) recurring_rules
-- =====================================================================
CREATE TABLE IF NOT EXISTS `recurring_rules` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`         BIGINT UNSIGNED NOT NULL,
    `type`              ENUM('subscription','bill','income') NOT NULL DEFAULT 'subscription',
    `template`          JSON NOT NULL,
    `cycle`             ENUM('monthly','yearly','weekly','custom') NOT NULL DEFAULT 'monthly',
    `cycle_custom`      JSON NULL,
    `next_run_at`       DATETIME NULL,
    `last_run_at`       DATETIME NULL,
    `alert_days_before` JSON NULL,
    `auto_create`       TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`        TIMESTAMP NULL,
    `updated_at`        TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `recurring_rules_family_id_foreign` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 11) transactions（含 softDeletes）
-- =====================================================================
CREATE TABLE IF NOT EXISTS `transactions` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`           BIGINT UNSIGNED NOT NULL,
    `user_id`             BIGINT UNSIGNED NOT NULL,
    `type`                ENUM('expense','income','transfer','split','refund','custom') NOT NULL DEFAULT 'expense',
    `type_custom`         VARCHAR(255) NULL,
    `amount`              DECIMAL(15,2) NOT NULL,
    `occurred_at`         DATETIME NOT NULL,
    `account_id`          BIGINT UNSIGNED NULL,
    `to_account_id`       BIGINT UNSIGNED NULL,
    `category_id`         BIGINT UNSIGNED NULL,
    `payee_user_id`       BIGINT UNSIGNED NULL,
    `payee_custom`        VARCHAR(255) NULL,
    `description`         VARCHAR(255) NULL,
    `note`                TEXT NULL,
    `attachment_ids`      JSON NULL,
    `tag_ids`             JSON NULL,
    `split_with`          JSON NULL,
    `recurring_rule_id`   BIGINT UNSIGNED NULL,
    `refunded_from_id`    BIGINT UNSIGNED NULL,
    `custom_fields`       JSON NULL,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    `deleted_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `transactions_family_id_foreign` (`family_id`),
    KEY `transactions_user_id_foreign` (`user_id`),
    KEY `transactions_account_id_foreign` (`account_id`),
    KEY `transactions_to_account_id_foreign` (`to_account_id`),
    KEY `transactions_category_id_foreign` (`category_id`),
    KEY `transactions_payee_user_id_foreign` (`payee_user_id`),
    KEY `transactions_recurring_rule_id_foreign` (`recurring_rule_id`),
    KEY `transactions_refunded_from_id_foreign` (`refunded_from_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 12) budgets
-- =====================================================================
CREATE TABLE IF NOT EXISTS `budgets` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`          BIGINT UNSIGNED NOT NULL,
    `period_type`        ENUM('month','quarter','year','custom') NOT NULL DEFAULT 'month',
    `period_start`       DATE NULL,
    `period_end`         DATE NULL,
    `scope`              ENUM('family','category','user','group') NOT NULL DEFAULT 'family',
    `scope_target_ids`   JSON NULL,
    `amount`             DECIMAL(15,2) NOT NULL,
    `alert_thresholds`   JSON NULL,
    `rollover`           TINYINT(1) NOT NULL DEFAULT 0,
    `parent_budget_id`   BIGINT UNSIGNED NULL,
    `created_at`         TIMESTAMP NULL,
    `updated_at`         TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `budgets_family_id_foreign` (`family_id`),
    KEY `budgets_parent_budget_id_foreign` (`parent_budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 13) child_limits
-- =====================================================================
CREATE TABLE IF NOT EXISTS `child_limits` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`           BIGINT UNSIGNED NOT NULL,
    `child_user_id`       BIGINT UNSIGNED NOT NULL,
    `per_transaction_max` DECIMAL(15,2) NULL,
    `daily_max`           DECIMAL(15,2) NULL,
    `weekly_max`          DECIMAL(15,2) NULL,
    `monthly_max`         DECIMAL(15,2) NULL,
    `ratio_of_pocket`     DECIMAL(5,2) NULL,
    `overrides`           JSON NULL,
    `effective_from`      DATE NULL,
    `effective_to`        DATE NULL,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `child_limits_family_id_foreign` (`family_id`),
    KEY `child_limits_child_user_id_foreign` (`child_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 14) subscriptions
-- =====================================================================
CREATE TABLE IF NOT EXISTS `subscriptions` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`           BIGINT UNSIGNED NOT NULL,
    `name`                VARCHAR(255) NOT NULL,
    `amount`              DECIMAL(15,2) NOT NULL,
    `cycle`               ENUM('monthly','yearly','weekly','custom') NOT NULL DEFAULT 'monthly',
    `cycle_custom`        JSON NULL,
    `next_billing_date`   DATE NULL,
    `account_id`          BIGINT UNSIGNED NULL,
    `category_id`         BIGINT UNSIGNED NULL,
    `shared_with`         JSON NULL,
    `trial_until`         DATE NULL,
    `url`                 VARCHAR(255) NULL,
    `note`                TEXT NULL,
    `is_paused`           TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `subscriptions_family_id_foreign` (`family_id`),
    KEY `subscriptions_account_id_foreign` (`account_id`),
    KEY `subscriptions_category_id_foreign` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 15) saving_goals
-- =====================================================================
CREATE TABLE IF NOT EXISTS `saving_goals` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         BIGINT UNSIGNED NOT NULL,
    `family_id`       BIGINT UNSIGNED NOT NULL,
    `name`            VARCHAR(255) NOT NULL,
    `target_amount`   DECIMAL(15,2) NOT NULL,
    `current_amount`  DECIMAL(15,2) NOT NULL DEFAULT 0,
    `deadline`        DATE NULL,
    `icon`            VARCHAR(50) NULL,
    `color`           VARCHAR(20) NULL,
    `contributions`   JSON NULL,
    `created_at`      TIMESTAMP NULL,
    `updated_at`      TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `saving_goals_user_id_foreign` (`user_id`),
    KEY `saving_goals_family_id_foreign` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 16) tasks
-- =====================================================================
CREATE TABLE IF NOT EXISTS `tasks` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`           BIGINT UNSIGNED NOT NULL,
    `name`                VARCHAR(255) NOT NULL,
    `reward_type`         ENUM('fixed','flexible','custom') NOT NULL DEFAULT 'fixed',
    `reward_amount`       DECIMAL(15,2) NULL,
    `reward_custom`       VARCHAR(255) NULL,
    `assignee_user_id`    BIGINT UNSIGNED NULL,
    `status`              ENUM('pending','reported','approved','rejected') NOT NULL DEFAULT 'pending',
    `due_date`            DATE NULL,
    `reported_at`         DATETIME NULL,
    `approved_at`         DATETIME NULL,
    `approved_by_user_id` BIGINT UNSIGNED NULL,
    `reject_reason`       TEXT NULL,
    `created_at`          TIMESTAMP NULL,
    `updated_at`          TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `tasks_family_id_foreign` (`family_id`),
    KEY `tasks_assignee_user_id_foreign` (`assignee_user_id`),
    KEY `tasks_approved_by_user_id_foreign` (`approved_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 17) audit_logs
-- =====================================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`    BIGINT UNSIGNED NULL,
    `user_id`      BIGINT UNSIGNED NULL,
    `action`       VARCHAR(255) NOT NULL,
    `entity_type`  VARCHAR(255) NULL,
    `entity_id`    BIGINT UNSIGNED NULL,
    `before`       JSON NULL,
    `after`        JSON NULL,
    `ip`           VARCHAR(45) NULL,
    `user_agent`   TEXT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `audit_logs_family_id_foreign` (`family_id`),
    KEY `audit_logs_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 18) notifications
-- =====================================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`              CHAR(36) NOT NULL,
    `user_id`         BIGINT UNSIGNED NOT NULL,
    `family_id`       BIGINT UNSIGNED NULL,
    `type`            VARCHAR(255) NOT NULL,
    `title`           VARCHAR(255) NULL,
    `body`            TEXT NULL,
    `channel`         ENUM('email','discord','system') NOT NULL DEFAULT 'system',
    `related_entity`  JSON NULL,
    `read_at`         TIMESTAMP NULL,
    `sent_at`         TIMESTAMP NULL,
    `created_at`      TIMESTAMP NULL,
    `updated_at`      TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `notifications_user_id_foreign` (`user_id`),
    KEY `notifications_family_id_foreign` (`family_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 19) custom_value_promotions
-- =====================================================================
CREATE TABLE IF NOT EXISTS `custom_value_promotions` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`             BIGINT UNSIGNED NOT NULL,
    `field_type`            ENUM('category','tag','account') NOT NULL,
    `proposed_value`        VARCHAR(255) NOT NULL,
    `proposed_by_user_id`   BIGINT UNSIGNED NOT NULL,
    `status`                ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `created_at`            TIMESTAMP NULL,
    `updated_at`            TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `custom_value_promotions_family_id_foreign` (`family_id`),
    KEY `custom_value_promotions_proposed_by_user_id_foreign` (`proposed_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 20) parent_codes
-- =====================================================================
CREATE TABLE IF NOT EXISTS `parent_codes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`       VARCHAR(255) NOT NULL,
    `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
    `note`       VARCHAR(255) NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `parent_codes_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 21) family_invitations
-- =====================================================================
CREATE TABLE IF NOT EXISTS `family_invitations` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`        BIGINT UNSIGNED NOT NULL,
    `email`            VARCHAR(255) NOT NULL,
    `token`            VARCHAR(255) NOT NULL,
    `role`             VARCHAR(255) NOT NULL DEFAULT 'child',
    `inviter_user_id`  BIGINT UNSIGNED NULL,
    `used_at`          TIMESTAMP NULL,
    `expires_at`       TIMESTAMP NULL,
    `created_at`       TIMESTAMP NULL,
    `updated_at`       TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `family_invitations_token_unique` (`token`),
    KEY `family_invitations_family_id_foreign` (`family_id`),
    KEY `family_invitations_inviter_user_id_foreign` (`inviter_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 22) attachments
-- =====================================================================
CREATE TABLE IF NOT EXISTS `attachments` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `transaction_id` BIGINT UNSIGNED NOT NULL,
    `family_id`      BIGINT UNSIGNED NULL,
    `user_id`        BIGINT UNSIGNED NULL,
    `file_path`      VARCHAR(255) NOT NULL,
    `file_name`      VARCHAR(255) NOT NULL,
    `mime_type`      VARCHAR(255) NULL,
    `file_size`      BIGINT UNSIGNED NULL,
    `created_at`     TIMESTAMP NULL,
    `updated_at`     TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `attachments_transaction_id_foreign` (`transaction_id`),
    KEY `attachments_family_id_foreign` (`family_id`),
    KEY `attachments_user_id_foreign` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 23) system_settings
-- =====================================================================
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key`         VARCHAR(255) NOT NULL,
    `value`       TEXT NULL,
    `description` VARCHAR(255) NULL,
    `created_at`  TIMESTAMP NULL,
    `updated_at`  TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `system_settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 24) family_ai_reports
-- =====================================================================
CREATE TABLE IF NOT EXISTS `family_ai_reports` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `family_id`             BIGINT UNSIGNED NOT NULL,
    `created_by_user_id`    BIGINT UNSIGNED NULL,
    `month`                 VARCHAR(7) NOT NULL COMMENT "e.g. '2026-08'",
    `financial_metrics`     JSON NULL,
    `ai_report`             LONGTEXT NOT NULL,
    `status`                VARCHAR(255) NOT NULL DEFAULT 'sent' COMMENT "draft, reviewed, sent",
    `sent_to_users_count`   INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`            TIMESTAMP NULL,
    `updated_at`            TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `family_ai_reports_family_id_month_index` (`family_id`,`month`),
    KEY `family_ai_reports_created_by_user_id_foreign` (`created_by_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 25) migrations（Laravel 用來追蹤哪些 migration 已跑過）
-- =====================================================================
CREATE TABLE IF NOT EXISTS `migrations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration`   VARCHAR(255) NOT NULL,
    `batch`       INT NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 把所有 migration 都登記成「已跑過」，這樣未來 php artisan migrate 不會重複執行
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
    ('0001_01_01_000000_create_users_table', 1),
    ('0001_01_01_000001_create_cache_table', 1),
    ('0001_01_01_000002_create_jobs_table', 1),
    ('2026_08_25_000001_create_families_table', 1),
    ('2026_08_25_000002_create_family_user_table', 1),
    ('2026_08_25_000003_create_accounts_table', 1),
    ('2026_08_25_000004_create_categories_table', 1),
    ('2026_08_25_000005_create_tags_table', 1),
    ('2026_08_25_000006_create_recurring_rules_table', 1),
    ('2026_08_25_000007_create_transactions_table', 1),
    ('2026_08_25_000008_create_budgets_table', 1),
    ('2026_08_25_000009_create_child_limits_table', 1),
    ('2026_08_25_000010_create_subscriptions_table', 1),
    ('2026_08_25_000011_create_saving_goals_table', 1),
    ('2026_08_25_000012_create_tasks_table', 1),
    ('2026_08_25_000013_create_audit_logs_table', 1),
    ('2026_08_25_000014_create_notifications_table', 1),
    ('2026_08_25_000015_create_custom_value_promotions_table', 1),
    ('2026_08_26_024500_add_invite_code_and_currency_to_families_table', 1),
    ('2026_08_26_030000_add_registration_role_to_users_table', 1),
    ('2026_08_26_040000_create_parent_codes_table', 1),
    ('2026_08_26_040001_create_family_invitations_table', 1),
    ('2026_08_26_050000_create_attachments_table', 1),
    ('2026_08_26_120000_add_notification_preferences_to_users_table', 1),
    ('2026_08_27_010000_add_2fa_fields_to_users_table', 1),
    ('2026_08_27_011500_extend_two_factor_secret_column', 1),
    ('2026_08_27_020000_create_system_settings_table', 1),
    ('2026_08_27_030000_create_family_ai_reports_table', 1),
    ('2026_08_27_040000_add_locale_to_users_table', 1),
    ('2026_08_28_010000_add_admin_role_to_enums', 1),
    ('2026_08_28_020000_migrate_heroicons_to_material_symbols', 1);

-- 開啟外鍵檢查
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 26) 預設管理員帳號 (admin / admin)
--    - 已驗證 bcrypt(cost=12) 雜湊可被 Laravel Hash::check('password', ...) 通過
--    - is_system_admin = 1  （後台 /admin 登入權限）
--    - registration_role = 'admin'
--    - 已通過 email 驗證
--    - 未啟用 2FA
-- =====================================================================
INSERT INTO `users` (
    `name`, `email`, `account`, `registration_role`,
    `is_system_admin`, `email_verified_at`, `password`,
    `two_factor_enabled`, `failed_login_count`,
    `created_at`, `updated_at`
) VALUES (
    'System Administrator',
    'admin@example.com',
    'admin',
    'admin',
    1,
    CURRENT_TIMESTAMP,
    '$2y$12$/LPoQ.5o1qkZopaXAJRu4Oc95UL2czvsZOhlcGd7lWpdMFlogeF6K',
    0,
    0,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
)
ON DUPLICATE KEY UPDATE
    `password`              = VALUES(`password`),
    `is_system_admin`       = VALUES(`is_system_admin`),
    `registration_role`     = VALUES(`registration_role`),
    `email_verified_at`     = VALUES(`email_verified_at`),
    `updated_at`            = CURRENT_TIMESTAMP;

-- =====================================================================
-- 完成提示
-- =====================================================================
SELECT
    '✅ Schema 安裝完成' AS status,
    (SELECT COUNT(*) FROM `users`) AS users_count,
    (SELECT COUNT(*) FROM `migrations`) AS migrations_count;

-- 顯示 admin 帳號資訊（密碼以 bcrypt 雜湊儲存，無法反解）
SELECT
    id,
    name,
    email,
    account,
    registration_role,
    is_system_admin,
    LEFT(password, 7) AS password_algo,
    SUBSTRING_INDEX(SUBSTRING_INDEX(password, '$', 3), '$', -1) AS bcrypt_cost,
    created_at
FROM `users`
WHERE `email` = 'admin@example.com';

