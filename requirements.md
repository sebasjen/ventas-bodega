# Requirements — Minimarket «La Esquina»

> **Formato:** EARS (Easy Approach to Requirements Syntax)  
> **Patrón principal:** `CUANDO [evento] EL SISTEMA DEBERÁ [respuesta]`  
> **Fuente base:** D0.md  
> **Trazabilidad:** cada criterio indica las Reglas de Negocio (RN) que cubre.

---

## RF1 — Registrar Producto

**Descripción:** El cajero puede dar de alta un producto especificando código único, nombre,
categoría, precio de venta, stock actual y stock mínimo.

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RF1-CA1 | CUANDO el cajero envíe el formulario de nuevo producto con todos los campos obligatorios (código, nombre, categoría, precio, stock actual, stock mínimo) válidos, EL SISTEMA DEBERÁ persistir el registro en la base de datos y mostrar un mensaje de confirmación «Producto registrado». | RN3, RN4 |
| RF1-CA2 | CUANDO el cajero intente registrar un producto con un código que ya existe en la base de datos, EL SISTEMA DEBERÁ rechazar la operación y mostrar el error «El código de producto ya está registrado» sin alterar el registro existente. | RN4 |
| RF1-CA3 | CUANDO el cajero ingrese un precio de venta negativo o igual a cero, EL SISTEMA DEBERÁ rechazar el formulario y mostrar el error «El precio debe ser mayor a cero». | RN3 |
| RF1-CA4 | CUANDO el cajero deje vacío cualquier campo obligatorio, EL SISTEMA DEBERÁ resaltar el campo faltante e impedir el envío del formulario. | — |
| RF1-CA5 | CUANDO el cajero ingrese un stock actual o stock mínimo negativo, EL SISTEMA DEBERÁ rechazar el formulario y mostrar el error «El stock no puede ser negativo». | — |

---

## RF2 — Listar Inventario con Alertas de Stock Mínimo

**Descripción:** El cajero puede ver el listado completo de productos; los que están en o por
debajo de su stock mínimo se resaltan visualmente.

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RF2-CA1 | CUANDO el cajero acceda a la vista de inventario, EL SISTEMA DEBERÁ mostrar todos los productos activos con sus campos (código, nombre, categoría, precio, stock actual, stock mínimo). | — |
| RF2-CA2 | CUANDO el stock actual de un producto sea menor o igual a su stock mínimo, EL SISTEMA DEBERÁ resaltar visualmente esa fila (por ejemplo, fondo rojo o ícono de alerta) diferenciándola del resto. | — |
| RF2-CA3 | CUANDO no existan productos registrados, EL SISTEMA DEBERÁ mostrar el mensaje «No hay productos registrados» en lugar de una tabla vacía. | — |
| RF2-CA4 | CUANDO se registre o actualice un producto, EL SISTEMA DEBERÁ reflejar el cambio de forma inmediata al recargar la vista de inventario. | — |

---

## RF3 — Registrar Venta

**Descripción:** El cajero selecciona un producto, indica la cantidad vendida; el sistema calcula
el total, registra fecha/hora y descuenta el stock de forma atómica.

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RF3-CA1 | CUANDO el cajero seleccione un producto y envíe una cantidad mayor a cero, EL SISTEMA DEBERÁ, en una única transacción, registrar la venta con fecha/hora actuales, calcular el total (precio × cantidad) y descontar la cantidad del stock del producto. | RN1, RN2, RN5 |
| RF3-CA2 | CUANDO el cajero intente vender una cantidad mayor al stock disponible, EL SISTEMA DEBERÁ rechazar la operación, mostrar el error «Stock insuficiente. Disponible: [X] unidades» y no modificar ningún dato. | RN2 |
| RF3-CA3 | CUANDO el cajero ingrese una cantidad igual a cero o negativa, EL SISTEMA DEBERÁ rechazar el formulario y mostrar el error «La cantidad debe ser mayor a cero». | RN1 |
| RF3-CA4 | CUANDO ocurra un error durante la escritura en base de datos (fallo en INSERT o UPDATE), EL SISTEMA DEBERÁ ejecutar un rollback completo, dejar el stock sin cambios y mostrar el error «Error al procesar la venta. Intente nuevamente». | RN5 |
| RF3-CA5 | CUANDO la venta sea registrada exitosamente, EL SISTEMA DEBERÁ mostrar un resumen con: producto, cantidad, precio unitario, total y hora de la venta. | — |
| RF3-CA6 | CUANDO se registre una venta, EL SISTEMA DEBERÁ incluir en el registro el token CSRF verificado para prevenir envíos forjados. | — |

