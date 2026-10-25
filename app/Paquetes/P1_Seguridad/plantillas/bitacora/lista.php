<?php
/**
 * CU03 · Bitácora.
 * @var array $resultado      Ebitacora::listar (paginado)
 * @var array $filtros
 * @var array $opciones       usuarios, acciones, entidades
 * @var array $erroresFiltro
 */
$f = static fn (string $clave): string => (string) ($filtros[$clave] ?? '');
$claseAccion = static fn (string $accion): string => match (true) {
    in_array($accion, ['crear', 'registrar', 'reservar', 'activar', 'publicar'], true) => 'insignia-exito',
    in_array($accion, ['cancelar', 'desactivar'], true)                                  => 'insignia-peligro',
    in_array($accion, ['acceso_fallido', 'restablecer_contrasena'], true)                => 'insignia-aviso',
    in_array($accion, ['iniciar_sesion', 'cerrar_sesion'], true)                         => 'insignia-info',
    default                                                                              => 'insignia-acento',
};
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU03 · Auditoría</p>
    <h1 class="titulo-pagina">Bitácora</h1>
    <p class="texto">Registro de solo lectura de las acciones realizadas en el sistema.</p>
  </div>
</div>

<form method="get" action="<?= e(url('/bitacora')) ?>" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end" role="search" aria-label="Filtrar bitácora">
  <div>
    <label for="b-desde" class="etiqueta">Desde</label>
    <input id="b-desde" name="desde" type="date" class="campo" value="<?= e($f('desde')) ?>">
  </div>
  <div>
    <label for="b-hasta" class="etiqueta">Hasta</label>
    <input id="b-hasta" name="hasta" type="date" class="campo<?= marca_error($erroresFiltro, 'hasta') ?>" value="<?= e($f('hasta')) ?>">
  </div>
  <div>
    <label for="b-usuario" class="etiqueta">Usuario</label>
    <select id="b-usuario" name="usuario_id" class="campo">
      <option value="">Todos</option>
      <?php foreach ($opciones['usuarios'] as $usuario): ?>
        <option value="<?= e($usuario['id']) ?>"<?= $f('usuario_id') === (string) $usuario['id'] ? ' selected' : '' ?>><?= e($usuario['nombre_usuario']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="b-accion" class="etiqueta">Acción</label>
    <select id="b-accion" name="accion" class="campo">
      <option value="">Todas</option>
      <?php foreach ($opciones['acciones'] as $accion): ?>
        <option<?= $f('accion') === $accion ? ' selected' : '' ?>><?= e($accion) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label for="b-entidad" class="etiqueta">Tabla</label>
    <select id="b-entidad" name="entidad" class="campo">
      <option value="">Todas</option>
      <?php foreach ($opciones['entidades'] as $entidad): ?>
        <option<?= $f('entidad') === $entidad ? ' selected' : '' ?>><?= e($entidad) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="flex gap-2">
    <button type="submit" class="btn-primario flex-1"><?= icono('filter') ?>Aplicar</button>
    <a href="<?= e(url('/bitacora')) ?>" class="btn-icono" aria-label="Limpiar filtros"><i data-lucide="x" class="w-5 h-5"></i></a>
  </div>
  <?php if ($erroresFiltro): ?>
    <div class="sm:col-span-2 lg:col-span-6 alerta alerta-error"><i data-lucide="circle-x"></i><p><?= e(implode(' ', $erroresFiltro)) ?></p></div>
  <?php endif; ?>
</form>

<section class="glass tabla-contenedor" aria-label="Registros de la bitácora">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'scroll-text', 'titulo' => 'Sin registros en este rango', 'texto' => 'Ajusta las fechas o los filtros para ver otros eventos.']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr><th scope="col">Fecha y hora</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Tabla</th><th scope="col">Registro</th><th scope="col">IP</th></tr></thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $fila): ?>
          <tr>
            <td class="numeros whitespace-nowrap"><?= e(fecha_hora($fila['fecha_hora'])) ?></td>
            <td class="font-mono"><?= e($fila['nombre_usuario']) ?></td>
            <td><span class="insignia <?= e($claseAccion($fila['accion'])) ?>"><?= e($fila['accion']) ?></span></td>
            <td class="font-mono text-muted"><?= e($fila['entidad']) ?></td>
            <td class="numeros"><?= e($fila['registro_id']) ?></td>
            <td class="font-mono text-subtle"><?= e($fila['ip'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'registros']) ?>
</section>
