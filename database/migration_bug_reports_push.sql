-- Push do administratorów o nowych zgłoszeniach błędów (1.8.2)

ALTER TABLE `bug_reports` ADD COLUMN `pushed_at` datetime DEFAULT NULL AFTER `notified_at`;   -- push do administratorów wysłany (cron)
UPDATE `bug_reports` SET `pushed_at` = NOW();                                               -- bez pushy o starych zgłoszeniach

-- przełącznik w Konto > Powiadomienia (widoczny tylko z dostępem do modułu zgłoszeń)
ALTER TABLE `push_settings` ADD COLUMN `notify_reports` tinyint NOT NULL DEFAULT 1 AFTER `notify_routines`;
