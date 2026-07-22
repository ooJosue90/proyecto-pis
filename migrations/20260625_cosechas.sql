CREATE TABLE IF NOT EXISTS cosechas (
    id_cosecha INT NOT NULL AUTO_INCREMENT,
    id_lote INT NOT NULL,
    id_usuario VARCHAR(20) NOT NULL,
    fecha_cosecha DATE NOT NULL,
    cantidad_total_kg DECIMAL(10,2) NOT NULL,
    calidad_primera_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    calidad_segunda_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    descarte_kg DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('Registrada','Validada','Rechazada','Recibida') NOT NULL DEFAULT 'Registrada',
    observaciones TEXT DEFAULT NULL,
    observaciones_admin TEXT DEFAULT NULL,
    id_usuario_valida VARCHAR(20) DEFAULT NULL,
    fecha_validacion DATETIME DEFAULT NULL,
    id_usuario_recibe VARCHAR(20) DEFAULT NULL,
    fecha_recepcion DATETIME DEFAULT NULL,
    id_producto_final INT DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cosecha),
    KEY idx_cosechas_lote (id_lote),
    KEY idx_cosechas_usuario (id_usuario),
    KEY idx_cosechas_estado (estado),
    KEY idx_cosechas_fecha (fecha_cosecha),
    KEY idx_cosechas_usuario_valida (id_usuario_valida),
    KEY idx_cosechas_usuario_recibe (id_usuario_recibe),
    KEY idx_cosechas_producto_final (id_producto_final),
    CONSTRAINT fk_cosechas_lote FOREIGN KEY (id_lote) REFERENCES lotes (id_lote),
    CONSTRAINT fk_cosechas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_cosechas_usuario_valida FOREIGN KEY (id_usuario_valida) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_cosechas_usuario_recibe FOREIGN KEY (id_usuario_recibe) REFERENCES usuarios (id_usuario),
    CONSTRAINT fk_cosechas_producto_final FOREIGN KEY (id_producto_final) REFERENCES productos_finales (id_producto_final),
    CONSTRAINT chk_cosechas_cantidad_total CHECK (cantidad_total_kg > 0),
    CONSTRAINT chk_cosechas_calidades CHECK (
        calidad_primera_kg >= 0
        AND calidad_segunda_kg >= 0
        AND descarte_kg >= 0
        AND calidad_primera_kg + calidad_segunda_kg + descarte_kg <= cantidad_total_kg
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE notificaciones
    ADD COLUMN IF NOT EXISTS rol_destino ENUM('Administrador','Agricultor','Bodeguero') DEFAULT NULL AFTER mensaje,
    ADD KEY IF NOT EXISTS idx_notificaciones_rol_destino (rol_destino);
