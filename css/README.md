# Arquitectura CSS

Los estilos editables de la aplicación viven en `css/src`. El archivo
Los archivos `css/dashboard.css`, `admin.css`, `farmer.css`, `warehouse.css`,
`public.css` y `auth.css` son bundles generados y no deben editarse directamente.
`css/theme.css` es una capa final deliberadamente pequeña: adapta superficies
y controles a los temas oscuro y noche, pero no redefine componentes completos.

## Compilar

```powershell
node scripts/build-css.mjs
```

El compilador no requiere paquetes externos y conserva el orden de la cascada.

## Modulos

- `00-foundation.css`: tokens, reset y estructura compartida.
- `10-farmer.css`: panel y flujos del agricultor.
- `20-admin.css`: panel administrativo.
- `30-public-home.css`: portada publica.
- `40-interactions-theme.css`: movimiento y selector de apariencia.
- `50-warehouse.css`: panel de bodega.
- `60-modals.css`: modales administrativos.
- `70-reports.css`: reportes generales.
- `80-auth.css`: autenticacion.
- `90-public-inner.css`: productos y pagina corporativa.
- `100-warehouse-reports.css`: reportes e historial de bodega.
- `110-request-history.css`: historial del agricultor.
- `120-public.css`: componentes publicos compartidos.
- `130-purchase-invoice.css`: facturas de compra.
- `140-compatibility.css`: ajustes responsivos y compatibilidad final.
- `150-home-hero.css`: composicion final del hero de inicio.
- `160-role-actions.css`: acciones principales compartidas por rol.
- `170-admin-controls.css`: controles, filtros y selectores del administrador.
- `180-admin-minimal.css`: acabado visual y jerarquía del panel administrativo.
- `190-admin-modals.css`: ajuste final, variantes y adaptación responsive de modales administrativos.
- `200-admin-invoices.css`: composición financiera de métricas, filtros y tabla de facturas del administrador.

## Reglas de mantenimiento

1. Agregar estilos al modulo propietario del componente.
2. Usar las variables de `00-foundation.css` para color, espacio, radios y sombras.
3. Limitar `!important` a integraciones con utilidades de Bootstrap o estilos de impresion.
4. Evitar selectores por pagina cuando una clase de componente sea suficiente.
5. Compilar y revisar las paginas afectadas antes de entregar cambios.
6. No crear hojas globales paralelas: todo CSS usado debe pertenecer a un modulo
   de `css/src` o a la capa de tokens de `theme.css`.
