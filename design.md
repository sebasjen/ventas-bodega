# Design — Minimarket «La Esquina»

> **Fuente base:** D0.md · **Requisitos:** requirements.md  
> **Stack:** PHP 8 · PDO · MySQL/MariaDB · XAMPP · Sin framework externo

---

## 1. Arquitectura en 5 Capas

```
┌─────────────────────────────────────────────┐
│                   views/                    │  ← Capa 5: Presentación
├─────────────────────────────────────────────┤
│               Controller/                   │  ← Capa 4: Orquestación HTTP
├─────────────────────────────────────────────┤
│                Service/                     │  ← Capa 3: Lógica de Negocio
├─────────────────────────────────────────────┤
│              Repository/                    │  ← Capa 2: Acceso a Datos
├─────────────────────────────────────────────┤
│                 Model/                      │  ← Capa 1: Entidades
└─────────────────────────────────────────────┘
```

La dependencia es **estrictamente descendente**: cada capa solo conoce a la
inmediatamente inferior. Ninguna capa puede saltar niveles.

---

### Capa 1 — Model (Entidades)

**Propósito:** Representar los datos del dominio como objetos PHP tipados.  
Actúa como contrato de estructura: define qué campos existen y sus tipos.

**Qué HACE:**
- Define clases PHP puras con propiedades tipadas (`int`, `string`, `float`).
- Puede incluir getters/setters o ser un `readonly` class de PHP 8.1+.
- Puede contener validaciones de formato de campo (p. ej. que el código no esté vacío).

**Qué NO HACE:**
- No ejecuta consultas SQL ni conoce PDO.
- No contiene lógica de negocio (reglas de stock, cálculo de totales).
- No genera HTML ni procesa `$_POST`/`$_GET`.

**Archivos:**
```
src/Model/Producto.php
src/Model/Venta.php
```

---

### Capa 2 — Repository (Acceso a Datos)

**Propósito:** Encapsular **todas** las consultas SQL. Es la única capa que
habla con PDO y con la base de datos.

**Qué HACE:**
- Recibe un objeto `PDO` por inyección de dependencia en el constructor.
- Implementa métodos CRUD: `findAll()`, `findById()`, `save()`, `update()`.
- Usa sentencias preparadas (`prepare` + `bindParam`/`bindValue`) en todo momento.
- Lanza excepciones propias (`RepositoryException`) ante fallos de PDO.
- Expone métodos de bajo nivel sin tomar decisiones de negocio.

**Qué NO HACE:**
- No valida reglas de negocio (eso es responsabilidad de Service).
- No abre ni cierra transacciones (eso lo hace Service para mantener atomicidad
  entre múltiples llamadas al repositorio).
- No genera HTML ni redirige.

**Archivos:**
```
src/Repository/ProductoRepository.php
src/Repository/VentaRepository.php
```

---

### Capa 3 — Service (Lógica de Negocio)

**Propósito:** Orquestar las reglas de negocio (RN1–RN5) y las transacciones.
Es el «cerebro» de la aplicación.

**Qué HACE:**
- Valida las reglas de negocio antes de persistir (cantidad > 0, stock suficiente,
  precio ≥ 0, código único).
- Abre, confirma o revierte transacciones PDO que involucren más de un repositorio.
- Calcula valores derivados (total = precio × cantidad).
- Lanza excepciones de dominio (`StockInsuficienteException`,
  `CodigoDuplicadoException`, `ValidationException`) con mensajes claros.
- Coordina llamadas a `ProductoRepository` y `VentaRepository` dentro de la
  misma unidad de trabajo.

**Qué NO HACE:**
- No ejecuta SQL directamente; siempre delega en los repositorios.
- No genera HTML ni conoce `$_POST`/`$_GET`.
- No decide rutas ni códigos HTTP.

**Archivos:**
```
src/Service/ProductoService.php
src/Service/VentaService.php
```

---

### Capa 4 — Controller (Orquestación HTTP)

