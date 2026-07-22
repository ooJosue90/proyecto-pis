# Cierre de la refactorización modular

Fecha de verificación: 22 de julio de 2026.

## Estado

La migración funcional a monolito modular está completa para autenticación,
cultivos, lotes, plagas, producción, solicitudes, inventario, insumos,
proveedores, pedidos, facturas, reportes, usuarios, movimientos, dashboards y
ADA. Las vistas se encuentran dentro de `app/Modules`; las utilidades de vista
compartidas viven en `app/Shared`. Ya no existen PHP en la raíz: Apache dirige
las rutas limpias al único Front Controller `public/index.php`.

## Verificaciones ejecutadas

- PHP 8.2.12.
- `composer validate`: configuración válida.
- `composer dump-autoload --optimize`: autoload PSR-4 generado.
- `php -l`: sin errores en Core, módulos, configuración, rutas y pruebas.
- PHPUnit: 47 pruebas y 89 aserciones correctas.
- Apache/XAMPP: dashboards de Administrador y Bodeguero, gestión agrícola,
  solicitudes, movimientos, calculadora e historial responden correctamente con
  las sesiones de sus roles.
- Control de acceso: un Agricultor recibe 403 al solicitar el dashboard de
  Administrador.
- Directorios internos (`app`, `config`, `routes`, `storage`) y archivos
  sensibles (`.env`, archivos dot, Composer y PHPUnit) responden 403.
- `.env` está ignorado por Git y `.env.example` no contiene secretos.

## Front Controller único

Los nombres PHP históricos fueron retirados después de migrar JavaScript,
formularios, redirecciones y navegación. Las URLs públicas usan rutas como
`/dashboard/admin`, `/abastecimiento`, `/facturas` y `/solicitudes/historial`.
El `.htaccess` de la raíz conserva archivos estáticos y reescribe las rutas de
aplicación hacia `public/index.php`.

## Puesta en marcha

1. Iniciar Apache y MySQL desde XAMPP.
2. Importar el respaldo y las migraciones con phpMyAdmin.
3. Crear `.env` desde `.env.example`.
4. Ejecutar `composer install` y `composer dump-autoload`.
5. Abrir `http://localhost/proyecto-pis/`.

Antes de desplegar fuera del entorno local, usar `APP_ENV=production`,
`APP_DEBUG=false`, HTTPS y un usuario MySQL dedicado con contraseña.
