-- Sistema de gestion de tutorias - esquema y datos de prueba
-- Importar desde phpMyAdmin o: mysql -u root < sql/tutorias.sql

DROP DATABASE IF EXISTS sistema_tutorias;
CREATE DATABASE sistema_tutorias
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sistema_tutorias;

-- estado: 1 = cuenta activa, 0 = desactivada
CREATE TABLE profesores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE estudiantes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE materias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE tutorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profesor_id INT UNSIGNED NOT NULL,
    materia_id INT UNSIGNED NOT NULL,
    tema VARCHAR(120) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    cupo_min INT UNSIGNED NOT NULL,
    cupo_max INT UNSIGNED NOT NULL,
    estado ENUM('abierta','confirmada','cancelada') NOT NULL DEFAULT 'abierta',
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tut_prof FOREIGN KEY (profesor_id) REFERENCES profesores(id) ON DELETE CASCADE,
    CONSTRAINT fk_tut_mat FOREIGN KEY (materia_id) REFERENCES materias(id),
    CONSTRAINT chk_cupos CHECK (cupo_min >= 1 AND cupo_min <= cupo_max),
    CONSTRAINT chk_horas CHECK (hora_fin > hora_inicio)
);

-- Historial: cada peticion del estudiante queda registrada, antes y despues de resolverse
CREATE TABLE solicitudes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutoria_id INT UNSIGNED NOT NULL,
    estudiante_id INT UNSIGNED NOT NULL,
    estado ENUM('pendiente','aceptada','rechazada','cancelada') NOT NULL DEFAULT 'pendiente',
    fecha_solicitud TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_resolucion DATETIME NULL,
    CONSTRAINT fk_sol_tut FOREIGN KEY (tutoria_id) REFERENCES tutorias(id) ON DELETE CASCADE,
    CONSTRAINT fk_sol_est FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT uq_solicitud UNIQUE (tutoria_id, estudiante_id)
);

-- Cupo confirmado: se crea al aceptar una solicitud
CREATE TABLE inscripciones (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutoria_id INT UNSIGNED NOT NULL,
    estudiante_id INT UNSIGNED NOT NULL,
    solicitud_id INT UNSIGNED NOT NULL,
    fecha_creacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ins_tut FOREIGN KEY (tutoria_id) REFERENCES tutorias(id) ON DELETE CASCADE,
    CONSTRAINT fk_ins_est FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ins_sol FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id) ON DELETE CASCADE,
    CONSTRAINT uq_inscripcion UNIQUE (tutoria_id, estudiante_id)
);
