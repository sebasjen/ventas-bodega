<?php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\CodigoDuplicadoException;
use App\Exceptions\RepositoryException;
use App\Exceptions\ValidationException;
use App\Models\Producto;
use App\Repositories\ProductoRepository;
use PDOException;

/**
 * Aplica las reglas de negocio para registrar y consultar productos.
 */
final class ProductoService
{
    /**
     * Recibe el repositorio utilizado para persistir y consultar productos.
     *
     * @param ProductoRepository $productoRepository Acceso a datos de productos.
     */
    public function __construct(
        private readonly ProductoRepository $productoRepository,
    ) {
    }

    /**
     * Valida los datos de un producto y persiste el nuevo registro.
     *
     * @param string $codigo Código único del producto.
     * @param string $nombre Nombre descriptivo del producto.
     * @param string $categoria Categoría del producto.
     * @param float $precio Precio de venta, que debe ser mayor a cero.
     * @param int $stock Existencias iniciales, que no pueden ser negativas.
     * @param int $stockMinimo Umbral de existencias, que no puede ser negativo.
     *
     * @return Producto Producto persistido.
     *
     * @throws ValidationException Si algún campo obligatorio o valor no cumple las reglas.
     * @throws CodigoDuplicadoException Si ya existe un producto con el mismo código.
     * @throws RepositoryException Si ocurre un error de persistencia o no puede recuperarse el registro.
     */
    public function registrar(
        string $codigo,
        string $nombre,
        string $categoria,
        float $precio,
        int $stock,
        int $stockMinimo,
    ): Producto {
        $codigo = trim($codigo);
        $nombre = trim($nombre);
        $categoria = trim($categoria);

        if ($codigo === '' || $nombre === '' || $categoria === '') {
            throw new ValidationException('Completa todos los campos obligatorios.');
        }

        if (mb_strlen($codigo) > 50 || mb_strlen($nombre) > 150 || mb_strlen($categoria) > 100) {
            throw new ValidationException('Uno o más campos superan la longitud permitida.');
        }

        if (!is_finite($precio) || $precio <= 0) {
            throw new ValidationException('El precio debe ser mayor a cero');
        }

        if ($precio > 99999999.99) {
            throw new ValidationException('El precio supera el máximo permitido.');
        }

        if ($stock < 0 || $stockMinimo < 0) {
            throw new ValidationException('El stock no puede ser negativo');
        }

        if ($this->productoRepository->findByCodigo($codigo) !== null) {
            throw new CodigoDuplicadoException();
        }

        try {
            $productoId = $this->productoRepository->save(
                $codigo,
                $nombre,
                $categoria,
                $precio,
                $stock,
                $stockMinimo,
            );
        } catch (RepositoryException $exception) {
            $cause = $exception->getPrevious();
            if (
                $cause instanceof PDOException
                && isset($cause->errorInfo[1])
                && (int) $cause->errorInfo[1] === 1062
            ) {
                throw new CodigoDuplicadoException();
            }

            throw $exception;
        }

        return $this->productoRepository->findById($productoId)
            ?? throw new RepositoryException('No se pudo recuperar el producto registrado.');
    }

    /**
     * Obtiene el inventario completo para la presentación.
     *
     * El estado de stock mínimo se deriva de los campos del producto en la vista,
     * sin modificar ni clasificar los datos en el repositorio.
     *
     * @return list<Producto> Productos ordenados por el repositorio; puede estar vacío.
     *
     * @throws RepositoryException Si ocurre un error al consultar la base de datos.
     */
    public function listar(): array
    {
        return $this->productoRepository->findAll();
    }
}
