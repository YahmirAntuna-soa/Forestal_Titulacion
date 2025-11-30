<?php
// alumno/documentos.php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'alumno') {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['user_id'];
$estado_filter = $_GET['estado'] ?? '';
$mensaje = '';

// Procesar eliminación de documento
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['action'] == 'eliminar_documento') {
    $documento_id = $_POST['documento_id'];
    
    // Obtener información del documento
    $sql_doc = "SELECT nombre_documento FROM usuarios_documentos WHERE id = ? AND id_usuario = ?";
    $stmt_doc = $pdo->prepare($sql_doc);
    $stmt_doc->execute([$documento_id, $usuario_id]);
    $documento = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    
    if ($documento) {
        // Eliminar archivo físico
        $archivo_path = "../uploads/" . $documento['nombre_documento'];
        if (file_exists($archivo_path)) {
            unlink($archivo_path);
        }
        
        // Eliminar registro de la base de datos
        $sql_delete = "DELETE FROM usuarios_documentos WHERE id = ? AND id_usuario = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        
        if ($stmt_delete->execute([$documento_id, $usuario_id])) {
            $mensaje = "success:Documento eliminado correctamente";
        } else {
            $mensaje = "error:Error al eliminar documento";
        }
    } else {
        $mensaje = "error:Documento no encontrado";
    }
}

// Obtener documentos del alumno
$sql_documentos = "SELECT ud.*, cd.nombre as tipo_documento 
                   FROM usuarios_documentos ud 
                   JOIN catalogo_documentos cd ON ud.id_documento = cd.id 
                   WHERE ud.id_usuario = :usuario_id";
                   
if ($estado_filter) {
    $sql_documentos .= " AND ud.estado = :estado";
}

$sql_documentos .= " ORDER BY ud.fecha_subida DESC";

$stmt = $pdo->prepare($sql_documentos);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
if ($estado_filter) {
    $stmt->bindParam(':estado', $estado_filter);
}
$stmt->execute();
$documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tipos_documento = getDocumentosCatalogo($pdo);

// Procesar mensaje
if ($mensaje) {
    list($tipo, $texto) = explode(':', $mensaje, 2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Documentos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Mis Documentos</h1>
            <div class="page-actions">
                <a href="subir_documento.php" class="btn-primary">Subir Nuevo Documento</a>
            </div>
        </div>

        <?php if (isset($tipo) && isset($texto)): ?>
            <div class="<?php echo $tipo == 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $texto; ?>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="filters">
            <h3>Filtrar por estado:</h3>
            <div class="filter-buttons">
                <a href="?" class="btn-secondary <?php echo !$estado_filter ? 'active' : ''; ?>">Todos</a>
                <a href="?estado=pendiente" class="btn-secondary <?php echo $estado_filter == 'pendiente' ? 'active' : ''; ?>">Pendientes</a>
                <a href="?estado=aprobado" class="btn-secondary <?php echo $estado_filter == 'aprobado' ? 'active' : ''; ?>">Aprobados</a>
            </div>
        </div>

        <?php if ($documentos): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo de Documento</th>
                            <th>Nombre del Archivo</th>
                            <th>Fecha de Subida</th>
                            <th>Fecha de Entrega</th>
                            <th>Tamaño</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentos as $doc): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['tipo_documento']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($doc['nombre_original']); ?></strong>
                                <?php if ($doc['comentarios']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($doc['comentarios'], 0, 50) . '...'); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></td>
                            <td>
                                <?php if ($doc['fecha_entrega']): ?>
                                    <?php echo date('d/m/Y', strtotime($doc['fecha_entrega'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">No asignada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $tamano = $doc['tamano_archivo'] ?? 0;
                                echo formatFileSize($tamano);
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $doc['estado']; ?>">
                                    <?php echo ucfirst($doc['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="./uploads/<?php echo $doc['nombre_documento']; ?>" 
                                       download class="btn-small btn-primary">Descargar</a>
                                    
                                    <?php if ($doc['estado'] == 'pendiente' || $doc['estado'] == 'rechazado'): ?>
                                        <a href="subir_documento.php?reemplazar=<?php echo $doc['id']; ?>" 
                                           class="btn-small btn-secondary">Reemplazar</a>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="eliminar_documento">
                                        <input type="hidden" name="documento_id" value="<?php echo $doc['id']; ?>">
                                        <button type="submit" class="btn-small btn-danger" 
                                                onclick="return confirm('¿Está seguro de que desea eliminar este documento? Esta acción no se puede deshacer.')">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No hay documentos <?php echo $estado_filter ? 'con estado ' . $estado_filter : ''; ?></h2>
                <p>
                    <?php if ($estado_filter): ?>
                        No tienes documentos con el estado "<?php echo $estado_filter; ?>".
                    <?php else: ?>
                        Comienza subiendo tu primer documento para el proceso de titulación.
                    <?php endif; ?>
                </p>
                <a href="subir_documento.php" class="btn-primary">Subir Primer Documento</a>
            </div>
        <?php endif; ?>

        <!-- Estadísticas de documentos -->
        <div class="content-card">
            <h2>Resumen de Documentos</h2>
            <div class="stats-mini">
                <?php
                $total = count($documentos);
                $pendientes = count(array_filter($documentos, function($d) { return $d['estado'] == 'pendiente'; }));
                $revisados = count(array_filter($documentos, function($d) { return $d['estado'] == 'revisado'; }));
                $aprobados = count(array_filter($documentos, function($d) { return $d['estado'] == 'aprobado'; }));
                $rechazados = count(array_filter($documentos, function($d) { return $d['estado'] == 'rechazado'; }));
                ?>
                <div class="stat-mini">
                    <h3>Total</h3>
                    <p class="stat-number"><?php echo $total; ?></p>
                </div>
                <div class="stat-mini">
                    <h3>Pendientes</h3>
                    <p class="stat-number"><?php echo $pendientes; ?></p>
                </div>
                <div class="stat-mini">
                    <h3>Revisados</h3>
                    <p class="stat-number"><?php echo $revisados; ?></p>
                </div>
                <div class="stat-mini">
                    <h3>Aprobados</h3>
                    <p class="stat-number"><?php echo $aprobados; ?></p>
                </div>
                <div class="stat-mini">
                    <h3>Rechazados</h3>
                    <p class="stat-number"><?php echo $rechazados; ?></p>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>

    <style>
        .text-muted {
            color: var(--text-light);
            font-style: italic;
        }
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .stat-mini {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-mini h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        .stat-mini .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin: 0;
        }
    </style>
</body>
</html>