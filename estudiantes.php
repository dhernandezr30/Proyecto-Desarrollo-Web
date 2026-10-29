<?php
require_once(__DIR__ . "/api/core/Pagina.php");
Pagina::privada("profesor");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mis estudiantes - Sistema de Tutorías</title>
  <meta name="description" content="Estudiantes inscritos en tus tutorías.">
  <meta name="robots" content="noindex, nofollow">
  <meta property="og:title" content="Mis estudiantes - Sistema de Tutorías">
  <meta property="og:description" content="Estudiantes inscritos en tus tutorías.">
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
    <h1>Mis estudiantes</h1>
    <p class="suave">Estudiantes aceptados en tus tutorías.</p>

    <div class="caja">
      <div class="campo-corto">
        <label for="f_tutoria">Filtrar por tutoría</label>
        <select id="f_tutoria">
          <option value="">Todas</option>
        </select>
      </div>
    </div>

    <div class="caja tabla-scroll">
      <table>
        <caption class="solo-lectores">Estudiantes inscritos</caption>
        <thead>
          <tr><th scope="col">Estudiante</th><th scope="col">Correo</th><th scope="col">Tutoría</th><th scope="col">Materia / tema</th><th scope="col">Fecha</th><th scope="col">Inscrito</th></tr>
        </thead>
        <tbody id="filas"></tbody>
      </table>
    </div>
  </main>

  <script src="js/api.js"></script>
  <script src="js/sesion.js"></script>
  <script src="js/estudiantes.js"></script>
</body>
</html>
