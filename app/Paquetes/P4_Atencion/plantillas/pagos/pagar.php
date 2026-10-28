<?php
/**
 * CU20 · Pagar una cita: resumen, intentos anteriores y botón «Pagar».
 * @var array       $cita             Ecita_medica::buscarDetalle
 * @var array       $intentos         Epago::deCita (el más reciente primero)
 * @var string|null $motivoNoPagable  null = se puede pagar
 * @var array|null  $pasarela         pasarela activa (pasarela_pago)
 */
$aprobado = null;
$pendiente = null;
foreach ($intentos as $intento) {
    $aprobado ??= $intento['estado'] === 'aprobado' ? $intento : null;
    $pendiente ??= $intento['estado'] === 'pendiente' ? $intento : null;
}
$volver = (usuario_actual()['perfil'] ?? '') === 'paciente' ? ['/mis-citas', 'Volver a mis citas'] : ['/citas/' . $cita['id'] . '/comprobante', 'Volver a la cita'];
?>
<a href="<?= e(url($volver[0])) ?>" class="btn-fantasma btn-sm -ml-3 mb-4"><?= icono('arrow-left') ?><?= e($volver[1]) ?></a>

<div class="max-w-2xl mx-auto">
  <div class="mb-6">
    <p class="sobretitulo">CU20 · Pago seguro</p>
    <h1 class="titulo-pagina">Pagar cita</h1>
    <p class="texto">Cita <span class="font-mono"><?= e($cita['codigo']) ?></span> · <?= e($cita['especialidad']) ?> con <?= e($cita['medico']) ?>.</p>
  </div>

  <section class="glass p-6 md:p-8" aria-label="Resumen del pago">
    <dl class="grid gap-4 sm:grid-cols-2 text-sm">
      <div><dt class="etiqueta">Paciente</dt><dd class="font-medium"><?= e($cita['paciente']) ?> <span class="font-mono text-subtle"><?= e($cita['codigo_paciente']) ?></span></dd></div>
      <div><dt class="etiqueta">Fecha y hora</dt><dd class="font-medium numeros"><?= e(fecha_corta($cita['inicio'])) ?> · <?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></dd></div>
      <div><dt class="etiqueta">Estado de la cita</dt><dd><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></dd></div>
      <div><dt class="etiqueta">Pasarela</dt><dd class="font-medium"><?= e($pasarela['nombre'] ?? 'No configurada') ?></dd></div>
    </dl>
    <div class="flex items-center justify-between rounded-2xl p-4 mt-6 fondo-acento-suave">
      <span class="text-muted">Total a pagar</span>
      <strong class="text-2xl numeros"><?= e(bs($cita['importe_acordado'])) ?></strong>
    </div>

    <?php if ($aprobado): ?>
      <div class="alerta alerta-exito mt-6"><i data-lucide="circle-check"></i><p>Esta cita ya está pagada (referencia <span class="font-mono"><?= e($aprobado['referencia_externa']) ?></span>).</p></div>
      <a href="<?= e(url('/pagos/' . $aprobado['id'])) ?>" class="btn-primario btn-grande w-full mt-4"><?= icono('receipt-text') ?>Ver comprobante de pago</a>
    <?php elseif ($pendiente): ?>
      <div class="alerta alerta-aviso mt-6"><i data-lucide="hourglass"></i><p>Hay un intento de pago pendiente desde las <?= e(hora_corta($pendiente['creado_en'])) ?>. Verifica con la pasarela si se completó; si no, anúlalo para reintentar.</p></div>
      <div class="flex flex-col sm:flex-row gap-2 mt-4">
        <form method="post" action="<?= e(url('/pagos/' . $pendiente['id'] . '/verificar')) ?>" class="flex-1"><?= csrf_campo() ?><button type="submit" class="btn-primario w-full"><?= icono('refresh-cw') ?>Verificar estado</button></form>
        <form method="post" action="<?= e(url('/pagos/' . $pendiente['id'] . '/anular')) ?>" class="flex-1"><?= csrf_campo() ?><button type="submit" class="btn-secundario w-full"><?= icono('x') ?>Anular intento</button></form>
      </div>
    <?php elseif ($motivoNoPagable): ?>
      <div class="alerta mt-6"><i data-lucide="info"></i><p><?= e($motivoNoPagable) ?></p></div>
    <?php elseif (!$pasarela): ?>
      <div class="alerta alerta-error mt-6"><i data-lucide="circle-x"></i><p>No hay una pasarela de pago activa configurada. Avisa a la administración.</p></div>
    <?php else: ?>
      <form method="post" action="<?= e(url('/citas/' . $cita['id'] . '/pagar')) ?>" class="mt-6">
        <?= csrf_campo() ?>
        <button type="submit" class="btn-primario btn-grande w-full"><?= icono('lock-keyhole') ?>Pagar <?= e(bs($cita['importe_acordado'])) ?></button>
        <p class="text-center text-xs text-subtle mt-3">
          <?= $pasarela['codigo'] === 'stripe' ? 'Serás redirigido a Stripe (modo prueba). Usa la tarjeta 4242 4242 4242 4242.' : 'Modo demostración · No se procesará ningún cargo real.' ?>
        </p>
      </form>
    <?php endif; ?>
  </section>

  <?php if ($intentos): ?>
    <section class="glass tabla-contenedor mt-6" aria-labelledby="t-intentos">
      <h2 id="t-intentos" class="font-semibold px-5 pt-5">Intentos de pago</h2>
      <div class="px-2 pt-2 md:px-0">
        <table class="tabla">
          <thead><tr><th scope="col">Fecha</th><th scope="col">Pasarela</th><th scope="col">Referencia</th><th scope="col">Estado</th></tr></thead>
          <tbody>
            <?php foreach ($intentos as $intento): ?>
              <tr>
                <td class="numeros"><?= e(fecha_hora($intento['creado_en'])) ?></td>
                <td><?= e($intento['pasarela']) ?></td>
                <td class="font-mono text-subtle" title="<?= e($intento['referencia_externa']) ?>"><?= e(mb_strimwidth((string) ($intento['referencia_externa'] ?: '—'), 0, 22, '…')) ?></td>
                <td><?= parcial('badge_estado', ['tipo' => 'pago', 'estado' => $intento['estado']]) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
