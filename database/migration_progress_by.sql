-- Kto i kiedy ustawił status „W trakcie” – zostaje też po przejściu na „Zrobione”, zeruje się przy powrocie do „Oczekuje”.
ALTER TABLE `list_elements`
  ADD COLUMN `progress_date` DATETIME NULL DEFAULT NULL AFTER `done_by`,
  ADD COLUMN `progress_by` INT(11) NULL DEFAULT NULL AFTER `progress_date`;

-- Istniejące zadania: ostatnie „W trakcie” z tabeli logs, o ile później nie wróciło do „Oczekuje”.
UPDATE `list_elements` e
JOIN (
    SELECT `object_id`, MAX(`id`) AS `log_id` FROM `logs`
    WHERE `object_type` = 'ListElement' AND `after` = 'Status: 3'
    GROUP BY `object_id`
) p ON p.`object_id` = e.`id`
JOIN `logs` l ON l.`id` = p.`log_id`
LEFT JOIN `logs` later ON later.`object_type` = 'ListElement' AND later.`object_id` = e.`id`
    AND later.`after` = 'Status: 1' AND later.`id` > l.`id`
SET e.`progress_date` = l.`when`,
    e.`progress_by` = l.`user_id`,
    e.`updated_at` = e.`updated_at`
WHERE e.`status_id` IN (2, 3, 4) AND later.`id` IS NULL AND l.`user_id` > 0;
