<?php
/**
 * Panel del paciente.
 * @var array  $usuario
 * @var string $saludo
 * @var array  $proximas
 * @var array  $medicos
 * @var array  $especialidades
 */
$siguiente = $proximas[0] ?? null;
?>
<div class="mb-8">
  <p class="sobretitulo"><?= e(fecha_corta(date('Y-m-d'))) ?></p>
  <h1 class="titulo-pagina"><?= e($saludo) ?>, <?= e($usuario['nombres']) ?></h1>
  <p class="texto">Reserva una nueva cita o revisa tus próximas atenciones.</p>
</div>

<div class="mb-8"><?= parcial('booking_bar', ['especialidades' => $especialidades, 'medicos' => $medicos]) ?></div>

<div class="grid gap-6 lg:grid-cols-3">
  <section class="lg:col-span-1" aria-labelledby="t-siguiente">
    <h2 id="t-siguiente" class="sr-only">Próxima cita</h2>
    <?php if ($siguiente): ?>
      <article class="glass-card tarjeta-destacada flex flex-col gap-4">
        <p class="text-sm opacity-90">Tu próxima cita</p>
        <p class="text-2xl font-bold numeros"><?= e(fecha_corta($siguiente['inicio'])) ?> · <?= e(hora_corta($siguiente['inicio'])) ?></p>
        <div><p class="font-semibold"><?= e($siguiente['medico']) ?></p><p class="text-sm opacity-90"><?= e($siguiente['especialidad']) ?> · <?= e(ucfirst($siguiente['modalidad'])) ?></p></div>
        <p class="font-mono text-sm opacity-90"><?= e($siguiente['codigo']) ?> · <?= e(texto_estado('cita', $siguiente['estado'])) ?></p>
        <a href="<?= e(url('/citas/' . $siguiente['id'] . '/comprobante')) ?>" class="btn-secundario btn-sm self-start" style="color:inherit;border-color:currentColor"><?= icono('receipt-text') ?>Ver comprobante</a>
      </article>
    <?php else: ?>
      <div class="glass"><?= parcial('estado_vacio', ['icono' => 'calendar-plus', 'titulo' => 'Aún no tienes citas', 'texto' => 'Reserva tu primera cita con nuestros especialistas.']) ?></div>
    <?php endif; ?>
  </section>
  <section class="glass tabla-contenedor lg:col-span-2" aria-labelledby="t-mis-citas">
    <div class="p-6 pb-2"><h2 id="t-mis-citas" class="font-semibold">Mis próximas citas</h2></div>
    <?php if ($proximas): ?>
      <?= parcial('tabla_citas', ['citas' => $proximas, 'con_medico' => true, 'acciones' => true]) ?>
    <?php else: ?>
      <p class="texto text-sm px-6 pb-6">No tienes citas programadas.</p>
    <?php endif; ?>
  </section>
</div>
