<?php
declare(strict_types=1);

namespace App\Controllers;

use RuntimeException;

/**
 * Genera y valida tokens CSRF almacenados en la sesión actual.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    /**
     * Devuelve el token CSRF de la sesión o crea uno criptográficamente seguro.
     *
     * @return string Token hexadecimal de 64 caracteres para formularios HTML.
     *
     * @throws RuntimeException Si la sesión no está activa o falla la generación aleatoria.
     */
    public static function token(): string
    {
        self::assertSessionIsActive();

        $token = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    /**
     * Compara de forma segura un token enviado con el almacenado en la sesión.
     *
     * @param mixed $submittedToken Valor recibido del formulario, aún no confiable.
     *
     * @return bool true si ambos tokens coinciden; false en cualquier otro caso.
     *
     * @throws RuntimeException Si la sesión no está activa.
     */
    public static function verify(mixed $submittedToken): bool
    {
        self::assertSessionIsActive();

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_string($submittedToken) || !is_string($sessionToken)) {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    /**
     * Impide usar el mecanismo CSRF antes de iniciar la sesión HTTP.
     *
     * @return void
     *
     * @throws RuntimeException Si no hay una sesión activa.
     */
    private static function assertSessionIsActive(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new RuntimeException('La sesión debe iniciarse antes de usar la protección CSRF.');
        }
    }
}
