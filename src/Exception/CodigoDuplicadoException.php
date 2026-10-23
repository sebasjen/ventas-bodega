<?php
declare(strict_types=1);

final class CodigoDuplicadoException extends ValidationException
{
    public function __construct()
    {
        parent::__construct('El código de producto ya está registrado');
    }
}