**Propósito:** Traducir la petición HTTP en una llamada al servicio y la
respuesta del servicio en una respuesta HTTP (redirección o render de vista).

**Qué HACE:**
- Lee `$_POST`, `$_GET` y `$_SESSION`.
- Valida y regenera el token CSRF antes de cualquier operación de escritura.
- Llama al Service correspondiente con los datos saneados.
- Captura las excepciones de dominio y las convierte en mensajes de error para
  la vista.
- Decide a qué vista renderizar o a qué URL redirigir (POST-Redirect-GET).
- Escapa todas las salidas con `htmlspecialchars()` antes de pasarlas a la vista.

**Qué NO HACE:**
- No ejecuta SQL ni instancia repositorios directamente.
- No contiene lógica de negocio (cálculos, validaciones de dominio).
- No genera HTML embebido en PHP (`echo "<table>..."`) — eso es la vista.

**Archivos:**
```
src/Controller/ProductoController.php
src/Controller/VentaController.php
```

---

### Capa 5 — views (Presentación)

**Propósito:** Renderizar HTML a partir de variables preparadas por el
Controller. Sin lógica de negocio.

**Qué HACE:**
- Recibe arrays/objetos ya saneados del Controller.
- Genera HTML con PHP como motor de plantillas mínimo (`<?= htmlspecialchars($var) ?>`).
- Muestra mensajes de error o éxito pasados por el Controller.
- Resalta visualmente las filas de stock bajo (RF2-CA2).

**Qué NO HACE:**
- No accede a `$_POST`/`$_GET` directamente.
- No llama a servicios ni repositorios.
- No toma decisiones de flujo; solo presenta lo que recibe.

**Archivos:**
```
views/productos/lista.php
views/productos/crear.php
views/ventas/registrar.php
views/ventas/reporte.php
views/layout/header.php
views/layout/footer.php
```

---

## 2. Estructura de Directorios

```
ventas-bodega/
├── public/
│   └── index.php            ← Front controller único (entry point)
├── src/
│   ├── Model/
│   │   ├── Producto.php
│   │   └── Venta.php
│   ├── Repository/
│   │   ├── ProductoRepository.php
│   │   └── VentaRepository.php
│   ├── Service/
│   │   ├── ProductoService.php
│   │   └── VentaService.php
│   ├── Controller/
│   │   ├── ProductoController.php
│   │   └── VentaController.php
│   └── Exception/
│       ├── ValidationException.php
│       ├── StockInsuficienteException.php
│       └── CodigoDuplicadoException.php
├── views/
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php
│   ├── productos/
│   │   ├── lista.php
│   │   └── crear.php
│   └── ventas/
│       ├── registrar.php
│       └── reporte.php
├── config/
│   └── database.php         ← Crea y devuelve la instancia PDO
├── D0.md
├── requirements.md
└── design.md
```

---

## 3. Diseño de Tablas

### 3.1 Tabla `producto`

```sql
CREATE TABLE producto (
    id           INT            UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(50)    NOT NULL,
    nombre       VARCHAR(150)   NOT NULL,
    categoria    VARCHAR(100)   NOT NULL,
    precio       DECIMAL(10,2)  NOT NULL,
    stock        INT            UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT            UNSIGNED NOT NULL DEFAULT 0,
    creado_en    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Llave primaria
    CONSTRAINT pk_producto PRIMARY KEY (id),

    -- Código único (RN4)
    CONSTRAINT uq_producto_codigo UNIQUE (codigo),

    -- Precio no negativo (RN3)
    CONSTRAINT chk_producto_precio  CHECK (precio >= 0),

    -- Stock no negativo
    CONSTRAINT chk_producto_stock   CHECK (stock >= 0),
    CONSTRAINT chk_producto_stock_min CHECK (stock_minimo >= 0)
);
```

