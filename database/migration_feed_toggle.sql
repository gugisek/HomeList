-- Plan dnia (zakładka Dziś) włączany per użytkownik (2.5.0) – jak licznik kalorii: nowe konta zaczynają bez niego,
-- włączają go z menu „więcej”, a wyłączają w Konto i ustawienia → Plan dnia.
ALTER TABLE `users`
  ADD COLUMN `feed_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `last_log`;

-- istniejące konta już korzystają z planu dnia – zostaje im włączony
UPDATE `users` SET `feed_enabled` = 1;
