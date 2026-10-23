<?php
/**
 * CU04 · Ficha del paciente.
 * @var array      $paciente   Epaciente::buscarDetalle
 * @var array      $contactos
 * @var array|null $ficha      ficha_medica
 * @var array      $citas      Ecita_medica::dePaciente
 * @var array      $pagos      Epago::dePaciente
 */
$nombre = $paciente['nombres'] . ' ' . $paciente['apellidos'];
?>
<a href="<?= e(url('/pacientes')) ?>" class="btn-fantasma btn-sm -ml-3 mb-4"><?= icono('arrow-left') ?>Volver a pacientes</a>

<section class="glass-card mb-6 flex flex-col md:flex-row md:items-center gap-5" aria-labelledby="nombre-paciente">
  <span class="avatar avatar-lg" aria-hidden="true"><?= e(iniciales($nombre)) ?></span>
  <div class="flex-1 min-w-0">
    <div class="flex flex-wrap items-center gap-2">
      <h1 id="nombre-paciente" class="text-2xl font-bold"><?= e($nombre) ?></h1>
      <?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $paciente['activo']]) ?>
    </div>
    <p class="texto text-sm mt-1"><span class="font-mono"><?= e($paciente['codigo']) ?></span> · CI <span class="numeros"><?= e($paciente['ci'] ?: '—') ?></span> · <?= e(edad($paciente['fecha_nacimiento'])) ?><?= $paciente['sexo'] ? ' · ' . e($paciente['sexo']) : '' ?></p>
    <div class="flex flex-wrap gap-2 mt-3">
      <?php if (!empty($ficha['alergias'])): ?>
        <span class="insignia insignia-peligro sin-punto"><?= icono('triangle-alert', 'w-3.5 h-3.5') ?>Alergia: <?= e($ficha['alergias']) ?></span>
      <?php endif; ?>
      <?php if (!empty($ficha['tipo_sangre'])): ?><span class="insignia insignia-neutra">Grupo <?= e($ficha['tipo_sangre']) ?></span><?php endif; ?>
      <span class="insignia insignia-neutra"><?= $paciente['nombre_usuario'] ? 'Cuenta: ' . e($paciente['nombre_usuario']) : 'Sin cuenta web' ?></span>
    </div>
  </div>
  <div class="flex flex-wrap gap-2">
    <a href="<?= e(url('/pacientes/' . $paciente['id'] . '/editar')) ?>" class="btn-secundario"><?= icono('pencil') ?>Editar</a>
    <?php if ($paciente['activo']): ?>
      <a href="<?= e(url('/citas/nueva?paciente_id=' . $paciente['id'])) ?>" class="btn-primario"><?= icono('calendar-plus') ?>Nueva cita</a>
      <button type="button" class="btn-peligro" data-abrir-modal="modal-desactivar-paciente"><?= icono('user-x') ?>Desactivar</button>
    <?php else: ?>
      <form method="post" action="<?= e(url('/pacientes/' . $paciente['id'] . '/activar')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-primario"><?= icono('user-check') ?>Reactivar</button></form>
    <?php endif; ?>
  </div>
</section>

<div class="glass-pill pestanas mb-6" role="tablist" aria-label="Información del paciente" data-pestanas="ficha">
  <button type="button" class="pestana" role="tab" id="tab-resumen" aria-controls="panel-resumen" aria-selected="true" data-pestana="resumen">Resumen</button>
  <button type="button" class="pestana" role="tab" id="tab-citas" aria-controls="panel-citas" aria-selected="false" data-pestana="citas">Citas (<?= count($citas) ?>)</button>
  <button type="button" class="pestana" role="tab" id="tab-pagos" aria-controls="panel-pagos" aria-selected="false" data-pestana="pagos">Pagos</button>
</div>

