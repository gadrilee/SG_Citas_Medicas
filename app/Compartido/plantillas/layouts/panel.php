<?php
/**
 * Panel interno: sidebar por perfil + topbar + contenido.
 * @var string      $contenido
 * @var string      $titulo
 * @var string|null $migas  módulo mostrado sobre el título en la topbar
 */
$usuario = usuario_actual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= parcial('head', ['titulo' => $titulo ?? '']) ?>
</head>
<body>
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] btn-primario">Saltar al contenido</a>
<?= parcial('sidebar', ['perfil' => $usuario['perfil']]) ?>
<div id="fondo-sidebar" class="fondo-sidebar" hidden></div>
<div class="contenido-panel">
<?= parcial('topbar', ['usuario' => $usuario, 'titulo' => $titulo ?? '', 'migas' => $migas ?? null]) ?>
<main id="contenido" class="flex-1 w-full max-w-6xl mx-auto px-1 sm:px-3 py-6 md:py-8">
<?= parcial('flash') ?>
<?= $contenido ?>
</main>
<?= parcial('pie') ?>
</div>
<div id="toasts" aria-live="polite" role="status"></div>
<?= parcial('scripts') ?>
</body>
</html>
