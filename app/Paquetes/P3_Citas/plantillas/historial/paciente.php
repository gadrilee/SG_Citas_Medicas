<?php
/**
 * CU14 · Pasos 2–5: citas del paciente y los eventos de cada una.
 * @var array $paciente  Epaciente::buscarDetalle
 * @var array $citas     Ecita_medica::historialDe
 * @var array $eventos   [cita_id => eventos]
 * @var array $filtros
 * @var array $estados
 * @var array $erroresFiltro
 */
$nombre = $paciente['nombres'] . ' ' . $paciente['apellidos'];
$porEstado = array_count_values(array_column($citas, 'estado'));
$accion = '/historial';
$ocultos = ['paciente_id' => $paciente['id']];
?>
<a href="<?= e(url('/historial')) ?>" class="btn-fantasma btn-sm -ml-3 mb-4"><?= icono('arrow-left') ?>Buscar otro paciente</a>

<div class="glass-card mb-6 flex flex-col md:flex-row md:items-center gap-5">
  <span class="avatar avatar-lg" aria-hidden="true"><?= e(iniciales($nombre)) ?></span>
  <div class="flex-1 min-w-0">
    <p class="sobretitulo">CU14 · Historial de citas</p>
    <h1 class="titulo-pagina"><?= e($nombre) ?></h1>
    <p class="text-sm text-muted"><span class="font-mono"><?= e($paciente['codigo']) ?></span> · CI <?= e($paciente['ci'] ?: '—') ?> · <?= e($paciente['telefono']) ?></p>
  </div>
  <div class="flex flex-wrap gap-2">
    <?php if (puede('pacientes.gestionar')): ?>
      <a href="<?= e(url('/pacientes/' . $paciente['id'])) ?>" class="btn-secundario btn-sm"><?= icono('id-card') ?>Ficha</a>
    <?php endif; ?>
    <?php if (puede('citas.reservar') && $paciente['activo']): ?>
      <a href="<?= e(url('/citas/nueva?paciente_id=' . $paciente['id'])) ?>" class="btn-primario btn-sm"><?= icono('calendar-plus') ?>Nueva cita</a>
    <?php endif; ?>
  </div>
</div>

<section class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6" aria-label="Resumen del paciente">
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-days"></i></span><p class="numero-kpi mt-3"><?= count($citas) ?></p><p class="text-sm text-muted">Citas en la lista</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="clipboard-check"></i></span><p class="numero-kpi mt-3"><?= (int) ($porEstado['atendida'] ?? 0) ?></p><p class="text-sm text-muted">Atendidas</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-x"></i></span><p class="numero-kpi mt-3"><?= (int) ($porEstado['cancelada'] ?? 0) ?></p><p class="text-sm text-muted">Canceladas</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="user-x"></i></span><p class="numero-kpi mt-3"><?= (int) ($porEstado['inasistencia'] ?? 0) ?></p><p class="text-sm text-muted">Inasistencias</p></div>
</section>

<?php include __DIR__ . '/_filtros.php'; ?>

<?php if (!$citas): ?>
  <div class="glass"><?= parcial('estado_vacio', ['icono' => 'history', 'titulo' => $erroresFiltro ? 'Corrige el rango de fechas' : 'Sin citas para mostrar', 'texto' => $erroresFiltro ? 'La búsqueda no se ejecutó.' : 'El paciente no tiene citas con los filtros elegidos.']) ?></div>
<?php else: ?>
<section class="grid gap-3" aria-label="Citas del paciente">
  <?php foreach ($citas as $cita): ?>
    <details class="acordeon glass-card p-0">
      <summary class="flex flex-col md:flex-row md:items-center gap-3 p-4 md:p-5">
        <div class="md:w-44 shrink-0">
          <p class="font-semibold numeros"><?= e(fecha_corta($cita['inicio'])) ?></p>
          <p class="text-sm text-muted numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></p>
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-medium"><?= e($cita['medico']) ?></p>
          <p class="text-sm text-muted"><?= e($cita['especialidad']) ?> · <span class="font-mono"><?= e($cita['codigo']) ?></span> · Registrada <?= e(fecha_corta($cita['fecha_creacion'])) ?></p>
        </div>
        <div><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></div>
        <span class="text-sm text-subtle flex items-center gap-1"><?= count($eventos[(int) $cita['id']] ?? []) ?> evento(s) <i data-lucide="chevron-down" class="w-4 h-4 girar"></i></span>
      </summary>
      <div class="px-4 md:px-5 pb-5 pt-1 grid gap-4 md:grid-cols-3">
        <div class="md:col-span-2"><?= parcial('eventos_cita', ['eventos' => $eventos[(int) $cita['id']] ?? []]) ?></div>
        <dl class="text-sm grid gap-2 h-fit">
          <div><dt class="etiqueta">Motivo</dt><dd><?= e($cita['motivo'] ?: '—') ?></dd></div>
          <div><dt class="etiqueta">Modalidad · importe</dt><dd><?= e(ucfirst($cita['modalidad'])) ?> · <span class="numeros"><?= e(bs($cita['importe_acordado'])) ?></span></dd></div>
          <dd><a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-secundario btn-sm"><?= icono('receipt-text') ?>Comprobante</a></dd>
        </dl>
      </div>
    </details>
  <?php endforeach; ?>
</section>
<?php endif; ?>
