<?php
class Request
{
    public static function body(): array
    {
        $datos = json_decode(file_get_contents("php://input"), true);
        return is_array($datos) ? $datos : [];
    }

    public static function texto(mixed $valor): string
    {
        return trim((string)($valor ?? ""));
    }
}
