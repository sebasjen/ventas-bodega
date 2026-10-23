<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RepositoryException;
use App\Exceptions\ValidationException;
use App\Exceptions\VentaException;
use App\Models\Venta;
use App\Services\ProductoService;
use App\Services\VentaService;

/**
 * Traduce solicitudes HTTP de ventas a llamadas de servicio y vistas.
 */
final class VentaController
{
    /**
     * Recibe los servicios necesarios para operar ventas y consultar productos.
     *
     * @param VentaService $ventaService Servicio de registro y reporte de ventas.
     * @param ProductoService $productoService Servicio que lista productos disponibles.
     */
    public function __construct(
        private readonly VentaService $ventaService,
        private readonly ProductoService $productoService,
    ) {
    }

    /**
     * Muestra el formulario de venta y los productos disponibles.
     *
     * @return void
     */
    public function registrar(): void
    {
        $success = isset($_SESSION['venta_success']) && is_array($_SESSION['venta_success'])
            ? $_SESSION['venta_success']
            : null;
        unset($_SESSION['venta_success']);

        $this->renderSaleForm('', $success);
    }

    /**
     * Verifica CSRF, convierte la entrada HTTP y delega el registro al servicio.
     *
     * @return void
     */
    public function guardar(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $this->renderSaleForm(
                'La solicitud venció o no es válida. Recarga el formulario e inténtalo nuevamente.',
            );

            return;
        }

        $productoIdInput = $_POST['producto_id'] ?? null;
        $cantidadInput = $_POST['cantidad'] ?? null;
        $productoId = is_string($productoIdInput)
            ? filter_var($productoIdInput, FILTER_VALIDATE_INT)
            : false;
        $cantidad = is_string($cantidadInput)
            ? filter_var($cantidadInput, FILTER_VALIDATE_INT)
            : false;

        if ($productoId === false || $cantidad === false) {
            http_response_code(400);
            $this->renderSaleForm('Selecciona un producto y una cantidad válida.');

            return;
        }

        try {
            $venta = $this->ventaService->registrar($productoId, $cantidad);
            $_SESSION['venta_success'] = $this->saleSummary($venta);
            $this->redirect('?r=ventas/registrar');
        } catch (ValidationException $exception) {
            http_response_code(400);
            $this->renderSaleForm($exception->getMessage());
        } catch (VentaException $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            $this->renderSaleForm('Error al procesar la venta. Intente nuevamente');
        }
    }

    /**
     * Muestra las ventas y el total acumulado del día del servidor.
     *
     * @return void
     */
    public function reporte(): void
    {
        try {
            $reporte = $this->ventaService->reporteHoy();
            $error = '';
        } catch (RepositoryException $exception) {
            error_log($exception->getMessage());
            $reporte = ['ventas' => [], 'total' => 0.0];
            $error = 'No se pudo cargar el reporte. Intente nuevamente.';
            http_response_code(500);
        }

        $title = 'Reporte de ventas del día';
        $currentRoute = 'ventas/reporte';
        require dirname(__DIR__, 2) . '/views/ventas/reporte.php';
    }

    /**
     * Carga productos y renderiza el formulario junto con el mensaje recibido.
     *
     * @param string $error Mensaje seguro para mostrar en el formulario.
     * @param array<string, int|float|string>|null $success Resumen de venta confirmada, si existe.
     *
     * @return void
     */
    private function renderSaleForm(string $error, ?array $success = null): void
    {
        try {
            $productos = $this->productoService->listar();
        } catch (RepositoryException $exception) {
            error_log($exception->getMessage());
            $productos = [];
            $error = 'No se pudieron cargar los productos. Intente nuevamente.';
            http_response_code(500);
        }

        $csrfToken = Csrf::token();
        $title = 'Registrar venta';
        $currentRoute = 'ventas/registrar';
        require dirname(__DIR__, 2) . '/views/ventas/registrar.php';
    }

    /**
     * Prepara los datos de producto y venta para el mensaje de éxito.
     *
     * @param Venta $venta Venta confirmada por el servicio.
     *
     * @return array{codigo: string, nombre: string, cantidad: int, precio_unit: float, total: float, vendido_en: string}
     */
    private function saleSummary(Venta $venta): array
    {
        return [
            'codigo' => $venta->codigoProducto,
            'nombre' => $venta->nombreProducto,
            'cantidad' => $venta->cantidad,
            'precio_unit' => $venta->precioUnit,
            'total' => $venta->total,
            'vendido_en' => $venta->vendidoEn,
        ];
    }

    /**
     * Redirige con POST-Redirect-GET para evitar reenvíos al recargar.
     *
     * @param string $location Ruta local de destino.
     *
     * @return never La ejecución finaliza después de enviar la redirección.
     */
    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
