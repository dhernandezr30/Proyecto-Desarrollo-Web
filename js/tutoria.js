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
  if (usuario.rol === "profesor" && tutoria.profesor_id === usuario.id) {
    cargarSolicitudes();
  }
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
      if (["pendiente", "aceptada"].includes(solicitud.estado)) {
        acciones += `<button type="button" class="boton peligro" data-accion="cancelar" data-id="${solicitud.id}">Cancelar mi solicitud</button>`;
      }
    } else if (t.estado === "cancelada") {
      acciones = '<p class="suave">Esta tutoría fue cancelada.</p>';
    } else if (libres <= 0) {
      acciones = '<p class="suave">Sin cupos disponibles.</p>';
    } else {
      acciones = '<button type="button" class="boton" data-accion="solicitar">Solicitar inscripción</button>';
    }
  } else if (esDueno) {
    acciones = `<a class="boton secundario" href="tutorias.html?editar=${t.id}">Editar datos</a>`;
    if (t.estado === "abierta") {
      acciones += ` <button type="button" class="boton ok" data-accion="estado" data-estado="confirmada"
        ${faltan > 0 ? 'disabled title="Aún no se alcanza el cupo mínimo"' : ""}>Confirmar tutoría</button>`;
    }
    if (t.estado === "confirmada") {
      acciones += ' <button type="button" class="boton secundario" data-accion="estado" data-estado="abierta">Reabrir</button>';
    }
    if (t.estado !== "cancelada") {
      acciones += ' <button type="button" class="boton peligro" data-accion="estado" data-estado="cancelada">Cancelar tutoría</button>';
    } else {
      acciones += ' <button type="button" class="boton secundario" data-accion="estado" data-estado="abierta">Reactivar</button>';
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
    } else if (boton.dataset.accion === "cancelar") {
      if (!confirm("¿Cancelar tu solicitud?")) return;
      await accion("api/solicitudes.php", "DELETE", { id: Number(boton.dataset.id) });
    } else if (boton.dataset.accion === "estado") {
      if (boton.dataset.estado === "cancelada" && !confirm("¿Cancelar esta tutoría?")) return;
      await accion("api/tutorias.php", "PUT", { id: tutoriaId, accion: "estado", estado: boton.dataset.estado });
    }
  } catch (error) {
    avisar(error.message, "error");
  }
  recargar();
});

const cargarSolicitudes = async () => {
  $("#zonaSolicitudes").classList.remove("oculto");
  try {
    const lista = await api("api/solicitudes.php?tutoria_id=" + tutoriaId);
    $("#filas").innerHTML = lista.length
      ? lista.map(fila).join("")
      : '<tr><td colspan="6" class="vacio">Aún no hay solicitudes.</td></tr>';
  } catch (e) {
    avisar(e.message, "error");
  }
};

const fila = (s) => {
  const botones = s.estado === "pendiente"
    ? `<button type="button" class="boton ok chico" data-id="${s.id}" data-estado="aceptada" aria-label="Aceptar a ${esc(s.estudiante)}">Aceptar</button>
       <button type="button" class="boton peligro chico" data-id="${s.id}" data-estado="rechazada" aria-label="Rechazar a ${esc(s.estudiante)}">Rechazar</button>`
    : "";
  return `<tr>
      <td>${esc(s.estudiante)}</td>
      <td>${esc(s.correo)}</td>
      <td>${fmtFechaHora(s.fecha_solicitud)}</td>
      <td>${fmtFechaHora(s.fecha_resolucion) || "-"}</td>
      <td>${insignia(s.estado)}</td>
      <td>${botones}</td>
    </tr>`;
};

$("#filas").addEventListener("click", async (evento) => {
  const boton = evento.target.closest("button[data-estado]");
  if (!boton) return;

  try {
    await accion("api/solicitudes.php", "PUT", { id: Number(boton.dataset.id), estado: boton.dataset.estado });
  } catch (error) {
    avisar(error.message, "error");
  }
  recargar();
});

iniciar();
