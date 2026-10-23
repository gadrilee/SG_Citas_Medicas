<?php
/**
 * CU01 · Mi cuenta.
 * @var array  $usuario      Eusuario::buscarDetalle
 * @var array  $persona      datos_personales
 * @var array  $preferencias preferencia_cuenta
 * @var string $pestana      datos | contrasena | preferencias
 * @var array  $errores
 */
$nombre = $persona['nombres'] . ' ' . $persona['apellidos'];
if (array_intersect_key($errores, array_flip(['contrasena_actual', 'contrasena_nueva', 'contrasena_confirmacion']))) {
    $pestana = 'contrasena';
}
$seleccionada = static fn (string $id): string => $pestana === $id ? 'true' : 'false';
?>
<div class="flex flex-col sm:flex-row sm:items-center gap-5 mb-8">
  <span class="avatar avatar-lg" aria-hidden="true"><?= e(iniciales($nombre)) ?></span>
  <div class="min-w-0">
    <p class="sobretitulo">CU01 · Mi cuenta</p>
    <h1 class="titulo-pagina"><?= e($nombre) ?></h1>
    <p class="texto text-sm">Usuario <span class="font-mono"><?= e($usuario['nombre_usuario']) ?></span> · <?= e(nombre_perfil($usuario['perfil'])) ?>
      <?php if ($usuario['codigo_paciente']): ?> <span class="font-mono"><?= e($usuario['codigo_paciente']) ?></span><?php endif; ?>
      <?php if ($usuario['codigo_medico']): ?> <span class="font-mono"><?= e($usuario['codigo_medico']) ?></span><?php endif; ?>
    </p>
  </div>
</div>

<div class="glass-pill pestanas mb-6" role="tablist" aria-label="Secciones de mi cuenta" data-pestanas="cuenta">
  <button type="button" class="pestana" role="tab" id="tab-datos" aria-controls="panel-datos" aria-selected="<?= $seleccionada('datos') ?>" data-pestana="datos">Mis datos</button>
  <button type="button" class="pestana" role="tab" id="tab-contrasena" aria-controls="panel-contrasena" aria-selected="<?= $seleccionada('contrasena') ?>" data-pestana="contrasena">Contraseña</button>
  <button type="button" class="pestana" role="tab" id="tab-preferencias" aria-controls="panel-preferencias" aria-selected="<?= $seleccionada('preferencias') ?>" data-pestana="preferencias">Preferencias</button>
</div>

<section id="panel-datos" role="tabpanel" aria-labelledby="tab-datos" data-panel-pestana="cuenta:datos" class="grid gap-6 lg:grid-cols-3">
  <div class="glass-card lg:col-span-2">
    <h2 class="font-semibold mb-5">Datos personales</h2>
    <dl class="grid gap-x-6 gap-y-5 sm:grid-cols-2 text-sm">
      <div><dt class="etiqueta">Cédula de identidad</dt><dd class="font-medium numeros"><?= e($persona['ci'] ?: '—') ?></dd></div>
      <div><dt class="etiqueta">Fecha de nacimiento</dt><dd class="font-medium numeros"><?= e(fecha_corta($persona['fecha_nacimiento'])) ?> <span class="text-subtle">(<?= e(edad($persona['fecha_nacimiento'])) ?>)</span></dd></div>
      <div><dt class="etiqueta">Nombres</dt><dd class="font-medium"><?= e($persona['nombres']) ?></dd></div>
      <div><dt class="etiqueta">Apellidos</dt><dd class="font-medium"><?= e($persona['apellidos']) ?></dd></div>
      <div><dt class="etiqueta">Sexo</dt><dd class="font-medium"><?= e($persona['sexo'] ?: '—') ?></dd></div>
      <div><dt class="etiqueta">Teléfono</dt><dd class="font-medium numeros"><?= e($persona['telefono']) ?></dd></div>
      <div class="sm:col-span-2"><dt class="etiqueta">Correo electrónico</dt><dd class="font-medium"><?= e($persona['correo'] ?: '—') ?></dd></div>
      <div class="sm:col-span-2"><dt class="etiqueta">Dirección</dt><dd class="font-medium"><?= e($persona['direccion'] ?: '—') ?></dd></div>
    </dl>
  </div>
  <aside class="flex flex-col gap-4">
    <div class="alerta alerta-aviso"><i data-lucide="info"></i><p>Para corregir tus datos acércate a recepción con tu cédula de identidad.</p></div>
    <div class="glass-card pequena">
      <p class="etiqueta">Cuenta</p>
      <ul class="text-sm space-y-3 mt-2">
        <li class="flex justify-between gap-3"><span class="text-muted">Perfil</span><span class="insignia <?= e(clase_perfil($usuario['perfil'])) ?>"><?= e(nombre_perfil($usuario['perfil'])) ?></span></li>
        <li class="flex justify-between gap-3"><span class="text-muted">Estado</span><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $usuario['activo']]) ?></li>
        <li class="flex justify-between gap-3"><span class="text-muted">Registrada</span><span class="numeros"><?= e(fecha_corta($usuario['fecha_registro'])) ?></span></li>
      </ul>
    </div>
  </aside>
