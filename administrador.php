<?php
session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.html');
    exit;
}

if (($_SESSION['rol_sistema'] ?? 'Usuario') !== 'Administrador') {
    http_response_code(403);
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><link href="css/sb-admin-2.min.css" rel="stylesheet"></head><body class="bg-light"><div class="container py-5"><div class="alert alert-danger"><h4>Acceso restringido</h4><p>Tu cuenta no tiene el rol de Administrador.</p><a href="usuario.php" class="btn btn-primary">Volver al módulo de usuario</a></div></div></body></html>';
    exit;
}

require __DIR__ . '/administrador.html';
