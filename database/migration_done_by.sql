-- Kto oznaczył zadanie jako zrobione (albo zarchiwizował) – uzupełnia done_date; szczegóły zadania na listach wspólnych.
ALTER TABLE `list_elements`
  ADD COLUMN `done_by` INT(11) NULL DEFAULT NULL AFTER `done_date`;

-- Istniejące zadania: autor ostatniej zmiany na obecny status z tabeli logs.
UPDATE `list_elements` e
SET e.`done_by` = (
    SELECT l.`user_id` FROM `logs` l
    WHERE l.`object_type` = 'ListElement' AND l.`object_id` = e.`id`
      AND l.`after` = CONCAT('Status: ', e.`status_id`) AND l.`user_id` > 0
    ORDER BY l.`id` DESC LIMIT 1
),
e.`updated_at` = e.`updated_at`
WHERE e.`status_id` IN (2, 4);
