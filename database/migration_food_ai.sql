-- „Policz ze zdjęcia” (2.1.5): czat AI, do którego prowadzi skrót w kreatorze – klucz z hl_food_ai_chats() (food_lib.php)
ALTER TABLE `food_settings`
  ADD COLUMN `ai_chat` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'chatgpt' AFTER `show_on_today`;
