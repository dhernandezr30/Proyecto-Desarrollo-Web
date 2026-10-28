<?php
require_once(__DIR__ . "/../Connection.php");
require_once(__DIR__ . "/Auth.php");

class Pagina
{
    public static function privada(?string $rol = null): array
    {
        $usuario = Auth::usuario(new Connection());
        if (!$usuario) {
            self::ir("index.php");
        }
        if ($rol !== null && $usuario["rol"] !== $rol) {
            self::ir("panel.php");
        }
        return $usuario;
    }

    public static function publica(): void
    {
        if (Auth::usuario(new Connection())) {
            self::ir("panel.php");
        }
    }

    private static function ir(string $pagina): void
    {
        header("Location: " . $pagina);
        exit;
    }
}
