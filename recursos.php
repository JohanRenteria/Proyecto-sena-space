<?php
session_start();

if (empty($_SESSION['usuario_id'])) {
    header('Location: Login.html');
    exit;
}

header('Location: recursos.html');
exit;
