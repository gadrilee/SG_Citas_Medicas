<?php
/**
 * CU07 · Horarios de atención y bloqueos de un médico.
 * @var array      $medicos     médicos activos
 * @var array|null $medico      médico seleccionado
 * @var array      $porDia      [1..7 => ventanas vigentes]
 * @var array      $dias        [1 => 'Lunes', …]
 * @var array      $bloqueos    próximos
 * @var array|null $editar      ventana abierta en el modal de edición (?editar=ID)
 * @var array|null $pendiente   bloqueo esperando confirmación (el día tiene citas)
 * @var array      $afectadas   citas del día a bloquear
 * @var array      $errores
 * @var array      $anterior
 */
$erroresBloqueo = array_intersect_key($errores, array_flip(['fecha', 'motivo']));
$erroresVentana = array_diff_key($errores, $erroresBloqueo);
$abrirVentana = $erroresVentana && !$editar;
$campos = static function (array $registro, array $anterior, array $errores, string $prefijo, array $dias): string {
    $v = static fn (string $campo, $defecto = ''): string => e(valor($anterior, $registro, $campo, $defecto));
    ob_start(); ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="sm:col-span-2">
        <span class="etiqueta">Día de la semana <span class="text-danger">*</span></span>
        <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Día de la semana">
          <?php foreach ($dias as $numero => $nombre): ?>
            <label class="chip-opcion"><input type="radio" name="dia_semana" value="<?= $numero ?>"<?= $numero === 1 ? ' required' : '' ?><?= $v('dia_semana') === (string) $numero ? ' checked' : '' ?>><span><?= e(mb_substr($nombre, 0, 3)) ?></span></label>
          <?php endforeach; ?>
        </div>
        <?= error_campo($errores, 'dia_semana') ?>
      </div>
      <div>
        <label for="<?= $prefijo ?>-hora-inicio" class="etiqueta">Hora de inicio <span class="text-danger">*</span></label>
        <input id="<?= $prefijo ?>-hora-inicio" name="hora_inicio" type="time" step="300" class="campo<?= marca_error($errores, 'hora_inicio') ?>" required value="<?= $v('hora_inicio', '08:00') ?>">
        <?= error_campo($errores, 'hora_inicio') ?>
      </div>
      <div>
        <label for="<?= $prefijo ?>-hora-fin" class="etiqueta">Hora de fin <span class="text-danger">*</span></label>
        <input id="<?= $prefijo ?>-hora-fin" name="hora_fin" type="time" step="300" class="campo<?= marca_error($errores, 'hora_fin') ?>" required value="<?= $v('hora_fin', '12:00') ?>">
        <?= error_campo($errores, 'hora_fin') ?>
      </div>
      <div>
        <label for="<?= $prefijo ?>-duracion" class="etiqueta">Duración del turno (min) <span class="text-danger">*</span></label>
        <input id="<?= $prefijo ?>-duracion" name="duracion_cita_min" type="number" min="5" max="240" step="5" class="campo numeros<?= marca_error($errores, 'duracion_cita_min') ?>" required value="<?= $v('duracion_cita_min', '15') ?>">
        <?= error_campo($errores, 'duracion_cita_min') ?>
      </div>
      <div></div>
      <div>
        <label for="<?= $prefijo ?>-desde" class="etiqueta">Vigente desde <span class="text-danger">*</span></label>
        <input id="<?= $prefijo ?>-desde" name="vigente_desde" type="date" class="campo<?= marca_error($errores, 'vigente_desde') ?>" required value="<?= e($anterior['vigente_desde'] ?? date('Y-m-d')) ?>">
        <?= error_campo($errores, 'vigente_desde') ?>
      </div>
      <div>
        <label for="<?= $prefijo ?>-hasta" class="etiqueta">Vigente hasta</label>
        <input id="<?= $prefijo ?>-hasta" name="vigente_hasta" type="date" class="campo<?= marca_error($errores, 'vigente_hasta') ?>" value="<?= $v('vigente_hasta') ?>">
        <p class="ayuda-campo">Vacío = sin fecha de fin.</p>
        <?= error_campo($errores, 'vigente_hasta') ?>
      </div>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU07 · Disponibilidad profesional</p>
    <h1 class="titulo-pagina">Horarios de atención</h1>
    <p class="texto">Ventanas semanales divididas en turnos y días bloqueados de cada médico.</p>
  </div>
  <?php if ($medico): ?>
    <button type="button" class="btn-primario" data-abrir-modal="modal-ventana"><?= icono('plus') ?>Nueva ventana</button>
  <?php endif; ?>
</div>

<?php if (!$medico): ?>
  <div class="glass"><?= parcial('estado_vacio', ['icono' => 'stethoscope', 'titulo' => 'No hay médicos activos', 'texto' => 'Registra un médico (CU05) para configurar su agenda.', 'boton' => 'Agregar médico', 'ruta' => '/medicos/nuevo']) ?></div>
<?php else: ?>

<form method="get" action="<?= e(url('/horarios')) ?>" class="glass p-3 mb-6 flex flex-col md:flex-row gap-3 md:items-center">
  <label for="h-medico" class="etiqueta md:mb-0 md:ml-2">Médico</label>
  <select id="h-medico" name="medico_id" class="campo md:max-w-md" onchange="this.form.submit()">
    <?php foreach ($medicos as $opcion): ?>
      <option value="<?= e($opcion['id']) ?>"<?= (int) $opcion['id'] === (int) $medico['id'] ? ' selected' : '' ?>><?= e($opcion['nombre']) ?> · <?= e($opcion['especialidades']) ?></option>
    <?php endforeach; ?>
  </select>
  <noscript><button type="submit" class="btn-secundario">Ver</button></noscript>
</form>

<section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 mb-8" aria-label="Semana de atención">
  <?php foreach ($dias as $numero => $nombre): ?>
    <div class="glass p-4 flex flex-col gap-2 min-h-[140px]" style="border-radius:18px">
      <p class="font-semibold text-sm"><?= e($nombre) ?></p>
      <?php if (!$porDia[$numero]): ?>
        <p class="text-xs text-subtle">Sin atención</p>
      <?php endif; ?>
      <?php foreach ($porDia[$numero] as $ventana): ?>
        <div class="bloque-horario">
          <p class="font-semibold numeros"><?= e($ventana['hora_inicio']) ?>–<?= e($ventana['hora_fin']) ?></p>
          <p class="text-subtle">Turnos de <?= e($ventana['duracion_cita_min']) ?> min</p>
          <p class="text-subtle">Desde <?= e(fecha_corta($ventana['vigente_desde'])) ?><?= $ventana['vigente_hasta'] ? ' hasta ' . e(fecha_corta($ventana['vigente_hasta'])) : '' ?></p>
          <div class="flex gap-1 mt-1">
            <a href="?medico_id=<?= e($medico['id']) ?>&amp;editar=<?= e($ventana['id']) ?>" class="btn-icono sm" aria-label="Modificar ventana del <?= e($nombre) ?>"><i data-lucide="pencil" class="w-4 h-4"></i></a>
            <button type="button" class="btn-icono sm" data-abrir-modal="modal-quitar-ventana" data-accion="<?= e(url('/horarios/' . $ventana['id'] . '/desactivar')) ?>" data-nombre="<?= e($nombre . ' ' . $ventana['hora_inicio'] . '–' . $ventana['hora_fin']) ?>" aria-label="Quitar ventana del <?= e($nombre) ?>"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</section>

<div class="grid gap-6 lg:grid-cols-3">
  <section class="glass p-6 lg:col-span-2" aria-labelledby="t-bloqueos">
    <h2 id="t-bloqueos" class="font-semibold mb-1">Días bloqueados</h2>
    <p class="text-sm text-subtle mb-4">Ausencias, feriados o capacitaciones: ese día no se ofrecen cupos.</p>
    <?php if (!$bloqueos): ?>
      <p class="texto text-sm">No hay bloqueos próximos.</p>
    <?php else: ?>
      <ul class="flex flex-col gap-2">
        <?php foreach ($bloqueos as $bloqueo): ?>
          <li class="flex items-center gap-3 rounded-2xl px-3 py-2 fondo-acento-suave">
            <span class="icono-cuadrado aviso" style="width:40px;height:40px;border-radius:12px"><i data-lucide="calendar-x" class="w-5 h-5"></i></span>
            <span class="flex-1"><span class="block font-semibold numeros"><?= e(fecha_corta($bloqueo['fecha'])) ?></span><span class="text-sm text-muted"><?= e($bloqueo['motivo']) ?></span></span>
            <form method="post" action="<?= e(url('/horarios/bloqueos/' . $bloqueo['id'] . '/eliminar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-icono sm" aria-label="Quitar bloqueo del <?= e(fecha_corta($bloqueo['fecha'])) ?>"><i data-lucide="x" class="w-4 h-4"></i></button></form>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>

  <form method="post" action="<?= e(url('/horarios/' . $medico['id'] . '/bloqueos')) ?>" class="glass-card h-fit" data-validar novalidate aria-labelledby="t-bloquear">
    <?= csrf_campo() ?>
    <h2 id="t-bloquear" class="font-semibold mb-4">Bloquear un día</h2>
    <div class="flex flex-col gap-4">
      <div>
        <label for="b-fecha" class="etiqueta">Fecha <span class="text-danger">*</span></label>
        <input id="b-fecha" name="fecha" type="date" class="campo<?= marca_error($erroresBloqueo, 'fecha') ?>" required min="<?= e(date('Y-m-d')) ?>" value="<?= e($anterior['fecha'] ?? '') ?>">
        <?= error_campo($erroresBloqueo, 'fecha') ?>
      </div>
      <div>
        <label for="b-motivo" class="etiqueta">Motivo <span class="text-danger">*</span></label>
        <input id="b-motivo" name="motivo" class="campo<?= marca_error($erroresBloqueo, 'motivo') ?>" required minlength="3" maxlength="250" placeholder="Ej. Congreso médico" value="<?= e($anterior['motivo'] ?? '') ?>">
        <?= error_campo($erroresBloqueo, 'motivo') ?>
      </div>
      <button type="submit" class="btn-secundario"><?= icono('calendar-x') ?>Bloquear día</button>
    </div>
  </form>
</div>

<div id="modal-ventana" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-ventana-titulo" hidden<?= $abrirVentana ? ' data-abrir-al-cargar' : '' ?>>
  <div class="modal-panel ancho">
    <form method="post" action="<?= e(url('/horarios')) ?>" data-validar novalidate>
      <?= csrf_campo() ?>
      <input type="hidden" name="medico_id" value="<?= e($medico['id']) ?>">
      <div class="flex items-start justify-between gap-4 mb-5">
        <div><h2 id="modal-ventana-titulo" class="text-lg font-semibold">Nueva ventana de atención</h2><p class="texto text-sm"><?= e($medico['nombre']) ?></p></div>
        <button type="button" class="btn-icono sm" data-cerrar-modal aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <?= $campos([], $editar ? [] : $anterior, $editar ? [] : $erroresVentana, 'nueva', $dias) ?>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <button type="button" class="btn-fantasma" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar ventana</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editar): ?>
