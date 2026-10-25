<?php /** Sitio público: navbar flotante + contenido + pie. @var string $contenido */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
<?= parcial('head', ['titulo' => $titulo ?? '']) ?>
</head>
<body>
<a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] btn-primario">Saltar al contenido</a>
<?= parcial('navbar_publica') ?>
<main id="contenido">
<?= parcial('flash', ['clase' => 'space-y-3 max-w-6xl mx-auto px-6 mt-6 empty:mt-0']) ?>
<?= $contenido ?>
</main>
<?= parcial('pie_publico') ?>
<div id="toasts" aria-live="polite" role="status"></div>
<?= parcial('scripts') ?>
</body>
</html>
