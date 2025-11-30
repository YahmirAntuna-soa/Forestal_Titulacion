<?php
// dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si ya está logueado, redirigir según su rol
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    switch($_SESSION['user_role']) {
        case 'admin':
            header("Location: admin/index.php");
            break;
        case 'revisor':
            header("Location: revisor/index.php");
            break;
        case 'alumno':
            header("Location: alumno/index.php");
            break;
        default:
            // Si el rol no es válido, redirigir al login
            header("Location: login.php");
    }
    exit();
} else {
    // Si no hay sesión, redirigir al login
    header("Location: login.php");
    exit();
}
?>