<?php
/**
 * Portada pública (diseño de V0, datos de la base).
 * @var array $especialidades Eespecialidad::activas
 * @var array $medicos        Emedico::activosConEspecialidades
 */
$iconos = ['Medicina general' => 'stethoscope', 'Pediatría' => 'baby', 'Geriatría' => 'heart-handshake', 'Ginecología' => 'flower-2', 'Odontología' => 'smile'];
?>
<section class="relative isolate pt-10 pb-20 md:pt-16 md:pb-28">
  <div class="brillo-hero" aria-hidden="true"></div>
  <div class="contenedor">
    <div class="max-w-3xl mx-auto text-center">
      <span class="insignia insignia-acento">Atención ambulatoria programada · Santa Cruz de la Sierra</span>
      <h1 class="titulo-hero mt-6">Tu salud, a tiempo con Vida <span class="clave">Salud</span></h1>
      <p class="texto text-base md:text-lg mt-6 max-w-2xl mx-auto">Elige especialidad, médico y horario en minutos. Recibe tu comprobante al instante y paga en línea, sin filas ni llamadas.</p>
    </div>
    <div class="mt-10 max-w-5xl mx-auto">
      <?= parcial('booking_bar', ['especialidades' => $especialidades, 'medicos' => $medicos]) ?>
    </div>
    <dl class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
      <div class="glass pequena p-4 text-center"><dt class="text-xs text-muted">Especialidades</dt><dd class="numero-kpi texto-acento"><?= count($especialidades) ?></dd></div>
      <div class="glass pequena p-4 text-center"><dt class="text-xs text-muted">Turnos de</dt><dd class="numero-kpi">15 min</dd></div>
      <div class="glass pequena p-4 text-center"><dt class="text-xs text-muted">Médicos</dt><dd class="numero-kpi"><?= count($medicos) ?></dd></div>
      <div class="glass pequena p-4 text-center"><dt class="text-xs text-muted">Pago en línea</dt><dd class="numero-kpi">Bs</dd></div>
    </dl>
  </div>
</section>

<section id="especialidades" class="seccion seccion-alterna" aria-labelledby="titulo-especialidades">
  <div class="contenedor">
    <div class="text-center max-w-2xl mx-auto mb-12">
      <p class="sobretitulo">Especialidades</p>
      <h2 id="titulo-especialidades" class="titulo-seccion">Atención para toda la <span class="ultima">familia</span></h2>
      <p class="texto mt-3">Especialidades con turnos de 15 minutos y médicos con registro profesional.</p>
    </div>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-5">
      <?php foreach ($especialidades as $especialidad): ?>
        <article class="glass-card interactiva flex flex-col gap-3">
          <span class="icono-cuadrado"><i data-lucide="<?= e($iconos[$especialidad['nombre']] ?? 'heart-pulse') ?>" class="w-6 h-6"></i></span>
          <h3 class="font-semibold"><?= e($especialidad['nombre']) ?></h3>
          <p class="text-sm text-muted flex-1"><?= e($especialidad['descripcion'] ?: 'Consulta programada con especialistas.') ?></p>
          <p class="text-sm"><span class="font-semibold numeros"><?= e($especialidad['medicos']) ?></span> <span class="text-subtle"><?= (int) $especialidad['medicos'] === 1 ? 'médico' : 'médicos' ?></span></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion" aria-labelledby="titulo-como-funciona">
  <div class="contenedor">
    <div class="text-center max-w-2xl mx-auto mb-12">
      <p class="sobretitulo">Cómo funciona</p>
      <h2 id="titulo-como-funciona" class="titulo-seccion">Tres pasos y <span class="ultima">listo</span></h2>
    </div>
    <ol class="grid gap-6 md:grid-cols-3">
      <?php foreach ([['user-plus', 'Regístrate', 'Crea tu cuenta con tu CI y tus datos de contacto. Solo una vez.'], ['calendar-clock', 'Elige tu horario', 'Selecciona especialidad, médico y un cupo libre de 15 minutos.'], ['badge-check', 'Asiste', 'Llega 10 minutos antes con tu comprobante. Nosotros te esperamos.']] as $i => [$icono, $titulo, $texto]): ?>
        <li class="glass-card flex flex-col gap-3">
          <div class="flex items-center justify-between"><span class="icono-cuadrado"><i data-lucide="<?= e($icono) ?>" class="w-6 h-6"></i></span><span class="font-display text-4xl font-semibold text-subtle">0<?= $i + 1 ?></span></div>
          <h3 class="font-semibold text-lg"><?= e($titulo) ?></h3>
          <p class="text-sm text-muted"><?= e($texto) ?></p>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<?php if ($medicos): ?>
