-- Powiadomienia push (1.6.0)

CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `endpoint` varchar(700) COLLATE utf8mb4_general_ci NOT NULL,
  `endpoint_hash` char(40) COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_ok` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `endpoint_hash` (`endpoint_hash`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ustawienia użytkownika: godziny rozpoczęcia pór dnia + rodzaje przypomnień
CREATE TABLE IF NOT EXISTS `push_settings` (
  `user_id` int NOT NULL,
  `enabled` tinyint NOT NULL DEFAULT 1,
  `morning_start` time NOT NULL DEFAULT '07:00:00',
  `afternoon_start` time NOT NULL DEFAULT '12:00:00',
  `evening_start` time NOT NULL DEFAULT '17:00:00',
  `day_end` time NOT NULL DEFAULT '21:30:00',
  `notify_parts` tinyint NOT NULL DEFAULT 1,
  `notify_missed` tinyint NOT NULL DEFAULT 1,
  `notify_deadlines` tinyint NOT NULL DEFAULT 1,
  `notify_timed` tinyint NOT NULL DEFAULT 1,
  `notify_routines` tinyint NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- rutyny, o których użytkownik nie chce przypomnień
CREATE TABLE IF NOT EXISTS `push_mute` (
  `user_id` int NOT NULL,
  `element_id` int NOT NULL,
  PRIMARY KEY (`user_id`, `element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- co już wysłano (każde zdarzenie najwyżej raz dziennie)
CREATE TABLE IF NOT EXISTS `push_log` (
  `user_id` int NOT NULL,
  `event_key` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `ref_date` date NOT NULL,
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `event_key`, `ref_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- treść powiadomień – service worker pobiera ją po otrzymaniu pusha (push bez szyfrowanego payloadu)
CREATE TABLE IF NOT EXISTS `push_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subscription_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `body` varchar(600) COLLATE utf8mb4_general_ci NOT NULL,
  `tag` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'panel.php',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
