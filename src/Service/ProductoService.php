<?php
declare(strict_types=1);

final class ProductoService
{
    public function __construct(private readonly ProductoRepository $productoRepository)
    {
    }

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
        if ($precio <= 0) {
            throw new ValidationException('El precio debe ser mayor a cero');
        }
        if ($precio > 99999999.99) {
            throw new ValidationException('El precio no puede superar $99,999,999.99.');
        }
        if ($stock < 0 || $stockMinimo < 0) {
            throw new ValidationException('El stock no puede ser negativo');
        }
        if (mb_strlen($codigo) > 50 || mb_strlen($nombre) > 150 || mb_strlen($categoria) > 100) {
            throw new ValidationException('Uno o más campos superan la longitud permitida.');
        }

        if ($this->productoRepository->findByCodigo($codigo) !== null) {
            throw new CodigoDuplicadoException();
        }

        try {
            $id = $this->productoRepository->save(
                $codigo,
                $nombre,
                $categoria,
                $precio,
                $stock,
                $stockMinimo,
            );
        } catch (RepositoryException $exception) {
            if ($exception->getPrevious() instanceof PDOException
                && $exception->getPrevious()->getCode() === '23000') {
                throw new CodigoDuplicadoException();
            }
            throw $exception;
        }

        return new Producto(
            $id,
            $codigo,
            $nombre,
            $categoria,
            $precio,
            $stock,
            $stockMinimo,
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        );
    }

    /** @return list<Producto> */
    public function listar(): array
    {
        return $this->productoRepository->findAll();
    }
}
