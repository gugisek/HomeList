-- Zgłoszenia błędów (1.8.0)

CREATE TABLE IF NOT EXISTS `bug_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `screenshots` text COLLATE utf8mb4_general_ci DEFAULT NULL,      -- JSON: nazwy plików w uploads/reports/
  `app_version` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `viewport` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,      -- rozmiar ekranu + czy zainstalowana (PWA)
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `app_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,   -- adres aplikacji do linku w mailu (cron nie zna hosta)
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `seen_at` datetime DEFAULT NULL,                                  -- pierwszy raz wyświetlone przez administratora
  `notified_at` datetime DEFAULT NULL,                              -- mail do administratorów wysłany (cron)
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `notified_at` (`notified_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- nowy moduł uprawnień: podgląd zgłoszeń (read) i usuwanie (full); dostaje go każdy, kto ma pełne ustawienia
ALTER TABLE `user_roles` ADD COLUMN `reports` int NOT NULL DEFAULT 5 AFTER `faq`;
UPDATE `user_roles` SET `reports` = 4 WHERE `settings` = 4;
