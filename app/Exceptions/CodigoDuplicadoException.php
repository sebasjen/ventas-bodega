<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Indica que se intentó registrar un producto con un código ya existente.
 */
final class CodigoDuplicadoException extends ValidationException
{
    /**
     * Construye la excepción con el mensaje definido por RF1-CA2.
     */
    public function __construct()
    {
        parent::__construct('El código de producto ya está registrado');
    }
}
