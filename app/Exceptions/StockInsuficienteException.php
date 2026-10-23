<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Indica que la cantidad solicitada supera las existencias disponibles.
 */
final class StockInsuficienteException extends ValidationException
{
    /**
     * Construye el error con el stock disponible para mostrar al cajero.
     *
     * @param int $disponible Unidades disponibles en el momento de validar la venta.
     */
    public function __construct(
        public readonly int $disponible,
    ) {
        parent::__construct(
            sprintf('Stock insuficiente. Disponible: %d unidades', $disponible),
        );
    }
}
