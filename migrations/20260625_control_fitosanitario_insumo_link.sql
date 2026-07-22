ALTER TABLE control_fitosanitario
    ADD COLUMN id_insumo INT DEFAULT NULL AFTER id_usuario,
    ADD KEY idx_cf_insumo (id_insumo),
    ADD CONSTRAINT fk_cf_insumo FOREIGN KEY (id_insumo) REFERENCES insumos_agricolas (id_insumos);

ALTER TABLE control_fitosanitario_tratamientos
    ADD COLUMN id_insumo INT DEFAULT NULL AFTER id_usuario,
    ADD KEY idx_cft_insumo (id_insumo),
    ADD CONSTRAINT fk_cft_insumo FOREIGN KEY (id_insumo) REFERENCES insumos_agricolas (id_insumos);
