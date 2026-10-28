let solicitudes = [];

const iniciar = async () => {
  if (!await iniciarPagina("estudiante")) return;
  await cargar();
};

const cargar = async () => {
  try {
    solicitudes = await api("api/solicitudes.php");
    pintar();
  } catch (e) {
    avisar(e.message, "error");
  }
};

const pintar = () => {
  const filtro = $("#f_estado").value;
  const lista = solicitudes.filter((s) => !filtro || s.estado === filtro);
  $("#filas").innerHTML = lista.length
    ? lista.map(fila).join("")
    : '<tr><td colspan="8" class="vacio">No hay solicitudes.</td></tr>';
};

const fila = (s) => {
  const cancelable = ["pendiente", "aceptada"].includes(s.estado);
  return `<tr>
      <td><a href="tutoria.php?id=${s.tutoria_id}">${esc(s.titulo)}</a> ${s.estado_tutoria === "cancelada" ? insignia("cancelada") : ""}</td>
      <td>${esc(s.materia)} / ${esc(s.tema)}</td>
      <td>${esc(s.profesor)}</td>
      <td>${fmtFecha(s.fecha)} ${fmtHora(s.hora_inicio)}</td>
      <td>${fmtFechaHora(s.fecha_solicitud)}</td>
      <td>${fmtFechaHora(s.fecha_resolucion) || "-"}</td>
      <td>${insignia(s.estado)}</td>
      <td>${cancelable ? `<button type="button" class="boton peligro chico" data-id="${s.id}" aria-label="Cancelar solicitud de ${esc(s.titulo)}">Cancelar</button>` : ""}</td>
    </tr>`;
};

$("#f_estado").addEventListener("change", pintar);

$("#filas").addEventListener("click", async (evento) => {
  const boton = evento.target.closest("button[data-id]");
  if (!boton || !confirm("¿Cancelar esta solicitud?")) return;

  try {
    await accion("api/solicitudes.php", "DELETE", { id: Number(boton.dataset.id) });
  } catch (error) {
    avisar(error.message, "error");
  }
  cargar();
});

iniciar();
