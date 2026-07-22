ALTER TABLE control_fitosanitario_tratamientos
    ADD COLUMN cantidad_solicitada DECIMAL(10,2) DEFAULT NULL AFTER dosis,
    ADD COLUMN cantidad_entregada DECIMAL(10,2) DEFAULT NULL AFTER cantidad_solicitada,
    ADD COLUMN estado_entrega ENUM('Pendiente','Aprobado','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente' AFTER estado_resultante,
    ADD COLUMN id_usuario_aprobacion VARCHAR(20) DEFAULT NULL AFTER estado_entrega,
    ADD COLUMN fecha_aprobacion DATETIME DEFAULT NULL AFTER id_usuario_aprobacion,
    ADD COLUMN id_usuario_entrega VARCHAR(20) DEFAULT NULL AFTER fecha_aprobacion,
    ADD COLUMN fecha_entrega DATETIME DEFAULT NULL AFTER id_usuario_entrega,
    ADD KEY idx_cft_estado_entrega (estado_entrega),
    ADD KEY idx_cft_aprobador (id_usuario_aprobacion),
    ADD KEY idx_cft_entrega_usuario (id_usuario_entrega),
    ADD CONSTRAINT fk_cft_aprobador FOREIGN KEY (id_usuario_aprobacion) REFERENCES usuarios (id_usuario),
    ADD CONSTRAINT fk_cft_entrega_usuario FOREIGN KEY (id_usuario_entrega) REFERENCES usuarios (id_usuario);

ALTER TABLE movimientos_insumos
    ADD COLUMN id_tratamiento_fitosanitario INT DEFAULT NULL AFTER id_producto_solicitud,
    ADD KEY idx_mov_tratamiento_fitosanitario (id_tratamiento_fitosanitario),
    ADD CONSTRAINT fk_mov_tratamiento_fitosanitario
        FOREIGN KEY (id_tratamiento_fitosanitario)
        REFERENCES control_fitosanitario_tratamientos (id_tratamiento);