| Campo         | Tipo              | PK | FK | UNIQUE | NOT NULL | CHECK        | Descripción                              |
|---------------|-------------------|----|----|--------|----------|--------------|------------------------------------------|
| `id`          | INT UNSIGNED      | ✓  |    |        | ✓        | —            | Llave primaria autoincremental            |
| `codigo`      | VARCHAR(50)       |    |    | ✓      | ✓        | —            | Código alfanumérico único del producto   |
| `nombre`      | VARCHAR(150)      |    |    |        | ✓        | —            | Nombre descriptivo                        |
| `categoria`   | VARCHAR(100)      |    |    |        | ✓        | —            | Categoría (bebidas, lácteos, etc.)        |
| `precio`      | DECIMAL(10,2)     |    |    |        | ✓        | `>= 0`       | Precio de venta al público (RN3)         |
| `stock`       | INT UNSIGNED      |    |    |        | ✓        | `>= 0`       | Unidades disponibles actuales             |
| `stock_minimo`| INT UNSIGNED      |    |    |        | ✓        | `>= 0`       | Umbral para alerta visual (RF2-CA2)      |
| `creado_en`   | DATETIME          |    |    |        | ✓        | —            | Marca de tiempo de alta                  |

---

### 3.2 Tabla `venta`

```sql
CREATE TABLE venta (
    id           INT            UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id  INT            UNSIGNED NOT NULL,
    cantidad     INT            UNSIGNED NOT NULL,
    precio_unit  DECIMAL(10,2)  NOT NULL,
    total        DECIMAL(10,2)  NOT NULL,
    vendido_en   DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Llave primaria
    CONSTRAINT pk_venta PRIMARY KEY (id),

    -- Llave foránea → producto
    CONSTRAINT fk_venta_producto
        FOREIGN KEY (producto_id) REFERENCES producto(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    -- Cantidad vendida debe ser mayor a cero (RN1)
    CONSTRAINT chk_venta_cantidad CHECK (cantidad > 0),

    -- Precio y total no negativos
    CONSTRAINT chk_venta_precio_unit CHECK (precio_unit >= 0),
    CONSTRAINT chk_venta_total       CHECK (total >= 0)
);
```

| Campo         | Tipo              | PK | FK              | UNIQUE | NOT NULL | CHECK    | Descripción                                      |
|---------------|-------------------|----|-----------------|--------|----------|----------|--------------------------------------------------|
| `id`          | INT UNSIGNED      | ✓  |                 |        | ✓        | —        | Llave primaria autoincremental                   |
| `producto_id` | INT UNSIGNED      |    | → `producto.id` |        | ✓        | —        | Producto vendido                                 |
| `cantidad`    | INT UNSIGNED      |    |                 |        | ✓        | `> 0`    | Unidades vendidas (RN1)                          |
| `precio_unit` | DECIMAL(10,2)     |    |                 |        | ✓        | `>= 0`   | Precio al momento de la venta (snapshot)         |
| `total`       | DECIMAL(10,2)     |    |                 |        | ✓        | `>= 0`   | `precio_unit × cantidad`, calculado en Service   |
| `vendido_en`  | DATETIME          |    |                 |        | ✓        | —        | Fecha/hora de la transacción (RF3-CA1, RF4-CA1)  |

> **Nota de diseño:** `precio_unit` es un snapshot del precio en el momento de
> la venta. Si el precio del producto cambia después, los registros históricos
> conservan el precio original.

---

## 4. Contratos de Clases

### 4.1 Model

```php
// src/Model/Producto.php
declare(strict_types=1);

final class Producto
{
    public function __construct(
        public readonly int    $id,
        public readonly string $codigo,
        public readonly string $nombre,
        public readonly string $categoria,
        public readonly float  $precio,
        public readonly int    $stock,
        public readonly int    $stockMinimo,
        public readonly string $creadoEn,
    ) {}
}
```

```php
// src/Model/Venta.php
declare(strict_types=1);

final class Venta
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $productoId,
        public readonly int    $cantidad,
        public readonly float  $precioUnit,
        public readonly float  $total,
        public readonly string $vendidoEn,
    ) {}
}
```

---

### 4.2 Repository

