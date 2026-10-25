<?php
/**
 * CU06 · Especialidades.
 * @var array      $especialidades  Eespecialidad::todas
 * @var array|null $editar          especialidad abierta en el modal de edición (?editar=ID)
 * @var array      $errores
 * @var array      $anterior
 */
$abrirNueva = $errores && !$editar;
$campos = static function (array $registro, array $anterior, array $errores, string $prefijo): string {
    ob_start(); ?>
    <div class="grid gap-4">
      <div>
        <label for="<?= $prefijo ?>-nombre" class="etiqueta">Nombre <span class="text-danger">*</span></label>
        <input id="<?= $prefijo ?>-nombre" name="nombre" class="campo<?= marca_error($errores, 'nombre') ?>" required maxlength="100" placeholder="Ej. Cardiología" value="<?= e(valor($anterior, $registro, 'nombre')) ?>">
        <?= error_campo($errores, 'nombre') ?>
      </div>
      <div>
        <label for="<?= $prefijo ?>-importe" class="etiqueta">Tarifa de consulta (Bs) <span class="text-danger">*</span></label>
        <input id="<?= $prefijo ?>-importe" name="importe_consulta" type="number" min="0" step="0.01" class="campo numeros<?= marca_error($errores, 'importe_consulta') ?>" required value="<?= e(valor($anterior, $registro, 'importe_consulta')) ?>">
        <p class="ayuda-campo">Se copia en cada nueva reserva; las citas ya reservadas conservan su importe.</p>
        <?= error_campo($errores, 'importe_consulta') ?>
      </div>
      <div>
        <label for="<?= $prefijo ?>-descripcion" class="etiqueta">Descripción</label>
        <textarea id="<?= $prefijo ?>-descripcion" name="descripcion" class="campo" maxlength="500" placeholder="Qué atiende esta especialidad…"><?= e(valor($anterior, $registro, 'descripcion')) ?></textarea>
      </div>
    </div>
    <?php return (string) ob_get_clean();
};
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU06 · Catálogo clínico</p>
    <h1 class="titulo-pagina">Especialidades</h1>
    <p class="texto">Configura las áreas de atención y su tarifa de consulta.</p>
  </div>
  <button type="button" class="btn-primario" data-abrir-modal="modal-especialidad"><?= icono('plus') ?>Nueva especialidad</button>
</div>

<section class="glass tabla-contenedor" aria-label="Especialidades">
  <?php if (!$especialidades): ?>
    <?= parcial('estado_vacio', ['icono' => 'heart-pulse', 'titulo' => 'Aún no hay especialidades']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr><th scope="col">Especialidad</th><th scope="col" class="text-right">Tarifa</th><th scope="col">Médicos activos</th><th scope="col">Estado</th><th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th></tr></thead>
      <tbody>
        <?php foreach ($especialidades as $especialidad): ?>
          <tr class="<?= $especialidad['activo'] ? '' : 'opacity-70' ?>">
            <td><span class="block font-semibold text-fg"><?= e($especialidad['nombre']) ?></span><span class="text-xs"><?= e($especialidad['descripcion'] ?? '') ?></span></td>
            <td class="numeros text-right font-medium"><?= e(bs($especialidad['importe_consulta'])) ?></td>
            <td class="numeros"><?= e($especialidad['medicos']) ?></td>
            <td><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $especialidad['activo']]) ?></td>
            <td class="text-right"><div class="flex justify-end gap-1">
              <a href="?editar=<?= e($especialidad['id']) ?>" class="btn-icono sm" aria-label="Editar <?= e($especialidad['nombre']) ?>"><i data-lucide="pencil" class="w-4 h-4"></i></a>
              <?php if ($especialidad['activo']): ?>
                <button type="button" class="btn-icono sm" data-abrir-modal="modal-desactivar-especialidad" data-accion="<?= e(url('/especialidades/' . $especialidad['id'] . '/desactivar')) ?>" data-nombre="<?= e($especialidad['nombre']) ?>" aria-label="Desactivar <?= e($especialidad['nombre']) ?>"><i data-lucide="circle-off" class="w-4 h-4"></i></button>
              <?php else: ?>
                <form method="post" action="<?= e(url('/especialidades/' . $especialidad['id'] . '/activar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-icono sm" aria-label="Reactivar <?= e($especialidad['nombre']) ?>"><i data-lucide="circle-check" class="w-4 h-4"></i></button></form>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<div id="modal-especialidad" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-especialidad-titulo" hidden<?= $abrirNueva ? ' data-abrir-al-cargar' : '' ?>>
  <div class="modal-panel">
    <form method="post" action="<?= e(url('/especialidades')) ?>" data-validar novalidate>
      <?= csrf_campo() ?>
      <div class="flex items-start justify-between gap-4 mb-5">
        <h2 id="modal-especialidad-titulo" class="text-lg font-semibold">Nueva especialidad</h2>
        <button type="button" class="btn-icono sm" data-cerrar-modal aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <?= $campos([], $editar ? [] : $anterior, $editar ? [] : $errores, 'nueva') ?>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <button type="button" class="btn-fantasma" data-cerrar-modal>Cancelar</button>
        <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar</button>
      </div>
    </form>
  </div>
</div>

<?php if ($editar): ?>
<div id="modal-editar-especialidad" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-editar-especialidad-titulo" hidden data-abrir-al-cargar>
  <div class="modal-panel">
    <form method="post" action="<?= e(url('/especialidades/' . $editar['id'])) ?>" data-validar novalidate>
      <?= csrf_campo() ?>
      <?= metodo_campo('PUT') ?>
      <div class="flex items-start justify-between gap-4 mb-5">
        <h2 id="modal-editar-especialidad-titulo" class="text-lg font-semibold">Editar especialidad</h2>
        <a href="<?= e(url('/especialidades')) ?>" class="btn-icono sm" aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></a>
      </div>
      <?= $campos($editar, $anterior, $errores, 'editar') ?>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <a href="<?= e(url('/especialidades')) ?>" class="btn-fantasma">Cancelar</a>
        <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar cambios</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?= parcial('modal_confirmar', [
    'id' => 'modal-desactivar-especialidad', 'titulo' => '¿Desactivar {nombre}?', 'icono' => 'circle-off', 'boton' => 'Desactivar',
    'texto' => 'Dejará de ofrecerse en nuevas reservas. Las citas y asignaciones de médicos existentes se conservan.',
]) ?>
