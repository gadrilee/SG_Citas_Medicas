<?php
/**
 * Calendario mensual dibujado por publico/js/cupos.js.
 * @var string      $id
 * @var string|null $seleccionado  AAAA-MM-DD
 * @var string|null $modo          'reserva' deshabilita días pasados, sin atención o bloqueados
 * @var string|null $dias          días ISO de atención "1,3,5" (si no hay select de médico)
 * @var string|null $bloqueados    fechas "2026-11-20,…"
 */
?>
<div id="<?= e($id) ?>" class="glass calendario" data-calendario data-modo="<?= e($modo ?? 'reserva') ?>"
     data-hoy="<?= e(date('Y-m-d')) ?>" data-seleccionado="<?= e($seleccionado ?? '') ?>"
     data-dias="<?= e($dias ?? '') ?>" data-bloqueados="<?= e($bloqueados ?? '') ?>" data-con-citas="" data-leyenda>
  <div data-cuerpo-calendario><div class="esqueleto h-72"></div></div>
</div>
