-- Estados persistentes de las fases: pendiente -> en_progreso -> completada.
-- Esta migración conserva etapa_actual y normaliza los lotes existentes.

ALTER TABLE lotes
    ADD COLUMN IF NOT EXISTS estado_fase_siembra
        ENUM('pendiente', 'en_progreso', 'completada') NOT NULL DEFAULT 'pendiente'
        AFTER etapa_cosecha,
    ADD COLUMN IF NOT EXISTS estado_fase_riego
        ENUM('pendiente', 'en_progreso', 'completada') NOT NULL DEFAULT 'pendiente'
        AFTER estado_fase_siembra,
    ADD COLUMN IF NOT EXISTS estado_fase_cosecha
        ENUM('pendiente', 'en_progreso', 'completada') NOT NULL DEFAULT 'pendiente'
        AFTER estado_fase_riego;

UPDATE lotes
SET estado_fase_siembra = CASE
        WHEN etapa_actual = 0 THEN 'pendiente'
        WHEN etapa_actual = 1 THEN 'en_progreso'
        ELSE 'completada'
    END,
    estado_fase_riego = CASE
        WHEN etapa_actual < 2 THEN 'pendiente'
        WHEN etapa_actual = 2 THEN 'en_progreso'
        ELSE 'completada'
    END,
    estado_fase_cosecha = CASE
        WHEN estado_cultivo = 'finalizado' THEN 'completada'
        WHEN etapa_actual = 3 THEN 'en_progreso'
        ELSE 'pendiente'
    END;

SET @pis_phase_status_constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'lotes'
      AND constraint_name = 'chk_lotes_estados_fases'
);
SET @pis_phase_status_constraint_sql = IF(
    @pis_phase_status_constraint_exists > 0,
    'SELECT 1',
    'ALTER TABLE lotes ADD CONSTRAINT chk_lotes_estados_fases CHECK (
        (etapa_actual = 0
            AND estado_fase_siembra = ''pendiente''
            AND estado_fase_riego = ''pendiente''
            AND estado_fase_cosecha = ''pendiente'')
        OR (etapa_actual = 1
            AND estado_fase_siembra = ''en_progreso''
            AND estado_fase_riego = ''pendiente''
            AND estado_fase_cosecha = ''pendiente'')
        OR (etapa_actual = 2
            AND estado_fase_siembra = ''completada''
            AND estado_fase_riego = ''en_progreso''
            AND estado_fase_cosecha = ''pendiente'')
        OR (etapa_actual = 3
            AND estado_fase_siembra = ''completada''
            AND estado_fase_riego = ''completada''
            AND estado_fase_cosecha IN (''en_progreso'', ''completada''))
    )'
);
PREPARE pis_phase_status_constraint_statement FROM @pis_phase_status_constraint_sql;
EXECUTE pis_phase_status_constraint_statement;
DEALLOCATE PREPARE pis_phase_status_constraint_statement;
