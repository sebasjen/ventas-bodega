<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once dirname(__DIR__) . '/src/Exception/RepositoryException.php';
require_once dirname(__DIR__) . '/src/Exception/ValidationException.php';
require_once dirname(__DIR__) . '/src/Exception/CodigoDuplicadoException.php';
require_once dirname(__DIR__) . '/src/Exception/StockInsuficienteException.php';
require_once dirname(__DIR__) . '/src/Model/Producto.php';
require_once dirname(__DIR__) . '/src/Model/Venta.php';
require_once dirname(__DIR__) . '/src/Repository/ProductoRepository.php';
require_once dirname(__DIR__) . '/src/Repository/VentaRepository.php';
require_once dirname(__DIR__) . '/src/Service/ProductoService.php';
require_once dirname(__DIR__) . '/src/Service/VentaService.php';
require_once dirname(__DIR__) . '/src/Controller/Csrf.php';
require_once dirname(__DIR__) . '/src/Controller/ProductoController.php';
require_once dirname(__DIR__) . '/src/Controller/VentaController.php';

function createApplication(): array
{
    $pdo = createDatabaseConnection();
    $productoRepository = new ProductoRepository($pdo);
    $ventaRepository = new VentaRepository($pdo);
    $productoService = new ProductoService($productoRepository);
    $ventaService = new VentaService($pdo, $productoRepository, $ventaRepository);

    return [
        'productos' => new ProductoController($productoService),
        'ventas' => new VentaController($ventaService, $productoService),
    ];
}
