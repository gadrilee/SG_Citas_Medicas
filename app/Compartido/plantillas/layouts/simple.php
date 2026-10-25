<?php
/**
 * Pantallas sin menú: iniciar sesión, registro y errores.
 * @var string      $contenido
 * @var string|null $clase_main
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= parcial('head', ['titulo' => $titulo ?? '']) ?>
</head>
<body>
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] btn-primario">Saltar al contenido</a>
<main id="contenido" class="<?= e($clase_main ?? '') ?>">
<?= $contenido ?>
</main>
<div id="toasts" aria-live="polite" role="status"></div>
<?= parcial('scripts') ?>
</body>
</html>
