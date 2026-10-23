<?php
/**
 * CU09 · Reservar cita médica (incluye CU08: calendario + chips de cupos).
 * @var array      $especialidades
 * @var array      $medicos
 * @var array      $pacientes  solo para recepción
 * @var array|null $paciente   el propio paciente cuando reserva él mismo
 * @var array      $seleccion  valores iniciales desde la URL (?especialidad_id=&medico_id=&fecha=&paciente_id=)
 * @var array      $errores
 * @var array      $anterior
 */
$sel = array_merge($seleccion, array_filter([
    'especialidad_id' => $anterior['especialidad_id'] ?? null,
    'medico_id'       => $anterior['medico_id'] ?? null,
    'paciente_id'     => $anterior['paciente_id'] ?? null,
    'fecha'           => isset($anterior['inicio']) && $anterior['inicio'] !== '' ? substr((string) $anterior['inicio'], 0, 10) : null,
]));
if (!empty($sel['fecha']) && $sel['fecha'] < date('Y-m-d')) {
    $sel['fecha'] = '';
}
$pacienteElegido = null;
foreach ($pacientes as $fila) {
    if ((string) $fila['id'] === (string) ($sel['paciente_id'] ?? '')) {
        $pacienteElegido = $fila;
    }
}
$modalidad = $anterior['modalidad'] ?? 'presencial';
?>
<div class="mb-6">
  <p class="sobretitulo">CU09 · Reserva en línea</p>
  <h1 class="titulo-pagina">Agenda una cita</h1>
  <p class="texto">Elige especialidad, médico, fecha y uno de los horarios libres de 15 minutos.</p>
</div>

