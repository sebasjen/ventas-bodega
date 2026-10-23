<?php
declare(strict_types=1);

final class Venta
{
    public function __construct(
        public readonly int $id,
        public readonly int $productoId,
        public readonly int $cantidad,
        public readonly float $precioUnit,
        public readonly float $total,
        public readonly string $vendidoEn,
        public readonly string $codigoProducto,
        public readonly string $nombreProducto,
    ) {
    }
}
