<?php

declare(strict_types=1);

namespace VidaSalud\Paquetes\P4_Atencion\Controladores;

use VidaSalud\Nucleo\BaseDatos;
use VidaSalud\Nucleo\Bitacora;
use VidaSalud\Nucleo\Configuracion;
use VidaSalud\Nucleo\Controlador;
use VidaSalud\Nucleo\Excepciones\ExcepcionAcceso;
use VidaSalud\Nucleo\Excepciones\ExcepcionNegocio;
use VidaSalud\Nucleo\Excepciones\ExcepcionNoEncontrado;
use VidaSalud\Nucleo\Respuesta;
use VidaSalud\Paquetes\P2_Agenda\Modelos\Ecita_medica;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Epago;
use VidaSalud\Paquetes\P4_Atencion\Modelos\Epasarela_pago;
use VidaSalud\Paquetes\P4_Atencion\Pasarelas\Pasarelas;
use VidaSalud\Paquetes\P4_Atencion\Vistas\Vpago;

/**
 * CU20 · Gestionar pagos de citas. La pasarela (simulada o Stripe) es un servicio externo que se usa
 * mediante la interfaz PasarelaPago. Regla central: el estado de un pago solo cambia después de
 * verificar la respuesta con la pasarela, y solo un intento pendiente puede cambiar.
 */
final class Cpago extends Controlador
{
    private const ESTADOS = ['pendiente', 'aprobado', 'rechazado', 'cancelado', 'reembolsado'];

    // ---------------------------------------------------------------- caja (recepción)

    public function listar(): Respuesta
    {
        if ($this->esPaciente()) {
            return $this->redirigir('/mis-citas');
        }
        $buscar = trim((string) $this->solicitud->consulta('buscar', ''));
        $estado = (string) $this->solicitud->consulta('estado', '');
        $estado = in_array($estado, self::ESTADOS, true) ? $estado : '';
        $pagos = new Epago();
        return (new Vpago())->mostrarLista(
            $pagos->buscar($buscar, $estado, (int) $this->solicitud->consulta('pagina', 1)),
            $pagos->resumen(),
            $pagos->devolucionesPendientes(),
            ['buscar' => $buscar, 'estado' => $estado]
        );
    }

    // ---------------------------------------------------------------- flujo principal

    /** Paso 1: resumen de la cita, sus intentos anteriores y el botón «Pagar». */
    public function mostrarPagar(string $id): Respuesta
    {
        $cita = $this->citaAccesible($id);
        $pagos = new Epago();
        return (new Vpago())->mostrarPagar(
            $cita,
            $pagos->deCita((int) $cita['id']),
            self::motivoNoPagable($cita, $pagos->vigenteDeCita((int) $cita['id'])),
            $this->pasarelaActiva()
        );
    }

    /** Pasos 2–3: crea el intento pendiente (en una transacción) y lleva al usuario al formulario de la pasarela. */
    public function iniciar(string $id): Respuesta
    {
        $cita = $this->citaAccesible($id);
        $pasarela = $this->pasarelaActiva() ?? throw new ExcepcionNegocio('No hay una pasarela de pago activa configurada.');
        $usuarioId = (int) $this->usuario()['id'];
        $pagos = new Epago();

        $pagoId = BaseDatos::transaccion(function () use ($cita, $pasarela, $usuarioId, $pagos): int {
            // Bloquea la cita: dos clics simultáneos en «Pagar» no crean dos intentos (E2).
            $actual = (new Ecita_medica())->paraActualizar((int) $cita['id']);
            if ($motivo = self::motivoNoPagable($actual, $pagos->vigenteDeCita((int) $actual['id']))) {
                throw new ExcepcionNegocio($motivo);
            }
            $pagoId = $pagos->registrarIntento((int) $actual['id'], (int) $pasarela['id'], $usuarioId, (string) $actual['importe_acordado'], (string) $actual['moneda']);
            Bitacora::registrar('iniciar_pago', 'pago', $pagoId);
            return $pagoId;
        });

        $pago = $pagos->buscarDetalle($pagoId);
        try {
            $cobro = Pasarelas::crear($pasarela['codigo'])->iniciar($pago, $cita, $this->urlRetorno($pagoId));
        } catch (ExcepcionNegocio $error) {
            // La pasarela no recibió el cobro: el intento se cierra para poder reintentar.
            $pagos->registrarEstado($pagoId, 'cancelado');
            throw $error;
        }
        $pagos->asignarReferencia($pagoId, $cobro['referencia']);
        return str_starts_with($cobro['url'], 'http') ? Respuesta::redirigirExterna($cobro['url']) : $this->redirigir($cobro['url']);
    }

