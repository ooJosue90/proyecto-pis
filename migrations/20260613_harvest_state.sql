ALTER TABLE lotes
    ADD COLUMN IF NOT EXISTS estado_cultivo
        ENUM('activo', 'en_cosecha', 'finalizado', 'cancelado')
        NOT NULL DEFAULT 'activo'
        AFTER etapa_actual,
    ADD COLUMN IF NOT EXISTS fecha_fin_cosecha_real DATE NULL
        AFTER fecha_fin_cosecha;

UPDATE lotes l
LEFT JOIN (
    SELECT id_lote, COUNT(*) AS total
    FROM productos_finales
    GROUP BY id_lote
) pf ON pf.id_lote = l.id_lote
SET
    l.estado_cultivo = CASE
        WHEN COALESCE(pf.total, 0) > 0 THEN 'finalizado'
        WHEN l.etapa_actual = 3 OR l.etapa_cosecha = 1 THEN 'en_cosecha'
        ELSE 'activo'
    END,
    l.fecha_fin_cosecha_real = CASE
        WHEN COALESCE(pf.total, 0) > 0 THEN (
            SELECT DATE(MAX(pf2.fecha))
            FROM productos_finales pf2
            WHERE pf2.id_lote = l.id_lote
        )
        ELSE l.fecha_fin_cosecha_real
    END
WHERE l.estado_cultivo = 'activo'
   OR l.estado_cultivo IS NULL;
