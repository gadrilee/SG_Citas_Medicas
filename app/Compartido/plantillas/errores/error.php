<?php
/** @var int $codigo @var string $titulo @var string $mensaje @var string|null $detalle */
?>
<section class="glass max-w-xl w-full p-8 md:p-12 text-center flex flex-col items-center gap-4">
  <div class="self-end -mt-4 -mr-4"><?= parcial('boton_tema') ?></div>
  <img src="<?= e(recurso('img/ilustracion-error.jpg')) ?>" alt="" width="176" height="176" class="foto-redonda w-44 h-44">
  <p class="font-display text-6xl font-bold texto-acento"><?= e($codigo) ?></p>
  <h1 class="titulo-pagina"><?= e($titulo) ?></h1>
  <p class="texto max-w-sm"><?= e($mensaje) ?></p>
  <?php if ($detalle): ?>
    <pre class="glass w-full overflow-x-auto p-4 text-left text-xs text-danger"><?= e($detalle) ?></pre>
  <?php endif; ?>
  <div class="flex flex-wrap justify-center gap-2 mt-2">
    <a href="<?= e(url(usuario_actual() ? '/panel' : '/')) ?>" class="btn-primario"><?= icono('house') ?>Volver al inicio</a>
    <?php if ($codigo === 403 && !usuario_actual()): ?>
      <a href="<?= e(url('/login')) ?>" class="btn-secundario">Iniciar sesión</a>
    <?php endif; ?>
  </div>
</section>
