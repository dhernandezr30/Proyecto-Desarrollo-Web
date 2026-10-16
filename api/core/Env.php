<?php
// Lee el archivo .env y entrega sus valores con Env::get()
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
            // Se ignoran comentarios (#) y lineas sin "="
            if ($linea === '' || $linea[0] === '#' || !str_contains($linea, '=')) {
                continue;
            }
            [$clave, $valor] = explode('=', $linea, 2);
            self::$variables[trim($clave)] = trim($valor, " \t\"'");
        }
    }

    public static function get(string $clave, ?string $porDefecto = null): ?string
    {
        // Una variable de entorno del sistema tiene prioridad sobre el .env
        $entorno = getenv($clave);
        if ($entorno !== false) {
            return $entorno;
        }
        return self::$variables[$clave] ?? $porDefecto;
    }
}