<section id="panel-resumen" role="tabpanel" aria-labelledby="tab-resumen" data-panel-pestana="ficha:resumen" class="grid gap-6 lg:grid-cols-3">
  <div class="glass-card lg:col-span-2">
    <h2 class="font-semibold mb-5">Contacto</h2>
    <dl class="grid gap-5 sm:grid-cols-2 text-sm">
      <div><dt class="etiqueta">Teléfono</dt><dd class="font-medium numeros"><a href="tel:<?= e($paciente['telefono']) ?>" class="hover:underline"><?= e($paciente['telefono']) ?></a></dd></div>
      <div><dt class="etiqueta">Correo</dt><dd class="font-medium"><?= $paciente['correo'] ? '<a href="mailto:' . e($paciente['correo']) . '" class="hover:underline">' . e($paciente['correo']) . '</a>' : '—' ?></dd></div>
      <div class="sm:col-span-2"><dt class="etiqueta">Dirección</dt><dd class="font-medium"><?= e($paciente['direccion'] ?: '—') ?></dd></div>
      <div><dt class="etiqueta">Fecha de nacimiento</dt><dd class="font-medium numeros"><?= e(fecha_corta($paciente['fecha_nacimiento'])) ?></dd></div>
      <div><dt class="etiqueta">Registrado</dt><dd class="font-medium numeros"><?= e(fecha_corta($paciente['fecha_registro'])) ?></dd></div>
    </dl>
    <hr class="separador my-6">
    <h2 class="font-semibold mb-4">Contactos de emergencia</h2>
    <?php if (!$contactos): ?>
      <p class="texto text-sm">Sin contactos registrados.</p>
    <?php else: ?>
      <ul class="grid gap-3 sm:grid-cols-2">
        <?php foreach ($contactos as $contacto): ?>
          <li class="glass pequena p-4" style="border-radius:16px"><p class="font-medium"><?= e($contacto['nombre']) ?></p><p class="text-sm text-muted"><?= e($contacto['parentesco']) ?> · <span class="numeros"><?= e($contacto['telefono']) ?></span></p></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="grid gap-4 grid-cols-2 lg:grid-cols-1 h-fit">
    <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-check"></i></span><p class="numero-kpi mt-3"><?= e($paciente['atendidas']) ?></p><p class="text-sm text-muted">Citas atendidas</p></div>
    <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="calendar-x"></i></span><p class="numero-kpi mt-3"><?= e($paciente['inasistencias']) ?></p><p class="text-sm text-muted">Inasistencias</p></div>
  </div>
</section>

<section id="panel-citas" role="tabpanel" aria-labelledby="tab-citas" data-panel-pestana="ficha:citas" hidden>
  <div class="glass tabla-contenedor">
    <?php if (!$citas): ?>
      <?= parcial('estado_vacio', ['icono' => 'calendar-x', 'titulo' => 'Sin citas registradas', 'texto' => 'Las citas del paciente aparecerán aquí.']) ?>
    <?php else: ?>
      <div class="pt-2"><?= parcial('tabla_citas', ['citas' => $citas, 'con_medico' => true, 'acciones' => true]) ?></div>
    <?php endif; ?>
  </div>
</section>

<section id="panel-pagos" role="tabpanel" aria-labelledby="tab-pagos" data-panel-pestana="ficha:pagos" hidden>
  <div class="glass tabla-contenedor">
    <?php if (!$pagos): ?>
      <?= parcial('estado_vacio', ['icono' => 'wallet', 'titulo' => 'Sin pagos registrados']) ?>
    <?php else: ?>
    <div class="px-2 pt-2 md:px-0 md:pt-0">
      <table class="tabla">
        <thead><tr><th scope="col">Fecha</th><th scope="col">Cita</th><th scope="col">Pasarela</th><th scope="col">Referencia</th><th scope="col" class="text-right">Monto</th><th scope="col">Estado</th></tr></thead>
        <tbody>
          <?php foreach ($pagos as $pago): ?>
            <tr>
              <td class="numeros"><?= e(fecha_corta($pago['creado_en'])) ?></td>
              <td class="font-mono"><?= e($pago['cita']) ?></td>
              <td><?= e($pago['pasarela']) ?></td>
              <td class="font-mono text-subtle"><?= e($pago['referencia_externa'] ?: '—') ?></td>
              <td class="numeros text-right font-medium"><?= e(bs($pago['importe'])) ?></td>
              <td><?= parcial('badge_estado', ['tipo' => 'pago', 'estado' => $pago['estado']]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>

<?= parcial('modal_confirmar', [
    'id' => 'modal-desactivar-paciente', 'titulo' => '¿Desactivar a ' . $nombre . '?', 'icono' => 'user-x',
    'accion' => '/pacientes/' . $paciente['id'] . '/desactivar', 'boton' => 'Desactivar',
    'texto' => 'No podrá recibir nuevas citas. Su historial, citas y pagos se conservan y puedes reactivarlo después.',
]) ?>
