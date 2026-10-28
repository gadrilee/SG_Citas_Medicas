<?php
/**
 * CU20 paso 7 · Comprobante de un intento de pago.
 * @var array $pago  Epago::buscarDetalle
 * @var array $cita  Ecita_medica::buscarDetalle
 */
$esPaciente = (usuario_actual()['perfil'] ?? '') === 'paciente';
?>
<div class="max-w-2xl mx-auto">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6 no-imprimir">
    <a href="<?= e(url($esPaciente ? '/mis-citas' : '/pagos')) ?>" class="btn-fantasma btn-sm -ml-3"><?= icono('arrow-left') ?><?= $esPaciente ? 'Volver a mis citas' : 'Volver a pagos' ?></a>
    <div class="flex gap-2">
      <button type="button" class="btn-secundario" data-accion="imprimir"><?= icono('printer') ?>Imprimir</button>
      <a href="<?= e(url('/citas/' . $cita['id'] . '/comprobante')) ?>" class="btn-secundario"><?= icono('calendar-check') ?>Ver la cita</a>
    </div>
  </div>

  <article class="glass p-6 md:p-8" aria-labelledby="t-pago">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="sobretitulo">Comprobante de pago</p>
        <h1 id="t-pago" class="text-3xl font-bold numeros mt-1"><?= e(bs($pago['importe'])) ?></h1>
        <div class="mt-2"><?= parcial('badge_estado', ['tipo' => 'pago', 'estado' => $pago['estado']]) ?></div>
      </div>
      <span class="icono-cuadrado"><i data-lucide="<?= $pago['estado'] === 'aprobado' ? 'badge-check' : 'receipt-text' ?>" class="w-6 h-6"></i></span>
    </div>

    <div class="ticket-corte" aria-hidden="true"></div>

    <dl class="grid gap-5 sm:grid-cols-2 text-sm">
      <div><dt class="etiqueta">Cita</dt><dd class="font-medium"><span class="font-mono"><?= e($cita['codigo']) ?></span> · <?= e($cita['especialidad']) ?></dd></div>
      <div><dt class="etiqueta">Paciente</dt><dd class="font-medium"><?= e($cita['paciente']) ?></dd></div>
      <div><dt class="etiqueta">Médico</dt><dd class="font-medium"><?= e($cita['medico']) ?></dd></div>
      <div><dt class="etiqueta">Fecha de la cita</dt><dd class="font-medium numeros"><?= e(fecha_corta($cita['inicio'])) ?> · <?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></dd></div>
      <div><dt class="etiqueta">Pasarela</dt><dd class="font-medium"><?= e($pago['pasarela']) ?></dd></div>
      <div><dt class="etiqueta">Referencia</dt><dd class="font-mono break-all"><?= e($pago['referencia_externa'] ?: '—') ?></dd></div>
      <div><dt class="etiqueta">Iniciado</dt><dd class="font-medium numeros"><?= e(fecha_hora($pago['creado_en'])) ?> por <span class="font-mono"><?= e($pago['iniciado_por_usuario']) ?></span></dd></div>
      <div><dt class="etiqueta">Verificado</dt><dd class="font-medium numeros"><?= e($pago['verificado_en'] ? fecha_hora($pago['verificado_en']) : 'Sin verificar') ?></dd></div>
      <div class="sm:col-span-2"><dt class="etiqueta">Clave de idempotencia</dt><dd class="font-mono text-xs text-subtle break-all"><?= e($pago['clave_idempotencia']) ?></dd></div>
    </dl>

    <?php if ($pago['estado'] === 'reembolsado'): ?>
      <div class="alerta mt-6"><i data-lucide="undo-2"></i><p>El importe se devolvió en su totalidad porque la cita fue cancelada.</p></div>
    <?php elseif ($pago['estado'] === 'aprobado' && (int) $pago['estado_cita_id'] === 4): ?>
      <div class="alerta alerta-aviso mt-6"><i data-lucide="info"></i><p>La cita fue cancelada: corresponde la devolución total en caja.</p></div>
    <?php endif; ?>
  </article>
</div>
