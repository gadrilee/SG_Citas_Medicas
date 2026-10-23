<?php
/**
 * Barra de reserva (Especialidad · Médico · Fecha). Envía por GET a /citas/nueva (CU09).
 * @var array $especialidades [['id', 'nombre'], ...]
 * @var array $medicos        [['id', 'nombre', 'especialidades' => '1,3'], ...]
 */
?>
<form method="get" action="<?= e(url('/citas/nueva')) ?>" class="booking-bar glass" data-booking-bar>
  <div class="segmento">
    <i data-lucide="heart-pulse"></i>
    <div class="flex-1 min-w-0">
      <label for="bb-especialidad">Especialidad</label>
      <select id="bb-especialidad" name="especialidad_id" class="campo">
        <option value="">Todas las especialidades</option>
        <?php foreach ($especialidades as $especialidad): ?>
          <option value="<?= e($especialidad['id']) ?>"><?= e($especialidad['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="segmento">
    <i data-lucide="stethoscope"></i>
    <div class="flex-1 min-w-0">
      <label for="bb-medico">Médico</label>
      <select id="bb-medico" name="medico_id" class="campo">
        <option value="">Cualquier médico</option>
        <?php foreach ($medicos as $medico): ?>
          <option value="<?= e($medico['id']) ?>" data-especialidades="<?= e($medico['especialidades_ids']) ?>"><?= e($medico['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="segmento">
    <i data-lucide="calendar"></i>
    <div class="flex-1 min-w-0">
      <label for="bb-fecha">Fecha</label>
      <input id="bb-fecha" name="fecha" type="date" class="campo" min="<?= e(date('Y-m-d')) ?>">
    </div>
  </div>
  <button type="submit" class="btn-primario btn-grande w-full md:w-auto"><i data-lucide="search" class="w-4 h-4"></i>Reservar ahora</button>
</form>
