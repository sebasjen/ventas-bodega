<?php
declare(strict_types=1);

use App\Controllers\ProductoController;
use App\Controllers\VentaController;
use App\Database;
use App\Repositories\ProductoRepository;
use App\Repositories\VentaRepository;
use App\Services\ProductoService;
use App\Services\VentaService;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

header('Content-Type: text/html; charset=UTF-8');

spl_autoload_register(
    /**
     * Carga clases del namespace App desde el directorio app/.
     *
     * @param string $class Nombre completo de la clase solicitada.
     *
     * @return void
     */
    static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $classPath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass);
        $filePath = dirname(__DIR__)
            . DIRECTORY_SEPARATOR
            . 'app'
            . DIRECTORY_SEPARATOR
            . $classPath
            . '.php';

        if (is_file($filePath)) {
            require_once $filePath;
        }
    },
);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/views/helpers.php';

try {
    $pdo = Database::connect();
    $productoRepository = new ProductoRepository($pdo);
    $ventaRepository = new VentaRepository($pdo);
    $productoService = new ProductoService($productoRepository);
    $ventaService = new VentaService($pdo, $productoRepository, $ventaRepository);
    $productoController = new ProductoController($productoService);
    $ventaController = new VentaController($ventaService, $productoService);
} catch (\Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head>';
    echo '<body><main><h1>No se pudo iniciar la aplicación</h1>';
    echo '<p>Verifica la configuración de la base de datos e inténtalo nuevamente.</p></main></body></html>';
    exit;
}

$route = isset($_GET['r']) && is_string($_GET['r']) ? $_GET['r'] : 'productos';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($method . ' ' . $route) {
        case 'GET productos':
            $productoController->index();
            break;
        case 'GET productos/crear':
            $productoController->crear();
            break;
        case 'POST productos/guardar':
            $productoController->guardar();
            break;
        case 'GET ventas/registrar':
            $ventaController->registrar();
            break;
        case 'POST ventas/guardar':
            $ventaController->guardar();
            break;
        case 'GET ventas/reporte':
            $ventaController->reporte();
            break;
        default:
            http_response_code(404);
            echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>No encontrado</title></head>';
            echo '<body><main><h1>Página no encontrada</h1>';
            echo '<p>La dirección solicitada no existe.</p>';
            echo '<p><a href="index.php?r=productos">Volver al inventario</a></p></main></body></html>';
    }
} catch (\Throwable $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error</title></head>';
    echo '<body><main><h1>Ocurrió un error</h1>';
    echo '<p>Intenta nuevamente. Si el problema continúa, contacta al administrador.</p></main></body></html>';
}
