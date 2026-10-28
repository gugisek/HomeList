-- Opłaty (2.3.0): subskrypcje, abonamenty/umowy, numery na kartę, raty/kredyty, karnety, ubezpieczenia, rachunki.
-- Opłata = definicja cyklu (bills); każda płatność to osobny wiersz z faktyczną kwotą (bill_payments) – z nich liczone są statystyki.
-- Kwoty w złotych (currency na przyszłość), daty płatności = termin z harmonogramu (due_date), nie dzień kliknięcia.

CREATE TABLE IF NOT EXISTS `bills` (
  `id` int NOT NULL AUTO_INCREMENT,
  `owner_id` int NOT NULL,
  `household_id` int DEFAULT NULL,                                      -- pod przyszły moduł „Dom” (wspólne opłaty); na razie zawsze NULL
  `type` enum('sub','plan','prepaid','loan','pass','ins','bill','other') COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ref` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,            -- numer telefonu, numer umowy/polisy
  `amount` decimal(10,2) NOT NULL,                                      -- aktualna kwota (rata, pakiet, abonament); przy zmiennej = szacunek
  `amount_variable` tinyint(1) NOT NULL DEFAULT 0,                      -- rachunki: kwotę podaje się przy opłaceniu
  `currency` char(3) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'PLN',
  `cycle_unit` enum('day','week','month','year') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'month',
  `cycle_every` smallint NOT NULL DEFAULT 1,                            -- kwartał = month × 3, pakiet 30 dni = day × 30
  `anchor` enum('schedule','last_paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'schedule', -- last_paid: następny termin liczony od ostatniej płatności (na kartę)
  `start_date` date NOT NULL,                                           -- termin pierwszej płatności (także wstecz – „płacę od”)
  `end_date` date DEFAULT NULL,                                         -- termin ostatniej płatności, jeśli znany
  `pay_mode` enum('auto','manual') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual', -- auto = pobiera się sama, tylko informujemy
  `status` enum('active','paused','ended') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `ended_at` date DEFAULT NULL,
  `reminders` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '1', -- ile dni przed terminem przypomnieć, po przecinku (0 = w dniu)
  -- subskrypcja
  `trial_until` date DEFAULT NULL,
  `review_at` date DEFAULT NULL,                                        -- ostatni przegląd „czy dalej korzystasz?”
  -- umowa / ubezpieczenie
  `contract_until` date DEFAULT NULL,                                   -- koniec umowy / ochrony
  `notice_months` tinyint DEFAULT NULL,                                 -- okres wypowiedzenia
  `promo_until` date DEFAULT NULL,
  `price_after` decimal(10,2) DEFAULT NULL,                             -- cena po promocji / po umowie
  `auto_renew` tinyint(1) DEFAULT NULL,
  `contract_plan` enum('renew','quit','open') COLLATE utf8mb4_general_ci DEFAULT NULL, -- decyzja „co dalej z umową”
  -- rata / kredyt
  `installments` smallint DEFAULT NULL,                                 -- liczba rat (end_date wynika z niej)
  `loan_rate` decimal(5,2) DEFAULT NULL,                                -- oprocentowanie / RRSO, informacyjnie
  -- karnet
  `pass_entries` smallint DEFAULT NULL,                                 -- NULL = open, liczba = wejść w okresie
  `single_price` decimal(8,2) DEFAULT NULL,                             -- cena pojedynczego wejścia (porównanie „czy się opłaca”)
  `sort` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  KEY `owner_status` (`owner_id`, `status`),
  KEY `household` (`household_id`),
  KEY `owner_sync` (`owner_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Płatności (także pominięte). Jeden wiersz na termin; amount = łącznie z dodatkami.
CREATE TABLE IF NOT EXISTS `bill_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `due_date` date NOT NULL,
  `paid_on` date DEFAULT NULL,                                          -- faktyczny dzień płatności (na kartę: od niego liczy się ważność)
  `amount` decimal(10,2) NOT NULL DEFAULT 0,
  `status` enum('paid','skipped') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'paid',
  `source` enum('manual','auto','estimate') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual', -- auto = dopisana sama (pay_mode auto), estimate = uzupełniona wstecz
  `user_id` int DEFAULT NULL,                                           -- kto odhaczył (pod wspólne opłaty)
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_due` (`bill_id`, `due_date`),
  KEY `bill_paid` (`bill_id`, `paid_on`),
  KEY `bill_sync` (`bill_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dodatki do płatności (na kartę: dokupiony internet, roaming…) – kwoty są już wliczone w bill_payments.amount
CREATE TABLE IF NOT EXISTS `bill_payment_extras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payment_id` int NOT NULL,
  `label` varchar(80) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Historia ceny (pierwszy wiersz przy dodaniu opłaty) – podwyżki w statystykach i kwota dopisywana wstecz
CREATE TABLE IF NOT EXISTS `bill_prices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `valid_from` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_from` (`bill_id`, `valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Wejścia na karnet („Odhacz wejście”) – koszt jednego treningu
CREATE TABLE IF NOT EXISTS `bill_visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `bill_id` int NOT NULL,
  `user_id` int NOT NULL,
  `visit_date` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bill_date` (`bill_id`, `visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- przypomnienia o opłatach (push) – przełącznik w Konto > Powiadomienia
ALTER TABLE `push_settings` ADD COLUMN `notify_bills` tinyint NOT NULL DEFAULT 1;
