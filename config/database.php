<?php
declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

/**
 * Crea conexiones PDO usando exclusivamente la configuración del archivo .env.
 */
final class Database
{
    /**
     * Crea una conexión PDO a MySQL con errores como excepciones y preparaciones nativas.
     *
     * @return PDO Conexión lista para ser inyectada en los repositorios.
     *
     * @throws RuntimeException Si falta .env, su contenido es inválido o faltan datos requeridos.
     * @throws \PDOException Si PDO no puede conectarse a MySQL.
     */
    public static function connect(): PDO
    {
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath) || !is_readable($envPath)) {
            throw new RuntimeException('No se encontró un archivo .env legible en la raíz del proyecto.');
        }

        $envContents = file_get_contents($envPath);
        if ($envContents === false) {
            throw new RuntimeException('No se pudo leer la configuración de la base de datos.');
        }

        $configuration = parse_ini_string($envContents, false, INI_SCANNER_RAW);
        if ($configuration === false) {
            throw new RuntimeException('El archivo .env tiene un formato inválido.');
        }

        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            if (!array_key_exists($key, $configuration) || !is_string($configuration[$key])) {
                throw new RuntimeException("Falta la variable {$key} en el archivo .env.");
            }
        }

        foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $key) {
            if (trim($configuration[$key]) === '') {
                throw new RuntimeException("La variable {$key} no puede estar vacía en .env.");
            }
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $configuration['DB_HOST'],
            $configuration['DB_NAME'],
        );

        return new PDO($dsn, $configuration['DB_USER'], $configuration['DB_PASS'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
