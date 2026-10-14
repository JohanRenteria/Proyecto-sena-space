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

$logoUrl = 'https://TU-DOMINIO.com/assets/logo.png';

$body = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contraseña</title>
</head>

<body style="margin:0; padding:0; background-color:#f1f5f9;
             font-family:Arial,Helvetica,sans-serif;">

    <table role="presentation" width="100%" cellspacing="0"
           cellpadding="0" border="0"
           style="background-color:#f1f5f9; padding:30px 10px;">
        <tr>
            <td align="center">

                <table role="presentation" width="100%" cellspacing="0"
                       cellpadding="0" border="0"
                       style="max-width:560px; background:#ffffff;
                              border-radius:12px; overflow:hidden;">

                    <!-- Encabezado -->
                    <tr>
                        <td align="center"
                            style="background:#173c70; padding:30px 20px;">

                            <div style="font-size:52px; line-height:1.3;
                                        text-align:center; margin:0 auto 14px;">
                                🎓
                            </div>

                            <h1 style="color:#ffffff; font-size:26px;
                                       margin:0;">
                                LOG-IN
                            </h1>

                            <p style="color:#dbeafe; font-size:14px;
                                      margin:8px 0 0;">
                                Recuperación de cuenta
                            </p>
                        </td>
                    </tr>

                    <!-- Contenido -->
                    <tr>
                        <td style="padding:32px 28px; color:#334155;">

                            <h2 style="color:#172033; font-size:23px;
                                       margin:0 0 18px;">
                                ¡Hola, ' . $greeting . '!
                            </h2>

                            <p style="font-size:15px; line-height:1.7;
                                      margin:0 0 16px;">
                                Hemos recibido una solicitud para
                                restablecer la contraseña de tu cuenta.
                            </p>

                            <p style="font-size:15px; line-height:1.7;
                                      margin:0 0 26px;">
                                Para crear una nueva contraseña, haz clic
                                en el siguiente botón:
                            </p>

                            <!-- Botón de recuperación -->
                            <table role="presentation" cellspacing="0"
                                   cellpadding="0" border="0"
                                   align="center"
                                   style="margin:0 auto 26px;">
                                <tr>
                                    <td align="center"
                                        bgcolor="#1769aa"
                                        style="border-radius:8px;">

                                        <a href="' . $safeResetUrl . '"
                                           style="display:inline-block;
                                                  padding:15px 28px;
                                                  color:#ffffff;
                                                  text-decoration:none;
                                                  font-size:15px;
                                                  font-weight:bold;
                                                  border-radius:8px;">
                                            Restablecer contraseña
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:13px; line-height:1.7;
                                      color:#64748b; margin:0 0 16px;">
                                Por seguridad, este enlace vence en una
                                hora. Si no solicitaste este cambio,
                                puedes ignorar este mensaje.
                            </p>

                            <hr style="border:0; border-top:1px solid
                                       #e2e8f0; margin:24px 0;">

                            <p style="font-size:12px; color:#64748b;
                                      line-height:1.6; margin:0;">
                                Si el botón no funciona, copia y pega
                                el siguiente enlace en tu navegador:
                            </p>

                            <p style="font-size:12px; line-height:1.6;
                                      word-break:break-all;">
                                <a href="' . $safeResetUrl . '"
                                   style="color:#1769aa;">
                                    ' . $safeResetUrl . '
                                </a>
                            </p>

                        </td>
                    </tr>

                    <!-- Pie de página -->
                    <tr>
                        <td align="center"
                            style="background:#f8fafc; padding:20px;
                                   border-top:1px solid #e2e8f0;">

                            <p style="font-size:12px; color:#64748b;
                                      margin:0 0 6px;">
                                LOG-IN
                            </p>

                            <p style="font-size:11px; color:#94a3b8;
                                      margin:0;">
                                Este es un mensaje automático.
                                Por favor, no respondas a este correo.
                            </p>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>';

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