<?php
/**
 * CU19 · «Mis resultados»: solo los estudios PUBLICADOS del propio paciente (E3).
 * @var array $estudios  Eestudio_laboratorio::publicadosDePaciente
 */
?>
<div class="mb-6">
  <p class="sobretitulo">CU19 · Laboratorio</p>
  <h1 class="titulo-pagina">Mis resultados</h1>
  <p class="texto">Resultados de los estudios que te indicaron. Abre el informe para verlo completo, imprimirlo o descargarlo en PDF.</p>
</div>

<?php if (!$estudios): ?>
  <div class="glass"><?= parcial('estado_vacio', ['icono' => 'flask-conical', 'titulo' => 'Aún no tienes resultados publicados', 'texto' => 'Cuando el médico publique el resultado de un estudio aparecerá aquí.']) ?></div>
<?php else: ?>
  <section class="grid gap-4" aria-label="Resultados publicados">
    <?php foreach ($estudios as $estudio): ?>
      <article class="glass-card flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
          <span class="icono-cuadrado"><i data-lucide="file-check-2"></i></span>
          <div class="flex-1 min-w-0">
            <p class="font-semibold"><?= e($estudio['nombre_estudio']) ?></p>
            <p class="texto text-sm">Solicitado por <?= e($estudio['medico']) ?> · <span class="numeros"><?= e(fecha_corta($estudio['solicitado_en'])) ?></span> · cita <span class="font-mono"><?= e($estudio['cita']) ?></span></p>
          </div>
          <?= parcial('badge_estado', ['tipo' => 'estudio', 'estado' => $estudio['estado']]) ?>
          <a href="<?= e(url('/estudios/' . $estudio['id'] . '/resultado')) ?>" class="btn-primario btn-sm"><?= icono('file-text') ?>Ver informe</a>
        </div>
        <?php if ($estudio['resultado_texto']): ?>
          <div class="rounded-2xl p-4 fondo-acento-suave text-sm whitespace-pre-line"><?= e($estudio['resultado_texto']) ?></div>
        <?php endif; ?>
        <p class="text-xs text-subtle">Publicado el <span class="numeros"><?= e(fecha_hora($estudio['publicado_en'])) ?></span> por <span class="font-mono"><?= e($estudio['publicado_por_usuario']) ?></span></p>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
