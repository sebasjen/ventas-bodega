<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Venta;
use PDO;
use PDOException;

/**
 * Ejecuta consultas SQL preparadas para registrar y consultar ventas.
 */
final class VentaRepository
{
    /**
     * Recibe la conexión PDO compartida con los demás repositorios.
     *
     * @param PDO $pdo Conexión configurada para lanzar excepciones de base de datos.
     */
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Inserta una venta con su precio histórico, total y fecha proporcionada.
     *
     * La transacción que agrupa esta inserción con el descuento del stock
     * corresponde al Service que orquesta el proceso.
     *
     * @param int $productoId Identificador del producto vendido.
     * @param int $cantidad Unidades incluidas en la venta.
     * @param float $precioUnit Precio por unidad al momento de la venta.
     * @param float $total Importe total de la venta.
     * @param string $vendidoEn Fecha y hora de la venta en formato DATETIME.
     *
     * @return int Identificador generado para la venta.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function save(
        int $productoId,
        int $cantidad,
        float $precioUnit,
        float $total,
        string $vendidoEn,
    ): int {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO venta (producto_id, cantidad, precio_unit, total, vendido_en)
                 VALUES (:producto_id, :cantidad, :precio_unit, :total, :vendido_en)'
            );
            $statement->bindValue(':producto_id', $productoId, PDO::PARAM_INT);
            $statement->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
            $statement->bindValue(':precio_unit', number_format($precioUnit, 2, '.', ''), PDO::PARAM_STR);
            $statement->bindValue(':total', number_format($total, 2, '.', ''), PDO::PARAM_STR);
            $statement->bindValue(':vendido_en', $vendidoEn, PDO::PARAM_STR);
            $statement->execute();

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo guardar la venta.', 0, $exception);
        }
    }

    /**
     * Busca las ventas dentro del intervalo recibido y agrega los datos del producto.
     *
     * @param string $inicio Límite inferior inclusivo en formato DATETIME.
     * @param string $fin Límite superior exclusivo en formato DATETIME.
     *
     * @return list<Venta> Ventas encontradas; la lista vacía indica que no hay registros.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function findBetween(string $inicio, string $fin): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT v.id, v.producto_id, v.cantidad, v.precio_unit, v.total, v.vendido_en,
                        p.codigo AS codigo_producto, p.nombre AS nombre_producto
                 FROM venta AS v
                 INNER JOIN producto AS p ON p.id = v.producto_id
                 WHERE v.vendido_en >= :inicio AND v.vendido_en < :fin
                 ORDER BY v.vendido_en DESC, v.id DESC'
            );
            $statement->bindValue(':inicio', $inicio, PDO::PARAM_STR);
            $statement->bindValue(':fin', $fin, PDO::PARAM_STR);
            $statement->execute();

            $sales = [];
            while (($row = $statement->fetch()) !== false) {
                $sales[] = $this->hydrate($row);
            }

            return $sales;
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo consultar el reporte de ventas.', 0, $exception);
        }
    }

    /**
     * Suma los importes de las ventas dentro del intervalo recibido.
     *
     * @param string $inicio Límite inferior inclusivo en formato DATETIME.
     * @param string $fin Límite superior exclusivo en formato DATETIME.
     *
     * @return float Importe acumulado; devuelve 0.0 si no hay ventas en el intervalo.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function totalBetween(string $inicio, string $fin): float
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT COALESCE(SUM(total), 0)
                 FROM venta
                 WHERE vendido_en >= :inicio AND vendido_en < :fin'
            );
            $statement->bindValue(':inicio', $inicio, PDO::PARAM_STR);
            $statement->bindValue(':fin', $fin, PDO::PARAM_STR);
            $statement->execute();
            $total = $statement->fetchColumn();

            return $total === false ? 0.0 : (float) $total;
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo calcular el total de ventas.', 0, $exception);
        }
    }

    /**
     * Convierte una fila de venta y producto en una entidad de dominio.
     *
     * @param array<string, mixed> $row Fila con columnas de venta y producto.
     *
     * @return Venta Venta hidratada desde la consulta.
     */
    private function hydrate(array $row): Venta
    {
        return new Venta(
            (int) $row['id'],
            (int) $row['producto_id'],
            (int) $row['cantidad'],
            (float) $row['precio_unit'],
            (float) $row['total'],
            (string) $row['vendido_en'],
            (string) $row['codigo_producto'],
            (string) $row['nombre_producto'],
        );
    }

    /**
     * Registra el detalle técnico sin exponerlo a la respuesta HTTP.
     *
     * @param PDOException $exception Excepción técnica generada por PDO.
     *
     * @return void
     */
    private function logDatabaseError(PDOException $exception): void
    {
        error_log($exception->getMessage());
    }
}
