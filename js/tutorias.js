let usuario = null;

const iniciar = async () => {
  usuario = await iniciarPagina();
  if (!usuario) return;

  const materias = await api("api/materias.php");
  for (const m of materias) {
    $("#f_materia").add(new Option(m.nombre, m.id));
    $("#materia_id").add(new Option(m.nombre, m.id));
  }

  if (usuario.rol === "profesor") {
    $("#zonaProfesor").classList.remove("oculto");
    $("#divMias").classList.remove("oculto");
  }

  const editar = new URLSearchParams(location.search).get("editar");
  if (editar && usuario.rol === "profesor") {
    try {
      abrirFormulario(await api("api/tutorias.php?id=" + encodeURIComponent(editar)));
    } catch (e) {
      avisar(e.message, "error");
    }
  }

  cargar();
};

const cargar = async () => {
  const params = new URLSearchParams();
  if ($("#f_materia").value) params.set("materia_id", $("#f_materia").value);
  if ($("#f_tema").value.trim()) params.set("tema", $("#f_tema").value.trim());
  if ($("#f_fecha").value) params.set("fecha", $("#f_fecha").value);
  if ($("#f_profesor").value.trim()) params.set("profesor", $("#f_profesor").value.trim());
  if ($("#f_estado").value) params.set("estado", $("#f_estado").value);
  if ($("#f_mias").checked) params.set("mias", "1");

  try {
    const lista = await api("api/tutorias.php?" + params.toString());
    $("#lista").innerHTML = lista.length
      ? lista.map(tarjeta).join("")
      : '<p class="vacio">No hay tutorías con esos filtros.</p>';
  } catch (e) {
    avisar(e.message, "error");
  }
};

const tarjeta = (t) => {
  const esDueno = usuario.rol === "profesor" && t.profesor_id === usuario.id;
  const nombre = esc(t.titulo);
  let botones = `<a class="boton secundario chico" href="tutoria.html?id=${t.id}" aria-label="Ver detalle de ${nombre}">Ver detalle</a>`;

  if (usuario.rol === "estudiante") {
    const solicitud = t.mi_solicitud_estado;
    if (solicitud && solicitud !== "cancelada") {
      botones += ` ${insignia(solicitud)}`;
    } else if (t.estado !== "cancelada" && Number(t.ocupados) < Number(t.cupo_max)) {
      botones += ` <button type="button" class="boton chico" data-accion="solicitar" data-id="${t.id}" aria-label="Solicitar ${nombre}">Solicitar</button>`;
    } else if (Number(t.ocupados) >= Number(t.cupo_max)) {
      botones += ' <span class="suave">Sin cupos</span>';
    }
  }
  if (esDueno) {
    botones += ` <button type="button" class="boton chico" data-accion="editar" data-id="${t.id}" aria-label="Editar ${nombre}">Editar</button>
                 <button type="button" class="boton peligro chico" data-accion="eliminar" data-id="${t.id}" aria-label="Eliminar ${nombre}">Eliminar</button>`;
  }

  return `<article class="caja tutoria">
      <div><span class="insignia materia">${esc(t.materia)}</span> ${insignia(t.estado)}</div>
      <h3>${nombre}</h3>
      <div><strong>Tema:</strong> ${esc(t.tema)}</div>
      <div class="datos">
        Prof. ${esc(t.profesor)}<br>
        ${fmtFecha(t.fecha)} &middot; ${fmtHora(t.hora_inicio)} - ${fmtHora(t.hora_fin)}
      </div>
      ${barraCupos(t)}
      <div class="acciones">${botones}</div>
    </article>`;
};

$("#lista").addEventListener("click", async (evento) => {
  const boton = evento.target.closest("button[data-accion]");
  if (!boton) return;
  const id = Number(boton.dataset.id);

  try {
    if (boton.dataset.accion === "solicitar") {
      boton.disabled = true;
      await accion("api/solicitudes.php", "POST", { tutoria_id: id });
      cargar();
    } else if (boton.dataset.accion === "editar") {
      abrirFormulario(await api("api/tutorias.php?id=" + id));
    } else if (boton.dataset.accion === "eliminar") {
      if (!confirm("¿Eliminar esta tutoría? Se borrarán también sus solicitudes e inscripciones.")) return;
      await accion("api/tutorias.php", "DELETE", { id });
      cargar();
    }
  } catch (error) {
    avisar(error.message, "error");
    cargar();
  }
});

$("#filtros").addEventListener("submit", (evento) => {
  evento.preventDefault();
  cargar();
});

$("#btnLimpiar").addEventListener("click", () => {
  $("#filtros").reset();
  cargar();
});

const abrirFormulario = (t = null) => {
  $("#formTutoria").reset();
  $("#errorForm").textContent = "";
  $("#id").value = t ? t.id : "";
  $("#tituloForm").textContent = t ? "Editar tutoría" : "Nueva tutoría";
  if (t) {
    $("#materia_id").value = t.materia_id;
    $("#tema").value = t.tema;
    $("#titulo").value = t.titulo;
    $("#descripcion").value = t.descripcion || "";
    $("#fecha").value = t.fecha;
    $("#hora_inicio").value = fmtHora(t.hora_inicio);
    $("#hora_fin").value = fmtHora(t.hora_fin);
    $("#cupo_min").value = t.cupo_min;
    $("#cupo_max").value = t.cupo_max;
  }
  $("#formTutoria").classList.remove("oculto");
  $("#formTutoria").scrollIntoView({ behavior: "smooth", block: "start" });
};

const cerrarFormulario = () => {
  $("#formTutoria").classList.add("oculto");
  if (location.search) history.replaceState(null, "", "tutorias.html");
};

$("#btnNueva").addEventListener("click", () => abrirFormulario());
$("#btnCancelarForm").addEventListener("click", cerrarFormulario);

const validarFormulario = (d) => {
  if (!d.materia_id) return "Selecciona una materia";
  if (!d.tema) return "El tema es obligatorio";
  if (!d.titulo) return "El título es obligatorio";
  if (!d.fecha) return "Selecciona la fecha";
  if (!d.hora_inicio || !d.hora_fin) return "Indica la hora de inicio y de fin";
  if (d.hora_fin <= d.hora_inicio) return "La hora de fin debe ser posterior a la de inicio";
  if (!(d.cupo_min >= 1)) return "El cupo mínimo debe ser al menos 1";
  if (d.cupo_max < d.cupo_min) return "El cupo máximo no puede ser menor al mínimo";
  return "";
};

$("#formTutoria").addEventListener("submit", async (evento) => {
  evento.preventDefault();
  const datos = {
    id: Number($("#id").value) || undefined,
    materia_id: Number($("#materia_id").value),
    tema: $("#tema").value.trim(),
    titulo: $("#titulo").value.trim(),
    descripcion: $("#descripcion").value.trim(),
    fecha: $("#fecha").value,
    hora_inicio: $("#hora_inicio").value,
    hora_fin: $("#hora_fin").value,
    cupo_min: Number($("#cupo_min").value),
    cupo_max: Number($("#cupo_max").value),
  };

  const problema = validarFormulario(datos);
  if (problema) {
    $("#errorForm").textContent = problema;
    return;
  }

  try {
    await accion("api/tutorias.php", datos.id ? "PUT" : "POST", datos);
    cerrarFormulario();
    cargar();
  } catch (error) {
    $("#errorForm").textContent = error.message;
  }
});

iniciar();
