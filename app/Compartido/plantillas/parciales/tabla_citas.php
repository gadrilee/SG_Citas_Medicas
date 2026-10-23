<?php
/**
 * Tabla compacta de citas (paneles de inicio y ficha del paciente).
 * @var array $citas         Ecita_medica::proximas / dePaciente
 * @var bool  $con_paciente  muestra la columna de paciente
 * @var bool  $con_medico    muestra la columna de médico
 * @var bool  $acciones      enlaces a comprobante (CU09) y reprogramación (CU10)
 */
$reprogramable = static fn (array $cita): bool => in_array((int) $cita['estado_cita_id'], [1, 2], true)
    && strtotime($cita['inicio']) > time() && puede('citas.reprogramar');
?>
<div class="px-2 pb-2 md:px-0">
  <table class="tabla">
    <thead><tr>
      <th scope="col">Fecha</th><th scope="col">Hora</th>
      <?php if (!empty($con_paciente)): ?><th scope="col">Paciente</th><?php endif; ?>
      <?php if (!empty($con_medico)): ?><th scope="col">Médico</th><?php endif; ?>
      <th scope="col">Especialidad</th><th scope="col">Estado</th>
      <?php if (!empty($acciones)): ?><th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th><?php endif; ?>
    </tr></thead>
    <tbody>
      <?php foreach ($citas as $cita): ?>
        <tr>
          <td class="numeros"><?= e(fecha_corta($cita['inicio'])) ?></td>
          <td class="numeros principal"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></td>
          <?php if (!empty($con_paciente)): ?>
            <td><span class="block text-fg font-medium"><?= e($cita['paciente']) ?></span><span class="text-xs font-mono"><?= e($cita['codigo']) ?></span></td>
          <?php endif; ?>
          <?php if (!empty($con_medico)): ?><td><?= e($cita['medico']) ?></td><?php endif; ?>
          <td><?= e($cita['especialidad']) ?></td>
          <td><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></td>
          <?php if (!empty($acciones)): ?>
            <td class="text-right"><div class="flex justify-end gap-1">
              <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-icono sm" aria-label="Comprobante de <?= e($cita['codigo']) ?>"><i data-lucide="receipt-text" class="w-4 h-4"></i></a>
              <?php if ($reprogramable($cita)): ?>
                <a href="<?= e(url('/citas/' . $cita['id'] . '/reprogramar')) ?>" class="btn-icono sm" aria-label="Reprogramar <?= e($cita['codigo']) ?>"><i data-lucide="calendar-sync" class="w-4 h-4"></i></a>
              <?php endif; ?>
            </div></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
