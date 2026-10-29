const $ = (selector) => document.querySelector(selector);

const esc = (texto) => String(texto ?? "").replace(/[&<>"']/g, (c) => ({
  "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
}[c]));

class ErrorApi extends Error {
  constructor(mensaje, estado) {
    super(mensaje);
    this.estado = estado;
  }
}

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
    }
    if (!respuesta.ok || !cuerpo || cuerpo.success === false) {
      throw new ErrorApi((cuerpo && cuerpo.message) || "Ocurrió un error inesperado", respuesta.status);
    }
    return cuerpo;
  }
}

const clienteApi = new ClienteApi();

const api = async (url, metodo = "GET", datos = null) => {
  const cuerpo = await clienteApi.solicitar(url, metodo, datos);
  return cuerpo.data;
};

const accion = async (url, metodo, datos = null) => {
  const cuerpo = await clienteApi.solicitar(url, metodo, datos);
  avisar(cuerpo.message);
  return cuerpo.data;
};

const avisar = (texto, tipo = "ok") => {
  const div = document.createElement("div");
  div.className = "aviso " + (tipo === "error" ? "error" : "");
  div.setAttribute("role", "status");
  div.textContent = texto;
  document.body.appendChild(div);
  setTimeout(() => div.remove(), 3500);
};

const fmtFecha = (f) => {
  if (!f) return "";
  const [anio, mes, dia] = String(f).slice(0, 10).split("-");
  return `${dia}/${mes}/${anio}`;
};

const fmtHora = (h) => String(h || "").slice(0, 5);

const fmtFechaHora = (f) => (f ? fmtFecha(f) + " " + String(f).slice(11, 16) : "");

const insignia = (estado) => `<span class="insignia ${esc(estado)}">${esc(estado)}</span>`;

const barraCupos = (t) => {
  const max = Number(t.cupo_max);
  const min = Number(t.cupo_min);
  const ocupados = Number(t.ocupados);

  return `<div class="cupo">
      <span>Inscritos <strong>${ocupados}/${max}</strong> &middot; mínimo ${min}</span>
    </div>`;
};

document.addEventListener("click", (evento) => {
  const campo = evento.target.closest('input[type="date"], input[type="time"]');
  if (campo && campo.showPicker) {
    try {
      campo.showPicker();
    } catch (e) {
    }
  }
});
