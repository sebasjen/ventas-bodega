<?php
declare(strict_types=1);

namespace App\Models;

/**
 * Representa una venta y los datos del producto necesarios para mostrarla.
 */
final class Venta
{
    /**
     * Construye una venta con su precio histórico y la información visible del producto.
     *
     * @param int $id Identificador asignado por la base de datos.
     * @param int $productoId Identificador del producto vendido.
     * @param int $cantidad Unidades incluidas en la venta.
     * @param float $precioUnit Precio por unidad al momento de la venta.
     * @param float $total Importe total de la línea de venta.
     * @param string $vendidoEn Fecha y hora de registro en formato DATETIME.
     * @param string $codigoProducto Código del producto para resumen y reporte.
     * @param string $nombreProducto Nombre del producto para resumen y reporte.
     */
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
