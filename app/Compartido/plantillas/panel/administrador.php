<?php
/** Panel del administrador. @var array $usuario @var string $saludo @var array $indicadores @var array $acciones */
$kpis = [
    ['calendar-days', $indicadores['hoy'] ?? 0, 'Citas de hoy'],
    ['calendar-clock', $indicadores['vigentes'] ?? 0, 'Citas programadas'],
    ['calendar-x', $indicadores['canceladas_mes'] ?? 0, 'Canceladas del mes'],
    ['user-x', $indicadores['inasistencias_mes'] ?? 0, 'Inasistencias del mes'],
    ['users-round', $indicadores['pacientes'] ?? 0, 'Pacientes activos'],
    ['stethoscope', $indicadores['medicos'] ?? 0, 'Médicos activos'],
];
$accesos = [
    ['users', 'Usuarios', '/usuarios', 'usuarios.gestionar'],
    ['stethoscope', 'Médicos', '/medicos', 'medicos.gestionar'],
    ['scroll-text', 'Bitácora', '/bitacora', 'bitacora.consultar'],
    ['shield-check', 'Perfiles', '/perfiles', 'perfiles.gestionar'],
];
?>
<div class="mb-8">
  <p class="sobretitulo"><?= e(fecha_corta(date('Y-m-d'))) ?></p>
  <h1 class="titulo-pagina"><?= e($saludo) ?>, <?= e($usuario['nombres']) ?></h1>
  <p class="texto">Resumen general de la clínica.</p>
</div>

<section aria-label="Indicadores" class="grid gap-4 grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 mb-8">
  <?php foreach ($kpis as [$icono, $valor, $texto]): ?>
    <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="<?= e($icono) ?>"></i></span><p class="numero-kpi mt-3"><?= e($valor) ?></p><p class="text-sm text-muted"><?= e($texto) ?></p></div>
  <?php endforeach; ?>
</section>

<div class="grid gap-6 lg:grid-cols-3">
  <section class="glass tabla-contenedor lg:col-span-2" aria-labelledby="t-acciones">
    <div class="flex flex-wrap items-center justify-between gap-3 p-6 pb-2">
      <div><h2 id="t-acciones" class="font-semibold">Últimas acciones</h2><p class="text-sm text-subtle">Bitácora del sistema</p></div>
      <a href="<?= e(url('/bitacora')) ?>" class="btn-secundario btn-sm"><?= icono('scroll-text') ?>Ver bitácora</a>
    </div>
    <div class="px-2 pb-2 md:px-0">
      <table class="tabla">
        <thead><tr><th scope="col">Fecha y hora</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Tabla</th></tr></thead>
        <tbody>
          <?php foreach ($acciones as $accion): ?>
            <tr><td class="numeros"><?= e(fecha_hora($accion['fecha_hora'])) ?></td><td class="font-mono"><?= e($accion['nombre_usuario']) ?></td><td><span class="insignia insignia-acento"><?= e($accion['accion']) ?></span></td><td class="font-mono text-muted"><?= e($accion['entidad']) ?> #<?= e($accion['registro_id']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <section class="grid gap-4 grid-cols-2 h-fit" aria-label="Accesos rápidos">
    <?php foreach ($accesos as [$icono, $texto, $ruta, $privilegio]): ?>
      <?php if (puede($privilegio)): ?>
        <a href="<?= e(url($ruta)) ?>" class="glass-card pequena interactiva flex flex-col gap-3"><span class="icono-cuadrado"><i data-lucide="<?= e($icono) ?>" class="w-6 h-6"></i></span><span class="font-medium"><?= e($texto) ?></span></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </section>
</div>
