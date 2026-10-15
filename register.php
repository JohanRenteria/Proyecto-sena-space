<?php
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Register.html');
    exit;
}

$nombre = trim($_POST['registerName'] ?? '');
$apellido = trim($_POST['registerLastName'] ?? '');
$correo = trim($_POST['registerEmail'] ?? '');
$password = $_POST['registerPassword'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$tipoDocumento = trim($_POST['tipo_documento'] ?? '');
$cedula = trim($_POST['cedula'] ?? '');
$userType = trim($_POST['userType'] ?? '');
$numeroFicha = trim($_POST['numeroFicha'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');

if (
    $nombre === '' ||
    $apellido === '' ||
    $correo === '' ||
    !is_string($password) ||
    !is_string($confirmPassword) ||
    $password === '' ||
    $confirmPassword === '' ||
    $tipoDocumento === '' ||
    $cedula === '' ||
    $userType === ''
) {
    echo "<script>
        alert('Por favor completa todos los campos.');
        window.location='Register.html';
    </script>";
    exit;
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
        alert('Ingresa un correo válido.');
        window.location='Register.html';
    </script>";
    exit;
}
if (strlen($telefono) > 30) {
    echo "<script>
        alert('El teléfono no puede superar 30 caracteres.');
        window.location='Register.html';
    </script>";
    exit;
}

if ($password !== $confirmPassword) {
    echo "<script>
        alert('Las contraseñas no coinciden.');
        window.location='Register.html';
    </script>";
    exit;
}

if ($userType === 'Aprendiz' && $numeroFicha === '') {
    echo "<script>
        alert('Ingresa el número de ficha para el rol Aprendiz.');
        window.location='Register.html';
    </script>";
    exit;
}
if ($userType !== 'Aprendiz') {
    $numeroFicha = null;
}

if (!ctype_digit($cedula)) {
    echo "<script>
        alert('La cédula debe contener solo números.');
        window.location='Register.html';
    </script>";
    exit;
}

$cedulaInt = (int) $cedula;

$roles = [
    'Aprendiz'   => 1,
    'Instructor' => 2,
    'Seguridad'  => 3,
    'Cafetería'  => 4,
    'Visitante'  => 5,
    'Almacen'    => 6,
    'Administrador' => 7
];

if (!isset($roles[$userType])) {
    echo "<script>
        alert('El rol seleccionado no es válido.');
        window.location='Register.html';
    </script>";
    exit;
}

$rol_id = $roles[$userType];
$rolSistema = $userType === 'Administrador'
    ? 'Administrador'
    : ($userType === 'Almacen' ? 'Almacen' : 'Usuario');

$sql = 'SELECT 1 FROM `admin` WHERE `correo` = ? LIMIT 1';

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die('Error en la consulta de verificación: ' . mysqli_error($conexion));
}

mysqli_stmt_bind_param($stmt, 's', $correo);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo "<script>
        alert('Este correo ya está registrado.');
        window.location='Register.html';
    </script>";
    exit;
}

mysqli_stmt_close($stmt);

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$sql = 'INSERT INTO `admin`
        (`cedula`, `correo`, `nombre`, `apellido`, `tipo_usuario`, `rol_id`, `rol_sistema`,
         `tipo_documento`, `numero_ficha`, `telefono`, `contraseña`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die('Error en la preparación del registro: ' . mysqli_error($conexion));
}

mysqli_stmt_bind_param(
    $stmt,
    'issssisssss',
    $cedulaInt,
    $correo,
    $nombre,
    $apellido,
    $userType,
    $rol_id,
    $rolSistema,
    $tipoDocumento,
    $numeroFicha,
    $telefono,
    $hashedPassword
);

if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);

    echo "<script>
        alert('Error al registrar: " . addslashes($error) . "');
        window.location='Register.html';
    </script>";
    exit;
}

mysqli_stmt_close($stmt);
mysqli_close($conexion);

echo "<script>
    alert('Registro exitoso. Rol asignado correctamente. Ahora puedes iniciar sesión.');
    window.location='Login.html';
</script>";

?>
