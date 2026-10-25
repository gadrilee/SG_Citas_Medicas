/* Vida Salud — Calendario, chips de horarios y reserva
   - [data-calendario]: calendario mensual (atributos data-mes, data-hoy, data-seleccionado,
     data-con-citas, data-bloqueados, data-modo="reserva|simple", data-campo-fecha).
   - [data-reserva]: raíz de una reserva. Contiene selects de especialidad/médico,
     un calendario, un contenedor [data-cupos] e inputs ocultos inicio y fin.
   - Los cupos vienen de GET /api/disponibilidad (CU08). El <option> del médico trae data-dias
     (días ISO en que atiende) y data-bloqueados; el de la especialidad, data-importe. */
(function () {
  var NOMBRES_MES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  var DIAS_CORTOS = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];
  var DIAS_LARGOS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

  function iso(fecha) {
    return fecha.getFullYear() + '-' + String(fecha.getMonth() + 1).padStart(2, '0') + '-' + String(fecha.getDate()).padStart(2, '0');
  }
  function desdeISO(texto) { return new Date(texto + 'T12:00:00'); }
  function formatoFecha(texto) { var p = texto.split('-'); return p[2] + '/' + p[1] + '/' + p[0]; }
  function formatoFechaLarga(texto) { return DIAS_LARGOS[desdeISO(texto).getDay()] + ' ' + formatoFecha(texto); }
  function horaDe(isoCompleto) { return isoCompleto.slice(11, 16); }
  function formatoBs(n) {
    return 'Bs ' + Number(n).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  function lista(texto) { return (texto || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean); }

  /* ---------- Lectura de valores (select visible o input oculto) ---------- */
  function campo(raiz, nombre) {
    return raiz.querySelector('[data-sincroniza="' + nombre + '"]') || raiz.querySelector('[name="' + nombre + '"]');
  }
  function valor(raiz, nombre) { var c = campo(raiz, nombre); return c ? c.value : ''; }
  function fijarOculto(raiz, nombre, v) {
    raiz.querySelectorAll('input[type="hidden"][name="' + nombre + '"]').forEach(function (i) {
      if (i.value === String(v)) return;
      i.value = v;
    });
  }
  function resumen(raiz, clave, texto) {
    raiz.querySelectorAll('[data-resumen="' + clave + '"]').forEach(function (el) {
      el.textContent = texto;
      el.classList.toggle('text-subtle', !texto || texto === '—');
    });
  }

  /* ---------- Calendario ---------- */
  function dibujarCalendario(cal) {
    var hoy = cal.getAttribute('data-hoy') || iso(new Date());
    var seleccionado = cal.getAttribute('data-seleccionado') || '';
    var mes = cal.getAttribute('data-mes') || (seleccionado || hoy).slice(0, 7);
    cal.setAttribute('data-mes', mes);
    var conCitas = lista(cal.getAttribute('data-con-citas'));
    var modo = cal.getAttribute('data-modo') || 'simple';
    var raiz = cal.closest('[data-reserva]');
    var medicoId = raiz ? valor(raiz, 'medico_id') : '';
    // Días de atención y bloqueos: del médico elegido en el select o fijos en el calendario.
    var opcionMedico = raiz && medicoId ? opcionElegida(campo(raiz, 'medico_id')) : null;
    var diasAtencion = lista((opcionMedico && opcionMedico.getAttribute('data-dias')) || cal.getAttribute('data-dias'));
    var bloqueados = lista(cal.getAttribute('data-bloqueados')).concat(lista(opcionMedico && opcionMedico.getAttribute('data-bloqueados')));

    var anio = Number(mes.slice(0, 4)), numMes = Number(mes.slice(5, 7)) - 1;
    var primero = new Date(anio, numMes, 1, 12);
    var desplazamiento = (primero.getDay() + 6) % 7;
    var diasMes = new Date(anio, numMes + 1, 0).getDate();

    var html = '';
    html += '<div class="flex items-center justify-between mb-3">';
    html += '<button type="button" class="btn-icono sm" data-mover-mes="-1" aria-label="Mes anterior"><i data-lucide="chevron-left"></i></button>';
    html += '<p class="font-semibold capitalize" aria-live="polite">' + NOMBRES_MES[numMes] + ' ' + anio + '</p>';
    html += '<button type="button" class="btn-icono sm" data-mover-mes="1" aria-label="Mes siguiente"><i data-lucide="chevron-right"></i></button>';
    html += '</div><div class="calendario-grilla" role="grid">';
    DIAS_CORTOS.forEach(function (d) { html += '<div class="calendario-dia-semana" role="columnheader">' + d + '</div>'; });
    for (var v = 0; v < desplazamiento; v++) html += '<span aria-hidden="true"></span>';
    for (var dia = 1; dia <= diasMes; dia++) {
      var f = iso(new Date(anio, numMes, dia, 12));
      var clases = ['calendario-dia'];
      var deshabilitado = false;
      var esBloqueado = bloqueados.indexOf(f) !== -1;
      if (f === hoy) clases.push('hoy');
      if (f === seleccionado) clases.push('seleccionado');
      if (conCitas.indexOf(f) !== -1) clases.push('con-citas');
      if (esBloqueado) { clases.push('bloqueado'); deshabilitado = modo === 'reserva'; }
      if (modo === 'reserva') {
        if (f < hoy) deshabilitado = true;
        else if (medicoId && diasAtencion.length && diasAtencion.indexOf(String(((desdeISO(f).getDay() + 6) % 7) + 1)) === -1) deshabilitado = true;
      }
      var etiqueta = formatoFechaLarga(f) + (esBloqueado ? ', bloqueado' : '') + (conCitas.indexOf(f) !== -1 ? ', con citas' : '');
      html += '<button type="button" role="gridcell" class="' + clases.join(' ') + '" data-fecha="' + f + '" aria-label="' + etiqueta + '"' +
        (f === seleccionado ? ' aria-selected="true"' : '') + (deshabilitado ? ' disabled' : '') + '>' + dia + '</button>';
    }
    html += '</div>';
    if (cal.hasAttribute('data-leyenda')) {
      html += '<div class="flex flex-wrap gap-x-4 gap-y-2 mt-4 text-xs text-muted">' +
        '<span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full" style="background:var(--accent-gradient)"></span>Seleccionado</span>' +
        '<span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full border border-accent"></span>Hoy</span>' +
        '<span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-accent"></span>Con citas</span>' +
        '<span class="inline-flex items-center gap-1.5 line-through opacity-60">15</span><span>No disponible</span></div>';
    }
    cal.querySelector('[data-cuerpo-calendario]') ? (cal.querySelector('[data-cuerpo-calendario]').innerHTML = html) : (cal.innerHTML = html);
    if (window.refrescarIconos) refrescarIconos();
  }

  function elegirFecha(cal, fecha) {
    cal.setAttribute('data-seleccionado', fecha);
    cal.setAttribute('data-mes', fecha.slice(0, 7));
    dibujarCalendario(cal);
    var idCampo = cal.getAttribute('data-campo-fecha');
    var entrada = idCampo && document.getElementById(idCampo);
    if (entrada) { entrada.value = fecha; entrada.dispatchEvent(new Event('change', { bubbles: true })); }
    cal.dispatchEvent(new CustomEvent('fecha-elegida', { bubbles: true, detail: { fecha: fecha } }));
  }

  /* ---------- Cupos ---------- */
  function obtenerCupos(medicoId, especialidadId, fecha) {
    var url = '/api/disponibilidad?medico_id=' + encodeURIComponent(medicoId) +
      '&especialidad_id=' + encodeURIComponent(especialidadId) + '&fecha=' + encodeURIComponent(fecha);
    return fetch(url, { headers: { Accept: 'application/json' } }).then(function (r) {
      if (!r.ok) throw new Error('Error ' + r.status);
      return r.json();
    });
  }

  function mensajeCupos(contenedor, icono, titulo, texto, extra) {
    contenedor.innerHTML = '<div class="estado-vacio py-8"><span class="icono-vacio"><i data-lucide="' + icono + '"></i></span>' +
      '<p class="font-semibold">' + titulo + '</p><p class="texto text-sm max-w-sm">' + texto + '</p>' + (extra || '') + '</div>';
    if (window.refrescarIconos) refrescarIconos();
  }

  function cargarCupos(raiz) {
    var contenedor = raiz.querySelector('[data-cupos]');
    if (!contenedor) return;
    var medicoId = valor(raiz, 'medico_id');
    var especialidadId = valor(raiz, 'especialidad_id');
    var cal = raiz.querySelector('[data-calendario]');
    var fecha = cal ? cal.getAttribute('data-seleccionado') : valor(raiz, 'fecha');
    fijarOculto(raiz, 'inicio', '');
    fijarOculto(raiz, 'fin', '');
    resumen(raiz, 'horario', '—');

    if (!medicoId || !especialidadId || !fecha) {
      mensajeCupos(contenedor, 'calendar-search', 'Elige especialidad, médico y fecha', 'Selecciona la especialidad, el médico y un día en el calendario para ver los horarios disponibles.');
      return;
    }
    var esqueleto = '';
    for (var i = 0; i < 8; i++) esqueleto += '<span class="esqueleto h-11 rounded-full"></span>';
    contenedor.innerHTML = '<div class="cupos-grilla" aria-busy="true">' + esqueleto + '</div>';

    obtenerCupos(medicoId, especialidadId, fecha).then(function (datos) {
      var cupos = (datos && datos.cupos) || [];
      if (!cupos.length || !cupos.some(function (c) { return c.libre; })) {
        var sugerencias = (datos && datos.sugerencias) || [];
        var botones = sugerencias.length
          ? '<div class="flex flex-wrap justify-center gap-2 mt-2">' + sugerencias.map(function (f) {
              return '<button type="button" class="btn-secundario btn-sm" data-sugerir-fecha="' + f + '"><i data-lucide="calendar"></i>' + formatoFechaLarga(f) + '</button>';
            }).join('') + '</div>'
          : '';
        var causa = (datos && datos.motivo) || 'El médico no tiene horarios libres el ' + formatoFechaLarga(fecha) + '.';
        mensajeCupos(contenedor, 'calendar-x', 'No hay cupos este día', causa + (sugerencias.length ? ' Prueba con alguna de las próximas fechas:' : ''), botones);
        return;
      }
      var libres = cupos.filter(function (c) { return c.libre; }).length;
      var html = '<p class="text-sm text-muted mb-3"><span class="font-semibold text-fg">' + libres + ' horarios libres</span> · ' + formatoFechaLarga(fecha) + ' · turnos de 15 min</p>';
      html += '<div class="cupos-grilla" role="radiogroup" aria-label="Horarios disponibles">';
      cupos.forEach(function (c) {
        var texto = horaDe(c.inicio) + '–' + horaDe(c.fin);
        html += '<button type="button" class="chip-horario" role="radio" aria-checked="false" data-inicio="' + c.inicio + '" data-fin="' + c.fin + '"' +
          (c.libre ? '' : ' disabled aria-label="' + texto + ' ocupado"') + '>' + texto + '</button>';
      });
      html += '</div>';
      contenedor.innerHTML = html;
    }).catch(function () {
      mensajeCupos(contenedor, 'wifi-off', 'No pudimos cargar los horarios', 'Revisa tu conexión e inténtalo nuevamente.',
        '<button type="button" class="btn-secundario btn-sm mt-2" data-reintentar-cupos><i data-lucide="refresh-cw"></i>Reintentar</button>');
    });
  }

  /* ---------- Booking bar: filtrar médicos por especialidad ---------- */
  function filtrarMedicos(raiz) {
    var esp = valor(raiz, 'especialidad_id');
    var selectMedico = campo(raiz, 'medico_id');
    if (selectMedico && selectMedico.tagName === 'SELECT') {
      var visibleActual = false;
      Array.prototype.forEach.call(selectMedico.options, function (op) {
        if (!op.value) return;
        var ok = !esp || lista(op.getAttribute('data-especialidades')).indexOf(esp) !== -1;
        op.hidden = !ok; op.disabled = !ok;
        if (ok && op.selected) visibleActual = true;
      });
      if (!visibleActual) selectMedico.value = '';
    }
    raiz.querySelectorAll('[data-tarjeta-medico]').forEach(function (t) {
      var ok = !esp || lista(t.getAttribute('data-especialidades')).indexOf(esp) !== -1;
      t.hidden = !ok;
    });
  }

  function actualizarTarjetasMedico(raiz) {
    var id = valor(raiz, 'medico_id');
    raiz.querySelectorAll('[data-elegir-medico]').forEach(function (b) {
      var activo = b.getAttribute('data-elegir-medico') === id;
      b.setAttribute('aria-pressed', String(activo));
      var tarjeta = b.closest('[data-tarjeta-medico]');
      if (tarjeta) {
        tarjeta.style.borderColor = activo ? 'var(--accent)' : '';
        tarjeta.style.boxShadow = activo ? 'var(--glass-shadow), var(--accent-glow)' : '';
      }
      b.textContent = activo ? 'Seleccionado' : 'Elegir';
      b.className = activo ? 'btn-primario btn-sm' : 'btn-secundario btn-sm';
    });
  }

  function actualizarResumenSelects(raiz) {
    ['especialidad_id', 'medico_id', 'modalidad'].forEach(function (n) {
      var c = campo(raiz, n);
      if (!c) return;
      var texto = c.tagName === 'SELECT' ? (c.value ? c.options[c.selectedIndex].text : '—') : (raiz.querySelector('[name="' + n + '"]:checked') || {}).value || c.getAttribute('data-texto') || '—';
      resumen(raiz, n.replace('_id', ''), texto === 'presencial' ? 'Presencial' : texto === 'virtual' ? 'Virtual' : texto);
      if (c.hasAttribute('data-sincroniza')) fijarOculto(raiz, n, c.value);
    });
    var opcionEsp = opcionElegida(campo(raiz, 'especialidad_id'));
    var importe = opcionEsp && opcionEsp.getAttribute('data-importe');
    resumen(raiz, 'importe', importe ? formatoBs(importe) : '—');
  }

  function opcionElegida(select) {
    return select && select.tagName === 'SELECT' && select.value ? select.options[select.selectedIndex] : null;
  }

  function iniciarReserva(raiz) {
    var cal = raiz.querySelector('[data-calendario]');
    filtrarMedicos(raiz);
    actualizarResumenSelects(raiz);
    actualizarTarjetasMedico(raiz);

    raiz.addEventListener('change', function (e) {
      if (e.target.hasAttribute('data-fecha-booking') && cal && e.target.value) { elegirFecha(cal, e.target.value); return; }
      var n = e.target.getAttribute('data-sincroniza') || e.target.name;
      if (n === 'especialidad_id') { filtrarMedicos(raiz); }
      if (n === 'especialidad_id' || n === 'medico_id' || n === 'modalidad') {
        actualizarResumenSelects(raiz);
        actualizarTarjetasMedico(raiz);
        if (n !== 'modalidad') { if (cal) dibujarCalendario(cal); cargarCupos(raiz); }
      }
    });

    raiz.addEventListener('fecha-elegida', function (e) {
      resumen(raiz, 'fecha', formatoFechaLarga(e.detail.fecha));
      fijarOculto(raiz, 'fecha', e.detail.fecha);
      raiz.querySelectorAll('[data-fecha-booking]').forEach(function (i) { i.value = e.detail.fecha; });
      cargarCupos(raiz);
    });

    raiz.addEventListener('click', function (e) {
      var medico = e.target.closest('[data-elegir-medico]');
      if (medico) {
        var c = campo(raiz, 'medico_id');
        c.value = medico.getAttribute('data-elegir-medico');
        c.dispatchEvent(new Event('change', { bubbles: true }));
        return;
      }
      var chip = e.target.closest('.chip-horario');
      if (chip && !chip.disabled) {
        chip.parentElement.querySelectorAll('.chip-horario').forEach(function (c) { c.setAttribute('aria-checked', 'false'); });
        chip.setAttribute('aria-checked', 'true');
        fijarOculto(raiz, 'inicio', chip.getAttribute('data-inicio'));
        fijarOculto(raiz, 'fin', chip.getAttribute('data-fin'));
        resumen(raiz, 'horario', chip.textContent);
        var err = raiz.querySelector('[data-error="inicio"]');
        if (err) err.textContent = '';
        raiz.dispatchEvent(new CustomEvent('cupo-elegido', { bubbles: true, detail: { inicio: chip.getAttribute('data-inicio'), fin: chip.getAttribute('data-fin') } }));
        return;
      }
      var sugerida = e.target.closest('[data-sugerir-fecha]');
      if (sugerida && cal) { elegirFecha(cal, sugerida.getAttribute('data-sugerir-fecha')); return; }
      if (e.target.closest('[data-reintentar-cupos]')) cargarCupos(raiz);

      var paciente = e.target.closest('[data-elegir-paciente]');
      if (paciente) {
        fijarOculto(raiz, 'paciente_id', paciente.getAttribute('data-elegir-paciente'));
        resumen(raiz, 'paciente', paciente.getAttribute('data-nombre'));
        raiz.querySelectorAll('[data-elegir-paciente]').forEach(function (b) {
          var activo = b === paciente;
          b.setAttribute('aria-pressed', String(activo));
          b.closest('[data-fila-paciente]').style.background = activo ? 'var(--accent-soft)' : '';
          b.textContent = activo ? 'Seleccionado' : 'Elegir';
        });
        var errP = raiz.querySelector('[data-error="paciente_id"]');
        if (errP) errP.textContent = '';
      }
    });

    var buscador = raiz.querySelector('[data-buscar-paciente]');
    if (buscador) buscador.addEventListener('input', function () {
      var q = buscador.value.trim().toLowerCase();
      raiz.querySelectorAll('[data-fila-paciente]').forEach(function (f) {
        f.hidden = q && f.textContent.toLowerCase().indexOf(q) === -1;
      });
    });

    if (cal && cal.getAttribute('data-seleccionado')) {
      resumen(raiz, 'fecha', formatoFechaLarga(cal.getAttribute('data-seleccionado')));
      fijarOculto(raiz, 'fecha', cal.getAttribute('data-seleccionado'));
    }
    cargarCupos(raiz);
  }

  document.addEventListener('click', function (e) {
    var mover = e.target.closest('[data-mover-mes]');
    if (mover) {
      var cal = mover.closest('[data-calendario]');
      var mes = cal.getAttribute('data-mes');
      var d = new Date(Number(mes.slice(0, 4)), Number(mes.slice(5, 7)) - 1 + Number(mover.getAttribute('data-mover-mes')), 1, 12);
      cal.setAttribute('data-mes', iso(d).slice(0, 7));
      dibujarCalendario(cal);
      return;
    }
    var dia = e.target.closest('.calendario-dia');
    if (dia && !dia.disabled) elegirFecha(dia.closest('[data-calendario]'), dia.getAttribute('data-fecha'));
  });

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-calendario]').forEach(dibujarCalendario);
    document.querySelectorAll('[data-reserva]').forEach(iniciarReserva);
    document.querySelectorAll('[data-booking-bar]').forEach(function (bar) {
      if (bar.closest('[data-reserva]')) return;
      filtrarMedicos(bar);
      bar.addEventListener('change', function (e) { if (e.target.name === 'especialidad_id') filtrarMedicos(bar); });
    });
  });

  window.dibujarCalendario = dibujarCalendario;
  window.cargarCupos = cargarCupos;
  window.formatoBs = formatoBs;
  window.formatoFecha = formatoFecha;
})();
