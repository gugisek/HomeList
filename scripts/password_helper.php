<?php
// Przejście ze starego hashowania (nienasolony sha256) na password_hash()/bcrypt – z panels-mOsiedle-v2.
// verifyPassword() rozpoznaje oba formaty; needsRehash() mówi, kiedy przepisać kolumnę pswd
// (robi to logowanie, więc nikt nie musi resetować hasła po wdrożeniu).

if (!function_exists('verifyPassword')) {
    function isLegacySha256Hash(string $hash): bool
    {
        return (bool)preg_match('/^[a-f0-9]{64}$/i', $hash);
    }

    function verifyPassword(string $plain, string $storedHash): bool
    {
        if (isLegacySha256Hash($storedHash)) {
            return hash_equals(strtolower($storedHash), hash('sha256', $plain));
        }
        return password_verify($plain, $storedHash);
    }

    function needsRehash(string $storedHash): bool
    {
        return isLegacySha256Hash($storedHash) || password_needs_rehash($storedHash, PASSWORD_DEFAULT);
    }

    function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    // Wspólna polityka haseł dla rejestracji, resetu, zmiany hasła i kont tworzonych przez admina.
    // Zwraca null gdy hasło jest OK, albo komunikat do pokazania użytkownikowi.
    function passwordPolicyViolation(string $password): ?string
    {
        $len = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : strlen($password);
        if ($len < 8) return 'Hasło musi mieć minimum 8 znaków';
        if ($len > 128) return 'Hasło może mieć maksymalnie 128 znaków';
        if (!preg_match('/[A-ZĄĆĘŁŃÓŚŹŻ]/u', $password)) return 'Hasło musi zawierać co najmniej jedną wielką literę';
        if (!preg_match('/[a-ząćęłńóśźż]/u', $password)) return 'Hasło musi zawierać co najmniej jedną małą literę';
        if (!preg_match('/[0-9]/', $password)) return 'Hasło musi zawierać co najmniej jedną cyfrę';
        return null;
    }
}
