<?php
/**
 * Barra de reserva (Especialidad · Médico · Fecha).
 * - modo 'formulario' (portada, panel): envía por GET a /citas/nueva.
 * - modo 'asistente' (dentro de la reserva, CU09): sin formulario propio; cupos.js copia los valores
 *   a los campos ocultos especialidad_id y medico_id del formulario que la contiene.
 * @var array       $especialidades [['id', 'nombre', 'importe_consulta'], ...]
 * @var array       $medicos        Emedico::activosConEspecialidades
 * @var string|null $modo
 * @var array|null  $seleccion      ['especialidad_id', 'medico_id', 'fecha']
 */
$asistente = ($modo ?? 'formulario') === 'asistente';
$seleccion ??= [];
$nombre = static fn (string $campo): string => $asistente ? 'data-sincroniza="' . $campo . '"' : 'name="' . $campo . '"';
$elegido = static fn (string $campo, $valor): string => (string) ($seleccion[$campo] ?? '') === (string) $valor ? ' selected' : '';
$sufijo = $asistente ? 'asistente' : 'formulario';
?>
<?php if ($asistente): ?>
<div class="booking-bar sin-boton glass" data-booking-bar>
<?php else: ?>
<form method="get" action="<?= e(url('/citas/nueva')) ?>" class="booking-bar glass" data-booking-bar>
<?php endif; ?>
  <div class="segmento">
    <i data-lucide="heart-pulse"></i>
    <div class="flex-1 min-w-0">
      <label for="bb-especialidad-<?= $sufijo ?>">Especialidad</label>
      <select id="bb-especialidad-<?= $sufijo ?>" <?= $nombre('especialidad_id') ?> class="campo"<?= $asistente ? ' required' : '' ?>>
        <option value=""><?= $asistente ? 'Elige una especialidad' : 'Todas las especialidades' ?></option>
        <?php foreach ($especialidades as $especialidad): ?>
          <option value="<?= e($especialidad['id']) ?>" data-importe="<?= e($especialidad['importe_consulta'] ?? '') ?>"<?= $elegido('especialidad_id', $especialidad['id']) ?>><?= e($especialidad['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="segmento">
    <i data-lucide="stethoscope"></i>
    <div class="flex-1 min-w-0">
      <label for="bb-medico-<?= $sufijo ?>">Médico</label>
      <select id="bb-medico-<?= $sufijo ?>" <?= $nombre('medico_id') ?> class="campo"<?= $asistente ? ' required' : '' ?>>
        <option value=""><?= $asistente ? 'Elige un médico' : 'Cualquier médico' ?></option>
        <?php foreach ($medicos as $medico): ?>
          <option value="<?= e($medico['id']) ?>" data-especialidades="<?= e($medico['especialidades_ids']) ?>" data-dias="<?= e($medico['dias_atencion'] ?? '') ?>" data-bloqueados="<?= e($medico['bloqueos'] ?? '') ?>"<?= $elegido('medico_id', $medico['id']) ?>><?= e($medico['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="segmento">
    <i data-lucide="calendar"></i>
    <div class="flex-1 min-w-0">
      <label for="bb-fecha-<?= $sufijo ?>">Fecha</label>
      <input id="bb-fecha-<?= $sufijo ?>" <?= $asistente ? 'data-fecha-booking' : 'name="fecha"' ?> type="date" class="campo" min="<?= e(date('Y-m-d')) ?>" value="<?= e($seleccion['fecha'] ?? '') ?>">
    </div>
  </div>
<?php if ($asistente): ?>
</div>
<?php else: ?>
  <button type="submit" class="btn-primario btn-grande w-full md:w-auto"><i data-lucide="search" class="w-4 h-4"></i>Reservar ahora</button>
</form>
<?php endif; ?>
