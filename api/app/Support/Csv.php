<?php

namespace App\Support;

/**
 * Ayudas para exportar CSV (CU30 auditoría, CU28 reportes).
 */
class Csv
{
    /**
     * Neutraliza la inyección de fórmulas: una celda que empiece por = + - @ (o tabulador / retorno
     * de carro) se interpretaría como fórmula al abrir el archivo en Excel o Calc. Se antepone una
     * comilla simple. Los nombres y valores exportados vienen de entradas de usuarios.
     */
    public static function cell($value)
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    /** Cabecera UTF-8 con BOM para que Excel lea bien los acentos. */
    public static function bom(): string
    {
        return "\xEF\xBB\xBF";
    }
}
