UPDATE insumos_agricolas
SET cantidad = CASE nombre
    WHEN 'Amistar 250 SC' THEN 15.00
    WHEN 'Score 250 EC' THEN 12.00
    WHEN 'Folicur 250 EW' THEN 10.00
    WHEN 'Tilt 250 EC' THEN 10.00
    WHEN 'Dithane M-45' THEN 25.00
    WHEN 'Kocide 2000' THEN 20.00
    WHEN 'Cuprofix Ultra' THEN 20.00
    WHEN 'Ridomil Gold MZ' THEN 15.00
    WHEN 'Aliette 80 WG' THEN 15.00
    WHEN 'Bravo 720 SC' THEN 18.00
    WHEN 'Actara 25 WG' THEN 8.00
    WHEN 'Karate Zeon' THEN 10.00
    WHEN 'Vertimec 1.8 EC' THEN 8.00
    WHEN 'Confidor 350 SC' THEN 10.00
    WHEN 'Decis 25 EC' THEN 10.00
    WHEN 'Fastac 100 EC' THEN 10.00
    WHEN 'Tracer 480 SC' THEN 8.00
    WHEN 'Mospilan 20 SP' THEN 8.00
    WHEN 'Talstar' THEN 10.00
    WHEN 'Roundup 747 SG' THEN 30.00
    WHEN 'Faena' THEN 25.00
    WHEN 'Gramoxone' THEN 20.00
    WHEN 'Atrazina 90 WG' THEN 25.00
    WHEN 'Diuron 80 WP' THEN 25.00
    WHEN 'Urea 46%' THEN 500.00
    WHEN 'NPK 15-15-15' THEN 500.00
    WHEN 'MAP 12-61-0' THEN 250.00
    WHEN 'DAP 18-46-0' THEN 250.00
    WHEN 'Sulfato de Potasio' THEN 250.00
    WHEN 'Cloruro de Potasio' THEN 250.00
    WHEN 'Nitrato de Calcio' THEN 200.00
    WHEN 'Sulfato de Magnesio' THEN 200.00
    WHEN 'Bayfolan Forte' THEN 30.00
    WHEN 'Basfoliar Zn' THEN 25.00
    WHEN 'Basfoliar Kelp SL' THEN 25.00
    WHEN 'NutriLeaf' THEN 30.00
    WHEN 'Borocal' THEN 25.00
    WHEN 'Break-Thru S240' THEN 12.00
    WHEN 'Inex-A' THEN 12.00
    WHEN 'Agrex Oil' THEN 12.00
    WHEN 'Agral 90' THEN 12.00
    WHEN 'Trampa McPhail' THEN 30.00
    WHEN 'Trampa Jackson' THEN 30.00
    WHEN 'Atrayente Proteína Hidrolizada' THEN 20.00
    WHEN 'Torula Yeast' THEN 50.00
    WHEN 'Cebo proteico Success' THEN 20.00
    WHEN 'Mochila Jacto 20 L' THEN 5.00
    WHEN 'Bomba de espalda Matabi' THEN 4.00
    WHEN 'Tijera de poda Bahco' THEN 10.00
    WHEN 'Serrucho Bahco' THEN 8.00
    WHEN 'Guantes de nitrilo' THEN 50.00
    WHEN 'Botas de PVC' THEN 15.00
    WHEN 'Mascarilla con filtro' THEN 20.00
    WHEN 'Gafas de seguridad' THEN 20.00
    ELSE cantidad
END
WHERE observaciones = 'Producto inicial para mango Tommy Atkins en Ecuador'
  AND cantidad = 0.00;
