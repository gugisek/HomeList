-- Cukry we wpisach dziennika (2.1.2): kopia jak reszta makro, NULL = produkt bez danych o cukrze
-- (szybkie dodanie, część produktów OFF). Podsumowanie dnia i statystyki liczą sumę z wpisów, które ją mają.
ALTER TABLE `food_log`
  ADD COLUMN `sugars` decimal(6,2) DEFAULT NULL AFTER `carbs`;

-- dotychczasowe wpisy: z obecnych wartości produktu (wcześniej cukrów nie kopiowano)
UPDATE `food_log` l JOIN `food_products` p ON p.id = l.product_id
   SET l.sugars = ROUND(p.sugars * l.grams / 100, 2)
 WHERE l.sugars IS NULL AND l.grams IS NOT NULL AND p.sugars IS NOT NULL;
