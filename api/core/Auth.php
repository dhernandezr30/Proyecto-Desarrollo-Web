<?php
class Auth
{
    private const TABLAS = ["profesor" => "profesores", "estudiante" => "estudiantes"];

    public static function usuario(PDO $pdo): ?array
    {
        if (isset($_SESSION["id"], $_SESSION["rol"])) {
            return ["id" => (int)$_SESSION["id"], "rol" => $_SESSION["rol"], "nombre" => $_SESSION["nombre"]];
        }
        return self::restaurarDesdeCookie($pdo);
    }

    public static function requiere(PDO $pdo, ?string $rol = null): array
    {
        $usuario = self::usuario($pdo);
        if (!$usuario) {
            Response::error("Debes iniciar sesión", -1, 401);
        }
        if ($rol !== null && $usuario["rol"] !== $rol) {
            Response::error("No tienes permiso para esta acción", -1, 403);
        }
        return $usuario;
    }

    public static function tabla(string $rol): ?string
    {
        return self::TABLAS[$rol] ?? null;
    }

    public static function iniciarSesion(int $id, string $rol, string $nombre, bool $recordar): void
    {
        session_regenerate_id(true);
        $_SESSION["id"] = $id;
        $_SESSION["rol"] = $rol;
        $_SESSION["nombre"] = $nombre;

        if ($recordar) {
            $expira = time() + DIAS_RECORDAR * 86400;
            $datos = $rol . "." . $id . "." . $expira;
            self::poner(COOKIE_NAME . "_recordar", $datos . "." . self::firmar($datos), $expira);
        }
    }

    public static function cerrarSesion(): void
    {
        $_SESSION = [];
        session_destroy();
        self::poner(COOKIE_NAME . "_recordar", "", time() - 3600);
    }

    private static function restaurarDesdeCookie(PDO $pdo): ?array
    {
        $valor = $_COOKIE[COOKIE_NAME . "_recordar"] ?? "";
        $partes = explode(".", $valor);
        if (count($partes) !== 4) {
            return null;
        }
        [$rol, $id, $expira, $firma] = $partes;

        if (!hash_equals(self::firmar("$rol.$id.$expira"), $firma) || (int)$expira < time()) {
            return null;
        }
        $tabla = self::tabla($rol);
        if ($tabla === null) {
            return null;
        }

        $st = $pdo->prepare("SELECT id, nombre FROM $tabla WHERE id = ? AND estado = 1");
        $st->execute([(int)$id]);
        $u = $st->fetch();
        if (!$u) {
            return null;
        }

        session_regenerate_id(true);
        $_SESSION["id"] = (int)$u["id"];
        $_SESSION["rol"] = $rol;
        $_SESSION["nombre"] = $u["nombre"];
        return ["id" => (int)$u["id"], "rol" => $rol, "nombre" => $u["nombre"]];
    }

    private static function firmar(string $datos): string
    {
        return hash_hmac("sha256", $datos, COOKIE_KEY);
    }

    private static function poner(string $nombre, string $valor, int $expira): void
    {
        setcookie($nombre, $valor, [
            "expires"  => $expira,
            "path"     => "/",
            "secure"   => COOKIE_SECURE,
            "httponly" => true,
            "samesite" => "Strict",
        ]);
    }
}
