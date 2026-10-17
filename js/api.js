// Funciones compartidas: llamadas a la API PHP y utilidades de pantalla.

// Atajo para buscar un elemento con selector CSS: $("#menu"), $(".caja")
const $ = (selector) => document.querySelector(selector);

// Escapa texto antes de meterlo en innerHTML (evita XSS)
const esc = (texto) => String(texto ?? "").replace(/[&<>"']/g, (c) => ({
  "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
}[c]));

// Error que trae el codigo HTTP (401, 403, 409...) ademas del mensaje
class ErrorApi extends Error {
  constructor(mensaje, estado) {
    super(mensaje);
    this.estado = estado;
  }
}

// Cliente que habla con los endpoints PHP. Todos responden:
//   { success: true, message: "...", data: ... }  o  { success: false, message: "..." }
class ClienteApi {
  async solicitar(url, metodo = "GET", datos = null) {
    const opciones = { method: metodo, headers: {} };
    if (datos) {
      opciones.headers["Content-Type"] = "application/json";
      opciones.body = JSON.stringify(datos);
    }

    let respuesta;
    try {
      respuesta = await fetch(url, opciones);
    } catch (e) {
      throw new ErrorApi("No se pudo conectar con el servidor", 0);
    }

    let cuerpo = null;
    try {
      cuerpo = await respuesta.json();
    } catch (e) {
      // la respuesta no era JSON
    }
    if (!respuesta.ok || !cuerpo || cuerpo.success === false) {
      throw new ErrorApi((cuerpo && cuerpo.message) || "Ocurrió un error inesperado", respuesta.status);
    }
    return cuerpo;
  }
}

const clienteApi = new ClienteApi();

// Consultas: devuelve solo "data"
const api = async (url, metodo = "GET", datos = null) => {
  const cuerpo = await clienteApi.solicitar(url, metodo, datos);
  return cuerpo.data;
};

// Acciones (crear, editar, aceptar...): muestra el mensaje del servidor y devuelve "data"
const accion = async (url, metodo, datos = null) => {
  const cuerpo = await clienteApi.solicitar(url, metodo, datos);
  avisar(cuerpo.message);
  return cuerpo.data;
};

// Aviso flotante: tipo "ok" o "error"
const avisar = (texto, tipo = "ok") => {
  const div = document.createElement("div");
  div.className = "aviso " + (tipo === "error" ? "error" : "");
  div.setAttribute("role", "status");
  div.textContent = texto;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 3500);
};

// 2026-10-05 -> 05/10/2026
const fmtFecha = (f) => {
  if (!f) return "";
  const [anio, mes, dia] = String(f).slice(0, 10).split("-");
  return `${dia}/${mes}/${anio}`;
};

// 08:00:00 -> 08:00
const fmtHora = (h) => String(h || "").slice(0, 5);

const fmtFechaHora = (f) => (f ? fmtFecha(f) + " " + String(f).slice(11, 16) : "");

const insignia = (estado) => `<span class="insignia ${esc(estado)}">${esc(estado)}</span>`;

// Barra de cupos: relleno = inscritos, marca negra = cupo minimo
const barraCupos = (t) => {
  const max = Number(t.cupo_max);
  const min = Number(t.cupo_min);
  const ocupados = Number(t.ocupados);
  const porcentaje = Math.min(100, Math.round(ocupados / max * 100));
  const porcentajeMin = Math.round(min / max * 100);
  const clase = ocupados >= max ? "completo" : (ocupados >= min ? "minimo" : "");

  return `<div class="cupo">
      <span>Inscritos <strong>${ocupados}/${max}</strong> &middot; mínimo ${min}</span>
      <div class="barra" role="img" aria-label="${ocupados} de ${max} cupos ocupados, mínimo ${min}">
        <div class="lleno ${clase}" style="width:${porcentaje}%"></div>
        <span class="marca-min" style="left:${porcentajeMin}%"></span>
      </div>
    </div>`;
};
