<?php
/**
 * CU13 · Agenda diaria de un médico (solo lectura).
 * @var array|null $medico     médico mostrado
 * @var array      $medicos    opciones para recepción y administración (vacío para el médico)
 * @var array      $citas      citas del día ya filtradas por estado
 * @var array      $conteo     [estado => cantidad] de todas las citas del día
 * @var array      $ventanas   Ehorario_atencion::paraFecha
 * @var array|null $bloqueo    Ebloqueo_agenda::deFecha
 * @var array      $filtros    fecha, medico_id, estado_cita_id
 * @var array      $estados
 * @var array      $erroresFiltro
 */
$f = static fn (string $clave): string => (string) ($filtros[$clave] ?? '');
$fecha = new DateTimeImmutable($filtros['fecha']);
$enlace = static fn (array $cambios): string => '?' . http_build_query(array_merge($filtros, $cambios));
$diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$esMedico = !$medicos;
$esHoy = $filtros['fecha'] === date('Y-m-d');
$total = array_sum($conteo);
$foto = $medico ? foto_medico($medico['codigo']) : null;
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU13 · Calendario operativo</p>
    <h1 class="titulo-pagina"><?= $esMedico ? 'Mi agenda' : 'Agenda médica' ?></h1>
    <p class="texto"><?= $esMedico ? 'Tus pacientes del día con su hora, motivo y estado.' : 'Elige el médico y la fecha para ver sus citas del día.' ?></p>
  </div>
  <?php if (!$esMedico && puede('citas.reservar')): ?>
    <a href="<?= e(url('/citas/nueva' . ($medico ? '?medico_id=' . $medico['id'] : ''))) ?>" class="btn-primario"><?= icono('calendar-plus') ?>Nueva cita</a>
  <?php endif; ?>
</div>

<?php if (!$medico): ?>
  <div class="glass"><?= parcial('estado_vacio', ['icono' => 'stethoscope', 'titulo' => 'No hay médicos activos', 'texto' => 'Registra un médico (CU05) para consultar su agenda.']) ?></div>
<?php else: ?>

