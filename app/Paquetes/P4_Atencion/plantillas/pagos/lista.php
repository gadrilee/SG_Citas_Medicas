<?php
/**
 * CU20 · Caja de recepción: intentos de pago, verificación de pendientes (A2) y devoluciones (A3).
 * @var array $resultado     Epago::buscar (paginado)
 * @var array $resumen       Epago::resumen
 * @var array $devoluciones  Epago::devolucionesPendientes
 * @var array $filtros       buscar, estado
 */
$estados = ['pendiente', 'aprobado', 'rechazado', 'cancelado', 'reembolsado'];
?>
<div class="mb-6">
  <p class="sobretitulo">CU20 · Caja y conciliación</p>
  <h1 class="titulo-pagina">Pagos</h1>
  <p class="texto">Cada intento de pago con su estado verificado. Para cobrar una cita, ábrela y presiona «Pagar».</p>
</div>

<section class="grid gap-4 sm:grid-cols-3 mb-6" aria-label="Resumen">
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="wallet"></i></span><p class="numero-kpi mt-3"><?= e(bs($resumen['cobrado_hoy'] ?? 0)) ?></p><p class="text-sm text-muted">Cobrado hoy (<?= (int) ($resumen['aprobados_hoy'] ?? 0) ?> pagos)</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="hourglass"></i></span><p class="numero-kpi mt-3"><?= (int) ($resumen['pendientes'] ?? 0) ?></p><p class="text-sm text-muted">Pendientes de verificar · <?= e(bs($resumen['pendiente'] ?? 0)) ?></p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="undo-2"></i></span><p class="numero-kpi mt-3"><?= count($devoluciones) ?></p><p class="text-sm text-muted">Devoluciones por realizar</p></div>
</section>

<?php if ($devoluciones): ?>
  <section class="glass-card mb-6" aria-labelledby="t-devoluciones">
    <h2 id="t-devoluciones" class="font-semibold flex items-center gap-2 mb-1"><?= icono('undo-2', 'w-5 h-5 texto-acento') ?>Devoluciones por realizar</h2>
    <p class="texto text-sm mb-4">Citas canceladas que tenían un pago aprobado (A3). La devolución se confirma con la pasarela.</p>
    <ul class="grid gap-3">
      <?php foreach ($devoluciones as $pago): ?>
        <li class="flex flex-col sm:flex-row sm:items-center gap-3 text-sm">
          <div class="flex-1"><p class="font-medium"><?= e($pago['paciente']) ?> · <span class="font-mono"><?= e($pago['cita']) ?></span></p><p class="text-subtle">Cita del <?= e(fecha_corta($pago['cita_inicio'])) ?> · <?= e($pago['pasarela']) ?> · <span class="font-mono" title="<?= e($pago['referencia_externa']) ?>"><?= e(mb_strimwidth((string) $pago['referencia_externa'], 0, 22, '…')) ?></span></p></div>
          <span class="font-semibold numeros"><?= e(bs($pago['importe'])) ?></span>
          <button type="button" class="btn-peligro btn-sm" data-abrir-modal="modal-devolver" data-accion="<?= e(url('/pagos/' . $pago['id'] . '/devolver')) ?>" data-nombre="<?= e(bs($pago['importe']) . ' de ' . $pago['cita']) ?>"><?= icono('undo-2') ?>Devolver</button>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
<?php endif; ?>

<form method="get" action="<?= e(url('/pagos')) ?>" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-[1fr_200px_auto] items-end" role="search" aria-label="Buscar pagos">
  <div>
    <label for="p-buscar" class="etiqueta">Cita, paciente o referencia</label>
    <div class="campo-con-icono"><i data-lucide="search"></i><input id="p-buscar" name="buscar" type="search" class="campo" placeholder="CM-0011, María López, VP-…" value="<?= e($filtros['buscar']) ?>"></div>
  </div>
  <div>
    <label for="p-estado" class="etiqueta">Estado</label>
    <select id="p-estado" name="estado" class="campo">
      <option value="">Todos</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= e($estado) ?>"<?= $filtros['estado'] === $estado ? ' selected' : '' ?>><?= e(texto_estado('pago', $estado)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2">
    <a href="<?= e(url('/pagos')) ?>" class="btn-fantasma"><?= icono('x') ?>Limpiar</a>
    <button type="submit" class="btn-primario"><?= icono('filter') ?>Buscar</button>
  </div>
</form>

<section class="glass tabla-contenedor" aria-label="Pagos">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'wallet', 'titulo' => 'No hay pagos para mostrar', 'texto' => 'Prueba con otro estado o término de búsqueda.']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr>
        <th scope="col">Fecha</th><th scope="col">Paciente</th><th scope="col">Cita</th><th scope="col">Pasarela · referencia</th>
        <th scope="col" class="text-right">Importe</th><th scope="col">Estado</th>
        <th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th>
      </tr></thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $pago): ?>
          <tr>
            <td class="numeros whitespace-nowrap"><?= e(fecha_hora($pago['creado_en'])) ?></td>
            <td><span class="block text-fg font-medium"><?= e($pago['paciente']) ?></span><span class="text-xs font-mono"><?= e($pago['codigo_paciente']) ?></span></td>
            <td class="font-mono principal"><?= e($pago['cita']) ?></td>
            <td><span class="block"><?= e($pago['pasarela']) ?></span><span class="text-xs font-mono text-subtle" title="<?= e($pago['referencia_externa']) ?>"><?= e(mb_strimwidth((string) ($pago['referencia_externa'] ?: '—'), 0, 22, '…')) ?></span></td>
            <td class="numeros text-right font-medium"><?= e(bs($pago['importe'])) ?></td>
            <td><?= parcial('badge_estado', ['tipo' => 'pago', 'estado' => $pago['estado']]) ?></td>
            <td class="text-right"><div class="flex justify-end gap-1">
              <?php if ($pago['estado'] === 'pendiente'): ?>
                <form method="post" action="<?= e(url('/pagos/' . $pago['id'] . '/verificar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-icono sm" aria-label="Verificar el pago de <?= e($pago['cita']) ?>" title="Verificar con la pasarela"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button></form>
                <form method="post" action="<?= e(url('/pagos/' . $pago['id'] . '/anular')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-icono sm" aria-label="Anular el intento de <?= e($pago['cita']) ?>" title="Anular intento"><i data-lucide="x" class="w-4 h-4"></i></button></form>
              <?php endif; ?>
              <a href="<?= e(url('/pagos/' . $pago['id'])) ?>" class="btn-icono sm" aria-label="Comprobante del pago de <?= e($pago['cita']) ?>"><i data-lucide="receipt-text" class="w-4 h-4"></i></a>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'pagos']) ?>
</section>

<?= parcial('modal_confirmar', [
    'id'     => 'modal-devolver',
    'titulo' => '¿Devolver {nombre}?',
    'texto'  => 'Se solicitará a la pasarela la devolución total. Cuando la confirme, el pago quedará como Reembolsado.',
    'boton'  => 'Solicitar devolución',
    'icono'  => 'undo-2',
]) ?>
