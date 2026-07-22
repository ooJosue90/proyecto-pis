ALTER TABLE insumos_agricolas
    ADD COLUMN dosis_recomendada DECIMAL(10,2) DEFAULT NULL AFTER uso_fitosanitario,
    ADD COLUMN unidad_dosis VARCHAR(30) DEFAULT NULL AFTER dosis_recomendada,
    ADD COLUMN unidad_aplicacion VARCHAR(30) DEFAULT NULL AFTER unidad_dosis,
    ADD COLUMN intervalo_seguridad INT DEFAULT NULL AFTER unidad_aplicacion,
    ADD COLUMN periodo_reingreso INT DEFAULT NULL AFTER intervalo_seguridad,
    ADD KEY idx_insumos_dosis_recomendada (dosis_recomendada);

UPDATE insumos_agricolas
SET dosis_recomendada = CASE nombre
    WHEN 'Amistar 250 SC' THEN 0.50
    WHEN 'Score 250 EC' THEN 0.40
    WHEN 'Folicur 250 EW' THEN 0.50
    WHEN 'Tilt 250 EC' THEN 0.50
    WHEN 'Dithane M-45' THEN 2.00
    WHEN 'Kocide 2000' THEN 1.50
    WHEN 'Cuprofix Ultra' THEN 2.00
    WHEN 'Ridomil Gold MZ' THEN 2.50
    WHEN 'Aliette 80 WG' THEN 2.00
    WHEN 'Bravo 720 SC' THEN 1.50
    WHEN 'Actara 25 WG' THEN 0.20
    WHEN 'Karate Zeon' THEN 0.30
    WHEN 'Vertimec 1.8 EC' THEN 0.50
    WHEN 'Confidor 350 SC' THEN 0.30
    WHEN 'Decis 25 EC' THEN 0.30
    WHEN 'Fastac 100 EC' THEN 0.25
    WHEN 'Tracer 480 SC' THEN 0.20
    WHEN 'Mospilan 20 SP' THEN 0.25
    WHEN 'Talstar' THEN 0.30
    WHEN 'Roundup 747 SG' THEN 1.50
    WHEN 'Faena' THEN 2.00
    WHEN 'Gramoxone' THEN 2.00
    WHEN 'Atrazina 90 WG' THEN 1.50
    WHEN 'Diuron 80 WP' THEN 2.00
    WHEN 'Break-Thru S240' THEN 0.10
    WHEN 'Inex-A' THEN 0.15
    WHEN 'Agrex Oil' THEN 1.00
    WHEN 'Agral 90' THEN 0.15
    WHEN 'Trampa McPhail' THEN 4.00
    WHEN 'Trampa Jackson' THEN 4.00
    WHEN 'Atrayente Proteína Hidrolizada' THEN 1.00
    WHEN 'Torula Yeast' THEN 6.00
    WHEN 'Cebo proteico Success' THEN 1.50
    ELSE dosis_recomendada
END,
unidad_dosis = CASE nombre
    WHEN 'Dithane M-45' THEN 'kg'
    WHEN 'Kocide 2000' THEN 'kg'
    WHEN 'Cuprofix Ultra' THEN 'kg'
    WHEN 'Ridomil Gold MZ' THEN 'kg'
    WHEN 'Aliette 80 WG' THEN 'kg'
    WHEN 'Actara 25 WG' THEN 'kg'
    WHEN 'Mospilan 20 SP' THEN 'kg'
    WHEN 'Roundup 747 SG' THEN 'kg'
    WHEN 'Atrazina 90 WG' THEN 'kg'
    WHEN 'Diuron 80 WP' THEN 'kg'
    WHEN 'Trampa McPhail' THEN 'unidades'
    WHEN 'Trampa Jackson' THEN 'unidades'
    WHEN 'Torula Yeast' THEN 'unidades'
    ELSE 'litros'
END,
unidad_aplicacion = CASE nombre
    WHEN 'Trampa McPhail' THEN 'ha'
    WHEN 'Trampa Jackson' THEN 'ha'
    WHEN 'Torula Yeast' THEN 'ha'
    ELSE 'ha'
END,
intervalo_seguridad = CASE
    WHEN tipo_producto IN ('Fungicidas', 'Insecticidas', 'Herbicidas') THEN 14
    ELSE NULL
END,
periodo_reingreso = CASE
    WHEN tipo_producto IN ('Fungicidas', 'Insecticidas', 'Herbicidas') THEN 24
    ELSE NULL
END
WHERE uso_fitosanitario = 1
  AND tipo_producto IN ('Fungicidas', 'Insecticidas', 'Herbicidas', 'Coadyuvantes', 'Trampas');
