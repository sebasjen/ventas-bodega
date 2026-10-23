# Minimarket «La Esquina»

Aplicación local de inventario y ventas para PHP 8.1 o posterior, PDO y MySQL/MariaDB en XAMPP.

## Requisitos

- XAMPP con PHP 8.1 o posterior y la extensión `pdo_mysql` habilitada.
- MySQL o MariaDB iniciado desde el panel de XAMPP.

## Puesta en marcha

1. En phpMyAdmin, importa `database/schema.sql`. El archivo crea la base de datos `bodega_ventas` y sus tablas.
2. Copia `.env.example` como `.env` en la raíz del proyecto y configura las credenciales locales de MySQL.
3. Abre `http://localhost/ventas-bodega/public/` en el navegador.

La carpeta `public/` es el punto de entrada web. `config/`, `src/` y `database/` deben permanecer fuera del directorio público.

## Rutas principales

- `?r=productos`: inventario y alertas de stock mínimo.
- `?r=productos/crear`: alta de productos.
- `?r=ventas/registrar`: formulario y confirmación de venta.
- `?r=ventas/reporte`: reporte del día.

Las ventas se guardan en una transacción PDO; el registro y el descuento de stock se confirman juntos o se revierten juntos.
