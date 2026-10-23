<footer id="contacto" class="pb-8 pt-4 px-4">
  <div class="max-w-6xl mx-auto glass p-8 md:p-10">
    <div class="grid gap-8 md:grid-cols-4">
      <div class="md:col-span-2">
        <a href="<?= e(url('/')) ?>" class="flex items-center gap-2.5"><?= parcial('logo') ?></a>
        <p class="texto mt-4 max-w-sm">Atención ambulatoria programada con especialistas de confianza en Santa Cruz de la Sierra. Reserva tu cita en minutos, sin filas.</p>
      </div>
      <div>
        <p class="font-semibold mb-3">Contacto</p>
        <ul class="space-y-2 text-sm text-muted">
          <li class="flex gap-2"><?= icono('map-pin', 'w-4 h-4 mt-0.5 texto-acento shrink-0') ?>Av. San Martín #450, Equipetrol, Santa Cruz de la Sierra</li>
          <li class="flex gap-2"><?= icono('phone', 'w-4 h-4 mt-0.5 texto-acento shrink-0') ?>(3) 345-6789 · 700-12345</li>
          <li class="flex gap-2"><?= icono('mail', 'w-4 h-4 mt-0.5 texto-acento shrink-0') ?>citas@vidasalud.bo</li>
        </ul>
      </div>
      <div>
        <p class="font-semibold mb-3">Horario de atención</p>
        <ul class="space-y-2 text-sm text-muted">
          <li>Lunes a viernes · 08:00–18:00</li>
          <li>Sábados, domingos y feriados · cerrado</li>
        </ul>
      </div>
    </div>
    <hr class="separador my-6">
    <div class="flex flex-col sm:flex-row gap-2 justify-between text-xs text-subtle">
      <p>© <?= date('Y') ?> Clínica Vida Salud · Grupo Seven · FICCT – UAGRM</p>
      <p>Atención ambulatoria programada · No atendemos urgencias.</p>
    </div>
  </div>
</footer>
