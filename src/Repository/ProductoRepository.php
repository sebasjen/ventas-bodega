<?php
declare(strict_types=1);

final class ProductoRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<Producto> */
    public function findAll(): array
    {
        try {
            $statement = $this->pdo->query(
                'SELECT id, codigo, nombre, categoria, precio, stock, stock_minimo, creado_en
                 FROM producto
                 ORDER BY nombre, id'
            );

            return array_map(
                fn (array $row): Producto => $this->hydrate($row),
                $statement->fetchAll(),
            );
        } catch (PDOException $exception) {
            throw $this->repositoryError('No se pudo listar productos.', $exception);
        }
    }

    public function findById(int $id): ?Producto
    {
        return $this->findOneById($id, false);
    }

    public function findByIdForUpdate(int $id): ?Producto
    {
        return $this->findOneById($id, true);
    }

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
            throw $this->repositoryError('No se pudo buscar el producto.', $exception);
        }
    }

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
            throw $this->repositoryError('No se pudo registrar el producto.', $exception);
        }
    }

    public function decrementarStock(int $productoId, int $cantidad): bool
    {
        try {
            $statement = $this->pdo->prepare(
                'UPDATE producto
                 SET stock = stock - :cantidad
                 WHERE id = :id AND stock >= :stock_requerido'
            );
            $statement->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
            $statement->bindValue(':id', $productoId, PDO::PARAM_INT);
            $statement->bindValue(':stock_requerido', $cantidad, PDO::PARAM_INT);
            $statement->execute();

            return $statement->rowCount() === 1;
        } catch (PDOException $exception) {
            throw $this->repositoryError('No se pudo descontar el stock.', $exception);
        }
    }

    private function findOneById(int $id, bool $forUpdate): ?Producto
    {
        try {
            $sql = 'SELECT id, codigo, nombre, categoria, precio, stock, stock_minimo, creado_en
                    FROM producto WHERE id = :id';
            if ($forUpdate) {
                $sql .= ' FOR UPDATE';
            }

            $statement = $this->pdo->prepare($sql);
            $statement->bindValue(':id', $id, PDO::PARAM_INT);
            $statement->execute();
            $row = $statement->fetch();

            return $row === false ? null : $this->hydrate($row);
        } catch (PDOException $exception) {
            throw $this->repositoryError('No se pudo consultar el producto.', $exception);
        }
    }

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

    private function repositoryError(string $safeMessage, PDOException $exception): RepositoryException
    {
        error_log($exception->getMessage());

        return new RepositoryException($safeMessage, (int) $exception->getCode(), $exception);
    }
}