<section id="medicos" class="seccion seccion-alterna" aria-labelledby="titulo-medicos">
  <div class="contenedor">
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-8">
      <div>
        <p class="sobretitulo">Nuestro equipo</p>
        <h2 id="titulo-medicos" class="titulo-seccion !text-left">Médicos <span class="ultima">destacados</span></h2>
      </div>
      <a href="<?= e(url('/citas/nueva')) ?>" class="btn-secundario self-start md:self-auto">Ver disponibilidad<?= icono('arrow-right') ?></a>
    </div>
    <div class="carrusel" tabindex="0" aria-label="Carrusel de médicos, desplázate horizontalmente">
      <?php foreach ($medicos as $medico): ?>
        <?php $foto = foto_medico($medico['codigo']); ?>
        <article class="glass-card interactiva flex flex-col gap-3">
          <?php if ($foto): ?>
            <img src="<?= e(recurso($foto)) ?>" alt="<?= e($medico['nombre']) ?>" width="320" height="320" class="foto-redonda w-full aspect-square" loading="lazy">
          <?php else: ?>
            <span class="avatar w-full aspect-square text-5xl" style="border-radius:16px" aria-hidden="true"><?= e(iniciales($medico['nombres'] . ' ' . $medico['apellidos'])) ?></span>
          <?php endif; ?>
          <div><h3 class="font-semibold"><?= e($medico['nombre']) ?></h3><p class="text-xs text-subtle font-mono"><?= e($medico['nro_registro_profesional']) ?></p></div>
          <div class="flex flex-wrap gap-1.5">
            <?php foreach (array_filter(explode(' · ', (string) $medico['especialidades'])) as $especialidad): ?><span class="chip"><?= e($especialidad) ?></span><?php endforeach; ?>
          </div>
          <a href="<?= e(url('/citas/nueva?medico_id=' . $medico['id'])) ?>" class="btn-primario btn-sm mt-auto">Agendar</a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="seccion" aria-labelledby="titulo-confianza">
  <div class="contenedor">
    <h2 id="titulo-confianza" class="sr-only">Información de la clínica</h2>
    <div class="glass p-6 md:p-8 grid gap-6 md:grid-cols-3">
      <div class="flex gap-4"><span class="icono-cuadrado"><i data-lucide="clock" class="w-6 h-6"></i></span><div><p class="font-semibold">Horarios de atención</p><p class="text-sm text-muted">Lunes a viernes · 08:00–18:00</p></div></div>
      <div class="flex gap-4"><span class="icono-cuadrado"><i data-lucide="map-pin" class="w-6 h-6"></i></span><div><p class="font-semibold">Ubicación</p><p class="text-sm text-muted">Av. San Martín #450, Equipetrol, Santa Cruz de la Sierra</p></div></div>
      <div class="flex gap-4"><span class="icono-cuadrado"><i data-lucide="phone" class="w-6 h-6"></i></span><div><p class="font-semibold">Teléfono</p><p class="text-sm text-muted">(3) 345-6789 · WhatsApp 700-12345</p></div></div>
    </div>
  </div>
</section>
