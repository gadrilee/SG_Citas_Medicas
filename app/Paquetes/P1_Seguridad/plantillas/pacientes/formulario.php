<?php
/**
 * CU04 · Registrar / editar paciente.
 * @var array|null $paciente    null = nuevo; al editar incluye 'contactos'
 * @var array      $tiposSangre
 * @var array      $errores
 * @var array      $anterior
 */
$editando = $paciente !== null;
$registro = $paciente ?? [];
$v = static fn (string $campo): string => e(valor($anterior, $registro, $campo));
$contactos = $anterior['contactos'] ?? ($registro['contactos'] ?? []);
$contactos = array_values($contactos ?: [['nombre' => '', 'parentesco' => '', 'telefono' => '']]);
$crearCuenta = ($anterior['crear_cuenta'] ?? '0') === '1';
$accion = $editando ? '/pacientes/' . $paciente['id'] : '/pacientes';
$volver = $editando ? '/pacientes/' . $paciente['id'] : '/pacientes';
?>
<div class="mb-6">
  <a href="<?= e(url($volver)) ?>" class="btn-fantasma btn-sm -ml-3 mb-2"><?= icono('arrow-left') ?><?= $editando ? 'Volver a la ficha' : 'Volver a pacientes' ?></a>
  <p class="sobretitulo">CU04 · <?= $editando ? 'Edición · ' . e($paciente['codigo']) : 'Registro' ?></p>
  <h1 class="titulo-pagina"><?= $editando ? 'Editar paciente' : 'Registrar paciente' ?></h1>
  <p class="texto">Los campos marcados con <span class="text-danger">*</span> son obligatorios.</p>
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
          <input id="ci" name="ci" class="campo<?= marca_error($errores, 'ci') ?>" required maxlength="30" pattern="\d{5,10}( ?[A-Za-z]{2})?" data-mensaje-patron="Ingresa un CI válido, ej. 7845123 SC." placeholder="7845123 SC" value="<?= $v('ci') ?>">
          <?= error_campo($errores, 'ci') ?>
        </div>
        <div>
          <label for="fecha_nacimiento" class="etiqueta">Fecha de nacimiento <span class="text-danger">*</span></label>
          <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="campo<?= marca_error($errores, 'fecha_nacimiento') ?>" required max="<?= e(date('Y-m-d')) ?>" autocomplete="bday" value="<?= $v('fecha_nacimiento') ?>">
          <?= error_campo($errores, 'fecha_nacimiento') ?>
        </div>
        <div>
          <label for="nombres" class="etiqueta">Nombres <span class="text-danger">*</span></label>
          <input id="nombres" name="nombres" class="campo<?= marca_error($errores, 'nombres') ?>" required maxlength="100" autocomplete="given-name" value="<?= $v('nombres') ?>">
          <?= error_campo($errores, 'nombres') ?>
        </div>
        <div>
          <label for="apellidos" class="etiqueta">Apellidos <span class="text-danger">*</span></label>
          <input id="apellidos" name="apellidos" class="campo<?= marca_error($errores, 'apellidos') ?>" required maxlength="100" autocomplete="family-name" value="<?= $v('apellidos') ?>">
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
      </div>
    </fieldset>

    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-5"><?= icono('phone', 'w-5 h-5 texto-acento') ?>Contacto</legend>
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label for="telefono" class="etiqueta">Teléfono <span class="text-danger">*</span></label>
          <input id="telefono" name="telefono" type="tel" class="campo<?= marca_error($errores, 'telefono') ?>" required inputmode="tel" pattern="\+?[0-9 ]{7,15}" data-mensaje-patron="Ingresa un teléfono válido (7 a 15 dígitos)." autocomplete="tel" value="<?= $v('telefono') ?>">
          <?= error_campo($errores, 'telefono') ?>
        </div>
        <div>
          <label for="correo" class="etiqueta">Correo electrónico</label>
          <input id="correo" name="correo" type="email" class="campo<?= marca_error($errores, 'correo') ?>" maxlength="254" autocomplete="email" value="<?= $v('correo') ?>">
          <?= error_campo($errores, 'correo') ?>
        </div>
        <div class="sm:col-span-2">
          <label for="direccion" class="etiqueta">Dirección</label>
          <input id="direccion" name="direccion" class="campo<?= marca_error($errores, 'direccion') ?>" maxlength="250" autocomplete="street-address" placeholder="Calle, número, barrio" value="<?= $v('direccion') ?>">
          <?= error_campo($errores, 'direccion') ?>
        </div>
      </div>
    </fieldset>

    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-5"><?= icono('siren', 'w-5 h-5 texto-acento') ?>Contactos de emergencia</legend>
      <div id="lista-contactos" class="flex flex-col gap-4" data-lista-filas>
        <?php foreach ($contactos as $i => $contacto): ?>
          <div class="grid gap-3 sm:grid-cols-[1fr_160px_160px_auto] items-start" data-fila>
            <div>
              <label for="contacto-nombre-<?= $i ?>" class="etiqueta">Nombre</label>
              <input id="contacto-nombre-<?= $i ?>" name="contactos[<?= $i ?>][nombre]" class="campo<?= marca_error($errores, "contactos[$i][nombre]") ?>" maxlength="150" value="<?= e($contacto['nombre'] ?? '') ?>">
              <?= error_campo($errores, "contactos[$i][nombre]") ?>
            </div>
            <div>
              <label for="contacto-parentesco-<?= $i ?>" class="etiqueta">Parentesco</label>
              <input id="contacto-parentesco-<?= $i ?>" name="contactos[<?= $i ?>][parentesco]" class="campo<?= marca_error($errores, "contactos[$i][parentesco]") ?>" maxlength="50" placeholder="Madre, esposo…" value="<?= e($contacto['parentesco'] ?? '') ?>">
              <?= error_campo($errores, "contactos[$i][parentesco]") ?>
            </div>
            <div>
              <label for="contacto-telefono-<?= $i ?>" class="etiqueta">Teléfono</label>
              <input id="contacto-telefono-<?= $i ?>" name="contactos[<?= $i ?>][telefono]" type="tel" class="campo<?= marca_error($errores, "contactos[$i][telefono]") ?>" inputmode="tel" value="<?= e($contacto['telefono'] ?? '') ?>">
              <?= error_campo($errores, "contactos[$i][telefono]") ?>
            </div>
            <button type="button" class="btn-icono sm sm:mt-7" data-quitar-fila aria-label="Quitar contacto"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn-secundario btn-sm mt-4" data-agregar-fila="plantilla-contacto" data-destino="lista-contactos"><?= icono('plus') ?>Agregar contacto</button>
      <template id="plantilla-contacto">
        <div class="grid gap-3 sm:grid-cols-[1fr_160px_160px_auto] items-start" data-fila>
          <div><label for="contacto-nombre-__i__" class="etiqueta">Nombre</label><input id="contacto-nombre-__i__" name="contactos[__i__][nombre]" class="campo" maxlength="150"></div>
          <div><label for="contacto-parentesco-__i__" class="etiqueta">Parentesco</label><input id="contacto-parentesco-__i__" name="contactos[__i__][parentesco]" class="campo" maxlength="50" placeholder="Madre, esposo…"></div>
          <div><label for="contacto-telefono-__i__" class="etiqueta">Teléfono</label><input id="contacto-telefono-__i__" name="contactos[__i__][telefono]" type="tel" class="campo" inputmode="tel"></div>
          <button type="button" class="btn-icono sm sm:mt-7" data-quitar-fila aria-label="Quitar contacto"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
        </div>
      </template>
    </fieldset>

    <?php if (!$editando): ?>
    <fieldset class="glass-card">
      <legend class="font-semibold flex items-center gap-2 mb-5"><?= icono('heart-pulse', 'w-5 h-5 texto-acento') ?>Datos clínicos declarados</legend>
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label for="tipo_sangre" class="etiqueta">Tipo de sangre</label>
          <select id="tipo_sangre" name="tipo_sangre" class="campo<?= marca_error($errores, 'tipo_sangre') ?>">
            <option value="">No registrado</option>
            <?php foreach ($tiposSangre as $tipo): ?>
              <option<?= ($anterior['tipo_sangre'] ?? '') === $tipo ? ' selected' : '' ?>><?= e($tipo) ?></option>
            <?php endforeach; ?>
          </select>
          <?= error_campo($errores, 'tipo_sangre') ?>
        </div>
        <div class="sm:col-span-2">
          <label for="alergias" class="etiqueta">Alergias conocidas</label>
          <textarea id="alergias" name="alergias" class="campo" maxlength="1000" placeholder="Ej. Penicilina. Deja vacío si no tiene."><?= e($anterior['alergias'] ?? '') ?></textarea>
          <p class="ayuda-campo">El resto de la historia clínica se completa en CU16.</p>
        </div>
      </div>
    </fieldset>
    <?php endif; ?>
  </div>

  <aside class="flex flex-col gap-6">
    <?php if (!$editando): ?>
    <div class="glass-card">
      <div class="flex items-start justify-between gap-4">
        <div>
          <label for="crear_cuenta" class="font-semibold">Crear cuenta de acceso</label>
          <p class="texto text-sm">El paciente podrá reservar y consultar sus citas en línea.</p>
        </div>
        <span class="interruptor"><input type="hidden" name="crear_cuenta" value="0"><input id="crear_cuenta" name="crear_cuenta" type="checkbox" value="1" role="switch"<?= $crearCuenta ? ' checked' : '' ?>><span></span></span>
      </div>
      <div class="mt-5">
        <label for="nombre_usuario" class="etiqueta">Nombre de usuario</label>
        <input id="nombre_usuario" name="nombre_usuario" class="campo font-mono<?= marca_error($errores, 'nombre_usuario') ?>" pattern="[a-z0-9._]{3,50}" data-mensaje-patron="Usa minúsculas, números, punto o guion bajo." placeholder="nombre.apellido" autocomplete="off" value="<?= e($anterior['nombre_usuario'] ?? '') ?>">
        <p class="ayuda-campo">Se generará una contraseña temporal que verás al guardar.</p>
        <?= error_campo($errores, 'nombre_usuario') ?>
      </div>
    </div>
    <?php elseif ($paciente['nombre_usuario']): ?>
      <div class="alerta"><i data-lucide="user-check"></i><p>Cuenta de acceso: <span class="font-mono"><?= e($paciente['nombre_usuario']) ?></span>. Se administra en Usuarios (CU02).</p></div>
    <?php endif; ?>
    <div class="alerta alerta-aviso"><i data-lucide="shield-check"></i><p>Verifica la cédula física del paciente antes de guardar. El CI no puede repetirse.</p></div>
    <div class="flex flex-col gap-2 lg:sticky lg:top-28">
      <button type="submit" class="btn-primario"><?= icono('save') ?>Guardar paciente</button>
      <a href="<?= e(url($volver)) ?>" class="btn-fantasma">Cancelar</a>
    </div>
  </aside>
</form>
