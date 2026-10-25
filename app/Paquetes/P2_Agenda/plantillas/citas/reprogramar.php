<?php
/**
 * CU10 · Reprogramar cita médica (incluye CU08 con el mismo médico y especialidad).
 * @var array $cita
 * @var array $diasAtencion  días ISO en que atiende el médico
 * @var array $bloqueos      fechas bloqueadas próximas
 * @var array $errores
 * @var array $anterior
 */
$fecha = isset($anterior['inicio']) && $anterior['inicio'] !== '' ? substr((string) $anterior['inicio'], 0, 10) : '';
?>
<div class="max-w-5xl mx-auto">
  <div class="mb-6">
    <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-fantasma btn-sm -ml-3 mb-2"><?= icono('arrow-left') ?>Volver al comprobante</a>
    <p class="sobretitulo">CU10 · <?= e($cita['codigo']) ?> · Cambio de horario</p>
    <h1 class="titulo-pagina">Reprogramar cita</h1>
    <p class="texto"><?= e($cita['medico']) ?> · <?= e($cita['especialidad']) ?> · <?= e($cita['paciente']) ?></p>
  </div>

  <form method="post" action="<?= e(url('/citas/' . $cita['id'] . '/reprogramar')) ?>" class="grid gap-6 lg:grid-cols-3" data-reserva data-validar novalidate>
    <?= csrf_campo() ?>
    <input type="hidden" name="medico_id" value="<?= e($cita['medico_id']) ?>">
    <input type="hidden" name="especialidad_id" value="<?= e($cita['especialidad_id']) ?>">

    <div class="lg:col-span-2 grid gap-6 md:grid-cols-2 h-fit">
      <div><?= parcial('calendario', ['id' => 'calendario-reprogramar', 'seleccionado' => $fecha, 'dias' => implode(',', $diasAtencion), 'bloqueados' => implode(',', $bloqueos)]) ?></div>
      <?= parcial('selector_horarios', ['titulo' => 'Nuevo horario', 'errores' => $errores]) ?>
    </div>

    <aside class="flex flex-col gap-6">
      <section class="glass-card" aria-labelledby="t-cambio">
        <h2 id="t-cambio" class="font-semibold mb-4">Antes → después</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-2xl p-3 fondo-acento-suave">
            <p class="etiqueta">Actual</p>
            <p class="font-semibold numeros"><?= e(fecha_corta($cita['inicio'])) ?></p>
            <p class="numeros text-muted"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></p>
          </div>
          <div class="rounded-2xl p-3" style="border:1px dashed var(--accent)">
            <p class="etiqueta">Nuevo</p>
            <p class="font-semibold capitalize" data-resumen="fecha">—</p>
            <p class="numeros text-muted" data-resumen="horario">—</p>
          </div>
        </div>
        <div class="mt-5">
          <label for="motivo" class="etiqueta">Motivo del cambio <span class="text-danger">*</span></label>
          <textarea id="motivo" name="motivo" class="campo<?= marca_error($errores, 'motivo') ?>" required minlength="5" maxlength="500" placeholder="Ej. Viaje de trabajo esa semana"><?= e($anterior['motivo'] ?? '') ?></textarea>
          <?= error_campo($errores, 'motivo') ?>
        </div>
        <div class="flex flex-col gap-2 mt-6">
          <button type="submit" class="btn-primario"><?= icono('calendar-sync') ?>Guardar cambio</button>
          <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-fantasma">Cancelar</a>
        </div>
      </section>
      <div class="alerta"><i data-lucide="info"></i><p>Se conserva el estado de la cita y el horario anterior queda en su historial.</p></div>
    </aside>
  </form>
</div>
