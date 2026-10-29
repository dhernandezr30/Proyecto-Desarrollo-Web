<?php
require_once(__DIR__ . "/api/core/Pagina.php");
Pagina::publica();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear cuenta - Sistema de Tutorías</title>
  <meta name="description" content="Crea tu cuenta de estudiante en el Sistema de Tutorías y empieza a solicitar tutorías.">
  <meta property="og:title" content="Crear cuenta - Sistema de Tutorías">
  <meta property="og:description" content="Regístrate como estudiante para solicitar tutorías.">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Sistema de Tutorías">
  <meta property="og:locale" content="es_GT">
  <link rel="stylesheet" href="css/estilos.css">
  <link rel="icon" href="favicon.svg" type="image/svg+xml">
</head>
<body>
  <main class="centrado">
    <section class="caja" aria-labelledby="titulo">
      <h1 id="titulo">Crear cuenta</h1>

      <form id="formRegistro" novalidate>
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" maxlength="255" autocomplete="name" required>

        <label for="correo">Correo</label>
        <input type="email" id="correo" name="correo" maxlength="150" autocomplete="email" required>

        <label for="password">Contraseña (mínimo 6 caracteres)</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>

        <label for="password2">Repite la contraseña</label>
        <input type="password" id="password2" name="password2" autocomplete="new-password" required>

        <p class="error-campo" id="error" role="alert"></p>
        <button type="submit" class="boton ancho">Crear cuenta</button>
      </form>

      <p class="suave sin-margen-abajo">¿Ya tienes cuenta? <a href="index.php">Inicia sesión</a></p>
    </section>
  </main>

  <script src="js/api.js"></script>
  <script>
    $("#formRegistro").addEventListener("submit", async (evento) => {
      evento.preventDefault();
      const error = $("#error");
      const datos = {
        nombre: $("#nombre").value.trim(),
        correo: $("#correo").value.trim(),
        password: $("#password").value,
      };
      error.textContent = "";

      if (datos.nombre.length < 3) {
        error.textContent = "El nombre debe tener al menos 3 caracteres";
        return;
      }
      if (!/^\S+@\S+\.\S+$/.test(datos.correo)) {
        error.textContent = "El correo no es válido";
        return;
      }
      if (datos.password.length < 6) {
        error.textContent = "La contraseña debe tener al menos 6 caracteres";
        return;
      }
      if (datos.password !== $("#password2").value) {
        error.textContent = "Las contraseñas no coinciden";
        return;
      }

      try {
        await accion("api/registro.php", "POST", datos);
        setTimeout(() => { location.href = "index.php"; }, 1200);
      } catch (err) {
        error.textContent = err.message;
      }
    });
  </script>
</body>
</html>

