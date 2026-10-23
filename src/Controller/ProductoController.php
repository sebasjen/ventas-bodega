<?php
declare(strict_types=1);

final class ProductoController
{
    public function __construct(private readonly ProductoService $productoService)
    {
    }

    public function index(): void
    {
        try {
            $productos = $this->productoService->listar();
            $error = '';
        } catch (RepositoryException $exception) {
            $productos = [];
            $error = 'No se pudo cargar el inventario. Intente nuevamente.';
        }

        $success = isset($_SESSION['flash_success']) && is_string($_SESSION['flash_success'])
            ? $_SESSION['flash_success']
            : '';
        unset($_SESSION['flash_success']);
        $title = 'Inventario';
        $currentRoute = 'productos';
        require dirname(__DIR__, 2) . '/views/productos/lista.php';
    }

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
        $fieldErrors = [];
        $error = '';
        $csrfToken = Csrf::token();
        $title = 'Registrar producto';
        $currentRoute = 'productos';
        require dirname(__DIR__, 2) . '/views/productos/crear.php';
    }

    public function guardar(): void
    {
        $values = [];
        foreach (['codigo', 'nombre', 'categoria', 'precio', 'stock', 'stock_minimo'] as $field) {
            $values[$field] = isset($_POST[$field]) && is_string($_POST[$field])
                ? trim($_POST[$field])
                : '';
        }
        $fieldErrors = [];
        $error = '';

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $error = 'La solicitud venció o no es válida. Recarga el formulario e inténtalo nuevamente.';
        } else {
            foreach (['codigo', 'nombre', 'categoria', 'precio', 'stock', 'stock_minimo'] as $field) {
                if ($values[$field] === '') {
                    $fieldErrors[$field] = 'Este campo es obligatorio.';
                }
            }

            if ($fieldErrors !== []) {
                $error = 'Completa todos los campos obligatorios.';
            } else {
                $precio = $this->parsePrecio($values['precio']);
                $stock = filter_var($values['stock'], FILTER_VALIDATE_INT);
                $stockMinimo = filter_var($values['stock_minimo'], FILTER_VALIDATE_INT);

                if ($precio === null) {
                    $fieldErrors['precio'] = 'Ingresa un precio válido con hasta dos decimales.';
                    $error = 'El precio debe ser mayor a cero';
                } elseif ($stock === false || $stockMinimo === false) {
                    $error = 'El stock debe ser un número entero válido.';
                } else {
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
                        $error = $exception->getMessage();
                    } catch (RepositoryException $exception) {
                        $error = 'No se pudo registrar el producto. Intente nuevamente.';
                    }
                }
            }
        }

        $csrfToken = Csrf::token();
        $title = 'Registrar producto';
        $currentRoute = 'productos';
        require dirname(__DIR__, 2) . '/views/productos/crear.php';
    }

    private function parsePrecio(string $value): ?float
    {
        if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $value)) {
            return null;
        }

        return (float) $value;
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }
}