<form method="post" action="<?= e(url('/citas')) ?>" class="grid gap-6 lg:grid-cols-3" data-reserva data-validar novalidate>
  <?= csrf_campo() ?>
  <input type="hidden" name="especialidad_id" value="<?= e($sel['especialidad_id'] ?? '') ?>" required data-validar-oculto data-mensaje-vacio="Elige una especialidad.">
  <input type="hidden" name="medico_id" value="<?= e($sel['medico_id'] ?? '') ?>" required data-validar-oculto data-mensaje-vacio="Elige un médico.">

  <div class="lg:col-span-2 flex flex-col gap-6">
    <?php if (!$paciente): ?>
    <!-- Paso 2: recepción busca y elige al paciente (A1: si no existe, se registra en CU04) -->
    <section class="glass p-5 md:p-6" aria-labelledby="t-paciente">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <h2 id="t-paciente" class="font-semibold flex items-center gap-2"><?= icono('user-round', 'w-5 h-5 texto-acento') ?>Paciente</h2>
        <a href="<?= e(url('/pacientes/nuevo')) ?>" class="btn-fantasma btn-sm"><?= icono('user-plus') ?>Registrar paciente</a>
      </div>
      <div class="campo-con-icono mb-3">
        <i data-lucide="search"></i>
        <label for="buscar-paciente" class="sr-only">Buscar paciente por CI, nombre o código</label>
        <input id="buscar-paciente" type="search" class="campo" placeholder="Buscar por CI, nombre o código…" data-buscar-paciente>
      </div>
      <input type="hidden" name="paciente_id" value="<?= e($sel['paciente_id'] ?? '') ?>" required data-validar-oculto data-mensaje-vacio="Elige al paciente de la cita.">
      <ul class="max-h-64 overflow-y-auto flex flex-col gap-1" aria-label="Pacientes activos">
        <?php foreach ($pacientes as $fila): ?>
          <?php $activo = $pacienteElegido && $pacienteElegido['id'] === $fila['id']; ?>
          <li class="flex items-center gap-3 rounded-2xl px-3 py-2" data-fila-paciente<?= $activo ? ' style="background:var(--accent-soft)"' : '' ?>>
            <span class="avatar avatar-sm" aria-hidden="true"><?= e(iniciales($fila['nombre'])) ?></span>
            <span class="flex-1 min-w-0"><span class="block font-medium truncate"><?= e($fila['nombre']) ?></span><span class="text-xs text-subtle font-mono"><?= e($fila['codigo']) ?> · CI <?= e($fila['ci'] ?: '—') ?></span></span>
            <button type="button" class="btn-secundario btn-sm" data-elegir-paciente="<?= e($fila['id']) ?>" data-nombre="<?= e($fila['nombre']) ?>" aria-pressed="<?= $activo ? 'true' : 'false' ?>"><?= $activo ? 'Seleccionado' : 'Elegir' ?></button>
          </li>
        <?php endforeach; ?>
      </ul>
      <?= error_campo($errores, 'paciente_id') ?>
    </section>
    <?php endif; ?>

    <!-- Paso 3: CU08 · especialidad, médico, fecha y cupo -->
    <?= parcial('booking_bar', ['modo' => 'asistente', 'especialidades' => $especialidades, 'medicos' => $medicos, 'seleccion' => $sel]) ?>
    <div class="grid gap-6 md:grid-cols-2">
      <div><?= parcial('calendario', ['id' => 'calendario-reserva', 'seleccionado' => $sel['fecha'] ?? '']) ?></div>
      <?= parcial('selector_horarios', ['titulo' => 'Elige un horario', 'errores' => $errores]) ?>
    </div>
  </div>

  <!-- Pasos 4–6: resumen, motivo, modalidad y confirmación -->
  <aside class="flex flex-col gap-6">
    <section class="glass-card lg:sticky lg:top-28" aria-labelledby="t-resumen">
      <h2 id="t-resumen" class="font-semibold mb-4">Resumen de la cita</h2>
      <dl class="text-sm space-y-3">
        <div class="flex justify-between gap-3"><dt class="text-muted">Paciente</dt><dd class="font-medium text-right" data-resumen="paciente"><?= e($paciente ? $paciente['nombres'] . ' ' . $paciente['apellidos'] : ($pacienteElegido['nombre'] ?? '—')) ?></dd></div>
        <div class="flex justify-between gap-3"><dt class="text-muted">Especialidad</dt><dd class="font-medium text-right" data-resumen="especialidad">—</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-muted">Médico</dt><dd class="font-medium text-right" data-resumen="medico">—</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-muted">Fecha</dt><dd class="font-medium text-right capitalize" data-resumen="fecha">—</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-muted">Horario</dt><dd class="font-medium text-right numeros" data-resumen="horario">—</dd></div>
        <div class="flex justify-between gap-3"><dt class="text-muted">Modalidad</dt><dd class="font-medium text-right" data-resumen="modalidad">—</dd></div>
        <hr class="separador">
        <div class="flex justify-between gap-3 text-base"><dt class="font-semibold">Importe</dt><dd class="font-bold numeros texto-acento" data-resumen="importe">—</dd></div>
      </dl>

      <fieldset class="mt-6">
        <legend class="etiqueta">Modalidad</legend>
        <div class="flex flex-wrap gap-2">
          <?php foreach (['presencial' => 'Presencial', 'virtual' => 'Virtual'] as $valorModalidad => $texto): ?>
            <label class="chip-opcion"><input type="radio" name="modalidad" value="<?= $valorModalidad ?>"<?= $modalidad === $valorModalidad ? ' checked' : '' ?>><span><?= $texto ?></span></label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      <div class="mt-5">
        <label for="motivo" class="etiqueta">Motivo de la consulta <span class="text-subtle">(opcional)</span></label>
        <textarea id="motivo" name="motivo" class="campo" maxlength="500" placeholder="Ej. Control de presión arterial"><?= e($anterior['motivo'] ?? '') ?></textarea>
        <?= error_campo($errores, 'motivo') ?>
      </div>
      <button type="submit" class="btn-primario btn-grande w-full mt-6"><?= icono('calendar-check') ?>Confirmar reserva</button>
      <p class="ayuda-campo text-center">El horario se vuelve a verificar al confirmar.</p>
    </section>
  </aside>
</form>
