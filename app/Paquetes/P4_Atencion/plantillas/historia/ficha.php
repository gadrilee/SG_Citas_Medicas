<?php
/**
 * CU16 · Historia clínica. Médico y recepción la editan; el paciente la ve en solo lectura (A1).
 * @var array $paciente    Epaciente::buscarDetalle
 * @var array $ficha       Eficha_medica::conResponsable ([] si no existe)
 * @var array $consultas   Econsulta::dePaciente (solo médico y paciente)
 * @var bool  $editable
 * @var bool  $propia      la ve el propio paciente
 * @var array $tiposSangre
 * @var array $campos      [columna => etiqueta]
 * @var array $errores
 * @var array $anterior
 */
$nombre = $paciente['nombres'] . ' ' . $paciente['apellidos'];
$esMedico = (usuario_actual()['perfil'] ?? '') === 'medico';
$volver = $esMedico ? ['/agenda', 'Volver a mi agenda'] : ['/pacientes/' . $paciente['id'], 'Volver a la ficha del paciente'];
$v = static fn (string $campo): string => valor($anterior, $ficha, $campo);
?>
<?php if (!$propia): ?>
  <a href="<?= e(url($volver[0])) ?>" class="btn-fantasma btn-sm -ml-3 mb-4"><?= icono('arrow-left') ?><?= e($volver[1]) ?></a>
<?php endif; ?>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU16 · Información clínica</p>
    <h1 class="titulo-pagina"><?= $propia ? 'Mi historia clínica' : 'Historia clínica' ?></h1>
    <p class="texto"><?= $propia ? 'Tus antecedentes y atenciones registradas por la clínica. Si algún dato no es correcto, avísalo en recepción.' : 'Antecedentes, alergias y tratamientos que el equipo debe conocer antes de atender.' ?></p>
  </div>
</div>

<section class="glass-card mb-6 flex flex-col md:flex-row md:items-center gap-5" aria-label="Paciente">
  <span class="avatar avatar-lg" aria-hidden="true"><?= e(iniciales($nombre)) ?></span>
  <div class="flex-1 min-w-0">
    <p class="text-xl font-bold"><?= e($nombre) ?></p>
    <p class="texto text-sm mt-1"><span class="font-mono"><?= e($paciente['codigo']) ?></span> · CI <span class="numeros"><?= e($paciente['ci'] ?: '—') ?></span> · <?= e(edad($paciente['fecha_nacimiento'])) ?><?= $paciente['sexo'] ? ' · ' . e($paciente['sexo']) : '' ?></p>
    <div class="flex flex-wrap gap-2 mt-3">
      <?php if (!empty($ficha['alergias'])): ?>
        <span class="insignia insignia-peligro sin-punto"><?= icono('triangle-alert', 'w-3.5 h-3.5') ?>Alergia: <?= e($ficha['alergias']) ?></span>
      <?php endif; ?>
      <span class="insignia insignia-neutra">Grupo <?= e(($ficha['tipo_sangre'] ?? '') ?: 'no registrado') ?></span>
    </div>
  </div>
  <?php if (!empty($ficha['fecha_actualizacion'])): ?>
    <div class="text-sm md:text-right">
      <p class="etiqueta mb-1">Última actualización</p>
      <p class="font-medium numeros"><?= e(fecha_hora($ficha['fecha_actualizacion'])) ?></p>
      <p class="text-subtle">por <?= e($ficha['actualizada_por_nombre']) ?> (<span class="font-mono"><?= e($ficha['actualizada_por_usuario']) ?></span>)</p>
    </div>
  <?php endif; ?>
</section>