<div id="modal-editar-ventana" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-editar-ventana-titulo" hidden data-abrir-al-cargar>
  <div class="modal-panel ancho">
    <form method="post" action="<?= e(url('/horarios/' . $editar['id'])) ?>" data-validar novalidate>
      <?= csrf_campo() ?>
      <?= metodo_campo('PUT') ?>
      <div class="flex items-start justify-between gap-4 mb-5">
        <div><h2 id="modal-editar-ventana-titulo" class="text-lg font-semibold">Modificar ventana</h2><p class="texto text-sm">La ventana actual termina el día anterior a «Vigente desde» y la nueva empieza ese día. Las citas ya reservadas no cambian.</p></div>
        <a href="?medico_id=<?= e($medico['id']) ?>" class="btn-icono sm" aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></a>
      </div>
      <?= $campos(['hora_inicio' => substr((string) $editar['hora_inicio'], 0, 5), 'hora_fin' => substr((string) $editar['hora_fin'], 0, 5)] + $editar, $anterior, $erroresVentana, 'editar', $dias) ?>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <a href="?medico_id=<?= e($medico['id']) ?>" class="btn-fantasma">Cancelar</a>
        <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php if ($pendiente): ?>
<!-- Paso 7: el día tiene citas; se listan antes de confirmar el bloqueo -->
<div id="modal-confirmar-bloqueo" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-confirmar-bloqueo-titulo" hidden data-abrir-al-cargar>
  <div class="modal-panel ancho">
    <div class="flex items-start gap-4">
      <span class="icono-cuadrado aviso"><i data-lucide="calendar-x" class="w-6 h-6"></i></span>
      <div class="flex-1 min-w-0">
        <h2 id="modal-confirmar-bloqueo-titulo" class="text-lg font-semibold">¿Bloquear el <?= e(fecha_corta($pendiente['fecha'])) ?>?</h2>
        <p class="texto text-sm mt-1">Ese día tiene <?= count($afectadas) ?> cita(s). No se cancelan automáticamente: recepción deberá reprogramarlas (CU10) o cancelarlas (CU11).</p>
      </div>
    </div>
    <div class="mt-5 max-h-60 overflow-y-auto">
      <table class="tabla">
        <thead><tr><th scope="col">Código</th><th scope="col">Horario</th><th scope="col">Paciente</th><th scope="col">Estado</th></tr></thead>
        <tbody>
          <?php foreach ($afectadas as $cita): ?>
            <tr><td class="font-mono"><?= e($cita['codigo']) ?></td><td class="numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></td><td><?= e($cita['paciente']) ?></td><td><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
      <form method="post" action="<?= e(url('/horarios/bloqueos/descartar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-fantasma w-full">No bloquear</button></form>
      <form method="post" action="<?= e(url('/horarios/' . $pendiente['medico_id'] . '/bloqueos')) ?>">
        <?= csrf_campo() ?>
        <input type="hidden" name="fecha" value="<?= e($pendiente['fecha']) ?>">
        <input type="hidden" name="motivo" value="<?= e($pendiente['motivo']) ?>">
        <input type="hidden" name="confirmar" value="1">
        <button type="submit" class="btn-peligro solido w-full">Bloquear de todos modos</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?= parcial('modal_confirmar', [
    'id' => 'modal-quitar-ventana', 'titulo' => '¿Quitar la ventana {nombre}?', 'icono' => 'trash-2', 'boton' => 'Quitar ventana',
    'texto' => 'Dejará de ofrecer cupos desde hoy. Las citas ya reservadas se conservan.',
]) ?>
<?php endif; ?>
