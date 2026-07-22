# Arquitectura CSS

El rediseño utiliza cuatro hojas autocontenidas dentro de `css/`:

- `admin.css`: paneles de administrador, agricultor, bodega y asistente ADA.
- `auth.css`: acceso y recuperación de contraseña.
- `public.css`: portada, productos y página corporativa.
- `material-icons.css`: soporte local para Material Symbols.

Los bundles se mantienen directamente; la antigua carpeta modular `css/src`
pertenece al diseño anterior y ya no forma parte de esta versión.

## Verificar

```powershell
pnpm run build:css
```

El comando comprueba que las cuatro hojas requeridas existan y no estén vacías.
Después de modificar estilos, valide también las páginas públicas y los paneles
de cada rol en el navegador.
