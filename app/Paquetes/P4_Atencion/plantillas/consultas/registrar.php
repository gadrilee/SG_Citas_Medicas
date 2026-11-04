<?php
/**
 * CU17 · Registrar consulta y tratamiento, con la sección opcional de CU18 «Solicitar estudios».
 * @var array $cita        Ecita_medica::buscarDetalle
 * @var array $paciente
 * @var array $ficha
 * @var array $consultas
 * @var array $resultados
 * @var array $campos
 * @var array $errores
 * @var array $anterior
 */
$tratamientos = array_values($anterior['tratamientos'] ?? [['indicaciones' => '', 'fecha_inicio' => '', 'fecha_fin' => '']]);
$estudios = array_values($anterior['estudios'] ?? []);
$hoy = date('Y-m-d');
?>
<a href="<?= e(url('/agenda')) ?>" class="btn-fantasma btn-sm -ml-3 mb-4"><?= icono('arrow-left') ?>Volver a mi agenda</a>

<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU17 · Atención · <span class="font-mono"><?= e($cita['codigo']) ?></span></p>
    <h1 class="titulo-pagina">Registrar consulta</h1>
    <p class="texto"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?> · <?= e($cita['especialidad']) ?><?= $cita['motivo'] ? ' · Motivo: ' . e($cita['motivo']) : '' ?></p>
  </div>
  <?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?>
</div>

