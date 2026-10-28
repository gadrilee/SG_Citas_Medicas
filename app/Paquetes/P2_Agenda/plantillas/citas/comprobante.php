<?php
/**
 * CU09 · Comprobante de la cita (y su historial de eventos).
 * @var array $cita       Ecita_medica::buscarDetalle
 * @var array $historial  Ehistorial_cita::deCita
 * @var bool  $pagoAprobado  A1 de CU11: avisa que corresponde la devolución
 */
$vigente = in_array((int) $cita['estado_cita_id'], [1, 2], true) && strtotime($cita['inicio']) > time();
// CU20: se paga una cita programada o confirmada con importe y sin pago aprobado.
$pagable = in_array((int) $cita['estado_cita_id'], [1, 2], true) && (float) $cita['importe_acordado'] > 0 && !$pagoAprobado;
$foto = foto_medico($cita['codigo_medico']);
// QR decorativo (11 × 11) determinado por el id de la cita, como en el diseño de V0.
$semilla = (int) $cita['id'] * 9301 + 49297;
?>
<div class="max-w-3xl mx-auto">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6 no-imprimir">
    <a href="<?= e(url('/panel')) ?>" class="btn-fantasma btn-sm -ml-3"><?= icono('arrow-left') ?>Volver al inicio</a>
    <div class="flex flex-wrap gap-2">
      <button type="button" class="btn-secundario" data-accion="imprimir"><?= icono('printer') ?>Imprimir</button>
      <?php if ($pagable && puede('pagos.gestionar')): ?>
        <a href="<?= e(url('/citas/' . $cita['id'] . '/pagar')) ?>" class="btn-primario"><?= icono('credit-card') ?>Pagar</a>
      <?php endif; ?>
      <?php if (puede('consultas.registrar') && (int) $cita['medico_id'] === (int) (usuario_actual()['datos_personales_id'] ?? 0) && in_array((int) $cita['estado_cita_id'], [1, 2, 3], true)): ?>
        <a href="<?= e(url('/citas/' . $cita['id'] . '/consulta')) ?>" class="btn-primario"><?= icono('stethoscope') ?><?= (int) $cita['estado_cita_id'] === 3 ? 'Ver consulta' : 'Registrar consulta' ?></a>
      <?php endif; ?>
      <?php if ($vigente && puede('citas.reprogramar')): ?>
        <a href="<?= e(url('/citas/' . $cita['id'] . '/reprogramar')) ?>" class="btn-secundario"><?= icono('calendar-sync') ?>Reprogramar</a>
      <?php endif; ?>
      <?php if ($vigente && puede('citas.cancelar')): ?>
        <button type="button" class="btn-peligro" data-abrir-modal="modal-cancelar-cita"><?= icono('calendar-x') ?>Cancelar cita</button>
      <?php endif; ?>
      <?php if (puede('citas.reservar')): ?>
        <a href="<?= e(url('/citas/nueva')) ?>" class="btn-primario"><?= icono('calendar-plus') ?>Nueva cita</a>
      <?php endif; ?>
    </div>
  </div>

  <article class="glass p-6 md:p-8" aria-labelledby="t-comprobante">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="sobretitulo">Comprobante de reserva</p>
        <h1 id="t-comprobante" class="text-3xl font-bold font-mono mt-1"><?= e($cita['codigo']) ?></h1>
        <div class="mt-2"><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></div>
      </div>
      <div class="qr-decorativo" aria-hidden="true">
        <?php for ($f = 0; $f < 11; $f++): ?>
          <?php for ($c = 0; $c < 11; $c++): ?>
            <?php $semilla = ($semilla * 9301 + 49297) % 233280; $esquina = ($f < 3 || $f > 7) && ($c < 3 || $c > 7) && !($f > 7 && $c > 7); ?>
            <span<?= $esquina || $semilla / 233280 > 0.52 ? ' class="lleno"' : '' ?>></span>
          <?php endfor; ?>
        <?php endfor; ?>
      </div>
    </div>

    <div class="ticket-corte" aria-hidden="true"></div>

    <div class="flex items-center gap-4 mb-6">
      <?php if ($foto): ?>
        <span class="avatar avatar-md"><img src="<?= e(recurso($foto)) ?>" alt=""></span>
      <?php else: ?>
        <span class="avatar avatar-md" aria-hidden="true"><?= e(iniciales(preg_replace('/^Dra?\. /', '', $cita['medico']))) ?></span>
      <?php endif; ?>
      <div><p class="font-semibold"><?= e($cita['medico']) ?></p><p class="text-sm text-muted"><?= e($cita['especialidad']) ?></p></div>
    </div>

    <dl class="grid gap-5 sm:grid-cols-2 text-sm">
      <div><dt class="etiqueta">Paciente</dt><dd class="font-medium"><?= e($cita['paciente']) ?> <span class="font-mono text-subtle"><?= e($cita['codigo_paciente']) ?></span></dd></div>
      <div><dt class="etiqueta">Fecha</dt><dd class="font-medium numeros"><?= e(fecha_corta($cita['inicio'])) ?></dd></div>
      <div><dt class="etiqueta">Horario</dt><dd class="font-medium numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></dd></div>
      <div><dt class="etiqueta">Modalidad</dt><dd class="font-medium"><?= e(ucfirst($cita['modalidad'])) ?></dd></div>
      <div><dt class="etiqueta">Importe</dt><dd class="font-bold numeros texto-acento"><?= e(bs($cita['importe_acordado'])) ?><?php if ($pagoAprobado): ?> <span class="insignia pago-aprobado ml-1">Pagada</span><?php endif; ?></dd></div>
      <div><dt class="etiqueta">Registrada</dt><dd class="font-medium numeros"><?= e(fecha_hora($cita['fecha_creacion'])) ?></dd></div>
      <?php if ($cita['motivo']): ?>
        <div class="sm:col-span-2"><dt class="etiqueta">Motivo</dt><dd class="font-medium"><?= e($cita['motivo']) ?></dd></div>
      <?php endif; ?>
    </dl>

    <?php if ($vigente): ?>
      <div class="alerta mt-6"><i data-lucide="info"></i><p>Llega 10 minutos antes con tu cédula de identidad y este código.</p></div>
    <?php endif; ?>
  </article>

  <?php if ($historial): ?>
  <section class="glass p-6 md:p-8 mt-6" aria-labelledby="t-historial">
    <h2 id="t-historial" class="font-semibold mb-5">Historial de la cita</h2>
    <?= parcial('eventos_cita', ['eventos' => $historial]) ?>
  </section>
  <?php endif; ?>
</div>

<?php if ($vigente && puede('citas.cancelar')): ?>
<?= parcial('modal_confirmar', [
    'id'     => 'modal-cancelar-cita',
    'titulo' => '¿Cancelar la cita ' . $cita['codigo'] . '?',
    'texto'  => 'La cita se conserva como Cancelada en el historial y el horario queda libre para otros pacientes.',
    'accion' => '/citas/' . $cita['id'] . '/cancelar',
    'boton'  => 'Cancelar cita',
    'icono'  => 'calendar-x',
    'motivo' => true,
    'aviso'  => $pagoAprobado ? 'Esta cita tiene un pago aprobado: al cancelarla corresponde solicitar la devolución total (CU20).' : null,
]) ?>
<?php endif; ?>
