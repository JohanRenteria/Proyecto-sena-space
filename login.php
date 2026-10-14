<?php
require_once 'conexion.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Login.html');
    exit;
}

$correo = trim($_POST['correo'] ?? '');
$password = $_POST['password'] ?? '';

if (!is_string($password) || $correo === '' || $password === '') {
    echo "<script>alert('Debe ingresar correo y contraseña.'); window.location='Login.html';</script>";
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo "<script>alert('Ingrese un correo válido.'); window.location='Login.html';</script>";
    exit;
}

$sql = "SELECT `cedula`, CONCAT_WS(' ', NULLIF(`nombre`, ''), NULLIF(`apellido`, '')), `correo`, `contraseña`, COALESCE(`rol_sistema`, 'Usuario') FROM `admin` WHERE `correo` = ?";
$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die('Error en la preparación de la consulta: ' . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, 's', $correo);
if (!mysqli_stmt_execute($stmt)) {
    die('Error en la ejecución de la consulta: ' . mysqli_stmt_error($stmt));
}

mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
    echo "<script>alert('Correo o contraseña incorrectos.'); window.location='Login.html';</script>";
    exit;
}

mysqli_stmt_bind_result($stmt, $cedula, $nombre, $email, $storedPassword, $rolSistema);

if (mysqli_stmt_fetch($stmt)) {
    $loginOk = false;
    $passwordText = (string) $storedPassword;

   if ($passwordText === $password) {
        $loginOk = true;
    } elseif (is_string($passwordText) && password_verify($password, $passwordText)) {
        $loginOk = true;
    } elseif (is_string($passwordText) && preg_match('/^[0-9a-f]{40}$/i', $passwordText) && sha1($password) === $passwordText) {
        // Legacy SHA1 passwords support
        $loginOk = true;
    }

    if ($loginOk) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $cedula;
        $_SESSION['usuario'] = $nombre ?: $email;
        $_SESSION['correo'] = $email;
        $_SESSION['rol_sistema'] = $rolSistema ?: 'Usuario';
        $_SESSION['rol_id'] = match ($rolSistema) {
            'Administrador' => 7,
            'Almacen' => 6,
            'Cafetería' => 4,
            'Seguridad' => 3,
            'Visitante' => 5,
            default => 1, // Usuario
        };
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        header('Location: ' . (($rolSistema === 'Administrador') ? 'administrador.php' : 'home.html'));
        exit;
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo "<script>alert('Correo o contraseña incorrectos.'); window.location='Login.html';</script>";
