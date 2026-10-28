// Panel resumen con indicadores segun el rol.

const indicador = (numero, nombre) =>
  `<div class="caja indicador"><div class="numero">${numero}</div><div class="nombre">${nombre}</div></div>`;

const iniciar = async () => {
  const usuario = await iniciarPagina();
  if (!usuario) return;
  $("#saludo").textContent = "Hola, " + usuario.nombre;

  let datos;
  try {
    datos = await api("api/panel.php");
  } catch (e) {
    avisar(e.message, "error");
    return;
  }
  if (datos.rol === "profesor") {
    panelProfesor(datos);
  } else {
    panelEstudiante(datos);
  }
};

const panelProfesor = (d) => {
  $("#indicadores").innerHTML =
    indicador(d.tutorias_total, "Tutorías creadas") +
    indicador(d.tutorias_abiertas, "Abiertas") +
    indicador(d.tutorias_confirmadas, "Confirmadas") +
    indicador(d.estudiantes_inscritos, "Estudiantes inscritos") +
    indicador(d.solicitudes_pendientes, "Solicitudes pendientes");

  const filas = d.tutorias.map((t) => {
    const faltan = Math.max(0, t.cupo_min - t.ocupados);
    return `<tr>
        <td><a href="tutoria.html?id=${t.id}">${esc(t.titulo)}</a><br><span class="suave">${esc(t.tema)}</span></td>
        <td>${fmtFecha(t.fecha)}</td>
        <td>${insignia(t.estado)}</td>
        <td class="col-cupos">${barraCupos(t)}</td>
        <td>${faltan > 0 ? `Faltan ${faltan} para el mínimo` : "Mínimo alcanzado"}</td>
      </tr>`;
  }).join("");

  $("#detalle").innerHTML = `<div class="caja tabla-scroll"><h3>Estado de mis tutorías</h3>
    <table>
      <thead><tr><th>Tutoría</th><th>Fecha</th><th>Estado</th><th>Cupos</th><th>Mínimo</th></tr></thead>
      <tbody>${filas || '<tr><td colspan="5" class="vacio">Aún no has creado tutorías.</td></tr>'}</tbody>
    </table>
    <div class="acciones"><a class="boton" href="tutorias.html">Gestionar tutorías</a></div></div>`;
};

const panelEstudiante = (d) => {
  $("#indicadores").innerHTML =
    indicador(d.disponibles, "Tutorías disponibles") +
    indicador(d.solicitudes.pendiente, "Solicitudes pendientes") +
    indicador(d.solicitudes.aceptada, "Aceptadas") +
    indicador(d.solicitudes.rechazada, "Rechazadas") +
    indicador(d.solicitudes.cancelada, "Canceladas");

  const filas = d.proximas.map((t) => `<tr>
      <td><a href="tutoria.html?id=${t.id}">${esc(t.titulo)}</a><br><span class="suave">${esc(t.tema)}</span></td>
      <td>${fmtFecha(t.fecha)} ${fmtHora(t.hora_inicio)}</td>
      <td>${esc(t.profesor)}</td>
      <td>${insignia(t.estado)}</td>
    </tr>`).join("");

  $("#detalle").innerHTML = `<div class="caja tabla-scroll"><h3>Mis próximas tutorías</h3>
    <table>
      <thead><tr><th>Tutoría</th><th>Fecha</th><th>Profesor</th><th>Estado</th></tr></thead>
      <tbody>${filas || '<tr><td colspan="4" class="vacio">No tienes tutorías próximas aceptadas.</td></tr>'}</tbody>
    </table>
    <div class="acciones"><a class="boton" href="tutorias.html">Buscar tutorías</a>
    <a class="boton secundario" href="mis-solicitudes.html">Ver mis solicitudes</a></div></div>`;
};

iniciar();
