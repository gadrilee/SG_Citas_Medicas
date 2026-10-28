<?php
/**
 * CU20 · «Vida Pay», la pasarela simulada. Representa el sitio externo de pago: no usa el menú del
 * sistema y devuelve su respuesta firmada a /pagos/retorno.
 * @var array $pago   Epago::buscarDetalle
 * @var array $errores
 * @var array $anterior
 */
?>
<div class="w-full max-w-md">
  <div class="flex items-center justify-center gap-2 mb-6">
    <span class="icono-cuadrado"><i data-lucide="shield-check" class="w-6 h-6"></i></span>
    <div><p class="font-bold text-lg leading-tight">Vida Pay</p><p class="text-xs text-subtle">Pasarela de pago simulada</p></div>
  </div>
  <?= parcial('flash') ?>

  <form method="post" action="<?= e(url('/pagos/simulada/' . $pago['id'])) ?>" class="glass p-6 md:p-8 space-y-5" data-validar novalidate>
    <?= csrf_campo() ?>
    <div>
      <p class="text-sm text-muted">Clínica Vida Salud · Cita <span class="font-mono"><?= e($pago['cita']) ?></span></p>
      <div class="flex items-center justify-between rounded-2xl p-4 mt-3 fondo-acento-suave">
        <span class="text-muted">Total a pagar</span>
        <strong class="text-2xl numeros"><?= e(bs($pago['importe'])) ?></strong>
      </div>
      <p class="text-xs text-subtle mt-2">Referencia <span class="font-mono"><?= e($pago['referencia_externa']) ?></span></p>
    </div>
    <div>
      <label for="numero_tarjeta" class="etiqueta">Número de tarjeta</label>
      <input id="numero_tarjeta" name="numero_tarjeta" class="campo numeros<?= marca_error($errores, 'numero_tarjeta') ?>" inputmode="numeric" autocomplete="cc-number" placeholder="4242 4242 4242 4242" required maxlength="25" value="<?= e($anterior['numero_tarjeta'] ?? '') ?>">
      <?= error_campo($errores, 'numero_tarjeta') ?>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label for="vencimiento" class="etiqueta">Vencimiento</label>
        <input id="vencimiento" name="vencimiento" class="campo numeros<?= marca_error($errores, 'vencimiento') ?>" placeholder="MM/AA" autocomplete="cc-exp" required maxlength="7" value="<?= e($anterior['vencimiento'] ?? '') ?>">
        <?= error_campo($errores, 'vencimiento') ?>
      </div>
      <div>
        <label for="cvc" class="etiqueta">CVC</label>
        <input id="cvc" name="cvc" class="campo numeros<?= marca_error($errores, 'cvc') ?>" placeholder="123" inputmode="numeric" autocomplete="cc-csc" required maxlength="4">
        <?= error_campo($errores, 'cvc') ?>
      </div>
    </div>
    <button class="btn-primario btn-grande w-full" type="submit"><i data-lucide="lock-keyhole"></i>Pagar <?= e(bs($pago['importe'])) ?></button>
    <div class="alerta text-sm"><i data-lucide="info"></i><p>Tarjetas de prueba: <span class="font-mono">4242 4242 4242 4242</span> se aprueba y <span class="font-mono">4000 0000 0000 0002</span> se rechaza. No se procesa ningún cargo real.</p></div>
  </form>
  <form method="post" action="<?= e(url('/pagos/simulada/' . $pago['id'])) ?>" class="text-center mt-4">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="cancelar">
    <button type="submit" class="btn-fantasma btn-sm"><?= icono('arrow-left') ?>Cancelar y volver a Vida Salud</button>
  </form>
</div>
