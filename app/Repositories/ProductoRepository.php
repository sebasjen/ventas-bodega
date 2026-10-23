<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\RepositoryException;
use App\Models\Producto;
use PDO;
use PDOException;

/**
 * Ejecuta consultas SQL preparadas para productos, sin aplicar reglas de negocio.
 */
final class ProductoRepository
{
    /**
     * Recibe la conexión PDO compartida por la aplicación.
     *
     * @param PDO $pdo Conexión configurada para lanzar excepciones de base de datos.
     */
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * Busca todos los productos ordenados por nombre y código.
     *
     * @return list<Producto> Productos encontrados; la lista vacía indica que no hay registros.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function findAll(): array
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, codigo, nombre, categoria, precio, stock, stock_minimo, creado_en
                 FROM producto
                 ORDER BY nombre, codigo'
            );
            $statement->execute();

            $products = [];
            while (($row = $statement->fetch()) !== false) {
                $products[] = $this->hydrate($row);
            }

            return $products;
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo consultar el inventario.', 0, $exception);
        }
    }

    /**
     * Busca un producto por su identificador.
     *
     * @param int $id Identificador del producto.
     *
     * @return Producto|null El producto encontrado o null si no existe.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function findById(int $id): ?Producto
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, codigo, nombre, categoria, precio, stock, stock_minimo, creado_en
                 FROM producto
                 WHERE id = :id'
            );
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            $row = $statement->fetch();

            return $row === false ? null : $this->hydrate($row);
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo consultar el producto.', 0, $exception);
        }
    }

    /**
     * Busca un producto por su código único.
     *
     * @param string $codigo Código exacto del producto.
     *
     * @return Producto|null El producto encontrado o null si no existe.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function findByCodigo(string $codigo): ?Producto
    {
        try {
            $statement = $this->pdo->prepare(
                'SELECT id, codigo, nombre, categoria, precio, stock, stock_minimo, creado_en
                 FROM producto
                 WHERE codigo = :codigo'
            );
            $statement->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            $statement->execute();
            $row = $statement->fetch();

            return $row === false ? null : $this->hydrate($row);
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo consultar el producto.', 0, $exception);
        }
    }

    /**
     * Inserta un producto y devuelve el identificador asignado por la base de datos.
     *
     * @param string $codigo Código único del producto.
     * @param string $nombre Nombre del producto.
     * @param string $categoria Categoría del producto.
     * @param float $precio Precio unitario de venta.
     * @param int $stock Existencias iniciales.
     * @param int $stockMinimo Umbral de alerta de existencias.
     *
     * @return int Identificador generado para el nuevo producto.
     *
     * @throws RepositoryException Si ocurre un error de base de datos, incluida una restricción UNIQUE.
     */
    public function save(
        string $codigo,
        string $nombre,
        string $categoria,
        float $precio,
        int $stock,
        int $stockMinimo,
    ): int {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO producto (codigo, nombre, categoria, precio, stock, stock_minimo)
                 VALUES (:codigo, :nombre, :categoria, :precio, :stock, :stock_minimo)'
            );
            $statement->bindValue(':codigo', $codigo, PDO::PARAM_STR);
            $statement->bindValue(':nombre', $nombre, PDO::PARAM_STR);
            $statement->bindValue(':categoria', $categoria, PDO::PARAM_STR);
            $statement->bindValue(':precio', number_format($precio, 2, '.', ''), PDO::PARAM_STR);
            $statement->bindValue(':stock', $stock, PDO::PARAM_INT);
            $statement->bindValue(':stock_minimo', $stockMinimo, PDO::PARAM_INT);
            $statement->execute();

            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo guardar el producto.', 0, $exception);
        }
    }

    /**
     * Descuenta existencias solo si el stock actual alcanza para la cantidad solicitada.
     *
     * @param int $id Identificador del producto que se actualizará.
     * @param int $cantidad Unidades que se intentan descontar.
     *
     * @return bool true si la actualización afectó una fila; false si no se actualizó ninguna.
     *
     * @throws RepositoryException Si ocurre un error de base de datos.
     */
    public function decrementStock(int $id, int $cantidad): bool
    {
        try {
            $statement = $this->pdo->prepare(
                'UPDATE producto
                 SET stock = stock - :cantidad_restar
                 WHERE id = :id AND stock >= :stock_requerido'
            );
            $statement->bindValue(':cantidad_restar', $cantidad, PDO::PARAM_INT);
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
            $statement->bindValue(':stock_requerido', $cantidad, PDO::PARAM_INT);
            $statement->execute();

            return $statement->rowCount() === 1;
        } catch (PDOException $exception) {
            $this->logDatabaseError($exception);
            throw new RepositoryException('No se pudo actualizar el stock.', 0, $exception);
        }
    }

    /**
     * Convierte una fila de PDO en una entidad de producto.
     *
     * @param array<string, mixed> $row Fila con columnas del producto.
     *
     * @return Producto Entidad hidratada desde la fila consultada.
     */
    private function hydrate(array $row): Producto
    {
        return new Producto(
            (int) $row['id'],
            (string) $row['codigo'],
            (string) $row['nombre'],
            (string) $row['categoria'],
            (float) $row['precio'],
            (int) $row['stock'],
            (int) $row['stock_minimo'],
            (string) $row['creado_en'],
        );
    }

    /**
     * Registra el detalle técnico sin mostrarlo en una respuesta al usuario.
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
