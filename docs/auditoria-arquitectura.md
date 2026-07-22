# Auditoría de arquitectura de SembriExport

Fecha: 2026-07-21  
Alcance: revisión estática del repositorio antes de iniciar la migración modular.

> Este documento conserva la fotografía inicial de la deuda técnica. La
> implementación actual ya trasladó las vistas a `app/Modules`, las utilidades
> compartidas a `app/Shared` y eliminó la conexión y autenticación procedurales
> descritas a continuación. Consulte `docs/cierre-refactorizacion.md` para el
> estado posterior a la migración.

## Estado actual comprobado

SembriExport es una aplicación PHP procedural ejecutada directamente por Apache. La mayoría de páginas raíz actúan a la vez como punto de entrada, controlador, servicio, repositorio y vista. `conexion.php` inicia la sesión, carga helpers y crea la conexión global `$conn`.

### Dependencias

- PHP 8.2 y MySQLi.
- Composer con `vlucas/phpdotenv` 5.6.3 instalado.
- Bootstrap, Chart.js y Font Awesome almacenados en `assets/vendor`.
- JavaScript y CSS propios en `js/` y `css/`.
- ADA usa cURL para llamar a Gemini desde PHP.
- No existía autoload PSR-4 ni infraestructura de pruebas.

### Puntos de entrada y composición

- Público: `index.html`, `about.html`, `products.html`, `login.html` y `password.html`.
- Autenticación: `login.php`, `logout.php` y `password_reset.php`.
- Paneles: `admin.php`, `agricultor.php` y `bodeguero.php`.
- Acciones y AJAX: archivos `admin_*.php`, `*_detalle.php`, `guardar_lote.php`, `procesar_solicitud.php`, `calcular_insumos.php`, `cosecha_acciones.php` y `asistente/asistente_virtual.php`.
- `admin.php` solicita fragmentos y acciones mediante `fetch()` a los archivos `admin_*.php`.
- `agricultor.php` carga `farmer_actions.php` antes de consultar y renderizar los datos del panel.

### Módulos funcionales encontrados

| Módulo | Archivos principales actuales | Dependencias relevantes |
|---|---|---|
| Auth | `login.php`, `logout.php`, `password_reset.php`, `includes/auth.php` | `usuarios`, sesión PHP |
| Cultivos | `admin_cultivos.php`, `cultivo_detalle.php`, `agricultor.php`, `farmer_actions.php` | `usuarios`, `lotes` |
| Lotes | `guardar_lote.php`, `lote_detalle.php`, `lote_historial.php` | `cultivos`, `plagas`, producción |
| Plagas | secciones de `admin_cultivos.php` y panel agricultor | `lotes` |
| Producción | `cosecha_acciones.php`, panel agricultor, reportes | `lotes`, `productos_finales` |
| Inventario/Insumos | `bodeguero.php`, `calcular_insumos.php` | insumos, movimientos, solicitudes |
| Proveedores/Pedidos | `admin_pedidos_proveedores.php` | proveedores, pedidos, facturas |
| Facturas | `admin_facturas.php`, `bodeguero_facturas.php`, detalles | pedidos, inventario, movimientos |
| Reportes | `admin_reportes.php`, impresiones | varios módulos operativos |
| ADA | `asistente/asistente_virtual.php`, `permisos.php`, frontend del chat | sesión, rol, múltiples tablas, Gemini |

### Flujo de autenticación

1. `login.php` busca el correo con consulta preparada.
2. Comprueba `password_verify()`; temporalmente también acepta una contraseña histórica en texto plano y la convierte a hash.
3. `login_user()` regenera el identificador de sesión y guarda `id_usuario`, `nombre`, `email` y `rol`.
4. Cada página llama de forma repetida a `require_auth('Rol')`.
5. `logout.php` vacía la sesión y elimina la cookie.

Los roles comprobados en el esquema son `Administrador`, `Agricultor` y `Bodeguero`; no se renombraron.

### SQL y acoplamiento

Se encontraron consultas SQL en más de veinte archivos PHP. Los puntos de mayor concentración son ADA, pedidos/proveedores, `admin.php`, facturas, cultivos y el panel agricultor. Aunque existen helpers de consultas preparadas, las consultas siguen mezcladas con validación, redirecciones y HTML. Las operaciones de facturación, cosecha y solicitudes ya usan algunas transacciones, pero no están centralizadas en servicios.

Los archivos heredados con sentencias SQL detectados son: `admin_cultivos.php`,
`admin_facturas.php`, `admin_movimientos.php`, `admin_pedidos_proveedores.php`,
`admin_reportes.php`, `admin_solicitudes.php`, `admin_usuarios.php`, `admin.php`,
`agricultor.php`, `asistente/asistente_virtual.php`, `bodeguero_facturas.php`,
`bodeguero.php`, `calcular_insumos.php`, `cultivo_detalle.php`,
`factura_detalle.php`, `guardar_lote.php`, `historial_solicitudes.php`,
`imprimir_productos.php`, `imprimir_solicitudes.php`,
`includes/farmer_actions.php`, `includes/farmer_dashboard_data.php`,
`includes/farmer_helpers.php`, `login.php`, `lote_detalle.php`,
`lote_historial.php`, `password_reset.php` y `procesar_solicitud.php`.
`app/Modules/Cultivos/Repositories/CultivoRepository.php` es el único archivo
SQL del módulo piloto nuevo, conforme a la separación por capas.

