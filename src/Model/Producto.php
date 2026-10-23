<?php
declare(strict_types=1);

final class Producto
{
    public function __construct(
        public readonly int $id,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly string $categoria,
        public readonly float $precio,
        public readonly int $stock,
        public readonly int $stockMinimo,
        public readonly string $creadoEn,
    ) {
    }
}
