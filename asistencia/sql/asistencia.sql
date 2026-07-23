-- BASE DE DATOS: asistencia

-- 1. TABLA: admins (Administradores)

CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(25) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA: cargos (Catálogo de puestos)

CREATE TABLE IF NOT EXISTS cargos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA: motivos_justificacion

CREATE TABLE IF NOT EXISTS motivos_justificacion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA: trabajadores

CREATE TABLE IF NOT EXISTS trabajadores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cve VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(50) NOT NULL,
    apellidoP VARCHAR(50) NOT NULL,
    apellidoM VARCHAR(50) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    cargo_id INT NOT NULL,
    activo TINYINT(1) DEFAULT 1 COMMENT '1 = activo, 0 = dado de baja',
    FOREIGN KEY (cargo_id) REFERENCES cargos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA: asistencia (Registros de asistencia)

CREATE TABLE IF NOT EXISTS asistencia (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trabajador_id INT NOT NULL,
    fecha_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo ENUM('Entrada', 'Salida', 'Justificada', 'SalidaJustificada') NOT NULL,
    motivo_id INT DEFAULT NULL,
    motivo_otro TEXT DEFAULT NULL,
    comprobante_path VARCHAR(255) DEFAULT NULL,
    fecha_justificacion DATE DEFAULT NULL,
    latitud DECIMAL(10,8) DEFAULT NULL,
    longitud DECIMAL(11,8) DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    FOREIGN KEY (trabajador_id) REFERENCES trabajadores(id) ON DELETE CASCADE,
    FOREIGN KEY (motivo_id) REFERENCES motivos_justificacion(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. ÍNDICES PARA OPTIMIZACIÓN
CREATE INDEX idx_asistencia_trabajador ON asistencia(trabajador_id);
CREATE INDEX idx_asistencia_fecha ON asistencia(fecha_hora);
CREATE INDEX idx_asistencia_tipo ON asistencia(tipo);
CREATE INDEX idx_trabajadores_cve ON trabajadores(cve);

-- 7. DATOS INICIALES (DE PRUEBA)

-- 7.1 Administrador (contraseña: admin1234)
-- El hash corresponde a: admin1234
INSERT INTO admins (usuario, password) VALUES 
('admin', '$2a$12$nYIEl7lkdcEndAgOPifV1uBFITAlZwXxEju3dDpO6YAYqZSppINkG');

-- 7.2 Cargos básicos
INSERT INTO cargos (nombre) VALUES 
('Administrador'),
('Operador'),
('Supervisor'),
('Vendedor'),
('Gerente'),
('Asistente');

-- 7.3 Motivos de justificación
INSERT INTO motivos_justificacion (nombre) VALUES 
('Enfermedad'),
('Permiso personal'),
('Cita médica'),
('Asunto familiar'),
('Problemas de transporte'),
('Fallecimiento familiar');

-- 8. VISTAS PARA REPORTES (OPCIONAL)

-- 8.1 Vista de resumen diario
CREATE VIEW v_resumen_diario AS
SELECT 
    t.cve,
    CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) AS trabajador,
    DATE(a.fecha_hora) AS fecha,
    MAX(CASE WHEN a.tipo = 'Entrada' THEN TIME(a.fecha_hora) END) AS hora_entrada,
    MAX(CASE WHEN a.tipo IN ('Salida', 'SalidaJustificada') THEN TIME(a.fecha_hora) END) AS hora_salida
FROM asistencia a
JOIN trabajadores t ON a.trabajador_id = t.id
WHERE a.tipo IN ('Entrada', 'Salida', 'SalidaJustificada')
GROUP BY t.id, DATE(a.fecha_hora);

-- 8.2 Vista de detalles de asistencia
CREATE VIEW v_asistencia_detalle AS
SELECT 
    a.id,
    t.cve,
    CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) AS nombre_completo,
    c.nombre AS cargo,
    DATE(a.fecha_hora) AS fecha,
    TIME(a.fecha_hora) AS hora,
    a.tipo,
    a.latitud,
    a.longitud,
    a.ip,
    a.motivo_id,
    a.motivo_otro,
    a.comprobante_path,
    a.fecha_justificacion,
    m.nombre AS motivo_nombre
FROM asistencia a
JOIN trabajadores t ON a.trabajador_id = t.id
LEFT JOIN cargos c ON t.cargo_id = c.id
LEFT JOIN motivos_justificacion m ON a.motivo_id = m.id
WHERE a.tipo IN ('Entrada', 'Salida', 'Justificada', 'SalidaJustificada');

