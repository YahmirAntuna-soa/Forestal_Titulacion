<?php
// alumno/ver_revisiones.php
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
$revision_id = $_GET['revision_id'] ?? null;

// Verificar que la revisión pertenece al alumno
if ($revision_id) {
    $sql = "SELECT r.*, 
                   u.nombre as revisor_nombre, u.apellido_paterno as revisor_apellido,
                   cd.nombre as documento_nombre
            FROM revisiones r
            JOIN usuarios u ON r.id_revisor = u.id
            JOIN catalogo_documentos cd ON r.id_documento = cd.id
            WHERE r.id = :revision_id AND r.id_usuario = :usuario_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':revision_id', $revision_id, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$revision) {
    header("Location: revisiones_recibidas.php");
    exit();
}

// Obtener documentos revisados
$documentos_revisados = getDocumentosRevisados($pdo, $revision_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revisiones del Documento</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Revisiones del Documento</h1>
            <a href="revisiones_recibidas.php" class="btn-secondary">Volver a Revisiones</a>
        </div>

        <div class="content-card">
            <h2>Información de la Revisión</h2>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Documento:</strong>
                    <?php echo htmlspecialchars($revision['documento_nombre']); ?>
                </div>
                <div class="info-item">
                    <strong>Revisor:</strong>
                    <?php echo htmlspecialchars($revision['revisor_nombre'] . ' ' . $revision['revisor_apellido']); ?>
                </div>
                <div class="info-item">
                    <strong>Fecha de asignación:</strong>
                    <?php echo date('d/m/Y', strtotime($revision['fecha_asignada'])); ?>
                </div>
                <div class="info-item">
                    <strong>Total de revisiones:</strong>
                    <?php echo count($documentos_revisados); ?>
                </div>
            </div>
        </div>

        <?php if ($documentos_revisados): ?>
            <div class="revisiones-list">
                <h2>Historial de Revisiones</h2>
                <?php foreach ($documentos_revisados as $index => $doc_revisado): ?>
                    <div class="revision-card">
                        <div class="revision-header">
                            <h3>Revisión #<?php echo count($documentos_revisados) - $index; ?></h3>
                            <div class="revision-meta">
                                <span class="status-badge status-<?php echo $doc_revisado['estado']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc_revisado['estado'])); ?>
                                </span>
                                <span class="revision-date">
                                    <?php echo date('d/m/Y H:i', strtotime($doc_revisado['fecha_revision'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="revision-content">
                            <div class="revision-document">
                                <h4>Documento Revisado:</h4>
                                <p>
                                    <strong>Archivo:</strong> <?php echo htmlspecialchars($doc_revisado['nombre_original']); ?>
                                    <br>
                                    <strong>Tamaño:</strong> <?php echo formatFileSize($doc_revisado['tamano_archivo']); ?>
                                </p>
                                <a href="../uploads/<?php echo $doc_revisado['nombre_documento']; ?>" 
                                   download class="btn-primary">Descargar Documento Revisado</a>
                            </div>
                            
                            <div class="revision-comments">
                                <h4>Comentarios del Revisor:</h4>
                                <div class="comments-box">
                                    <?php echo nl2br(htmlspecialchars($doc_revisado['comentarios'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($doc_revisado['estado'] != 'aprobado'): ?>
                                <div class="revision-actions">
                                    <a href="subir_documento.php?reemplazar=<?php echo $revision['id_documento']; ?>" 
                                       class="btn-primary">Subir Versión Corregida</a>
                                    <a href="chat.php?destino=<?php echo $revision['id_revisor']; ?>" 
                                       class="btn-secondary">Consultar con Revisor</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>No hay revisiones disponibles</h3>
                <p>El revisor aún no ha subido revisiones para este documento.</p>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>

    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .revisiones-list {
            margin-top: 2rem;
        }
        
        .revision-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .revision-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .revision-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .revision-date {
            color: var(--text-light);
            font-size: 0.9rem;
        }
        
        .revision-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .revision-document, .revision-comments {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .comments-box {
            background: white;
            padding: 1rem;
            border-radius: 4px;
            border: 1px solid var(--border-color);
            min-height: 150px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .revision-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        @media (max-width: 768px) {
            .revision-content {
                grid-template-columns: 1fr;
            }
            
            .revision-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .revision-actions {
                flex-direction: column;
            }
        }
    </style>
</body>
</html>