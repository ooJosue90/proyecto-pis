-- Secuencia única: 0=Sin etapa, 1=Siembra, 2=Riego, 3=Cosecha.
-- Conserva etapa_actual y reconstruye los indicadores acumulativos.
UPDATE lotes
SET etapa_actual = CASE
        WHEN etapa_actual BETWEEN 0 AND 3 THEN etapa_actual
        WHEN etapa_cosecha = 1 THEN 3
        WHEN etapa_riego = 1 THEN 2
        WHEN etapa_siembra = 1 THEN 1
        ELSE 0
    END;

UPDATE lotes
SET etapa_siembra = CASE WHEN etapa_actual >= 1 THEN 1 ELSE 0 END,
    etapa_riego = CASE WHEN etapa_actual >= 2 THEN 1 ELSE 0 END,
    etapa_cosecha = CASE WHEN etapa_actual = 3 THEN 1 ELSE 0 END;

-- Ordena físicamente las columnas del cronograma sin cambiar sus datos.
ALTER TABLE lotes
    MODIFY COLUMN etapa_siembra TINYINT(1) NOT NULL DEFAULT 0 AFTER estado_cultivo,
    MODIFY COLUMN etapa_riego TINYINT(1) NOT NULL DEFAULT 0 AFTER etapa_siembra,
    MODIFY COLUMN etapa_cosecha TINYINT(1) NOT NULL DEFAULT 0 AFTER etapa_riego,
    MODIFY COLUMN fecha_inicio_siembra DATE NULL AFTER etapa_cosecha,
    MODIFY COLUMN fecha_fin_siembra DATE NULL AFTER fecha_inicio_siembra,
    MODIFY COLUMN fecha_inicio_riego DATE NULL AFTER fecha_fin_siembra,
    MODIFY COLUMN fecha_fin_riego DATE NULL AFTER fecha_inicio_riego,
    MODIFY COLUMN fecha_inicio_cosecha DATE NULL AFTER fecha_fin_riego,
    MODIFY COLUMN fecha_fin_cosecha DATE NULL AFTER fecha_inicio_cosecha;

-- Impide saltos incluso si se escribe directamente desde phpMyAdmin.
SET @pis_stage_constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'lotes'
      AND constraint_name = 'chk_lotes_etapas_secuencia'
);
SET @pis_stage_constraint_sql = IF(
    @pis_stage_constraint_exists > 0,
    'SELECT 1',
    'ALTER TABLE lotes ADD CONSTRAINT chk_lotes_etapas_secuencia CHECK (
        (etapa_actual = 0 AND etapa_siembra = 0 AND etapa_riego = 0 AND etapa_cosecha = 0)
        OR (etapa_actual = 1 AND etapa_siembra = 1 AND etapa_riego = 0 AND etapa_cosecha = 0)
        OR (etapa_actual = 2 AND etapa_siembra = 1 AND etapa_riego = 1 AND etapa_cosecha = 0)
        OR (etapa_actual = 3 AND etapa_siembra = 1 AND etapa_riego = 1 AND etapa_cosecha = 1)
    )'
);
PREPARE pis_stage_constraint_statement FROM @pis_stage_constraint_sql;
EXECUTE pis_stage_constraint_statement;
DEALLOCATE PREPARE pis_stage_constraint_statement;

-- Acelera las consultas que presentan lotes en orden de etapa.
SET @pis_stage_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'lotes'
      AND index_name = 'idx_lotes_etapa_actual'
);
SET @pis_stage_index_sql = IF(
    @pis_stage_index_exists > 0,
    'SELECT 1',
    'ALTER TABLE lotes ADD INDEX idx_lotes_etapa_actual (etapa_actual, id_lote)'
);
PREPARE pis_stage_index_statement FROM @pis_stage_index_sql;
EXECUTE pis_stage_index_statement;
DEALLOCATE PREPARE pis_stage_index_statement;
