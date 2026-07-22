ALTER TABLE control_fitosanitario_tratamientos
    ADD COLUMN dosis_recomendada DECIMAL(10,2) DEFAULT NULL AFTER producto_aplicado,
    ADD COLUMN dosis_aplicada DECIMAL(10,2) DEFAULT NULL AFTER dosis_recomendada,
    ADD COLUMN unidad_dosis VARCHAR(30) DEFAULT NULL AFTER dosis_aplicada,
    ADD COLUMN unidad_aplicacion VARCHAR(30) DEFAULT NULL AFTER unidad_dosis,
    ADD COLUMN cantidad_sugerida DECIMAL(10,2) DEFAULT NULL AFTER dosis,
    ADD COLUMN cantidad_aplicada DECIMAL(10,2) DEFAULT NULL AFTER cantidad_sugerida,
    ADD COLUMN motivo_ajuste TEXT DEFAULT NULL AFTER observaciones,
    ADD KEY idx_cft_dosis_aplicada (dosis_aplicada),
    ADD KEY idx_cft_cantidad_aplicada (cantidad_aplicada);
