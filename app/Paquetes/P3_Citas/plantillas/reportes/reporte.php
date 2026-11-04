<?php
/**
 * CU15 · Reportes administrativos (citas, asistencia, pacientes y agenda diaria), imprimibles.
 * @var string $tipo
 * @var array  $tipos          [clave => [nombre corto, descripción]]
 * @var array  $filtros        desde, hasta, fecha, medico_id, especialidad_id, estado_cita_id
 * @var array  $reporte        según el tipo (vacío si hay errores de filtro)
 * @var array  $opciones       medicos, especialidades, estados
 * @var array  $erroresFiltro
 */
$f = static fn (string $clave): string => (string) ($filtros[$clave] ?? '');
$porDia = $tipo === 'agenda';
$usaPeriodo = !$porDia;
$usaFiltrosCita = $tipo !== 'pacientes';
$nombreDe = static function (array $lista, string $valor, string $campo = 'nombre'): ?string {
    foreach ($lista as $item) {
        if ((string) $item['id'] === $valor) {
            return $item[$campo];
        }
    }
    return null;
};
$descripcionFiltros = array_filter([
    $porDia ? 'Fecha: ' . fecha_corta($filtros['fecha']) : 'Período: ' . fecha_corta($filtros['desde']) . ' – ' . fecha_corta($filtros['hasta']),
    $usaFiltrosCita && $f('medico_id') !== '' ? 'Médico: ' . $nombreDe($opciones['medicos'], $f('medico_id')) : null,
    $usaFiltrosCita && $f('especialidad_id') !== '' ? 'Especialidad: ' . $nombreDe($opciones['especialidades'], $f('especialidad_id')) : null,
    $usaFiltrosCita && $f('estado_cita_id') !== '' ? 'Estado: ' . texto_estado('cita', (string) $nombreDe($opciones['estados'], $f('estado_cita_id'))) : null,
]);
$kpi = static fn (string $icono, string|int $valor, string $texto): string =>
    '<div class="glass-card pequena"><span class="icono-kpi"><i data-lucide="' . e($icono) . '"></i></span><p class="numero-kpi mt-3">' . e($valor) . '</p><p class="text-sm text-muted">' . e($texto) . '</p></div>';
$porcentaje = static fn (int $parte, int $total): int => $total ? (int) round($parte * 100 / $total) : 0;
?>
<div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-6">
  <div>
    <p class="sobretitulo">CU15 · Administración</p>
    <h1 class="titulo-pagina">Reportes</h1>
    <p class="texto"><?= e($tipos[$tipo][1]) ?>. Se calculan en el momento con los datos registrados.</p>
  </div>
  <button type="button" class="btn-secundario no-imprimir" data-accion="imprimir"><?= icono('printer') ?>Imprimir</button>
</div>

<nav class="glass-pill pestanas mb-6 no-imprimir" role="tablist" aria-label="Tipo de reporte">
  <?php foreach ($tipos as $clave => [$nombre]): ?>
    <a href="<?= e(url('/reportes/' . $clave)) ?>" class="pestana" role="tab" aria-selected="<?= $clave === $tipo ? 'true' : 'false' ?>"><?= e($nombre) ?></a>
  <?php endforeach; ?>
</nav>

<div class="solo-imprimir mb-4">
  <p class="font-semibold">Clínica Vida Salud · Reporte de <?= e(mb_strtolower($tipos[$tipo][0])) ?></p>
  <p class="text-sm"><?= e(implode(' · ', $descripcionFiltros)) ?> · Generado el <?= e(date('d/m/Y H:i')) ?></p>
</div>

