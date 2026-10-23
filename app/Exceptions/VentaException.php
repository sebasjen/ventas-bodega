<?php
declare(strict_types=1);

namespace App\Exceptions;

use Throwable;

/**
 * Representa un fallo técnico al completar una venta transaccional.
 */
final class VentaException extends AppException
{
    /**
     * Construye el error seguro y conserva el detalle técnico como causa interna.
     *
     * @param string $message Mensaje seguro que el Controller puede presentar.
     * @param int $code Código de error opcional.
     * @param Throwable|null $previous Causa técnica interna, si existe.
     */
    public function __construct(
        string $message = 'Error al procesar la venta. Intente nuevamente',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
