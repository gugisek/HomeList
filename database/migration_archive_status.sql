-- Migration: status "archived" for list_elements
-- Zadanie zarchiwizowane ręcznie (bez oznaczania jako zrobione) trafia od razu do archiwum.
-- done_date przechowuje wtedy datę archiwizacji.

INSERT IGNORE INTO `element_status` (`id`, `name`) VALUES (4, 'archived');