```php
// src/Repository/ProductoRepository.php
declare(strict_types=1);

interface ProductoRepositoryInterface
{
    /** Devuelve todos los productos ordenados por nombre. */
    public function findAll(): array;                          // Producto[]

    /** Devuelve un producto por su PK o null si no existe. */
    public function findById(int $id): ?Producto;

    /** Devuelve un producto por su código único o null. */
    public function findByCodigo(string $codigo): ?Producto;

    /**
     * Inserta un nuevo producto.
     * @throws RepositoryException si falla la inserción.
     */
    public function save(
        string $codigo,
        string $nombre,
        string $categoria,
        float  $precio,
        int    $stock,
        int    $stockMinimo,
    ): int;                                                   // retorna el nuevo id

    /**
     * Descuenta 'cantidad' unidades del stock del producto.
     * Se llama dentro de una transacción gestionada por VentaService.
     * @throws RepositoryException si el UPDATE falla.
     */
    public function decrementarStock(int $productoId, int $cantidad): void;
}
```

```php
// src/Repository/VentaRepository.php
declare(strict_types=1);

interface VentaRepositoryInterface
{
    /**
     * Inserta un registro de venta.
     * Se llama dentro de una transacción gestionada por VentaService.
     * @throws RepositoryException si el INSERT falla.
     */
    public function save(
        int   $productoId,
        int   $cantidad,
        float $precioUnit,
        float $total,
    ): int;                                                   // retorna el nuevo id

    /** Devuelve las ventas del día actual ordenadas por hora descendente. */
    public function findHoy(): array;                         // Venta[]

    /** Suma los totales de las ventas del día. */
    public function totalHoy(): float;
}
```

---

### 4.3 Service

```php
// src/Service/ProductoService.php
declare(strict_types=1);

interface ProductoServiceInterface
{
    /**
     * Valida y registra un nuevo producto.
     * @throws ValidationException      si algún campo es inválido.
     * @throws CodigoDuplicadoException si el código ya existe (RN4).
     */
    public function registrar(
        string $codigo,
        string $nombre,
        string $categoria,
        float  $precio,
        int    $stock,
        int    $stockMinimo,
    ): Producto;

    /** Retorna todos los productos; marca cuáles están bajo stock mínimo. */
    public function listar(): array;                          // Producto[]
}
```

```php
// src/Service/VentaService.php
declare(strict_types=1);

interface VentaServiceInterface
{
    /**
     * Registra la venta y descuenta stock en una transacción atómica.
     * @throws ValidationException         si cantidad <= 0 (RN1).
     * @throws StockInsuficienteException  si cantidad > stock (RN2).
     * @throws \RuntimeException           si la transacción falla (RN5).
     */
    public function registrar(int $productoId, int $cantidad): Venta;

    /** Devuelve las ventas del día y el total acumulado. */
    public function reporteHoy(): array;                      // ['ventas' => Venta[], 'total' => float]
}
```

---

### 4.4 Controller

```php
// src/Controller/ProductoController.php
declare(strict_types=1);

final class ProductoController
{
    public function __construct(
        private readonly ProductoServiceInterface $productoService,
    ) {}

    /** GET /productos → muestra el inventario. */
    public function index(): void;

    /** GET /productos/crear → muestra el formulario. */
    public function crear(): void;

    /** POST /productos/guardar → valida CSRF, llama al service, redirige. */
    public function guardar(): void;
}
```

```php
// src/Controller/VentaController.php
declare(strict_types=1);

final class VentaController
{
    public function __construct(
        private readonly VentaServiceInterface    $ventaService,
        private readonly ProductoServiceInterface $productoService,
    ) {}

    /** GET /ventas/registrar → muestra formulario de venta. */
    public function registrar(): void;

    /** POST /ventas/guardar → valida CSRF, registra venta, redirige. */
    public function guardar(): void;

    /** GET /ventas/reporte → muestra las ventas del día. */
    public function reporte(): void;
}
```

---

## 5. Manejo de Transacciones

La transacción la **abre y cierra `VentaService`**, que es quien orquesta los
dos pasos atómicos requeridos por RN5.