<form method="get" action="<?= e(url('/agenda')) ?>" class="glass p-4 mb-6 flex flex-col lg:flex-row gap-3 lg:items-end" aria-label="Filtrar agenda">
  <?php if (!$esMedico): ?>
    <div class="flex-1">
      <label for="ag-medico" class="etiqueta">Médico</label>
      <select id="ag-medico" name="medico_id" class="campo" onchange="this.form.submit()">
        <?php foreach ($medicos as $opcion): ?>
          <option value="<?= e($opcion['id']) ?>"<?= (int) $opcion['id'] === (int) $medico['id'] ? ' selected' : '' ?>><?= e($opcion['nombre']) ?> · <?= e($opcion['especialidades']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
  <div>
    <label for="ag-fecha" class="etiqueta">Fecha</label>
    <div class="flex items-center gap-1">
      <a href="<?= e($enlace(['fecha' => $fecha->modify('-1 day')->format('Y-m-d')])) ?>" class="btn-icono" aria-label="Día anterior"><i data-lucide="chevron-left" class="w-5 h-5"></i></a>
      <input id="ag-fecha" name="fecha" type="date" class="campo<?= marca_error($erroresFiltro, 'fecha') ?>" value="<?= e($f('fecha')) ?>" onchange="this.form.submit()">
      <a href="<?= e($enlace(['fecha' => $fecha->modify('+1 day')->format('Y-m-d')])) ?>" class="btn-icono" aria-label="Día siguiente"><i data-lucide="chevron-right" class="w-5 h-5"></i></a>
    </div>
  </div>
  <div>
    <label for="ag-estado" class="etiqueta">Estado</label>
    <select id="ag-estado" name="estado_cita_id" class="campo" onchange="this.form.submit()">
      <option value="">Todos</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= e($estado['id']) ?>"<?= $f('estado_cita_id') === (string) $estado['id'] ? ' selected' : '' ?>><?= e(texto_estado('cita', $estado['nombre'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2">
    <noscript><button type="submit" class="btn-secundario">Ver</button></noscript>
    <a href="<?= e($enlace(['fecha' => date('Y-m-d')])) ?>" class="btn-secundario"><?= icono('calendar-check') ?>Hoy</a>
  </div>
</form>

<div class="grid gap-6 lg:grid-cols-3">
  <section class="glass p-5 md:p-6 lg:col-span-2" aria-labelledby="t-citas-dia">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <h2 id="t-citas-dia" class="font-semibold">Citas del <?= e($diasSemana[(int) $fecha->format('w')]) ?> <span class="numeros"><?= e($fecha->format('d/m/Y')) ?></span></h2>
      <span class="text-sm text-subtle"><?= count($citas) ?> de <?= $total ?> cita(s)</span>
    </div>
    <?php if (!$citas): ?>
      <?= parcial('estado_vacio', ['icono' => 'calendar-x', 'titulo' => 'Sin citas programadas', 'texto' => $total ? 'Ninguna cita del día tiene el estado elegido.' : 'No hay citas registradas para esta fecha.']) ?>
    <?php else: ?>
      <ol class="grid gap-3">
        <?php foreach ($citas as $cita): ?>
          <li class="bloque-cita estado-<?= e($cita['estado']) ?> p-4 flex flex-col sm:flex-row sm:items-center gap-3">
            <p class="sm:w-28 shrink-0 font-semibold numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></p>
            <div class="flex-1 min-w-0">
              <p class="font-medium"><?= e($cita['paciente']) ?> <span class="text-xs font-mono text-subtle"><?= e($cita['codigo']) ?></span></p>
              <p class="text-sm text-muted"><?= e($cita['especialidad']) ?> · <?= e(ucfirst($cita['modalidad'])) ?><?= $cita['motivo'] ? ' · ' . e($cita['motivo']) : '' ?></p>
            </div>
            <div class="flex items-center justify-between gap-2">
              <?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?>
              <?php if ($esMedico && (int) $cita['estado_cita_id'] === 3): ?>
                <a href="<?= e(url('/citas/' . $cita['id'] . '/consulta')) ?>" class="btn-secundario btn-sm"><?= icono('clipboard-check') ?>Ver consulta</a>
              <?php elseif ($esMedico && $esHoy && in_array((int) $cita['estado_cita_id'], [1, 2], true)): ?>
                <a href="<?= e(url('/citas/' . $cita['id'] . '/consulta')) ?>" class="btn-primario btn-sm"><?= icono('stethoscope') ?>Atender</a>
              <?php endif; ?>
              <?php if (puede('historia.consultar') && ($esHoy || !$esMedico) && (int) $cita['estado_cita_id'] !== 4): ?>
                <a href="<?= e(url('/pacientes/' . $cita['paciente_id'] . '/historia')) ?>" class="btn-icono sm" aria-label="Historia clínica de <?= e($cita['paciente']) ?>" title="Historia clínica"><i data-lucide="file-heart" class="w-4 h-4"></i></a>
              <?php endif; ?>
              <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-icono sm" aria-label="Detalle de <?= e($cita['codigo']) ?>"><i data-lucide="eye" class="w-4 h-4"></i></a>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </section>

  <aside class="flex flex-col gap-6">
    <section class="glass-card" aria-labelledby="t-medico-agenda">
      <div class="flex items-center gap-3 mb-4">
        <?php if ($foto): ?>
          <span class="avatar avatar-md"><img src="<?= e(recurso($foto)) ?>" alt=""></span>
        <?php else: ?>
          <span class="avatar avatar-md" aria-hidden="true"><?= e(iniciales($medico['nombres'] . ' ' . $medico['apellidos'])) ?></span>
        <?php endif; ?>
        <div class="min-w-0">
          <h2 id="t-medico-agenda" class="font-semibold leading-snug"><?= e($medico['nombre']) ?></h2>
          <p class="text-sm text-muted"><?= e($medico['especialidades']) ?></p>
        </div>
      </div>
      <?php if ($bloqueo): ?>
        <div class="alerta alerta-aviso mb-4"><i data-lucide="calendar-off"></i><p>Día bloqueado: <?= e($bloqueo['motivo']) ?></p></div>
      <?php endif; ?>
      <p class="etiqueta">Horario de atención</p>
      <?php if ($ventanas): ?>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($ventanas as $ventana): ?>
            <span class="bloque-horario numeros"><?= e($ventana['hora_inicio']) ?>–<?= e($ventana['hora_fin']) ?> · <?= e($ventana['duracion_cita_min']) ?> min</span>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-sm text-subtle">No atiende este día.</p>
      <?php endif; ?>
    </section>
    <section class="glass-card" aria-labelledby="t-resumen-agenda">
      <h2 id="t-resumen-agenda" class="font-semibold mb-3">Resumen del día</h2>
      <ul class="grid gap-2 text-sm">
        <?php foreach ($estados as $estado): ?>
          <li class="flex items-center justify-between gap-2">
            <a href="<?= e($enlace(['estado_cita_id' => $f('estado_cita_id') === (string) $estado['id'] ? '' : $estado['id']])) ?>" class="insignia <?= e(clase_estado('cita', $estado['nombre'])) ?>"><?= e(texto_estado('cita', $estado['nombre'])) ?></a>
            <span class="font-semibold numeros"><?= (int) ($conteo[$estado['nombre']] ?? 0) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  </aside>
</div>
<?php endif; ?>
