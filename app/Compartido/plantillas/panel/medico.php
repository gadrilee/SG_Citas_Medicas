<?php
/** Panel del médico. @var array $usuario @var string $saludo @var array $indicadores @var array $proximas */
$foto = foto_medico($usuario['codigo_medico'] ?? null);
?>
<div class="glass-card mb-8 flex flex-col sm:flex-row sm:items-center gap-5">
  <?php if ($foto): ?>
    <img src="<?= e(recurso($foto)) ?>" alt="" class="w-20 h-20 rounded-2xl object-cover">
  <?php else: ?>
    <span class="avatar avatar-lg" aria-hidden="true"><?= e(iniciales($usuario['nombres'] . ' ' . $usuario['apellidos'])) ?></span>
  <?php endif; ?>
  <div>
    <p class="sobretitulo"><?= e(fecha_corta(date('Y-m-d'))) ?></p>
    <h1 class="titulo-pagina"><?= e($saludo) ?>, <?= e($usuario['nombres']) ?></h1>
    <p class="texto">Tienes <?= e($indicadores['hoy'] ?? 0) ?> citas hoy y <?= e($indicadores['vigentes'] ?? 0) ?> pendientes en tu agenda.</p>
  </div>
</div>

<section aria-label="Indicadores" class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-8">
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-days"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['hoy'] ?? 0) ?></p><p class="text-sm text-muted">Citas de hoy</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-clock"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['vigentes'] ?? 0) ?></p><p class="text-sm text-muted">Pendientes</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="clipboard-check"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['atendidas_mes'] ?? 0) ?></p><p class="text-sm text-muted">Atendidas este mes</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="user-x"></i></span><p class="numero-kpi mt-3"><?= e($indicadores['inasistencias_mes'] ?? 0) ?></p><p class="text-sm text-muted">Inasistencias del mes</p></div>
</section>

<section class="glass tabla-contenedor" aria-labelledby="t-agenda">
  <div class="p-6 pb-2"><h2 id="t-agenda" class="font-semibold">Mis próximas citas</h2><p class="text-sm text-subtle">Solo ves a los pacientes asignados a tu agenda</p></div>
  <?php if ($proximas): ?>
    <?= parcial('tabla_citas', ['citas' => $proximas, 'con_paciente' => true]) ?>
  <?php else: ?>
    <?= parcial('estado_vacio', ['icono' => 'calendar-check', 'titulo' => 'Sin citas próximas']) ?>
  <?php endif; ?>
</section>
