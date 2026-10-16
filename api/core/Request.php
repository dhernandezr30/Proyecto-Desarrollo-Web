<?php
// Lectura de lo que envia el navegador
class Request
{
    // Cuerpo JSON de POST / PUT / DELETE, como arreglo
    public static function body(): array
    {
        $datos = json_decode(file_get_contents("php://input"), true);
        return is_array($datos) ? $datos : [];
    }

    // Texto limpio (sin espacios sobrantes)
    public static function texto(mixed $valor): string
    {
        return trim((string)($valor ?? ""));
    }
}
