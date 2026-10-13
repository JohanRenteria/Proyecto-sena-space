<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['usuario_id']) || empty($_SESSION['correo'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

require_once __DIR__ . '/conexion.php';
$usuarioId = (int) $_SESSION['usuario_id'];
$stmt = mysqli_prepare(
    $conexion,
    "SELECT CONCAT_WS(' ', NULLIF(nombre, ''), NULLIF(apellido, '')) AS usuario,
            correo, tipo_usuario, rol_sistema, numero_ficha AS ficha, telefono
     FROM admin WHERE cedula = ? LIMIT 1"
);
if (!$stmt) {
    error_log('Session profile query preparation failed: ' . mysqli_error($conexion));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No fue posible consultar el perfil.']);
    exit;
}

mysqli_stmt_bind_param($stmt, 'i', $usuarioId);
if (!mysqli_stmt_execute($stmt)) {
    error_log('Session profile query failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No fue posible consultar el perfil.']);
    exit;
}

$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
mysqli_close($conexion);

if (!$usuario) {
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'La cuenta de esta sesión ya no existe.']);
    exit;
}

echo json_encode([
    'success' => true,
    'usuario' => $usuario['usuario'] ?: $_SESSION['usuario'],
    'correo' => $usuario['correo'],
    'tipo_usuario' => $usuario['tipo_usuario'],
    'rol_sistema' => $usuario['rol_sistema'],
    'ficha' => $usuario['ficha'],
    'telefono' => $usuario['telefono']
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
