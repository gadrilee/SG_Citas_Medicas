<?php /** CU01 · Iniciar sesión. @var array $errores @var array $anterior */ ?>
<div class="contenedor w-full py-8">
  <div class="glass grid lg:grid-cols-2 overflow-hidden p-2">
    <div class="p-6 sm:p-10 flex flex-col">
      <div class="flex items-center justify-between">
        <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5" aria-label="Vida Salud, inicio"><?= parcial('logo') ?></a>
        <?= parcial('boton_tema') ?>
      </div>
      <div class="my-auto py-10 max-w-sm w-full mx-auto">
        <h1 class="titulo-pagina">Bienvenido de nuevo</h1>
        <p class="texto mt-2">Ingresa para gestionar tus citas y resultados.</p>
        <?= parcial('flash', ['clase' => 'space-y-3 mt-6 empty:mt-0']) ?>
        <form method="post" action="<?= e(url('/login')) ?>" class="space-y-5 mt-6" data-validar novalidate>
          <?= csrf_campo() ?>
          <div>
            <label for="usuario_o_correo" class="etiqueta">Usuario o correo</label>
            <div class="campo-con-icono">
              <i data-lucide="user"></i>
              <input id="usuario_o_correo" name="usuario_o_correo" type="text" class="campo<?= marca_error($errores, 'usuario_o_correo') ?>" autocomplete="username" required
                     value="<?= e($anterior['usuario_o_correo'] ?? '') ?>" placeholder="maria.lopez o maria@correo.com" autofocus>
            </div>
            <?= error_campo($errores, 'usuario_o_correo') ?>
          </div>
          <div>
            <label for="contrasena" class="etiqueta">Contraseña</label>
            <div class="campo-con-icono relative">
              <i data-lucide="lock"></i>
              <input id="contrasena" name="contrasena" type="password" class="campo pr-12" autocomplete="current-password" required>
              <button type="button" class="btn-icono sm absolute right-1 top-1/2 -translate-y-1/2" data-mostrar-contrasena="contrasena" aria-label="Mostrar contraseña" aria-pressed="false"><i data-lucide="eye" class="w-4 h-4"></i></button>
            </div>
            <?= error_campo($errores, 'contrasena') ?>
          </div>
          <button type="submit" class="btn-primario btn-grande w-full">Iniciar sesión</button>
        </form>
        <p class="text-sm text-muted text-center mt-6">¿Aún no tienes cuenta? <a href="<?= e(url('/registro')) ?>" class="texto-acento font-semibold hover:underline">Crear cuenta</a></p>
      </div>
      <p class="text-xs text-subtle">© <?= date('Y') ?> Clínica Vida Salud</p>
    </div>
    <div class="hidden lg:block relative rounded-[20px] overflow-hidden min-h-[560px]">
      <img src="<?= e(recurso('img/ilustracion-acceso.jpg')) ?>" alt="Recepción luminosa de la Clínica Vida Salud" class="absolute inset-0 w-full h-full object-cover">
      <div class="absolute left-6 right-6 bottom-6 glass p-5">
        <p class="font-semibold">Reserva en minutos</p>
        <p class="text-sm text-muted mt-1">Turnos de 15 minutos con 5 especialidades, comprobante inmediato y pago en línea.</p>
      </div>
    </div>
  </div>
</div>
