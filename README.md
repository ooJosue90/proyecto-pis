# SembriExport

SembriExport es una aplicación web para administrar la operación agrícola: usuarios, cultivos, lotes, control fitosanitario, producción, solicitudes de insumos, inventario, proveedores, pedidos, facturas, movimientos y reportes.

El proyecto usa una arquitectura modular en PHP, rutas limpias y un único Front Controller. Incluye además a ADA, un asistente opcional conectado con Google Gemini que responde según el rol y los permisos del usuario.

## Estado actual

- La aplicación privada ya no depende de archivos PHP en la raíz.
- Las peticiones pasan por `public/index.php` y las rutas declaradas en `routes/`.
- Los enlaces y formularios utilizan URLs limpias como `/login`, `/dashboard/admin` y `/plagas`.
- La interfaz comparte el mismo sistema visual, sidebar, navbar, formularios, tablas, selects y modales.
- Existen dashboards diferenciados para Administrador, Agricultor y Bodeguero.
- Las operaciones sensibles incluyen autenticación, permisos por rol y protección CSRF.
- La suite automatizada utiliza PHPUnit 10.

## Módulos principales

| Módulo | Funciones principales |
|---|---|
| Autenticación | Inicio de sesión, cierre de sesión y solicitud de recuperación de contraseña. |
| Usuarios | Alta, edición y eliminación protegida de cuentas y roles. |
| Cultivos y lotes | Registro, consulta, etapas productivas e historial. |
| Control fitosanitario | Registro de plagas o afectaciones asociadas a lotes del agricultor. |
| Producción | Cierre de cosechas y registro de productos finales. |
| Solicitudes | Solicitud, aprobación, rechazo, cancelación y entrega de insumos. |
| Inventario | Catálogo, existencias, ajustes y movimientos. |
| Proveedores y pedidos | Gestión de proveedores y flujo de abastecimiento. |
| Facturas | Recepción, detalle y revisión de facturas de compra. |
| Reportes | Resúmenes administrativos y operativos. |
| ADA | Consultas asistidas por Gemini con contexto filtrado por permisos. |

## Tecnologías

- PHP `8.2` o superior dentro de la rama 8.x.
- Apache con `mod_rewrite` y soporte para `.htaccess`.
- MySQL o MariaDB.
- Composer 2.
- HTML, CSS y JavaScript.
- Bootstrap 5.2.3.
- Font Awesome 7.0.1.
- Chart.js 4.4.7.
- PHPUnit 10.5 para pruebas.
- Node.js y pnpm únicamente para tareas opcionales de mantenimiento de recursos.

## Arquitectura

El flujo principal es:

```text
Apache + .htaccess
        ↓
public/index.php
        ↓
Router → Middleware → Controller → Service → Repository → MySQL
                              └────→ View o respuesta JSON
```

- `Controller`: recibe la petición y construye la respuesta.
- `Service`: contiene validaciones y reglas de negocio.
- `Repository`: concentra las consultas SQL preparadas.
- `Middleware`: comprueba sesión y permisos.
- `View`: recibe datos preparados y escapa la salida.
- `Database::transaction()`: protege operaciones que modifican varias tablas.

## Estructura del repositorio

```text
app/
├── Core/                       Router, Request, Response, sesión, CSRF y base de datos
├── Modules/                    Módulos de negocio
│   ├── Auth/
│   ├── Cultivos/
│   ├── Dashboard/
│   ├── Facturas/
│   ├── Insumos/
│   ├── Inventario/
│   ├── Lotes/
│   ├── Movimientos/
│   ├── Pedidos/
│   ├── Plagas/
│   ├── Produccion/
│   ├── Proveedores/
│   ├── Reportes/
│   ├── Solicitudes/
│   └── Usuarios/
└── Shared/                     Helpers, contratos, excepciones y layout

assets/vendor/                  Dependencias web listas para servir
config/                         Aplicación, base de datos, Gemini y permisos
css/                            Estilos compartidos
docs/                           Auditoría y cierre de la refactorización
js/                             Comportamiento de interfaz
migrations/                     Migraciones SQL versionadas
public/index.php                Front Controller único
routes/web.php                  Rutas HTML y acciones de formularios
routes/api.php                  Endpoints JSON
storage/logs/                   Errores de ejecución
storage/sessions/               Sesiones PHP
storage/exports/                Archivos generados
tests/                          Pruebas automatizadas
.htaccess                      Reescritura y protección de archivos sensibles
```