<form method="get" action="<?= e(url('/reportes/' . $tipo)) ?>" class="glass p-4 mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end no-imprimir" aria-label="Filtros del reporte">
  <?php if ($usaPeriodo): ?>
    <div>
      <label for="r-desde" class="etiqueta">Desde</label>
      <input id="r-desde" name="desde" type="date" class="campo<?= marca_error($erroresFiltro, 'desde') ?>" value="<?= e($f('desde')) ?>">
    </div>
    <div>
      <label for="r-hasta" class="etiqueta">Hasta</label>
      <input id="r-hasta" name="hasta" type="date" class="campo<?= marca_error($erroresFiltro, 'hasta') ?>" value="<?= e($f('hasta')) ?>">
    </div>
  <?php else: ?>
    <div>
      <label for="r-fecha" class="etiqueta">Fecha</label>
      <input id="r-fecha" name="fecha" type="date" class="campo<?= marca_error($erroresFiltro, 'fecha') ?>" value="<?= e($f('fecha')) ?>">
    </div>
  <?php endif; ?>
  <?php if ($usaFiltrosCita): ?>
    <div>
      <label for="r-medico" class="etiqueta">Médico</label>
      <select id="r-medico" name="medico_id" class="campo">
        <option value="">Todos</option>
        <?php foreach ($opciones['medicos'] as $medico): ?>
          <option value="<?= e($medico['id']) ?>"<?= $f('medico_id') === (string) $medico['id'] ? ' selected' : '' ?>><?= e($medico['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="r-especialidad" class="etiqueta">Especialidad</label>
      <select id="r-especialidad" name="especialidad_id" class="campo">
        <option value="">Todas</option>
        <?php foreach ($opciones['especialidades'] as $especialidad): ?>
          <option value="<?= e($especialidad['id']) ?>"<?= $f('especialidad_id') === (string) $especialidad['id'] ? ' selected' : '' ?>><?= e($especialidad['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($tipo !== 'resumen'): ?>
      <div>
        <label for="r-estado" class="etiqueta">Estado</label>
        <select id="r-estado" name="estado_cita_id" class="campo">
          <option value="">Todos</option>
          <?php foreach ($opciones['estados'] as $estado): ?>
            <option value="<?= e($estado['id']) ?>"<?= $f('estado_cita_id') === (string) $estado['id'] ? ' selected' : '' ?>><?= e(texto_estado('cita', $estado['nombre'])) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>
  <?php endif; ?>
  <div class="flex gap-2">
    <button type="submit" class="btn-primario flex-1"><?= icono('chart-column') ?>Generar</button>
    <a href="<?= e(url('/reportes/' . $tipo)) ?>" class="btn-icono" aria-label="Restablecer filtros"><i data-lucide="x" class="w-5 h-5"></i></a>
  </div>
  <?php if ($erroresFiltro): ?>
    <div class="sm:col-span-2 lg:col-span-6 alerta alerta-error"><i data-lucide="circle-x"></i><p><?= e(implode(' ', $erroresFiltro)) ?> El reporte no se generó.</p></div>
  <?php endif; ?>
</form>

<?php if (!$erroresFiltro): ?>

<?php if ($tipo === 'citas'): ?>
  <?php $totales = $reporte['totales']; $suma = array_sum(array_map('intval', array_column($totales, 'total'))); ?>
  <section class="grid gap-4 grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 mb-6" aria-label="Totales por estado">
    <?= $kpi('calendar-days', $suma, 'Citas del período') ?>
    <?php foreach ($totales as $nombre => $fila): ?>
      <?= $kpi(['programada' => 'calendar-clock', 'confirmada' => 'user-check', 'atendida' => 'clipboard-check', 'cancelada' => 'calendar-x', 'inasistencia' => 'user-x'][$nombre] ?? 'circle', (int) $fila['total'], texto_estado('cita', $nombre)) ?>
    <?php endforeach; ?>
  </section>
  <section class="glass tabla-contenedor" aria-label="Listado de citas">
    <?php if (!$reporte['citas']): ?>
      <?= parcial('estado_vacio', ['icono' => 'chart-column', 'titulo' => 'Sin citas para estos filtros', 'texto' => 'El reporte está vacío y sus totales en cero.']) ?>
    <?php else: ?>
    <div class="px-2 pt-2 md:px-0 md:pt-0">
      <table class="tabla">
        <thead><tr><th scope="col">Fecha</th><th scope="col">Hora</th><th scope="col">Código</th><th scope="col">Paciente</th><th scope="col">Médico</th><th scope="col">Especialidad</th><th scope="col">Estado</th><th scope="col" class="text-right">Importe</th></tr></thead>
        <tbody>
          <?php foreach ($reporte['citas'] as $cita): ?>
            <tr>
              <td class="numeros"><?= e(fecha_corta($cita['inicio'])) ?></td>
              <td class="numeros"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></td>
              <td class="font-mono"><?= e($cita['codigo']) ?></td>
              <td class="principal"><?= e($cita['paciente']) ?></td>
              <td><?= e($cita['medico']) ?></td>
              <td><?= e($cita['especialidad']) ?></td>
              <td><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></td>
              <td class="numeros text-right"><?= e(bs($cita['importe_acordado'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="7" class="font-semibold">Total: <?= count($reporte['citas']) ?> cita(s)</td><td class="numeros text-right font-semibold"><?= e(bs(array_sum(array_column($reporte['citas'], 'importe_acordado')))) ?></td></tr></tfoot>
      </table>
    </div>
    <?php endif; ?>
  </section>

<?php elseif ($tipo === 'resumen'): ?>
  <?php
    $t = static fn (string $estado): int => (int) ($reporte['totales'][$estado]['total'] ?? 0);
    $cerradas = $t('atendida') + $t('cancelada') + $t('inasistencia');
    $tablaResumen = static function (array $filas, string $encabezado) use ($porcentaje): string {
        ob_start(); ?>
        <div class="px-2 pt-2 md:px-0 md:pt-0">
          <table class="tabla">
            <thead><tr><th scope="col"><?= e($encabezado) ?></th><th scope="col" class="text-right">Total</th><th scope="col" class="text-right">Vigentes</th><th scope="col" class="text-right">Atendidas</th><th scope="col" class="text-right">Canceladas</th><th scope="col" class="text-right">Inasistencias</th><th scope="col">Asistencia</th><th scope="col" class="text-right">Facturado</th></tr></thead>
            <tbody>
              <?php foreach ($filas as $fila): ?>
                <?php $asistencia = $porcentaje((int) $fila['atendidas'], (int) $fila['atendidas'] + (int) $fila['inasistencias']); ?>
                <tr>
                  <td class="principal"><?= e($fila['grupo']) ?></td>
                  <td class="numeros text-right"><?= (int) $fila['total'] ?></td>
                  <td class="numeros text-right"><?= (int) $fila['vigentes'] ?></td>
                  <td class="numeros text-right"><?= (int) $fila['atendidas'] ?></td>
                  <td class="numeros text-right"><?= (int) $fila['canceladas'] ?></td>
                  <td class="numeros text-right"><?= (int) $fila['inasistencias'] ?></td>
                  <td class="min-w-[140px]"><div class="flex items-center gap-2"><div class="barra-horizontal flex-1"><span style="width:<?= $asistencia ?>%"></span></div><span class="text-xs numeros"><?= $asistencia ?>%</span></div></td>
                  <td class="numeros text-right"><?= e(bs($fila['importe_atendidas'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php return (string) ob_get_clean();
    };
  ?>
  <section class="grid gap-4 grid-cols-2 lg:grid-cols-5 mb-6" aria-label="Totales del período">
    <?= $kpi('clipboard-check', $t('atendida'), 'Atendidas') ?>
    <?= $kpi('calendar-x', $t('cancelada'), 'Canceladas') ?>
    <?= $kpi('user-x', $t('inasistencia'), 'Inasistencias') ?>
    <?= $kpi('calendar-clock', $t('programada') + $t('confirmada'), 'Aún vigentes') ?>
    <?= $kpi('percent', $porcentaje($t('atendida'), $t('atendida') + $t('inasistencia')) . '%', 'Asistencia') ?>
  </section>
  <?php if (!$reporte['porMedico']): ?>
    <div class="glass"><?= parcial('estado_vacio', ['icono' => 'chart-column', 'titulo' => 'Sin citas en el período', 'texto' => 'Los totales están en cero.']) ?></div>
  <?php else: ?>
    <section class="glass tabla-contenedor mb-6" aria-labelledby="t-por-medico">
      <h2 id="t-por-medico" class="font-semibold p-6 pb-2">Por médico</h2>
      <?= $tablaResumen($reporte['porMedico'], 'Médico') ?>
    </section>
    <section class="glass tabla-contenedor" aria-labelledby="t-por-especialidad">
      <h2 id="t-por-especialidad" class="font-semibold p-6 pb-2">Por especialidad</h2>
      <?= $tablaResumen($reporte['porEspecialidad'], 'Especialidad') ?>
    </section>
    <p class="text-xs text-subtle mt-3">Asistencia = atendidas ÷ (atendidas + inasistencias). <?= $cerradas ?> cita(s) cerradas en el período.</p>
  <?php endif; ?>

<?php elseif ($tipo === 'pacientes'): ?>
  <?php $pacientes = $reporte['pacientes']; ?>
  <section class="grid gap-4 grid-cols-2 lg:grid-cols-4 mb-6" aria-label="Totales">
    <?= $kpi('user-plus', count($pacientes), 'Registrados en el período') ?>
    <?= $kpi('user-check', count(array_filter($pacientes, static fn (array $p): bool => (bool) $p['activo'])), 'Activos') ?>
    <?= $kpi('calendar-days', array_sum(array_column($pacientes, 'citas')), 'Citas de estos pacientes') ?>
  </section>
  <section class="glass tabla-contenedor" aria-label="Pacientes registrados">
    <?php if (!$pacientes): ?>
      <?= parcial('estado_vacio', ['icono' => 'users-round', 'titulo' => 'Sin pacientes registrados en el período']) ?>
    <?php else: ?>
    <div class="px-2 pt-2 md:px-0 md:pt-0">
      <table class="tabla">
        <thead><tr><th scope="col">Registro</th><th scope="col">Código</th><th scope="col">Paciente</th><th scope="col">CI</th><th scope="col">Teléfono</th><th scope="col" class="text-right">Citas</th><th scope="col">Estado</th></tr></thead>
        <tbody>
          <?php foreach ($pacientes as $paciente): ?>
            <tr>
              <td class="numeros"><?= e(fecha_corta($paciente['fecha_registro'])) ?></td>
              <td class="font-mono"><?= e($paciente['codigo']) ?></td>
              <td class="principal"><?= e($paciente['nombre']) ?></td>
              <td class="font-mono"><?= e($paciente['ci'] ?: '—') ?></td>
              <td class="numeros"><?= e($paciente['telefono']) ?></td>
              <td class="numeros text-right"><?= (int) $paciente['citas'] ?></td>
              <td><?= parcial('badge_estado', ['tipo' => 'activo', 'estado' => (bool) $paciente['activo']]) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

<?php else: ?>
  <?php
    $grupos = [];
    foreach ($reporte['citas'] as $cita) {
        $grupos[$cita['medico']][] = $cita;
    }
  ?>
  <?php if (!$grupos): ?>
    <div class="glass"><?= parcial('estado_vacio', ['icono' => 'calendar-x', 'titulo' => 'Sin citas programadas', 'texto' => 'No hay citas para la fecha y los filtros elegidos.']) ?></div>
  <?php else: ?>
    <div class="grid gap-6">
      <?php foreach ($grupos as $medico => $citasMedico): ?>
        <section class="glass tabla-contenedor" aria-label="Agenda de <?= e($medico) ?>">
          <div class="flex flex-wrap items-center justify-between gap-2 p-6 pb-2">
            <h2 class="font-semibold"><?= e($medico) ?></h2>
            <span class="text-sm text-subtle"><?= count($citasMedico) ?> cita(s)</span>
          </div>
          <div class="px-2 pb-2 md:px-0">
            <table class="tabla">
              <thead><tr><th scope="col">Hora</th><th scope="col">Código</th><th scope="col">Paciente</th><th scope="col">Especialidad</th><th scope="col">Motivo</th><th scope="col">Estado</th></tr></thead>
              <tbody>
                <?php foreach ($citasMedico as $cita): ?>
                  <tr>
                    <td class="numeros principal"><?= e(rango_horas($cita['inicio'], $cita['fin'])) ?></td>
                    <td class="font-mono"><?= e($cita['codigo']) ?></td>
                    <td><?= e($cita['paciente']) ?></td>
                    <td><?= e($cita['especialidad']) ?></td>
                    <td class="text-muted"><?= e($cita['motivo'] ?: '—') ?></td>
                    <td><?= parcial('badge_estado', ['tipo' => 'cita', 'estado' => $cita['estado']]) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php endif; ?>
