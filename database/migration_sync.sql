-- Synchronizacja list na żywo (1.9.1)
-- updated_at aktualizuje sama baza przy każdym UPDATE – scripts/sync/sync_lib.php wykrywa dzięki temu
-- edycje i zmiany statusów bez dopisywania czegokolwiek w endpointach. Indeksy = agregaty bez skanowania tabel.

ALTER TABLE `list_elements`
  ADD COLUMN `updated_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  ADD KEY `list_sync` (`list_id`, `updated_at`);

ALTER TABLE `day_plan`
  ADD COLUMN `updated_at` TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3);

ALTER TABLE `list_invities`
  ADD KEY `list_user` (`list_id`, `user_id`),
  ADD KEY `user_list` (`user_id`, `list_id`);
