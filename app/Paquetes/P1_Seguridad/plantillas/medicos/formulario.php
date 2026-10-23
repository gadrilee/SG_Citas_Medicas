<?php
/**
 * CU05 · Registrar / editar médico.
 * @var array|null $medico          null = nuevo; al editar incluye 'especialidades' (ids)
 * @var array      $especialidades  activas
 * @var array      $errores
 * @var array      $anterior
 */
$editando = $medico !== null;
$registro = $medico ?? [];
$v = static fn (string $campo): string => e(valor($anterior, $registro, $campo));
$seleccionadas = array_map('strval', (array) ($anterior['especialidades'] ?? ($registro['especialidades'] ?? [])));
$accion = $editando ? '/medicos/' . $medico['id'] : '/medicos';
?>
<div class="mb-6">
  <a href="<?= e(url('/medicos')) ?>" class="btn-fantasma btn-sm -ml-3 mb-2"><?= icono('arrow-left') ?>Volver a médicos</a>
  <p class="sobretitulo">CU05 · <?= $editando ? 'Edición · ' . e($medico['codigo']) : 'Registro' ?></p>
  <h1 class="titulo-pagina"><?= $editando ? 'Editar médico' : 'Registrar médico' ?></h1>
  <p class="texto">Si la persona ya está registrada (por ejemplo, como paciente), se reutilizan sus datos al ingresar su CI.</p>
</div>

<form method="post" action="<?= e(url($accion)) ?>" data-validar novalidate class="grid gap-6 lg:grid-cols-3">
  <?= csrf_campo() ?>
  <?= $editando ? metodo_campo('PUT') : '' ?>
  <div class="lg:col-span-2 flex flex-col gap-6">
    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-5"><?= icono('id-card', 'w-5 h-5 texto-acento') ?>Datos personales</legend>
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label for="ci" class="etiqueta">Cédula de identidad <span class="text-danger">*</span></label>
          <input id="ci" name="ci" class="campo<?= marca_error($errores, 'ci') ?>" required maxlength="30" value="<?= $v('ci') ?>">
          <?= error_campo($errores, 'ci') ?>
        </div>
        <div>
          <label for="fecha_nacimiento" class="etiqueta">Fecha de nacimiento <span class="text-danger">*</span></label>
          <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="campo<?= marca_error($errores, 'fecha_nacimiento') ?>" required max="<?= e(date('Y-m-d')) ?>" value="<?= $v('fecha_nacimiento') ?>">
          <?= error_campo($errores, 'fecha_nacimiento') ?>
        </div>
        <div>
          <label for="nombres" class="etiqueta">Nombres <span class="text-danger">*</span></label>
          <input id="nombres" name="nombres" class="campo<?= marca_error($errores, 'nombres') ?>" required maxlength="100" value="<?= $v('nombres') ?>">
          <?= error_campo($errores, 'nombres') ?>
        </div>
        <div>
          <label for="apellidos" class="etiqueta">Apellidos <span class="text-danger">*</span></label>
          <input id="apellidos" name="apellidos" class="campo<?= marca_error($errores, 'apellidos') ?>" required maxlength="100" value="<?= $v('apellidos') ?>">
          <?= error_campo($errores, 'apellidos') ?>
        </div>
        <div class="sm:col-span-2">
          <span class="etiqueta" id="etiqueta-sexo">Sexo <span class="text-danger">*</span></span>
          <div class="flex flex-wrap gap-2" role="radiogroup" aria-labelledby="etiqueta-sexo">
            <?php foreach (['Femenino', 'Masculino', 'Otro'] as $i => $opcion): ?>
              <label class="chip-opcion"><input type="radio" name="sexo" value="<?= e($opcion) ?>"<?= $i === 0 ? ' required' : '' ?><?= valor($anterior, $registro, 'sexo') === $opcion ? ' checked' : '' ?>><span><?= e($opcion) ?></span></label>
            <?php endforeach; ?>
          </div>
          <?= error_campo($errores, 'sexo') ?>
        </div>
        <div>
          <label for="telefono" class="etiqueta">Teléfono <span class="text-danger">*</span></label>
          <input id="telefono" name="telefono" type="tel" class="campo<?= marca_error($errores, 'telefono') ?>" required inputmode="tel" pattern="\+?[0-9 ]{7,15}" data-mensaje-patron="Ingresa un teléfono válido (7 a 15 dígitos)." value="<?= $v('telefono') ?>">
          <?= error_campo($errores, 'telefono') ?>
        </div>
        <div>
          <label for="correo" class="etiqueta">Correo electrónico</label>
          <input id="correo" name="correo" type="email" class="campo<?= marca_error($errores, 'correo') ?>" maxlength="254" value="<?= $v('correo') ?>">
          <?= error_campo($errores, 'correo') ?>
        </div>
      </div>
    </fieldset>

    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-5"><?= icono('stethoscope', 'w-5 h-5 texto-acento') ?>Datos profesionales</legend>
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label for="nro_registro_profesional" class="etiqueta">Registro profesional <span class="text-danger">*</span></label>
          <input id="nro_registro_profesional" name="nro_registro_profesional" class="campo font-mono<?= marca_error($errores, 'nro_registro_profesional') ?>" required maxlength="50" placeholder="MP-4521" value="<?= $v('nro_registro_profesional') ?>">
          <?= error_campo($errores, 'nro_registro_profesional') ?>
        </div>
        <?php if ($editando): ?>
          <div><span class="etiqueta">Código</span><p class="campo font-mono flex items-center" aria-readonly="true"><?= e($medico['codigo']) ?></p></div>
        <?php endif; ?>
        <div class="sm:col-span-2">
          <span class="etiqueta" id="etiqueta-especialidades">Especialidades <span class="text-danger">*</span></span>
          <div class="flex flex-wrap gap-2" role="group" aria-labelledby="etiqueta-especialidades">
            <?php foreach ($especialidades as $especialidad): ?>
              <label class="chip-opcion"><input type="checkbox" name="especialidades[]" value="<?= e($especialidad['id']) ?>"<?= in_array((string) $especialidad['id'], $seleccionadas, true) ? ' checked' : '' ?>><span><?= e($especialidad['nombre']) ?></span></label>
            <?php endforeach; ?>
          </div>
          <?= error_campo($errores, 'especialidades') ?>
        </div>
      </div>
    </fieldset>
  </div>

  <aside class="flex flex-col gap-6">
    <div class="alerta"><i data-lucide="info"></i><p>Después de registrar al médico configura sus horarios (CU07) y crea su cuenta en Usuarios (CU02).</p></div>
    <div class="flex flex-col gap-2 lg:sticky lg:top-28">
      <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar médico</button>
      <a href="<?= e(url('/medicos')) ?>" class="btn-fantasma">Cancelar</a>
    </div>
  </aside>
</form>
