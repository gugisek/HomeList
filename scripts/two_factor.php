<?php

require_once __DIR__ . '/secrets.php';

// Szyfrowanie sekretu TOTP w spoczynku (AES-256-GCM), żeby zrzut/wyciek bazy
// sam w sobie nie pozwalał generować poprawnych kodów 2FA. Klucz żyje poza bazą,
// w gitignorowanym scripts/app_secrets.php (patrz plik, osobny na każdym środowisku).
// Format w kolumnie `secret`: "enc:" + base64(nonce . ciphertext . tag).
// Wartości bez prefiksu "enc:" są traktowane jako starsze, jeszcze niezaszyfrowane
// sekrety (analogicznie do isLegacySha256Hash() w password_helper.php) - dalej działają,
// a przy najbliższym udanym użyciu są przezroczyście domigrowywane (patrz verify_2fa.php).
function twoFactorIsEncryptedSecret(string $stored): bool
{
    return str_starts_with($stored, 'enc:');
}

function twoFactorEncryptSecret(string $plainSecret): string
{
    $key = base64_decode(hl_secret('two_factor_key'), true);
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plainSecret, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

    return 'enc:' . base64_encode($nonce . $ciphertext . $tag);
}

function twoFactorDecryptSecret(string $stored): ?string
{
    if (!twoFactorIsEncryptedSecret($stored)) {
        // Legacy: sekret zapisany jeszcze jawnym tekstem (base32).
        return $stored;
    }

    $raw = base64_decode(substr($stored, 4), true);
    if ($raw === false || strlen($raw) < 12 + 16) {
        return null;
    }

    $key = base64_decode(hl_secret('two_factor_key'), true);
    $nonce = substr($raw, 0, 12);
    $tag = substr($raw, -16);
    $ciphertext = substr($raw, 12, -16);

    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

    return $plain === false ? null : $plain;
}

function twoFactorGenerateSecret(int $length = 32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    $bytes = random_bytes($length);

    for ($i = 0; $i < $length; $i++) {
        $secret .= $alphabet[ord($bytes[$i]) % 32];
    }

    return $secret;
}

function twoFactorBase32Decode(string $secret): string
{
    $secret = strtoupper(preg_replace('/\s+/', '', $secret));
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = rtrim($secret, '=');

    $binaryString = '';
    $length = strlen($secret);

    for ($i = 0; $i < $length; $i++) {
        $char = $secret[$i];
        $position = strpos($alphabet, $char);
        if ($position === false) {
            return '';
        }
        $binaryString .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
    }

    $decoded = '';
    $binaryLength = strlen($binaryString);
    for ($i = 0; $i + 8 <= $binaryLength; $i += 8) {
        $decoded .= chr(bindec(substr($binaryString, $i, 8)));
    }

    return $decoded;
}

function twoFactorGenerateCode(string $secret, ?int $timeSlice = null, int $digits = 6, int $period = 30): ?string
{
    $timeSlice = $timeSlice ?? (int) floor(time() / $period);
    $key = twoFactorBase32Decode($secret);

    if ($key === '') {
        return null;
    }

    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hmac = hash_hmac('sha1', $time, $key, true);
    $offset = ord(substr($hmac, -1)) & 0x0F;
    $hashPart = substr($hmac, $offset, 4);

    $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
    $modulo = 10 ** $digits;
    $code = (string) ($value % $modulo);

    return str_pad($code, $digits, '0', STR_PAD_LEFT);
}

function twoFactorVerifyCode(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30): bool
{
    $cleanCode = preg_replace('/\D/', '', $code ?? '');
    if (strlen($cleanCode) !== $digits) {
        return false;
    }

    $timeSlice = (int) floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        $generated = twoFactorGenerateCode($secret, $timeSlice + $i, $digits, $period);
        if ($generated !== null && hash_equals($generated, $cleanCode)) {
            return true;
        }
    }

    return false;
}

function twoFactorBuildOtpAuthUri(string $secret, string $account, string $issuer = 'HomeList'): string
{
    $label = rawurlencode($issuer . ':' . $account);
    $issuerEncoded = rawurlencode($issuer);

    return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
}

function twoFactorGenerateBackupCodes(int $count = 8): array
{
    $plainCodes = [];
    $hashedCodes = [];

    for ($i = 0; $i < $count; $i++) {
        $raw = strtoupper(bin2hex(random_bytes(4)) . bin2hex(random_bytes(2)));
        $display = substr($raw, 0, 4) . '-' . substr($raw, 4, 4) . '-' . substr($raw, 8, 4);
        $plainCodes[] = $display;
        $hashedCodes[] = password_hash($display, PASSWORD_DEFAULT);
    }

    return [
        'plain' => $plainCodes,
        'hashed' => $hashedCodes,
    ];
}

function twoFactorNormalizeBackupCode(string $input): string
{
    $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $input));
    if (strlen($normalized) !== 12) {
        return strtoupper(trim($input));
    }

    return substr($normalized, 0, 4) . '-' . substr($normalized, 4, 4) . '-' . substr($normalized, 8, 4);
}
