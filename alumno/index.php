<?php
// alumno/index.php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'alumno') {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$usuario = getUsuarioById($pdo, $usuario_id);

// Obtener documentos del alumno
$sql_documentos = "SELECT ud.*, cd.nombre as tipo_documento 
                   FROM usuarios_documentos ud 
                   JOIN catalogo_documentos cd ON ud.id_documento = cd.id 
                   WHERE ud.id_usuario = :usuario_id 
                   ORDER BY ud.fecha_subida DESC
                   LIMIT 5";
$stmt = $pdo->prepare($sql_documentos);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener revisiones asignadas
$sql_revisiones = "SELECT r.*, cd.nombre as documento_nombre, 
                          u.nombre as revisor_nombre, u.apellido_paterno as revisor_apellido
                   FROM revisiones r
                   JOIN catalogo_documentos cd ON r.id_documento = cd.id
                   JOIN usuarios u ON r.id_revisor = u.id
                   WHERE r.id_usuario = :usuario_id
                   ORDER BY r.fecha_asignada DESC";
$stmt = $pdo->prepare($sql_revisiones);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$revisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener mensajes recientes
$sql_mensajes = "SELECT c.*, u.nombre as remitente_nombre, u.apellido_paterno as remitente_apellido
                 FROM chat c
                 JOIN usuarios u ON c.id_usuario_fuente = u.id
                 WHERE c.id_usuario_destino = :usuario_id
                 ORDER BY c.fecha_enviado DESC
                 LIMIT 5";
$stmt = $pdo->prepare($sql_mensajes);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$mensajes_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$total_documentos = $pdo->prepare("SELECT COUNT(*) FROM usuarios_documentos WHERE id_usuario = ?");
$total_documentos->execute([$usuario_id]);
$total_documentos = $total_documentos->fetchColumn();

$documentos_aprobados = $pdo->prepare("SELECT COUNT(*) FROM usuarios_documentos WHERE id_usuario = ? AND estado = 'aprobado'");
$documentos_aprobados->execute([$usuario_id]);
$documentos_aprobados = $documentos_aprobados->fetchColumn();

$revisiones_pendientes = $pdo->prepare("SELECT COUNT(*) FROM revisiones WHERE id_usuario = ? AND estado IN ('pendiente', 'en_revision')");
$revisiones_pendientes->execute([$usuario_id]);
$revisiones_pendientes = $revisiones_pendientes->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alumno</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Bienvenido, <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido_paterno']); ?></h1>
            <p>Matrícula: <?php echo htmlspecialchars($usuario['matricula']); ?></p>
            <p>Carrera: <?php echo htmlspecialchars($usuario['carrera_nombre']); ?> | 
               Unidad Académica: <?php echo htmlspecialchars($usuario['unidad_nombre']); ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Documentos Subidos</h3>
                <p class="stat-number"><?php echo $total_documentos; ?></p>
                <a href="documentos.php" class="stat-link">Ver documentos</a>
            </div>
            
            <div class="stat-card">
                <h3>Documentos Aprobados</h3>
                <p class="stat-number"><?php echo $documentos_aprobados; ?></p>
                <a href="documentos.php?estado=aprobado" class="stat-link">Ver aprobados</a>
            </div>
            
            <div class="stat-card">
                <h3>Revisiones Pendientes</h3>
                <p class="stat-number"><?php echo $revisiones_pendientes; ?></p>
                <a href="documentos.php" class="stat-link">Ver revisiones</a>
            </div>
            
            <div class="stat-card">
                <h3>Revisores Asignados</h3>
                <p class="stat-number"><?php echo count($revisiones); ?></p>
                <a href="chat.php" class="stat-link">Contactar</a>
            </div>

