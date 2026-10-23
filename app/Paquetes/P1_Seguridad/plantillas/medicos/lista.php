<?php
/**
 * CU05 · Directorio de médicos.
 * @var array      $medicos
 * @var array      $especialidades
 * @var array|null $desactivar   médico a desactivar (?desactivar=ID) con sus citas futuras en $afectadas
 * @var array      $afectadas
 */
$filtro = static fn (string $clave): string => (string) ($_GET[$clave] ?? '');
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU05 · Directorio clínico</p>
    <h1 class="titulo-pagina">Médicos</h1>
    <p class="texto">Gestiona profesionales, registros profesionales y especialidades.</p>
  </div>
  <a href="<?= e(url('/medicos/nuevo')) ?>" class="btn-primario"><?= icono('user-plus') ?>Agregar médico</a>
</div>

<form method="get" action="<?= e(url('/medicos')) ?>" class="glass p-3 mb-6 flex flex-col md:flex-row gap-3" role="search" aria-label="Buscar médicos">
  <div class="campo-con-icono flex-1">
    <i data-lucide="search"></i>
    <label for="m-buscar" class="sr-only">Buscar por nombre, código o especialidad</label>
    <input id="m-buscar" name="buscar" type="search" class="campo" placeholder="Buscar por nombre, código o especialidad…" value="<?= e($filtro('buscar')) ?>">
  </div>
  <label for="m-especialidad" class="sr-only">Especialidad</label>
  <select id="m-especialidad" name="especialidad_id" class="campo md:w-56">
    <option value="">Todas las especialidades</option>
    <?php foreach ($especialidades as $especialidad): ?>
      <option value="<?= e($especialidad['id']) ?>"<?= $filtro('especialidad_id') === (string) $especialidad['id'] ? ' selected' : '' ?>><?= e($especialidad['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn-secundario"><?= icono('search') ?>Buscar</button>
</form>

<?php if (!$medicos): ?>
  <div class="glass"><?= parcial('estado_vacio', ['icono' => 'stethoscope', 'titulo' => 'No encontramos médicos', 'texto' => 'Prueba con otro nombre o especialidad.', 'boton' => 'Agregar médico', 'ruta' => '/medicos/nuevo']) ?></div>
<?php else: ?>
<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Médicos">
  <?php foreach ($medicos as $medico): ?>
    <?php $nombre = $medico['nombres'] . ' ' . $medico['apellidos']; $foto = foto_medico($medico['codigo']); ?>
    <article class="glass-card flex flex-col gap-4<?= $medico['activo'] ? '' : ' opacity-70' ?>">
      <div class="flex items-start gap-4">
        <?php if ($foto): ?>
          <img src="<?= e(recurso($foto)) ?>" alt="" class="w-16 h-16 rounded-2xl object-cover">
        <?php else: ?>
          <span class="avatar avatar-lg" style="border-radius:16px;width:64px;height:64px" aria-hidden="true"><?= e(iniciales($nombre)) ?></span>
        <?php endif; ?>
        <div class="min-w-0 flex-1">
          <p class="font-semibold leading-snug"><?= e($nombre) ?></p>
          <p class="text-xs text-subtle font-mono"><?= e($medico['codigo']) ?> · <?= e($medico['nro_registro_profesional']) ?></p>
          <div class="mt-2"><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $medico['activo']]) ?></div>
        </div>
      </div>
      <div class="flex flex-wrap gap-1.5">
        <?php foreach (array_filter(explode(' · ', (string) $medico['especialidades'])) as $especialidad): ?>
          <span class="chip"><?= e($especialidad) ?></span>
        <?php endforeach; ?>
        <?php if (!$medico['especialidades']): ?><span class="text-sm text-subtle">Sin especialidad activa</span><?php endif; ?>
      </div>
      <p class="text-sm text-muted flex items-center gap-2"><?= icono('phone', 'w-4 h-4 texto-acento') ?><span class="numeros"><?= e($medico['telefono']) ?></span></p>
      <div class="flex flex-wrap gap-2 mt-auto">
        <a href="<?= e(url('/medicos/' . $medico['id'] . '/editar')) ?>" class="btn-secundario btn-sm"><?= icono('pencil') ?>Editar</a>
        <?php if ($medico['activo']): ?>
          <a href="?<?= e(http_build_query(array_merge($_GET, ['desactivar' => $medico['id']]))) ?>" class="btn-peligro btn-sm"><?= icono('user-x') ?>Desactivar</a>
        <?php else: ?>
          <form method="post" action="<?= e(url('/medicos/' . $medico['id'] . '/activar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-secundario btn-sm"><?= icono('user-check') ?>Reactivar</button></form>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<?php if ($desactivar): ?>
<!-- A2: antes de desactivar se muestran las citas futuras afectadas -->
<div id="modal-desactivar-medico" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-desactivar-titulo" hidden data-abrir-al-cargar>
  <div class="modal-panel ancho">
    <form method="post" action="<?= e(url('/medicos/' . $desactivar['id'] . '/desactivar')) ?>">
      <?= csrf_campo() ?>
      <div class="flex items-start gap-4">
        <span class="icono-cuadrado peligro"><i data-lucide="user-x" class="w-6 h-6"></i></span>
        <div class="flex-1 min-w-0">
          <h2 id="modal-desactivar-titulo" class="text-lg font-semibold">¿Desactivar a <?= e($desactivar['nombres'] . ' ' . $desactivar['apellidos']) ?>?</h2>
          <p class="texto text-sm mt-1">No recibirá nuevas citas. Sus citas históricas se conservan.</p>
        </div>
        <a href="<?= e(url('/medicos')) ?>" class="btn-icono sm -mt-1 -mr-1" aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></a>
      </div>
      <?php if ($afectadas): ?>
        <div class="alerta alerta-aviso mt-5"><i data-lucide="calendar-clock"></i><p>Tiene <?= count($afectadas) ?> cita(s) futuras. Recepción deberá reprogramarlas (CU10) o cancelarlas (CU11).</p></div>
        <div class="mt-4 max-h-60 overflow-y-auto">
          <table class="tabla">
            <thead><tr><th scope="col">Código</th><th scope="col">Fecha</th><th scope="col">Horario</th><th scope="col">Paciente</th><th scope="col">Estado</th></tr></thead>
            <tbody>
              <?php foreach ($afectadas as $cita): ?>
                <tr>
                  <td class="font-mono"><?= e($cita['codigo']) ?></td>
                  <td class="numeros"><?= e(fecha_corta($cita['inicio'])) ?></td>
                  <td class="numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></td>
                  <td><?= e($cita['paciente']) ?></td>
                  <td><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alerta alerta-exito mt-5"><i data-lucide="circle-check"></i><p>No tiene citas futuras pendientes.</p></div>
      <?php endif; ?>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <a href="<?= e(url('/medicos')) ?>" class="btn-fantasma">Volver</a>
        <button type="submit" class="btn-peligro solido">Desactivar médico</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>
