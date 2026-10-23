<?php
declare(strict_types=1);

namespace App\Views;

use App\Models\Venta;
use DateTimeImmutable;

/**
 * Presenta las ventas del día y el total acumulado recibido del Controller.
 *
 * @var array{ventas: list<Venta>, total: float} $reporte Datos agregados por VentaService.
 * @var string $error Mensaje de error seguro, o cadena vacía.
 * @var string $title Título de la página.
 * @var string $currentRoute Ruta lógica para la navegación.
 */
require_once dirname(__DIR__) . '/helpers.php';
require dirname(__DIR__) . '/layout/header.php';

$fechaActual = (new DateTimeImmutable())->format('d/m/Y');
?>
<?php if ($error !== ''): ?>
    <div class="notice error" role="alert"><?= Html::escape($error) ?></div>
<?php endif; ?>

<p class="report-date">Ventas del día <?= Html::escape($fechaActual) ?></p>

<?php if ($reporte['ventas'] === [] && $error === ''): ?>
    <section class="empty-state">
        <h2>Sin ventas registradas hoy</h2>
        <p>Total acumulado: <strong>$0.00</strong></p>
    </section>
<?php elseif ($reporte['ventas'] !== []): ?>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th scope="col">Hora</th>
                <th scope="col">Código</th>
                <th scope="col">Producto</th>
                <th scope="col" class="numeric">Cantidad</th>
                <th scope="col" class="numeric">Precio unitario</th>
                <th scope="col" class="numeric">Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($reporte['ventas'] as $venta): ?>
                <?php $hora = (new DateTimeImmutable($venta->vendidoEn))->format('H:i:s'); ?>
                <tr>
                    <td><?= Html::escape($hora) ?></td>
                    <td><?= Html::escape($venta->codigoProducto) ?></td>
                    <td><?= Html::escape($venta->nombreProducto) ?></td>
                    <td class="numeric"><?= Html::escape($venta->cantidad) ?></td>
                    <td class="numeric">$<?= Html::escape(number_format($venta->precioUnit, 2, '.', ',')) ?></td>
                    <td class="numeric">$<?= Html::escape(number_format($venta->total, 2, '.', ',')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th scope="row" colspan="5">Total acumulado</th>
                <th class="numeric">$<?= Html::escape(number_format($reporte['total'], 2, '.', ',')) ?></th>
            </tr>
            </tfoot>
        </table>
    </div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/layout/footer.php'; ?>