    /** Formulario de «Vida Pay» (hace el papel del sitio externo de la pasarela simulada). */
    public function mostrarSimulada(string $id): Respuesta
    {
        $pago = $this->pagoAccesible($id);
        if ($pago['pasarela_codigo'] !== 'simulada' || $pago['estado'] !== 'pendiente') {
            return $this->redirigir('/pagos/' . $pago['id']);
        }
        return (new Vpago())->mostrarSimulada($pago);
    }

    /** Paso 4 (simulado): la pasarela cobra y devuelve al sistema su respuesta firmada. */
    public function cobrarSimulada(string $id): Respuesta
    {
        $pago = $this->pagoAccesible($id);
        if ($pago['pasarela_codigo'] !== 'simulada' || $pago['estado'] !== 'pendiente') {
            return $this->redirigir('/pagos/' . $pago['id']);
        }
        if ($this->solicitud->entrada('accion') === 'cancelar') {
            return $this->redirigir('/pagos/retorno?pago=' . $pago['id'] . '&cancelado=1');
        }
        $datos = $this->validar(['numero_tarjeta' => 'requerido|max:25', 'vencimiento' => 'requerido|max:7', 'cvc' => 'requerido|max:4']);
        $respuesta = Pasarelas::simulada()->cobrar($pago, $datos['numero_tarjeta']);
        return $this->redirigir('/pagos/retorno?pago=' . $pago['id'] . '&' . http_build_query($respuesta));
    }

    /** Pasos 4–7: la pasarela devuelve al usuario; el sistema verifica el resultado y muestra el comprobante. */
    public function retorno(): Respuesta
    {
        $pago = $this->pagoAccesible((string) $this->solicitud->consulta('pago', ''));
        $usuarioId = (int) $this->usuario()['id'];

        if ($this->solicitud->consulta('cancelado')) {
            // El usuario volvió sin pagar: se confirma con la pasarela que no hubo cobro y se cierra el intento.
            $estado = $this->verificarConPasarela($pago, [], $usuarioId);
            if ($estado === 'pendiente') {
                Pasarelas::crear($pago['pasarela_codigo'])->anular($pago);
                $estado = $this->registrarRespuesta((int) $pago['id'], ['estado' => 'cancelado'], $usuarioId);
            }
        } else {
            $estado = $this->verificarConPasarela($pago, $this->solicitud->todo(), $usuarioId);
        }

        [$tipo, $mensaje] = self::mensaje($estado, $pago['cita']);
        $destino = $estado === 'aprobado' ? '/pagos/' . $pago['id'] : '/citas/' . $pago['cita_id'] . '/pagar';
        return $this->redirigir($destino, $tipo, $mensaje);
    }

    public function comprobante(string $id): Respuesta
    {
        $pago = $this->pagoAccesible($id);
        return (new Vpago())->mostrarComprobante($pago, (new Ecita_medica())->buscarDetalle((int) $pago['cita_id']));
    }

    // ---------------------------------------------------------------- alternativos

    /** A2: sin respuesta de la pasarela, se le vuelve a preguntar. */
    public function verificar(string $id): Respuesta
    {
        $pago = $this->pagoAccesible($id);
        if ($pago['estado'] !== 'pendiente') {
            return $this->volver('aviso', 'El pago ya está ' . texto_estado('pago', $pago['estado']) . '.');
        }
        $estado = $this->verificarConPasarela($pago, [], (int) $this->usuario()['id']);
        return $this->volver(...self::mensaje($estado, $pago['cita']));
    }

