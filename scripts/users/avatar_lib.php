<?php
// Zapis zdjęcia profilowego do img/users_images/. Typ ustalany z zawartości pliku (nie z nazwy),
// rozszerzenie nadawane przez serwer – nie da się wgrać skryptu udającego obrazek.

if (!function_exists('hl_save_avatar')) {
    // zwraca ['ok' => true, 'file' => nazwa] albo ['ok' => false, 'error' => komunikat]; null gdy nie wysłano pliku
    function hl_save_avatar(?array $upload, int $userId): ?array
    {
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($upload['name'] ?? '') === '') return null;
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            return ['ok' => false, 'error' => 'Nie udało się przesłać pliku'];
        }
        if ($upload['size'] > 5000000) {
            return ['ok' => false, 'error' => 'Plik jest za duży (maks. 5 MB)'];
        }
        $info = @getimagesize($upload['tmp_name']);
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($types[$info[2]])) {
            return ['ok' => false, 'error' => 'Nieprawidłowy format pliku (JPG, PNG, GIF lub WEBP)'];
        }
        $file = 'pp-' . $userId . '_' . time() . '.' . $types[$info[2]];
        if (!move_uploaded_file($upload['tmp_name'], __DIR__ . '/../../img/users_images/' . $file)) {
            return ['ok' => false, 'error' => 'Nie udało się zapisać pliku'];
        }
        return ['ok' => true, 'file' => $file];
    }
}
