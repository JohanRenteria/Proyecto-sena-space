<?php
session_start();
// Validación de sesión activa
if (empty($_SESSION['usuario_id'])) { 
    header('Location: Login.html'); 
    exit; 
}

// Control de acceso por roles (Validación RBAC)
if (!in_array($_SESSION['rol_sistema'] ?? 'Usuario', ['Administrador', 'Almacen'], true)) {
    http_response_code(403); 
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><link href="css/sb-admin-2.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger"><h4>Acceso restringido</h4><p>No tienes permisos para ver este módulo.</p><a href="usuario.php" class="btn btn-primary">Volver</a></div></div></body></html>'; 
    exit; 
}

$nombre = htmlspecialchars($_SESSION['usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
?>