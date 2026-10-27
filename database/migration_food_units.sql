-- Porcje i opakowania (2.0.1)
-- 1) etykieta porcji z OFF (serving_size, np. „1 bottle (555 ml)” -> butelka) zamiast ogólnej „porcji”
-- 2) jednostki zgłaszane przez użytkowników (np. opakowanie, którego OFF nie zna): autor widzi je od razu,
--    pozostali po akceptacji w „Bazie produktów”; owner_id NULL = jednostka seeda / autora produktu / dania

ALTER TABLE `food_products`
  ADD COLUMN `serving_label` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `serving_g`;

ALTER TABLE `food_product_units`
  ADD COLUMN `owner_id` int DEFAULT NULL AFTER `sort`,
  ADD COLUMN `status` enum('approved','pending','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'approved' AFTER `owner_id`,
  ADD COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `status`,
  ADD KEY `status` (`status`);

-- „porcja 100 g” z OFF to tylko przeliczenie etykiety – dubluje jednostkę 100 g
UPDATE `food_products` SET `serving_g` = NULL WHERE `source` = 'off' AND `serving_g` = 100;

-- następny import (php scripts/food/import_off.php) odświeży wszystkie produkty OFF i uzupełni serving_label
UPDATE `food_products` SET `off_modified` = NULL WHERE `source` = 'off';
