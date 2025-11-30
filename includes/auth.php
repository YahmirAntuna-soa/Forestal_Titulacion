<?php
// includes/auth.php

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        $role = getUserRole();
        switch($role) {
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
                header("Location: login.php");
        }
        exit();
    }
}

function requireRole($allowed_roles) {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
    
    $user_role = getUserRole();
    if (!in_array($user_role, $allowed_roles)) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Usuario';
}
?>