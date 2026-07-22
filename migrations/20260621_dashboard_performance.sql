-- Índices para las métricas y la actividad del dashboard administrativo.
-- Ejecutar una sola vez sobre la base de datos local.

CREATE INDEX IF NOT EXISTS idx_solicitudes_estado_fecha
    ON productos_solicitud (estado, fecha);

CREATE INDEX IF NOT EXISTS idx_facturas_estado_fecha
    ON facturas_compra (estado, fecha_registro);

CREATE INDEX IF NOT EXISTS idx_pedidos_estado_fecha
    ON pedidos (estado, fecha);

CREATE INDEX IF NOT EXISTS idx_usuarios_rol_fecha
    ON usuarios (rol, fecha_registro);

CREATE INDEX IF NOT EXISTS idx_notificaciones_leida_fecha
    ON notificaciones (leida, fecha);

CREATE INDEX IF NOT EXISTS idx_insumos_cantidad
    ON insumos_agricolas (cantidad);

CREATE INDEX IF NOT EXISTS idx_productos_factura_cantidad
    ON productos_factura (cantidad);
