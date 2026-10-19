<?php
class Cupos
{
    // Cuantos estudiantes tienen el cupo confirmado en una tutoria
    public static function ocupados(PDO $pdo, int $tutoriaId): int
    {
        $st = $pdo->prepare("SELECT COUNT(*) FROM inscripciones WHERE tutoria_id = ?");
        $st->execute([$tutoriaId]);
        return (int)$st->fetchColumn();
    }
}
