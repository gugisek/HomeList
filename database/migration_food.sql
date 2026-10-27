-- Jedzenie / licznik kalorii (2.0.0)
-- Produkty z trzech źródeł w jednej tabeli: base (seed produktów podstawowych), off (import Open Food Facts),
-- user (dodane przez użytkowników) i dish (dania domowe – wartości liczone ze składników).
-- Wartości odżywcze zawsze na 100 g (albo 100 ml, gdy per_ml = 1).

CREATE TABLE IF NOT EXISTS `food_products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `source` enum('base','off','user','dish') COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(32) COLLATE utf8mb4_general_ci DEFAULT NULL,           -- EAN / kod OFF
  `seed_key` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,       -- klucz produktu podstawowego (ponowny seed = aktualizacja)
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `brand` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,       -- opis opakowania, np. „150 g”
  `package_g` decimal(8,1) DEFAULT NULL,                                -- opakowanie w g/ml (jednostka „opakowanie”)
  `serving_g` decimal(7,1) DEFAULT NULL,                                -- porcja z etykiety
  `per_ml` tinyint(1) NOT NULL DEFAULT 0,
  `kcal` decimal(6,1) NOT NULL,
  `protein` decimal(5,2) NOT NULL,
  `fat` decimal(5,2) NOT NULL,
  `carbs` decimal(5,2) NOT NULL,
  `sugars` decimal(5,2) DEFAULT NULL,
  `fiber` decimal(5,2) DEFAULT NULL,
  `sat_fat` decimal(5,2) DEFAULT NULL,
  `salt` decimal(5,2) DEFAULT NULL,
  `name_search` varchar(400) COLLATE utf8mb4_general_ci NOT NULL,      -- nazwa + marka małymi literami, bez polskich znaków
  `popularity` int NOT NULL DEFAULT 0,                                  -- OFF: unique_scans_n; base: stała premia w rankingu
  `owner_id` int DEFAULT NULL,                                          -- autor (user / dish)
  `status` enum('approved','pending','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'approved',
  `off_modified` int DEFAULT NULL,                                      -- OFF last_modified_t – import pomija niezmienione
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `seed_key` (`seed_key`),
  KEY `source_status` (`source`, `status`),
  KEY `owner` (`owner_id`),
  KEY `popularity` (`popularity`),
  FULLTEXT KEY `name_search` (`name_search`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Jednostki porcji (sztuka, kromka, kubek…) dla produktów podstawowych, własnych i dań.
-- Produkty z OFF korzystają z serving_g / package_g z food_products.
CREATE TABLE IF NOT EXISTS `food_product_units` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `label` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `grams` decimal(7,1) NOT NULL,
  `sort` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `product` (`product_id`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- „Skład i jakość” z OFF – osobno, żeby tabela wyszukiwania została lekka.
-- Tagi jako listy po przecinku bez prefiksu języka (np. allergens = 'milk,gluten', additives = 'e330,e250').
CREATE TABLE IF NOT EXISTS `food_product_details` (
  `product_id` int NOT NULL,
  `ingredients` text COLLATE utf8mb4_general_ci DEFAULT NULL,         -- alergeny w tekście oznaczone _podkreślnikami_ (format OFF)
  `ingredients_lang` char(2) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `allergens` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `traces` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `additives` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nova` tinyint DEFAULT NULL,                                          -- 1–4
  `nutriscore` char(1) COLLATE utf8mb4_general_ci DEFAULT NULL,         -- a–e
  `palm_oil` tinyint DEFAULT NULL,                                      -- 1 zawiera, 2 może zawierać, 0 bez, NULL nie wiadomo
  `vegan` tinyint DEFAULT NULL,                                         -- 1 tak, 2 może, 0 nie
  `vegetarian` tinyint DEFAULT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fetched_at` datetime DEFAULT NULL,                                   -- dociągnięte na żywo z API OFF (produkt z wyszukiwarki nie ma składu)
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Słownik dodatków (taksonomia OFF): nazwa PL i ryzyko nadmiernego spożycia wg EFSA.
CREATE TABLE IF NOT EXISTS `food_additives` (
  `code` varchar(12) COLLATE utf8mb4_general_ci NOT NULL,               -- 'e250'
  `name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `risk` enum('none','moderate','high') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `efsa_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Pamięć zapytań do wyszukiwarki OFF: to samo zapytanie przez tydzień nie idzie drugi raz do OFF (limity API).
CREATE TABLE IF NOT EXISTS `food_off_queries` (
  `query` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,             -- znormalizowane (hl_food_norm)
  `codes` text COLLATE utf8mb4_general_ci DEFAULT NULL,                 -- kody znalezionych produktów, po przecinku
  `fetched_at` datetime NOT NULL,
  PRIMARY KEY (`query`),
  KEY `fetched_at` (`fetched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dania domowe: produkt (source = 'dish') + skład. Wartości produktu przeliczane przy zapisie.
