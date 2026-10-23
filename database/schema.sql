CREATE DATABASE IF NOT EXISTS bodega_ventas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bodega_ventas;

CREATE TABLE IF NOT EXISTS producto (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(50) NOT NULL,
    nombre       VARCHAR(150) NOT NULL,
    categoria    VARCHAR(100) NOT NULL,
    precio       DECIMAL(10, 2) NOT NULL,
    stock        INT UNSIGNED NOT NULL DEFAULT 0,
    stock_minimo INT UNSIGNED NOT NULL DEFAULT 0,
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_producto PRIMARY KEY (id),
    CONSTRAINT uq_producto_codigo UNIQUE (codigo),
    CONSTRAINT chk_producto_precio CHECK (precio >= 0),
    CONSTRAINT chk_producto_stock CHECK (stock >= 0),
    CONSTRAINT chk_producto_stock_minimo CHECK (stock_minimo >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS venta (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    producto_id INT UNSIGNED NOT NULL,
    cantidad    INT UNSIGNED NOT NULL,
    precio_unit DECIMAL(10, 2) NOT NULL,
    total       DECIMAL(10, 2) NOT NULL,
    vendido_en  DATETIME NOT NULL,
    CONSTRAINT pk_venta PRIMARY KEY (id),
    CONSTRAINT fk_venta_producto FOREIGN KEY (producto_id)
        REFERENCES producto (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_venta_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_venta_precio_unit CHECK (precio_unit >= 0),
    CONSTRAINT chk_venta_total CHECK (total >= 0),
    INDEX idx_venta_vendido_en (vendido_en)
) ENGINE=InnoDB;
