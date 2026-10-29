<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Request.php");
require_once(__DIR__ . "/core/Auth.php");
require_once(__DIR__ . "/core/Cupos.php");

$pdo = new Connection();
$u = Auth::requiere($pdo);
$metodo = $_SERVER["REQUEST_METHOD"];

function abortar(PDO $pdo, string $mensaje, int $status = 400): void
{
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    Response::error($mensaje, -1, $status);
}

if ($metodo === "GET") {
    if ($u["rol"] === "estudiante") {
        $st = $pdo->prepare("SELECT s.id, s.estado, s.fecha_solicitud, s.fecha_resolucion,
                t.id AS tutoria_id, t.titulo, t.tema, m.nombre AS materia, p.nombre AS profesor,
                t.fecha, t.hora_inicio, t.hora_fin, t.estado AS estado_tutoria
            FROM solicitudes s
            JOIN tutorias t ON t.id = s.tutoria_id
            JOIN materias m ON m.id = t.materia_id
            JOIN profesores p ON p.id = t.profesor_id
            WHERE s.estudiante_id = ?
            ORDER BY s.fecha_solicitud DESC");
        $st->execute([$u["id"]]);
        Response::success($st->fetchAll());
    }

    $tid = (int)($_GET["tutoria_id"] ?? 0);
    $chk = $pdo->prepare("SELECT 1 FROM tutorias WHERE id = ? AND profesor_id = ?");
    $chk->execute([$tid, $u["id"]]);
    if (!$chk->fetch()) {
        Response::error("Tutoría no encontrada", -1, 404);
    }

    $st = $pdo->prepare("SELECT s.id, s.estado, s.fecha_solicitud, s.fecha_resolucion, e.nombre AS estudiante, e.correo
        FROM solicitudes s JOIN estudiantes e ON e.id = s.estudiante_id
        WHERE s.tutoria_id = ?
        ORDER BY FIELD(s.estado, 'pendiente', 'aceptada', 'rechazada', 'cancelada'), s.fecha_solicitud");
    $st->execute([$tid]);
    Response::success($st->fetchAll());
}

if ($metodo === "POST") {
    Auth::requiere($pdo, "estudiante");
    $tid = (int)(Request::body()["tutoria_id"] ?? 0);

    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT * FROM tutorias WHERE id = ? FOR UPDATE");
    $st->execute([$tid]);
    $t = $st->fetch();
    if (!$t) {
        abortar($pdo, "Tutoría no encontrada", 404);
    }
    if ($t["estado"] === "cancelada") {
        abortar($pdo, "Esta tutoría fue cancelada");
    }
    if ($t["fecha"] . " " . $t["hora_inicio"] <= date("Y-m-d H:i:s")) {
        abortar($pdo, "Esta tutoría ya pasó");
    }
    if (Cupos::ocupados($pdo, $tid) >= (int)$t["cupo_max"]) {
        abortar($pdo, "Sin cupos disponibles", 409);
    }

    $st = $pdo->prepare("SELECT id, estado FROM solicitudes WHERE tutoria_id = ? AND estudiante_id = ?");
    $st->execute([$tid, $u["id"]]);
    $previa = $st->fetch();

    if ($previa) {
        if ($previa["estado"] !== "cancelada") {
            abortar($pdo, "Ya tienes una solicitud para esta tutoría (" . $previa["estado"] . ")", 409);
        }
        $pdo->prepare("UPDATE solicitudes SET estado='pendiente', fecha_solicitud=NOW(), fecha_resolucion=NULL WHERE id=?")->execute([$previa["id"]]);
    } else {
        $pdo->prepare("INSERT INTO solicitudes (tutoria_id, estudiante_id) VALUES (?, ?)")->execute([$tid, $u["id"]]);
    }
    $pdo->commit();
    Response::success(null, "Solicitud enviada, queda pendiente de aprobación", 201);
}

if ($metodo === "PUT") {
    Auth::requiere($pdo, "profesor");
    $d = Request::body();
    $sid = (int)($d["id"] ?? 0);
    $nuevo = $d["estado"] ?? "";
    if (!in_array($nuevo, ["aceptada", "rechazada"], true)) {
        Response::error("Estado inválido");
    }

    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT s.*, t.profesor_id, t.estado AS estado_tutoria, t.cupo_max, t.fecha, t.hora_inicio
        FROM solicitudes s JOIN tutorias t ON t.id = s.tutoria_id WHERE s.id = ? FOR UPDATE");
    $st->execute([$sid]);
    $s = $st->fetch();
    if (!$s) {
        abortar($pdo, "Solicitud no encontrada", 404);
    }
    if ((int)$s["profesor_id"] !== $u["id"]) {
        abortar($pdo, "Esta solicitud no es de una tutoría tuya", 403);
    }
    if ($s["estado"] !== "pendiente") {
        abortar($pdo, "La solicitud ya fue resuelta (" . $s["estado"] . ")", 409);
    }
    if ($s["estado_tutoria"] === "cancelada") {
        abortar($pdo, "La tutoría está cancelada");
    }

    if ($nuevo === "aceptada") {
        if ($s["fecha"] . " " . $s["hora_inicio"] <= date("Y-m-d H:i:s")) {
            abortar($pdo, "La tutoría ya empezó: no se pueden aceptar más estudiantes");
        }
        $pdo->prepare("SELECT id FROM tutorias WHERE id = ? FOR UPDATE")->execute([$s["tutoria_id"]]);
        if (Cupos::ocupados($pdo, (int)$s["tutoria_id"]) >= (int)$s["cupo_max"]) {
            abortar($pdo, "Sin cupos disponibles: se alcanzó el cupo máximo", 409);
        }
        $pdo->prepare("INSERT INTO inscripciones (tutoria_id, estudiante_id, solicitud_id) VALUES (?, ?, ?)")
            ->execute([$s["tutoria_id"], $s["estudiante_id"], $s["id"]]);
    }
    $pdo->prepare("UPDATE solicitudes SET estado = ?, fecha_resolucion = NOW() WHERE id = ?")->execute([$nuevo, $sid]);
    $pdo->commit();
    Response::success(null, "Solicitud " . $nuevo);
}

if ($metodo === "DELETE") {
    Auth::requiere($pdo, "estudiante");
    $sid = (int)(Request::body()["id"] ?? $_GET["id"] ?? 0);

    $pdo->beginTransaction();
    $st = $pdo->prepare("SELECT s.*, t.estado AS estado_tutoria, t.cupo_min
        FROM solicitudes s JOIN tutorias t ON t.id = s.tutoria_id WHERE s.id = ? AND s.estudiante_id = ? FOR UPDATE");
    $st->execute([$sid, $u["id"]]);
    $s = $st->fetch();
    if (!$s) {
        abortar($pdo, "Solicitud no encontrada", 404);
    }
    if (!in_array($s["estado"], ["pendiente", "aceptada"], true)) {
        abortar($pdo, "Esta solicitud ya no se puede cancelar", 409);
    }

    if ($s["estado"] === "aceptada") {
        $pdo->prepare("DELETE FROM inscripciones WHERE solicitud_id = ?")->execute([$sid]);
        if ($s["estado_tutoria"] === "confirmada" && Cupos::ocupados($pdo, (int)$s["tutoria_id"]) < (int)$s["cupo_min"]) {
            $pdo->prepare("UPDATE tutorias SET estado = 'abierta' WHERE id = ?")->execute([$s["tutoria_id"]]);
        }
    }
    $pdo->prepare("UPDATE solicitudes SET estado = 'cancelada', fecha_resolucion = NOW() WHERE id = ?")->execute([$sid]);
    $pdo->commit();
    Response::success(null, "Solicitud cancelada");
}

Response::error("Método no permitido", -1, 405);
