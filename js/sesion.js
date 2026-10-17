// Control de sesion en el navegador. La seguridad real esta en el backend (PHP);
// esto solo redirige y muestra el menu segun el rol.

const iniciarPagina = async (rolRequerido = null) => {
  let usuario;
  try {
    usuario = await api("api/sesion.php");
  } catch (e) {
    location.href = "index.html";
    return null;
  }
  if (rolRequerido && usuario.rol !== rolRequerido) {
    location.href = "panel.html";
    return null;
  }
  pintarMenu(usuario);
  return usuario;
};

const pintarMenu = (usuario) => {
  const actual = location.pathname.split("/").pop() || "index.html";
  const enlaces = [["panel.html", "Panel"], ["tutorias.html", "Tutorías"]];
  if (usuario.rol === "estudiante") {
    enlaces.push(["mis-solicitudes.html", "Mis solicitudes"]);
  }

  $("#menu").innerHTML = enlaces.map(([url, texto]) =>
    `<a href="${url}" class="${actual === url ? "activo" : ""}" ${actual === url ? 'aria-current="page"' : ""}>${texto}</a>`).join("") +
    `<span class="usuario">${esc(usuario.nombre)} (${esc(usuario.rol)})</span>
     <button type="button" id="btnSalir" aria-label="Cerrar sesión de ${esc(usuario.nombre)}">Salir</button>`;

  $("#btnSalir").addEventListener("click", async () => {
    try {
      await api("api/logout.php", "POST", {});
    } catch (e) {
      // se redirige igual
    }
    location.href = "index.html";
  });
};