Los archivos públicos `index.html`, `about.html` y `products.html` corresponden al sitio informativo. La aplicación autenticada comienza en `/login`.

## Instalación completa en otro PC

Las instrucciones siguientes están pensadas para Windows con XAMPP. Se recomienda instalar el proyecto en una carpeta sin espacios, por ejemplo `proyecto-pis`.

### 1. Preparar lo que debe trasladarse

Antes de cambiar de equipo se necesitan dos elementos:

1. El repositorio de GitHub con el código actualizado.
2. Una exportación SQL reciente de la base `mangos_pr`.

La base de datos, `.env`, `vendor/`, `node_modules/`, sesiones, logs y respaldos están excluidos de Git por seguridad o porque se regeneran. Por eso, clonar el repositorio no recupera los datos de MySQL.

#### Exportar la base desde el equipo anterior

Desde phpMyAdmin:

1. Abra `http://localhost/phpmyadmin`.
2. Seleccione `mangos_pr`.
3. Pulse **Exportar**.
4. Use el método rápido y formato SQL.
5. Guarde el archivo fuera del repositorio.

También puede hacerlo desde CMD:

```bat
C:\xampp\mysql\bin\mysqldump.exe -u root -p --routines --triggers mangos_pr > C:\respaldos\mangos_pr.sql
```

No publique ese respaldo si contiene información real de usuarios, proveedores o producción.

### 2. Instalar programas en el equipo nuevo

Instale:

