<?php
/**
 * @var string      $titulo
 * @var string|null $texto
 * @var string|null $icono
 * @var string|null $boton  texto del botón
 * @var string|null $ruta   destino del botón
 */
?>
<div class="estado-vacio">
  <span class="icono-vacio"><i data-lucide="<?= e($icono ?? 'inbox') ?>"></i></span>
  <p class="text-lg font-semibold"><?= e($titulo) ?></p>
  <?php if (!empty($texto)): ?><p class="texto text-sm max-w-sm"><?= e($texto) ?></p><?php endif; ?>
  <?php if (!empty($boton)): ?><a href="<?= e(url($ruta)) ?>" class="btn-primario mt-2"><?= icono('plus') ?><?= e($boton) ?></a><?php endif; ?>
</div>
