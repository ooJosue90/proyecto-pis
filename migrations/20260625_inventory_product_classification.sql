ALTER TABLE insumos_agricolas
    ADD COLUMN tipo_producto VARCHAR(100) DEFAULT NULL AFTER tipo,
    ADD COLUMN ingrediente_activo VARCHAR(200) DEFAULT NULL AFTER tipo_producto,
    ADD COLUMN presentacion VARCHAR(100) DEFAULT NULL AFTER ingrediente_activo,
    ADD COLUMN stock_minimo DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cantidad,
    ADD COLUMN uso_fitosanitario TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_minimo,
    ADD KEY idx_insumos_tipo_producto (tipo_producto),
    ADD KEY idx_insumos_uso_fitosanitario (uso_fitosanitario),
    ADD KEY idx_insumos_stock_minimo (stock_minimo);

UPDATE insumos_agricolas
SET tipo_producto = COALESCE(tipo_producto, tipo)
WHERE tipo_producto IS NULL;
