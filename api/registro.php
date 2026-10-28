<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Request.php");
require_once(__DIR__ . "/core/Auth.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Response::error("Método no permitido", -1, 405);
}

$pdo = new Connection();
$datos = Request::body();
$rol = Request::texto($datos["rol"] ?? "");
$nombre = Request::texto($datos["nombre"] ?? "");
$correo = strtolower(Request::texto($datos["correo"] ?? ""));
$clave = (string)($datos["password"] ?? "");

$tabla = Auth::tabla($rol);
if ($tabla === null) {
    Response::error("Rol inválido");
}
if (mb_strlen($nombre) < 3) {
    Response::error("El nombre debe tener al menos 3 caracteres");
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    Response::error("Correo inválido");
}
if (strlen($clave) < 6) {
    Response::error("La contraseña debe tener al menos 6 caracteres");
}

$st = $pdo->prepare("SELECT 1 FROM profesores WHERE correo = ? UNION SELECT 1 FROM estudiantes WHERE correo = ?");
$st->execute([$correo, $correo]);
if ($st->fetch()) {
    Response::error("Ese correo ya está registrado", -1, 409);
}

$st = $pdo->prepare("INSERT INTO $tabla (nombre, correo, password) VALUES (?, ?, ?)");
$st->execute([$nombre, $correo, password_hash($clave, PASSWORD_DEFAULT)]);

Response::success(null, "Cuenta creada, ya puedes iniciar sesión", 201);
