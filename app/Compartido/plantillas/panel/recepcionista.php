<?php
/** Panel de recepción. @var array $usuario @var string $saludo @var array $indicadores @var array $proximas */
?>
<div class="grid gap-6 lg:grid-cols-3 mb-8">
  <div class="lg:col-span-2 flex flex-col justify-center">
    <p class="sobretitulo"><?= e(fecha_corta(date('Y-m-d'))) ?></p>
    <h1 class="titulo-pagina"><?= e($saludo) ?>, <?= e($usuario['nombres']) ?></h1>
    <p class="texto">Hoy hay <?= e($indicadores['hoy'] ?? 0) ?> citas programadas.</p>
  </div>
  <a href="<?= e(url('/pacientes/nuevo')) ?>" class="glass-card interactiva tarjeta-destacada flex items-center gap-4">
    <span class="icono-sobre-acento"><i data-lucide="user-plus" class="w-7 h-7"></i></span>
    <span><span class="block text-xl font-bold">Nuevo paciente</span><span class="block text-sm opacity-90">Registro presencial</span></span>
    <i data-lucide="arrow-right" class="w-5 h-5 ml-auto"></i>
  </a>
</div>

<section aria-label="Indicadores del día" class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-8">
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-days"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['hoy'] ?? 0) ?></p><p class="text-sm text-muted">Citas de hoy</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="user-check"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['hoy_confirmadas'] ?? 0) ?></p><p class="text-sm text-muted">Asistencias confirmadas</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="hourglass"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['vigentes'] ?? 0) ?></p><p class="text-sm text-muted">Citas por atender</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="wallet"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['pagos_pendientes'] ?? 0) ?></p><p class="text-sm text-muted">Pagos pendientes</p></div>
</section>

<section class="glass tabla-contenedor" aria-labelledby="t-proximas">
  <div class="flex flex-wrap items-center justify-between gap-3 p-6 pb-2">
    <div><h2 id="t-proximas" class="font-semibold">Próximas citas</h2><p class="text-sm text-subtle">Programadas y confirmadas, ordenadas por fecha</p></div>
    <a href="<?= e(url('/pacientes')) ?>" class="btn-secundario btn-sm"><?= icono('users-round') ?>Pacientes</a>
  </div>
  <?php if ($proximas): ?>
    <?= parcial('tabla_citas', ['citas' => $proximas, 'con_paciente' => true, 'con_medico' => true, 'acciones' => true]) ?>
  <?php else: ?>
    <?= parcial('estado_vacio', ['icono' => 'calendar-check', 'titulo' => 'No hay citas próximas']) ?>
  <?php endif; ?>
</section>
