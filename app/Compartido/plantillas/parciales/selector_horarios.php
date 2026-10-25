<?php
/**
 * Chips de cupos (CU08). cupos.js los llena desde /api/disponibilidad y copia el elegido a inicio/fin.
 * @var string|null $titulo
 * @var array       $errores
 */
?>
<div class="glass p-5 md:p-6">
  <div class="flex items-center justify-between gap-3 mb-4">
    <h3 class="font-semibold flex items-center gap-2"><i data-lucide="clock" class="w-5 h-5 texto-acento"></i><?= e($titulo ?? 'Horarios disponibles') ?></h3>
    <div class="hidden sm:flex items-center gap-3 text-xs text-muted">
      <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full border" style="border-color:var(--input-border)"></span>Libre</span>
      <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full" style="background:var(--accent-gradient)"></span>Elegido</span>
      <span class="inline-flex items-center gap-1.5 opacity-50 line-through">09:00</span><span>Ocupado</span>
    </div>
  </div>
  <div data-cupos aria-live="polite"></div>
  <input type="hidden" name="inicio" value="" required data-validar-oculto data-mensaje-vacio="Selecciona un horario disponible.">
  <input type="hidden" name="fin" value="">
  <?= error_campo($errores ?? [], 'inicio') ?>
</div>