<div class="grid gap-6 lg:grid-cols-3">
  <div class="lg:col-span-2">
    <?php if ($editable): ?>
      <form method="post" action="<?= e(url('/pacientes/' . $paciente['id'] . '/historia')) ?>" class="glass-card" data-validar novalidate>
        <?= csrf_campo() ?>
        <h2 class="font-semibold flex items-center gap-2 mb-5"><?= icono('file-heart', 'w-5 h-5 texto-acento') ?>Datos clínicos</h2>
        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label for="tipo_sangre" class="etiqueta">Tipo de sangre</label>
            <select id="tipo_sangre" name="tipo_sangre" class="campo<?= marca_error($errores, 'tipo_sangre') ?>">
              <option value="">No registrado</option>
              <?php foreach ($tiposSangre as $tipo): ?>
                <option<?= $v('tipo_sangre') === $tipo ? ' selected' : '' ?>><?= e($tipo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= error_campo($errores, 'tipo_sangre') ?>
          </div>
          <?php foreach ($campos as $campo => $etiqueta): ?>
            <div class="sm:col-span-2">
              <label for="<?= e($campo) ?>" class="etiqueta"><?= e($etiqueta) ?></label>
              <textarea id="<?= e($campo) ?>" name="<?= e($campo) ?>" class="campo<?= marca_error($errores, $campo) ?>" maxlength="2000" rows="2" placeholder="Sin datos registrados"><?= e($v($campo)) ?></textarea>
              <?= error_campo($errores, $campo) ?>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="flex justify-end mt-6">
          <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar historia clínica</button>
        </div>
      </form>
    <?php else: ?>
      <section class="glass-card" aria-labelledby="t-antecedentes">
        <h2 id="t-antecedentes" class="font-semibold flex items-center gap-2 mb-5"><?= icono('file-heart', 'w-5 h-5 texto-acento') ?>Antecedentes</h2>
        <dl class="grid gap-5 sm:grid-cols-2 text-sm">
          <div><dt class="etiqueta">Tipo de sangre</dt><dd class="font-medium"><?= e(($ficha['tipo_sangre'] ?? '') ?: 'No registrado') ?></dd></div>
          <?php foreach ($campos as $campo => $etiqueta): ?>
            <div class="sm:col-span-2"><dt class="etiqueta"><?= e($etiqueta) ?></dt><dd class="font-medium whitespace-pre-line"><?= e(($ficha[$campo] ?? '') ?: 'Sin datos registrados') ?></dd></div>
          <?php endforeach; ?>
        </dl>
      </section>
    <?php endif; ?>
  </div>

  <aside class="flex flex-col gap-6">
    <?php if ($consultas): ?>
      <section class="glass-card" aria-labelledby="t-consultas">
        <h2 id="t-consultas" class="font-semibold mb-4"><?= $propia ? 'Últimas atenciones' : 'Consultas anteriores' ?></h2>
        <ol class="grid gap-4">
          <?php foreach ($consultas as $consulta): ?>
            <li class="text-sm">
              <p class="flex items-center justify-between gap-2"><span class="font-medium"><?= e($consulta['diagnostico'] ?: 'Sin diagnóstico') ?></span><span class="numeros text-subtle shrink-0"><?= e(fecha_corta($consulta['fecha_hora'])) ?></span></p>
              <p class="text-muted"><?= e($consulta['medico']) ?> · <?= e($consulta['especialidad']) ?></p>
              <?php if ($consulta['tratamientos']): ?>
                <p class="text-subtle mt-1 whitespace-pre-line"><?= icono('pill', 'w-3.5 h-3.5 inline') ?> <?= e($consulta['tratamientos']) ?></p>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>
    <?php elseif ($propia || $esMedico): ?>
      <section class="glass-card"><p class="font-semibold mb-1">Sin consultas registradas</p><p class="text-sm text-muted">Las atenciones aparecerán aquí después de cada consulta.</p></section>
    <?php endif; ?>
    <?php if ($propia): ?>
      <a href="<?= e(url('/mis-resultados')) ?>" class="btn-secundario"><?= icono('flask-conical') ?>Ver mis resultados</a>
    <?php elseif (!$esMedico): ?>
      <div class="alerta"><i data-lucide="lock"></i><p>Las consultas y diagnósticos solo los ve el médico tratante.</p></div>
    <?php endif; ?>
  </aside>
</div>
