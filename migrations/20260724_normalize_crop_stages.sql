-- Nombres canónicos: Siembra, Riego y Cosecha.
-- Los códigos existentes no cambian: 0=Sin etapa, 1=Siembra, 2=Riego, 3=Cosecha.
ALTER TABLE lotes
    MODIFY COLUMN etapa_actual TINYINT NOT NULL DEFAULT 0
        COMMENT '0=Sin etapa, 1=Siembra, 2=Riego, 3=Cosecha';

-- Normaliza valores textuales heredados sin modificar nombres desconocidos.
UPDATE insumos_agricolas
SET tipo = CASE
    WHEN LOWER(TRIM(tipo)) = 'siembra' THEN 'Siembra'
    WHEN LOWER(TRIM(tipo)) IN ('riego', 'desarrollo', 'crecimiento') THEN 'Riego'
    WHEN LOWER(TRIM(tipo)) = 'cosecha' THEN 'Cosecha'
    ELSE TRIM(tipo)
END
WHERE tipo IS NOT NULL;

UPDATE productos_solicitud
SET etapa = CASE
    WHEN LOWER(TRIM(etapa)) = 'siembra' THEN 'Siembra'
    WHEN LOWER(TRIM(etapa)) IN ('riego', 'desarrollo', 'crecimiento') THEN 'Riego'
    WHEN LOWER(TRIM(etapa)) = 'cosecha' THEN 'Cosecha'
    WHEN LOWER(TRIM(etapa)) IN ('sin etapa', 'ninguna') THEN 'Sin etapa'
    ELSE TRIM(etapa)
END
WHERE etapa IS NOT NULL;
