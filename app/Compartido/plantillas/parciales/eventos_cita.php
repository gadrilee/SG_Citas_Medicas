<?php
/**
 * Línea de tiempo de los eventos de una cita (CU09 comprobante, CU14 historial).
 * @var array $eventos  Ehistorial_cita::deCita / deCitas[cita_id]
 */
$nombres = ['creacion' => 'Cita creada', 'cambio_estado' => 'Cambio de estado', 'reprogramacion' => 'Reprogramada'];
?>
<ol class="linea-tiempo">
  <?php foreach ($eventos as $evento): ?>
    <li class="evento-tiempo">
      <p class="font-medium"><?= e($nombres[$evento['tipo_evento']] ?? $evento['tipo_evento']) ?>
        <?php if ($evento['tipo_evento'] === 'cambio_estado'): ?>
          · <?= e(texto_estado('cita', $evento['estado_anterior'])) ?> → <?= e(texto_estado('cita', $evento['estado_nuevo'])) ?>
        <?php endif; ?>
      </p>
      <?php if ($evento['tipo_evento'] === 'reprogramacion'): ?>
        <p class="text-sm text-muted numeros"><?= e(fecha_corta($evento['inicio_anterior']) . ' ' . rango_horas($evento['inicio_anterior'], $evento['fin_anterior'])) ?> → <?= e(fecha_corta($evento['inicio_nuevo']) . ' ' . rango_horas($evento['inicio_nuevo'], $evento['fin_nuevo'])) ?></p>
      <?php endif; ?>
      <?php if ($evento['observacion']): ?><p class="text-sm text-muted">Motivo: <?= e($evento['observacion']) ?></p><?php endif; ?>
      <p class="text-xs text-subtle"><?= e(fecha_hora($evento['fecha_hora'])) ?> · <span class="font-mono"><?= e($evento['nombre_usuario']) ?></span></p>
    </li>
  <?php endforeach; ?>
</ol>
