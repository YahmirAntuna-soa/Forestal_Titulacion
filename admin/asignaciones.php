<?php
// admin/asignaciones.php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'admin') {
    header("Location: ../login.php");
    exit();
}

$alumnos = getAlumnos($pdo);
$revisores = getRevisores($pdo);
$documentos = getDocumentosCatalogo($pdo);
$mensaje = '';

// Procesar asignación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'asignar') {
        $alumno_id = $_POST['alumno_id'];
        $revisor_id = $_POST['revisor_id'];
        $documento_id = $_POST['documento_id'];
        
        try {
            // Verificar si ya existe una asignación similar
            $sql_check = "SELECT id FROM revisiones 
                         WHERE id_usuario = :alumno_id 
                         AND id_revisor = :revisor_id 
                         AND id_documento = :documento_id 
                         AND estado IN ('pendiente', 'en_revision')";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->bindParam(':alumno_id', $alumno_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
            $stmt_check->bindParam(':documento_id', $documento_id, PDO::PARAM_INT);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                $mensaje = "warning:Ya existe una asignación activa para este alumno, revisor y documento";
            } else {
                $sql = "INSERT INTO revisiones (id_usuario, id_documento, fecha_asignada, id_revisor) 
                        VALUES (:alumno_id, :documento_id, NOW(), :revisor_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':alumno_id', $alumno_id, PDO::PARAM_INT);
                $stmt->bindParam(':documento_id', $documento_id, PDO::PARAM_INT);
                $stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
                
                if ($stmt->execute()) {
                    $mensaje = "success:Revisor asignado correctamente";
                    
                    // Crear notificación para el alumno
                    $alumno = getUsuarioById($pdo, $alumno_id);
                    $revisor = getUsuarioById($pdo, $revisor_id);
                    $documento = $pdo->query("SELECT nombre FROM catalogo_documentos WHERE id = $documento_id")->fetchColumn();
                    
                    $sql_notif = "INSERT INTO notificaciones (id_usuario, titulo, mensaje, tipo) 
                                 VALUES (:usuario_id, 'Nuevo revisor asignado', 
                                         'El {$revisor['nombre']} {$revisor['apellido_paterno']} ha sido asignado para revisar tu {$documento}', 
                                         'info')";
                    $stmt_notif = $pdo->prepare($sql_notif);
                    $stmt_notif->bindParam(':usuario_id', $alumno_id, PDO::PARAM_INT);
                    $stmt_notif->execute();
                } else {
                    $mensaje = "error:Error al asignar revisor";
                }
            }
        } catch (PDOException $e) {
            $mensaje = "error:Error al asignar revisor: " . $e->getMessage();
        }
    }
    
    elseif ($_POST['action'] == 'eliminar_asignacion') {
    $asignacion_id = $_POST['asignacion_id'];
    
    // Primero eliminar registros relacionados en documentos_revisados
    try {
        $sql_delete_relacionados = "DELETE FROM documentos_revisados WHERE id_revision = :id";
        $stmt_relacionados = $pdo->prepare($sql_delete_relacionados);
        $stmt_relacionados->bindParam(':id', $asignacion_id, PDO::PARAM_INT);
        $stmt_relacionados->execute();
        
        // Ahora eliminar la revisión
        $sql = "DELETE FROM revisiones WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $asignacion_id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $mensaje = "success:Asignación eliminada correctamente";
        } else {
            $mensaje = "error:Error al eliminar asignación";
        }
    } catch (PDOException $e) {
        $mensaje = "error:Error al eliminar asignación: " . $e->getMessage();
    }
}
}

// Obtener asignaciones actuales
$sql_asignaciones = "SELECT r.*, 
                    a.nombre as alumno_nombre, a.apellido_paterno as alumno_apellido, a.matricula as alumno_matricula,
                    rev.nombre as revisor_nombre, rev.apellido_paterno as revisor_apellido,
                    d.nombre as documento_nombre,
                    CASE 
                        WHEN r.estado = 'pendiente' THEN 'Pendiente'
                        WHEN r.estado = 'en_revision' THEN 'En Revisión'
                        WHEN r.estado = 'completada' THEN 'Completada'
                        ELSE r.estado
                    END as estado_texto
             FROM revisiones r
             JOIN usuarios a ON r.id_usuario = a.id
             JOIN usuarios rev ON r.id_revisor = rev.id
             JOIN catalogo_documentos d ON r.id_documento = d.id
             ORDER BY r.fecha_asignada DESC";
