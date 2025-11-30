<?php
// alumno/subir_documento.php
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
$tipos_documento = getDocumentosCatalogo($pdo);
$reemplazar_id = $_GET['reemplazar'] ?? null;
$mensaje = '';

// Si es reemplazo, obtener información del documento a reemplazar
$documento_reemplazar = null;
if ($reemplazar_id) {
    $sql_doc = "SELECT * FROM usuarios_documentos WHERE id = ? AND id_usuario = ?";
    $stmt_doc = $pdo->prepare($sql_doc);
    $stmt_doc->execute([$reemplazar_id, $usuario_id]);
    $documento_reemplazar = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    
    if (!$documento_reemplazar) {
        header("Location: documentos.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo_documento = $_POST['tipo_documento'];
    $fecha_entrega = $_POST['fecha_entrega'] ?: null;
    $accion = $_POST['accion']; // 'subir' o 'reemplazar'
    
    // Validar y procesar archivo
    if (isset($_FILES['documento']) && $_FILES['documento']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['documento']['name'];
        $file_tmp = $_FILES['documento']['tmp_name'];
        $file_size = $_FILES['documento']['size'];
        $file_type = $_FILES['documento']['type'];
        
        // Validar tipo de archivo
        $allowed_extensions = ['doc', 'docx'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            $mensaje = "error:Solo se permiten archivos WORD (.doc, .docx)";
        } elseif ($file_size > MAX_FILE_SIZE) {
            $mensaje = "error:El archivo es demasiado grande. Máximo " . formatFileSize(MAX_FILE_SIZE);
        } else {
            // Generar nombre único
            $new_file_name = $usuario_id . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_name);
            $upload_path = UPLOAD_PATH . $new_file_name;
            
            // DEBUG: Mostrar información de subida
            error_log("Intentando subir archivo: $upload_path");
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                try {
                    if ($accion == 'reemplazar' && $reemplazar_id) {
                        // Eliminar archivo anterior
                        $archivo_anterior = UPLOAD_PATH . $documento_reemplazar['nombre_documento'];
                        if (file_exists($archivo_anterior)) {
                            unlink($archivo_anterior);
                        }
                        
                        // Actualizar registro existente
                        $sql = "UPDATE usuarios_documentos 
                                SET id_documento = :documento_id, 
                                    nombre_documento = :nombre_documento,
                                    nombre_original = :nombre_original,
                                    fecha_subida = NOW(),
                                    tamano_archivo = :tamano,
                                    estado = 'pendiente',
                                    comentarios = NULL
                                WHERE id = :id AND id_usuario = :usuario_id";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':documento_id' => $tipo_documento,
                            ':nombre_documento' => $new_file_name,
                            ':nombre_original' => $file_name,
                            ':tamano' => $file_size,
                            ':id' => $reemplazar_id,
                            ':usuario_id' => $usuario_id
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            $mensaje = "success:Documento reemplazado correctamente";
                            header("Location: documentos.php");
                            exit();
                        } else {
                            $mensaje = "error:Error al actualizar en la base de datos";
                            unlink($upload_path);
                        }
                    } else {
                        // Insertar nuevo registro
                        $sql = "INSERT INTO usuarios_documentos (id_usuario, id_documento, nombre_documento, nombre_original, fecha_entrega, fecha_subida, tamano_archivo) 
                                VALUES (:usuario_id, :documento_id, :nombre_documento, :nombre_original, :fecha_entrega, NOW(), :tamano_archivo)";
                        
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([
                            ':usuario_id' => $usuario_id,
                            ':documento_id' => $tipo_documento,
                            ':nombre_documento' => $new_file_name,
                            ':nombre_original' => $file_name,
                            ':fecha_entrega' => $fecha_entrega,
                            ':tamano_archivo' => $file_size
                        ]);
                        
                        if ($stmt->rowCount() > 0) {
                            $mensaje = "success:Documento subido correctamente";
                            header("Location: documentos.php");
                            exit();
                        } else {
                            $mensaje = "error:Error al guardar en la base de datos";
                            unlink($upload_path);
                        }
                    }
                } catch (PDOException $e) {
                    $mensaje = "error:Error en la base de datos: " . $e->getMessage();
                    unlink($upload_path);
                }
            } else {
                $mensaje = "error:Error al subir el archivo al servidor. Verifica los permisos de la carpeta uploads/";
                error_log("Error moviendo archivo: " . error_get_last()['message']);
            }
        }
    } else {
        $error_code = $_FILES['documento']['error'] ?? -1;
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo solo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir en el disco',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
        ];
        $mensaje = "error:" . ($error_messages[$error_code] ?? 'Error desconocido al subir archivo');
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
    <title><?php echo $reemplazar_id ? 'Reemplazar Documento' : 'Subir Documento'; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1><?php echo $reemplazar_id ? 'Reemplazar Documento' : 'Subir Documento'; ?></h1>
            <a href="documentos.php" class="btn-secondary">Volver a Documentos</a>
        </div>

        <?php if (isset($tipo) && isset($texto)): ?>
            <div class="<?php echo $tipo == 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $texto; ?>
            </div>
        <?php endif; ?>

        <?php if ($reemplazar_id && $documento_reemplazar): ?>
            <div class="info-message">
                <h3>Reemplazando documento:</h3>
                <p><strong><?php echo htmlspecialchars($documento_reemplazar['nombre_original']); ?></strong></p>
                <p>Tipo: <?php 
                    $tipo_actual = array_filter($tipos_documento, function($t) use ($documento_reemplazar) {
                        return $t['id'] == $documento_reemplazar['id_documento'];
                    });
                    if ($tipo_actual) {
                        echo htmlspecialchars(current($tipo_actual)['nombre']);
                    }
                ?></p>
                <p>Subido: <?php echo date('d/m/Y H:i', strtotime($documento_reemplazar['fecha_subida'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="<?php echo $reemplazar_id ? 'reemplazar' : 'subir'; ?>">
                
                <div class="form-group">
                    <label for="tipo_documento">Tipo de Documento:</label>
                    <select id="tipo_documento" name="tipo_documento" required>
                        <option value="">Seleccionar tipo de documento</option>
                        <?php foreach ($tipos_documento as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" 
                                <?php echo ($reemplazar_id && $documento_reemplazar['id_documento'] == $tipo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="fecha_entrega">Fecha de Entrega (Opcional):</label>
                    <input type="date" id="fecha_entrega" name="fecha_entrega" 
                           value="<?php echo $reemplazar_id && $documento_reemplazar['fecha_entrega'] ? date('Y-m-d', strtotime($documento_reemplazar['fecha_entrega'])) : date('Y-m-d'); ?>"
                            min="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="documento">Seleccionar Archivo:</label>
                    <input type="file" id="documento" name="documento" 
                           accept=".doc,.docx" required>
                    <small>
                        Formatos permitidos: WORD (DOC, DOCX). 
                        Tamaño máximo: <?php echo formatFileSize(MAX_FILE_SIZE); ?>
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <?php echo $reemplazar_id ? 'Reemplazar Documento' : 'Subir Documento'; ?>
                    </button>
                    <a href="documentos.php" class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        <div class="info-card">
            <h3>Recomendaciones para subir documentos:</h3>
            <ul>
                <li>Asegúrate de que el documento esté completo antes de subirlo</li>
                <li>Verifica que el formato sea correcto (WORD)</li>
                <li>Usa un nombre descriptivo para el archivo</li>
                <li>Si es un reemplazo, el documento anterior será eliminado permanentemente</li>
                <li>Después de subir, el documento quedará en estado "pendiente" hasta que sea revisado</li>
            </ul>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>

    <style>
        .info-message {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-message h3 {
            margin-top: 0;
            color: #1976d2;
        }
        .info-card {
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .info-card h3 {
            margin-top: 0;
            color: var(--primary-color);
        }
        .info-card ul {
            margin-bottom: 0;
        }
        .info-card li {
            margin-bottom: 0.5rem;
        }
    </style>

    <script>
        // Validación de archivo en el cliente
        document.getElementById('documento').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const maxSize = <?php echo MAX_FILE_SIZE; ?>;
            
            if (file) {
                // Validar tamaño
                if (file.size > maxSize) {
                    alert('El archivo es demasiado grande. Tamaño máximo: <?php echo formatFileSize(MAX_FILE_SIZE); ?>');
                    e.target.value = '';
                    return;
                }
                
                // Validar extensión
                const allowedExtensions = ['.pdf', '.doc', '.docx', '.txt'];
                const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                
                if (!allowedExtensions.includes(fileExtension)) {
                    alert('Formato de archivo no permitido. Solo se aceptan: PDF, DOC, DOCX, TXT');
                    e.target.value = '';
                    return;
                }
            }
        });
    </script>
</body>
</html>