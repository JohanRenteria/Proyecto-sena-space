-- ============================================================
-------------------------------------------
--LOG-IN AGREGAR A BD (NO OLVIDAR)
-------------------------------------------
-- ============================================================

USE sena_space;

ALTER TABLE admin
    ADD COLUMN IF NOT EXISTS rol_sistema VARCHAR(30) NOT NULL DEFAULT 'Usuario';

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    correo VARCHAR(254) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY uq_password_resets_correo (correo),
    INDEX idx_password_resets_expiry (expires_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- Cuenta demo administrativa ya existente en el proyecto.
-- Correo: kd@gmail.com / contraseña actual: 0
UPDATE admin
    SET rol_sistema = 'Administrador'
    WHERE correo = 'kd@gmail.com';

CREATE TABLE IF NOT EXISTS recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    stock_total INT NOT NULL DEFAULT 1,
    estado ENUM('Disponible', 'Mantenimiento', 'Baja') NOT NULL DEFAULT 'Disponible',
    stock_minimo INT NOT NULL DEFAULT 1,
    tipo ENUM('Objeto', 'Servicio') NOT NULL DEFAULT 'Objeto',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recursos_estado (estado),
    INDEX idx_recursos_categoria (categoria)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    recurso_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_devolucion DATE NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    estado ENUM('Pendiente', 'Aprobado', 'Rechazado', 'Devuelto') NOT NULL DEFAULT 'Pendiente',
    observacion TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion DATETIME NULL,
    fecha_devolucion_real DATETIME NULL,
    devolucion_solicitada TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_solicitudes_usuario (usuario_id),
    INDEX idx_solicitudes_recurso (recurso_id),
    INDEX idx_solicitudes_estado (estado),
    CONSTRAINT fk_solicitudes_usuario FOREIGN KEY (usuario_id) REFERENCES admin(cedula) ON UPDATE CASCADE,
    CONSTRAINT fk_solicitudes_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS reposiciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recurso_id INT NOT NULL,
    cantidad_solicitada INT NOT NULL,
    estado ENUM('Solicitada', 'Atendida', 'Cancelada') NOT NULL DEFAULT 'Solicitada',
    observacion TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reposiciones_recurso FOREIGN KEY (recurso_id) REFERENCES recursos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

CREATE TABLE IF NOT EXISTS historial_recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    accion VARCHAR(80) NOT NULL,
    detalle TEXT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historial_fecha (fecha),
    CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES admin(cedula) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

INSERT INTO recursos (nombre, categoria, stock_total, estado, stock_minimo, tipo)
SELECT 'Par de raquetas', 'Deportes', 4, 'Disponible', 2, 'Objeto'
WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Par de raquetas');

INSERT INTO recursos (nombre, categoria, stock_total, estado, stock_minimo, tipo)
SELECT 'Balon de futbol', 'Deportes', 5, 'Disponible', 2, 'Objeto'
WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Balon de futbol');

INSERT INTO recursos (nombre, categoria, stock_total, estado, stock_minimo, tipo)
SELECT 'Balon de basquet', 'Deportes', 3, 'Disponible', 1, 'Objeto'
WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Balon de basquet');

INSERT INTO recursos (nombre, categoria, stock_total, estado, stock_minimo, tipo)
SELECT 'Cancha de futbol', 'Deportes', 1, 'Disponible', 1, 'Objeto'
WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Cancha de futbol');

INSERT INTO recursos (nombre, categoria, stock_total, estado, stock_minimo, tipo)
SELECT 'Servicio de deporte', 'Servicios', 1, 'Disponible', 1, 'Servicio'
WHERE NOT EXISTS (SELECT 1 FROM recursos WHERE nombre = 'Servicio de deporte');