```
VentaService::registrar(productoId, cantidad)
    │
    ├─ 1. Validar cantidad > 0                         [lanza ValidationException]
    ├─ 2. $producto = ProductoRepository::findById()
    ├─ 3. Validar stock suficiente                     [lanza StockInsuficienteException]
    ├─ 4. Calcular total = precio × cantidad
    │
    ├─ 5. $pdo->beginTransaction()
    │       │
    │       ├─ 6. VentaRepository::save(...)           [INSERT venta]
    │       ├─ 7. ProductoRepository::decrementarStock(...) [UPDATE producto]
    │       │
    │       ├─ éxito → $pdo->commit()
    │       └─ falla → $pdo->rollBack()  +  lanza \RuntimeException
    │
    └─ 8. Retorna objeto Venta hidratado
```

**Puntos clave de implementación:**

- `PDO::ATTR_ERRMODE` debe estar en `PDO::ERRMODE_EXCEPTION` para que los
  errores SQL lancen `PDOException` automáticamente.
- `PDO::ATTR_AUTOCOMMIT` = `false` dentro de la transacción; PDO lo maneja
  internamente con `beginTransaction`.
- El objeto `PDO` se crea **una sola vez** en `config/database.php` y se
  inyecta en repositorios y services (no hay new PDO() dispersos).
- `decrementarStock` usa `UPDATE producto SET stock = stock - :cantidad WHERE id = :id`
  para que el decremento sea atómico a nivel de fila en MySQL y no dependa de
  un valor leído previamente en PHP.

---

## 6. Manejo de Excepciones

### Jerarquía

```
\Exception
└── AppException                     (base de todas las excepciones propias)
    ├── ValidationException          (datos de entrada inválidos, HTTP 400)
    │   ├── CodigoDuplicadoException (RN4 — código ya registrado)
    │   └── StockInsuficienteException (RN2 — sin stock)
    └── RepositoryException          (fallo de base de datos, HTTP 500)
```

### Flujo de captura por capa

| Dónde se lanza | Tipo de excepción | Quién la captura | Acción |
|---|---|---|---|
| `ProductoService` | `CodigoDuplicadoException` | `ProductoController` | Muestra «Código ya registrado» en el formulario |
| `ProductoService` | `ValidationException` | `ProductoController` | Muestra el mensaje de validación al usuario |
| `VentaService` | `StockInsuficienteException` | `VentaController` | Muestra «Stock insuficiente. Disponible: X» |
| `VentaService` | `ValidationException` | `VentaController` | Muestra «La cantidad debe ser mayor a cero» |
| `VentaService` | `\RuntimeException` | `VentaController` | Muestra «Error al procesar la venta. Intente nuevamente» |
| `*Repository` | `RepositoryException` | `*Service` | Re-lanza como `\RuntimeException` tras hacer rollback |
| Cualquier capa | `PDOException` | `*Repository` | Envuelve en `RepositoryException` con mensaje seguro |

### Regla de oro

> **Nunca** exponer mensajes de `PDOException` directamente al usuario
> (pueden filtrar estructura de la base de datos). Los repositorios los
> capturan y relanza una `RepositoryException` con un mensaje genérico seguro.
> El mensaje técnico se registra en el log del servidor (error_log).

---

## 7. Seguridad Transversal

| Mecanismo | Capa responsable | Detalle |
|-----------|-----------------|---------|
| Token CSRF | Controller | Generado en `$_SESSION['csrf_token']` con `bin2hex(random_bytes(32))`. Validado antes de cada POST. |
| Escape XSS | views | Toda variable dinámica se imprime con `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`. |
| SQL Injection | Repository | Cero interpolación en SQL. Solo `prepare()` + `bindParam()`/`bindValue()`. |
| Tipado estricto | Todas | `declare(strict_types=1);` en todos los archivos PHP. |
| Strict types PDO | config | `PDO::ATTR_EMULATE_PREPARES => false` para que el driver valide tipos reales. |
