/* Vida Salud — CU20 · Dibuja el formulario de Stripe (Checkout embebido) dentro de la página de pago.
   El servidor ya creó la sesión de pago; aquí solo se monta con su client_secret. Al terminar,
   Stripe lleva al usuario a /pagos/retorno, donde el sistema verifica el pago. */
(function () {
  var zona = document.getElementById('checkout-stripe');
  if (!zona) return;

  function mostrarError() {
    zona.innerHTML = '<p class="text-center text-sm text-danger" style="padding:4rem 1rem">No se pudo cargar el formulario de Stripe. Revisa tu conexión y recarga la página.</p>';
  }
  if (!window.Stripe) { mostrarError(); return; }

  var stripe = window.Stripe(zona.getAttribute('data-clave-publica'));
  // Stripe.js recientes usan createEmbeddedCheckoutPage; las anteriores, initEmbeddedCheckout.
  var crear = stripe.createEmbeddedCheckoutPage || stripe.initEmbeddedCheckout;
  crear.call(stripe, { clientSecret: zona.getAttribute('data-client-secret') })
    .then(function (checkout) {
      zona.innerHTML = '';
      checkout.mount('#checkout-stripe');
    })
    .catch(mostrarError);
})();
