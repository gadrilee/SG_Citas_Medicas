<?php
/**
 * CU19 · Informe formal del resultado de laboratorio. Se ve como una hoja A4 y se puede imprimir o
 * descargar en PDF (publico/js/informe.js).
 * @var array $estudio  Eestudio_laboratorio::paraInforme
 */
$numero = 'LAB-' . str_pad((string) $estudio['id'], 6, '0', STR_PAD_LEFT);
$esPaciente = (usuario_actual()['perfil'] ?? '') === 'paciente';
$volver = $esPaciente ? ['/mis-resultados', 'Volver a mis resultados'] : ['/estudios?estado=disponible', 'Volver a estudios'];
$archivo = $numero . '-' . trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $estudio['nombre_estudio']) ?: 'resultado')), '-') . '.pdf';
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-6 no-imprimir">
  <a href="<?= e(url($volver[0])) ?>" class="btn-fantasma btn-sm -ml-3"><?= icono('arrow-left') ?><?= e($volver[1]) ?></a>
  <div class="flex flex-wrap gap-2">
    <?php if ($estudio['archivo_referencia']): ?>
      <a href="<?= e(url('/estudios/' . $estudio['id'] . '/informe')) ?>" target="_blank" rel="noopener" class="btn-secundario"><?= icono('paperclip') ?>PDF del laboratorio</a>
    <?php endif; ?>
    <button type="button" class="btn-secundario" data-accion="imprimir"><?= icono('printer') ?>Imprimir</button>
    <button type="button" class="btn-primario" data-descargar-pdf="#informe" data-archivo="<?= e($archivo) ?>"><?= icono('download') ?>Descargar PDF</button>
  </div>
</div>

<article id="informe" class="informe" aria-labelledby="t-informe">
  <header class="informe-cabecera">
    <img src="<?= e(recurso('img/logo-vida-salud.png')) ?>" alt="Clínica Vida Salud" class="informe-logo">
    <div class="flex-1 min-w-0">
      <p class="informe-clinica">Clínica Vida Salud</p>
      <p class="informe-area">Laboratorio de Análisis Clínicos</p>
      <p class="informe-dato">Av. San Martín #450, Equipetrol · Santa Cruz de la Sierra, Bolivia</p>
      <p class="informe-dato">Tel. (3) 345-6789 · WhatsApp 700-12345</p>
    </div>
    <div class="informe-casillas">
      <div class="informe-casilla"><span>Nº de informe</span><strong><?= e($numero) ?></strong></div>
      <div class="informe-casilla"><span>Fecha de emisión</span><strong><?= e(fecha_corta($estudio['publicado_en'])) ?></strong></div>
    </div>
  </header>

  <h1 id="t-informe" class="informe-titulo">Informe de resultado de laboratorio</h1>

  <section>
    <h2 class="informe-seccion">Datos del paciente</h2>
    <dl class="informe-datos">
      <div class="ancho"><dt>Nombre:</dt><dd><?= e($estudio['paciente']) ?></dd></div>
      <div><dt>Código:</dt><dd><?= e($estudio['codigo_paciente']) ?></dd></div>
      <div><dt>C.I.:</dt><dd><?= e($estudio['ci'] ?: '—') ?></dd></div>
      <div><dt>Fecha de nacimiento:</dt><dd><?= e(fecha_corta($estudio['fecha_nacimiento'])) ?></dd></div>
      <div><dt>Edad:</dt><dd><?= e(edad($estudio['fecha_nacimiento'])) ?></dd></div>
      <div><dt>Sexo:</dt><dd><?= e($estudio['sexo'] ?: '—') ?></dd></div>
      <div><dt>Teléfono:</dt><dd><?= e($estudio['telefono'] ?: '—') ?></dd></div>
    </dl>
  </section>

  <section>
    <h2 class="informe-seccion">Datos de la solicitud</h2>
    <dl class="informe-datos">
      <div class="ancho"><dt>Médico solicitante:</dt><dd><?= e($estudio['medico']) ?></dd></div>
      <div><dt>Especialidad:</dt><dd><?= e($estudio['especialidad']) ?></dd></div>
      <div><dt>Cita:</dt><dd><?= e($estudio['cita']) ?></dd></div>
      <div><dt>Fecha de solicitud:</dt><dd><?= e(fecha_hora($estudio['solicitado_en'])) ?></dd></div>
      <div><dt>Fecha de resultado:</dt><dd><?= e(fecha_hora($estudio['publicado_en'])) ?></dd></div>
      <div class="ancho"><dt>Indicaciones para la toma:</dt><dd><?= e($estudio['indicaciones'] ?: 'Ninguna') ?></dd></div>
    </dl>
  </section>

  <section>
    <h2 class="informe-seccion">Resultados</h2>
    <table class="informe-tabla">
      <thead><tr><th scope="col" style="width:32%">Estudio</th><th scope="col">Resultado</th></tr></thead>
      <tbody>
        <tr>
          <td class="informe-estudio"><?= e($estudio['nombre_estudio']) ?></td>
          <td class="informe-resultado"><?= $estudio['resultado_texto'] ? e($estudio['resultado_texto']) : 'Ver el informe del laboratorio adjunto en PDF.' ?></td>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="informe-observaciones">
    <h2 class="informe-seccion">Observaciones</h2>
    <p>Resultado validado y publicado en el sistema el <?= e(fecha_hora($estudio['publicado_en'])) ?>.</p>
    <?php if ($estudio['archivo_referencia']): ?>
      <p>Se adjunta el informe original del laboratorio en PDF, disponible en el sistema.</p>
    <?php endif; ?>
    <p>Este informe debe ser interpretado por su médico tratante.</p>
  </section>

  <footer class="informe-firma">
    <div class="linea" aria-hidden="true"></div>
    <p class="nombre"><?= e($estudio['responsable'] ?: $estudio['publicado_por_usuario']) ?></p>
    <?php if ($estudio['matricula_responsable']): ?><p>Mat. Prof. <?= e($estudio['matricula_responsable']) ?></p><?php endif; ?>
    <p class="cargo">Responsable</p>
  </footer>

  <p class="informe-pie">Documento emitido por el Sistema de Gestión de Citas Médicas · Clínica Vida Salud · <?= e($numero) ?> · Generado el <?= e(date('d/m/Y H:i')) ?></p>
</article>

<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.2/dist/html2pdf.bundle.min.js"></script>
<script src="<?= e(recurso('js/informe.js')) ?>"></script>