---

## RF4 — Reporte de Ventas del Día

**Descripción:** El cajero puede ver todas las ventas realizadas en el día actual junto con el
total acumulado.

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RF4-CA1 | CUANDO el cajero acceda al reporte diario, EL SISTEMA DEBERÁ listar únicamente las ventas cuya fecha/hora corresponda al día actual (mismo día calendario del servidor). | — |
| RF4-CA2 | CUANDO el cajero visualice el reporte diario, EL SISTEMA DEBERÁ mostrar al pie de la tabla el total acumulado de todas las ventas del día. | — |
| RF4-CA3 | CUANDO no existan ventas registradas en el día actual, EL SISTEMA DEBERÁ mostrar el mensaje «Sin ventas registradas hoy» y total acumulado $0.00. | — |
| RF4-CA4 | CUANDO el cajero visualice el reporte, EL SISTEMA DEBERÁ mostrar por cada venta: código de producto, nombre, cantidad, precio unitario, total de la línea y hora exacta. | — |

---

## RNF — Requisitos No Funcionales

### RNF1 — Seguridad

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RNF1-CA1 | CUANDO el sistema genere cualquier formulario de escritura (producto o venta), EL SISTEMA DEBERÁ incluir un token CSRF único por sesión y validarlo en el servidor antes de procesar la petición. | — |
| RNF1-CA2 | CUANDO el sistema muestre datos provenientes de la base de datos o del usuario en HTML, EL SISTEMA DEBERÁ escapar todas las salidas con `htmlspecialchars()` para prevenir ataques XSS. | — |
| RNF1-CA3 | CUANDO el sistema construya cualquier consulta SQL con datos de entrada, EL SISTEMA DEBERÁ usar sentencias preparadas (PDO `prepare` + `bindParam`/`bindValue`) y nunca interpolar variables directamente en el SQL. | — |

### RNF2 — Integridad de Datos

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RNF2-CA1 | CUANDO se inicie el proceso de registro de venta, EL SISTEMA DEBERÁ abrir una transacción PDO (`beginTransaction`) que englobe tanto el INSERT en ventas como el UPDATE de stock, haciendo `commit` solo si ambas operaciones tienen éxito y `rollBack` si cualquiera falla. | RN5 |
| RNF2-CA2 | CUANDO la base de datos rechace una operación por violación de restricción (UNIQUE, CHECK, FK), EL SISTEMA DEBERÁ capturar la excepción PDO, ejecutar rollback y traducir el error a un mensaje comprensible para el cajero. | RN3, RN4 |

### RNF3 — Calidad de Código

| ID | Criterio de Aceptación (EARS) | RN trazadas |
|----|-------------------------------|-------------|
| RNF3-CA1 | CUANDO se agregue o modifique cualquier archivo PHP del sistema, EL SISTEMA DEBERÁ declarar `declare(strict_types=1);` al inicio y usar tipado estricto en todas las firmas de funciones y métodos. | — |
| RNF3-CA2 | CUANDO se estructure el código, EL SISTEMA DEBERÁ seguir la separación en cinco capas (Model → Repository → Service → Controller → views) sin saltarse niveles de abstracción. | — |

---

## Matriz de Trazabilidad RN → Criterios

| Regla de Negocio | Descripción | Criterios que la cubren |
|------------------|-------------|------------------------|
| RN1 | Cantidad vendida > 0 | RF3-CA1, RF3-CA3 |
| RN2 | No vender más stock del disponible | RF3-CA1, RF3-CA2 |
| RN3 | Precio ≥ 0 (no negativo) | RF1-CA3, RNF2-CA2 |
| RN4 | Código de producto único | RF1-CA2, RNF2-CA2 |
| RN5 | Venta y descuento de stock son atómicos | RF3-CA1, RF3-CA4, RNF2-CA1 |
