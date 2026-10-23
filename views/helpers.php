<?php
declare(strict_types=1);

namespace App\Views;

/**
 * Proporciona utilidades seguras compartidas por las plantillas HTML.
 */
final class Html
{
    /**
     * Escapa un valor escalar antes de insertarlo en texto o atributos HTML.
     *
     * @param string|int|float|bool|null $value Valor que se imprimirá en HTML.
     *
     * @return string Texto escapado para un documento UTF-8.
     */
    public static function escape(string|int|float|bool|null $value): string
    {
        return htmlspecialchars(
            (string) ($value ?? ''),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );
    }
}
