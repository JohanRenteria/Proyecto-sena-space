<?php
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/enviar.php';

function passwordResetAlert(string $message, string $destination = 'Forgot-password.html'): void
{
    $safeMessage = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $safeDestination = json_encode($destination, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    echo "<script>alert($safeMessage); window.location.href=$safeDestination;</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Forgot-password.html');
    exit;
}

$submittedEmail = $_POST['email'] ?? '';
$email = is_string($submittedEmail) ? trim($submittedEmail) : '';
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    passwordResetAlert('Ingrese un correo válido.');
}

$stmt = mysqli_prepare($conexion, 'SELECT correo, nombre FROM admin WHERE correo = ? LIMIT 1');
if (!$stmt) {
    error_log('Password reset lookup failed: ' . mysqli_error($conexion));
    mysqli_close($conexion);
    passwordResetAlert('No fue posible procesar la solicitud. Inténtelo más tarde.');
}

mysqli_stmt_bind_param($stmt, 's', $email);
if (!mysqli_stmt_execute($stmt)) {
    error_log('Password reset lookup failed: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    passwordResetAlert('No fue posible procesar la solicitud. Inténtelo más tarde.');
}

mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    passwordResetAlert('Si el correo está registrado, recibirás un enlace para restablecer la contraseña.', 'Login.html');
}

mysqli_stmt_bind_result($stmt, $dbEmail, $nombre);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$token = '';
try {
    $token = bin2hex(random_bytes(32));
} catch (Throwable $exception) {
    error_log('Password reset token generation failed: ' . $exception->getMessage());
    mysqli_close($conexion);
    passwordResetAlert('No fue posible procesar la solicitud. Inténtelo más tarde.');
}

if ($token === '') {
    mysqli_close($conexion);
    passwordResetAlert('No fue posible procesar la solicitud. Inténtelo más tarde.');
}

$tokenHash = hash('sha256', $token);
$saveToken = mysqli_prepare(
    $conexion,
    'INSERT INTO password_resets (correo, token_hash, expires_at)
     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
     ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = CURRENT_TIMESTAMP'
);

if (!$saveToken) {
    error_log('Password reset token storage failed: ' . mysqli_error($conexion));
    mysqli_close($conexion);
    passwordResetAlert('No fue posible procesar la solicitud. Inténtelo más tarde.');
}

mysqli_stmt_bind_param($saveToken, 'ss', $dbEmail, $tokenHash);
if (!mysqli_stmt_execute($saveToken)) {
    error_log('Password reset token storage failed: ' . mysqli_stmt_error($saveToken));
    mysqli_stmt_close($saveToken);
    mysqli_close($conexion);
    passwordResetAlert('No fue posible procesar la solicitud. Inténtelo más tarde.');
}
mysqli_stmt_close($saveToken);

$configuredBaseUrl = getenv('APP_BASE_URL');
$baseUrl = is_string($configuredBaseUrl) && trim($configuredBaseUrl) !== ''
    ? rtrim(trim($configuredBaseUrl), '/')
    : 'http://localhost/Proyecto-sena-space';
$resetUrl = $baseUrl . '/New_password.html?' . http_build_query([
    'email' => $dbEmail,
    'token' => $token,
]);
$safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$safeName = htmlspecialchars(trim((string) $nombre), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$greeting = $safeName !== '' ? $safeName : 'usuario';
$body = '<p>Hola, ' . $greeting . '.</p>'
    . '<p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>'
    . '<p><a href="' . $safeResetUrl . '">Restablecer contraseña</a></p>'
    . '<p>Este enlace vence en una hora. Si no solicitaste el cambio, puedes ignorar este mensaje.</p>';

if (!sendEmail($dbEmail, $nombre, 'Restablecer contraseña - LOG-IN', $body)) {
    $removeToken = mysqli_prepare($conexion, 'DELETE FROM password_resets WHERE correo = ? AND token_hash = ?');
    if ($removeToken) {
        mysqli_stmt_bind_param($removeToken, 'ss', $dbEmail, $tokenHash);
        mysqli_stmt_execute($removeToken);
        mysqli_stmt_close($removeToken);
    }
    mysqli_close($conexion);
    passwordResetAlert('No se pudo enviar el correo. Verifica la configuración SMTP e inténtalo de nuevo.');
}

mysqli_close($conexion);
passwordResetAlert('Si el correo está registrado, recibirás un enlace para restablecer la contraseña.', 'Login.html');