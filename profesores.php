<?php
require_once(__DIR__ . "/api/core/Pagina.php");
Pagina::privada("admin");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profesores - Sistema de Tutorías</title>
  <meta name="description" content="Administración de las cuentas de profesores.">
  <meta name="robots" content="noindex, nofollow">
  <meta property="og:title" content="Profesores - Sistema de Tutorías">
  <meta property="og:description" content="Administración de las cuentas de profesores.">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Sistema de Tutorías">
  <meta property="og:locale" content="es_GT">
  <link rel="stylesheet" href="css/estilos.css">
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
</head>
<body>
  <header class="encabezado">
    <div class="contenido">
      <p class="marca">Sistema de Tutorías</p>
      <nav class="menu" id="menu" aria-label="Menú principal"></nav>
    </div>
  </header>

  <main>
    <h1>Profesores</h1>

    <form id="formProfesor" class="caja" novalidate>
      <h2>Nuevo profesor</h2>
      <div class="fila">
        <div><label for="nombre">Nombre completo</label><input type="text" id="nombre" name="nombre" maxlength="255" required></div>
        <div><label for="correo">Correo</label><input type="email" id="correo" name="correo" maxlength="150" required></div>
        <div><label for="password">Contraseña (mínimo 6 caracteres)</label><input type="password" id="password" name="password" autocomplete="new-password" required></div>
      </div>
      <p class="error-campo" id="error" role="alert"></p>
      <button type="submit" class="boton">Crear profesor</button>
    </form>

    <div class="caja tabla-scroll">
      <table>
        <caption class="solo-lectores">Profesores registrados</caption>
        <thead>
          <tr><th scope="col">Nombre</th><th scope="col">Correo</th><th scope="col">Tutorías</th><th scope="col">Estado</th><th scope="col">Creado</th></tr>
        </thead>
        <tbody id="filas"></tbody>
      </table>
    </div>
  </main>

  <script src="js/api.js"></script>
  <script src="js/sesion.js"></script>
  <script src="js/profesores.js"></script>
</body>
</html>