<div class="grid gap-6 lg:grid-cols-3">
  <form method="post" action="<?= e(url('/citas/' . $cita['id'] . '/consulta')) ?>" class="lg:col-span-2 flex flex-col gap-6" data-validar novalidate>
    <?= csrf_campo() ?>

    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-5"><?= icono('stethoscope', 'w-5 h-5 texto-acento') ?>Atención</legend>
      <div class="grid gap-5">
        <div>
          <label for="observaciones" class="etiqueta">Observaciones</label>
          <textarea id="observaciones" name="observaciones" class="campo<?= marca_error($errores, 'observaciones') ?>" rows="4" maxlength="4000" placeholder="Síntomas, examen físico, signos vitales…"><?= e($anterior['observaciones'] ?? '') ?></textarea>
          <?= error_campo($errores, 'observaciones') ?>
        </div>
        <div>
          <label for="diagnostico" class="etiqueta">Diagnóstico</label>
          <textarea id="diagnostico" name="diagnostico" class="campo<?= marca_error($errores, 'diagnostico') ?>" rows="2" maxlength="2000" placeholder="Ej. Faringitis aguda"><?= e($anterior['diagnostico'] ?? '') ?></textarea>
          <p class="ayuda-campo">Completa al menos las observaciones o el diagnóstico.</p>
          <?= error_campo($errores, 'diagnostico') ?>
        </div>
      </div>
    </fieldset>

    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-1"><?= icono('pill', 'w-5 h-5 texto-acento') ?>Tratamiento</legend>
      <p class="texto text-sm mb-5">Una indicación por fila. Las fechas son opcionales.</p>
      <div id="lista-tratamientos" class="flex flex-col gap-4">
        <?php foreach ($tratamientos as $i => $fila): ?>
          <div class="grid gap-3 sm:grid-cols-[1fr_150px_150px_auto] items-start" data-fila>
            <div>
              <label for="trat-indicaciones-<?= $i ?>" class="etiqueta">Indicaciones</label>
              <input id="trat-indicaciones-<?= $i ?>" name="tratamientos[<?= $i ?>][indicaciones]" class="campo<?= marca_error($errores, "tratamientos[$i][indicaciones]") ?>" maxlength="1000" placeholder="Ibuprofeno 400 mg cada 8 h por 3 días" value="<?= e($fila['indicaciones'] ?? '') ?>">
              <?= error_campo($errores, "tratamientos[$i][indicaciones]") ?>
            </div>
            <div>
              <label for="trat-inicio-<?= $i ?>" class="etiqueta">Inicio</label>
              <input id="trat-inicio-<?= $i ?>" name="tratamientos[<?= $i ?>][fecha_inicio]" type="date" class="campo<?= marca_error($errores, "tratamientos[$i][fecha_inicio]") ?>" value="<?= e($fila['fecha_inicio'] ?? '') ?>">
              <?= error_campo($errores, "tratamientos[$i][fecha_inicio]") ?>
            </div>
            <div>
              <label for="trat-fin-<?= $i ?>" class="etiqueta">Fin</label>
              <input id="trat-fin-<?= $i ?>" name="tratamientos[<?= $i ?>][fecha_fin]" type="date" class="campo<?= marca_error($errores, "tratamientos[$i][fecha_fin]") ?>" value="<?= e($fila['fecha_fin'] ?? '') ?>">
              <?= error_campo($errores, "tratamientos[$i][fecha_fin]") ?>
            </div>
            <button type="button" class="btn-icono sm sm:mt-7" data-quitar-fila aria-label="Quitar indicación"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="text-sm text-subtle" data-vacio-filas<?= $tratamientos ? ' hidden' : '' ?>>Sin tratamiento: se guardarán solo las observaciones y el diagnóstico.</p>
      <button type="button" class="btn-secundario btn-sm mt-4" data-agregar-fila="plantilla-tratamiento" data-destino="lista-tratamientos"><?= icono('plus') ?>Agregar indicación</button>
      <template id="plantilla-tratamiento">
        <div class="grid gap-3 sm:grid-cols-[1fr_150px_150px_auto] items-start" data-fila>
          <div><label for="trat-indicaciones-__i__" class="etiqueta">Indicaciones</label><input id="trat-indicaciones-__i__" name="tratamientos[__i__][indicaciones]" class="campo" maxlength="1000"></div>
          <div><label for="trat-inicio-__i__" class="etiqueta">Inicio</label><input id="trat-inicio-__i__" name="tratamientos[__i__][fecha_inicio]" type="date" class="campo" value="<?= e($hoy) ?>"></div>
          <div><label for="trat-fin-__i__" class="etiqueta">Fin</label><input id="trat-fin-__i__" name="tratamientos[__i__][fecha_fin]" type="date" class="campo"></div>
          <button type="button" class="btn-icono sm sm:mt-7" data-quitar-fila aria-label="Quitar indicación"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
      </template>
    </fieldset>

    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-1"><?= icono('flask-conical', 'w-5 h-5 texto-acento') ?>Estudios de laboratorio <span class="insignia insignia-neutra ml-1">CU18 · opcional</span></legend>
      <p class="texto text-sm mb-5">Cada estudio queda pendiente hasta que publiques su resultado en «Estudios».</p>
      <div id="lista-estudios" class="flex flex-col gap-4">
        <?php foreach ($estudios as $i => $fila): ?>
          <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] items-start" data-fila>
            <div>
              <label for="est-nombre-<?= $i ?>" class="etiqueta">Estudio</label>
              <input id="est-nombre-<?= $i ?>" name="estudios[<?= $i ?>][nombre_estudio]" class="campo<?= marca_error($errores, "estudios[$i][nombre_estudio]") ?>" maxlength="150" list="estudios-frecuentes" value="<?= e($fila['nombre_estudio'] ?? '') ?>">
              <?= error_campo($errores, "estudios[$i][nombre_estudio]") ?>
            </div>
            <div>
              <label for="est-indicaciones-<?= $i ?>" class="etiqueta">Indicaciones</label>
              <input id="est-indicaciones-<?= $i ?>" name="estudios[<?= $i ?>][indicaciones]" class="campo<?= marca_error($errores, "estudios[$i][indicaciones]") ?>" maxlength="1000" placeholder="En ayunas de 8 horas" value="<?= e($fila['indicaciones'] ?? '') ?>">
              <?= error_campo($errores, "estudios[$i][indicaciones]") ?>
            </div>
            <button type="button" class="btn-icono sm sm:mt-7" data-quitar-fila aria-label="Quitar estudio"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="text-sm text-subtle" data-vacio-filas<?= $estudios ? ' hidden' : '' ?>>No se solicitarán estudios.</p>
      <button type="button" class="btn-secundario btn-sm mt-4" data-agregar-fila="plantilla-estudio" data-destino="lista-estudios"><?= icono('plus') ?>Solicitar estudio</button>
      <template id="plantilla-estudio">
        <div class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] items-start" data-fila>
          <div><label for="est-nombre-__i__" class="etiqueta">Estudio</label><input id="est-nombre-__i__" name="estudios[__i__][nombre_estudio]" class="campo" maxlength="150" list="estudios-frecuentes" required></div>
          <div><label for="est-indicaciones-__i__" class="etiqueta">Indicaciones</label><input id="est-indicaciones-__i__" name="estudios[__i__][indicaciones]" class="campo" maxlength="1000" placeholder="En ayunas de 8 horas"></div>
          <button type="button" class="btn-icono sm sm:mt-7" data-quitar-fila aria-label="Quitar estudio"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
      </template>
      <datalist id="estudios-frecuentes">
        <?php foreach (['Hemograma completo', 'Glucosa en ayunas', 'Perfil lipídico', 'Examen general de orina', 'Creatinina', 'Hemoglobina glicosilada (HbA1c)', 'Perfil tiroideo (TSH, T4)', 'Ecografía abdominal', 'Radiografía de tórax'] as $sugerencia): ?>
          <option value="<?= e($sugerencia) ?>">
        <?php endforeach; ?>
      </datalist>
    </fieldset>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
      <a href="<?= e(url('/agenda')) ?>" class="btn-fantasma">Cancelar</a>
      <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar consulta y marcar Atendida</button>
    </div>
  </form>

  <?php include __DIR__ . '/_paciente.php'; ?>
</div>
