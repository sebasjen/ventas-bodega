<?php
declare(strict_types=1);

namespace App\Views;

use App\Models\Producto;

/**
 * Presenta el inventario, el estado vacío y las alertas de stock mínimo.
 *
 * @var list<Producto> $productos Productos preparados por el Controller.
 * @var string $success Mensaje de confirmación, o cadena vacía.
 * @var string $error Mensaje de error seguro, o cadena vacía.
 * @var string $title Título de la página.
 * @var string $currentRoute Ruta lógica para marcar la navegación activa.
 */
require_once dirname(__DIR__) . '/helpers.php';
require dirname(__DIR__) . '/layout/header.php';
?>
<?php if ($success !== ''): ?>
    <div class="notice success" role="status"><?= Html::escape($success) ?></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="notice error" role="alert"><?= Html::escape($error) ?></div>
<?php endif; ?>

<div class="page-actions">
    <p>Consulta existencias y productos que requieren reposición.</p>
    <a class="button" href="index.php?r=productos/crear">Registrar producto</a>
</div>

<?php if ($productos === [] && $error === ''): ?>
    <section class="empty-state">
        <h2>No hay productos registrados</h2>
        <p>Registra el primer producto para comenzar a controlar el inventario.</p>
    </section>
<?php elseif ($productos !== []): ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th scope="col">Código</th>
                <th scope="col">Producto</th>
                <th scope="col">Categoría</th>
                <th scope="col" class="numeric">Precio</th>
                <th scope="col" class="numeric">Stock</th>
                <th scope="col" class="numeric">Stock mínimo</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $producto): ?>
                <?php $stockBajo = $producto->stock <= $producto->stockMinimo; ?>
                <tr class="<?= $stockBajo ? 'low-stock' : '' ?>">
                    <td><?= Html::escape($producto->codigo) ?></td>
                    <td>
                        <?= Html::escape($producto->nombre) ?>
                        <?php if ($stockBajo): ?>
                            <span class="badge">Reponer</span>
                        <?php endif; ?>
                    </td>
                    <td><?= Html::escape($producto->categoria) ?></td>
                    <td class="numeric">$<?= Html::escape(number_format($producto->precio, 2, '.', ',')) ?></td>
                    <td class="numeric"><?= Html::escape($producto->stock) ?></td>
                    <td class="numeric"><?= Html::escape($producto->stockMinimo) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