1. [Git](https://git-scm.com/download/win).
2. [XAMPP](https://www.apachefriends.org/) con PHP 8.2 o superior.
3. [Composer](https://getcomposer.org/download/).
4. Opcionalmente [Node.js](https://nodejs.org/) y pnpm si modificará recursos del frontend.

Compruebe las herramientas desde PowerShell:

```powershell
git --version
php -v
composer --version
```

Si `php` no está agregado al `PATH`, compruebe directamente la versión de XAMPP:

```powershell
C:\xampp\php\php.exe -v
```

PHP debe tener habilitadas al menos estas extensiones:

- `mysqli`
- `mbstring`
- `curl` para ADA
- `openssl`
- `json`

Puede revisarlas con:

```powershell
C:\xampp\php\php.exe -m
```

### 3. Clonar el repositorio dentro de XAMPP

Abra PowerShell:

```powershell
Set-Location C:\xampp\htdocs
git clone https://github.com/ooJosue90/proyecto-pis.git proyecto-pis
Set-Location C:\xampp\htdocs\proyecto-pis
```

Si necesita una rama distinta de la predeterminada:

```powershell
git fetch --all --prune
git switch NOMBRE_DE_LA_RAMA
git pull
```

Evite usar `C:\xampp\htdocs\proyecto pis` si puede conservar el nombre `proyecto-pis`; ambos funcionan, pero una carpeta sin espacios produce URLs más simples.

### 4. Instalar dependencias PHP

Dentro del proyecto ejecute:

```powershell
composer install
composer dump-autoload
```

Esto crea `vendor/` e instala las versiones registradas en `composer.lock`, entre ellas `vlucas/phpdotenv` y PHPUnit.

Para una instalación destinada únicamente a producción:

```powershell
composer install --no-dev --optimize-autoloader
```

No copie `vendor/` desde otro equipo si puede reconstruirlo con Composer.

### 5. Crear el archivo de entorno

Duplique `.env.example`:

```powershell
Copy-Item .env.example .env
```

Configuración típica para XAMPP:

```dotenv
APP_NAME=SembriExport
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=America/Guayaquil

DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=mangos_pr
DB_PORT=3306
DB_CHARSET=utf8mb4

GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
```

| Variable | Uso |
|---|---|
| `APP_NAME` | Nombre de la aplicación. |
| `APP_ENV` | `local` durante desarrollo y `production` al desplegar. |
| `APP_DEBUG` | Muestra información técnica cuando es `true`; debe ser `false` en producción. |
| `APP_TIMEZONE` | Zona horaria usada por PHP. |
| `APP_FOUNDATION_DATE` | Fecha mínima aceptada para registros operativos (`YYYY-MM-DD`). |
| `DB_HOST` | Host de MySQL; normalmente `localhost`. |
| `DB_USER` | Usuario de MySQL. |
| `DB_PASSWORD` | Contraseña del usuario. Puede estar vacía en un XAMPP local. |
| `DB_NAME` | Base de datos de la aplicación. |
| `DB_PORT` | Puerto de MySQL, normalmente `3306`. |
| `DB_CHARSET` | Codificación, recomendada `utf8mb4`. |
| `GEMINI_API_KEY` | Clave privada para ADA. Puede quedar vacía. |
| `GEMINI_MODEL` | Modelo de Gemini usado por ADA. |

Reglas importantes:

- No publique `.env`.
- No copie una clave privada en `.env.example`.
- Reinicie Apache después de cambiar configuración si observa valores antiguos.
- En producción use `APP_ENV=production` y `APP_DEBUG=false`.

### 6. Crear e importar la base de datos

Inicie **Apache** y **MySQL** desde el panel de XAMPP. Después abra:

```text
http://localhost/phpmyadmin
```

Para restaurar el respaldo:

1. Entre en la pestaña **Importar**.
2. Seleccione el archivo `mangos_pr.sql` exportado desde el otro equipo.
3. Pulse **Importar** o **Continuar**.
4. Compruebe que aparezca la base `mangos_pr` y sus tablas.

El respaldo debe incluir como mínimo las tablas de usuarios, proveedores, pedidos, facturas, inventario, cultivos, lotes, plagas, solicitudes, producción y notificaciones.

#### Importación mediante CMD

Si el respaldo contiene `CREATE DATABASE` y `USE mangos_pr`:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -p < C:\respaldos\mangos_pr.sql
```

Si el respaldo solo contiene tablas, cree primero la base en phpMyAdmin con cotejamiento `utf8mb4_unicode_ci` e importe el archivo dentro de ella.

#### Migraciones

Las migraciones versionadas viven en `migrations/` y deben ejecutarse en orden cronológico cuando se instala sobre un respaldo antiguo.

Actualmente están disponibles:

```text
migrations/20260613_harvest_state.sql
migrations/20260724_crop_phase_status_workflow.sql
migrations/20260724_enforce_crop_stage_sequence.sql
migrations/20260724_normalize_crop_stages.sql
```

Estas migraciones agregan el estado operativo y la fecha real de cosecha, persisten el estado de cada fase y aplican la secuencia obligatoria Siembra → Riego → Cosecha. Un respaldo reciente puede incluir ya algunas columnas; revise el SQL antes de repetir una migración.

Haga siempre una copia de seguridad antes de modificar una base que ya contiene datos.

### 7. Comprobar Apache y las rutas limpias

La raíz contiene un `.htaccess` que:

- impide listar directorios;
- bloquea el acceso web a archivos sensibles;
- sirve directamente recursos existentes;
- envía las rutas de aplicación a `public/index.php`.

En XAMPP, `mod_rewrite` debe estar habilitado. Abra `C:\xampp\apache\conf\httpd.conf` y confirme que esta línea no tenga `#` al inicio:

```apache
LoadModule rewrite_module modules/mod_rewrite.so
```

En el bloque correspondiente a `C:/xampp/htdocs`, confirme:

```apache
AllowOverride All
```

Después de cambiar `httpd.conf`, reinicie Apache.

### 8. Preparar carpetas de escritura

La aplicación escribe únicamente datos temporales o generados dentro de `storage/`:

```text
storage/sessions
storage/logs
storage/exports
```

En Windows con XAMPP suelen funcionar sin ajustes adicionales. Si no existen, créelas:

```powershell
New-Item -ItemType Directory -Force storage\sessions, storage\logs, storage\exports
```

No traslade archivos `sess_*` entre equipos. Las sesiones antiguas no son portables ni necesarias.

### 9. Abrir la aplicación

Con la carpeta recomendada:

```text
Sitio público: http://localhost/proyecto-pis/
Inicio de sesión: http://localhost/proyecto-pis/login
Recuperación: http://localhost/proyecto-pis/password/forgot
```

Si la carpeta conserva un espacio:

```text
http://localhost/proyecto%20pis/login
```

No abra directamente `public/index.php` ni cree enlaces a PHP antiguos. Las URLs deben apuntar a las rutas limpias.

Después de iniciar sesión, el sistema redirige según el rol:

```text
Administrador: /dashboard/admin
Agricultor:    /dashboard/agricultor
Bodeguero:     /dashboard/bodega
```

Las cuentas disponibles dependen del respaldo SQL importado. El repositorio no debe documentar contraseñas reales. Solicite una cuenta temporal al responsable de la base y cambie su contraseña.

### 10. Activar ADA de forma opcional

Los demás módulos funcionan con `GEMINI_API_KEY` vacía. Para activar ADA:

1. Obtenga una clave válida de Google Gemini.
2. Guárdela únicamente en `.env`.
3. Configure un modelo permitido para esa clave.
4. Confirme que PHP tenga `curl` y salida HTTPS.

```dotenv
GEMINI_API_KEY=SU_CLAVE_PRIVADA
GEMINI_MODEL=gemini-2.0-flash
```

ADA usa `POST /api/asistente/chat`, requiere sesión, permiso `asistente.usar` y CSRF. El contexto se limita y se filtra por rol; los agricultores solo consultan información asociada a su usuario.

### 11. Verificar la instalación

Ejecute primero las comprobaciones técnicas:

```powershell
composer validate
vendor\bin\phpunit
node scripts\build-css.mjs
```

La última comprobación requiere Node.js, pero la aplicación puede ejecutarse sin instalar paquetes npm porque los recursos necesarios están versionados en `assets/vendor/`.

Lista de comprobación manual:

- [ ] Apache y MySQL están iniciados.
- [ ] `http://localhost/` responde.
- [ ] phpMyAdmin muestra `mangos_pr`.
- [ ] Existe `.env` con las credenciales correctas.
- [ ] Existe `vendor/autoload.php`.
- [ ] `/proyecto-pis/` abre el sitio público.
- [ ] `/proyecto-pis/login` muestra el formulario.
- [ ] El login redirige al dashboard del rol.
- [ ] Se cargan CSS, iconos y JavaScript sin errores 404.
- [ ] El Agricultor puede abrir `/plagas` y registrar una afectación.
- [ ] El Administrador puede gestionar usuarios, solicitudes y proveedores.
- [ ] El Bodeguero puede consultar inventario, pedidos y facturas.
- [ ] ADA responde si se configuró una clave válida.

## Actualización de una instalación existente

Antes de actualizar, exporte la base y conserve una copia de `.env` fuera del repositorio.

```powershell
Set-Location C:\xampp\htdocs\proyecto-pis
git status
git pull
composer install
composer dump-autoload
vendor\bin\phpunit
```

Después:

1. Revise nuevas variables en `.env.example` y añádalas manualmente a `.env`.
2. Revise `migrations/` y aplique únicamente las migraciones pendientes.
3. Reinicie Apache.
4. Recargue el navegador sin caché con `Ctrl + F5`.
5. Compruebe login, dashboards y operaciones principales.

No reemplace `.env` con `.env.example`, porque perdería las credenciales locales.

## Acceso desde otro dispositivo de la red local

Solo el equipo servidor necesita XAMPP, PHP, Composer y MySQL. Los demás equipos necesitan un navegador.

En el servidor ejecute:

```powershell
ipconfig
```

Localice la dirección IPv4, por ejemplo `192.168.1.50`, y abra desde el otro dispositivo:

```text
http://192.168.1.50/proyecto-pis/
```

Si Apache usa el puerto 8080:

```text
http://192.168.1.50:8080/proyecto-pis/
```

Si no responde:

1. Confirme que ambos equipos estén en la misma red.
2. Configure la red de Windows como privada.
3. Permita `httpd.exe` o el puerto de Apache en Firewall de Windows.
4. Use la IP del servidor, nunca `localhost`, desde el dispositivo cliente.
5. No abra el puerto `3306`; MySQL debe permanecer accesible solo para PHP en el servidor.

## Rutas principales

| Método | Ruta | Acceso general |
|---|---|---|
| GET/POST | `/login` | Público |
| POST | `/logout` | Autenticado |
| GET/POST | `/password/forgot` | Público |
| GET | `/dashboard/admin` | Administrador |
| GET | `/dashboard/agricultor` | Agricultor |
| GET | `/dashboard/bodega` | Bodeguero |
| GET/POST | `/cultivos` | Según permiso |
| GET/POST | `/lotes` | Según permiso |
| GET/POST | `/plagas` | Agricultor o permiso asignado |
| GET | `/insumos/calculadora` | Agricultor |
| GET/POST | `/inventario` | Bodeguero |
| GET/POST | `/solicitudes/*` | Según rol y acción |
| GET | `/abastecimiento` | Administrador |
| POST | `/proveedores/*` | Administrador |
| POST | `/pedidos/*` | Administrador |
| GET/POST | `/facturas/*` | Administrador o Bodeguero según acción |
| GET | `/reportes/*` | Según permiso |
| GET/POST | `/usuarios/*` | Administrador |
| POST | `/api/asistente/chat` | Autenticado con permiso |

La lista exacta está en `routes/web.php` y `routes/api.php`.

## Seguridad

- Nunca publique `.env`, claves API, respaldos SQL ni sesiones.
- Use `APP_DEBUG=false` fuera de desarrollo.
- Cambie las credenciales iniciales y utilice contraseñas fuertes.
- En una instalación real, cree un usuario MySQL exclusivo y no utilice `root` sin contraseña.
- Mantenga Apache, PHP, MySQL y Composer actualizados.
- Haga respaldos antes de aplicar migraciones o actualizar código.
- No exponga phpMyAdmin ni MySQL directamente a Internet.
- Para acceso externo use HTTPS y un servidor preparado para producción; XAMPP está orientado al desarrollo local.
- Los formularios privados deben conservar su token `_token` y pasar por las rutas centrales.

## Solución de problemas

### `vendor/autoload.php` no existe

Ejecute:

```powershell
composer install
```

### Composer usa otro PHP

Compruebe:

```powershell
composer diagnose
where.exe php
php --ini
```

Asegúrese de habilitar las extensiones en el `php.ini` que realmente utiliza Composer.

### Error de conexión con MySQL

- Inicie MySQL en XAMPP.
- Revise `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` y `DB_PORT`.
- Compruebe que `mangos_pr` exista.
- Si MySQL usa otro puerto, actualice `DB_PORT`.
- Reinicie Apache después de corregir `.env`.

### Las rutas muestran 404 o `Not Found`

- Confirme que el proyecto esté dentro de `htdocs`.
- Habilite `mod_rewrite`.
- Use `AllowOverride All` para `htdocs`.
- Compruebe que exista `.htaccess` en la raíz.
- Reinicie Apache.
- Use la URL correspondiente al nombre exacto de la carpeta.

### La página abre, pero CSS, iconos o JavaScript devuelven 404

- No entre mediante una ruta física de `public/`.
- Abra el proyecto desde `/proyecto-pis/` o `/proyecto-pis/login`.
- Compruebe que `assets/vendor/`, `css/` y `js/` existan.
- Vacíe la caché con `Ctrl + F5`.

### El inicio de sesión no permanece activo

- Compruebe que `storage/sessions` exista y tenga escritura.
- Borre únicamente las cookies del sitio.
- Reinicie Apache.
- No copie sesiones desde otro equipo.

### Error 500 sin información visible

En producción el detalle se oculta. Revise:

```text
storage/logs/
```

En desarrollo puede usar temporalmente:

```dotenv
APP_ENV=local
APP_DEBUG=true
```

No deje el modo debug activo en producción.

### ADA no responde

- Revise `GEMINI_API_KEY` y `GEMINI_MODEL`.
- Habilite `curl` y `mbstring`.
- Compruebe la conexión HTTPS del servidor.
- Confirme que el usuario tenga permiso `asistente.usar`.
- Revise los logs sin mostrar la clave API.

### Otro dispositivo no puede conectarse

- Use la IPv4 del servidor.
- Permita Apache en redes privadas.
- Confirme que no haya una VPN aislando la red.
- Añada el puerto de Apache a la URL si no usa el 80.

## Desarrollo

### Pruebas

```powershell
vendor\bin\phpunit
```

### Validar Composer

```powershell
composer validate
composer dump-autoload
```

### Dependencias frontend opcionales

Los bundles necesarios para ejecutar el sistema ya están en `assets/vendor/`. Si va a mantener u optimizar recursos:

```powershell
corepack enable
pnpm install
pnpm run build:css
```

El comando `build:css` verifica que los bundles principales existan y no estén vacíos.

### Convenciones para nuevos cambios

- Añada rutas en `routes/web.php` o `routes/api.php`.
- No cree nuevos PHP de entrada en la raíz.
- Mantenga SQL dentro de repositorios.
- Mantenga reglas de negocio dentro de servicios.
- Proteja las acciones privadas con middleware y permisos.
- Incluya CSRF en formularios y solicitudes AJAX que modifican datos.
- Use `App\Core\Url::route()` para enlaces internos.
- Escape la salida HTML con `e()`.
- Añada o actualice pruebas para cambios de comportamiento.

## Documentación adicional

- [`docs/auditoria-arquitectura.md`](docs/auditoria-arquitectura.md): diagnóstico y decisiones de arquitectura.
- [`docs/cierre-refactorizacion.md`](docs/cierre-refactorizacion.md): resultado y compuerta final de la migración modular.
