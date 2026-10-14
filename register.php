<?php
require_once 'conexion.php';

/*
|--------------------------------------------------------------------------
| REGISTRO DE USUARIOS Y ASIGNACIÓN NUMÉRICA DE ROLES
|--------------------------------------------------------------------------
|
| El formulario HTML envía el rol seleccionado mediante "userType".
| En este archivo PHP hacemos la conversión oficial del nombre del rol
| a su número (rol_id) antes de guardarlo en la base de datos.
|
| Roles disponibles y nivel de acceso:
|   Aprendiz, Instructor, Seguridad, Cafetería y Visitante = Usuario
|   Almacen = Almacen
|   Administrador = Administrador
|
| IMPORTANTE:
| No confiamos únicamente en el valor "rol_id" enviado por HTML/JavaScript.
| PHP vuelve a calcular el ID a partir de "userType" para evitar que un
| usuario pueda modificar el número desde el navegador.
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Register.html');
    exit;
}


// ==========================================================================
// 1. RECIBIR DATOS DEL FORMULARIO
// ==========================================================================

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


// ==========================================================================
// 2. VALIDAR CAMPOS OBLIGATORIOS
// ==========================================================================

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


// ==========================================================================
// 3. VALIDAR CORREO
// ==========================================================================

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


// ==========================================================================
// 4. VALIDAR CONTRASEÑAS
// ==========================================================================

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


// ==========================================================================
// 5. VALIDAR CÉDULA
// ==========================================================================

if (!ctype_digit($cedula)) {
    echo "<script>
        alert('La cédula debe contener solo números.');
        window.location='Register.html';
    </script>";
    exit;
}

$cedulaInt = (int) $cedula;


// ==========================================================================
// 6. ASIGNACIÓN NUMÉRICA DE ROLES
// ==========================================================================
//
// El usuario selecciona el nombre del rol en Register.html.
// Aquí PHP transforma ese nombre en el número que se guardará en la BD.
//
// Aprendiz   -> 1
// Instructor -> 2
// Seguridad  -> 3
// Cafetería  -> 4
// Visitante  -> 5
// Almacen    -> 6
//
// Este paso se realiza en PHP para que el número del rol no dependa
// únicamente del HTML o JavaScript.
// ==========================================================================

$roles = [
    'Aprendiz'   => 1,
    'Instructor' => 2,
    'Seguridad'  => 3,
    'Cafetería'  => 4,
    'Visitante'  => 5,
    'Almacen'    => 6,
    'Administrador' => 7
];


// ==========================================================================
// 7. COMPROBAR QUE EL ROL SEA VÁLIDO
// ==========================================================================

if (!isset($roles[$userType])) {
    echo "<script>
        alert('El rol seleccionado no es válido.');
        window.location='Register.html';
    </script>";
    exit;
}


// ==========================================================================
// 8. OBTENER EL ID NUMÉRICO DEL ROL
// ==========================================================================

$rol_id = $roles[$userType];
$rolSistema = $userType === 'Administrador'
    ? 'Administrador'
    : ($userType === 'Almacen' ? 'Almacen' : 'Usuario');


// ==========================================================================
// 9. COMPROBAR SI EL CORREO YA EXISTE
// ==========================================================================

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


// ==========================================================================
// 10. ENCRIPTAR LA CONTRASEÑA
// ==========================================================================

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);


// ==========================================================================
// 11. INSERTAR EL USUARIO
// ==========================================================================
//
// El nombre del rol se guarda en tipo_usuario y su ID en rol_id.
// ==========================================================================

$sql = 'INSERT INTO `admin`
        (`cedula`, `correo`, `nombre`, `apellido`, `tipo_usuario`, `rol_id`, `rol_sistema`,
         `tipo_documento`, `numero_ficha`, `telefono`, `contraseña`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

$stmt = mysqli_prepare($conexion, $sql);

if (!$stmt) {
    die('Error en la preparación del registro: ' . mysqli_error($conexion));
}


// ==========================================================================
// 12. GUARDAR EL USUARIO Y LOS CAMPOS DEL FORMULARIO
// ==========================================================================
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


// ==========================================================================
// 13. EJECUTAR REGISTRO
// ==========================================================================

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


// ==========================================================================
// 14. CERRAR CONEXIÓN
// ==========================================================================

mysqli_stmt_close($stmt);
mysqli_close($conexion);


// ==========================================================================
// 15. REGISTRO EXITOSO
// ==========================================================================
//
// El usuario ya quedó guardado con su ID numérico de rol.
// ==========================================================================

echo "<script>
    alert('Registro exitoso. Rol asignado correctamente. Ahora puedes iniciar sesión.');
    window.location='Login.html';
</script>";

?>
