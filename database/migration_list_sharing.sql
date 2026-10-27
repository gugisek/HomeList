-- Udostępnianie list (QR) + push o nowych zadaniach na listach wspólnych (1.9.0)

-- listy wspólne, o których użytkownik nie chce powiadomień (domyślnie włączone = brak wiersza)
CREATE TABLE IF NOT EXISTS `push_list_mute` (
  `user_id` int NOT NULL,
  `list_id` int NOT NULL,
  PRIMARY KEY (`user_id`, `list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- do którego zadania lista została już ogłoszona (cron scala serię dodanych zadań w jeden push)
CREATE TABLE IF NOT EXISTS `list_push_state` (
  `list_id` int NOT NULL,
  `last_element_id` int NOT NULL DEFAULT 0,
  `notified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- bez pushy o zadaniach sprzed migracji
INSERT IGNORE INTO `list_push_state` (`list_id`, `last_element_id`)
SELECT `lists`.`id`, COALESCE(MAX(`list_elements`.`id`), 0) FROM `lists`
LEFT JOIN `list_elements` ON `list_elements`.`list_id` = `lists`.`id`
GROUP BY `lists`.`id`;

-- przełącznik w Konto > Powiadomienia
ALTER TABLE `push_settings` ADD COLUMN `notify_lists` tinyint NOT NULL DEFAULT 1 AFTER `notify_routines`;
