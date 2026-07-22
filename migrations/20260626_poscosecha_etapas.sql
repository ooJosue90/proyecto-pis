CREATE TABLE IF NOT EXISTS poscosecha_etapas (
    id_etapa INT NOT NULL AUTO_INCREMENT,
    id_poscosecha INT NOT NULL,
    etapa_anterior ENUM('Recepción','Lavado','Clasificación','Empaque','Almacenamiento','Finalizada') DEFAULT NULL,
    etapa_nueva ENUM('Recepción','Lavado','Clasificación','Empaque','Almacenamiento','Finalizada') NOT NULL,
    id_usuario VARCHAR(20) NOT NULL,
    observacion TEXT DEFAULT NULL,
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_etapa),
    KEY idx_poscosecha_etapas_poscosecha (id_poscosecha),
    KEY idx_poscosecha_etapas_usuario (id_usuario),
    KEY idx_poscosecha_etapas_fecha (fecha_cambio),
    CONSTRAINT fk_poscosecha_etapas_poscosecha FOREIGN KEY (id_poscosecha) REFERENCES poscosecha (id_poscosecha),
    CONSTRAINT fk_poscosecha_etapas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO poscosecha_etapas (
    id_poscosecha, etapa_anterior, etapa_nueva, id_usuario, observacion, fecha_cambio
)
SELECT
    p.id_poscosecha,
    NULL,
    p.estado,
    p.id_responsable,
    'Historial inicial generado automáticamente.',
    COALESCE(p.fecha_registro, NOW())
FROM poscosecha p
LEFT JOIN poscosecha_etapas pe ON pe.id_poscosecha = p.id_poscosecha
WHERE pe.id_etapa IS NULL;
