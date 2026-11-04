<?php
/**
 * CU17 paso 2 · Panel lateral: datos del paciente, su historia clínica, consultas anteriores y
 * resultados publicados (CU19 paso 7). Lo incluyen registrar.php y detalle.php.
 * @var array $paciente
 * @var array $ficha       [] si la cita no es de hoy (el médico solo ve la historia de sus pacientes del día)
 * @var array $consultas
 * @var array $resultados
 * @var array $campos
 */
$nombrePaciente = $paciente['nombres'] . ' ' . $paciente['apellidos'];
$conHistoria = $ficha !== [];
?>
<aside class="flex flex-col gap-6">
  <section class="glass-card" aria-labelledby="t-paciente">
    <div class="flex items-center gap-3 mb-4">
      <span class="avatar avatar-md" aria-hidden="true"><?= e(iniciales($nombrePaciente)) ?></span>
      <div class="min-w-0">
        <h2 id="t-paciente" class="font-semibold leading-snug"><?= e($nombrePaciente) ?></h2>
        <p class="text-sm text-muted"><span class="font-mono"><?= e($paciente['codigo']) ?></span> · <?= e(edad($paciente['fecha_nacimiento'])) ?></p>
      </div>
    </div>
    <?php if ($conHistoria): ?>
      <div class="flex flex-wrap gap-2 mb-4">
        <?php if (!empty($ficha['alergias'])): ?>
          <span class="insignia insignia-peligro sin-punto"><?= icono('triangle-alert', 'w-3.5 h-3.5') ?>Alergia: <?= e($ficha['alergias']) ?></span>
        <?php endif; ?>
        <span class="insignia insignia-neutra">Grupo <?= e(($ficha['tipo_sangre'] ?? '') ?: '—') ?></span>
      </div>
      <dl class="grid gap-3 text-sm">
        <?php foreach ($campos as $campo => $etiqueta): ?>
          <?php if ($campo !== 'alergias' && !empty($ficha[$campo])): ?>
            <div><dt class="etiqueta"><?= e($etiqueta) ?></dt><dd class="whitespace-pre-line"><?= e($ficha[$campo]) ?></dd></div>
          <?php endif; ?>
        <?php endforeach; ?>
      </dl>
      <a href="<?= e(url('/pacientes/' . $paciente['id'] . '/historia')) ?>" class="btn-secundario btn-sm mt-4"><?= icono('file-heart') ?>Historia clínica completa</a>
    <?php else: ?>
      <p class="text-sm text-subtle">La historia clínica solo está disponible el día de la cita.</p>
    <?php endif; ?>
  </section>

  <?php if ($resultados): ?>
    <section class="glass-card" aria-labelledby="t-resultados">
      <h2 id="t-resultados" class="font-semibold mb-4">Resultados publicados</h2>
      <ul class="grid gap-3 text-sm">
        <?php foreach ($resultados as $estudio): ?>
          <li>
            <p class="flex justify-between gap-2"><span class="font-medium"><?= e($estudio['nombre_estudio']) ?></span><span class="numeros text-subtle"><?= e(fecha_corta($estudio['publicado_en'])) ?></span></p>
            <?php if ($estudio['resultado_texto']): ?><p class="text-muted whitespace-pre-line"><?= e($estudio['resultado_texto']) ?></p><?php endif; ?>
            <a href="<?= e(url('/estudios/' . $estudio['id'] . '/resultado')) ?>" class="texto-acento text-sm inline-flex items-center gap-1"><?= icono('file-text') ?>Ver informe</a>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php if ($consultas): ?>
    <section class="glass-card" aria-labelledby="t-anteriores">
      <h2 id="t-anteriores" class="font-semibold mb-4">Consultas anteriores</h2>
      <ol class="grid gap-4">
        <?php foreach (array_slice($consultas, 0, 5) as $anteriorConsulta): ?>
          <li class="text-sm">
            <p class="flex justify-between gap-2"><span class="font-medium"><?= e($anteriorConsulta['diagnostico'] ?: 'Sin diagnóstico') ?></span><span class="numeros text-subtle shrink-0"><?= e(fecha_corta($anteriorConsulta['fecha_hora'])) ?></span></p>
            <p class="text-muted"><?= e($anteriorConsulta['medico']) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
  <?php endif; ?>
</aside>
