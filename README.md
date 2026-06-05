# SembriExport

Sistema web para la gestión agrícola de cultivos de mango, lotes, monitoreo,
plagas, producción, solicitudes de insumos, inventario, proveedores, facturas y
reportes. Incluye a ADA, un asistente virtual conectado con Gemini que responde
consultas utilizando los datos almacenados en MySQL y los permisos del usuario.

## Tecnologías utilizadas

- PHP 8.2 o una versión compatible de PHP 8.x.
- Apache.
- MySQL o MariaDB.
- Composer.
- HTML, CSS y JavaScript.
- Bootstrap y Font Awesome mediante CDN.
- API de Google Gemini para ADA.

## Requisitos del equipo servidor

La forma más sencilla de ejecutar el proyecto en Windows es utilizar XAMPP.

1. Instalar [XAMPP](https://www.apachefriends.org/).
2. Instalar [Composer](https://getcomposer.org/).
3. Tener una exportación SQL de la base de datos entregada por separado. Los
   archivos de base de datos no se almacenan en GitHub.
4. Verificar que PHP tenga habilitadas las extensiones:
   - `mysqli`
   - `curl`
   - `mbstring`
   - `openssl`
5. Tener conexión a Internet para:
   - Descargar las dependencias con Composer.
   - Cargar Bootstrap y Font Awesome.
   - Utilizar el asistente ADA con Gemini.

Para comprobar las versiones instaladas:

```powershell
php -v
composer --version
```

Si Windows no reconoce `php`, puede utilizar directamente:

```powershell
C:\xampp\php\php.exe -v
```

## 1. Copiar el proyecto

Copie la carpeta completa del proyecto dentro de `htdocs`:

```text
C:\xampp\htdocs\proyecto pis
```

También puede cambiar el nombre a uno sin espacios, por ejemplo:

```text
C:\xampp\htdocs\proyecto-pis
```

No copie el archivo `.env` de un equipo público ni lo publique en Git. Este
archivo contiene las credenciales de la base de datos y la clave de Gemini.

## 2. Iniciar XAMPP

Abra el panel de control de XAMPP e inicie:

- Apache
- MySQL

Ambos servicios deben aparecer en estado activo. De forma predeterminada,
Apache utiliza el puerto `80` y MySQL el puerto `3306`.

Si Apache no inicia, compruebe que aplicaciones como IIS, Skype u otro servidor
web no estén utilizando los puertos `80` o `443`.

## 3. Instalar dependencias

Abra PowerShell o CMD dentro de la carpeta del proyecto:

```powershell
cd "C:\xampp\htdocs\proyecto pis"
composer install
```

Este comando genera la carpeta `vendor/` y permite cargar
`vlucas/phpdotenv`. Aunque el proyecto recibido ya contenga `vendor/`, es
recomendable ejecutar `composer install` para instalar las versiones indicadas
en `composer.lock`.

Si Composer usa otra instalación de PHP:

```powershell
composer install --no-dev --optimize-autoloader
```

## 4. Crear la base de datos

Abra phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Después:

1. Seleccione la pestaña **Importar**.
2. Elija el archivo SQL de respaldo recibido por separado.
3. Pulse **Importar** o **Continuar**.
4. Compruebe que se haya creado la base de datos `mangos_pr`.

> **Advertencia:** antes de importar un respaldo sobre una instalación que ya
> contiene información, exporte una copia de seguridad desde phpMyAdmin. Algunos
> respaldos pueden eliminar y volver a crear tablas o la base completa.

También puede importar la base desde **CMD**:

```bat
cd /d "C:\xampp\htdocs\proyecto pis"
C:\xampp\mysql\bin\mysql.exe -u root -p < "C:\ruta\al\respaldo\mangos_pr.sql"
```

Si el usuario `root` no tiene contraseña, presione `Enter` cuando MySQL la
solicite. Este ejemplo utiliza CMD porque la redirección con `<` no funciona
igual en PowerShell.

## 5. Configurar el archivo `.env`

En la raíz del proyecto, duplique `.env.example` y cambie el nombre de la copia
a `.env`.

En PowerShell:

```powershell
Copy-Item .env.example .env
```

Contenido recomendado para una instalación local de XAMPP:

```dotenv
APP_NAME=SembriExport
APP_ENV=local

DB_HOST=localhost
DB_USER=root
DB_PASSWORD=
DB_NAME=mangos_pr
DB_PORT=3306
DB_CHARSET=utf8mb4

GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.0-flash
```

Descripción de las variables:

| Variable | Descripción |
|---|---|
| `APP_NAME` | Nombre mostrado por la aplicación. |
| `APP_ENV` | Entorno de ejecución, normalmente `local`. |
| `DB_HOST` | Servidor MySQL. En XAMPP local se utiliza `localhost`. |
| `DB_USER` | Usuario de MySQL. XAMPP suele utilizar `root`. |
| `DB_PASSWORD` | Contraseña del usuario MySQL. Puede estar vacía en XAMPP. |
| `DB_NAME` | Nombre de la base de datos: `mangos_pr`. |
| `DB_PORT` | Puerto de MySQL, normalmente `3306`. |
| `DB_CHARSET` | Codificación de conexión, recomendada `utf8mb4`. |
| `GEMINI_API_KEY` | Clave privada utilizada por ADA. |
| `GEMINI_MODEL` | Modelo de Gemini que atenderá las consultas. |

No agregue comillas alrededor de los valores salvo que sean realmente parte
del valor. Después de modificar `.env`, recargue la página. Si Apache mantiene
una configuración anterior, reinícielo desde XAMPP.

## 6. Configurar ADA

ADA funciona aunque su interfaz esté visible únicamente cuando existe una
sesión iniciada. Para que pueda generar respuestas, agregue una clave válida:

```dotenv
GEMINI_API_KEY=SU_CLAVE_PRIVADA
```

La clave se utiliza solamente en PHP y no se envía al JavaScript del navegador.
El equipo servidor necesita acceso HTTPS a la API de Google.

Si no desea utilizar ADA, deje `GEMINI_API_KEY` vacía. Los demás módulos del
sistema continuarán funcionando, pero ADA mostrará que la clave no está
configurada.

## 7. Preparar las sesiones

El sistema almacena las sesiones PHP en:

```text
storage/sessions
```

La carpeta se crea automáticamente cuando es necesario, pero Apache debe tener
permiso de escritura. En Windows con XAMPP normalmente no se requiere ninguna
configuración adicional. Si el inicio de sesión no se conserva, compruebe que
la carpeta exista y no sea de solo lectura.

No copie archivos `sess_*` entre equipos. Son sesiones temporales y están
excluidas mediante `.gitignore`.

## 8. Abrir el proyecto en el equipo servidor

Si la carpeta conserva el nombre `proyecto pis`, abra:

```text
http://localhost/proyecto%20pis/
```

Normalmente también funciona:

```text
http://localhost/proyecto pis/
```

Si cambió el nombre a `proyecto-pis`, utilice:

```text
http://localhost/proyecto-pis/
```

Para iniciar sesión directamente:

```text
http://localhost/proyecto%20pis/login.html
```

## 9. Usuarios iniciales

La importación limpia crea estas cuentas de demostración:

| Rol | Correo | Contraseña inicial |
|---|---|---|
| Administrador | `admin@sembriexport.com` | `admin123` |
| Agricultor | `agricultor@sembriexport.com` | `agri123` |
| Bodeguero | `bodeguero@sembriexport.com` | `bodega123` |

Al iniciar sesión por primera vez, el sistema convierte automáticamente las
contraseñas iniciales en hashes seguros.

> Cambie estas contraseñas antes de utilizar el sistema fuera de un entorno de
> demostración.

## 10. Acceder desde otro dispositivo en la misma red

El proyecto se instala y ejecuta en un equipo servidor. Los demás dispositivos
no necesitan XAMPP, PHP, Composer ni MySQL; solamente necesitan un navegador y
estar conectados a la misma red local.

### Obtener la dirección IP del servidor

En el equipo donde está instalado XAMPP, ejecute:

```powershell
ipconfig
```

Busque la dirección **IPv4** del adaptador Wi-Fi o Ethernet. Por ejemplo:

```text
192.168.1.50
```

### Abrir la aplicación desde el otro dispositivo

En el navegador del teléfono, tableta o computadora cliente:

```text
http://192.168.1.50/proyecto%20pis/
```

Si renombró la carpeta:

```text
http://192.168.1.50/proyecto-pis/
```

Reemplace `192.168.1.50` por la IP real del equipo servidor.

### Permitir Apache en el firewall

Si `localhost` funciona en el servidor, pero otro dispositivo no puede abrir la
aplicación:

1. Abra **Firewall de Windows Defender**.
2. Entre en **Permitir una aplicación a través del firewall**.
3. Permita **Apache HTTP Server** en redes privadas.
4. Si Apache no aparece, cree una regla de entrada para `httpd.exe` o para el
   puerto TCP utilizado por Apache, normalmente el `80`.
5. Confirme que ambos dispositivos estén en la misma red y que la red de
   Windows esté configurada como **Privada**.

No es necesario exponer el puerto `3306` de MySQL. Los dispositivos cliente se
conectan a Apache; únicamente PHP se conecta a MySQL dentro del servidor.

### Si Apache utiliza otro puerto

Si Apache está configurado, por ejemplo, en el puerto `8080`, la URL será:

```text
http://192.168.1.50:8080/proyecto%20pis/
```

## 11. Ejecutar el proyecto en una computadora diferente

Para trasladar toda la instalación a otro equipo:

1. Instale XAMPP y Composer en el equipo nuevo.
2. Copie la carpeta del proyecto dentro de `C:\xampp\htdocs\`.
3. Ejecute `composer install`.
4. Exporte la base del equipo anterior desde phpMyAdmin. El archivo SQL se
   transporta por separado y no se obtiene desde GitHub.
5. Importe ese respaldo en el equipo nuevo.
6. Cree el archivo `.env` con las credenciales del nuevo MySQL.
7. Configure nuevamente `GEMINI_API_KEY`.
8. Inicie Apache y MySQL.
9. Abra la URL local del proyecto.

Para conservar información existente, exporte la base desde phpMyAdmin usando
la opción **Exportar** y formato SQL. Guarde ese respaldo fuera del repositorio.

## 12. Comprobaciones rápidas

Compruebe cada punto en este orden:

1. `http://localhost/` muestra la página de XAMPP.
2. `http://localhost/phpmyadmin` abre phpMyAdmin.
3. La base `mangos_pr` aparece con sus tablas.
4. Existe el archivo `.env`.
5. Existe `vendor/autoload.php`.
6. La página pública del proyecto abre sin error.
7. El inicio de sesión redirige al dashboard correspondiente.
8. ADA responde cuando existe una clave de Gemini válida.

## 13. Solución de problemas

### “No se encontró vendor/autoload.php”

Ejecute dentro del proyecto:

```powershell
composer install
```

### “La variable de entorno ... no está configurada”

Compruebe que `.env` esté en la raíz del proyecto, junto a `composer.json`, y
que contenga todas las variables de `.env.example`.

### “No se pudo conectar con la base de datos”

- Confirme que MySQL esté iniciado en XAMPP.
- Verifique `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME` y `DB_PORT`.
- Compruebe que la base `mangos_pr` exista.
- Si MySQL usa otro puerto, actualice `DB_PORT`.

### La página muestra “Not Found”

- Verifique que la carpeta esté dentro de `C:\xampp\htdocs\`.
- Confirme el nombre exacto de la carpeta.
- Use `%20` en la URL si el nombre contiene espacios.

### El inicio de sesión no permanece activo

- Compruebe que `storage/sessions` tenga permisos de escritura.
- Elimine solamente las cookies del sitio en el navegador y vuelva a iniciar
  sesión.
- Reinicie Apache.

### ADA no responde

- Compruebe `GEMINI_API_KEY`.
- Confirme que PHP tenga habilitada la extensión `curl`.
- Verifique que el servidor tenga conexión a Internet.
- Revise que el modelo configurado en `GEMINI_MODEL` esté disponible para la
  clave utilizada.

### Otro dispositivo no puede entrar

- Use la IP IPv4 del servidor, no `localhost`.
- Mantenga Apache iniciado.
- Permita Apache en el firewall para redes privadas.
- Compruebe que ambos dispositivos estén en la misma red.
- Desactive temporalmente una VPN que pueda aislar la red local.

## 14. Recomendaciones de seguridad

- No publique `.env`.
- No comparta la clave de Gemini.
- Cambie las cuentas y contraseñas iniciales.
- Utilice un usuario MySQL exclusivo con contraseña en instalaciones reales.
- Realice copias de seguridad frecuentes de `mangos_pr`.
- No exponga phpMyAdmin ni MySQL directamente a Internet.
- Para acceso fuera de la red local, configure HTTPS, autenticación segura y un
  servidor preparado para producción. No abra simplemente los puertos de XAMPP
  en el router.

## Estructura principal

- `asistente/`: backend, permisos, estilos y JavaScript de ADA.
- `assets/`: imágenes e iconos.
- `config/`: carga de variables de entorno y configuración central.
- `css/`: estilos del sistema.
- `includes/`: autenticación, base de datos, helpers y layout compartido.
- `js/`: comportamiento del frontend.
- `storage/sessions/`: sesiones PHP temporales.
- `composer.json` y `composer.lock`: dependencias PHP.
- `*.php`: módulos funcionales de cada rol.

## Convenciones de desarrollo

- Proteger páginas con `require_auth('Rol')`.
- Escapar contenido dinámico con `e()`.
- Utilizar consultas preparadas para entradas del usuario.
- Mantener estilos compartidos en `css/dashboard.css`.
- Mantener secretos y credenciales únicamente en `.env`.
