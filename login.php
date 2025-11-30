<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $matricula = trim($_POST['matricula']);
    $password = trim($_POST['password']);
    
    $sql = "SELECT u.*, r.nombre as rol_nombre 
            FROM usuarios u 
            JOIN roles r ON u.roles = r.id 
            WHERE u.matricula = :matricula AND u.activo = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':matricula', $matricula, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar contraseña (asumiendo que está hasheada)
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['rol_nombre'];
            $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido_paterno'];
            $_SESSION['user_matricula'] = $user['matricula'];
            
            // Redirigir según el rol
            switch($user['rol_nombre']) {
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
                    $error = "Rol no válido";
            }
            exit();
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado o inactivo";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Titulación</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--secondary-color);
            color: var(--white);
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }
        
        .back-button:hover {
            background: var(--primary-color);
        }
        
        .login-container {
            position: relative;
            padding-top: 60px;
        }
        
        @media (max-width: 480px) {
            .back-button {
                top: 15px;
                left: 15px;
                padding: 8px 12px;
                font-size: 0.8rem;
            }
            
            .login-container {
                padding-top: 50px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Botón de regreso -->
        <a href="index.php" class="back-button, small-btn">
            ← Volver al Inicio
        </a>
        
        <div class="login-header">
            <h1>Sistema de Titulación</h1>
            <h2>Facultad de Ciencias Forestales y Ambientales</h2>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <div class="form-group">
                <label for="matricula">Matrícula:</label>
                <input type="text" id="matricula" name="matricula" required value="<?php echo $_POST['matricula'] ?? ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-primary">Iniciar Sesión</button>
        </form>

        <div class="login-footer">
            <p>¿Problemas para acceder? Contacta al administrador del sistema.</p>
        </div>
    </div>
</body>
</html>