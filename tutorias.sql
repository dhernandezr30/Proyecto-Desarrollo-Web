DROP DATABASE IF EXISTS sistema_tutorias;
CREATE DATABASE sistema_tutorias
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE sistema_tutorias;

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

INSERT INTO profesores (nombre, correo, password) VALUES
('Ana Morales',  'ana@uni.edu',   '$2b$10$xH1VwLC8pPDfGU9uU1Y0bep863ic9YbCo8cTcDDCNctyPD1.wYDZ.'),
('Luis Herrera', 'luis@uni.edu',  '$2b$10$xH1VwLC8pPDfGU9uU1Y0bep863ic9YbCo8cTcDDCNctyPD1.wYDZ.');

INSERT INTO estudiantes (nombre, correo, password) VALUES
('Carlos Perez',   'carlos@uni.edu',  '$2b$10$jnD/uq6Q6sDyWS/mJ80XyuTUkcPYIRE/9GVyVnkWW4TLcuB/T/lZC'),
('Maria Lopez',    'maria@uni.edu',   '$2b$10$jnD/uq6Q6sDyWS/mJ80XyuTUkcPYIRE/9GVyVnkWW4TLcuB/T/lZC'),
('Jose Ramirez',   'jose@uni.edu',    '$2b$10$jnD/uq6Q6sDyWS/mJ80XyuTUkcPYIRE/9GVyVnkWW4TLcuB/T/lZC'),
('Sofia Castillo', 'sofia@uni.edu',   '$2b$10$jnD/uq6Q6sDyWS/mJ80XyuTUkcPYIRE/9GVyVnkWW4TLcuB/T/lZC'),
('Diego Ortiz',    'diego@uni.edu',   '$2b$10$jnD/uq6Q6sDyWS/mJ80XyuTUkcPYIRE/9GVyVnkWW4TLcuB/T/lZC'),
('Lucia Mendez',   'lucia@uni.edu',   '$2b$10$jnD/uq6Q6sDyWS/mJ80XyuTUkcPYIRE/9GVyVnkWW4TLcuB/T/lZC');

INSERT INTO materias (nombre) VALUES
('Matematica'), ('Fisica'), ('Programacion'), ('Quimica'), ('Idiomas');

INSERT INTO tutorias (profesor_id, materia_id, tema, titulo, descripcion, fecha, hora_inicio, hora_fin, cupo_min, cupo_max, estado) VALUES
(1, 1, 'Algebra',           'Refuerzo de algebra basica',   'Ecuaciones, factorizacion y sistemas.', CURDATE() + INTERVAL 5 DAY, '08:00', '10:00', 3, 5,  'abierta'),
(1, 1, 'Calculo diferencial','Limites y derivadas',          'Repaso previo al parcial.',             CURDATE() + INTERVAL 8 DAY, '14:00', '16:00', 10, 20, 'abierta'),
(2, 3, 'PHP y MySQL',       'Introduccion a PHP con MySQL', 'CRUD y consultas preparadas.',          CURDATE() + INTERVAL 6 DAY, '10:00', '12:00', 2, 4,  'abierta'),
(2, 2, 'Cinematica',        'Movimiento rectilineo',        'Ejercicios de MRU y MRUA.',             CURDATE() + INTERVAL 3 DAY, '09:00', '11:00', 2, 3,  'confirmada');

INSERT INTO solicitudes (tutoria_id, estudiante_id, estado, fecha_resolucion) VALUES
(1, 1, 'aceptada',  NOW()),
(1, 2, 'aceptada',  NOW()),
(1, 3, 'pendiente', NULL),
(1, 4, 'rechazada', NOW()),
(3, 1, 'aceptada',  NOW()),
(3, 5, 'pendiente', NULL),
(4, 2, 'aceptada',  NOW()),
(4, 5, 'aceptada',  NOW()),
(4, 6, 'aceptada',  NOW()),
(2, 3, 'cancelada', NOW());

INSERT INTO inscripciones (tutoria_id, estudiante_id, solicitud_id)
SELECT tutoria_id, estudiante_id, id FROM solicitudes WHERE estado = 'aceptada';
