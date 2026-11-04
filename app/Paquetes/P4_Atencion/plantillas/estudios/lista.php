<?php
/**
 * CU19 · Estudios de los pacientes del médico: marcar en proceso y publicar resultados.
 * @var array $resultado  Eestudio_laboratorio::delMedico (paginado)
 * @var array $conteo     [estado => total]
 * @var array $filtros    estado, buscar
 */
$pestanas = [
    'pendientes'  => ['Por publicar', (int) $conteo['pendiente'] + (int) $conteo['en_proceso']],
    'disponible'  => ['Publicados', (int) $conteo['disponible']],
    ''            => ['Todos', array_sum(array_map('intval', $conteo))],
];
$enlace = static fn (string $estado): string => url('/estudios') . '?' . http_build_query(['estado' => $estado, 'buscar' => $filtros['buscar']]);
?>
<div class="mb-6">
  <p class="sobretitulo">CU19 · Laboratorio</p>
  <h1 class="titulo-pagina">Estudios</h1>
  <p class="texto">Estudios que solicitaste en tus consultas. Publica el resultado en texto o adjunta el informe en PDF; recién entonces el paciente puede verlo.</p>
</div>

<section class="grid gap-4 grid-cols-3 mb-6" aria-label="Resumen">
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="clock"></i></span><p class="numero-kpi mt-3"><?= (int) $conteo['pendiente'] ?></p><p class="text-sm text-muted">Pendientes</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="loader"></i></span><p class="numero-kpi mt-3"><?= (int) $conteo['en_proceso'] ?></p><p class="text-sm text-muted">En proceso</p></div>
  <div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="file-check-2"></i></span><p class="numero-kpi mt-3"><?= (int) $conteo['disponible'] ?></p><p class="text-sm text-muted">Publicados</p></div>
</section>

<div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
  <nav class="glass-pill pestanas" aria-label="Filtrar por estado">
    <?php foreach ($pestanas as $estado => [$texto, $total]): ?>
      <a href="<?= e($enlace($estado)) ?>" class="pestana" aria-selected="<?= $filtros['estado'] === $estado ? 'true' : 'false' ?>"<?= $filtros['estado'] === $estado ? ' aria-current="page"' : '' ?>><?= e($texto) ?> (<?= $total ?>)</a>
    <?php endforeach; ?>
  </nav>
  <form method="get" action="<?= e(url('/estudios')) ?>" class="md:w-80" role="search">
    <input type="hidden" name="estado" value="<?= e($filtros['estado']) ?>">
    <label for="e-buscar" class="sr-only">Buscar estudio</label>
    <div class="campo-con-icono"><i data-lucide="search"></i><input id="e-buscar" name="buscar" type="search" class="campo" placeholder="Paciente, estudio o CM-0001…" value="<?= e($filtros['buscar']) ?>"></div>
  </form>
</div>