### Duplicación observada

- Inicialización de sesión, validación de rol, mensajes flash y redirecciones en páginas independientes.
- Validación manual de fechas, identificadores y cantidades en múltiples acciones.
- Construcción repetida de respuestas JSON para AJAX.
- Cabeceras, navegación y componentes visuales repetidos entre paneles.
- Consultas similares para cultivos, lotes, usuarios e inventario en paneles y endpoints de detalle.

## Riesgos priorizados

| Prioridad | Riesgo comprobado | Tratamiento propuesto |
|---|---|---|
| Alta | No había protección CSRF en formularios ni acciones AJAX | Token central y migración formulario por formulario |
| Alta | Autorización basada en comparaciones de rol repetidas | Matriz central de permisos y middleware |
| Alta | Respaldo de demostración contiene contraseñas iniciales en texto plano | Mantener solo para desarrollo, exigir cambio y migrar todos los registros a `password_hash()` |
| Alta | ADA concentra acceso amplio a datos | Repositorio limitado, filtro de permisos y contexto acotado en su futura migración |
| Alta | Posible IDOR en endpoints que reciben identificadores | Consultas con propietario/alcance en repositorios y comprobación de permisos |
| Media | Errores técnicos se registran de forma dispersa y algunos endpoints construyen su propia salida | Manejador global con logs fuera del área pública |
| Media | Archivos internos son accesibles bajo el document root de XAMPP | Front controller y reglas de denegación en nuevas carpetas internas |
| Media | Validaciones de servidor son inconsistentes | `Validator` central y validaciones de dominio en servicios |
| Media | Dependencia de conexión global `$conn` | Inyección de `Database`/repositorios por constructor |
| Baja | CSS y JavaScript contienen lógica y selectores heredados | Consolidar únicamente al migrar cada módulo para no alterar el diseño |

No se detectó un flujo de subida de archivos en el alcance revisado. No se observó ejecución de SQL generado por Gemini; ADA usa consultas definidas por el backend, pero su acceso y volumen aún deben reducirse.

## Dependencias entre módulos

`Usuarios/Auth` es transversal. `Cultivos` depende de usuarios; `Lotes` depende de cultivos; `Plagas` y `Producción` dependen de lotes. `Solicitudes` enlaza agricultor, lotes e insumos. `Pedidos`, `Proveedores`, `Facturas` e `Inventario` forman un flujo transaccional y deben migrarse en conjunto coordinado. `Reportes` y ADA son consumidores de casi todos los módulos y deben migrarse después de estabilizar sus repositorios.

## Plan de migración progresiva

1. Base: Composer PSR-4, configuración, `Database`, sesión, auth, permisos, CSRF, validación, excepciones, router y `public/index.php`.
2. Piloto Cultivos: entidad/DTO, repositorio MySQLi, servicio, controlador, vistas y rutas, incluyendo control de propiedad.
3. Autenticación: repositorio de usuarios, servicio de login/restablecimiento, CSRF y mensajes no enumerables.
4. Lotes, Plagas y Producción, conservando las reglas y transacciones actuales.
5. Inventario, Solicitudes, Proveedores, Pedidos y Facturas, con pruebas de transacciones e idempotencia.
6. Reportes, centralizando consultas de lectura y exportaciones en `storage/exports`.
7. ADA, con repositorio de solo lectura, filtro de permisos, límites de contexto y cliente Gemini aislado.
8. Retirar archivos antiguos solo después de buscar referencias, validar rutas y dejar redirecciones compatibles.

## Primera etapa aplicada

Se creó la base modular y el módulo piloto Cultivos. Los archivos antiguos siguen disponibles. El formulario de cultivos del agricultor apunta al nuevo front controller y conserva su retorno al panel; el antiguo bloque de acción quedó como puente compatible mediante el mismo servicio, sin SQL propio para esa operación. La consulta por propietario evita IDOR y la eliminación administrativa nueva exige permiso, CSRF y ausencia de lotes asociados.

Las carpetas internas de la nueva arquitectura tienen reglas de denegación para Apache. Esto reduce la exposición mientras las páginas heredadas de la raíz permanecen accesibles durante la migración progresiva.

Los riesgos aún pendientes se resolverán por módulo; esta primera etapa no simula que todos los formularios heredados ya tengan CSRF o que todo el SQL haya sido migrado.

## Etapas posteriores aplicadas

### Autenticación

