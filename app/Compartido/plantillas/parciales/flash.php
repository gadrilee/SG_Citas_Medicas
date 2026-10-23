<?php
/** Mensajes del servidor (Sesion::flash). @var string|null $clase */
use VidaSalud\Nucleo\Sesion;

$iconos = ['exito' => 'circle-check', 'error' => 'circle-x', 'aviso' => 'triangle-alert', 'info' => 'info'];
$mensajes = Sesion::consumirFlash();
?>
<div id="flash" class="<?= e($clase ?? 'space-y-3 mb-6 empty:mb-0') ?>"><?php foreach ($mensajes as $flash): ?>
  <div class="alerta alerta-<?= e($flash['tipo']) ?>" role="<?= $flash['tipo'] === 'error' ? 'alert' : 'status' ?>"><i data-lucide="<?= e($iconos[$flash['tipo']] ?? 'info') ?>"></i><p><?= e($flash['mensaje']) ?></p></div>
<?php endforeach; ?></div>
