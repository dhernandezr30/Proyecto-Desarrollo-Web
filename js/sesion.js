const iniciarPagina = async (rolRequerido = null) => {
  let usuario;
  try {
    usuario = await api("api/sesion.php");
  } catch (e) {
    location.href = "index.php";
    return null;
  }
  if (rolRequerido && usuario.rol !== rolRequerido) {
    location.href = "panel.php";
    return null;
  }
  pintarMenu(usuario);
  return usuario;
};

const pintarMenu = (usuario) => {
  const actual = location.pathname.split("/").pop() || "index.php";
  const enlaces = [["panel.php", "Panel"], ["tutorias.php", "Tutorías"]];
  if (usuario.rol === "estudiante") {
    enlaces.push(["mis-solicitudes.php", "Mis solicitudes"]);
  }

  $("#menu").innerHTML = enlaces.map(([url, texto]) =>
    `<a href="${url}" class="${actual === url ? "activo" : ""}" ${actual === url ? 'aria-current="page"' : ""}>${texto}</a>`).join("") +
    `<span class="usuario">${esc(usuario.nombre)} (${esc(usuario.rol)})</span>
     <button type="button" id="btnSalir" aria-label="Cerrar sesión de ${esc(usuario.nombre)}">Salir</button>`;

  $("#btnSalir").addEventListener("click", async () => {
    try {
      await api("api/logout.php", "POST", {});
    } catch (e) {
    }
    location.href = "index.php";
  });
};

