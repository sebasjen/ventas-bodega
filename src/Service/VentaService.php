<?php
declare(strict_types=1);

final class VentaService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductoRepository $productoRepository,
        private readonly VentaRepository $ventaRepository,
    ) {
    }

    public function registrar(int $productoId, int $cantidad): Venta
    {
        if ($productoId <= 0) {
            throw new ValidationException('Selecciona un producto válido.');
        }
        if ($cantidad <= 0) {
            throw new ValidationException('La cantidad debe ser mayor a cero');
        }

        try {
            $this->pdo->beginTransaction();
            $producto = $this->productoRepository->findByIdForUpdate($productoId);
            if ($producto === null) {
                throw new ValidationException('El producto seleccionado no existe.');
            }
            if ($cantidad > $producto->stock) {
                throw new StockInsuficienteException($producto->stock);
            }

            $total = round($producto->precio * $cantidad, 2);
            $vendidoEn = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $ventaId = $this->ventaRepository->save(
                $producto->id,
                $cantidad,
                $producto->precio,
                $total,
                $vendidoEn,
            );

            if (!$this->productoRepository->decrementarStock($producto->id, $cantidad)) {
                throw new StockInsuficienteException($producto->stock);
            }

            $this->pdo->commit();

            return new Venta(
                $ventaId,
                $producto->id,
                $cantidad,
                $producto->precio,
                $total,
                $vendidoEn,
                $producto->codigo,
                $producto->nombre,
            );
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            if ($exception instanceof ValidationException
                || $exception instanceof StockInsuficienteException) {
                throw $exception;
            }

            if ($exception instanceof RepositoryException || $exception instanceof PDOException) {
                error_log($exception->getMessage());
            }

            throw new RuntimeException(
                'Error al procesar la venta. Intente nuevamente',
                0,
                $exception,
            );
        }
    }

    /** @return array{ventas: list<Venta>, total: float} */
    public function reporteHoy(): array
    {
        $fecha = (new DateTimeImmutable())->format('Y-m-d');

        return [
            'ventas' => $this->ventaRepository->findHoy($fecha),
            'total' => $this->ventaRepository->totalHoy($fecha),
        ];
    }
}
