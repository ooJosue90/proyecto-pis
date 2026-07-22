CREATE TABLE IF NOT EXISTS poscosecha (
    id_poscosecha INT NOT NULL AUTO_INCREMENT,
    id_cosecha INT NOT NULL,
    id_lote INT NOT NULL,
    id_responsable VARCHAR(20) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    kg_recibidos DECIMAL(10,2) NOT NULL,
    kg_lavados DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_clasificados DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_primera DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_segunda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_descarte DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_merma DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    motivo_merma TEXT DEFAULT NULL,
    kg_exportacion DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_mercado_nacional DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kg_procesamiento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    destino_previsto ENUM('Exportación','Mercado nacional','Procesamiento') NOT NULL DEFAULT 'Exportación',
    estado ENUM('Recepción','Lavado','Clasificación','Empaque','Almacenamiento','Finalizada') NOT NULL DEFAULT 'Recepción',
    listo_para_despacho TINYINT(1) NOT NULL DEFAULT 0,
    observaciones TEXT DEFAULT NULL,
    fecha_finalizacion DATETIME DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_poscosecha),
    UNIQUE KEY uq_poscosecha_cosecha (id_cosecha),
    KEY idx_poscosecha_lote (id_lote),
    KEY idx_poscosecha_responsable (id_responsable),
    KEY idx_poscosecha_estado (estado),
    KEY idx_poscosecha_listo_despacho (estado, listo_para_despacho),
    CONSTRAINT fk_poscosecha_cosecha FOREIGN KEY (id_cosecha) REFERENCES cosechas (id_cosecha),
    CONSTRAINT fk_poscosecha_lote FOREIGN KEY (id_lote) REFERENCES lotes (id_lote),
    CONSTRAINT fk_poscosecha_responsable FOREIGN KEY (id_responsable) REFERENCES usuarios (id_usuario),
    CONSTRAINT chk_poscosecha_kg_no_negativos CHECK (
        kg_recibidos >= 0
        AND kg_lavados >= 0
        AND kg_clasificados >= 0
        AND kg_primera >= 0
        AND kg_segunda >= 0
        AND kg_descarte >= 0
        AND kg_merma >= 0
        AND kg_exportacion >= 0
        AND kg_mercado_nacional >= 0
        AND kg_procesamiento >= 0
    ),
    CONSTRAINT chk_poscosecha_clasificados CHECK (kg_clasificados <= kg_recibidos),
    CONSTRAINT chk_poscosecha_balance_calidad CHECK (
        kg_primera + kg_segunda + kg_descarte + kg_merma <= kg_recibidos
    ),
    CONSTRAINT chk_poscosecha_balance_destino CHECK (
        kg_exportacion + kg_mercado_nacional + kg_procesamiento <= kg_recibidos
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS poscosecha_estado_historial (
    id_historial INT NOT NULL AUTO_INCREMENT,
    id_poscosecha INT NOT NULL,
    estado_anterior ENUM('Recepción','Lavado','Clasificación','Empaque','Almacenamiento','Finalizada') DEFAULT NULL,
    estado_nuevo ENUM('Recepción','Lavado','Clasificación','Empaque','Almacenamiento','Finalizada') NOT NULL,
    id_usuario VARCHAR(20) NOT NULL,
    observaciones TEXT DEFAULT NULL,
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_historial),
    KEY idx_poscosecha_historial_poscosecha (id_poscosecha),
    KEY idx_poscosecha_historial_usuario (id_usuario),
    CONSTRAINT fk_poscosecha_historial_poscosecha FOREIGN KEY (id_poscosecha) REFERENCES poscosecha (id_poscosecha),
    CONSTRAINT fk_poscosecha_historial_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
