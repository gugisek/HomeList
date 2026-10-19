-- Migration: Planer dnia + listy rutyn (1.5.0)
-- Uruchom raz na bazie produkcyjnej.

-- Typ listy: 'normal' (zwykła lista zadań) lub 'routine' (lista rutyn)
ALTER TABLE `lists`
  ADD COLUMN `list_type` VARCHAR(10) NOT NULL DEFAULT 'normal' AFTER `list_code`;

-- Pola rutyn (używane tylko dla elementów list typu 'routine')
--   recurrence_type: daily | weekly | interval | monthly
--   recurrence_days: weekly -> "1,3,5" (1 = pon ... 7 = niedz), monthly -> "1,15,31", interval -> "3" (co ile dni)
--   recurrence_start: data od której liczy się powtarzanie
--   day_part: 0 = kiedykolwiek, 1 = rano, 2 = popołudnie, 3 = wieczór
--   plan_time: opcjonalna godzina
--   shared_done: 0 = każdy odhacza osobno, 1 = odhaczenie wspólne dla wszystkich
ALTER TABLE `list_elements`
  ADD COLUMN `recurrence_type` VARCHAR(10) DEFAULT NULL AFTER `link_url`,
  ADD COLUMN `recurrence_days` VARCHAR(100) DEFAULT NULL AFTER `recurrence_type`,
  ADD COLUMN `recurrence_start` DATE DEFAULT NULL AFTER `recurrence_days`,
  ADD COLUMN `day_part` TINYINT NOT NULL DEFAULT 0 AFTER `recurrence_start`,
  ADD COLUMN `plan_time` TIME DEFAULT NULL AFTER `day_part`,
  ADD COLUMN `shared_done` TINYINT(1) NOT NULL DEFAULT 0 AFTER `plan_time`;

-- Prywatny plan dnia użytkownika.
--   Dla zwykłego zadania: wiersz = zadanie zaplanowane na dany dzień (max 1 wiersz na użytkownika i zadanie).
--   Dla rutyny: opcjonalny wiersz nadpisujący porę dnia / kolejność w konkretnym dniu.
CREATE TABLE `day_plan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `element_id` int NOT NULL,
  `plan_date` DATE NOT NULL,
  `day_part` TINYINT NOT NULL DEFAULT 0,
  `plan_time` TIME DEFAULT NULL,
  `position` int NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_date` (`user_id`, `plan_date`),
  KEY `element` (`element_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Odhaczenia rutyn (jeden wiersz = rutyna zrobiona danego dnia przez danego użytkownika)
CREATE TABLE `routine_done` (
  `id` int NOT NULL AUTO_INCREMENT,
  `element_id` int NOT NULL,
  `user_id` int NOT NULL,
  `done_date` DATE NOT NULL,
  `done_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `element_user_date` (`element_id`, `user_id`, `done_date`),
  KEY `element_date` (`element_id`, `done_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
