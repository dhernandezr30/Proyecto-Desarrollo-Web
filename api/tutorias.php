<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Request.php");
require_once(__DIR__ . "/core/Auth.php");
require_once(__DIR__ . "/core/Cupos.php");

$pdo = new Connection();
$u = Auth::requiere($pdo);
$metodo = $_SERVER["REQUEST_METHOD"];

const SQL_BASE = "SELECT t.id, t.profesor_id, p.nombre AS profesor, t.materia_id, m.nombre AS materia,
        t.tema, t.titulo, t.descripcion, t.fecha, t.hora_inicio, t.hora_fin,
        t.cupo_min, t.cupo_max, t.estado,
        (SELECT COUNT(*) FROM inscripciones i WHERE i.tutoria_id = t.id) AS ocupados,
        (SELECT COUNT(*) FROM solicitudes s WHERE s.tutoria_id = t.id AND s.estado = 'pendiente') AS pendientes
    FROM tutorias t
    JOIN profesores p ON p.id = t.profesor_id
    JOIN materias m ON m.id = t.materia_id";

function validar_tutoria(array $d): array
{
    $c = [
        "materia_id"  => (int)($d["materia_id"] ?? 0),
        "tema"        => Request::texto($d["tema"] ?? ""),
        "titulo"      => Request::texto($d["titulo"] ?? ""),
        "descripcion" => Request::texto($d["descripcion"] ?? ""),
        "fecha"       => Request::texto($d["fecha"] ?? ""),
        "hora_inicio" => Request::texto($d["hora_inicio"] ?? ""),
        "hora_fin"    => Request::texto($d["hora_fin"] ?? ""),
        "cupo_min"    => (int)($d["cupo_min"] ?? 0),
        "cupo_max"    => (int)($d["cupo_max"] ?? 0),
    ];

    if ($c["materia_id"] < 1) {
        Response::error("Selecciona una materia");
    }
    if ($c["tema"] === "" || mb_strlen($c["tema"]) > 120) {
        Response::error("El tema es obligatorio (máx. 120 caracteres)");
    }
    if ($c["titulo"] === "" || mb_strlen($c["titulo"]) > 150) {
        Response::error("El título es obligatorio (máx. 150 caracteres)");
    }
    $f = DateTime::createFromFormat("Y-m-d", $c["fecha"]);
    if (!$f || $f->format("Y-m-d") !== $c["fecha"]) {
        Response::error("Fecha inválida");
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $c["hora_inicio"]) || !preg_match('/^\d{2}:\d{2}$/', $c["hora_fin"])) {
        Response::error("Hora inválida");
    }
    if ($c["hora_fin"] <= $c["hora_inicio"]) {
        Response::error("La hora de fin debe ser posterior a la de inicio");
    }
    if ($c["cupo_min"] < 1) {
        Response::error("El cupo mínimo debe ser al menos 1");
    }
    if ($c["cupo_max"] < $c["cupo_min"]) {
        Response::error("El cupo máximo no puede ser menor al mínimo");
    }
    if ($c["cupo_max"] > 500) {
        Response::error("El cupo máximo es demasiado alto");
    }
    return $c;
}

