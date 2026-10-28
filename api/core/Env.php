<?php
class Env
{
    private static array $variables = [];

    public static function load(string $ruta): void
    {
        if (!is_readable($ruta)) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Falta el archivo de configuración (.env)', 'code' => -1]);
            exit;
        }

        foreach (file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim($linea);
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            self::$variables[trim($clave)] = trim($valor, " \t\"'");
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        $entorno = getenv($clave);
        if ($entorno !== false) {
            return $entorno;
        }
        return self::$variables[$clave] ?? $porDefecto;
    }
}
