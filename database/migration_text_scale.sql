-- Rozmiar tekstu w aplikacji per użytkownik (2.5.0) – Konto i ustawienia → Wygląd.
-- Procent (100 = standard); panel.php ustawia z niego --hl-fs na <html>, dozwolone wartości w scripts/users/text_scale.php.
ALTER TABLE `users`
  ADD COLUMN `text_scale` SMALLINT UNSIGNED NOT NULL DEFAULT 100 AFTER `feed_enabled`;