    /** A2: un intento que la pasarela nunca confirmó se anula para poder reintentar (antes se verifica que no se cobró). */
    public function anular(string $id): Respuesta
    {
        $pago = $this->pagoAccesible($id);
        if ($pago['estado'] !== 'pendiente') {
            return $this->volver('aviso', 'Solo se anula un intento pendiente.');
        }
        $usuarioId = (int) $this->usuario()['id'];
        $estado = $this->verificarConPasarela($pago, [], $usuarioId);
        if ($estado === 'pendiente') {
            Pasarelas::crear($pago['pasarela_codigo'])->anular($pago);
            $estado = $this->registrarRespuesta((int) $pago['id'], ['estado' => 'cancelado'], $usuarioId);
        }
        return $this->volver(...self::mensaje($estado, $pago['cita']));
    }

    /** A3: devolución total del pago aprobado de una cita cancelada, confirmada por la pasarela. */
    public function devolver(string $id): Respuesta
    {
        if ($this->esPaciente()) {
            throw new ExcepcionAcceso('La devolución la solicita la recepción.');
        }
        $pago = $this->pagoAccesible($id);
        if ($pago['estado'] !== 'aprobado' || (int) $pago['estado_cita_id'] !== Ecita_medica::CANCELADA) {
            throw new ExcepcionNegocio('Solo se devuelve el pago aprobado de una cita cancelada.');
        }
        Pasarelas::crear($pago['pasarela_codigo'])->reembolsar($pago);

        BaseDatos::transaccion(function () use ($pago): void {
            $pagos = new Epago();
            $actual = $pagos->paraActualizar((int) $pago['id']);
            if ($actual['estado'] === 'aprobado') {
                $pagos->registrarEstado((int) $actual['id'], 'reembolsado');
                Bitacora::registrar('devolver', 'pago', (int) $actual['id']);
            }
        });
        return $this->volver('exito', 'Devolución de ' . bs($pago['importe']) . ' registrada para la cita ' . $pago['cita'] . '.');
    }

    /**
     * Webhook de Stripe (ruta pública, sin CSRF). La firma prueba que el aviso viene de Stripe y,
     * igual que al volver el usuario, el estado se confirma consultando la sesión a Stripe.
     */
    public function webhook(): Respuesta
    {
        try {
            $evento = Pasarelas::stripe()->leerWebhook($this->solicitud->cuerpoCrudo(), (string) $this->solicitud->cabecera('Stripe-Signature'));
        } catch (ExcepcionNegocio $error) {
            return $this->json(['recibido' => false, 'mensaje' => $error->getMessage()], 400);
        }
        if (!str_starts_with((string) ($evento['type'] ?? ''), 'checkout.session.')) {
            return $this->json(['recibido' => true]);
        }
        $pago = (new Epago())->porReferencia('stripe', (string) ($evento['data']['object']['id'] ?? ''));
        if (!$pago) {
            return $this->json(['recibido' => true, 'pago' => null]);
        }
        $estado = $this->verificarConPasarela((new Epago())->buscarDetalle((int) $pago['id']), [], null);
        return $this->json(['recibido' => true, 'pago' => (int) $pago['id'], 'estado' => $estado]);
    }

    // ---------------------------------------------------------------- reglas comunes

    /** Paso 5: pregunta a la pasarela (fuera de la transacción, es una llamada de red) y registra la respuesta. */
    private function verificarConPasarela(array $pago, array $datosRetorno, ?int $usuarioId): string
    {
        if ($pago['estado'] !== 'pendiente') {
            return $pago['estado'];
        }
        $respuesta = Pasarelas::crear($pago['pasarela_codigo'])->verificar($pago, $datosRetorno);
        return $this->registrarRespuesta((int) $pago['id'], $respuesta, $usuarioId);
    }

