<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Representa un producto persistido del inventario.
 */
final class Producto
{
    /**
     * Construye una entidad de producto con los datos almacenados.
     *
     * @param int $id Identificador asignado por la base de datos.
     * @param string $codigo Código único del producto.
     * @param string $nombre Nombre visible del producto.
     * @param string $categoria Categoría a la que pertenece.
     * @param float $precio Precio de venta por unidad.
     * @param int $stock Unidades disponibles.
     * @param int $stockMinimo Umbral de alerta para reponer existencias.
     * @param string $creadoEn Fecha y hora de creación en formato DATETIME.
     */
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
