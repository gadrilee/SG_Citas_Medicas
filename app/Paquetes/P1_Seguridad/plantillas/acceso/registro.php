<?php
/** CU01 · A1 Registro web del paciente (asistente de 2 pasos). @var array $errores @var array $anterior */
$v = static fn (string $campo): string => e($anterior[$campo] ?? '');
// Si el servidor devolvió errores de la cuenta, el asistente abre directamente el paso 2.
$paso = array_intersect_key($errores, array_flip(['nombre_usuario', 'contrasena', 'contrasena_confirmacion', 'aceptar_terminos'])) ? 2 : 1;
?>
<div class="contenedor py-8 max-w-3xl">
  <div class="flex items-center justify-between mb-8">
    <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5" aria-label="Vida Salud, inicio"><?= parcial('logo') ?></a>
    <div class="flex items-center gap-2"><?= parcial('boton_tema') ?><a href="<?= e(url('/login')) ?>" class="btn-fantasma btn-sm">Ya tengo cuenta</a></div>
  </div>

  <form method="post" action="<?= e(url('/registro')) ?>" class="glass p-6 sm:p-10" data-validar novalidate data-asistente data-paso-actual="<?= $paso ?>">
    <?= csrf_campo() ?>
    <div class="text-center mb-8">
      <p class="sobretitulo" data-contador-pasos>Paso <?= $paso ?> de 2</p>
      <h1 class="titulo-pagina">Crea tu cuenta de paciente</h1>
      <p class="texto mt-2">Solo te tomará un minuto. Usaremos estos datos para tus citas.</p>
    </div>
    <ol class="indicador-pasos mb-8" aria-label="Progreso del registro">
      <li class="paso-indicador" data-indicador-paso="1"><span class="numero">1</span><span class="nombre">Datos personales</span><span class="linea" aria-hidden="true"></span></li>
      <li class="paso-indicador !flex-none" data-indicador-paso="2"><span class="numero">2</span><span class="nombre">Cuenta</span></li>
    </ol>
    <?= parcial('flash') ?>

    <fieldset data-paso="1" class="grid gap-5 sm:grid-cols-2">
      <legend class="sr-only">Datos personales</legend>
      <div>
        <label for="ci" class="etiqueta">Cédula de identidad <span class="text-danger">*</span></label>
        <input id="ci" name="ci" class="campo<?= marca_error($errores, 'ci') ?>" required maxlength="30" pattern="[0-9]{5,10}( ?[A-Za-z]{2})?" data-mensaje-patron="Ingresa solo números, con extensión opcional (ej.: 7845123 SC)." placeholder="7845123 SC" value="<?= $v('ci') ?>">
        <?= error_campo($errores, 'ci') ?>
      </div>
      <div>
        <label for="fecha_nacimiento" class="etiqueta">Fecha de nacimiento <span class="text-danger">*</span></label>
        <input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="campo<?= marca_error($errores, 'fecha_nacimiento') ?>" required max="<?= e(date('Y-m-d')) ?>" value="<?= $v('fecha_nacimiento') ?>">
        <?= error_campo($errores, 'fecha_nacimiento') ?>
      </div>
      <div>
        <label for="nombres" class="etiqueta">Nombres <span class="text-danger">*</span></label>
        <input id="nombres" name="nombres" class="campo<?= marca_error($errores, 'nombres') ?>" required minlength="2" maxlength="100" autocomplete="given-name" placeholder="María" value="<?= $v('nombres') ?>">
        <?= error_campo($errores, 'nombres') ?>
      </div>
      <div>
        <label for="apellidos" class="etiqueta">Apellidos <span class="text-danger">*</span></label>
        <input id="apellidos" name="apellidos" class="campo<?= marca_error($errores, 'apellidos') ?>" required minlength="2" maxlength="100" autocomplete="family-name" placeholder="López Ribera" value="<?= $v('apellidos') ?>">
        <?= error_campo($errores, 'apellidos') ?>
      </div>
      <div>
        <label for="sexo" class="etiqueta">Sexo <span class="text-danger">*</span></label>
        <select id="sexo" name="sexo" class="campo<?= marca_error($errores, 'sexo') ?>" required>
          <option value="">Selecciona…</option>
          <?php foreach (['Femenino', 'Masculino', 'Otro'] as $opcion): ?>
            <option<?= ($anterior['sexo'] ?? '') === $opcion ? ' selected' : '' ?>><?= e($opcion) ?></option>
          <?php endforeach; ?>
        </select>
        <?= error_campo($errores, 'sexo') ?>
      </div>
      <div>
        <label for="telefono" class="etiqueta">Teléfono <span class="text-danger">*</span></label>
        <div class="campo-con-icono"><i data-lucide="phone"></i><input id="telefono" name="telefono" type="tel" class="campo<?= marca_error($errores, 'telefono') ?>" required pattern="[0-9]{7,8}" data-mensaje-patron="Ingresa 7 u 8 dígitos." autocomplete="tel" placeholder="70012345" value="<?= $v('telefono') ?>"></div>
        <?= error_campo($errores, 'telefono') ?>
      </div>
      <div class="sm:col-span-2">
        <label for="correo" class="etiqueta">Correo electrónico <span class="text-danger">*</span></label>
        <div class="campo-con-icono"><i data-lucide="mail"></i><input id="correo" name="correo" type="email" class="campo<?= marca_error($errores, 'correo') ?>" required maxlength="254" autocomplete="email" placeholder="maria@correo.com" value="<?= $v('correo') ?>"></div>
        <?= error_campo($errores, 'correo') ?>
      </div>
      <div class="sm:col-span-2 flex justify-end pt-2">
        <button type="button" class="btn-primario" data-siguiente>Continuar<i data-lucide="arrow-right" class="w-4 h-4"></i></button>
      </div>
    </fieldset>

    <fieldset data-paso="2" class="grid gap-5" hidden>
      <legend class="sr-only">Datos de la cuenta</legend>
      <div>
        <label for="nombre_usuario" class="etiqueta">Nombre de usuario <span class="text-danger">*</span></label>
        <div class="campo-con-icono"><i data-lucide="at-sign"></i><input id="nombre_usuario" name="nombre_usuario" class="campo<?= marca_error($errores, 'nombre_usuario') ?>" required pattern="[a-z0-9._]{3,50}" data-mensaje-patron="Usa de 3 a 50 letras minúsculas, números, punto o guion bajo." autocomplete="username" placeholder="maria.lopez" value="<?= $v('nombre_usuario') ?>"></div>
        <?= error_campo($errores, 'nombre_usuario') ?>
      </div>
      <div class="grid gap-5 sm:grid-cols-2">
        <div>
          <label for="contrasena" class="etiqueta">Contraseña <span class="text-danger">*</span></label>
          <div class="relative">
            <input id="contrasena" name="contrasena" type="password" class="campo pr-12<?= marca_error($errores, 'contrasena') ?>" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-mensaje-patron="Debe tener al menos 8 caracteres con letras y números." autocomplete="new-password">
            <button type="button" class="btn-icono sm absolute right-1 top-1/2 -translate-y-1/2" data-mostrar-contrasena="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><i data-lucide="eye" class="w-4 h-4"></i></button>
          </div>
          <p class="ayuda-campo">Mínimo 8 caracteres, con letras y números.</p>
          <?= error_campo($errores, 'contrasena') ?>
        </div>
        <div>
          <label for="contrasena_confirmacion" class="etiqueta">Confirmar contraseña <span class="text-danger">*</span></label>
          <input id="contrasena_confirmacion" name="contrasena_confirmacion" type="password" class="campo<?= marca_error($errores, 'contrasena_confirmacion') ?>" required autocomplete="new-password">
          <?= error_campo($errores, 'contrasena_confirmacion') ?>
        </div>
      </div>
      <div>
        <label class="flex items-start gap-3 text-sm text-muted cursor-pointer">
          <input type="checkbox" name="aceptar_terminos" value="1" class="casilla mt-0.5" required>
          <span>Acepto los términos de uso y la política de privacidad de la Clínica Vida Salud.</span>
        </label>
        <?= error_campo($errores, 'aceptar_terminos') ?>
      </div>
      <div class="flex flex-col-reverse sm:flex-row justify-between gap-2 pt-2">
        <button type="button" class="btn-fantasma" data-anterior><i data-lucide="arrow-left" class="w-4 h-4"></i>Volver</button>
        <button type="submit" class="btn-primario"><i data-lucide="user-plus" class="w-4 h-4"></i>Crear cuenta</button>
      </div>
    </fieldset>
  </form>
</div>
