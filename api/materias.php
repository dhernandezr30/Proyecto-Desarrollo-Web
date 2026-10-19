<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Auth.php");

$pdo = new Connection();
Auth::requiere($pdo);

Response::success($pdo->query("SELECT id, nombre FROM materias ORDER BY nombre")->fetchAll());
