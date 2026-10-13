<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/conexion.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

function responder(bool $success, string $message, $data = null, int $status = 200): void
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(false, 'Método no permitido.', null, 405);
}

$usuarioId = $_SESSION['usuario_id'] ?? null;
if (
    (!is_int($usuarioId) && !(is_string($usuarioId) && ctype_digit($usuarioId))) ||
    (int) $usuarioId < 1
) {
    responder(false, 'Debes iniciar sesión para continuar.', null, 401);
}
$usuarioId = (int) $usuarioId;

$accion = $_POST['accion'] ?? '';
if (!is_string($accion) || $accion === '') {
    responder(false, 'La acción es obligatoria.', null, 400);
}

$transaccionActiva = false;

function obtenerFechas(): array
{
    $inicioTexto = $_POST['fecha_inicio'] ?? '';
    $finTexto = $_POST['fecha_devolucion'] ?? '';

    if (!is_string($inicioTexto) || !is_string($finTexto)) {
        responder(false, 'Debes indicar fechas válidas.', null, 400);
    }

    $inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $inicioTexto);
    $erroresInicio = DateTimeImmutable::getLastErrors();
    $fin = DateTimeImmutable::createFromFormat('!Y-m-d', $finTexto);
    $erroresFin = DateTimeImmutable::getLastErrors();

    if (
        !$inicio ||
        !$fin ||
        ($erroresInicio !== false && ($erroresInicio['warning_count'] || $erroresInicio['error_count'])) ||
        ($erroresFin !== false && ($erroresFin['warning_count'] || $erroresFin['error_count'])) ||
        $inicio->format('Y-m-d') !== $inicioTexto ||
        $fin->format('Y-m-d') !== $finTexto
    ) {
        responder(false, 'Las fechas no tienen un formato válido.', null, 400);
    }

    if ($inicio < new DateTimeImmutable('today')) {
        responder(false, 'La fecha de inicio no puede ser anterior a hoy.', null, 400);
    }
    if ($fin < $inicio) {
        responder(false, 'La devolución no puede ser anterior a la fecha de inicio.', null, 400);
    }

    return [$inicioTexto, $finTexto];
}

function obtenerRecurso(mysqli $conexion, int $recursoId, bool $bloquear = false): ?array
{
    $sql = 'SELECT id, nombre, tipo, estado, stock_total
            FROM recursos
            WHERE id = ?' . ($bloquear ? ' FOR UPDATE' : '');
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $recursoId);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $recurso = mysqli_fetch_assoc($resultado);
    mysqli_stmt_close($stmt);

    return $recurso ?: null;
}

function obtenerDisponibilidad(mysqli $conexion, array $recurso, string $inicio, string $fin): array
{
    $stmt = mysqli_prepare(
        $conexion,
        "SELECT COALESCE(MAX(ocupacion_diaria), 0)
         FROM (
             SELECT fechas.dia, COALESCE(SUM(s.cantidad), 0) AS ocupacion_diaria
             FROM (
                 SELECT CAST(? AS DATE) AS dia
                 UNION
                 SELECT fecha_inicio
                 FROM solicitudes
                 WHERE recurso_id = ?
                   AND estado IN ('Pendiente', 'Aprobado')
                   AND fecha_inicio > ?
                   AND fecha_inicio <= ?
             ) AS fechas
             LEFT JOIN solicitudes AS s
               ON s.recurso_id = ?
              AND s.estado IN ('Pendiente', 'Aprobado')
              AND s.fecha_inicio <= fechas.dia
              AND s.fecha_devolucion >= fechas.dia
             GROUP BY fechas.dia
         ) AS ocupacion"
    );
    mysqli_stmt_bind_param(
        $stmt,
        'sissi',
        $inicio,
        $recurso['id'],
        $inicio,
        $fin,
        $recurso['id']
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $ocupados);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    $stock = (int) $recurso['stock_total'];
    $ocupados = (int) $ocupados;
    $disponibles = max(0, $stock - $ocupados);

    return [
        'stock_total' => $stock,
        'ocupados' => $ocupados,
        'disponibles' => $disponibles
    ];
}

