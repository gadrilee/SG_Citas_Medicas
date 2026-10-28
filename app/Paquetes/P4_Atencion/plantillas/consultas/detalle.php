<?php
/**
 * CU17 · Consulta ya registrada (solo lectura): una cita tiene una única consulta.
 * @var array $cita
 * @var array $consulta      Econsulta::deCita
 * @var array $tratamientos  Etratamiento::deConsulta
 * @var array $estudios      Eestudio_laboratorio::deConsulta
 * @var array $paciente
 * @var array $ficha
 * @var array $consultas
 * @var array $resultados
 * @var array $campos
 */
?>
<a href="<?= e(url('/agenda?fecha=' . substr($cita['inicio'], 0, 10))) ?>" class="btn-fantasma btn-sm -ml-3 mb-4"><?= icono('arrow-left') ?>Volver a la agenda</a>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU17 · Consulta · <span class="font-mono"><?= e($cita['codigo']) ?></span></p>
    <h1 class="titulo-pagina">Consulta registrada</h1>
    <p class="texto"><?= e(fecha_corta($cita['inicio'])) ?> · <?= e(rango_horas($cita['inicio'], $cita['fin'])) ?> · <?= e($cita['especialidad']) ?> · registrada a las <?= e(hora_corta($consulta['fecha_hora'])) ?></p>
  </div>
  <?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
  <div class="lg:col-span-2 flex flex-col gap-6">
    <section class="glass-card" aria-labelledby="t-atencion">
      <h2 id="t-atencion" class="font-semibold flex items-center gap-2 mb-5"><?= icono('stethoscope', 'w-5 h-5 texto-acento') ?>Atención</h2>
      <dl class="grid gap-5 text-sm">
        <div><dt class="etiqueta">Diagnóstico</dt><dd class="font-semibold text-base"><?= e($consulta['diagnostico'] ?: '—') ?></dd></div>
        <div><dt class="etiqueta">Observaciones</dt><dd class="whitespace-pre-line"><?= e($consulta['observaciones'] ?: '—') ?></dd></div>
      </dl>
    </section>

    <section class="glass-card" aria-labelledby="t-tratamiento">
      <h2 id="t-tratamiento" class="font-semibold flex items-center gap-2 mb-5"><?= icono('pill', 'w-5 h-5 texto-acento') ?>Tratamiento</h2>
      <?php if (!$tratamientos): ?>
        <p class="text-sm text-subtle">La consulta no incluye indicaciones de tratamiento.</p>
      <?php else: ?>
        <ol class="grid gap-3">
          <?php foreach ($tratamientos as $n => $tratamiento): ?>
            <li class="flex gap-3 text-sm">
              <span class="icono-kpi shrink-0 numeros" style="width:28px;height:28px;font-size:.8rem"><?= $n + 1 ?></span>
              <div>
                <p class="font-medium"><?= e($tratamiento['indicaciones']) ?></p>
                <?php if ($tratamiento['fecha_inicio'] || $tratamiento['fecha_fin']): ?>
                  <p class="text-subtle numeros"><?= e(fecha_corta($tratamiento['fecha_inicio']) ?: '—') ?> → <?= e(fecha_corta($tratamiento['fecha_fin']) ?: 'sin fecha de fin') ?></p>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    </section>

    <section class="glass-card" aria-labelledby="t-estudios">
      <div class="flex items-center justify-between gap-2 mb-5">
        <h2 id="t-estudios" class="font-semibold flex items-center gap-2"><?= icono('flask-conical', 'w-5 h-5 texto-acento') ?>Estudios solicitados</h2>
        <?php if ($estudios && puede('resultados.publicar')): ?>
          <a href="<?= e(url('/estudios?estado=&buscar=' . urlencode($cita['codigo']))) ?>" class="btn-secundario btn-sm"><?= icono('upload') ?>Gestionar resultados</a>
        <?php endif; ?>
      </div>
      <?php if (!$estudios): ?>
        <p class="text-sm text-subtle">No se solicitaron estudios en esta consulta.</p>
      <?php else: ?>
        <ul class="grid gap-3">
          <?php foreach ($estudios as $estudio): ?>
            <li class="flex flex-wrap items-center justify-between gap-2 text-sm">
              <div><p class="font-medium"><?= e($estudio['nombre_estudio']) ?></p><?php if ($estudio['indicaciones']): ?><p class="text-subtle"><?= e($estudio['indicaciones']) ?></p><?php endif; ?></div>
              <?= parcial('badge_estado', ['tipo' => 'estudio', 'estado' => $estudio['estado']]) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>
  </div>

  <?php include __DIR__ . '/_paciente.php'; ?>
</div>
