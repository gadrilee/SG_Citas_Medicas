# SISTEMA WEB PARA LA GESTIÓN DE CITAS MÉDICAS PARA LA CLÍNICA VIDA SALUD 
* **Nombres**: 
                Gabriel Mamani Sandoval - 224071572
                Gabriel Condori Hurtado - 224027077
* **Materia**: Ingenieria de Requisito y Modelado INF343-SC
* **Grupo**: Seven
<img width="1536" height="1024" alt="image" src="recursos/marca/LogoCompletoHorizontal.png" />

_FICCT-UAGRM-SANTA CRUZ DE LA SIERRA_

## Stack

PHP 8.3 sin framework (MVC propio en español) · PostgreSQL 16 con PDO `pdo_pgsql` · HTML + Tailwind (compilado) + JavaScript vanilla · Apache · Docker.


## Ejecución local

Requisitos: Docker con Docker Compose.

```bash
docker compose up --build          # http://localhost:8080 (PUERTO_APP=8090 si el 8080 está ocupado)
docker compose down -v             # borra la base; al volver a subir se recrea con 001–008
./scripts/migrar.sh --solo-demo-p3 # agrega citas alrededor de HOY (repetible, útil el día de la demo)
./scripts/migrar.sh --solo-p4      # aplica 007 y 008 sobre una base que ya tenía 001–006
```

- Cuentas de demostración (contraseña `VidaSalud2026`): `admin`, `rosa.ag` (recepción), `dr.perez` (médico), `maria.lopez` (paciente).
- Salud del sistema: `/salud`.
- Sin Docker (XAMPP): copiar `.env.example` a `.env`, compilar estilos con `./scripts/css.sh` y apuntar el servidor a `publico/`.
- Pruebas: `composer install && vendor/bin/phpunit`.

### Pagos (CU20)

La pasarela se elige con `PASARELA` en `.env` (docker compose la toma de ahí):

- `simulada` (por defecto): «Vida Pay», sin cuentas externas. Tarjeta `4242 4242 4242 4242` se aprueba y `4000 0000 0000 0002` se rechaza.
- `stripe`: Stripe en modo prueba con Checkout embebido (el formulario de Stripe aparece dentro de la página de pago). Requiere `STRIPE_PUBLIC_KEY` (`pk_test_…`), `STRIPE_SECRET_KEY` (`sk_test_…`) y `STRIPE_WEBHOOK_SECRET` (`whsec_…`). En local los webhooks llegan con la CLI:

```bash
stripe listen --forward-to localhost:8080/pagos/webhook \
  --events checkout.session.completed,checkout.session.expired,charge.refunded
```

## Estructura

```
app/Nucleo/        MVC propio: Enrutador, Solicitud, Respuesta, BaseDatos (PDO), Sesion, Autorizacion, Csrf, Validador, Bitacora
app/Compartido/    layouts, parciales, menú por perfil, portada y panel de inicio
app/Paquetes/      un paquete por grupo de casos de uso, cada uno con:
                   Controladores/ (C*), Modelos/ (E*, una clase por tabla), Vistas/ (V*), plantillas/ y rutas.php
  P1_Seguridad     CU01–CU05 acceso, usuarios y permisos, bitácora, pacientes, médicos   ✔ ciclo 1
  P2_Agenda        CU06–CU10 especialidades, horarios, disponibilidad, reservas         ✔ ciclo 1
  P3_Citas         CU11–CU15 cancelación, asistencia, agenda, historial, reportes      ✔ ciclo 2
  P4_Atencion      CU16–CU20 historia clínica, consultas, estudios, resultados, pagos  ✔ ciclo 2
                   (Pasarelas/: interfaz PasarelaPago + PasarelaSimulada + PasarelaStripe)
almacen/           informes PDF de resultados (CU19), fuera de la carpeta pública
publico/           punto de entrada (index.php), CSS compilado, JS e imágenes
recursos/          fuente del CSS (Tailwind + sistema Liquid Glass) y logos
bd/                esquema (001), migraciones (002, 003, 005, 007) y datos demo (004, 006, 008)
pruebas/           PHPUnit
```