<section class="glass tabla-contenedor" aria-label="Estudios">
  <?php if (!$resultado['filas']): ?>
    <?= parcial('estado_vacio', ['icono' => 'flask-conical', 'titulo' => $filtros['estado'] === 'pendientes' ? 'No tienes estudios por publicar' : 'No hay estudios para mostrar', 'texto' => 'Los estudios se solicitan al registrar una consulta (CU18).']) ?>
  <?php else: ?>
  <div class="px-2 pt-2 md:px-0 md:pt-0">
    <table class="tabla">
      <thead><tr>
        <th scope="col">Paciente</th><th scope="col">Estudio</th><th scope="col">Solicitado</th><th scope="col">Estado</th>
        <th scope="col" data-etiqueta=""><span class="sr-only">Acciones</span></th>
      </tr></thead>
      <tbody>
        <?php foreach ($resultado['filas'] as $estudio): ?>
          <tr>
            <td><span class="block text-fg font-medium"><?= e($estudio['paciente']) ?></span><span class="text-xs font-mono"><?= e($estudio['codigo_paciente']) ?> · <?= e($estudio['cita']) ?></span></td>
            <td class="principal"><span class="block"><?= e($estudio['nombre_estudio']) ?></span><?php if ($estudio['indicaciones']): ?><span class="text-xs text-subtle"><?= e($estudio['indicaciones']) ?></span><?php endif; ?></td>
            <td class="numeros whitespace-nowrap"><?= e(fecha_corta($estudio['solicitado_en'])) ?></td>
            <td>
              <?= parcial('badge_estado', ['tipo' => 'estudio', 'estado' => $estudio['estado']]) ?>
              <?php if ($estudio['estado'] === 'disponible'): ?><span class="block text-xs text-subtle mt-1 numeros"><?= e(fecha_hora($estudio['publicado_en'])) ?></span><?php endif; ?>
            </td>
            <td class="text-right"><div class="flex justify-end gap-1">
              <?php if ($estudio['estado'] === 'pendiente'): ?>
                <form method="post" action="<?= e(url('/estudios/' . $estudio['id'] . '/en-proceso')) ?>"><?= csrf_campo() ?><button type="submit" class="btn-fantasma btn-sm"><?= icono('loader') ?>En proceso</button></form>
              <?php endif; ?>
              <?php if ($estudio['estado'] !== 'disponible'): ?>
                <button type="button" class="btn-primario btn-sm" data-abrir-modal="modal-publicar" data-accion="<?= e(url('/estudios/' . $estudio['id'] . '/publicar')) ?>" data-nombre="<?= e($estudio['nombre_estudio'] . ' · ' . $estudio['paciente']) ?>"><?= icono('upload') ?>Publicar</button>
              <?php else: ?>
                <a href="<?= e(url('/estudios/' . $estudio['id'] . '/resultado')) ?>" class="btn-secundario btn-sm"><?= icono('file-text') ?>Ver informe</a>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <?= parcial('paginacion', ['resultado' => $resultado, 'nombre' => 'estudios']) ?>
</section>

<div id="modal-publicar" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-publicar-titulo" hidden>
  <div class="modal-panel ancho">
    <form method="post" action="<?= e(url('/estudios')) ?>" enctype="multipart/form-data">
      <?= csrf_campo() ?>
      <div class="flex items-start gap-4">
        <span class="icono-cuadrado"><i data-lucide="flask-conical" class="w-6 h-6"></i></span>
        <div class="flex-1 min-w-0">
          <h2 id="modal-publicar-titulo" class="text-lg font-semibold">Publicar resultado</h2>
          <p class="texto text-sm mt-1" data-campo-modal="nombre"></p>
        </div>
        <button type="button" class="btn-icono sm -mt-1 -mr-1" data-cerrar-modal aria-label="Cerrar"><i data-lucide="x" class="w-5 h-5"></i></button>
      </div>
      <div class="grid gap-5 mt-6">
        <div>
          <label for="resultado_texto" class="etiqueta">Resultado</label>
          <textarea id="resultado_texto" name="resultado_texto" class="campo" rows="5" maxlength="10000" placeholder="Valores obtenidos e interpretación…"></textarea>
        </div>
        <label class="zona-arrastre">
          <input type="file" name="archivo" accept="application/pdf,.pdf">
          <i data-lucide="file-up" class="w-6 h-6 texto-acento"></i>
          <span class="font-medium">Adjuntar informe en PDF (opcional)</span>
          <span class="text-sm text-subtle" data-nombre-archivo>Ningún archivo seleccionado · máximo 5 MB</span>
        </label>
        <div class="alerta alerta-aviso"><i data-lucide="info"></i><p>Escribe el resultado, adjunta el PDF o ambos. Una vez publicado, el paciente lo ve en «Mis resultados» y ya no se puede modificar.</p></div>
      </div>
      <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 mt-6">
        <button type="button" class="btn-fantasma" data-cerrar-modal>Volver</button>
        <button type="submit" class="btn-primario"><?= icono('upload') ?>Publicar resultado</button>
      </div>
    </form>
  </div>
</div>
