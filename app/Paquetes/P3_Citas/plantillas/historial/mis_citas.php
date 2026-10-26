<?php
/**
 * CU14 · «Mis citas» del paciente, con cancelación (CU11) y reprogramación (CU10) de las vigentes.
 * @var array $citas            Ecita_medica::historialDe
 * @var array $eventos          [cita_id => eventos]
 * @var array $conPagoAprobado  ids de citas con pago aprobado (A1 de CU11)
 * @var array $filtros
 * @var array $estados
 * @var array $erroresFiltro
 */
$vigente = static fn (array $cita): bool => in_array((int) $cita['estado_cita_id'], [1, 2], true) && strtotime($cita['inicio']) > time();
$proximas = array_reverse(array_values(array_filter($citas, $vigente)));
$anteriores = array_values(array_filter($citas, static fn (array $cita): bool => !$vigente($cita)));
$accion = '/mis-citas';
$tarjeta = static function (array $cita, array $eventos, bool $pagada, bool $esVigente): string {
    $foto = foto_medico($cita['codigo_medico']);
    ob_start(); ?>
    <article class="glass-card flex flex-col gap-4">
      <div class="flex items-start gap-3">
        <?php if ($foto): ?>
          <span class="avatar avatar-md"><img src="<?= e(recurso($foto)) ?>" alt=""></span>
        <?php else: ?>
          <span class="avatar avatar-md" aria-hidden="true"><?= e(iniciales(preg_replace('/^Dra?\. /', '', $cita['medico']))) ?></span>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
          <p class="font-semibold leading-snug"><?= e($cita['medico']) ?></p>
          <p class="text-sm text-muted"><?= e($cita['especialidad']) ?></p>
        </div>
        <?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?>
      </div>
      <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
        <div class="flex items-center gap-2"><i data-lucide="calendar" class="w-4 h-4 texto-acento shrink-0"></i><dt class="sr-only">Fecha</dt><dd class="numeros"><?= e(fecha_corta($cita['inicio'])) ?></dd></div>
        <div class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 texto-acento shrink-0"></i><dt class="sr-only">Hora</dt><dd class="numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></dd></div>
        <div class="flex items-center gap-2"><i data-lucide="hash" class="w-4 h-4 text-subtle shrink-0"></i><dt class="sr-only">Código</dt><dd class="font-mono text-muted"><?= e($cita['codigo']) ?></dd></div>
        <div class="flex items-center gap-2"><i data-lucide="building-2" class="w-4 h-4 text-subtle shrink-0"></i><dt class="sr-only">Modalidad</dt><dd class="text-muted"><?= e(ucfirst($cita['modalidad'])) ?></dd></div>
      </dl>
      <div class="flex items-center justify-between text-sm rounded-2xl px-3 py-2 fondo-acento-suave">
        <span class="text-muted">Importe · <span class="font-semibold text-fg numeros"><?= e(bs($cita['importe_acordado'])) ?></span></span>
        <?php if ($pagada): ?><span class="insignia pago-aprobado">Pagada</span><?php endif; ?>
      </div>
      <?php if ($eventos): ?>
        <details class="acordeon">
          <summary class="flex items-center gap-1 text-sm font-medium texto-acento"><?= icono('history') ?>Ver historial (<?= count($eventos) ?>)<i data-lucide="chevron-down" class="w-4 h-4 girar"></i></summary>
          <div class="mt-4"><?= parcial('eventos_cita', ['eventos' => $eventos]) ?></div>
        </details>
      <?php endif; ?>
      <div class="flex flex-wrap items-center gap-2 pt-1">
        <?php if ($esVigente && puede('citas.reprogramar')): ?>
          <a href="<?= e(url('/citas/' . $cita['id'] . '/reprogramar')) ?>" class="btn-secundario btn-sm"><?= icono('calendar-sync') ?>Reprogramar</a>
        <?php endif; ?>
        <?php if ($esVigente && puede('citas.cancelar')): ?>
          <button type="button" class="btn-peligro btn-sm" data-abrir-modal="<?= $pagada ? 'modal-cancelar-pagada' : 'modal-cancelar-cita' ?>" data-accion="<?= e(url('/citas/' . $cita['id'] . '/cancelar')) ?>" data-nombre="<?= e($cita['codigo']) ?>"><?= icono('calendar-x') ?>Cancelar</button>
        <?php endif; ?>
        <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-fantasma btn-sm"><?= icono('receipt-text') ?>Comprobante</a>
      </div>
    </article>
    <?php return (string) ob_get_clean();
};
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU14 · Tu agenda</p>
    <h1 class="titulo-pagina">Mis citas</h1>
    <p class="texto">Revisa tus próximas atenciones, tu historial y los cambios de cada cita.</p>
  </div>
  <?php if (puede('citas.reservar')): ?>
    <a href="<?= e(url('/citas/nueva')) ?>" class="btn-primario"><?= icono('calendar-plus') ?>Agendar nueva cita</a>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/_filtros.php'; ?>

<?php if (!$citas): ?>
  <div class="glass"><?= parcial('estado_vacio', [
      'icono'  => 'calendar-plus',
      'titulo' => $erroresFiltro ? 'Corrige el rango de fechas' : 'No tienes citas para mostrar',
      'texto'  => $erroresFiltro ? 'La búsqueda no se ejecutó.' : 'Cuando reserves una cita aparecerá aquí con todo su historial.',
      'boton'  => $erroresFiltro ? null : 'Reservar cita',
      'ruta'   => '/citas/nueva',
  ]) ?></div>
<?php else: ?>
  <?php if ($proximas): ?>
    <h2 class="titulo-seccion text-lg font-semibold mb-3">Próximas (<?= count($proximas) ?>)</h2>
    <div class="grid gap-4 md:grid-cols-2 mb-8">
      <?php foreach ($proximas as $cita): ?>
        <?= $tarjeta($cita, $eventos[(int) $cita['id']] ?? [], in_array((int) $cita['id'], $conPagoAprobado, true), true) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if ($anteriores): ?>
    <h2 class="titulo-seccion text-lg font-semibold mb-3">Historial (<?= count($anteriores) ?>)</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach ($anteriores as $cita): ?>
        <?= $tarjeta($cita, $eventos[(int) $cita['id']] ?? [], in_array((int) $cita['id'], $conPagoAprobado, true), false) ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?= parcial('modal_confirmar', [
    'id'     => 'modal-cancelar-cita',
    'titulo' => '¿Cancelar la cita {nombre}?',
    'texto'  => 'La cita se conserva como Cancelada en tu historial y el horario queda libre para otros pacientes.',
    'boton'  => 'Cancelar cita',
    'icono'  => 'calendar-x',
    'motivo' => true,
]) ?>
<?= parcial('modal_confirmar', [
    'id'     => 'modal-cancelar-pagada',
    'titulo' => '¿Cancelar la cita {nombre}?',
    'texto'  => 'La cita se conserva como Cancelada en tu historial y el horario queda libre para otros pacientes.',
    'boton'  => 'Cancelar cita',
    'icono'  => 'calendar-x',
    'motivo' => true,
    'aviso'  => 'Esta cita tiene un pago aprobado: al cancelarla corresponde solicitar la devolución total (CU20).',
]) ?>
