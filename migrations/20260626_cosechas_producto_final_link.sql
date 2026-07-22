ALTER TABLE cosechas
    ADD COLUMN IF NOT EXISTS id_producto_final INT DEFAULT NULL AFTER fecha_recepcion;

ALTER TABLE cosechas
    ADD KEY IF NOT EXISTS idx_cosechas_producto_final (id_producto_final);

SET @fk_cosechas_producto_final_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'cosechas'
      AND CONSTRAINT_NAME = 'fk_cosechas_producto_final'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
);

SET @fk_cosechas_producto_final_sql := IF(
    @fk_cosechas_producto_final_exists = 0,
    'ALTER TABLE cosechas ADD CONSTRAINT fk_cosechas_producto_final FOREIGN KEY (id_producto_final) REFERENCES productos_finales (id_producto_final)',
    'SELECT 1'
);

PREPARE fk_cosechas_producto_final_stmt FROM @fk_cosechas_producto_final_sql;
EXECUTE fk_cosechas_producto_final_stmt;
DEALLOCATE PREPARE fk_cosechas_producto_final_stmt;
