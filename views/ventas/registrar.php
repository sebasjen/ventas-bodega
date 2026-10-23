<?php
declare(strict_types=1);

namespace App\Views;

use App\Models\Producto;

/**
 * Presenta el formulario de venta y el resumen de una operación confirmada.
 *
 * @var list<Producto> $productos Productos que el Controller obtuvo del servicio.
 * @var string $error Mensaje general para mostrar, o cadena vacía.
 * @var string $csrfToken Token de sesión que debe acompañar el POST.
 * @var array{codigo: string, nombre: string, cantidad: int, precio_unit: float, total: float, vendido_en: string}|null $success Resumen de venta confirmada.
 * @var string $title Título de la página.
 * @var string $currentRoute Ruta lógica para la navegación.
 */
require_once dirname(__DIR__) . '/helpers.php';
require dirname(__DIR__) . '/layout/header.php';
?>
<?php if ($success !== null): ?>
    <section class="notice success" role="status">
        <h2>Venta registrada</h2>
        <dl class="sale-summary">
            <div>
                <dt>Producto</dt>
                <dd><?= Html::escape($success['nombre']) ?> (<?= Html::escape($success['codigo']) ?>)</dd>
            </div>
            <div>
                <dt>Cantidad</dt>
                <dd><?= Html::escape($success['cantidad']) ?></dd>
            </div>
            <div>
                <dt>Precio unitario</dt>
                <dd>$<?= Html::escape(number_format($success['precio_unit'], 2, '.', ',')) ?></dd>
            </div>
            <div>
                <dt>Total</dt>
                <dd>$<?= Html::escape(number_format($success['total'], 2, '.', ',')) ?></dd>
            </div>
            <div>
                <dt>Hora</dt>
                <dd><?= Html::escape($success['vendido_en']) ?></dd>
            </div>
        </dl>
    </section>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="notice error" role="alert"><?= Html::escape($error) ?></div>
<?php endif; ?>

<?php if ($productos === [] && $error === ''): ?>
    <section class="empty-state">
        <h2>No hay productos disponibles</h2>
        <p>Registra productos con stock antes de procesar una venta.</p>
        <a class="button" href="index.php?r=productos/crear">Registrar producto</a>
    </section>
<?php elseif ($productos !== []): ?>
    <form class="form-card compact" method="post" action="index.php?r=ventas/guardar">
        <input type="hidden" name="csrf_token" value="<?= Html::escape($csrfToken) ?>">
        <div class="field">
            <label for="producto_id">Producto <span aria-hidden="true">*</span></label>
            <select id="producto_id" name="producto_id" required>
                <option value="">Selecciona un producto</option>
                <?php foreach ($productos as $producto): ?>
                    <option
                        value="<?= Html::escape($producto->id) ?>"
                        <?= $producto->stock === 0 ? 'disabled' : '' ?>
                    >
                        <?= Html::escape($producto->codigo) ?>
                        · <?= Html::escape($producto->nombre) ?>
                        · Stock: <?= Html::escape($producto->stock) ?>
                        · $<?= Html::escape(number_format($producto->precio, 2, '.', ',')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="cantidad">Cantidad <span aria-hidden="true">*</span></label>
            <input id="cantidad" name="cantidad" type="number" min="1" step="1" value="1" required>
        </div>
        <button class="button" type="submit">Registrar venta</button>
    </form>
<?php endif; ?>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
