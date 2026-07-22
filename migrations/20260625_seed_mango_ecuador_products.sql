INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Amistar 250 SC', 'Fungicidas', 'Fungicidas', 'Azoxistrobin', '250 SC', 'Fungicida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Amistar 250 SC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Score 250 EC', 'Fungicidas', 'Fungicidas', 'Difenoconazol', '250 EC', 'Fungicida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Score 250 EC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Folicur 250 EW', 'Fungicidas', 'Fungicidas', 'Tebuconazol', '250 EW', 'Fungicida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Folicur 250 EW'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Tilt 250 EC', 'Fungicidas', 'Fungicidas', 'Propiconazol', '250 EC', 'Fungicida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Tilt 250 EC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Dithane M-45', 'Fungicidas', 'Fungicidas', 'Mancozeb', 'M-45', 'Fungicida usado en manejo fitosanitario de mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Dithane M-45'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Kocide 2000', 'Fungicidas', 'Fungicidas', 'Hidróxido de cobre', '2000', 'Fungicida cúprico usado en mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Kocide 2000'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Cuprofix Ultra', 'Fungicidas', 'Fungicidas', 'Oxicloruro de cobre', 'Ultra', 'Fungicida cúprico usado en mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Cuprofix Ultra'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Ridomil Gold MZ', 'Fungicidas', 'Fungicidas', 'Metalaxil + Mancozeb', 'Gold MZ', 'Fungicida sistémico y de contacto usado en mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Ridomil Gold MZ'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Aliette 80 WG', 'Fungicidas', 'Fungicidas', 'Fosetil-Al', '80 WG', 'Fungicida usado en manejo fitosanitario de mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Aliette 80 WG'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Bravo 720 SC', 'Fungicidas', 'Fungicidas', 'Clorotalonil', '720 SC', 'Fungicida de contacto usado en mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Bravo 720 SC'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Actara 25 WG', 'Insecticidas', 'Insecticidas', 'Thiamethoxam', '25 WG', 'Insecticida usado en manejo fitosanitario de mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Actara 25 WG'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Karate Zeon', 'Insecticidas', 'Insecticidas', 'Lambda-cialotrina', 'Zeon', 'Insecticida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Karate Zeon'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Vertimec 1.8 EC', 'Insecticidas', 'Insecticidas', 'Abamectina', '1.8 EC', 'Insecticida-acaricida usado en mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Vertimec 1.8 EC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Confidor 350 SC', 'Insecticidas', 'Insecticidas', 'Imidacloprid', '350 SC', 'Insecticida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Confidor 350 SC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Decis 25 EC', 'Insecticidas', 'Insecticidas', 'Deltametrina', '25 EC', 'Insecticida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Decis 25 EC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Fastac 100 EC', 'Insecticidas', 'Insecticidas', 'Alfa-cipermetrina', '100 EC', 'Insecticida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Fastac 100 EC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Tracer 480 SC', 'Insecticidas', 'Insecticidas', 'Spinosad', '480 SC', 'Insecticida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Tracer 480 SC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Mospilan 20 SP', 'Insecticidas', 'Insecticidas', 'Acetamiprid', '20 SP', 'Insecticida usado en manejo fitosanitario de mango.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Mospilan 20 SP'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Talstar', 'Insecticidas', 'Insecticidas', 'Bifentrina', NULL, 'Insecticida usado en manejo fitosanitario de mango.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Talstar'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Roundup 747 SG', 'Herbicidas', 'Herbicidas', 'Glifosato', '747 SG', 'Herbicida para manejo de malezas.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Roundup 747 SG'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Faena', 'Herbicidas', 'Herbicidas', 'Glifosato', NULL, 'Herbicida para manejo de malezas.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Faena'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Gramoxone', 'Herbicidas', 'Herbicidas', 'Paraquat', NULL, 'Herbicida para manejo de malezas.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Gramoxone'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Atrazina 90 WG', 'Herbicidas', 'Herbicidas', 'Atrazina', '90 WG', 'Herbicida para manejo de malezas.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Atrazina 90 WG'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Diuron 80 WP', 'Herbicidas', 'Herbicidas', 'Diuron', '80 WP', 'Herbicida para manejo de malezas.', 'kg', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Diuron 80 WP'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Urea 46%', 'Fertilizantes', 'Fertilizantes', 'Nitrógeno ureico', '46%', 'Fertilizante granulado para nutrición del mango.', 'kg', 0.00, 50.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Urea 46%'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'NPK 15-15-15', 'Fertilizantes', 'Fertilizantes', 'Nitrógeno + Fósforo + Potasio', '15-15-15', 'Fertilizante compuesto para nutrición del mango.', 'kg', 0.00, 50.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('NPK 15-15-15'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'MAP 12-61-0', 'Fertilizantes', 'Fertilizantes', 'Fosfato monoamónico', '12-61-0', 'Fertilizante fosfatado para nutrición del mango.', 'kg', 0.00, 25.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('MAP 12-61-0'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'DAP 18-46-0', 'Fertilizantes', 'Fertilizantes', 'Fosfato diamónico', '18-46-0', 'Fertilizante fosfatado para nutrición del mango.', 'kg', 0.00, 25.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('DAP 18-46-0'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Sulfato de Potasio', 'Fertilizantes', 'Fertilizantes', 'Sulfato de potasio', NULL, 'Fertilizante potásico para nutrición del mango.', 'kg', 0.00, 25.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Sulfato de Potasio'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Cloruro de Potasio', 'Fertilizantes', 'Fertilizantes', 'Cloruro de potasio', NULL, 'Fertilizante potásico para nutrición del mango.', 'kg', 0.00, 25.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Cloruro de Potasio'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Nitrato de Calcio', 'Fertilizantes', 'Fertilizantes', 'Nitrato de calcio', NULL, 'Fertilizante cálcico para nutrición del mango.', 'kg', 0.00, 25.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Nitrato de Calcio'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Sulfato de Magnesio', 'Fertilizantes', 'Fertilizantes', 'Sulfato de magnesio', NULL, 'Fertilizante magnésico para nutrición del mango.', 'kg', 0.00, 25.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Sulfato de Magnesio'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Bayfolan Forte', 'Fertilizantes foliares', 'Fertilizantes foliares', NULL, NULL, 'Fertilizante foliar para nutrición complementaria.', 'litros', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Bayfolan Forte'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Basfoliar Zn', 'Fertilizantes foliares', 'Fertilizantes foliares', 'Zinc', NULL, 'Fertilizante foliar con zinc.', 'litros', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Basfoliar Zn'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Basfoliar Kelp SL', 'Fertilizantes foliares', 'Fertilizantes foliares', 'Extracto de algas', 'SL', 'Fertilizante foliar con extracto de algas.', 'litros', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Basfoliar Kelp SL'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'NutriLeaf', 'Fertilizantes foliares', 'Fertilizantes foliares', NULL, NULL, 'Fertilizante foliar para nutrición complementaria.', 'kg', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('NutriLeaf'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Borocal', 'Fertilizantes foliares', 'Fertilizantes foliares', 'Boro + Calcio', NULL, 'Fertilizante foliar con boro y calcio.', 'litros', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Borocal'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Break-Thru S240', 'Coadyuvantes', 'Coadyuvantes', 'Organosiliconado', 'S240', 'Coadyuvante para aplicaciones agrícolas.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Break-Thru S240'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Inex-A', 'Coadyuvantes', 'Coadyuvantes', NULL, NULL, 'Coadyuvante para aplicaciones agrícolas.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Inex-A'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Agrex Oil', 'Coadyuvantes', 'Coadyuvantes', 'Aceite agrícola', NULL, 'Coadyuvante oleoso para aplicaciones agrícolas.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Agrex Oil'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Agral 90', 'Coadyuvantes', 'Coadyuvantes', NULL, '90', 'Coadyuvante para aplicaciones agrícolas.', 'litros', 0.00, 1.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Agral 90'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Trampa McPhail', 'Trampas', 'Trampas', NULL, NULL, 'Trampa para monitoreo de mosca de la fruta.', 'unidades', 0.00, 5.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Trampa McPhail'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Trampa Jackson', 'Trampas', 'Trampas', NULL, NULL, 'Trampa para monitoreo fitosanitario.', 'unidades', 0.00, 5.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Trampa Jackson'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Atrayente Proteína Hidrolizada', 'Trampas', 'Trampas', 'Proteína hidrolizada', NULL, 'Atrayente para monitoreo y control de mosca de la fruta.', 'litros', 0.00, 2.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Atrayente Proteína Hidrolizada'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Torula Yeast', 'Trampas', 'Trampas', 'Levadura torula', NULL, 'Atrayente para trampas de monitoreo.', 'unidades', 0.00, 10.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Torula Yeast'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Cebo proteico Success', 'Trampas', 'Trampas', 'Cebo proteico', NULL, 'Cebo proteico para manejo fitosanitario.', 'litros', 0.00, 2.00, 1, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Cebo proteico Success'));

INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Mochila Jacto 20 L', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, '20 L', 'Equipo de aplicación agrícola.', 'unidades', 0.00, 1.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Mochila Jacto 20 L'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Bomba de espalda Matabi', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Equipo de aplicación agrícola.', 'unidades', 0.00, 1.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Bomba de espalda Matabi'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Tijera de poda Bahco', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Herramienta para poda de mango.', 'unidades', 0.00, 2.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Tijera de poda Bahco'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Serrucho Bahco', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Herramienta para poda de mango.', 'unidades', 0.00, 2.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Serrucho Bahco'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Guantes de nitrilo', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Equipo de protección personal.', 'pares', 0.00, 10.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Guantes de nitrilo'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Botas de PVC', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Equipo de protección personal.', 'pares', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Botas de PVC'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Mascarilla con filtro', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Equipo de protección personal para aplicaciones.', 'unidades', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Mascarilla con filtro'));
INSERT INTO insumos_agricolas (id_usuario, nombre, tipo, tipo_producto, ingrediente_activo, presentacion, descripcion, unidad_medida, cantidad, stock_minimo, uso_fitosanitario, observaciones)
SELECT '1', 'Gafas de seguridad', 'Equipos/Herramientas', 'Equipos/Herramientas', NULL, NULL, 'Equipo de protección personal para aplicaciones.', 'unidades', 0.00, 5.00, 0, 'Producto inicial para mango Tommy Atkins en Ecuador'
WHERE NOT EXISTS (SELECT 1 FROM insumos_agricolas WHERE LOWER(nombre) = LOWER('Gafas de seguridad'));