$asignaciones = $pdo->query($sql_asignaciones)->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Asignar Revisores</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Asignar Revisores a Alumnos</h1>
        </div>

        <?php if (isset($tipo) && isset($texto)): ?>
            <div class="<?php echo $tipo == 'success' ? 'success-message' : ($tipo == 'warning' ? 'warning-message' : 'error-message'); ?>">
                <?php echo $texto; ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h2>Nueva Asignación</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="asignar">
                    
                    <div class="form-group">
                        <label for="alumno_id">Alumno:</label>
                        <select id="alumno_id" name="alumno_id" required>
                            <option value="">Seleccionar alumno</option>
                            <?php foreach ($alumnos as $alumno): ?>
                                <option value="<?php echo $alumno['id']; ?>">
                                    <?php echo htmlspecialchars($alumno['matricula'] . ' - ' . $alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="revisor_id">Revisor:</label>
                        <select id="revisor_id" name="revisor_id" required>
                            <option value="">Seleccionar revisor</option>
                            <?php foreach ($revisores as $revisor): ?>
                                <option value="<?php echo $revisor['id']; ?>">
                                    <?php echo htmlspecialchars($revisor['nombre'] . ' ' . $revisor['apellido_paterno'] . ' ' . $revisor['apellido_materno']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="documento_id">Tipo de Documento:</label>
                        <select id="documento_id" name="documento_id" required>
                            <option value="">Seleccionar documento</option>
                            <?php foreach ($documentos as $documento): ?>
                                <option value="<?php echo $documento['id']; ?>">
                                    <?php echo htmlspecialchars($documento['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Asignar Revisor</button>
                    </div>
                </form>
            </div>

            <div class="content-card">
                <h2>Estadísticas de Asignaciones</h2>
                <div class="stats-mini">
                    <?php
                    $total_asignaciones = count($asignaciones);
                    $pendientes = count(array_filter($asignaciones, function($a) {
                        return $a['estado'] == 'pendiente';
                    }));
                    $en_revision = count(array_filter($asignaciones, function($a) {
                        return $a['estado'] == 'en_revision';
                    }));
                    $completadas = count(array_filter($asignaciones, function($a) {
                        return $a['estado'] == 'completada';
                    }));
                    ?>
                    <div class="stat-mini">
                        <h3>Total</h3>
                        <p class="stat-number"><?php echo $total_asignaciones; ?></p>
                    </div>
                    <div class="stat-mini">
                        <h3>Pendientes</h3>
                        <p class="stat-number"><?php echo $pendientes; ?></p>
                    </div>
                    <div class="stat-mini">
                        <h3>En Revisión</h3>
                        <p class="stat-number"><?php echo $en_revision; ?></p>
                    </div>
                    <div class="stat-mini">
                        <h3>Completadas</h3>
                        <p class="stat-number"><?php echo $completadas; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h2>Asignaciones Actuales</h2>
            <?php if ($asignaciones): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Alumno</th>
                            <th>Revisor</th>
                            <th>Documento</th>
                            <th>Fecha Asignada</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asignaciones as $asignacion): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($asignacion['alumno_nombre'] . ' ' . $asignacion['alumno_apellido']); ?></strong>
                                <br><small>Matrícula: <?php echo htmlspecialchars($asignacion['alumno_matricula']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($asignacion['revisor_nombre'] . ' ' . $asignacion['revisor_apellido']); ?></td>
                            <td><?php echo htmlspecialchars($asignacion['documento_nombre']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($asignacion['fecha_asignada'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $asignacion['estado']; ?>">
                                    <?php echo htmlspecialchars($asignacion['estado_texto']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="eliminar_asignacion">
                                    <input type="hidden" name="asignacion_id" value="<?php echo $asignacion['id']; ?>">
                                    <button type="submit" class="btn-danger btn-small" 
                                            onclick="return confirm('¿Está seguro de que desea eliminar esta asignación?')">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No hay asignaciones registradas</h3>
                    <p>Comienza asignando un revisor a un alumno usando el formulario superior.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>