<div class="stat-card">
    <h3>Revisiones Recibidas</h3>
    <p class="stat-number"><?php 
        $sql_revisiones = "SELECT COUNT(DISTINCT r.id) as total 
                          FROM revisiones r 
                          JOIN documentos_revisados dr ON r.id = dr.id_revision 
                          WHERE r.id_usuario = ?";
        $stmt = $pdo->prepare($sql_revisiones);
        $stmt->execute([$usuario_id]);
        echo $stmt->fetchColumn();
    ?></p>
    <a href="revisiones_recibidas.php" class="stat-link">Ver revisiones</a>
</div>

        </div>

        <div class="content-grid">
            <div class="content-card">
                <h2>Mis Documentos Recientes</h2>
                <?php if ($documentos): ?>
                    <div class="activity-list">
                        <?php foreach ($documentos as $doc): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <?php 
                                    switch($doc['estado']) {
                                        case 'aprobado': echo '✅'; break;
                                        case 'revisado': echo '📝'; break;
                                        case 'rechazado': echo '❌'; break;
                                        default: echo '📄';
                                    }
                                    ?>
                                </div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($doc['tipo_documento']); ?></strong>
                                    <span><?php echo htmlspecialchars($doc['nombre_original']); ?></span>
                                    <small>
                                        Subido: <?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?>
                                        • 
                                        <span class="status-badge status-<?php echo $doc['estado']; ?>">
                                            <?php echo ucfirst($doc['estado']); ?>
                                        </span>
                                    </small>
                                </div>
                                <div class="activity-actions">
                                    <a href="./uploads/<?php echo $doc['nombre_documento']; ?>" 
                                       download class="btn-small btn-secondary">Descargar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-actions">
                        <a href="documentos.php" class="btn-primary">Ver todos los documentos</a>
                    </div>
                <?php else: ?>
                    <p>No hay documentos subidos aún.</p>
                    <a href="subir_documento.php" class="btn-primary">Subir primer documento</a>
                <?php endif; ?>
            </div>

            <div class="content-card">
                <h2>Revisores Asignados</h2>
                <?php if ($revisiones): ?>
                    <div class="activity-list">
                        <?php foreach ($revisiones as $revision): ?>
                            <div class="activity-item">
                                <div class="activity-icon">👨‍🏫</div>
                                <div class="activity-content">
                                    <strong><?php echo htmlspecialchars($revision['revisor_nombre'] . ' ' . $revision['revisor_apellido']); ?></strong>
                                    <span><?php echo htmlspecialchars($revision['documento_nombre']); ?></span>
                                    <small>
                                        Estado: 
                                        <span class="status-badge status-<?php echo $revision['estado']; ?>">
                                            <?php echo ucfirst($revision['estado']); ?>
                                        </span>
                                        • <?php echo date('d/m/Y', strtotime($revision['fecha_asignada'])); ?>
                                    </small>
                                </div>
                                <div class="activity-actions">
                                    <a href="chat.php?destino=<?php echo $revision['id_revisor']; ?>" 
                                       class="btn-small btn-primary">Contactar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No tienes revisores asignados aún.</p>
                    <p><small>El administrador te asignará revisores para tu proceso de titulación.</small></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($mensajes_recientes): ?>
        <div class="content-card">
            <h2>Mensajes Recientes</h2>
            <div class="activity-list">
                <?php foreach ($mensajes_recientes as $mensaje): ?>
                    <div class="activity-item">
                        <div class="activity-icon">💬</div>
                        <div class="activity-content">
                            <strong><?php echo htmlspecialchars($mensaje['remitente_nombre'] . ' ' . $mensaje['remitente_apellido']); ?></strong>
                            <span class="message-preview"><?php echo htmlspecialchars(substr($mensaje['mensaje'], 0, 60) . (strlen($mensaje['mensaje']) > 60 ? '...' : '')); ?></span>
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
        </div>
        <?php endif; ?>

        <div class="quick-actions">
            <h2>Acciones Rápidas</h2>
            <div class="action-buttons">
                <a href="subir_documento.php" class="btn-primary">Subir Documento</a>
                <a href="documentos.php" class="btn-secondary">Ver Mis Documentos</a>
                <a href="chat.php" class="btn-secondary">Chat con Revisores</a>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>