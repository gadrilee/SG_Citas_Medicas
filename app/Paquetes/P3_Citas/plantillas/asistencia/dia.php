<?php
/**
 * CU12 · Control de asistencia: citas del día agrupadas por médico.
 * @var array $porMedico      [nombre del médico => ['especialidad', 'codigo_medico', 'citas' => [...]]]
 * @var array $conteo         [estado => cantidad] de las citas listadas
 * @var array $filtros        fecha, medico_id, estado_cita_id
 * @var array $medicos
 * @var array $estados
 * @var array $erroresFiltro
 */
$f = static fn (string $clave): string => (string) ($filtros[$clave] ?? '');
$fecha = new DateTimeImmutable($filtros['fecha']);
$enlaceDia = static fn (DateTimeImmutable $dia): string => '?' . http_build_query(array_merge($filtros, ['fecha' => $dia->format('Y-m-d')]));
$ahora = time();
$esHoy = $filtros['fecha'] === date('Y-m-d');
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU12 · Operación del día</p>
    <h1 class="titulo-pagina">Control de asistencia</h1>
    <p class="texto">Confirma la asistencia prevista y registra la inasistencia cuando el turno terminó sin que el paciente se presentara.</p>
  </div>
  <div class="glass-pill flex items-center gap-1 p-1 self-start sm:self-auto">
    <a href="<?= e($enlaceDia($fecha->modify('-1 day'))) ?>" class="btn-icono sm" aria-label="Día anterior"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
    <span class="px-2 text-sm font-semibold numeros"><?= e(fecha_corta($filtros['fecha'])) ?><?= $esHoy ? ' · Hoy' : '' ?></span>
    <a href="<?= e($enlaceDia($fecha->modify('+1 day'))) ?>" class="btn-icono sm" aria-label="Día siguiente"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
  </div>
</div>