Login y recuperación se migraron a `Modules/Auth` con controlador, servicios,
repositorio y modelo de usuario. Las respuestas de credenciales y recuperación
ya no permiten enumerar correos, los formularios nuevos usan CSRF y se conserva
la conversión compatible de contraseñas históricas a `password_hash()` tras una
autenticación correcta. `login.php` y `password_reset.php` quedaron como
puentes al Front Controller.

### Lotes

El registro, listado y consulta de lotes se migraron a `Modules/Lotes`. La capa
de servicio comprueba que el cultivo pertenezca al agricultor, valida área,
etapas y rangos de fechas; las consultas quedaron en el repositorio. El
formulario del panel Agricultor utiliza la ruta nueva con CSRF. El historial de
solicitudes permanece heredado hasta migrar Solicitudes; Plagas y finalización
de cosecha ya fueron trasladadas en las etapas siguientes.

### Plagas y Producción

Plagas se migró con repositorio preparado, validación y comprobación de
propiedad del lote. `guardar_lote.php`, aunque no tenía referencias internas
activas, se conservó como puente JSON y ahora coordina Lotes y Plagas mediante
los servicios nuevos, una transacción y CSRF.

Producción se migró con una transacción de servicio que bloquea el lote,
comprueba el estado `en_cosecha`, registra `productos_finales` y actualiza el
lote a `finalizado`. Esto corrige el formulario que apuntaba al archivo
inexistente `cosecha_acciones.php` y cuyos nombres de campos no coincidían con
el bloque procedural anterior. La clasificación por calidad se guarda en
observaciones para mantener el esquema actual sin inventar columnas.

### Inventario y Solicitudes — entrega de bodega

Se migró la operación crítica de entrega: bloqueo de solicitud e insumo,
comprobación de stock, descuento, transición a `Entregado` y movimiento de
salida en una transacción. `procesar_solicitud.php` quedó como puente y el
formulario heredado de Bodega apunta a la ruta protegida con CSRF. La creación
manual agrupada valida lote, superficie e insumos y se ejecuta en una
transacción. La revisión AJAX del Administrador delega al mismo controlador y
envía CSRF. La solicitud automática queda pendiente junto con la migración de
la calculadora de Insumos.

### Inventario e Insumos

Se añadió catálogo y ajuste de stock modular para Bodega. Altas y ajustes se
registran transaccionalmente en `movimientos_inventario`; no se inventaron las
fechas presentes en un formulario antiguo porque `insumos_agricolas` no tiene
esas columnas. La calculadora pasó a un servicio puro y un endpoint autenticado
con control de propiedad del lote. El antiguo bloque de solicitud automática no
tiene un formulario activo localizado y se conserva hasta la limpieza final de
referencias.

### Proveedores y Pedidos

Las seis operaciones AJAX de `admin_pedidos_proveedores.php` se trasladaron a
controladores, servicios y repositorios modulares. Las rutas nuevas están
protegidas por autenticación, permisos y CSRF, y todas las consultas con datos
externos son preparadas. Crear un producto desde un pedido ahora agrupa ambas
altas en una transacción. La vista visual existente se conserva mediante un
puente temporal; el bloque procedural anterior queda inaccesible y podrá
retirarse en la etapa final de limpieza de archivos heredados.

### Facturas

Se migró la recepción de compras a una transacción de servicio que bloquea el
pedido y el insumo, valida duplicados, inserta cabecera y detalle, actualiza el
stock, registra movimientos en `movimientos_inventario` y
`movimientos_insumos`, y finalmente cambia el pedido a `Recibido`. La revisión
administrativa y la consulta de detalle también usan el repositorio modular.
Los tres archivos PHP originales se conservan como puentes para no romper las
rutas ni la carga AJAX existente. Las instalaciones antiguas deben importar
`actualizar_flujo_pedidos_facturas.sql` desde phpMyAdmin.

### Reportes

Los indicadores administrativos y los reportes imprimibles de solicitudes y
productos en factura se trasladaron a `Modules/Reportes`. Las consultas de
totales, agrupaciones, actividad y listados quedaron centralizadas en un
repositorio de solo lectura. Los archivos raíz conservan sus URL como puentes,
pero ya no ejecutan las consultas cuando son atendidos por la arquitectura
modular. Los reportes de Bodega usan un permiso explícito independiente del
reporte administrativo.

### ADA

ADA se migró a `Modules/Asistente` con cliente Gemini, constructor de contexto,
filtro de permisos, repositorio de solo lectura y controlador JSON separados.
La API exige sesión, permiso y CSRF. Las consultas son estáticas, se limitan a
20 filas por tema y el contexto completo se corta a 12 000 caracteres. Para el
Agricultor, Cultivos, Lotes, Solicitudes, Producción y Plagas incluyen siempre
el identificador del usuario autenticado. Antes de enviar contexto se eliminan
campos cuyos nombres indiquen contraseñas, tokens, secretos o claves. La clave
Gemini se transmite en una cabecera backend y los archivos de configuración
heredados quedaron denegados por Apache.
