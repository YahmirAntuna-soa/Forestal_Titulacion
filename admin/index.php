<?php
// admin/index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'admin') {
    header("Location: ../login.php");
    exit();
}

$estadisticas = getEstadisticasAdmin($pdo);

// Obtener actividad reciente
$sql_actividad = "SELECT 
    'documento' as tipo, ud.fecha_subida as fecha, 
    CONCAT(u.nombre, ' ', u.apellido_paterno) as usuario,
    CONCAT('Subió: ', cd.nombre) as accion
    FROM usuarios_documentos ud
    JOIN usuarios u ON ud.id_usuario = u.id
    JOIN catalogo_documentos cd ON ud.id_documento = cd.id
    UNION ALL
    SELECT 'mensaje' as tipo, c.fecha_enviado as fecha,
    CONCAT(u.nombre, ' ', u.apellido_paterno) as usuario,
    'Envió un mensaje' as accion
    FROM chat c
    JOIN usuarios u ON c.id_usuario_fuente = u.id
    ORDER BY fecha DESC
    LIMIT 10";

$actividad_reciente = $pdo->query($sql_actividad)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Panel de Administración</h1>
            <p>Gestión completa del sistema de titulación</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Alumnos</h3>
                <p class="stat-number"><?php echo $estadisticas['total_alumnos']; ?></p>
                <a href="usuarios.php?rol=alumno" class="stat-link">Ver alumnos</a>
            </div>
            
            <div class="stat-card">
                <h3>Total Revisores</h3>
                <p class="stat-number"><?php echo $estadisticas['total_revisores']; ?></p>
                <a href="usuarios.php?rol=revisor" class="stat-link">Ver revisores</a>
            </div>
            
            <div class="stat-card">
                <h3>Documentos Subidos</h3>
                <p class="stat-number"><?php echo $estadisticas['total_documentos']; ?></p>
                <a href="reportes.php" class="stat-link">Ver reportes</a>
            </div>
            
            <div class="stat-card">
                <h3>Revisiones Pendientes</h3>
                <p class="stat-number"><?php echo $estadisticas['revisiones_pendientes']; ?></p>
                <a href="asignaciones.php" class="stat-link">Gestionar</a>
            </div>

            <div class="stat-card">
                <h3>Mensajes Hoy</h3>
                <p class="stat-number"><?php echo $estadisticas['mensajes_hoy']; ?></p>
                <a href="../admin/reportes.php" class="stat-link">Ver actividad</a>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="content-card">
                <h2>Actividad Reciente</h2>
                <?php if ($actividad_reciente): ?>
                    <div class="activity-list">
                        <?php foreach ($actividad_reciente as $actividad): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php echo $actividad['tipo'] == 'documento' ? '📄' : '💬'; ?>
                                </div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($actividad['usuario']); ?></strong>
                                    <span><?php echo htmlspecialchars($actividad['accion']); ?></span>
                                    <small><?php echo date('d/m/Y H:i', strtotime($actividad['fecha'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No hay actividad reciente.</p>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <h2>Acciones Rápidas</h2>
                <div class="quick-actions-grid">
                    <a href="usuarios.php?action=add" class="action-card">
                        <div class="action-icon">👥</div>
                        <h3>Agregar Usuario</h3>
                        <p>Registrar nuevo alumno o revisor</p>
                    </a>
                    
                    <a href="asignaciones.php" class="action-card">
                        <div class="action-icon">📋</div>
                        <h3>Asignar Revisores</h3>
                        <p>Asignar revisores a alumnos</p>
                    </a>
                    
                    <a href="reportes.php" class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>Ver Reportes</h3>
                        <p>Estadísticas del sistema</p>
                    </a>
                    
                    <a href="../admin/usuarios.php" class="action-card">
                        <div class="action-icon">⚙️</div>
                        <h3>Gestionar Usuarios</h3>
                        <p>Administrar todos los usuarios</p>
                    </a>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>