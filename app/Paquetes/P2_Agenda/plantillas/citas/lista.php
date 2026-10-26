<?php
/**
 * CU11 · Citas: recepción busca la cita para revisarla, reprogramarla o cancelarla.
 * @var array $resultado        Ecita_medica::buscar (paginado)
 * @var array $filtros
 * @var array $medicos
 * @var array $estados
 * @var array $conPagoAprobado  ids de citas con pago aprobado (A1)
 * @var array $erroresFiltro
 */
$f = static fn (string $clave): string => (string) ($filtros[$clave] ?? '');
$vigente = static fn (array $cita): bool => in_array((int) $cita['estado_cita_id'], [1, 2], true) && strtotime($cita['inicio']) > time();
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU11 · Gestión operativa</p>
    <h1 class="titulo-pagina">Citas</h1>
    <p class="texto">Busca una cita por código, paciente o fecha para revisarla, reprogramarla o cancelarla.</p>
  </div>
  <?php if (puede('citas.reservar')): ?>
    <a href="<?= e(url('/citas/nueva')) ?>" class="btn-primario"><?= icono('calendar-plus') ?>Nueva cita</a>
  <?php endif; ?>
</div>

<form method="get" action="<?= e(url('/citas')) ?>" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5 items-end" role="search" aria-label="Buscar citas">
  <div class="lg:col-span-2">
    <label for="c-buscar" class="etiqueta">Código, paciente o CI</label>
    <div class="campo-con-icono">
      <i data-lucide="search"></i>
      <input id="c-buscar" name="buscar" type="search" class="campo" placeholder="CM-0011, María López, 7845123…" value="<?= e($f('buscar')) ?>">
    </div>
  </div>
  <div>
    <label for="c-fecha" class="etiqueta">Fecha</label>
    <input id="c-fecha" name="fecha" type="date" class="campo<?= marca_error($erroresFiltro, 'fecha') ?>" value="<?= e($f('fecha')) ?>">
  </div>
  <div>
    <label for="c-medico" class="etiqueta">Médico</label>
    <select id="c-medico" name="medico_id" class="campo">
      <option value="">Todos</option>
      <?php foreach ($medicos as $medico): ?>
        <option value="<?= e($medico['id']) ?>"<?= $f('medico_id') === (string) $medico['id'] ? ' selected' : '' ?>><?= e($medico['nombre']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="c-estado" class="etiqueta">Estado</label>
    <select id="c-estado" name="estado_cita_id" class="campo">
      <option value="">Todos</option>
      <?php foreach ($estados as $estado): ?>
        <option value="<?= e($estado['id']) ?>"<?= $f('estado_cita_id') === (string) $estado['id'] ? ' selected' : '' ?>><?= e(texto_estado('cita', $estado['nombre'])) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2 sm:col-span-2 lg:col-span-5 justify-end">
    <a href="<?= e(url('/citas')) ?>" class="btn-fantasma"><?= icono('x') ?>Limpiar</a>
    <button type="submit" class="btn-primario"><?= icono('filter') ?>Buscar</button>
  </div>
  <?php if ($erroresFiltro): ?>
    <div class="sm:col-span-2 lg:col-span-5 alerta alerta-error"><i data-lucide="circle-x"></i><p><?= e(implode(' ', $erroresFiltro)) ?></p></div>
  <?php endif; ?>
</form>

<section class="glass tabla-contenedor" aria-label="Resultados">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'calendar-search', 'titulo' => 'No encontramos citas', 'texto' => 'Prueba con otro código, paciente, fecha o estado.']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr>
        <th scope="col">Código</th><th scope="col">Paciente</th><th scope="col">Médico</th>
        <th scope="col">Fecha y hora</th><th scope="col">Estado</th>
        <th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th>
      </tr></thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $cita): ?>
          <tr>
            <td class="font-mono principal"><?= e($cita['codigo']) ?></td>
            <td><span class="block text-fg font-medium"><?= e($cita['paciente']) ?></span><span class="text-xs font-mono"><?= e($cita['codigo_paciente']) ?> · CI <?= e($cita['ci_paciente'] ?: '—') ?></span></td>
            <td><span class="block"><?= e($cita['medico']) ?></span><span class="text-xs text-subtle"><?= e($cita['especialidad']) ?></span></td>
            <td class="numeros whitespace-nowrap"><?= e(fecha_corta($cita['inicio'])) ?> · <?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></td>
            <td>
              <?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?>
              <?php if (in_array((int) $cita['id'], $conPagoAprobado, true)): ?>
                <span class="insignia pago-aprobado ml-1" title="Tiene un pago aprobado">Pagada</span>
              <?php endif; ?>
            </td>
            <td class="text-right"><div class="flex justify-end gap-1">
              <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-icono sm" aria-label="Ver <?= e($cita['codigo']) ?>"><i data-lucide="eye" class="w-4 h-4"></i></a>
              <?php if ($vigente($cita) && puede('citas.reprogramar')): ?>
                <a href="<?= e(url('/citas/' . $cita['id'] . '/reprogramar')) ?>" class="btn-icono sm" aria-label="Reprogramar <?= e($cita['codigo']) ?>"><i data-lucide="calendar-sync" class="w-4 h-4"></i></a>
              <?php endif; ?>
              <?php if ($vigente($cita) && puede('citas.cancelar')): ?>
                <button type="button" class="btn-icono sm" data-abrir-modal="modal-cancelar-cita" data-accion="<?= e(url('/citas/' . $cita['id'] . '/cancelar')) ?>" data-nombre="<?= e($cita['codigo']) ?>" aria-label="Cancelar <?= e($cita['codigo']) ?>"><i data-lucide="calendar-x" class="w-4 h-4"></i></button>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'citas']) ?>
</section>

<?= parcial('modal_confirmar', [
    'id'     => 'modal-cancelar-cita',
    'titulo' => '¿Cancelar la cita {nombre}?',
    'texto'  => 'La cita se conserva como Cancelada en el historial y el horario queda libre para otros pacientes.',
    'boton'  => 'Cancelar cita',
    'icono'  => 'calendar-x',
    'motivo' => true,
]) ?>
