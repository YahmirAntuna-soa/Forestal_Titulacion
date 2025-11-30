<?php
// revisor/index.php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'revisor') {
    header("Location: ../login.php");
    exit();
}

$revisor_id = $_SESSION['user_id'];
$usuario = getUsuarioById($pdo, $revisor_id);

// Obtener estadísticas del revisor
$sql_estadisticas = "SELECT 
    COUNT(*) as total_revisiones,
    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
    COUNT(CASE WHEN estado = 'en_revision' THEN 1 END) as en_revision,
    COUNT(CASE WHEN estado = 'completada' THEN 1 END) as completadas
    FROM revisiones 
    WHERE id_revisor = :revisor_id";

$stmt = $pdo->prepare($sql_estadisticas);
$stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
$stmt->execute();
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener revisiones recientes
$sql_revisiones = "SELECT r.*, 
                          u.nombre as alumno_nombre, u.apellido_paterno as alumno_apellido,
                          u.matricula as alumno_matricula,
                          cd.nombre as documento_nombre,
                          ud.nombre_documento as archivo_subido
                   FROM revisiones r
                   JOIN usuarios u ON r.id_usuario = u.id
                   JOIN catalogo_documentos cd ON r.id_documento = cd.id
                   LEFT JOIN usuarios_documentos ud ON (u.id = ud.id_usuario AND cd.id = ud.id_documento)
                   WHERE r.id_revisor = :revisor_id
                   ORDER BY r.fecha_asignada DESC
                   LIMIT 5";

$stmt = $pdo->prepare($sql_revisiones);
$stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
$stmt->execute();
$revisiones_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener mensajes recientes
$sql_mensajes = "SELECT c.*, u.nombre as remitente_nombre, u.apellido_paterno as remitente_apellido
                 FROM chat c
                 JOIN usuarios u ON c.id_usuario_fuente = u.id
                 WHERE c.id_usuario_destino = :revisor_id
                 ORDER BY c.fecha_enviado DESC
                 LIMIT 5";
$stmt = $pdo->prepare($sql_mensajes);
$stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
$stmt->execute();
$mensajes_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Revisor</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Bienvenido, <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido_paterno']); ?></h1>
            <p>Panel del Revisor - Gestión de revisiones asignadas</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Revisiones</h3>
                <p class="stat-number"><?php echo $estadisticas['total_revisiones']; ?></p>
                <a href="revisiones.php" class="stat-link">Ver todas</a>
            </div>
            
            <div class="stat-card">
                <h3>Pendientes</h3>
                <p class="stat-number"><?php echo $estadisticas['pendientes']; ?></p>
                <a href="revisiones.php?estado=pendiente" class="stat-link">Revisar</a>
            </div>
            
            <div class="stat-card">
                <h3>En Revisión</h3>
                <p class="stat-number"><?php echo $estadisticas['en_revision']; ?></p>
                <a href="revisiones.php?estado=en_revision" class="stat-link">Continuar</a>
            </div>
            
            <div class="stat-card">
                <h3>Completadas</h3>
                <p class="stat-number"><?php echo $estadisticas['completadas']; ?></p>
                <a href="revisiones.php?estado=completada" class="stat-link">Ver historial</a>
            </div>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h2>Revisiones Recientes</h2>
                <?php if ($revisiones_recientes): ?>
                    <div class="activity-list">
                        <?php foreach ($revisiones_recientes as $revision): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php echo $revision['archivo_subido'] ? '📄' : '⏳'; ?>
                                </div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($revision['alumno_nombre'] . ' ' . $revision['alumno_apellido']); ?></strong>
                                    <span><?php echo htmlspecialchars($revision['documento_nombre']); ?></span>
                                    <small>
                                        Estado: 
                                        <span class="status-badge status-<?php echo $revision['estado']; ?>">
                                            <?php echo $revision['estado']; ?>
                                        </span>
                                        • <?php echo date('d/m/Y', strtotime($revision['fecha_asignada'])); ?>
                                    </small>
                                </div>
                                <div class="activity-actions">
                                    <?php if ($revision['archivo_subido']): ?>
                                        <a href="../alumno/uploads/<?php echo $revision['archivo_subido']; ?>" 
                                           download class="btn-small btn-secondary">Descargar</a>
                                    <?php endif; ?>
                                    <a href="revisiones.php?alumno_id=<?php echo $revision['id_usuario']; ?>" 
                                       class="btn-small btn-primary">Revisar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-actions">
                        <a href="revisiones.php" class="btn-primary">Ver todas las revisiones</a>
                    </div>
                <?php else: ?>
                    <p>No tienes revisiones asignadas actualmente.</p>
                    <a href="revisiones.php" class="btn-primary">Ver revisiones</a>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <h2>Mensajes Recientes</h2>
                <?php if ($mensajes_recientes): ?>
                    <div class="activity-list">
                        <?php foreach ($mensajes_recientes as $mensaje): ?>
                            <div class="activity-item">
                                <div class="activity-icon">💬</div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($mensaje['remitente_nombre'] . ' ' . $mensaje['remitente_apellido']); ?></strong>
                                    <span class="message-preview"><?php echo htmlspecialchars(substr($mensaje['mensaje'], 0, 50) . '...'); ?></span>
                                    <small><?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_enviado'])); ?></small>
                                </div>
                                <div class="activity-actions">
                                    <a href="chat.php?destino=<?php echo $mensaje['id_usuario_fuente']; ?>" 
                                       class="btn-small btn-secondary">Responder</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-actions">
                        <a href="chat.php" class="btn-primary">Ver todos los mensajes</a>
                    </div>
                <?php else: ?>
                    <p>No tienes mensajes nuevos.</p>
                    <a href="chat.php" class="btn-primary">Ir al chat</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="quick-actions">
            <h2>Acciones Rápidas</h2>
            <div class="action-buttons">
                <a href="revisiones.php" class="btn-primary">Gestionar Revisiones</a>
                <a href="chat.php" class="btn-secondary">Chat con Alumnos</a>
                <a href="../logout.php" class="btn-secondary">Cerrar Sesión</a>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>