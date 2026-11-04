<?php
/**
 * CU14 · Filtro de rango de fechas y estado (paso 3), compartido por el historial y «Mis citas».
 * @var string     $accion        ruta del formulario
 * @var array      $filtros
 * @var array      $estados
 * @var array      $erroresFiltro
 * @var array|null $ocultos       campos que se conservan (paciente_id)
 */
$f = static fn (string $clave): string => (string) ($filtros[$clave] ?? '');
?>
<form method="get" action="<?= e(url($accion)) ?>" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 items-end" aria-label="Filtrar citas">
  <?php foreach ($ocultos ?? [] as $nombre => $valor): ?>
    <input type="hidden" name="<?= e($nombre) ?>" value="<?= e($valor) ?>">
  <?php endforeach; ?>
  <div>
    <label for="hf-desde" class="etiqueta">Desde</label>
    <input id="hf-desde" name="desde" type="date" class="campo<?= marca_error($erroresFiltro, 'desde') ?>" value="<?= e($f('desde')) ?>">
  </div>
  <div>
    <label for="hf-hasta" class="etiqueta">Hasta</label>
    <input id="hf-hasta" name="hasta" type="date" class="campo<?= marca_error($erroresFiltro, 'hasta') ?>" value="<?= e($f('hasta')) ?>">
  </div>
  <div>
    <label for="hf-estado" class="etiqueta">Estado</label>
    <select id="hf-estado" name="estado_cita_id" class="campo">
      <option value="">Todos</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= e($estado['id']) ?>"<?= $f('estado_cita_id') === (string) $estado['id'] ? ' selected' : '' ?>><?= e(texto_estado('cita', $estado['nombre'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2">
    <button type="submit" class="btn-primario flex-1"><?= icono('filter') ?>Aplicar</button>
    <a href="<?= e(url($accion . (!empty($ocultos) ? '?' . http_build_query($ocultos) : ''))) ?>" class="btn-icono" aria-label="Limpiar filtros"><i data-lucide="x" class="w-5 h-5"></i></a>
  </div>
  <?php if ($erroresFiltro): ?>
    <div class="sm:col-span-2 lg:col-span-4 alerta alerta-error"><i data-lucide="circle-x"></i><p><?= e(implode(' ', $erroresFiltro)) ?></p></div>
  <?php endif; ?>
</form>
