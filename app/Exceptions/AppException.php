<?php
declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Excepción base para errores controlados de la aplicación.
 */
class AppException extends RuntimeException
{
    /**
     * Construye una excepción propia y conserva su causa técnica opcional.
     *
     * @param string $message Mensaje seguro destinado a la capa que presenta la respuesta.
     * @param int $code Código de error opcional.
     * @param Throwable|null $previous Excepción original, si este error envuelve otro.
     */
    public function __construct(
        string $message = 'Ocurrió un error en la aplicación.',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
