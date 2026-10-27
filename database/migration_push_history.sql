-- Historia wysłanych powiadomień push (1.9.2) – podgląd w Użytkownicy > szczegóły > Powiadomienia
-- Bez treści: tylko rodzaj (tag), liczba urządzeń i ile przyjęło. Wpisy starsze niż 90 dni kasuje scripts/push/cron.php.

CREATE TABLE IF NOT EXISTS `push_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `tag` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `devices` tinyint NOT NULL DEFAULT 0,
  `delivered` tinyint NOT NULL DEFAULT 0,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_sent` (`user_id`, `sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
