<?php
require_once(__DIR__ . "/api/core/Pagina.php");
Pagina::privada();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel - Sistema de Tutorías</title>
  <meta name="description" content="Resumen de tus tutorías, cupos y solicitudes en el Sistema de Tutorías.">
  <meta name="robots" content="noindex, nofollow">
  <meta property="og:title" content="Panel - Sistema de Tutorías">
  <meta property="og:description" content="Resumen de tus tutorías, cupos y solicitudes.">
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
    <h1 id="saludo">Panel</h1>
    <div id="indicadores" class="indicadores" aria-live="polite"></div>
    <section id="detalle" aria-label="Detalle del resumen"></section>
  </main>
</body>
</html>
