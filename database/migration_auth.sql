-- Core: logowanie, rejestracja, role, bezpieczeństwo (1.7.0)
-- Mechanizmy przeniesione z panels-mOsiedle-v2: bcrypt, rotacja remember_me, login_attempts,
-- aktywacja kodem email, reset hasła, 2FA (TOTP), ustawienia SMTP w app_settings.

-- bcrypt/argon (password_hash) potrzebuje więcej niż 64 znaki; stare hashe sha256 są
-- przepisywane przezroczyście przy najbliższym logowaniu (scripts/password_helper.php)
ALTER TABLE `users` MODIFY `pswd` varchar(255) COLLATE utf8mb4_general_ci NOT NULL;

-- znacznik bezpieczeństwa konta: zmiana (reset hasła, "wyloguj wszędzie", blokada) unieważnia
-- wszystkie aktywne sesje PHP tego konta przy ich najbliższym żądaniu (scripts/security.php)
ALTER TABLE `users` ADD COLUMN `security_stamp` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' AFTER `pswd`;

-- "zbanowane" miało uprawnienie do logowania (privileges = 1)
UPDATE `user_status` SET `privileges` = 2 WHERE `id` = 4;

-- poziom dostępu do modułu FAQ (wcześniej FAQ widział każdy admin na sztywno w sidebarze)
ALTER TABLE `user_roles` ADD COLUMN `faq` int NOT NULL DEFAULT 5 AFTER `settings`;
UPDATE `user_roles` SET `faq` = 4 WHERE `id` = 1;

-- rotacja tokenu "zapamiętaj mnie" + okno tolerancji (patrz scripts/session_restore.php)
-- oraz dane urządzenia do listy "Zapamiętane urządzenia" w profilu
ALTER TABLE `remember_tokens`
  ADD COLUMN `previous_token_hash` varchar(64) DEFAULT NULL AFTER `token_hash`,
  ADD COLUMN `rotated_at` datetime DEFAULT NULL AFTER `expires`,
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `rotated_at`,
  ADD COLUMN `last_used_at` datetime DEFAULT NULL AFTER `created_at`,
  ADD COLUMN `user_agent` varchar(255) DEFAULT NULL AFTER `last_used_at`,
  ADD KEY `user_id` (`user_id`);

-- log prób logowania (udanych i nieudanych) – throttling brute-force + podgląd w Archiwum
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL COMMENT 'NULL gdy login_input nie pasuje do żadnego konta',
  `login_input` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `method` enum('password','2fa','backup_code','remember_me','activation') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'password',
  `failure_reason` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_user` (`user_id`),
  KEY `idx_login_attempts_created` (`created_at`),
  KEY `idx_login_attempts_login_input` (`login_input`),
  KEY `idx_login_attempts_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6-cyfrowe kody aktywacji konta (ważne 24h)
CREATE TABLE IF NOT EXISTS `activation_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(6) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- jednorazowe linki resetu hasła – w bazie tylko sha256(token), surowy token wyłącznie w mailu
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token_hash` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `requester_ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2FA (TOTP) – sekret szyfrowany AES-256-GCM kluczem z scripts/app_secrets.php
CREATE TABLE IF NOT EXISTS `user_two_factor_settings` (
  `user_id` int NOT NULL,
  `secret` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `enabled_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `user_two_factor_backup_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ustawienia aplikacji (SMTP, rejestracja) – edytowane w Ustawienia > Email / Rejestracja
CREATE TABLE IF NOT EXISTS `app_settings` (
  `name_type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`name_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `app_settings` (`name_type`, `value`) VALUES
  ('smtp_server', ''),
  ('smtp_port', '587'),
  ('smtp_secure', 'tls'),
  ('smtp_email', ''),
  ('smtp_username', ''),
  ('smtp_auth_token', ''),
  ('smtp_name', 'HomeList'),
  ('registration_open', '1'),
  ('registration_role', '3'),
  ('registration_activation', '1');