try {
    mysqli_set_charset($conexion, 'utf8mb4');

    switch ($accion) {
        case 'listar_recursos':
            $resultado = mysqli_query(
                $conexion,
                "SELECT id, nombre, tipo, stock_total
                 FROM recursos
                 WHERE estado = 'Disponible' AND stock_total > 0
                 ORDER BY nombre"
            );
            responder(true, 'Recursos cargados correctamente.', mysqli_fetch_all($resultado, MYSQLI_ASSOC));

        case 'verificar_disponibilidad':
            $recursoId = filter_var($_POST['recurso_id'] ?? null, FILTER_VALIDATE_INT);
            if ($recursoId === false || $recursoId < 1) {
                responder(false, 'Selecciona un recurso válido.', null, 400);
            }

            [$inicio, $fin] = obtenerFechas();
            $recurso = obtenerRecurso($conexion, $recursoId);
            if (!$recurso) {
                responder(false, 'El recurso seleccionado no existe.', null, 404);
            }
            if ($recurso['estado'] !== 'Disponible') {
                responder(false, 'Este recurso no está disponible para solicitudes.', null, 409);
            }

            $disponibilidad = obtenerDisponibilidad($conexion, $recurso, $inicio, $fin);
            responder(true, 'Disponibilidad consultada.', $disponibilidad);

        case 'crear_solicitud':
            $recursoId = filter_var($_POST['recurso_id'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $observacion = $_POST['observacion'] ?? '';

            if ($recursoId === false || $recursoId < 1) {
                responder(false, 'Selecciona un recurso válido.', null, 400);
            }
            if ($cantidad === false || $cantidad < 1) {
                responder(false, 'La cantidad debe ser un número mayor que cero.', null, 400);
            }
            if (!is_string($observacion) || strlen(trim($observacion)) > 2000) {
                responder(false, 'La observación no puede superar 2000 caracteres.', null, 400);
            }

            [$inicio, $fin] = obtenerFechas();
            mysqli_begin_transaction($conexion);
            $transaccionActiva = true;

            $recurso = obtenerRecurso($conexion, $recursoId, true);
            if (!$recurso) {
                mysqli_rollback($conexion);
                $transaccionActiva = false;
                responder(false, 'El recurso seleccionado no existe.', null, 404);
            }
            if ($recurso['estado'] !== 'Disponible') {
                mysqli_rollback($conexion);
                $transaccionActiva = false;
                responder(false, 'Este recurso no está disponible para solicitudes.', null, 409);
            }

            $disponibilidad = obtenerDisponibilidad($conexion, $recurso, $inicio, $fin);
            if ($cantidad > $disponibilidad['disponibles']) {
                mysqli_rollback($conexion);
                $transaccionActiva = false;
                responder(
                    false,
                    'No hay suficientes unidades disponibles para esas fechas.',
                    $disponibilidad,
                    409
                );
            }

            $observacion = trim($observacion);
            $stmt = mysqli_prepare(
                $conexion,
                'INSERT INTO solicitudes
                    (usuario_id, recurso_id, fecha_inicio, fecha_devolucion, cantidad, observacion)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'iissis',
                $usuarioId,
                $recursoId,
                $inicio,
                $fin,
                $cantidad,
                $observacion
            );
            mysqli_stmt_execute($stmt);
            $solicitudId = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            mysqli_commit($conexion);
            $transaccionActiva = false;

            responder(true, 'Solicitud de préstamo enviada correctamente.', [
                'id' => $solicitudId,
                'estado' => 'Pendiente'
            ], 201);

        case 'mis_solicitudes':
            $stmt = mysqli_prepare(
                $conexion,
                'SELECT s.id, r.nombre AS recurso_nombre, r.tipo, s.fecha_inicio,
                        s.fecha_devolucion, s.cantidad, s.estado, s.observacion
                 FROM solicitudes AS s
                 INNER JOIN recursos AS r ON r.id = s.recurso_id
                 WHERE s.usuario_id = ?
                 ORDER BY s.fecha_creacion DESC, s.id DESC'
            );
            mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
            mysqli_stmt_execute($stmt);
            $solicitudes = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
            mysqli_stmt_close($stmt);
            responder(true, 'Solicitudes cargadas correctamente.', $solicitudes);

        default:
            responder(false, 'La acción solicitada no está disponible.', null, 400);
    }
} catch (mysqli_sql_exception $error) {
    if ($transaccionActiva) {
        mysqli_rollback($conexion);
    }
    error_log('Error en recursos_api.php: ' . $error->getMessage());
    responder(false, 'No fue posible completar la operación en la base de datos.', null, 500);
}
