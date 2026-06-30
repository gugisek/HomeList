-- Migration: Add link_url to list_elements
-- Run this once to add the link attachment feature

ALTER TABLE `list_elements`
  ADD COLUMN `link_url` VARCHAR(2000) DEFAULT NULL AFTER `done_date`;
