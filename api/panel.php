<?php
require_once(__DIR__ . "/Connection.php");
require_once(__DIR__ . "/core/Auth.php");

$pdo = new Connection();
$u = Auth::requiere($pdo);

// Cuenta filas con una consulta preparada
function contar(PDO $pdo, string $sql, array $args): int
{
    $st = $pdo->prepare($sql);
    $st->execute($args);
    return (int)$st->fetchColumn();
}

if ($u["rol"] === "profesor") {
    $id = $u["id"];
    $resumen = [
        "rol" => "profesor",
        "tutorias_total"        => contar($pdo, "SELECT COUNT(*) FROM tutorias WHERE profesor_id = ?", [$id]),
        "tutorias_abiertas"     => contar($pdo, "SELECT COUNT(*) FROM tutorias WHERE profesor_id = ? AND estado = 'abierta'", [$id]),
        "tutorias_confirmadas"  => contar($pdo, "SELECT COUNT(*) FROM tutorias WHERE profesor_id = ? AND estado = 'confirmada'", [$id]),
        "estudiantes_inscritos" => contar($pdo, "SELECT COUNT(*) FROM inscripciones i JOIN tutorias t ON t.id = i.tutoria_id WHERE t.profesor_id = ?", [$id]),
        "solicitudes_pendientes" => contar($pdo, "SELECT COUNT(*) FROM solicitudes s JOIN tutorias t ON t.id = s.tutoria_id WHERE t.profesor_id = ? AND s.estado = 'pendiente'", [$id]),
    ];

    // Progreso de cada tutoria activa hacia su cupo minimo/maximo
    $st = $pdo->prepare("SELECT t.id, t.titulo, t.tema, t.fecha, t.cupo_min, t.cupo_max, t.estado,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.tutoria_id = t.id) AS ocupados
        FROM tutorias t WHERE t.profesor_id = ? AND t.estado <> 'cancelada' ORDER BY t.fecha");
    $st->execute([$id]);
    $resumen["tutorias"] = $st->fetchAll();
    Response::success($resumen);
}

$id = $u["id"];
$por_estado = ["pendiente" => 0, "aceptada" => 0, "rechazada" => 0, "cancelada" => 0];
$st = $pdo->prepare("SELECT estado, COUNT(*) AS n FROM solicitudes WHERE estudiante_id = ? GROUP BY estado");
$st->execute([$id]);
foreach ($st->fetchAll() as $fila) {
    $por_estado[$fila["estado"]] = (int)$fila["n"];
}

$st = $pdo->prepare("SELECT t.id, t.titulo, t.tema, t.fecha, t.hora_inicio, p.nombre AS profesor, t.estado
    FROM inscripciones i JOIN tutorias t ON t.id = i.tutoria_id JOIN profesores p ON p.id = t.profesor_id
    WHERE i.estudiante_id = ? AND t.fecha >= CURDATE() AND t.estado <> 'cancelada'
    ORDER BY t.fecha, t.hora_inicio LIMIT 5");
$st->execute([$id]);

Response::success([
    "rol" => "estudiante",
    "solicitudes" => $por_estado,
    "proximas" => $st->fetchAll(),
    "disponibles" => contar($pdo, "SELECT COUNT(*) FROM tutorias WHERE estado <> 'cancelada' AND fecha >= CURDATE()", []),
]);
