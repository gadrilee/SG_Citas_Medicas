<?php
/**
 * @var string      $tipo    cita | pago | estudio | activo
 * @var string|bool $estado  programada, aprobado, disponible… o true/false para 'activo'
 */
$tipo ??= 'cita';
?>
<span class="insignia <?= e(clase_estado($tipo, $estado)) ?>"><?= e(texto_estado($tipo, $estado)) ?></span>
