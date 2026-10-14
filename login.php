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

$sql = "SELECT `cedula`, CONCAT_WS(' ', NULLIF(`nombre`, ''), NULLIF(`apellido`, '')),
              `correo`, `contraseña`, COALESCE(`rol_sistema`, 'Usuario'),
              `tipo_usuario`, `rol_id`
       FROM `admin` WHERE `correo` = ?";
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

mysqli_stmt_bind_result($stmt, $cedula, $nombre, $email, $storedPassword, $rolSistema, $tipoUsuario, $rolId);

if (mysqli_stmt_fetch($stmt)) {
    $loginOk = false;
    $passwordText = (string) $storedPassword;
    $rolSistema = trim((string) $rolSistema);
    $tipoUsuario = trim((string) $tipoUsuario);
    $rolId = (int) $rolId;

    if ($passwordText === $password) {
        $loginOk = true;
    } elseif (is_string($passwordText) && password_verify($password, $passwordText)) {
        $loginOk = true;
    } elseif (is_string($passwordText) && preg_match('/^[0-9a-f]{40}$/i', $passwordText) && sha1($password) === $passwordText) {
        // Legacy SHA1 passwords support
        $loginOk = true;
    }

    if ($loginOk) {
        if (
            $rolSistema === 'Administrador' ||
            $rolSistema === '7' ||
            $tipoUsuario === 'Administrador' ||
            $rolId === 7
        ) {
            $rolSistema = 'Administrador';
        } elseif (
            $rolSistema === 'Almacen' ||
            $tipoUsuario === 'Almacen' ||
            $rolId === 6
        ) {
            $rolSistema = 'Almacen';
        } else {
            $rolSistema = 'Usuario';
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $cedula;
        $_SESSION['usuario'] = $nombre ?: $email;
        $_SESSION['correo'] = $email;
        $_SESSION['tipo_usuario'] = $tipoUsuario;
        $_SESSION['rol_sistema'] = $rolSistema;
        mysqli_stmt_close($stmt);
        mysqli_close($conexion);
        if ($rolSistema === 'Administrador') {
            header('Location: administrador.php');
        } elseif ($rolSistema === 'Almacen') {
            header('Location: almacen.php');
        } else {
            header('Location: home.html');
        }
        exit;
    }
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo "<script>alert('Correo o contraseña incorrectos.'); window.location='Login.html';</script>";
