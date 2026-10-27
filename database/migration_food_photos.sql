-- Zdjęcia dań (2.1.1): plik w uploads/food/ (Require all denied), wydaje go scripts/food/photo.php
-- po sprawdzeniu widoczności – zdjęcie dania „tylko dom” nie wycieknie poza dom.
ALTER TABLE `food_products`
  ADD COLUMN `photo` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `public`;
