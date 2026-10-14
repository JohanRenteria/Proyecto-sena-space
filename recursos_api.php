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

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' &&
    !($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['accion'] ?? '') === 'descargar_adjunto')
) {
    header('Allow: POST, GET');
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

$accion = $_SERVER['REQUEST_METHOD'] === 'GET'
    ? 'descargar_adjunto'
    : ($_POST['accion'] ?? '');
if (!is_string($accion) || $accion === '') {
    responder(false, 'La acción es obligatoria.', null, 400);
}

$transaccionActiva = false;
$archivoSubidoRuta = null;

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

function requireRoles(array $roles): void
{
    if (!in_array($_SESSION['rol_sistema'] ?? 'Usuario', $roles, true)) {
        responder(false, 'No tienes permisos para realizar esta operación.', null, 403);
    }
}

function registrarHistorial(mysqli $conexion, int $usuarioId, string $accion, string $detalle): void
{
    $stmt = mysqli_prepare(
        $conexion,
        'INSERT INTO historial_recursos (usuario_id, accion, detalle) VALUES (?, ?, ?)'
    );
    mysqli_stmt_bind_param($stmt, 'iss', $usuarioId, $accion, $detalle);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function obtenerSolicitudes(mysqli $conexion): array
{
    $resultado = mysqli_query(
        $conexion,
        "SELECT s.id, s.usuario_id, s.recurso_id,
                CONCAT_WS(' ', NULLIF(u.nombre, ''), NULLIF(u.apellido, '')) AS usuario_nombre,
                u.correo AS usuario_correo, u.numero_ficha AS usuario_ficha,
                u.telefono AS usuario_telefono,
                r.nombre AS recurso_nombre, r.tipo,
                s.fecha_inicio, s.fecha_devolucion, s.cantidad, s.estado,
                s.observacion, s.fecha_creacion, s.fecha_aprobacion,
                s.fecha_devolucion_real, s.devolucion_solicitada,
                s.modalidad, s.tipo_apoyo, s.prioridad, s.nombre_archivo,
                IF(s.archivo_disco IS NULL, 0, 1) AS tiene_archivo
         FROM solicitudes AS s
         INNER JOIN admin AS u ON u.cedula = s.usuario_id
         INNER JOIN recursos AS r ON r.id = s.recurso_id
         ORDER BY s.fecha_creacion DESC, s.id DESC"
    );

    return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}

function cargarPanelAdministracion(mysqli $conexion): array
{
    $recursosResultado = mysqli_query($conexion, 'SELECT * FROM recursos ORDER BY nombre');
    $usuariosResultado = mysqli_query(
        $conexion,
        "SELECT cedula, CONCAT_WS(' ', NULLIF(nombre, ''), NULLIF(apellido, '')) AS nombre,
                correo, tipo_usuario, rol_sistema, numero_ficha AS ficha, telefono
         FROM admin ORDER BY nombre, apellido"
    );
    $historialResultado = mysqli_query(
        $conexion,
        "SELECT h.id, h.fecha, h.accion, h.detalle,
                COALESCE(CONCAT_WS(' ', NULLIF(a.nombre, ''), NULLIF(a.apellido, '')), 'Sistema') AS actor
         FROM historial_recursos AS h
         LEFT JOIN admin AS a ON a.cedula = h.usuario_id
         ORDER BY h.fecha DESC, h.id DESC LIMIT 200"
    );
    $reposicionesResultado = mysqli_query(
        $conexion,
        'SELECT rp.*, r.nombre AS recurso_nombre
         FROM reposiciones AS rp
         INNER JOIN recursos AS r ON r.id = rp.recurso_id
         ORDER BY rp.fecha DESC, rp.id DESC'
    );

    $solicitudes = obtenerSolicitudes($conexion);
    $devoluciones = [];
    foreach ($solicitudes as $solicitud) {
        if (!in_array($solicitud['estado'], ['Aprobado', 'Devuelto'], true)) {
            continue;
        }

        $solicitud['fecha_prestamo'] = $solicitud['fecha_inicio'];
        $solicitud['estado'] = $solicitud['estado'] === 'Devuelto'
            ? 'Devuelto'
            : ((int) $solicitud['devolucion_solicitada'] === 1 ? 'Solicitada' : 'Pendiente');
        $devoluciones[] = $solicitud;
    }

    return [
        'recursos' => mysqli_fetch_all($recursosResultado, MYSQLI_ASSOC),
        'solicitudes' => $solicitudes,
        'devoluciones' => $devoluciones,
        'usuarios' => mysqli_fetch_all($usuariosResultado, MYSQLI_ASSOC),
        'historial' => mysqli_fetch_all($historialResultado, MYSQLI_ASSOC),
        'reposiciones' => mysqli_fetch_all($reposicionesResultado, MYSQLI_ASSOC)
    ];
}

try {
    mysqli_set_charset($conexion, 'utf8mb4');

    switch ($accion) {
        case 'listar_recursos':
            $resultado = mysqli_query(
                $conexion,
                "SELECT id, nombre, categoria, tipo, stock_total, stock_minimo, estado
                 FROM recursos
                 WHERE estado = 'Disponible' AND stock_total > 0
                 ORDER BY nombre"
            );
            responder(true, 'Recursos cargados correctamente.', mysqli_fetch_all($resultado, MYSQLI_ASSOC));

        case 'inventario_publico':
            $resultado = mysqli_query(
                $conexion,
                'SELECT id, nombre, categoria, tipo, stock_total, stock_minimo, estado
                 FROM recursos ORDER BY nombre'
            );
            responder(true, 'Inventario cargado correctamente.', mysqli_fetch_all($resultado, MYSQLI_ASSOC));

        case 'admin_dashboard':
            requireRoles(['Administrador']);
            responder(true, 'Datos cargados desde la base de datos.', cargarPanelAdministracion($conexion));

        case 'almacen_dashboard':
            requireRoles(['Administrador', 'Almacen']);
            $panel = cargarPanelAdministracion($conexion);
            if (($_SESSION['rol_sistema'] ?? 'Usuario') !== 'Administrador') {
                $panel['usuarios'] = [];
            }
            responder(true, 'Datos cargados desde la base de datos.', $panel);

        case 'descargar_adjunto':
            $solicitudId = filter_var($_GET['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
            if ($solicitudId === false || $solicitudId < 1) {
                responder(false, 'La solicitud seleccionada no es válida.', null, 400);
            }
            $stmt = mysqli_prepare(
                $conexion,
                'SELECT usuario_id, archivo_disco, nombre_archivo
                 FROM solicitudes WHERE id = ? LIMIT 1'
            );
            mysqli_stmt_bind_param($stmt, 'i', $solicitudId);
            mysqli_stmt_execute($stmt);
            $adjunto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$adjunto || !$adjunto['archivo_disco']) {
                responder(false, 'No se encontró un archivo adjunto para esta solicitud.', null, 404);
            }
            if (
                !in_array($_SESSION['rol_sistema'] ?? 'Usuario', ['Administrador', 'Almacen'], true) &&
                (int) $adjunto['usuario_id'] !== $usuarioId
            ) {
                responder(false, 'No tienes permiso para consultar este archivo.', null, 403);
            }
            if (!preg_match('/^[a-f0-9]{32}\.(pdf|doc|docx|png|jpg|jpeg)$/', $adjunto['archivo_disco'])) {
                error_log('Unexpected stored attachment name for request #' . $solicitudId);
                responder(false, 'El archivo adjunto no está disponible.', null, 500);
            }
            $rutaAdjunto = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR .
                'solicitudes' . DIRECTORY_SEPARATOR . $adjunto['archivo_disco'];
            if (!is_file($rutaAdjunto)) {
                responder(false, 'El archivo adjunto ya no está disponible.', null, 404);
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($rutaAdjunto) ?: 'application/octet-stream';
            header_remove('Content-Type');
            header('Content-Type: ' . $mime);
            header('Content-Length: ' . filesize($rutaAdjunto));
            header('Content-Disposition: attachment; filename="soporte"; filename*=UTF-8\'\'' . rawurlencode($adjunto['nombre_archivo']));
            header('Cache-Control: private, no-store');
            header('X-Content-Type-Options: nosniff');
            readfile($rutaAdjunto);
            exit;

        case 'mi_perfil':
            $stmt = mysqli_prepare(
                $conexion,
                "SELECT cedula, CONCAT_WS(' ', NULLIF(nombre, ''), NULLIF(apellido, '')) AS nombre,
                        correo, tipo_usuario, rol_sistema, numero_ficha AS ficha, telefono
                 FROM admin WHERE cedula = ? LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
            mysqli_stmt_execute($stmt);
            $usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$usuario) {
                responder(false, 'No se encontró el usuario de la sesión.', null, 404);
            }

            $solicitudesStmt = mysqli_prepare(
                $conexion,
                'SELECT s.id, s.usuario_id, s.recurso_id, r.nombre AS recurso_nombre,
                        r.tipo, r.categoria, s.fecha_inicio, s.fecha_devolucion,
                        s.cantidad, s.estado, s.observacion, s.fecha_creacion
                 FROM solicitudes AS s
                 INNER JOIN recursos AS r ON r.id = s.recurso_id
                 WHERE s.usuario_id = ?
                 ORDER BY s.fecha_creacion DESC, s.id DESC'
            );
            mysqli_stmt_bind_param($solicitudesStmt, 'i', $usuarioId);
            mysqli_stmt_execute($solicitudesStmt);
            $solicitudes = mysqli_fetch_all(mysqli_stmt_get_result($solicitudesStmt), MYSQLI_ASSOC);
            mysqli_stmt_close($solicitudesStmt);

            $recursosResultado = mysqli_query(
                $conexion,
                "SELECT id, nombre, categoria, tipo FROM recursos WHERE estado <> 'Baja' ORDER BY nombre"
            );
            $favoritosStmt = mysqli_prepare(
                $conexion,
                'SELECT recurso_id FROM recursos_favoritos WHERE usuario_id = ? ORDER BY fecha_creacion DESC'
            );
            mysqli_stmt_bind_param($favoritosStmt, 'i', $usuarioId);
            mysqli_stmt_execute($favoritosStmt);
            $favoritosResultado = mysqli_stmt_get_result($favoritosStmt);
            $favoritos = array_map(
                'intval',
                array_column(mysqli_fetch_all($favoritosResultado, MYSQLI_ASSOC), 'recurso_id')
            );
            mysqli_stmt_close($favoritosStmt);

            responder(true, 'Perfil cargado desde la base de datos.', [
                'usuario' => $usuario,
                'solicitudes' => $solicitudes,
                'recursos' => mysqli_fetch_all($recursosResultado, MYSQLI_ASSOC),
                'favoritos' => $favoritos
            ]);

        case 'guardar_favorito':
            $recursoId = filter_var($_POST['recurso_id'] ?? null, FILTER_VALIDATE_INT);
            if ($recursoId === false || $recursoId < 1) {
                responder(false, 'El recurso seleccionado no es válido.', null, 400);
            }
            $recursoStmt = mysqli_prepare(
                $conexion,
                "SELECT id FROM recursos WHERE id = ? AND estado <> 'Baja' LIMIT 1"
            );
            mysqli_stmt_bind_param($recursoStmt, 'i', $recursoId);
            mysqli_stmt_execute($recursoStmt);
            $recursoExiste = mysqli_stmt_get_result($recursoStmt)->num_rows === 1;
            mysqli_stmt_close($recursoStmt);
            if (!$recursoExiste) {
                responder(false, 'El recurso no existe o ya no está disponible.', null, 404);
            }
            $stmt = mysqli_prepare(
                $conexion,
                'INSERT INTO recursos_favoritos (usuario_id, recurso_id) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE fecha_creacion = CURRENT_TIMESTAMP'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $usuarioId, $recursoId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            responder(true, 'Favorito guardado correctamente.');

        case 'quitar_favorito':
            $recursoId = filter_var($_POST['recurso_id'] ?? null, FILTER_VALIDATE_INT);
            if ($recursoId === false || $recursoId < 1) {
                responder(false, 'El recurso seleccionado no es válido.', null, 400);
            }
            $stmt = mysqli_prepare(
                $conexion,
                'DELETE FROM recursos_favoritos WHERE usuario_id = ? AND recurso_id = ?'
            );
            mysqli_stmt_bind_param($stmt, 'ii', $usuarioId, $recursoId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            responder(true, 'Favorito eliminado.');

        case 'guardar_recurso':
            requireRoles(['Administrador', 'Almacen']);
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $categoria = trim((string) ($_POST['categoria'] ?? ''));
            $tipo = $_POST['tipo'] ?? '';
            $estado = $_POST['estado'] ?? 'Disponible';
            $stock = filter_var($_POST['stock_total'] ?? null, FILTER_VALIDATE_INT);
            $minimo = filter_var($_POST['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
            $codigo = trim((string) ($_POST['codigo'] ?? ''));
            $ubicacion = trim((string) ($_POST['ubicacion'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));

            if (
                $nombre === '' || strlen($nombre) > 100 ||
                $categoria === '' || strlen($categoria) > 50 ||
                !in_array($tipo, ['Objeto', 'Servicio'], true) ||
                !in_array($estado, ['Disponible', 'Mantenimiento', 'Baja'], true) ||
                $stock === false || $stock < 0 ||
                $minimo === false || $minimo < 0 ||
                ($id !== false && $id < 1)
            ) {
                responder(false, 'Verifica los campos del recurso y sus cantidades.', null, 400);
            }

            if ($id === false) {
                $stmt = mysqli_prepare(
                    $conexion,
                    'INSERT INTO recursos
                        (nombre, categoria, codigo, ubicacion, descripcion, stock_total, estado, stock_minimo, tipo)
                     VALUES (?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'), NULLIF(?, \'\'), ?, ?, ?, ?)'
                );
                mysqli_stmt_bind_param(
                    $stmt,
                    'sssssisis',
                    $nombre,
                    $categoria,
                    $codigo,
                    $ubicacion,
                    $descripcion,
                    $stock,
                    $estado,
                    $minimo,
                    $tipo
                );
                mysqli_stmt_execute($stmt);
                $recursoId = mysqli_insert_id($conexion);
                mysqli_stmt_close($stmt);
                registrarHistorial($conexion, $usuarioId, 'Nuevo recurso', 'Se creó el recurso "' . $nombre . '".');
                responder(true, 'Recurso guardado correctamente.', ['id' => $recursoId], 201);
            }

            $stmt = mysqli_prepare(
                $conexion,
                'UPDATE recursos
                 SET nombre = ?, categoria = ?, codigo = NULLIF(?, \'\'),
                     ubicacion = NULLIF(?, \'\'), descripcion = NULLIF(?, \'\'),
                     stock_total = ?, estado = ?, stock_minimo = ?, tipo = ?
                 WHERE id = ?'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'sssssisssi',
                $nombre,
                $categoria,
                $codigo,
                $ubicacion,
                $descripcion,
                $stock,
                $estado,
                $minimo,
                $tipo,
                $id
            );
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) === 0) {
                $check = mysqli_prepare($conexion, 'SELECT 1 FROM recursos WHERE id = ?');
                mysqli_stmt_bind_param($check, 'i', $id);
                mysqli_stmt_execute($check);
                $existe = mysqli_stmt_get_result($check)->num_rows > 0;
                mysqli_stmt_close($check);
                if (!$existe) {
                    mysqli_stmt_close($stmt);
                    responder(false, 'El recurso no existe.', null, 404);
                }
            }
            mysqli_stmt_close($stmt);
            registrarHistorial($conexion, $usuarioId, 'Editar recurso', 'Se actualizó el recurso "' . $nombre . '".');
            responder(true, 'Recurso actualizado correctamente.', ['id' => $id]);

        case 'retirar_recurso':
            requireRoles(['Administrador', 'Almacen']);
            $recursoId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($recursoId === false || $recursoId < 1) {
                responder(false, 'El recurso seleccionado no es válido.', null, 400);
            }
            $stmt = mysqli_prepare($conexion, "UPDATE recursos SET estado = 'Baja' WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $recursoId);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                responder(false, 'El recurso no existe o ya está retirado.', null, 404);
            }
            mysqli_stmt_close($stmt);
            registrarHistorial($conexion, $usuarioId, 'Retirar recurso', 'Se dio de baja el recurso #' . $recursoId . '.');
            responder(true, 'Recurso retirado correctamente.');

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
            $tipoApoyo = trim((string) ($_POST['tipo_apoyo'] ?? ''));
            $prioridad = $_POST['prioridad'] ?? 'Media';
            $nombreArchivo = '';
            $archivoTemporal = null;
            $extensionArchivo = null;
            $modalidad = $tipoApoyo !== '' || isset($_POST['tipo_solicitud']) ? 'Almacen' : 'Prestamo';

            if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $archivo = $_FILES['archivo'];
                if ($archivo['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($archivo['tmp_name'])) {
                    responder(false, 'No se pudo recibir el archivo adjunto.', null, 400);
                }
                if ($archivo['size'] < 1 || $archivo['size'] > 5 * 1024 * 1024) {
                    responder(false, 'El archivo adjunto debe pesar como máximo 5 MB.', null, 400);
                }
                $nombreArchivo = basename(str_replace('\\', '/', (string) $archivo['name']));
                $nombreArchivo = preg_replace('/[\x00-\x1F\x7F]/', '', $nombreArchivo);
                if ($nombreArchivo === '' || strlen($nombreArchivo) > 255) {
                    responder(false, 'El nombre del archivo adjunto no es válido.', null, 400);
                }
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
                $extensionesPermitidas = [
                    'application/pdf' => 'pdf',
                    'application/msword' => 'doc',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg'
                ];
                $extensionArchivo = $extensionesPermitidas[$mime] ?? null;
                if (!$extensionArchivo) {
                    responder(false, 'Adjunta un PDF, documento Word, PNG o JPEG válido.', null, 400);
                }
                $archivoTemporal = $archivo['tmp_name'];
            }

            if ($recursoId === false || $recursoId < 1) {
                responder(false, 'Selecciona un recurso válido.', null, 400);
            }
            if ($cantidad === false || $cantidad < 1) {
                responder(false, 'La cantidad debe ser un número mayor que cero.', null, 400);
            }
            if (!is_string($observacion) || strlen(trim($observacion)) > 2000) {
                responder(false, 'La observación no puede superar 2000 caracteres.', null, 400);
            }

            if (!in_array($prioridad, ['Baja', 'Media', 'Alta', 'Urgente'], true)) {
                responder(false, 'La prioridad seleccionada no es válida.', null, 400);
            }
            if (strlen($tipoApoyo) > 100 || strlen($nombreArchivo) > 255) {
                responder(false, 'Los datos de apoyo o el nombre del archivo son demasiado extensos.', null, 400);
            }

            if (!isset($_POST['fecha_inicio']) && !isset($_POST['fecha_devolucion']) && $modalidad === 'Almacen') {
                $inicio = date('Y-m-d');
                $fin = $inicio;
            } else {
                [$inicio, $fin] = obtenerFechas();
            }
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

            $archivoDisco = '';
            if ($archivoTemporal !== null && $extensionArchivo !== null) {
                $directorioAdjuntos = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'solicitudes';
                if (!is_dir($directorioAdjuntos) && !mkdir($directorioAdjuntos, 0750, true) && !is_dir($directorioAdjuntos)) {
                    mysqli_rollback($conexion);
                    $transaccionActiva = false;
                    error_log('Could not create the request attachment directory.');
                    responder(false, 'No se pudo guardar el archivo adjunto.', null, 500);
                }
                $archivoDisco = bin2hex(random_bytes(16)) . '.' . $extensionArchivo;
                $archivoSubidoRuta = $directorioAdjuntos . DIRECTORY_SEPARATOR . $archivoDisco;
                if (!move_uploaded_file($archivoTemporal, $archivoSubidoRuta)) {
                    $archivoSubidoRuta = null;
                    mysqli_rollback($conexion);
                    $transaccionActiva = false;
                    responder(false, 'No se pudo guardar el archivo adjunto.', null, 500);
                }
            }

            $observacion = trim($observacion);
            $stmt = mysqli_prepare(
                $conexion,
                'INSERT INTO solicitudes
                    (usuario_id, recurso_id, fecha_inicio, fecha_devolucion, cantidad,
                     modalidad, tipo_apoyo, prioridad, nombre_archivo, archivo_disco, observacion)
                 VALUES (?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), ?, NULLIF(?, \'\'), NULLIF(?, \'\'), ?)'
            );
            mysqli_stmt_bind_param(
                $stmt,
                'iississssss',
                $usuarioId,
                $recursoId,
                $inicio,
                $fin,
                $cantidad,
                $modalidad,
                $tipoApoyo,
                $prioridad,
                $nombreArchivo,
                $archivoDisco,
                $observacion
            );
            mysqli_stmt_execute($stmt);
            $solicitudId = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmt);
            mysqli_commit($conexion);
            $transaccionActiva = false;
            $archivoSubidoRuta = null;

            responder(true, 'Solicitud de préstamo enviada correctamente.', [
                'id' => $solicitudId,
                'estado' => 'Pendiente'
            ], 201);

        case 'aprobar_solicitud':
        case 'rechazar_solicitud':
        case 'solicitar_devolucion':
        case 'registrar_devolucion':
            requireRoles(['Administrador', 'Almacen']);
            $solicitudId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if ($solicitudId === false || $solicitudId < 1) {
                responder(false, 'La solicitud seleccionada no es válida.', null, 400);
            }
            $observacion = trim((string) ($_POST['observacion'] ?? ''));
            if (strlen($observacion) > 2000) {
                responder(false, 'La observación no puede superar 2000 caracteres.', null, 400);
            }

            mysqli_begin_transaction($conexion);
            $transaccionActiva = true;
            $stmt = mysqli_prepare(
                $conexion,
                'SELECT s.estado, s.recurso_id, s.fecha_inicio, s.fecha_devolucion,
                        s.cantidad, s.devolucion_solicitada, r.stock_total, r.estado AS recurso_estado
                 FROM solicitudes AS s
                 INNER JOIN recursos AS r ON r.id = s.recurso_id
                 WHERE s.id = ? FOR UPDATE'
            );
            mysqli_stmt_bind_param($stmt, 'i', $solicitudId);
            mysqli_stmt_execute($stmt);
            $solicitud = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$solicitud) {
                mysqli_rollback($conexion);
                $transaccionActiva = false;
                responder(false, 'La solicitud no existe.', null, 404);
            }

            $estadoActual = $solicitud['estado'];
            if ($accion === 'aprobar_solicitud' || $accion === 'rechazar_solicitud') {
                if ($estadoActual !== 'Pendiente') {
                    mysqli_rollback($conexion);
                    $transaccionActiva = false;
                    responder(false, 'La solicitud ya fue procesada.', null, 409);
                }
                if ($accion === 'aprobar_solicitud') {
                    $recurso = [
                        'id' => $solicitud['recurso_id'],
                        'stock_total' => $solicitud['stock_total']
                    ];
                    $disponibilidad = obtenerDisponibilidad(
                        $conexion,
                        $recurso,
                        $solicitud['fecha_inicio'],
                        $solicitud['fecha_devolucion']
                    );
                    if (
                        $solicitud['recurso_estado'] !== 'Disponible' ||
                        $disponibilidad['disponibles'] < (int) $solicitud['cantidad']
                    ) {
                        mysqli_rollback($conexion);
                        $transaccionActiva = false;
                        responder(false, 'No hay inventario disponible para aprobar esta solicitud.', $disponibilidad, 409);
                    }
                    $stmt = mysqli_prepare(
                        $conexion,
                        "UPDATE solicitudes SET estado = 'Aprobado', fecha_aprobacion = NOW(),
                                observacion = COALESCE(NULLIF(?, ''), observacion)
                         WHERE id = ? AND estado = 'Pendiente'"
                    );
                    mysqli_stmt_bind_param($stmt, 'si', $observacion, $solicitudId);
                    $historialAccion = 'Aprobar solicitud';
                } else {
                    $stmt = mysqli_prepare(
                        $conexion,
                        "UPDATE solicitudes SET estado = 'Rechazado',
                                observacion = COALESCE(NULLIF(?, ''), observacion)
                         WHERE id = ? AND estado = 'Pendiente'"
                    );
                    mysqli_stmt_bind_param($stmt, 'si', $observacion, $solicitudId);
                    $historialAccion = 'Rechazar solicitud';
                }
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                registrarHistorial($conexion, $usuarioId, $historialAccion, 'Se procesó la solicitud #' . $solicitudId . '.');
            } elseif ($accion === 'solicitar_devolucion') {
                if ($estadoActual !== 'Aprobado' || (int) $solicitud['devolucion_solicitada'] === 1) {
                    mysqli_rollback($conexion);
                    $transaccionActiva = false;
                    responder(false, 'Solo se puede solicitar una devolución pendiente para un préstamo aprobado.', null, 409);
                }
                $stmt = mysqli_prepare(
                    $conexion,
                    'UPDATE solicitudes SET devolucion_solicitada = 1 WHERE id = ? AND estado = \'Aprobado\''
                );
                mysqli_stmt_bind_param($stmt, 'i', $solicitudId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                registrarHistorial($conexion, $usuarioId, 'Solicitar devolución', 'Se solicitó devolución para la solicitud #' . $solicitudId . '.');
            } else {
                if ($estadoActual !== 'Aprobado') {
                    mysqli_rollback($conexion);
                    $transaccionActiva = false;
                    responder(false, 'Solo se puede registrar la devolución de un préstamo aprobado.', null, 409);
                }
                $stmt = mysqli_prepare(
                    $conexion,
                    "UPDATE solicitudes SET estado = 'Devuelto', fecha_devolucion_real = NOW(),
                            devolucion_solicitada = 0
                     WHERE id = ? AND estado = 'Aprobado'"
                );
                mysqli_stmt_bind_param($stmt, 'i', $solicitudId);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                registrarHistorial($conexion, $usuarioId, 'Registrar devolución', 'Se registró la devolución para la solicitud #' . $solicitudId . '.');
            }
            mysqli_commit($conexion);
            $transaccionActiva = false;
            responder(true, 'La solicitud se actualizó correctamente.');

        case 'solicitar_reposicion':
            requireRoles(['Administrador', 'Almacen']);
            $recursoId = filter_var($_POST['recurso_id'] ?? null, FILTER_VALIDATE_INT);
            $cantidad = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
            $observacion = trim((string) ($_POST['observacion'] ?? ''));
            if ($recursoId === false || $recursoId < 1 || $cantidad === false || $cantidad < 1 || strlen($observacion) > 2000) {
                responder(false, 'Recurso, cantidad u observación no válidos.', null, 400);
            }
            $stmt = mysqli_prepare(
                $conexion,
                "INSERT INTO reposiciones (recurso_id, cantidad_solicitada, observacion)
                 SELECT id, ?, NULLIF(?, '') FROM recursos WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, 'isi', $cantidad, $observacion, $recursoId);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                responder(false, 'El recurso no existe.', null, 404);
            }
            mysqli_stmt_close($stmt);
            registrarHistorial($conexion, $usuarioId, 'Solicitar implementos', 'Se solicitaron ' . $cantidad . ' unidad(es) para el recurso #' . $recursoId . '.');
            responder(true, 'Reposición registrada correctamente.');

        case 'cambiar_rol':
            requireRoles(['Administrador']);
            $cedula = filter_var($_POST['cedula'] ?? null, FILTER_VALIDATE_INT);
            $rol = $_POST['rol'] ?? '';
            if ($cedula === false || $cedula < 1 || !in_array($rol, ['Administrador', 'Usuario'], true)) {
                responder(false, 'Usuario o rol no válidos.', null, 400);
            }
            $stmt = mysqli_prepare($conexion, 'UPDATE admin SET rol_sistema = ? WHERE cedula = ?');
            mysqli_stmt_bind_param($stmt, 'si', $rol, $cedula);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                responder(false, 'El usuario no existe o ya tiene ese rol.', null, 404);
            }
            mysqli_stmt_close($stmt);
            registrarHistorial($conexion, $usuarioId, 'Cambiar rol', 'Se asignó el rol ' . $rol . ' al usuario #' . $cedula . '.');
            responder(true, 'Rol actualizado correctamente.');

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
    if ($archivoSubidoRuta !== null && is_file($archivoSubidoRuta)) {
        unlink($archivoSubidoRuta);
    }
    error_log('Error en recursos_api.php: ' . $error->getMessage());
    responder(false, 'No fue posible completar la operación en la base de datos.', null, 500);
}
