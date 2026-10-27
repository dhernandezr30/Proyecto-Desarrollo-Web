// Detalle de una tutoria: el estudiante solicita/cancela; el profesor dueno gestiona solicitudes.
const tutoriaId = Number(new URLSearchParams(location.search).get("id"));
let usuario = null;
let tutoria = null;

const iniciar = async () => {
  usuario = await iniciarPagina();
  if (!usuario) return;
  if (!tutoriaId) {
    $("#detalle").textContent = "Tutoría no indicada.";
    return;
  }
  await recargar();
};

const recargar = async () => {
  try {
    tutoria = await api("api/tutorias.php?id=" + tutoriaId);
  } catch (e) {
    $("#detalle").textContent = e.message;
    return;
  }
  pintarDetalle();
};

const pintarDetalle = () => {
  const t = tutoria;
  const esDueno = usuario.rol === "profesor" && t.profesor_id === usuario.id;
  const libres = Number(t.cupo_max) - Number(t.ocupados);
  const faltan = Math.max(0, Number(t.cupo_min) - Number(t.ocupados));
  let acciones = "";

  if (usuario.rol === "estudiante") {
    const solicitud = t.mi_solicitud;
    if (solicitud && solicitud.estado !== "cancelada") {
      acciones = `<p>Tu solicitud: ${insignia(solicitud.estado)}</p>`;
    } else if (t.estado === "cancelada") {
      acciones = '<p class="suave">Esta tutoría fue cancelada.</p>';
    } else if (libres <= 0) {
      acciones = '<p class="suave">Sin cupos disponibles.</p>';
    } else {
      acciones = '<button type="button" class="boton" data-accion="solicitar">Solicitar inscripción</button>';
    }
  }

  $("#detalle").innerHTML = `
    <div><span class="insignia materia">${esc(t.materia)}</span> ${insignia(t.estado)}</div>
    <h1>${esc(t.titulo)}</h1>
    <p><strong>Tema:</strong> ${esc(t.tema)}</p>
    <p>${esc(t.descripcion) || '<span class="suave">Sin descripción.</span>'}</p>
    <p class="datos">
      <strong>Profesor:</strong> ${esc(t.profesor)}<br>
      <strong>Fecha:</strong> ${fmtFecha(t.fecha)} &middot; ${fmtHora(t.hora_inicio)} - ${fmtHora(t.hora_fin)}
    </p>
    ${barraCupos(t)}
    <p class="suave">
      Cupos libres: ${libres} &middot;
      ${faltan > 0 ? `faltan ${faltan} inscritos para alcanzar el mínimo` : "cupo mínimo alcanzado"}
      ${esDueno ? ` &middot; solicitudes pendientes: ${t.pendientes}` : ""}
    </p>
    <div class="acciones">${acciones}</div>`;
};

$("#detalle").addEventListener("click", async (evento) => {
  const boton = evento.target.closest("button[data-accion]");
  if (!boton || boton.disabled) return;

  try {
    if (boton.dataset.accion === "solicitar") {
      await accion("api/solicitudes.php", "POST", { tutoria_id: tutoriaId });
    }
  } catch (error) {
    avisar(error.message, "error");
  }
  recargar();
});

iniciar();
