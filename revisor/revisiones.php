<?php
// revisor/revisiones.php
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
$alumno_id = $_GET['alumno_id'] ?? null;
$estado_filter = $_GET['estado'] ?? '';
$mensaje = '';

// Obtener revisiones asignadas
$sql_revisiones = "SELECT r.*, 
                          u.id as alumno_id, u.nombre as alumno_nombre, 
                          u.apellido_paterno as alumno_apellido, u.matricula as alumno_matricula,
                          cd.nombre as documento_nombre, cd.id as documento_id,
                          ud.nombre_documento as archivo_subido, ud.fecha_subida,
                          ud.estado as estado_documento
                   FROM revisiones r
                   JOIN usuarios u ON r.id_usuario = u.id
                   JOIN catalogo_documentos cd ON r.id_documento = cd.id
                   LEFT JOIN usuarios_documentos ud ON (u.id = ud.id_usuario AND cd.id = ud.id_documento)
                   WHERE r.id_revisor = :revisor_id";

$params = [':revisor_id' => $revisor_id];

if ($estado_filter) {
    $sql_revisiones .= " AND r.estado = :estado";
    $params[':estado'] = $estado_filter;
}

$sql_revisiones .= " ORDER BY r.fecha_asignada DESC";

$stmt = $pdo->prepare($sql_revisiones);
$stmt->execute($params);
$revisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener documentos del alumno específico si se seleccionó
$documentos_alumno = [];
if ($alumno_id) {
    $sql_documentos = "SELECT ud.*, cd.nombre as tipo_documento 
                       FROM usuarios_documentos ud 
                       JOIN catalogo_documentos cd ON ud.id_documento = cd.id 
                       WHERE ud.id_usuario = :alumno_id 
                       ORDER BY ud.fecha_subida DESC";
    $stmt = $pdo->prepare($sql_documentos);
    $stmt->bindParam(':alumno_id', $alumno_id, PDO::PARAM_INT);
    $stmt->execute();
    $documentos_alumno = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'cambiar_estado') {
        $revision_id = $_POST['revision_id'];
        $nuevo_estado = $_POST['nuevo_estado'];

        $sql = "UPDATE revisiones SET estado = :estado WHERE id = :id AND id_revisor = :revisor_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $revision_id, PDO::PARAM_INT);
        $stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $mensaje = "success:Estado de revisión actualizado correctamente";

            // Recargar revisiones
            $stmt = $pdo->prepare($sql_revisiones);
            $stmt->execute($params);
            $revisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $mensaje = "error:Error al actualizar estado";
        }
    } elseif ($_POST['action'] == 'enviar_retroalimentacion') {
        $revision_id = $_POST['revision_id'];
        $comentarios = trim($_POST['comentarios']);
        $estado_retro = $_POST['estado'];
        $alumno_id_msg = $_POST['alumno_id'];

        if (!empty($comentarios)) {
            // Guardar retroalimentación
            $sql_retro = "INSERT INTO retroalimentacion (id_revision, id_revisor, comentarios, estado) 
                         VALUES (:revision_id, :revisor_id, :comentarios, :estado)";
            $stmt_retro = $pdo->prepare($sql_retro);
            $stmt_retro->bindParam(':revision_id', $revision_id, PDO::PARAM_INT);
            $stmt_retro->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
            $stmt_retro->bindParam(':comentarios', $comentarios);
            $stmt_retro->bindParam(':estado', $estado_retro);

            if ($stmt_retro->execute()) {
                $mensaje = "success:Retroalimentación enviada correctamente";

                // Enviar mensaje automático al alumno
                $mensaje_auto = "He revisado tu documento. Estado: " . $estado_retro . ". " .
                    (strlen($comentarios) > 100 ? substr($comentarios, 0, 100) . "..." : $comentarios);
                enviarMensaje($pdo, $revisor_id, $alumno_id_msg, $mensaje_auto);

                // Marcar revisión como completada
                $sql_update = "UPDATE revisiones SET estado = 'completada', fecha_completada = NOW() 
                              WHERE id = :id";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->bindParam(':id', $revision_id, PDO::PARAM_INT);
                $stmt_update->execute();
            } else {
                $mensaje = "error:Error al enviar retroalimentación";
            }
        } else {
            $mensaje = "error:Por favor ingresa comentarios para la retroalimentación";
        }
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
    <title>Gestión de Revisiones</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1>Gestión de Revisiones</h1>
            <p>Documentos asignados para revisión</p>
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
                <a href="?estado=en_revision" class="btn-secondary <?php echo $estado_filter == 'en_revision' ? 'active' : ''; ?>">En Revisión</a>
                <a href="?estado=completada" class="btn-secondary <?php echo $estado_filter == 'completada' ? 'active' : ''; ?>">Completadas</a>
            </div>
        </div>

        <div class="table-container">
            <h2>Revisiones Asignadas</h2>
            <?php if ($revisiones): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Documento</th>
                            <th>Archivo</th>
                            <th>Fecha Asignada</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($revisiones as $revision): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($revision['alumno_nombre'] . ' ' . $revision['alumno_apellido']); ?></strong>
                                    <br><small>Matrícula: <?php echo htmlspecialchars($revision['alumno_matricula']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($revision['documento_nombre']); ?></td>
                                <td>
                                    <?php if ($revision['archivo_subido']): ?>
                                        <span class="status-badge status-completed">Disponible</span>
                                        <a href="../alumno/uploads/<?php echo $revision['archivo_subido']; ?>"
                                            download class="btn-small btn-secondary">Descargar</a>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($revision['fecha_asignada'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $revision['estado']; ?>">
                                        <?php echo ucfirst($revision['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($revision['archivo_subido']): ?>
                                            <a href="subir_revision.php?revision_id=<?php echo $revision['id']; ?>"
                                            class="btn-small btn-primary">Revisar</a>
                                        <?php endif; ?>
                                        <a href="chat.php?destino=<?php echo $revision['alumno_id']; ?>"
                                            class="btn-small btn-secondary">Chat</a>

                                        <!-- Cambiar estado -->
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="cambiar_estado">
                                            <input type="hidden" name="revision_id" value="<?php echo $revision['id']; ?>">
                                            <select name="nuevo_estado" onchange="this.form.submit()" class="status-select">
                                                <option value="pendiente" <?php echo $revision['estado'] == 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="en_revision" <?php echo $revision['estado'] == 'en_revision' ? 'selected' : ''; ?>>En Revisión</option>
                                                <option value="completada" <?php echo $revision['estado'] == 'completada' ? 'selected' : ''; ?>>Completada</option>
                                            </select>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No tienes revisiones asignadas</h3>
                    <p>El administrador te asignará revisiones cuando estén disponibles.</p>
                    <a href="../revisor/index.php" class="btn-primary">Volver al Dashboard</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($alumno_id && $documentos_alumno): ?>
            <div class="content-card">
                <h2>Documentos de <?php echo htmlspecialchars($revisiones[0]['alumno_nombre'] . ' ' . $revisiones[0]['alumno_apellido']); ?></h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo de Documento</th>
                            <th>Archivo</th>
                            <th>Fecha de Subida</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documentos_alumno as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['tipo_documento']); ?></td>
                                <td><?php echo htmlspecialchars($doc['nombre_original']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($doc['fecha_subida'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $doc['estado']; ?>">
                                        <?php echo ucfirst($doc['estado']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="../alumno/uploads/<?php echo $doc['nombre_documento']; ?>"
                                        download class="btn-small btn-primary">Descargar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>


    <?php include '../includes/footer.php'; ?>

    <style>
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }

        .close-modal:hover {
            color: var(--text-color);
        }

        .status-select {
            padding: 0.25rem 0.5rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .message-preview {
            color: var(--text-light);
            font-style: italic;
        }
    </style>

    <script>
        function cerrarModal() {
            document.getElementById('modalRetroalimentacion').style.display = 'none';
            document.getElementById('formRetroalimentacion').reset();
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const modal = document.getElementById('modalRetroalimentacion');
            if (event.target === modal) {
                cerrarModal();
            }
        }
    </script>
</body>

</html>