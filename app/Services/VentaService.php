<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\RepositoryException;
use App\Exceptions\StockInsuficienteException;
use App\Exceptions\ValidationException;
use App\Exceptions\VentaException;
use App\Models\Venta;
use App\Repositories\ProductoRepository;
use App\Repositories\VentaRepository;
use DateTimeImmutable;
use PDO;
use Throwable;

/**
 * Aplica las reglas de negocio de ventas y coordina la transacción de stock.
 */
final class VentaService
{
    /**
     * Recibe la conexión transaccional y los repositorios participantes.
     *
     * @param PDO $pdo Conexión compartida por ambas escrituras.
     * @param ProductoRepository $productoRepository Repositorio que consulta y descuenta stock.
     * @param VentaRepository $ventaRepository Repositorio que registra y consulta ventas.
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly ProductoRepository $productoRepository,
        private readonly VentaRepository $ventaRepository,
    ) {
    }

    /**
     * Registra una venta y descuenta el stock dentro de una única transacción.
     *
     * @param int $productoId Identificador del producto seleccionado.
     * @param int $cantidad Unidades a vender, que deben ser mayores a cero.
     *
     * @return Venta Venta confirmada con su resumen de producto.
     *
     * @throws ValidationException Si la cantidad o el producto seleccionado no son válidos.
     * @throws StockInsuficienteException Si el stock disponible no alcanza.
     * @throws VentaException Si falla una escritura, el commit o el control transaccional.
     */
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

            $producto = $this->productoRepository->findById($productoId);
            if ($producto === null) {
                throw new ValidationException('El producto seleccionado no existe.');
            }

            if ($cantidad > $producto->stock) {
                throw new StockInsuficienteException($producto->stock);
            }

            $total = round($producto->precio * $cantidad, 2);
            if (!is_finite($total) || $total > 99999999.99) {
                throw new ValidationException('El total de la venta supera el máximo permitido.');
            }

            $vendidoEn = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $ventaId = $this->ventaRepository->save(
                $producto->id,
                $cantidad,
                $producto->precio,
                $total,
                $vendidoEn,
            );

            if (!$this->productoRepository->decrementStock($producto->id, $cantidad)) {
                $this->pdo->rollBack();
                $productoActual = $this->productoRepository->findById($producto->id);
                $disponible = $productoActual?->stock ?? 0;
                throw new StockInsuficienteException($disponible);
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
        } catch (ValidationException | StockInsuficienteException $exception) {
            $this->rollbackSafely($exception);
            throw $exception;
        } catch (Throwable $exception) {
            $this->rollbackSafely($exception);
            error_log($exception->getMessage());

            throw new VentaException(
                'Error al procesar la venta. Intente nuevamente',
                0,
                $exception,
            );
        }
    }

    /**
     * Devuelve las ventas y el total del día calendario del servidor.
     *
     * @return array{ventas: list<Venta>, total: float} Reporte diario y suma acumulada.
     *
     * @throws RepositoryException Si ocurre un error al consultar ventas o totales.
     */
    public function reporteHoy(): array
    {
        $inicioDelDia = new DateTimeImmutable('today');
        $finDelDia = $inicioDelDia->modify('+1 day');
        $inicio = $inicioDelDia->format('Y-m-d H:i:s');
        $fin = $finDelDia->format('Y-m-d H:i:s');

        return [
            'ventas' => $this->ventaRepository->findBetween($inicio, $fin),
            'total' => $this->ventaRepository->totalBetween($inicio, $fin),
        ];
    }

    /**
     * Intenta revertir una transacción activa sin ocultar la excepción original.
     *
     * @param Throwable $cause Excepción que motivó el rollback.
     *
     * @return void
     */
    private function rollbackSafely(Throwable $cause): void
    {
        if (!$this->pdo->inTransaction()) {
            return;
        }

        try {
            $this->pdo->rollBack();
        } catch (Throwable $rollbackException) {
            error_log($rollbackException->getMessage());
            error_log('No fue posible revertir la transacción tras: ' . $cause->getMessage());
        }
    }
}
