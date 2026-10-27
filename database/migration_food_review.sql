-- Historia moderacji bazy produktów (2.0.2): kto i kiedy zaakceptował / odrzucił produkt albo jednostkę porcji.
-- Jednostka dodana przez moderatora jest od razu zaakceptowana – reviewed_by = on sam.
-- Usunięcia nie zostawiają wiersza – trafiają do archiwum zmian (logs, object_type food_product / food_unit).

ALTER TABLE `food_products`
  ADD COLUMN `reviewed_by` int DEFAULT NULL AFTER `status`,
  ADD COLUMN `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by`;

ALTER TABLE `food_product_units`
  ADD COLUMN `reviewed_by` int DEFAULT NULL AFTER `status`,
  ADD COLUMN `reviewed_at` datetime DEFAULT NULL AFTER `reviewed_by`;

-- zgłoszone jednostki zaakceptowane przed tą migracją (np. dodane przez moderatora) – decyzja = moment dodania
UPDATE `food_product_units` SET `reviewed_at` = `created_at` WHERE `owner_id` IS NOT NULL AND `status` <> 'pending' AND `reviewed_at` IS NULL;
