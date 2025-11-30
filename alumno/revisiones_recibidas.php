<?php
// alumno/revisiones_recibidas.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'alumno') {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];

// Obtener revisiones con documentos revisados
$sql = "SELECT r.*, 
               u.nombre as revisor_nombre, u.apellido_paterno as revisor_apellido,
               cd.nombre as documento_nombre,
               (SELECT COUNT(*) FROM documentos_revisados dr WHERE dr.id_revision = r.id) as total_revisiones,
               (SELECT MAX(fecha_revision) FROM documentos_revisados dr WHERE dr.id_revision = r.id) as ultima_revision
        FROM revisiones r
        JOIN usuarios u ON r.id_revisor = u.id
        JOIN catalogo_documentos cd ON r.id_documento = cd.id
        WHERE r.id_usuario = :usuario_id
        ORDER BY r.fecha_asignada DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$revisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisiones Recibidas</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Revisiones Recibidas</h1>
            <p>Documentos revisados por tus asesores</p>
        </div>

        <?php if ($revisiones): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Documento</th>
                            <th>Revisor</th>
                            <th>Fecha Asignación</th>
                            <th>Revisiones</th>
                            <th>Última Revisión</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisiones as $revision): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($revision['documento_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($revision['revisor_nombre'] . ' ' . $revision['revisor_apellido']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($revision['fecha_asignada'])); ?></td>
                            <td>
                                <span class="stat-number"><?php echo $revision['total_revisiones']; ?></span>
                            </td>
                            <td>
                                <?php if ($revision['ultima_revision']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($revision['ultima_revision'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin revisiones</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($revision['total_revisiones'] > 0): ?>
                                    <a href="ver_revisiones.php?revision_id=<?php echo $revision['id']; ?>" 
                                       class="btn-small btn-primary">Ver Revisiones</a>
                                <?php else: ?>
                                    <span class="text-muted">Esperando revisión</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No tienes revisiones asignadas</h2>
                <p>Los revisores te notificarán cuando revisen tus documentos.</p>
                <a href="documentos.php" class="btn-primary">Ver Mis Documentos</a>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>