function hay_traslape(PDO $pdo, int $profesorId, array $c, int $excluirId = 0): bool
{
    $st = $pdo->prepare("SELECT 1 FROM tutorias WHERE profesor_id = ? AND fecha = ? AND estado <> 'cancelada'
        AND id <> ? AND hora_inicio < ? AND hora_fin > ? LIMIT 1");
    $st->execute([$profesorId, $c["fecha"], $excluirId, $c["hora_fin"], $c["hora_inicio"]]);
    return (bool)$st->fetch();
}

function materia_existe(PDO $pdo, int $id): bool
{
    $st = $pdo->prepare("SELECT 1 FROM materias WHERE id = ?");
    $st->execute([$id]);
    return (bool)$st->fetch();
}

function tutoria_propia(PDO $pdo, int $id, array $u): array
{
    $st = $pdo->prepare("SELECT * FROM tutorias WHERE id = ?");
    $st->execute([$id]);
    $t = $st->fetch();
    if (!$t) {
        Response::error("Tutoría no encontrada", -1, 404);
    }
    if ($u["rol"] !== "profesor" || (int)$t["profesor_id"] !== $u["id"]) {
        Response::error("Solo el profesor dueño puede hacer esto", -1, 403);
    }
    return $t;
}

if ($metodo === "GET") {
    if (isset($_GET["id"])) {
        $st = $pdo->prepare(SQL_BASE . " WHERE t.id = ?");
        $st->execute([(int)$_GET["id"]]);
        $t = $st->fetch();
        if (!$t) {
            Response::error("Tutoría no encontrada", -1, 404);
        }
        if ($u["rol"] === "estudiante") {
            $s = $pdo->prepare("SELECT id, estado FROM solicitudes WHERE tutoria_id = ? AND estudiante_id = ?");
            $s->execute([$t["id"], $u["id"]]);
            $t["mi_solicitud"] = $s->fetch() ?: null;
        }
        Response::success($t);
    }

    $where = [];
    $args = [];
    if (!empty($_GET["materia_id"])) { $where[] = "t.materia_id = ?"; $args[] = (int)$_GET["materia_id"]; }
    if (!empty($_GET["tema"]))       { $where[] = "t.tema LIKE ?";    $args[] = "%" . $_GET["tema"] . "%"; }
    if (!empty($_GET["fecha"]))      { $where[] = "t.fecha = ?";      $args[] = $_GET["fecha"]; }
    if (!empty($_GET["profesor"]))   { $where[] = "p.nombre LIKE ?";  $args[] = "%" . $_GET["profesor"] . "%"; }
    if (!empty($_GET["estado"]) && in_array($_GET["estado"], ["abierta", "confirmada", "cancelada"], true)) {
        $where[] = "t.estado = ?";
        $args[] = $_GET["estado"];
    }
    if ($u["rol"] === "profesor" && !empty($_GET["mias"])) {
        $where[] = "t.profesor_id = ?";
        $args[] = $u["id"];
    }

    $sql = SQL_BASE . ($where ? " WHERE " . implode(" AND ", $where) : "") . " ORDER BY t.fecha, t.hora_inicio";
    $st = $pdo->prepare($sql);
    $st->execute($args);
    $lista = $st->fetchAll();

    if ($u["rol"] === "estudiante") {
        $s = $pdo->prepare("SELECT tutoria_id, estado FROM solicitudes WHERE estudiante_id = ?");
        $s->execute([$u["id"]]);
        $mias = [];
        foreach ($s->fetchAll() as $fila) {
            $mias[$fila["tutoria_id"]] = $fila["estado"];
        }
        foreach ($lista as &$t) {
            $t["mi_solicitud_estado"] = $mias[$t["id"]] ?? null;
        }
        unset($t);
    }
    Response::success($lista);
}

if ($metodo === "POST") {
    Auth::requiere($pdo, "profesor");
    $c = validar_tutoria(Request::body());
    if (!materia_existe($pdo, $c["materia_id"])) {
        Response::error("La materia no existe");
    }
    if (hay_traslape($pdo, $u["id"], $c)) {
        Response::error("Ya tienes una tutoría en ese horario", -1, 409);
    }

    $st = $pdo->prepare("INSERT INTO tutorias (profesor_id, materia_id, tema, titulo, descripcion, fecha, hora_inicio, hora_fin, cupo_min, cupo_max)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $st->execute([$u["id"], $c["materia_id"], $c["tema"], $c["titulo"], $c["descripcion"], $c["fecha"],
        $c["hora_inicio"], $c["hora_fin"], $c["cupo_min"], $c["cupo_max"]]);
    Response::success(["id" => (int)$pdo->lastInsertId()], "Tutoría creada", 201);
}

if ($metodo === "PUT") {
    Auth::requiere($pdo, "profesor");
    $d = Request::body();
    $t = tutoria_propia($pdo, (int)($d["id"] ?? 0), $u);
    $ocupados = Cupos::ocupados($pdo, (int)$t["id"]);

    if (($d["accion"] ?? "") === "estado") {
        $nuevo = $d["estado"] ?? "";
        if (!in_array($nuevo, ["abierta", "confirmada", "cancelada"], true)) {
            Response::error("Estado inválido");
        }
        if ($nuevo === "confirmada" && $ocupados < (int)$t["cupo_min"]) {
            Response::error("Aún faltan " . ($t["cupo_min"] - $ocupados) . " inscritos para alcanzar el cupo mínimo (" . $t["cupo_min"] . ")");
        }
        $pdo->prepare("UPDATE tutorias SET estado = ? WHERE id = ?")->execute([$nuevo, $t["id"]]);
        Response::success(null, "Estado actualizado a " . $nuevo);
    }

    $c = validar_tutoria($d);
    if (!materia_existe($pdo, $c["materia_id"])) {
        Response::error("La materia no existe");
    }
    if ($c["cupo_max"] < $ocupados) {
        Response::error("Ya hay $ocupados inscritos; el cupo máximo no puede ser menor");
    }
    if ($t["estado"] === "confirmada" && $ocupados < $c["cupo_min"]) {
        Response::error("La tutoría está confirmada: el cupo mínimo no puede superar a los inscritos actuales (" . $ocupados . ")");
    }
    if (hay_traslape($pdo, $u["id"], $c, (int)$t["id"])) {
        Response::error("Ya tienes una tutoría en ese horario", -1, 409);
    }

    $st = $pdo->prepare("UPDATE tutorias SET materia_id=?, tema=?, titulo=?, descripcion=?, fecha=?, hora_inicio=?, hora_fin=?, cupo_min=?, cupo_max=? WHERE id=?");
    $st->execute([$c["materia_id"], $c["tema"], $c["titulo"], $c["descripcion"], $c["fecha"],
        $c["hora_inicio"], $c["hora_fin"], $c["cupo_min"], $c["cupo_max"], $t["id"]]);
    Response::success(null, "Tutoría actualizada");
}

if ($metodo === "DELETE") {
    Auth::requiere($pdo, "profesor");
    $d = Request::body();
    $t = tutoria_propia($pdo, (int)($d["id"] ?? $_GET["id"] ?? 0), $u);
    $pdo->prepare("DELETE FROM tutorias WHERE id = ?")->execute([$t["id"]]);
    Response::success(null, "Tutoría eliminada");
}

Response::error("Método no permitido", -1, 405);
