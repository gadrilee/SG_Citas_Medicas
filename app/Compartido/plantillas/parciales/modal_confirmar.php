<?php
/**
 * Modal de confirmación (POST). El botón que lo abre puede fijar la ruta con data-accion y el nombre
 * mostrado con data-nombre (ver publico/js/app.js), así un solo modal sirve a toda una tabla.
 * @var string      $id
 * @var string      $titulo   puede incluir el marcador {nombre}
 * @var string|null $texto
 * @var string|null $accion
 * @var string|null $boton
 * @var string|null $tipo     peligro | primario
 * @var string|null $icono
 * @var bool|null   $motivo   pide un motivo obligatorio
 * @var string|null $aviso
 */
$tipo ??= 'peligro';
$tituloHtml = str_replace('{nombre}', '<span data-campo-modal="nombre"></span>', e($titulo));
?>
<div id="<?= e($id) ?>" class="modal" role="dialog" aria-modal="true" aria-labelledby="<?= e($id) ?>-titulo" hidden>
  <div class="modal-panel max-w-md">
    <form method="post" action="<?= e(url($accion ?? '/')) ?>" data-validar>
      <?= csrf_campo() ?>
      <div class="flex items-start gap-4">
        <span class="icono-cuadrado <?= $tipo === 'peligro' ? 'peligro' : '' ?>"><i data-lucide="<?= e($icono ?? 'triangle-alert') ?>" class="w-6 h-6"></i></span>
        <div class="flex-1 min-w-0">
          <h2 id="<?= e($id) ?>-titulo" class="text-lg font-semibold"><?= $tituloHtml ?></h2>
          <?php if (!empty($texto)): ?><p class="texto text-sm mt-1"><?= e($texto) ?></p><?php endif; ?>
        </div>
        <button type="button" class="btn-icono sm -mt-1 -mr-1" data-cerrar-modal aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <?php if (!empty($aviso)): ?>
        <div class="alerta alerta-aviso mt-5"><i data-lucide="info"></i><p><?= e($aviso) ?></p></div>
      <?php endif; ?>
      <?php if (!empty($motivo)): ?>
        <div class="mt-5">
          <label for="<?= e($id) ?>-motivo" class="etiqueta">Motivo <span class="text-danger">*</span></label>
          <textarea id="<?= e($id) ?>-motivo" name="motivo" class="campo" required minlength="5" placeholder="Describe brevemente el motivo…"></textarea>
          <p class="error-campo" data-error="motivo"></p>
        </div>
      <?php endif; ?>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <button type="button" class="btn-fantasma" data-cerrar-modal>Volver</button>
        <button type="submit" class="<?= $tipo === 'peligro' ? 'btn-peligro solido' : 'btn-primario' ?>"><?= e($boton ?? 'Confirmar') ?></button>
      </div>
    </form>
  </div>
</div>
