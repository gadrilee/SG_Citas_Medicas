<?php
/**
 * @var array       $usuario  sesión actual
 * @var string      $titulo
 * @var string|null $migas
 */
$nombre = trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
$foto = $usuario['perfil'] === 'medico' ? foto_medico($usuario['codigo_medico'] ?? null) : null;
?>
<header class="topbar glass flex items-center gap-2">
  <button type="button" class="btn-icono btn-menu-panel" data-alternar-menu="sidebar" aria-controls="sidebar" aria-expanded="false" aria-label="Abrir menú">
    <i data-lucide="menu" class="w-5 h-5"></i>
  </button>
  <div class="min-w-0 mr-auto">
    <?php if (!empty($migas)): ?><p class="text-xs text-subtle truncate"><?= e($migas) ?></p><?php endif; ?>
    <p class="font-semibold truncate"><?= e($titulo) ?></p>
  </div>
  <?= parcial('boton_tema') ?>
  <div class="relative">
    <button type="button" class="flex items-center gap-2.5 rounded-full pl-1 pr-2 py-1 hover:bg-accent-soft transition" data-desplegable="menu-usuario" aria-haspopup="true" aria-expanded="false">
      <?php if ($foto): ?>
        <span class="avatar avatar-sm"><img src="<?= e(recurso($foto)) ?>" alt=""></span>
      <?php else: ?>
        <span class="avatar avatar-sm" aria-hidden="true"><?= e(iniciales($nombre)) ?></span>
      <?php endif; ?>
      <span class="hidden sm:block text-left leading-tight">
        <span class="block text-sm font-semibold"><?= e($nombre) ?></span>
        <span class="block text-xs text-subtle"><?= e(nombre_perfil($usuario['perfil'])) ?></span>
      </span>
      <i data-lucide="chevron-down" class="w-4 h-4 text-subtle hidden sm:block"></i>
    </button>
    <div id="menu-usuario" class="menu-desplegable glass" hidden>
      <a href="<?= e(url('/mi-cuenta')) ?>" class="opcion-menu"><i data-lucide="user-cog"></i>Mi cuenta</a>
      <hr class="separador my-1">
      <form method="post" action="<?= e(url('/logout')) ?>">
        <?= csrf_campo() ?>
        <button type="submit" class="opcion-menu text-danger"><i data-lucide="log-out"></i>Cerrar sesión</button>
      </form>
    </div>
  </div>
</header>
