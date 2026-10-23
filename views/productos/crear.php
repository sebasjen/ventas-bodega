<?php
declare(strict_types=1);

namespace App\Views;

/**
 * Presenta el formulario de alta y los errores preparados por el Controller.
 *
 * @var array{codigo: string, nombre: string, categoria: string, precio: string, stock: string, stock_minimo: string} $values Valores actuales del formulario.
 * @var array<string, string> $fieldErrors Errores asociados a campos individuales.
 * @var string $error Mensaje general para presentar, o cadena vacía.
 * @var string $csrfToken Token de sesión para validar el envío del formulario.
 * @var string $title Título de la página.
 * @var string $currentRoute Ruta lógica para la navegación.
 */
require_once dirname(__DIR__) . '/helpers.php';
require dirname(__DIR__) . '/layout/header.php';

$fields = [
    'codigo' => ['Código', 'text', 'p. ej. BEB-001', null, null],
    'nombre' => ['Nombre', 'text', 'Nombre del producto', null, null],
    'categoria' => ['Categoría', 'text', 'p. ej. Bebidas', null, null],
    'precio' => ['Precio de venta', 'number', '0.00', '0.01', '0.01'],
    'stock' => ['Stock actual', 'number', '0', '0', '1'],
    'stock_minimo' => ['Stock mínimo', 'number', '0', '0', '1'],
];
?>
<?php if ($error !== ''): ?>
    <div class="notice error" role="alert"><?= Html::escape($error) ?></div>
<?php endif; ?>

<form class="form-card" method="post" action="index.php?r=productos/guardar">
    <input type="hidden" name="csrf_token" value="<?= Html::escape($csrfToken) ?>">
    <div class="form-grid">
        <?php foreach ($fields as $field => [$label, $type, $placeholder, $min, $step]): ?>
            <?php $hasError = isset($fieldErrors[$field]); ?>
            <div class="field">
                <label for="<?= Html::escape($field) ?>">
                    <?= Html::escape($label) ?> <span aria-hidden="true">*</span>
                </label>
                <input
                    id="<?= Html::escape($field) ?>"
                    name="<?= Html::escape($field) ?>"
                    type="<?= Html::escape($type) ?>"
                    value="<?= Html::escape($values[$field]) ?>"
                    placeholder="<?= Html::escape($placeholder) ?>"
                    <?= $min !== null ? 'min="' . Html::escape($min) . '"' : '' ?>
                    <?= $step !== null ? 'step="' . Html::escape($step) . '"' : '' ?>
                    <?= $hasError ? 'aria-invalid="true"' : '' ?>
                    <?= $hasError ? 'aria-describedby="' . Html::escape($field . '-error') . '"' : '' ?>
                    required
                >
                <?php if ($hasError): ?>
                    <small id="<?= Html::escape($field . '-error') ?>" class="field-error">
                        <?= Html::escape($fieldErrors[$field]) ?>
                    </small>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="form-hint">El precio debe ser mayor a cero. Los valores de stock no pueden ser negativos.</p>
    <div class="form-actions">
        <a class="button secondary" href="index.php?r=productos">Cancelar</a>
        <button class="button" type="submit">Guardar producto</button>
    </div>
</form>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
