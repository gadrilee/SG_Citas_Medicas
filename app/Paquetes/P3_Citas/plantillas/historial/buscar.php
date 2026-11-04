<?php
/**
 * CU14 · Paso 1: recepción y administración buscan al paciente.
 * @var string $buscar
 * @var array  $resultado  Epaciente::listar (paginado)
 */
?>
<div class="mb-6">
  <p class="sobretitulo">CU14 · Trazabilidad</p>
  <h1 class="titulo-pagina">Historial de citas</h1>
  <p class="texto">Busca al paciente para ver todas sus citas y los cambios de cada una.</p>
</div>

<form method="get" action="<?= e(url('/historial')) ?>" class="glass p-3 mb-6 flex flex-col md:flex-row gap-3" role="search" aria-label="Buscar paciente">
  <div class="campo-con-icono flex-1">
    <i data-lucide="search"></i>
    <label for="h-buscar" class="sr-only">Buscar por CI, nombre o código</label>
    <input id="h-buscar" name="buscar" type="search" class="campo" placeholder="Buscar por CI, nombre o código del paciente…" value="<?= e($buscar) ?>" autofocus>
  </div>
  <button type="submit" class="btn-primario"><?= icono('search') ?>Buscar</button>
</form>

<section class="glass tabla-contenedor" aria-label="Pacientes">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'user-search', 'titulo' => 'No encontramos pacientes', 'texto' => 'Prueba con otro nombre, CI o código.']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr><th scope="col">Paciente</th><th scope="col">CI</th><th scope="col">Teléfono</th><th scope="col">Última cita</th><th scope="col">Estado</th><th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th></tr></thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $fila): ?>
          <tr>
            <td class="principal"><span class="block text-fg font-medium"><?= e($fila['nombres'] . ' ' . $fila['apellidos']) ?></span><span class="text-xs font-mono"><?= e($fila['codigo']) ?></span></td>
            <td class="font-mono"><?= e($fila['ci'] ?: '—') ?></td>
            <td class="numeros"><?= e($fila['telefono']) ?></td>
            <td class="numeros"><?= e($fila['ultima_cita'] ? fecha_corta($fila['ultima_cita']) : '—') ?></td>
            <td><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $fila['activo']]) ?></td>
            <td class="text-right"><a href="<?= e(url('/historial?paciente_id=' . $fila['id'])) ?>" class="btn-secundario btn-sm"><?= icono('history') ?>Ver historial</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'pacientes']) ?>
</section>
