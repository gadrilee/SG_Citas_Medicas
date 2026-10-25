/* Vida Salud — Interacciones generales de la interfaz
   Menú móvil, modales, drawers, toasts, pestañas, menús desplegables,
   filas dinámicas, asistentes por pasos y utilidades varias. */
(function () {
  var ultimoFoco = null;

  /* ---------- Iconos ---------- */
  function refrescarIconos() {
    if (window.lucide) window.lucide.createIcons({ attrs: { 'stroke-width': 1.75 } });
  }

  /* ---------- Modales y drawers ---------- */
  function abrirModal(id) {
    var modal = document.getElementById(id);
    if (!modal) return;
    ultimoFoco = document.activeElement;
    modal.hidden = false;
    requestAnimationFrame(function () { modal.classList.add('abierto'); });
    document.body.style.overflow = 'hidden';
    var enfocable = modal.querySelector('[autofocus], input:not([type=hidden]), select, textarea, button');
    if (enfocable) setTimeout(function () { enfocable.focus(); }, 60);
  }

  function cerrarModal(id) {
    var modal = typeof id === 'string' ? document.getElementById(id) : id;
    if (!modal) return;
    modal.classList.remove('abierto');
    setTimeout(function () { modal.hidden = true; }, 200);
    if (!document.querySelector('.modal.abierto, .drawer.abierto')) document.body.style.overflow = '';
    if (ultimoFoco && ultimoFoco.focus) ultimoFoco.focus();
  }

  /* ---------- Toasts ---------- */
  var ICONOS_TOAST = { exito: 'circle-check', error: 'circle-x', aviso: 'triangle-alert', info: 'info' };

  function mostrarToast(tipo, texto) {
    var contenedor = document.getElementById('toasts');
    if (!contenedor) {
      contenedor = document.createElement('div');
      contenedor.id = 'toasts';
      contenedor.setAttribute('aria-live', 'polite');
      contenedor.setAttribute('role', 'status');
      document.body.appendChild(contenedor);
    }
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + (tipo || 'info');
    toast.innerHTML = '<i data-lucide="' + (ICONOS_TOAST[tipo] || 'info') + '"></i><span></span>';
    toast.querySelector('span').textContent = texto;
    contenedor.appendChild(toast);
    refrescarIconos();
    setTimeout(function () {
      toast.style.transition = 'opacity .3s';
      toast.style.opacity = '0';
      setTimeout(function () { toast.remove(); }, 300);
    }, 4200);
  }

  /* ---------- Menú móvil (sidebar / navbar) ---------- */
  function alternarMenu(id) {
    var objetivo = document.getElementById(id || 'sidebar');
    if (!objetivo) return;
    var botones = document.querySelectorAll('[data-alternar-menu="' + objetivo.id + '"]');
    if (objetivo.classList.contains('sidebar')) {
      var abierto = objetivo.classList.toggle('abierto');
      var fondo = document.getElementById('fondo-sidebar');
      if (fondo) fondo.hidden = !abierto;
      botones.forEach(function (b) { b.setAttribute('aria-expanded', String(abierto)); });
    } else {
      objetivo.hidden = !objetivo.hidden;
      botones.forEach(function (b) { b.setAttribute('aria-expanded', String(!objetivo.hidden)); });
    }
  }

  /* ---------- Pestañas ---------- */
  function cambiarPestana(grupo, id) {
    document.querySelectorAll('[data-pestanas="' + grupo + '"] [data-pestana]').forEach(function (boton) {
      var activa = boton.getAttribute('data-pestana') === id;
      boton.setAttribute('aria-selected', String(activa));
      boton.tabIndex = activa ? 0 : -1;
    });
    document.querySelectorAll('[data-panel-pestana^="' + grupo + ':"]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-panel-pestana') !== grupo + ':' + id;
    });
  }

  /* ---------- Filas dinámicas (<template>) ---------- */
  function renumerarFilas(contenedor) {
    Array.prototype.forEach.call(contenedor.querySelectorAll('[data-fila]'), function (fila, indice) {
      fila.querySelectorAll('[name]').forEach(function (campo) {
        campo.name = campo.name.replace(/^(\w+)\[\d+\]/, '$1[' + indice + ']');
      });
      fila.querySelectorAll('[data-error]').forEach(function (error) {
        error.setAttribute('data-error', error.getAttribute('data-error').replace(/^(\w+)\[\d+\]/, '$1[' + indice + ']'));
      });
      fila.querySelectorAll('[id]').forEach(function (el) {
        el.id = el.id.replace(/-\d+$/, '-' + indice);
      });
      fila.querySelectorAll('label[for]').forEach(function (el) {
        el.htmlFor = el.htmlFor.replace(/-\d+$/, '-' + indice);
      });
      var numero = fila.querySelector('[data-numero-fila]');
      if (numero) numero.textContent = indice + 1;
    });
    var vacio = contenedor.parentElement && contenedor.parentElement.querySelector('[data-vacio-filas]');
    if (vacio) vacio.hidden = contenedor.querySelectorAll('[data-fila]').length > 0;
  }

  function agregarFila(idPlantilla, idDestino) {
    var plantilla = document.getElementById(idPlantilla);
    var destino = document.getElementById(idDestino);
    if (!plantilla || !destino) return;
    var indice = destino.querySelectorAll('[data-fila]').length;
    var html = plantilla.innerHTML.replace(/__i__/g, indice).replace(/__n__/g, indice + 1);
    destino.insertAdjacentHTML('beforeend', html);
    renumerarFilas(destino);
    refrescarIconos();
    var nueva = destino.querySelectorAll('[data-fila]')[indice];
    var primerCampo = nueva && nueva.querySelector('input, select, textarea');
    if (primerCampo) primerCampo.focus();
  }

  /* ---------- Asistente por pasos ---------- */
  function irAPaso(asistente, numero) {
    var pasos = asistente.querySelectorAll('[data-paso]');
    var total = pasos.length;
    numero = Math.max(1, Math.min(total, numero));
    asistente.setAttribute('data-paso-actual', numero);
    pasos.forEach(function (paso) { paso.hidden = Number(paso.getAttribute('data-paso')) !== numero; });
    asistente.querySelectorAll('[data-indicador-paso]').forEach(function (ind) {
      var n = Number(ind.getAttribute('data-indicador-paso'));
      ind.classList.toggle('actual', n === numero);
      ind.classList.toggle('hecho', n < numero);
      if (n === numero) ind.setAttribute('aria-current', 'step'); else ind.removeAttribute('aria-current');
    });
    var contador = asistente.querySelector('[data-contador-pasos]');
    if (contador) contador.textContent = 'Paso ' + numero + ' de ' + total;
    asistente.dispatchEvent(new CustomEvent('paso-cambiado', { detail: { paso: numero } }));
    var arriba = asistente.getBoundingClientRect().top;
    if (arriba < 0) asistente.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function iniciarAsistentes() {
    document.querySelectorAll('[data-asistente]').forEach(function (asistente) {
      irAPaso(asistente, Number(asistente.getAttribute('data-paso-actual')) || 1);
      asistente.addEventListener('click', function (e) {
        var siguiente = e.target.closest('[data-siguiente]');
        var anterior = e.target.closest('[data-anterior]');
        var actual = Number(asistente.getAttribute('data-paso-actual')) || 1;
        if (siguiente) {
          e.preventDefault();
          var panel = asistente.querySelector('[data-paso="' + actual + '"]');
          if (window.validarSeccion && !window.validarSeccion(panel)) return;
          irAPaso(asistente, actual + 1);
        }
        if (anterior) { e.preventDefault(); irAPaso(asistente, actual - 1); }
        var saltar = e.target.closest('[data-ir-paso]');
        if (saltar) { e.preventDefault(); irAPaso(asistente, Number(saltar.getAttribute('data-ir-paso'))); }
      });
    });
  }

  /* ---------- Cuenta regresiva ---------- */
  function iniciarCuentasRegresivas() {
    document.querySelectorAll('[data-cuenta-regresiva]').forEach(function (el) {
      var objetivo = new Date(el.getAttribute('data-cuenta-regresiva')).getTime();
      var referencia = el.getAttribute('data-ahora') ? new Date(el.getAttribute('data-ahora')).getTime() : Date.now();
      var desfase = Date.now() - referencia;
      function actualizar() {
        var resto = Math.max(0, objetivo - (Date.now() - desfase));
        var d = Math.floor(resto / 86400000);
        var h = Math.floor((resto % 86400000) / 3600000);
        var m = Math.floor((resto % 3600000) / 60000);
        var s = Math.floor((resto % 60000) / 1000);
        var valores = { dias: d, horas: h, minutos: m, segundos: s };
        Object.keys(valores).forEach(function (k) {
          var nodo = el.querySelector('[data-unidad="' + k + '"]');
          if (nodo) nodo.textContent = String(valores[k]).padStart(2, '0');
        });
      }
      actualizar();
      setInterval(actualizar, 1000);
    });
  }

  /* ---------- Tablas → tarjetas en móvil ---------- */
  function etiquetarTablas() {
    document.querySelectorAll('table.tabla').forEach(function (tabla) {
      var titulos = Array.prototype.map.call(tabla.querySelectorAll('thead th'), function (th) {
        return th.getAttribute('data-etiqueta') !== null ? th.getAttribute('data-etiqueta') : th.textContent.trim();
      });
      tabla.querySelectorAll('tbody tr, tfoot tr').forEach(function (fila) {
        Array.prototype.forEach.call(fila.children, function (celda, i) {
          if (!celda.hasAttribute('data-etiqueta')) celda.setAttribute('data-etiqueta', titulos[i] || '');
        });
      });
    });
  }

  /* ---------- Zona de arrastre de archivos ---------- */
  function iniciarZonasArrastre() {
    document.querySelectorAll('.zona-arrastre').forEach(function (zona) {
      var entrada = zona.querySelector('input[type=file]');
      var nombre = zona.querySelector('[data-nombre-archivo]');
      ['dragenter', 'dragover'].forEach(function (ev) { zona.addEventListener(ev, function () { zona.classList.add('arrastrando'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { zona.addEventListener(ev, function () { zona.classList.remove('arrastrando'); }); });
      if (entrada) entrada.addEventListener('change', function () {
        if (nombre) nombre.textContent = entrada.files.length ? entrada.files[0].name : 'Ningún archivo seleccionado';
      });
    });
  }

  /* ---------- Delegación de eventos ---------- */
  document.addEventListener('click', function (e) {
    var abrir = e.target.closest('[data-abrir-modal]');
    if (abrir) { e.preventDefault(); abrirModal(abrir.getAttribute('data-abrir-modal')); return; }

    var cerrar = e.target.closest('[data-cerrar-modal]');
    if (cerrar) { e.preventDefault(); cerrarModal(cerrar.closest('.modal, .drawer')); return; }

    if (e.target.classList && (e.target.classList.contains('modal') || e.target.classList.contains('drawer'))) {
      cerrarModal(e.target); return;
    }

    var menu = e.target.closest('[data-alternar-menu]');
    if (menu) { e.preventDefault(); alternarMenu(menu.getAttribute('data-alternar-menu')); return; }

    if (e.target.id === 'fondo-sidebar') { alternarMenu('sidebar'); return; }

    var pestana = e.target.closest('[data-pestana]');
    if (pestana) {
      var grupo = pestana.closest('[data-pestanas]').getAttribute('data-pestanas');
      cambiarPestana(grupo, pestana.getAttribute('data-pestana'));
      return;
    }

    var desplegable = e.target.closest('[data-desplegable]');
    document.querySelectorAll('.menu-desplegable').forEach(function (m) {
      if (!desplegable || m.id !== desplegable.getAttribute('data-desplegable')) m.hidden = true;
    });
    if (desplegable) {
      var objetivo = document.getElementById(desplegable.getAttribute('data-desplegable'));
      objetivo.hidden = !objetivo.hidden;
      desplegable.setAttribute('aria-expanded', String(!objetivo.hidden));
      return;
    }

    var agregar = e.target.closest('[data-agregar-fila]');
    if (agregar) { e.preventDefault(); agregarFila(agregar.getAttribute('data-agregar-fila'), agregar.getAttribute('data-destino')); return; }

    var quitar = e.target.closest('[data-quitar-fila]');
    if (quitar) {
      e.preventDefault();
      var fila = quitar.closest('[data-fila]');
      var lista = fila.parentElement;
      fila.remove();
      renumerarFilas(lista);
      return;
    }

    var mostrar = e.target.closest('[data-mostrar-contrasena]');
    if (mostrar) {
      var campo = document.getElementById(mostrar.getAttribute('data-mostrar-contrasena'));
      var visible = campo.type === 'text';
      campo.type = visible ? 'password' : 'text';
      mostrar.setAttribute('aria-pressed', String(!visible));
      mostrar.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
      return;
    }

    var toast = e.target.closest('[data-toast]');
    if (toast) {
      var partes = toast.getAttribute('data-toast').split('|');
      mostrarToast(partes[0], partes[1]);
    }

    var imprimir = e.target.closest('[data-accion="imprimir"]');
    if (imprimir) { e.preventDefault(); window.print(); }

    var vista = e.target.closest('[data-vista]');
    if (vista) {
      var grupoVista = vista.getAttribute('data-grupo-vista');
      document.querySelectorAll('[data-grupo-vista="' + grupoVista + '"]').forEach(function (b) { b.setAttribute('aria-pressed', String(b === vista)); });
      document.querySelectorAll('[data-contenido-vista^="' + grupoVista + ':"]').forEach(function (c) {
        c.hidden = c.getAttribute('data-contenido-vista') !== grupoVista + ':' + vista.getAttribute('data-vista');
      });
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      var abiertos = document.querySelectorAll('.modal.abierto, .drawer.abierto');
      if (abiertos.length) cerrarModal(abiertos[abiertos.length - 1]);
      document.querySelectorAll('.menu-desplegable').forEach(function (m) { m.hidden = true; });
      var sidebar = document.getElementById('sidebar');
      if (sidebar && sidebar.classList.contains('abierto')) alternarMenu('sidebar');
    }
    var pestana = e.target.closest && e.target.closest('[data-pestana]');
    if (pestana && (e.key === 'ArrowRight' || e.key === 'ArrowLeft')) {
      var hermanos = Array.prototype.slice.call(pestana.parentElement.querySelectorAll('[data-pestana]'));
      var i = hermanos.indexOf(pestana) + (e.key === 'ArrowRight' ? 1 : -1);
      var destino = hermanos[(i + hermanos.length) % hermanos.length];
      destino.focus(); destino.click();
    }
  });

  document.addEventListener('DOMContentLoaded', function () {
    refrescarIconos();
    etiquetarTablas();
    iniciarAsistentes();
    iniciarCuentasRegresivas();
    iniciarZonasArrastre();
    document.querySelectorAll('[data-pestanas]').forEach(function (grupo) {
      var activa = grupo.querySelector('[data-pestana][aria-selected="true"]') || grupo.querySelector('[data-pestana]');
      if (activa) cambiarPestana(grupo.getAttribute('data-pestanas'), activa.getAttribute('data-pestana'));
    });
    document.querySelectorAll('[data-lista-filas]').forEach(renumerarFilas);
    var vistaInicial = new URLSearchParams(location.search).get('vista');
    var botonVista = vistaInicial && document.querySelector('[data-vista="' + vistaInicial + '"]');
    if (botonVista) botonVista.click();
    var flash = document.getElementById('flash');
    if (flash && flash.getAttribute('data-toast-inicial')) {
      var p = flash.getAttribute('data-toast-inicial').split('|');
      mostrarToast(p[0], p[1]);
    }
  });

  window.refrescarIconos = refrescarIconos;
  window.abrirModal = abrirModal;
  window.cerrarModal = cerrarModal;
  window.mostrarToast = mostrarToast;
  window.alternarMenu = alternarMenu;
  window.cambiarPestana = cambiarPestana;
  window.agregarFila = agregarFila;
  window.irAPaso = irAPaso;
})();
