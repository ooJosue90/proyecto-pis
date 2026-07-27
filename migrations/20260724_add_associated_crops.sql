-- Cultivo principal fijo: Mango Tommy Atkins.
-- Los cultivos complementarios se almacenan en una relación normalizada.

CREATE TABLE IF NOT EXISTS cultivos_asociados (
    id_cultivo_asociado INT NOT NULL AUTO_INCREMENT,
    id_cultivo INT NOT NULL,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    fecha_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cultivo_asociado),
    UNIQUE KEY uq_cultivo_asociado_codigo (id_cultivo, codigo),
    KEY idx_cultivos_asociados_codigo (codigo),
    CONSTRAINT fk_cultivos_asociados_cultivo
        FOREIGN KEY (id_cultivo)
        REFERENCES cultivos (id_cultivo)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Normaliza registros previos sin modificar fechas, propietarios ni lotes.
UPDATE cultivos
SET tipo = 'Mango Tommy Atkins'
WHERE tipo <> 'Mango Tommy Atkins';
