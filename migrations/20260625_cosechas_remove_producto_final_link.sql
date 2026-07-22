ALTER TABLE cosechas
    DROP FOREIGN KEY fk_cosechas_producto_final;

ALTER TABLE cosechas
    DROP INDEX idx_cosechas_producto_final;

ALTER TABLE cosechas
    DROP COLUMN id_producto_final;
