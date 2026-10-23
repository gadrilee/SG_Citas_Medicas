<?php
$enlaces = [
    ['Inicio', '/'],
    ['Especialidades', '/#especialidades'],
    ['Médicos', '/#medicos'],
    ['Contacto', '/#contacto'],
];
$sesion = usuario_actual();
?>
<header class="navbar-publica">
  <div class="max-w-6xl mx-auto">
    <nav class="glass-pill flex items-center justify-between gap-3" aria-label="Principal">
      <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5 shrink-0" aria-label="Vida Salud, inicio"><?= parcial('logo') ?></a>
      <div class="hidden md:flex items-center gap-1">
        <?php foreach ($enlaces as $i => [$texto, $ruta]): ?>
          <a href="<?= e($ruta) ?>" class="enlace-nav<?= $i === 0 ? ' activo' : '' ?>"><?= e($texto) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="flex items-center gap-1.5">
        <?= parcial('boton_tema') ?>
        <?php if ($sesion): ?>
          <a href="<?= e(url('/panel')) ?>" class="btn-primario hidden sm:inline-flex"><?= icono('layout-dashboard') ?>Mi panel</a>
        <?php else: ?>
          <a href="<?= e(url('/login')) ?>" class="btn-secundario hidden sm:inline-flex">Iniciar sesión</a>
          <a href="<?= e(url('/citas/nueva')) ?>" class="btn-primario hidden sm:inline-flex"><?= icono('calendar-plus') ?>Agendar cita</a>
        <?php endif; ?>
        <button type="button" class="btn-icono md:hidden" data-alternar-menu="menu-movil" aria-controls="menu-movil" aria-expanded="false" aria-label="Abrir menú">
          <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
      </div>
    </nav>
    <div id="menu-movil" class="glass menu-movil md:hidden" hidden>
      <div class="flex flex-col gap-1">
        <?php foreach ($enlaces as [$texto, $ruta]): ?>
          <a href="<?= e($ruta) ?>" class="enlace-nav"><?= e($texto) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="grid grid-cols-2 gap-2 mt-4">
        <?php if ($sesion): ?>
          <a href="<?= e(url('/panel')) ?>" class="btn-primario col-span-2">Mi panel</a>
        <?php else: ?>
          <a href="<?= e(url('/login')) ?>" class="btn-secundario">Iniciar sesión</a>
          <a href="<?= e(url('/citas/nueva')) ?>" class="btn-primario">Agendar cita</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>
