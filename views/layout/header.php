<?php
declare(strict_types=1);

namespace App\Views;

/**
 * Plantilla de apertura HTML y navegación compartida por las vistas.
 *
 * @var string $title Título seguro preparado por el Controller.
 * @var string $currentRoute Ruta lógica usada para indicar la página activa.
 */
require_once dirname(__DIR__) . '/helpers.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::escape($title) ?> · Minimarket La Esquina</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php?r=productos">La Esquina <span>Minimarket</span></a>
    <nav aria-label="Navegación principal">
        <a href="index.php?r=productos"<?= $currentRoute === 'productos' ? ' aria-current="page"' : '' ?>>Inventario</a>
        <a href="index.php?r=productos/crear"<?= $currentRoute === 'productos/crear' ? ' aria-current="page"' : '' ?>>Nuevo producto</a>
        <a href="index.php?r=ventas/registrar"<?= $currentRoute === 'ventas/registrar' ? ' aria-current="page"' : '' ?>>Registrar venta</a>
        <a href="index.php?r=ventas/reporte"<?= $currentRoute === 'ventas/reporte' ? ' aria-current="page"' : '' ?>>Reporte diario</a>
    </nav>
</header>
<main class="container">
    <h1><?= Html::escape($title) ?></h1>
