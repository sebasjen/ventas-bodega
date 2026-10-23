<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Exceptions\RepositoryException;
use App\Exceptions\ValidationException;
use App\Services\ProductoService;

/**
 * Traduce solicitudes HTTP de productos a llamadas del servicio y vistas.
 */
final class ProductoController
{
    /**
     * Recibe el servicio de productos que contiene las reglas de negocio.
     *
     * @param ProductoService $productoService Servicio de registro y consulta de productos.
     */
    public function __construct(
        private readonly ProductoService $productoService,
    ) {
    }

    /**
     * Muestra el inventario actual y el mensaje de éxito de una operación anterior.
     *
     * @return void
     */
    public function index(): void
    {
        try {
            $productos = $this->productoService->listar();
            $error = '';
        } catch (RepositoryException $exception) {
            error_log($exception->getMessage());
            $productos = [];
            $error = 'No se pudo cargar el inventario. Intente nuevamente.';
            http_response_code(500);
        }

        $success = isset($_SESSION['flash_success']) && is_string($_SESSION['flash_success'])
            ? $_SESSION['flash_success']
            : '';
        unset($_SESSION['flash_success']);

        $title = 'Inventario';
        $currentRoute = 'productos';
        require dirname(__DIR__, 2) . '/views/productos/lista.php';
    }

    /**
     * Muestra el formulario vacío para registrar un producto.
     *
     * @return void
     */
    public function crear(): void
    {
        $values = [
            'codigo' => '',
            'nombre' => '',
            'categoria' => '',
            'precio' => '',
            'stock' => '0',
            'stock_minimo' => '0',
        ];

        $this->renderCreateForm($values, [], '');
    }

    /**
     * Valida el token CSRF y delega el registro de producto al servicio.
     *
     * @return void
     */
    public function guardar(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $values = $this->readProductValues();
            $this->renderCreateForm(
                $values,
                [],
                'La solicitud venció o no es válida. Recarga el formulario e inténtalo nuevamente.',
            );

            return;
        }

        $values = $this->readProductValues();
        $fieldErrors = [];

        foreach (['codigo', 'nombre', 'categoria', 'precio', 'stock', 'stock_minimo'] as $field) {
            if ($values[$field] === '') {
                $fieldErrors[$field] = 'Este campo es obligatorio.';
            }
        }

        if ($fieldErrors !== []) {
            http_response_code(400);
            $this->renderCreateForm($values, $fieldErrors, 'Completa todos los campos obligatorios.');

            return;
        }

        $precio = $this->parsePrice($values['precio']);
        if ($precio === null) {
            $fieldErrors['precio'] = 'Ingresa un precio válido con hasta dos decimales.';
            http_response_code(400);
            $this->renderCreateForm($values, $fieldErrors, 'Revisa los campos indicados.');

            return;
        }

        $stock = filter_var($values['stock'], FILTER_VALIDATE_INT);
        $stockMinimo = filter_var($values['stock_minimo'], FILTER_VALIDATE_INT);
        if ($stock === false) {
            $fieldErrors['stock'] = 'Ingresa un número entero válido.';
        }
        if ($stockMinimo === false) {
            $fieldErrors['stock_minimo'] = 'Ingresa un número entero válido.';
        }
        if ($fieldErrors !== []) {
            http_response_code(400);
            $this->renderCreateForm($values, $fieldErrors, 'Revisa los campos indicados.');

            return;
        }

        try {
            $this->productoService->registrar(
                $values['codigo'],
                $values['nombre'],
                $values['categoria'],
                $precio,
                $stock,
                $stockMinimo,
            );

            $_SESSION['flash_success'] = 'Producto registrado';
            $this->redirect('?r=productos');
        } catch (ValidationException $exception) {
            http_response_code(400);
            $this->renderCreateForm($values, [], $exception->getMessage());
        } catch (RepositoryException $exception) {
            error_log($exception->getMessage());
            http_response_code(500);
            $this->renderCreateForm(
                $values,
                [],
                'No se pudo registrar el producto. Intente nuevamente.',
            );
        }
    }

    /**
     * Lee solo valores escalares del formulario y los normaliza como texto.
     *
     * @return array{codigo: string, nombre: string, categoria: string, precio: string, stock: string, stock_minimo: string}
     */
    private function readProductValues(): array
    {
        $values = [];
        foreach (['codigo', 'nombre', 'categoria', 'precio', 'stock', 'stock_minimo'] as $field) {
            $value = $_POST[$field] ?? '';
            $values[$field] = is_string($value) ? trim($value) : '';
        }

        return $values;
    }

    /**
     * Convierte un precio decimal escrito con punto en un número.
     *
     * @param string $value Precio recibido del formulario.
     *
     * @return float|null Precio convertido o null si el formato no es válido.
     */
    private function parsePrice(string $value): ?float
    {
        if (preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value) !== 1) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Renderiza el formulario conservando valores y errores de validación.
     *
     * @param array{codigo: string, nombre: string, categoria: string, precio: string, stock: string, stock_minimo: string} $values Valores escritos por el usuario.
     * @param array<string, string> $fieldErrors Errores asociados a campos individuales.
     * @param string $error Mensaje general para presentar en el formulario.
     *
     * @return void
     */
    private function renderCreateForm(array $values, array $fieldErrors, string $error): void
    {
        $csrfToken = Csrf::token();
        $title = 'Registrar producto';
        $currentRoute = 'productos';
        require dirname(__DIR__, 2) . '/views/productos/crear.php';
    }

    /**
     * Redirige con POST-Redirect-GET para evitar repetir el alta al recargar.
     *
     * @param string $location Ruta local de destino.
     *
     * @return never La ejecución termina después de enviar la redirección.
     */
    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
