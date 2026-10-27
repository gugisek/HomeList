-- Dania domowe 2.0 (2.1.0)
-- 1) danie „dla wszystkich”: public = 1; dom widzi je od razu, pozostali po akceptacji (status jak przy produktach użytkowników)
--    danie „tylko dom”: public = 0, status approved (widoczność i tak ogranicza się do domowników)
-- 2) składnik pamięta jednostkę, w której go wpisano („3 × sztuka” jajka), żeby edytor pokazał to samo
-- 3) liczba porcji, na które dzieli się garnek – porcja dania = waga / servings

ALTER TABLE `food_products`
  ADD COLUMN `public` tinyint(1) NOT NULL DEFAULT 0 AFTER `owner_id`;

ALTER TABLE `food_dish_items`
  ADD COLUMN `unit_label` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `grams`,
  ADD COLUMN `unit_g` decimal(7,1) DEFAULT NULL AFTER `unit_label`;

ALTER TABLE `food_dishes`
  ADD COLUMN `servings` tinyint DEFAULT NULL AFTER `cooked_g`;
