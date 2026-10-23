<?php
/**
 * Tabla compacta de próximas citas para los paneles de inicio.
 * @var array  $citas         Ecita_medica::proximas
 * @var bool   $con_paciente  muestra la columna de paciente
 * @var bool   $con_medico    muestra la columna de médico
 */
?>
<div class="px-2 pb-2 md:px-0">
  <table class="tabla">
    <thead><tr>
      <th scope="col">Fecha</th><th scope="col">Hora</th>
      <?php if (!empty($con_paciente)): ?><th scope="col">Paciente</th><?php endif; ?>
      <?php if (!empty($con_medico)): ?><th scope="col">Médico</th><?php endif; ?>
      <th scope="col">Especialidad</th><th scope="col">Estado</th>
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
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