-- Widoczne dla autora i domowników (osoby ze wspólnych list).
CREATE TABLE IF NOT EXISTS `food_dishes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `owner_id` int NOT NULL,
  `cooked_g` decimal(8,1) DEFAULT NULL,                                 -- waga po ugotowaniu; NULL = suma składników
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product` (`product_id`),
  KEY `owner` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `food_dish_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `dish_id` int NOT NULL,
  `product_id` int NOT NULL,
  `grams` decimal(7,1) NOT NULL,
  `sort` smallint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `dish` (`dish_id`, `sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dziennik. Nazwa i makro zapisane jako kopia (dla całej porcji) – zmiana produktu nie zmienia historii.
CREATE TABLE IF NOT EXISTS `food_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `log_date` date NOT NULL,
  `meal` tinyint NOT NULL,                                              -- 1 śniadanie, 2 II śniadanie, 3 obiad, 4 podwieczorek, 5 kolacja
  `product_id` int DEFAULT NULL,                                        -- NULL = szybkie dodanie samych kcal
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `grams` decimal(7,1) DEFAULT NULL,
  `unit_label` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,    -- jednostka wybrana przy dodawaniu (do opisu „2 × kromka”)
  `unit_g` decimal(7,1) DEFAULT NULL,
  `kcal` decimal(7,1) NOT NULL,
  `protein` decimal(6,2) NOT NULL DEFAULT 0,
  `fat` decimal(6,2) NOT NULL DEFAULT 0,
  `carbs` decimal(6,2) NOT NULL DEFAULT 0,
  `source` enum('manual','quick','barcode','copy','ai') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual',
  `sort` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `user_day` (`user_id`, `log_date`, `meal`, `sort`),
  KEY `user_product` (`user_id`, `product_id`, `created_at`),
  KEY `user_sync` (`user_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cele z datą obowiązywania: zmiana celu nie przepisuje oceny minionych dni.
CREATE TABLE IF NOT EXISTS `food_goals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `valid_from` date NOT NULL,
  `kcal` smallint NOT NULL,
  `protein` smallint NOT NULL,
  `fat` smallint NOT NULL,
  `carbs` smallint NOT NULL,
  `calc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,         -- JSON danych z kalkulatora (płeć, wiek, wzrost, waga, aktywność, cel)
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_from` (`user_id`, `valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Moduł włączany per użytkownik (opt-in) + które posiłki pokazywać.
CREATE TABLE IF NOT EXISTS `food_settings` (
  `user_id` int NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `meals` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1,2,3,4,5',
  `show_on_today` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- nowy moduł uprawnień: baza produktów (read = podgląd zgłoszonych produktów, edit = akceptacja i poprawki, full = usuwanie)
ALTER TABLE `user_roles` ADD COLUMN `food` int NOT NULL DEFAULT 5 AFTER `reports`;
UPDATE `user_roles` SET `food` = 4 WHERE `settings` = 4;
