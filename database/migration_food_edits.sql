-- Propozycje zmian produktów (2.4.0): każdy może zaproponować poprawkę dowolnego produktu (OFF, podstawowy, od użytkownika).
-- Zmiana trafia do moderatora („Baza produktów” > Poprawki), po akceptacji zmienia produkt dla wszystkich.
-- data = tylko zmienione pola {pole: nowa wartość}, base = te same pola przed zmianą (porównanie „teraz → propozycja”).
-- Moderator zapisuje od razu (status approved, reviewed_by = on sam) – wiersz zostaje w historii.

CREATE TABLE IF NOT EXISTS `food_product_edits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `user_id` int NOT NULL,
  `data` text COLLATE utf8mb4_general_ci NOT NULL,
  `base` text COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,          -- komentarz autora („co poprawiam”)
  `label_photo` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,    -- zdjęcie etykiety (widzą autor i moderatorzy)
  `status` enum('approved','pending','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `review_note` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product` (`product_id`),
  KEY `user` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- produkt poprawiony w HomeList: import OFF i ponowny seed produktów podstawowych już go nie nadpisują
ALTER TABLE `food_products` ADD COLUMN `edited_at` datetime DEFAULT NULL AFTER `off_modified`;
