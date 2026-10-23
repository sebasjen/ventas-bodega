<?php
declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Representa un fallo al consultar o modificar datos persistidos.
 */
final class RepositoryException extends AppException
{
    /**
     * Construye un error de persistencia y conserva su causa técnica opcional.
     *
     * @param string $message Mensaje seguro para las capas de la aplicación.
     * @param int $code Código de error opcional.
     * @param Throwable|null $previous Excepción PDO original, si existe.
     */
    public function __construct(
        string $message = 'No se pudo completar la operación de base de datos.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
