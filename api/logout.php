<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Auth.php");

Auth::cerrarSesion();
Response::success(null, "Sesión cerrada");