    /**
     * Paso 6, E1 y E3: con la fila bloqueada, solo un intento PENDIENTE cambia de estado. Así, si la
     * pasarela responde dos veces (retorno + webhook), el cobro se registra una sola vez.
     */
    private function registrarRespuesta(int $pagoId, array $respuesta, ?int $usuarioId): string
    {
        return BaseDatos::transaccion(function () use ($pagoId, $respuesta, $usuarioId): string {
            $pagos = new Epago();
            $actual = $pagos->paraActualizar($pagoId);
            if ($actual['estado'] !== 'pendiente' || $respuesta['estado'] === 'pendiente') {
                return $actual['estado'];
            }
            $estado = Epago::estadoVerificado($actual, $respuesta);
            $pagos->registrarEstado($pagoId, $estado);
            $accion = ['aprobado' => 'aprobar_pago', 'rechazado' => 'rechazar_pago'][$estado] ?? 'cancelar_pago';
            // El webhook no tiene sesión: la operación se atribuye a quien inició el pago.
            Bitacora::registrar($accion, 'pago', $pagoId, $usuarioId ?? (int) $actual['iniciado_por']);
            return $estado;
        });
    }

    /** Precondiciones y E2/E4: devuelve por qué la cita no se puede pagar, o null si se puede. */
    private static function motivoNoPagable(array $cita, ?array $pagoVigente): ?string
    {
        if (!in_array((int) $cita['estado_cita_id'], [Ecita_medica::PROGRAMADA, Ecita_medica::CONFIRMADA], true)) {
            return 'Solo se pagan citas programadas o confirmadas.';
        }
        if ((float) $cita['importe_acordado'] <= 0) {
            return 'Esta cita no tiene importe: no requiere pago.';
        }
        if ($pagoVigente && $pagoVigente['estado'] === 'aprobado') {
            return 'La cita ya está pagada.';
        }
        if ($pagoVigente) {
            return 'Hay un intento de pago pendiente. Verifícalo o anúlalo antes de reintentar.';
        }
        return null;
    }

    private static function mensaje(string $estado, string $cita): array
    {
        return match ($estado) {
            'aprobado'    => ['exito', "Pago aprobado: la cita $cita quedó pagada."],
            'rechazado'   => ['error', 'La pasarela rechazó el pago. Puedes reintentar con otra tarjeta.'],
            'cancelado'   => ['aviso', 'El intento de pago se canceló; no se realizó ningún cobro.'],
            'reembolsado' => ['aviso', 'El pago ya fue devuelto.'],
            default       => ['aviso', 'La pasarela aún no confirma el pago. Queda pendiente y se puede verificar después.'],
        };
    }

    /** La cita existe y es accesible: el paciente solo paga las suyas. */
    private function citaAccesible(string $id): array
    {
        $cita = (new Ecita_medica())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('La cita no existe.');
        if ($this->esPaciente() && (int) $cita['paciente_id'] !== (int) $this->usuario()['datos_personales_id']) {
            throw new ExcepcionAcceso('No tienes acceso a esta cita.');
        }
        return $cita;
    }

    private function pagoAccesible(string $id): array
    {
        $pago = (new Epago())->buscarDetalle((int) $id) ?? throw new ExcepcionNoEncontrado('El pago no existe.');
        if ($this->esPaciente() && (int) $pago['paciente_id'] !== (int) $this->usuario()['datos_personales_id']) {
            throw new ExcepcionAcceso('No tienes acceso a este pago.');
        }
        return $pago;
    }

    private function pasarelaActiva(): ?array
    {
        return (new Epasarela_pago())->activaPorCodigo((string) Configuracion::obtener('pasarela'));
    }

    /** Dirección absoluta a la que la pasarela devuelve al usuario. */
    private function urlRetorno(int $pagoId): string
    {
        $base = (string) Configuracion::obtener('url');
        if ($base === '') {
            $base = ($this->solicitud->esSegura() ? 'https://' : 'http://') . $this->solicitud->cabecera('Host');
        }
        return $base . '/pagos/retorno?pago=' . $pagoId;
    }

    private function esPaciente(): bool
    {
        return $this->usuario()['perfil'] === 'paciente';
    }
}
