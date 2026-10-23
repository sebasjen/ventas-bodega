<?php
declare(strict_types=1);

namespace App\Exceptions;

/**
 * Representa datos de entrada que incumplen una regla de validación.
 */
class ValidationException extends AppException
{
    /**
     * Construye una excepción con el mensaje de validación que el Controller puede presentar.
     *
     * @param string $message Explicación segura del dato inválido.
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
