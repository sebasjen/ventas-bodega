<?php
declare(strict_types=1);

final class StockInsuficienteException extends RuntimeException
{
    public function __construct(int $disponible)
    {
        parent::__construct("Stock insuficiente. Disponible: {$disponible} unidades");
    }
}
