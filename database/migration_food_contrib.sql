-- Produkty od użytkowników 2.0 (2.3.0)
-- 1) zdjęcie etykiety do weryfikacji: plik w uploads/food/ (label-*), widzą je tylko autor i moderatorzy bazy produktów
-- 2) uwaga moderatora przy odrzuceniu – autor widzi ją w „Twój wkład”; akceptacja ją czyści
-- Skład, alergeny, Nutri-Score, NOVA itd. od użytkownika trafiają do food_product_details (jak dane z OFF),
-- więc sekcja „Skład i jakość” działa bez zmian.

ALTER TABLE `food_products`
  ADD COLUMN `label_photo` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `photo`,
  ADD COLUMN `review_note` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL AFTER `reviewed_at`;

-- „Twój wkład” liczy wpisy innych osób z produktem
ALTER TABLE `food_log` ADD KEY `product` (`product_id`);
