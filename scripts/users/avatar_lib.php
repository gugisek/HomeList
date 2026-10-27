<?php
// Zapis zdjęcia profilowego do img/users_images/. Typ ustalany z zawartości pliku (nie z nazwy),
// rozszerzenie nadawane przez serwer – nie da się wgrać skryptu udającego obrazek.

if (!function_exists('hl_save_avatar')) {
    // limit = 5 MB, ale nie więcej niż pozwala konfiguracja PHP serwera
    function hl_avatar_max_bytes(): int
    {
        return min(5 * 1048576, hl_ini_bytes('upload_max_filesize'), hl_ini_bytes('post_max_size'));
    }

    // tekst wymagań do formularzy ("JPG, PNG, GIF lub WEBP, maks. 5 MB")
    function hl_avatar_rules(): string
    {
        return 'JPG, PNG, GIF lub WEBP, maks. ' . hl_format_mb(hl_avatar_max_bytes());
    }

    // <input type="file"> z ograniczeniami dla walidacji w przeglądarce (imgPrevProduct w panel_body.php)
    function hl_avatar_input_attrs(): string
    {
        return 'accept="image/jpeg,image/png,image/gif,image/webp" data-max-bytes="' . hl_avatar_max_bytes()
            . '" data-rules="' . htmlspecialchars(hl_avatar_rules(), ENT_QUOTES, 'UTF-8') . '"';
    }

    // zwraca ['ok' => true, 'file' => nazwa] albo ['ok' => false, 'error' => komunikat]; null gdy nie wysłano pliku
    function hl_save_avatar(?array $upload, int $userId): ?array
    {
        if (!$upload || ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || ($upload['name'] ?? '') === '') return null;
        $tooBig = 'Plik jest za duży – zdjęcie może mieć maks. ' . hl_format_mb(hl_avatar_max_bytes());
        if ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE) {
            return ['ok' => false, 'error' => $tooBig];
        }
        if ($upload['error'] === UPLOAD_ERR_PARTIAL) {
            return ['ok' => false, 'error' => 'Plik nie został przesłany w całości – spróbuj ponownie'];
        }
        if ($upload['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'])) {
            return ['ok' => false, 'error' => 'Nie udało się przesłać pliku (błąd serwera)'];
        }
        if ($upload['size'] > hl_avatar_max_bytes()) {
            return ['ok' => false, 'error' => $tooBig];
        }
        $info = @getimagesize($upload['tmp_name']);
        $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        if (!$info || !isset($types[$info[2]])) {
            return ['ok' => false, 'error' => 'Nieobsługiwany format pliku – dozwolone: JPG, PNG, GIF lub WEBP'];
        }
        $file = 'pp-' . $userId . '_' . time() . '.' . $types[$info[2]];
        if (!move_uploaded_file($upload['tmp_name'], __DIR__ . '/../../img/users_images/' . $file)) {
            return ['ok' => false, 'error' => 'Nie udało się zapisać pliku'];
        }
        return ['ok' => true, 'file' => $file];
    }
}
