<?php
// revisor/subir_revision.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'revisor') {
    header("Location: ../login.php");
    exit();
}

$revisor_id = $_SESSION['user_id'];
$revision_id = $_GET['revision_id'] ?? null;
$mensaje = '';

// Obtener información de la revisión
$revision = null;
if ($revision_id) {
    $sql = "SELECT r.*, 
                   u.id as alumno_id, u.nombre as alumno_nombre, u.apellido_paterno as alumno_apellido,
                   u.matricula as alumno_matricula, u.email as alumno_email,
                   cd.nombre as documento_nombre
            FROM revisiones r
            JOIN usuarios u ON r.id_usuario = u.id
            JOIN catalogo_documentos cd ON r.id_documento = cd.id
            WHERE r.id = :revision_id AND r.id_revisor = :revisor_id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':revision_id', $revision_id, PDO::PARAM_INT);
    $stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
    $stmt->execute();
    $revision = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$revision) {
    header("Location: revisiones.php");
    exit();
}

// Obtener documentos revisados anteriores
$documentos_revisados = getDocumentosRevisados($pdo, $revision_id);

// Procesar envío de documento revisado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'subir_revision') {
    $comentarios = trim($_POST['comentarios']);
    $estado = $_POST['estado'];
    
    // Validar y procesar archivo
    if (isset($_FILES['documento_revisado']) && $_FILES['documento_revisado']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['documento_revisado']['name'];
        $file_tmp = $_FILES['documento_revisado']['tmp_name'];
        $file_size = $_FILES['documento_revisado']['size'];
        
        // Validar tipo de archivo
        $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $mensaje = "error:Solo se permiten archivos PDF, DOC, DOCX y TXT";
        } elseif ($file_size > MAX_FILE_SIZE) {
            $mensaje = "error:El archivo es demasiado grande. Máximo " . formatFileSize(MAX_FILE_SIZE);
        } else {
            // Generar nombre único
            $new_file_name = 'revisado_' . $revision_id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
            $upload_path = '../uploads/' . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                try {
                    // Iniciar transacción
                    $pdo->beginTransaction();
                    
                    // 1. Guardar documento revisado
                    $sql_doc = "INSERT INTO documentos_revisados 
                               (id_revision, id_revisor, nombre_documento, nombre_original, 
                                comentarios, tamano_archivo, estado) 
                               VALUES (:revision_id, :revisor_id, :nombre_documento, :nombre_original, 
                                       :comentarios, :tamano, :estado)";
                    
                    $stmt_doc = $pdo->prepare($sql_doc);
                    $stmt_doc->execute([
                        ':revision_id' => $revision_id,
                        ':revisor_id' => $revisor_id,
                        ':nombre_documento' => $new_file_name,
                        ':nombre_original' => $file_name,
                        ':comentarios' => $comentarios,
                        ':tamano' => $file_size,
                        ':estado' => $estado
                    ]);
                    
                    // 2. Actualizar contador de intentos en la revisión
                    $sql_update = "UPDATE revisiones 
                                  SET intentos = intentos + 1, 
                                      ultima_revision = NOW(),
                                      estado = 'completada'
                                  WHERE id = :revision_id";
                    
                    $stmt_update = $pdo->prepare($sql_update);
                    $stmt_update->bindParam(':revision_id', $revision_id, PDO::PARAM_INT);
                    $stmt_update->execute();
                    
                    // 3. Enviar notificación al alumno
                    $mensaje_alumno = "El revisor " . $_SESSION['user_name'] . " ha revisado tu documento '" . 
                                     $revision['documento_nombre'] . "'. Estado: " . $estado . 
                                     ". Puedes descargar el documento revisado desde tu panel.";
                    
                    enviarMensaje($pdo, $revisor_id, $revision['alumno_id'], $mensaje_alumno);
                    
                    // 4. Actualizar estado del documento original si es aprobado
                    if ($estado == 'aprobado') {
                        $sql_doc_original = "UPDATE usuarios_documentos 
                                            SET estado = 'aprobado',
                                                comentarios = :comentarios
                                            WHERE id_usuario = :alumno_id 
                                            AND id_documento = :documento_id";
                        
                        $stmt_doc_original = $pdo->prepare($sql_doc_original);
                        $stmt_doc_original->execute([
                            ':comentarios' => $comentarios,
                            ':alumno_id' => $revision['alumno_id'],
                            ':documento_id' => $revision['id_documento']
                        ]);
                    }
                    
                    $pdo->commit();
                    
                    $mensaje = "success:Documento revisado subido correctamente. El alumno ha sido notificado.";
                    header("Location: revisiones.php");
                    exit();
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $mensaje = "error:Error al procesar la revisión: " . $e->getMessage();
                    // Eliminar archivo subido en caso de error
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            } else {
                $mensaje = "error:Error al subir el archivo al servidor";
            }
        }
    } else {
        $mensaje = "error:Por favor selecciona un archivo para la revisión";
    }
}

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
    <title>Subir Documento Revisado</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Subir Documento Revisado</h1>
            <a href="revisiones.php" class="btn-secondary">Volver a Revisiones</a>
        </div>

        <?php if (isset($tipo) && isset($texto)): ?>
            <div class="<?php echo $tipo == 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $texto; ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h2>Información de la Revisión</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <strong>Alumno:</strong>
                        <?php echo htmlspecialchars($revision['alumno_nombre'] . ' ' . $revision['alumno_apellido']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Matrícula:</strong>
                        <?php echo htmlspecialchars($revision['alumno_matricula']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Documento:</strong>
                        <?php echo htmlspecialchars($revision['documento_nombre']); ?>
                    </div>
                    <div class="info-item">
                        <strong>Intentos de revisión:</strong>
                        <?php echo $revision['intentos']; ?>
                    </div>
                </div>
            </div>

            <?php if ($documentos_revisados): ?>
            <div class="content-card">
                <h2>Revisiones Anteriores</h2>
                <div class="revisiones-anteriores">
                    <?php foreach ($documentos_revisados as $doc_revisado): ?>
                        <div class="revision-item">
                            <div class="revision-header">
                                <strong>Versión <?php echo date('d/m/Y H:i', strtotime($doc_revisado['fecha_revision'])); ?></strong>
                                <span class="status-badge status-<?php echo $doc_revisado['estado']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc_revisado['estado'])); ?>
                                </span>
                            </div>
                            <div class="revision-content">
                                <p><strong>Archivo:</strong> <?php echo htmlspecialchars($doc_revisado['nombre_original']); ?></p>
                                <?php if ($doc_revisado['comentarios']): ?>
                                    <p><strong>Comentarios:</strong> <?php echo htmlspecialchars($doc_revisado['comentarios']); ?></p>
                                <?php endif; ?>
                                <a href="../uploads/<?php echo $doc_revisado['nombre_documento']; ?>" 
                                   download class="btn-small btn-primary">Descargar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="form-container">
            <h2>Subir Nueva Revisión</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="subir_revision">
                
                <div class="form-group">
                    <label for="estado">Estado de la Revisión:</label>
                    <select id="estado" name="estado" required>
                        <option value="">Seleccionar estado</option>
                        <option value="correcciones_minimas">Correcciones Mínimas</option>
                        <option value="correcciones_sustanciales">Correcciones Sustanciales</option>
                        <option value="aprobado">Aprobado</option>
                    </select>
                    <small>
                        <strong>Correcciones Mínimas:</strong> Pequeños ajustes necesarios<br>
                        <strong>Correcciones Sustanciales:</strong> Cambios importantes requeridos<br>
                        <strong>Aprobado:</strong> Documento listo para siguiente fase
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="comentarios">Comentarios y Observaciones:</label>
                    <textarea id="comentarios" name="comentarios" rows="6" 
                              placeholder="Proporciona comentarios detallados sobre las correcciones realizadas, sugerencias y observaciones para el alumno..." 
                              required></textarea>
                    <small>Estos comentarios serán visibles para el alumno.</small>
                </div>
                
                <div class="form-group">
                    <label for="documento_revisado">Documento Revisado:</label>
                    <input type="file" id="documento_revisado" name="documento_revisado" 
                           accept=".pdf,.doc,.docx,.txt" required>
                    <small>
                        Sube el documento con tus correcciones y comentarios incorporados.<br>
                        Formatos permitidos: PDF, DOC, DOCX, TXT. Tamaño máximo: <?php echo formatFileSize(MAX_FILE_SIZE); ?>
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Subir Documento Revisado</button>
                    <a href="revisiones.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
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
        
        .revisiones-anteriores {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .revision-item {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
        }
        
        .revision-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .revision-content p {
            margin: 0.5rem 0;
        }
    </style>
</body>
</html>