/* Vida Salud — Validaciones en cliente
   Se activan en todo <form data-validar>. Los mensajes se muestran en [data-error="name"].
   Con DEMO = true el envío se simula (toast + redirección opcional data-demo-redirigir). */
(function () {
  function hoyISO() {
    var d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
  }

  function aMinutos(hora) {
    if (!hora) return null;
    var p = hora.split(':');
    return Number(p[0]) * 60 + Number(p[1]);
  }

  function contenedorError(raiz, campo) {
    var form = campo.form || raiz;
    var selector = '[data-error="' + campo.name + '"]';
    return (campo.closest('[data-fila]') || form).querySelector(selector) || form.querySelector(selector);
  }

  function ponerError(raiz, campo, mensaje) {
    campo.classList.add('campo-error');
    campo.setAttribute('aria-invalid', 'true');
    var p = contenedorError(raiz, campo);
    if (p) {
      p.textContent = mensaje;
      if (p.id) campo.setAttribute('aria-describedby', p.id);
    }
  }

  function limpiarError(raiz, campo) {
    campo.classList.remove('campo-error');
    campo.removeAttribute('aria-invalid');
    var p = contenedorError(raiz, campo);
    if (p) p.textContent = '';
  }

  function mensajeNativo(campo) {
    var v = campo.validity;
    if (v.valueMissing) {
      if (campo.type === 'checkbox') return 'Debes marcar esta casilla para continuar.';
      if (campo.tagName === 'SELECT') return 'Selecciona una opción.';
      return 'Este campo es obligatorio.';
    }
    if (v.typeMismatch && campo.type === 'email') return 'Ingresa un correo electrónico válido.';
    if (v.tooShort) return 'Debe tener al menos ' + campo.minLength + ' caracteres.';
    if (v.tooLong) return 'No debe superar ' + campo.maxLength + ' caracteres.';
    if (v.patternMismatch) return campo.getAttribute('data-mensaje-patron') || 'El formato no es válido.';
    if (v.rangeOverflow) return campo.type === 'date' ? 'La fecha no puede ser posterior a la permitida.' : 'El valor es demasiado alto.';
    if (v.rangeUnderflow) return campo.type === 'date' ? 'La fecha no puede ser anterior a la permitida.' : 'El valor es demasiado bajo.';
    if (v.stepMismatch) return 'El valor no es válido.';
    return campo.validationMessage;
  }

  function reglasPropias(raiz, campo) {
    var valor = campo.value;
    var form = campo.form || raiz;
    if (campo.name === 'fecha_nacimiento' && valor && valor > hoyISO()) return 'La fecha de nacimiento no puede ser futura.';
    if ((campo.name === 'contrasena' || campo.name === 'contrasena_nueva') && valor && valor.length < 8) return 'La contraseña debe tener al menos 8 caracteres.';
    if (campo.name === 'contrasena_confirmacion' && valor) {
      var original = form.querySelector('[name="contrasena_nueva"]') || form.querySelector('[name="contrasena"]');
      if (original && original.value !== valor) return 'Las contraseñas no coinciden.';
    }
    if (campo.name === 'hora_fin' && valor) {
      var inicio = form.querySelector('[name="hora_inicio"]');
      var duracion = form.querySelector('[name="duracion_cita_min"]');
      var ini = aMinutos(inicio && inicio.value), fin = aMinutos(valor);
      if (ini !== null && fin <= ini) return 'La hora de fin debe ser posterior a la hora de inicio.';
      if (ini !== null && duracion && Number(duracion.value) > 0 && (fin - ini) % Number(duracion.value) !== 0)
        return 'El rango debe ser divisible entre la duración del turno (' + duracion.value + ' min).';
    }
    if (campo.name === 'vigente_hasta' && valor) {
      var desde = form.querySelector('[name="vigente_desde"]');
      if (desde && desde.value && valor < desde.value) return 'La vigencia final no puede ser anterior a la inicial.';
    }
    if (campo.name === 'fecha_fin' && valor && campo.closest('[data-fila]')) {
      var fi = campo.closest('[data-fila]').querySelector('[name$="[fecha_inicio]"]');
      if (fi && fi.value && valor < fi.value) return 'La fecha de fin no puede ser anterior a la de inicio.';
    }
    if (/\[fecha_fin\]$/.test(campo.name) && valor) {
      var fi2 = campo.closest('[data-fila]').querySelector('[name$="[fecha_inicio]"]');
      if (fi2 && fi2.value && valor < fi2.value) return 'La fecha de fin no puede ser anterior a la de inicio.';
    }
    return '';
  }

  function validarCampo(raiz, campo) {
    if (campo.disabled || campo.type === 'hidden' && !campo.hasAttribute('data-validar-oculto')) return true;
    if (campo.closest('[hidden]') && !campo.hasAttribute('data-validar-oculto')) return true;
    limpiarError(raiz, campo);
    var mensaje = '';
    if (!campo.checkValidity()) mensaje = mensajeNativo(campo);
    if (!mensaje) mensaje = reglasPropias(raiz, campo);
    if (campo.type === 'hidden' && campo.required && !campo.value) mensaje = campo.getAttribute('data-mensaje-vacio') || 'Completa este dato.';
    if (mensaje) { ponerError(raiz, campo, mensaje); return false; }
    return true;
  }

  function camposDe(raiz) {
    return Array.prototype.filter.call(raiz.querySelectorAll('input, select, textarea'), function (c) {
      return c.name && c.name !== '_csrf' && c.name !== '_metodo';
    });
  }

  function validarSeccion(raiz) {
    if (!raiz) return true;
    var valido = true, primero = null;
    camposDe(raiz).forEach(function (campo) {
      var ok = validarCampo(raiz, campo);
      if (!ok && !primero) primero = campo;
      valido = valido && ok;
    });
    if (primero) {
      if (primero.type === 'hidden') {
        var aviso = raiz.querySelector('[data-error="' + primero.name + '"]');
        if (aviso) aviso.scrollIntoView({ behavior: 'smooth', block: 'center' });
      } else primero.focus();
    }
    return valido;
  }

  function validarFormulario(form) {
    var pasos = form.querySelectorAll('[data-paso]');
    if (pasos.length) {
      var todo = true;
      pasos.forEach(function (p) {
        var ocultos = p.hidden; p.hidden = false;
        var ok = validarSeccion(p);
        p.hidden = ocultos;
        todo = todo && ok;
      });
      return todo;
    }
    return validarSeccion(form);
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute('data-validar')) return;
    if (!validarFormulario(form)) {
      e.preventDefault();
      if (window.mostrarToast) window.mostrarToast('error', 'Revisa los campos marcados en rojo.');
      return;
    }
    if (window.DEMO) {
      e.preventDefault();
      var modal = form.closest('.modal, .drawer');
      if (modal && window.cerrarModal) window.cerrarModal(modal);
      if (window.mostrarToast) window.mostrarToast(form.getAttribute('data-demo-tipo') || 'exito', form.getAttribute('data-demo-mensaje') || 'Cambios guardados (modo demostración).');
      var destino = form.getAttribute('data-demo-redirigir');
      if (destino) setTimeout(function () { window.location.href = destino; }, 900);
    }
  }, true);

  document.addEventListener('input', function (e) {
    var campo = e.target;
    if (campo.classList && campo.classList.contains('campo-error')) validarCampo(campo.form || document, campo);
  });
  document.addEventListener('change', function (e) {
    var campo = e.target;
    if (campo.classList && campo.classList.contains('campo-error')) validarCampo(campo.form || document, campo);
  });

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[type="date"][name="fecha_nacimiento"]').forEach(function (c) {
      if (!c.max) c.max = hoyISO();
    });
    document.querySelectorAll('form[data-validar]').forEach(function (f) { f.noValidate = true; });
  });

  window.validarFormulario = validarFormulario;
  window.validarSeccion = validarSeccion;
})();