</section>

<section id="panel-contrasena" role="tabpanel" aria-labelledby="tab-contrasena" data-panel-pestana="cuenta:contrasena" hidden>
  <form method="post" action="<?= e(url('/mi-cuenta/contrasena')) ?>" class="glass-card max-w-xl" data-validar novalidate>
    <?= csrf_campo() ?>
    <h2 class="font-semibold">Cambiar contraseña</h2>
    <p class="texto text-sm mb-6">Usa al menos 8 caracteres, combinando letras y números.</p>
    <div class="flex flex-col gap-5">
      <div>
        <label for="contrasena_actual" class="etiqueta">Contraseña actual</label>
        <div class="campo-con-icono relative">
          <i data-lucide="lock"></i>
          <input id="contrasena_actual" name="contrasena_actual" type="password" class="campo pr-12<?= marca_error($errores, 'contrasena_actual') ?>" autocomplete="current-password" required>
          <button type="button" class="btn-icono sm absolute right-1 top-1/2 -translate-y-1/2" data-mostrar-contrasena="contrasena_actual" aria-label="Mostrar contraseña" aria-pressed="false"><i data-lucide="eye" class="w-4 h-4"></i></button>
        </div>
        <?= error_campo($errores, 'contrasena_actual') ?>
      </div>
      <div>
        <label for="contrasena_nueva" class="etiqueta">Contraseña nueva</label>
        <div class="campo-con-icono relative">
          <i data-lucide="key-round"></i>
          <input id="contrasena_nueva" name="contrasena_nueva" type="password" class="campo pr-12<?= marca_error($errores, 'contrasena_nueva') ?>" autocomplete="new-password" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" data-mensaje-patron="Debe tener al menos 8 caracteres con letras y números." aria-describedby="ayuda-contrasena">
          <button type="button" class="btn-icono sm absolute right-1 top-1/2 -translate-y-1/2" data-mostrar-contrasena="contrasena_nueva" aria-label="Mostrar contraseña" aria-pressed="false"><i data-lucide="eye" class="w-4 h-4"></i></button>
        </div>
        <p id="ayuda-contrasena" class="ayuda-campo">Mínimo 8 caracteres, con letras y números.</p>
        <?= error_campo($errores, 'contrasena_nueva') ?>
      </div>
      <div>
        <label for="contrasena_confirmacion" class="etiqueta">Confirmar contraseña nueva</label>
        <div class="campo-con-icono">
          <i data-lucide="key-round"></i>
          <input id="contrasena_confirmacion" name="contrasena_confirmacion" type="password" class="campo<?= marca_error($errores, 'contrasena_confirmacion') ?>" autocomplete="new-password" required>
        </div>
        <?= error_campo($errores, 'contrasena_confirmacion') ?>
      </div>
    </div>
    <div class="flex justify-end mt-6">
      <button type="submit" class="btn-primario"><i data-lucide="save" class="w-4 h-4"></i>Actualizar contraseña</button>
    </div>
  </form>
</section>

<section id="panel-preferencias" role="tabpanel" aria-labelledby="tab-preferencias" data-panel-pestana="cuenta:preferencias" hidden>
  <form method="post" action="<?= e(url('/mi-cuenta/preferencias')) ?>" class="glass-card max-w-xl">
    <?= csrf_campo() ?>
    <h2 class="font-semibold mb-6">Preferencias</h2>
    <ul class="flex flex-col">
      <li class="flex items-start justify-between gap-4 pb-5 border-b borde-glass">
        <div>
          <label for="notificaciones" class="font-medium">Recibir recordatorios</label>
          <p class="texto text-sm">Te avisaremos por correo 24 horas antes de cada cita.</p>
        </div>
        <span class="interruptor"><input type="hidden" name="notificaciones" value="0"><input id="notificaciones" name="notificaciones" type="checkbox" value="1" role="switch"<?= $preferencias['notificaciones'] ? ' checked' : '' ?>><span></span></span>
      </li>
      <li class="flex items-start justify-between gap-4 pt-5">
        <div>
          <label for="mostrar_contacto_al_medico" class="font-medium">Mostrar mi teléfono al médico</label>
          <p class="texto text-sm">Permite que tu médico te contacte por seguimiento del tratamiento.</p>
        </div>
        <span class="interruptor"><input type="hidden" name="mostrar_contacto_al_medico" value="0"><input id="mostrar_contacto_al_medico" name="mostrar_contacto_al_medico" type="checkbox" value="1" role="switch"<?= $preferencias['mostrar_contacto_al_medico'] ? ' checked' : '' ?>><span></span></span>
      </li>
    </ul>
    <div class="flex justify-end mt-6">
      <button type="submit" class="btn-primario"><i data-lucide="save" class="w-4 h-4"></i>Guardar preferencias</button>
    </div>
  </form>
</section>