<form method="get" action="<?= e(url('/asistencia')) ?>" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end" aria-label="Filtrar citas del día">
  <div>
    <label for="a-fecha" class="etiqueta">Fecha</label>
    <input id="a-fecha" name="fecha" type="date" class="campo<?= marca_error($erroresFiltro, 'fecha') ?>" value="<?= e($f('fecha')) ?>">
  </div>
  <div>
    <label for="a-medico" class="etiqueta">Médico</label>
    <select id="a-medico" name="medico_id" class="campo">
      <option value="">Todos</option>
      <?php foreach ($medicos as $medico): ?>
        <option value="<?= e($medico['id']) ?>"<?= $f('medico_id') === (string) $medico['id'] ? ' selected' : '' ?>><?= e($medico['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="a-estado" class="etiqueta">Estado</label>
    <select id="a-estado" name="estado_cita_id" class="campo">
      <option value="">Todos</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= e($estado['id']) ?>"<?= $f('estado_cita_id') === (string) $estado['id'] ? ' selected' : '' ?>><?= e(texto_estado('cita', $estado['nombre'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2">
    <button type="submit" class="btn-primario flex-1"><?= icono('filter') ?>Aplicar</button>
    <a href="<?= e(url('/asistencia')) ?>" class="btn-icono" aria-label="Volver a hoy"><i data-lucide="calendar-check" class="w-5 h-5"></i></a>
  </div>
  <?php if ($erroresFiltro): ?>
    <div class="sm:col-span-2 lg:col-span-4 alerta alerta-error"><i data-lucide="circle-x"></i><p>La fecha no es válida; se muestra el día de hoy.</p></div>
  <?php endif; ?>
</form>

<section class="flex flex-wrap gap-2 mb-6" aria-label="Resumen del día">
  <?php foreach ($estados as $estado): ?>
    <span class="insignia <?= e(clase_estado('cita', $estado['nombre'])) ?>"><?= e(texto_estado('cita', $estado['nombre'])) ?> · <?= (int) ($conteo[$estado['nombre']] ?? 0) ?></span>
  <?php endforeach; ?>
</section>

<?php if (!$porMedico): ?>
  <div class="glass"><?= parcial('estado_vacio', ['icono' => 'calendar-check', 'titulo' => 'Sin citas para este día', 'texto' => 'Cambia la fecha o los filtros para ver otras citas.']) ?></div>
<?php else: ?>
<div class="grid gap-6">
  <?php foreach ($porMedico as $nombreMedico => $grupo): ?>
    <?php $foto = foto_medico($grupo['codigo_medico']); ?>
    <section class="glass p-5 md:p-6" aria-label="<?= e($nombreMedico) ?>">
      <div class="flex items-center gap-3 mb-4">
        <?php if ($foto): ?>
          <span class="avatar avatar-md"><img src="<?= e(recurso($foto)) ?>" alt=""></span>
        <?php else: ?>
          <span class="avatar avatar-md" aria-hidden="true"><?= e(iniciales(preg_replace('/^Dra?\. /', '', $nombreMedico))) ?></span>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <h2 class="font-semibold"><?= e($nombreMedico) ?></h2>
          <p class="text-sm text-muted"><?= e($grupo['especialidad']) ?> · <?= count($grupo['citas']) ?> cita(s)</p>
        </div>
      </div>
      <div class="grid gap-3">
        <?php foreach ($grupo['citas'] as $cita): ?>
          <?php
            $estadoId = (int) $cita['estado_cita_id'];
            $termino = strtotime($cita['fin']) <= $ahora;
            $enCurso = !$termino && strtotime($cita['inicio']) <= $ahora;
            $puedeConfirmar = $estadoId === 1 && !$termino;
            $puedeInasistencia = in_array($estadoId, [1, 2], true) && $termino;
          ?>
          <article class="bloque-cita estado-<?= e($cita['estado']) ?> p-4 flex flex-col md:flex-row md:items-center gap-3">
            <div class="md:w-28 shrink-0">
              <p class="font-semibold numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></p>
              <?php if ($enCurso): ?><p class="text-xs texto-acento font-medium">En curso</p><?php elseif ($termino && in_array($estadoId, [1, 2], true)): ?><p class="text-xs text-subtle">Turno terminado</p><?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium"><?= e($cita['paciente']) ?> <span class="text-xs font-mono text-subtle"><?= e($cita['codigo_paciente']) ?></span></p>
              <p class="text-sm text-muted"><span class="font-mono"><?= e($cita['codigo']) ?></span><?= $cita['motivo'] ? ' · ' . e($cita['motivo']) : '' ?></p>
            </div>
            <div><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></div>
            <div class="flex flex-wrap gap-2 md:justify-end">
              <?php if ($puedeConfirmar): ?>
                <form method="post" action="<?= e(url('/asistencia/' . $cita['id'] . '/confirmar')) ?>"><?= csrf_campo() ?>
                  <button type="submit" class="btn-primario btn-sm"><?= icono('user-check') ?>Confirmar asistencia</button>
                </form>
              <?php endif; ?>
              <?php if ($puedeInasistencia): ?>
                <button type="button" class="btn-peligro btn-sm" data-abrir-modal="modal-inasistencia" data-accion="<?= e(url('/asistencia/' . $cita['id'] . '/inasistencia')) ?>" data-nombre="<?= e($cita['codigo'] . ' · ' . $cita['paciente']) ?>"><?= icono('user-x') ?>Registrar inasistencia</button>
              <?php endif; ?>
              <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-icono sm" aria-label="Ver <?= e($cita['codigo']) ?>"><i data-lucide="eye" class="w-4 h-4"></i></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?= parcial('modal_confirmar', [
    'id'     => 'modal-inasistencia',
    'titulo' => '¿Registrar inasistencia de {nombre}?',
    'texto'  => 'La cita pasa a Inasistencia. Este cambio queda en el historial y no se puede revertir.',
    'boton'  => 'Registrar inasistencia',
    'icono'  => 'user-x',
]) ?>
