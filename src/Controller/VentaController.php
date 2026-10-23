<?php
declare(strict_types=1);

final class VentaController
{
    public function __construct(
        private readonly VentaService $ventaService,
        private readonly ProductoService $productoService,
    ) {
    }

    public function registrar(): void
    {
        $this->renderFormulario('');
    }

    public function guardar(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $this->renderFormulario('La solicitud venció o no es válida. Recarga el formulario e inténtalo nuevamente.');
            return;
        }

        $productoId = isset($_POST['producto_id']) && is_string($_POST['producto_id'])
            ? filter_var($_POST['producto_id'], FILTER_VALIDATE_INT)
            : false;
        $cantidad = isset($_POST['cantidad']) && is_string($_POST['cantidad'])
            ? filter_var($_POST['cantidad'], FILTER_VALIDATE_INT)
            : false;

        if ($productoId === false || $cantidad === false) {
            $this->renderFormulario('Selecciona un producto y una cantidad válida.');
            return;
        }

        try {
            $venta = $this->ventaService->registrar($productoId, $cantidad);
            $_SESSION['venta_success'] = [
                'codigo' => $venta->codigoProducto,
                'nombre' => $venta->nombreProducto,
                'cantidad' => $venta->cantidad,
                'precio_unit' => $venta->precioUnit,
                'total' => $venta->total,
                'vendido_en' => $venta->vendidoEn,
            ];
            $this->redirect('?r=ventas/registrar');
        } catch (StockInsuficienteException | ValidationException $exception) {
            $this->renderFormulario($exception->getMessage());
        } catch (RuntimeException $exception) {
            $this->renderFormulario('Error al procesar la venta. Intente nuevamente');
        }
    }

    public function reporte(): void
    {
        try {
            $reporte = $this->ventaService->reporteHoy();
            $error = '';
        } catch (RepositoryException $exception) {
            $reporte = ['ventas' => [], 'total' => 0.0];
            $error = 'No se pudo cargar el reporte. Intente nuevamente.';
        }

        $title = 'Reporte de ventas del día';
        $currentRoute = 'ventas/reporte';
        require dirname(__DIR__, 2) . '/views/ventas/reporte.php';
    }

    private function renderFormulario(string $error): void
    {
        try {
            $productos = $this->productoService->listar();
        } catch (RepositoryException $exception) {
            $productos = [];
            $error = 'No se pudieron cargar los productos. Intente nuevamente.';
        }

        $csrfToken = Csrf::token();
        $success = isset($_SESSION['venta_success']) && is_array($_SESSION['venta_success'])
            ? $_SESSION['venta_success']
            : null;
        unset($_SESSION['venta_success']);
        $title = 'Registrar venta';
        $currentRoute = 'ventas/registrar';
        require dirname(__DIR__, 2) . '/views/ventas/registrar.php';
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
