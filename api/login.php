<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Request.php");
require_once(__DIR__ . "/core/Auth.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Response::error("Método no permitido", -1, 405);
}

$pdo = new Connection();
$datos = Request::body();
$correo = strtolower(Request::texto($datos["correo"] ?? ""));
$clave = (string)($datos["password"] ?? "");

if ($correo === "" || $clave === "") {
    Response::error("Ingresa correo y contraseña");
}

// Se busca primero entre los profesores y luego entre los estudiantes
foreach (["profesor", "estudiante"] as $rol) {
    $tabla = Auth::tabla($rol);
    $st = $pdo->prepare("SELECT id, nombre, password FROM $tabla WHERE correo = ? AND estado = 1");
    $st->execute([$correo]);
    $u = $st->fetch();

    if ($u && password_verify($clave, $u["password"])) {
        Auth::iniciarSesion((int)$u["id"], $rol, $u["nombre"], !empty($datos["recordar"]));
        Response::success(["id" => (int)$u["id"], "rol" => $rol, "nombre" => $u["nombre"]], "Bienvenido");
    }
}

// Mensaje generico: no revela si el correo existe
Response::error("Correo o contraseña incorrectos", -1, 401);
