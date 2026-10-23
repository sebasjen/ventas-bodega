<?php
declare(strict_types=1);

final class VentaRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

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
            throw $this->repositoryError('No se pudo registrar la venta.', $exception);
        }
    }

    /** @return list<Venta> */
    public function findHoy(string $fecha): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT v.id, v.producto_id, v.cantidad, v.precio_unit, v.total, v.vendido_en,
                        p.codigo AS codigo_producto, p.nombre AS nombre_producto
                 FROM venta v
                 INNER JOIN producto p ON p.id = v.producto_id
                 WHERE v.vendido_en >= :inicio AND v.vendido_en < :fin
                 ORDER BY v.vendido_en DESC, v.id DESC'
            );
            $statement->bindValue(':inicio', $fecha . ' 00:00:00', PDO::PARAM_STR);
            $statement->bindValue(
                ':fin',
                (new DateTimeImmutable($fecha))->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
                PDO::PARAM_STR,
            );
            $statement->execute();

            return array_map(
                fn (array $row): Venta => $this->hydrate($row),
                $statement->fetchAll(),
            );
        } catch (PDOException $exception) {
            throw $this->repositoryError('No se pudo consultar el reporte diario.', $exception);
        }
    }

    public function totalHoy(string $fecha): float
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT COALESCE(SUM(total), 0)
                 FROM venta
                 WHERE vendido_en >= :inicio AND vendido_en < :fin'
            );
            $statement->bindValue(':inicio', $fecha . ' 00:00:00', PDO::PARAM_STR);
            $statement->bindValue(
                ':fin',
                (new DateTimeImmutable($fecha))->modify('+1 day')->format('Y-m-d') . ' 00:00:00',
                PDO::PARAM_STR,
            );
            $statement->execute();

            return (float) $statement->fetchColumn();
        } catch (PDOException $exception) {
            throw $this->repositoryError('No se pudo calcular el total diario.', $exception);
        }
    }

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

    private function repositoryError(string $safeMessage, PDOException $exception): RepositoryException
    {
        error_log($exception->getMessage());

        return new RepositoryException($safeMessage, (int) $exception->getCode(), $exception);
    }
}
