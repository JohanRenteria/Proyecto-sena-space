<?php
require_once __DIR__ . '/conexion.php';

function newPasswordAlert(string $message, string $destination = 'Forgot-password.html'): void
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

$submittedToken = $_POST['token'] ?? '';
$submittedEmail = $_POST['email'] ?? '';
$token = is_string($submittedToken) ? trim($submittedToken) : '';
$email = is_string($submittedEmail) ? trim($submittedEmail) : '';
$newPassword = $_POST['newPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if (
    $email === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $token === '' ||
    !is_string($newPassword) ||
    !is_string($confirmPassword)
) {
    newPasswordAlert('El enlace no es válido. Solicita un nuevo restablecimiento.');
}

$resetPageUrl = 'New_password.html?' . http_build_query([
    'email' => $email,
    'token' => $token,
]);

if ($newPassword === '' || $confirmPassword === '') {
    newPasswordAlert('Debe ingresar y confirmar la nueva contraseña.', $resetPageUrl);
}

if ($newPassword !== $confirmPassword) {
    newPasswordAlert('Las contraseñas no coinciden.', $resetPageUrl);
}

if (strlen($newPassword) < 6) {
    newPasswordAlert('La contraseña debe tener al menos 6 caracteres.', $resetPageUrl);
}

$tokenHash = hash('sha256', $token);
mysqli_begin_transaction($conexion);

$check = mysqli_prepare(
    $conexion,
    'SELECT expires_at FROM password_resets WHERE correo = ? AND token_hash = ? AND expires_at > NOW() LIMIT 1 FOR UPDATE'
);
if (!$check) {
    error_log('Password reset token lookup failed: ' . mysqli_error($conexion));
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('No fue posible procesar el enlace. Solicita un nuevo restablecimiento.');
}

mysqli_stmt_bind_param($check, 'ss', $email, $tokenHash);
if (!mysqli_stmt_execute($check)) {
    error_log('Password reset token lookup failed: ' . mysqli_stmt_error($check));
    mysqli_stmt_close($check);
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('No fue posible procesar el enlace. Solicita un nuevo restablecimiento.');
}

mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) === 0) {
    mysqli_stmt_close($check);
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('El enlace es inválido o venció. Solicita un nuevo restablecimiento.');
}
mysqli_stmt_close($check);

$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
$update = mysqli_prepare($conexion, 'UPDATE admin SET `contraseña` = ? WHERE correo = ? LIMIT 1');
if (!$update) {
    error_log('Password reset update preparation failed: ' . mysqli_error($conexion));
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('No fue posible actualizar la contraseña. Inténtelo más tarde.');
}

mysqli_stmt_bind_param($update, 'ss', $hashedPassword, $email);
if (!mysqli_stmt_execute($update) || mysqli_stmt_affected_rows($update) !== 1) {
    error_log('Password reset update failed: ' . mysqli_stmt_error($update));
    mysqli_stmt_close($update);
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('No fue posible actualizar la contraseña. Solicita un nuevo enlace.');
}
mysqli_stmt_close($update);

$delete = mysqli_prepare($conexion, 'DELETE FROM password_resets WHERE correo = ? AND token_hash = ?');
if (!$delete) {
    error_log('Password reset token removal preparation failed: ' . mysqli_error($conexion));
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('No fue posible actualizar la contraseña. Inténtelo más tarde.');
}

mysqli_stmt_bind_param($delete, 'ss', $email, $tokenHash);
if (!mysqli_stmt_execute($delete) || mysqli_stmt_affected_rows($delete) !== 1) {
    error_log('Password reset token removal failed: ' . mysqli_stmt_error($delete));
    mysqli_stmt_close($delete);
    mysqli_rollback($conexion);
    mysqli_close($conexion);
    newPasswordAlert('No fue posible actualizar la contraseña. Solicita un nuevo enlace.');
}

mysqli_stmt_close($delete);
mysqli_commit($conexion);
mysqli_close($conexion);
newPasswordAlert('Contraseña actualizada correctamente.', 'Login.html');