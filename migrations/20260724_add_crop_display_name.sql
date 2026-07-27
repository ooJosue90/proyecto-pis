-- Nombre operativo para distinguir registros de la misma variedad principal.

ALTER TABLE cultivos
    ADD COLUMN IF NOT EXISTS nombre VARCHAR(120) NULL AFTER id_usuario;

UPDATE cultivos
SET nombre = CONCAT('Cultivo ', id_cultivo)
WHERE nombre IS NULL OR TRIM(nombre) = '';

ALTER TABLE cultivos
    MODIFY COLUMN nombre VARCHAR(120) NOT NULL AFTER id_usuario;

SET @pis_crop_name_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'cultivos'
      AND index_name = 'uq_cultivos_usuario_nombre'
);
SET @pis_crop_name_index_sql = IF(
    @pis_crop_name_index_exists > 0,
    'SELECT 1',
    'ALTER TABLE cultivos ADD UNIQUE KEY uq_cultivos_usuario_nombre (id_usuario, nombre)'
);
PREPARE pis_crop_name_index_statement FROM @pis_crop_name_index_sql;
EXECUTE pis_crop_name_index_statement;
DEALLOCATE PREPARE pis_crop_name_index_statement;
