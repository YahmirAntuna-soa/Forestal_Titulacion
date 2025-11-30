<?php
// admin/reportes.php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Obtener estadísticas generales
$estadisticas = getEstadisticasAdmin($pdo);

// Obtener documentos por tipo
$sql_docs_tipo = "SELECT cd.nombre, COUNT(ud.id) as cantidad 
                  FROM catalogo_documentos cd 
                  LEFT JOIN usuarios_documentos ud ON cd.id = ud.id_documento 
                  GROUP BY cd.id, cd.nombre 
                  ORDER BY cantidad DESC";
$docs_por_tipo = $pdo->query($sql_docs_tipo)->fetchAll(PDO::FETCH_ASSOC);

// Obtener alumnos con más documentos
$sql_alumnos_top = "SELECT u.nombre, u.apellido_paterno, u.matricula, 
                           COUNT(ud.id) as total_documentos
                    FROM usuarios u 
                    LEFT JOIN usuarios_documentos ud ON u.id = ud.id_usuario 
                    WHERE u.roles = (SELECT id FROM roles WHERE nombre = 'alumno') AND u.activo = 1
                    GROUP BY u.id, u.nombre, u.apellido_paterno, u.matricula 
                    ORDER BY total_documentos DESC 
                    LIMIT 10";
$alumnos_top = $pdo->query($sql_alumnos_top)->fetchAll(PDO::FETCH_ASSOC);

// Obtener revisores más activos
$sql_revisores_activos = "SELECT u.nombre, u.apellido_paterno, 
                                 COUNT(r.id) as revisiones_asignadas
                          FROM usuarios u 
                          LEFT JOIN revisiones r ON u.id = r.id_revisor 
                          WHERE u.roles = (SELECT id FROM roles WHERE nombre = 'revisor') AND u.activo = 1
                          GROUP BY u.id, u.nombre, u.apellido_paterno 
                          ORDER BY revisiones_asignadas DESC";
$revisores_activos = $pdo->query($sql_revisores_activos)->fetchAll(PDO::FETCH_ASSOC);

// Filtros para reportes
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Reporte de actividad por fecha
$sql_actividad = "SELECT 
    DATE(ud.fecha_subida) as fecha,
    COUNT(ud.id) as documentos_subidos,
    (SELECT COUNT(*) FROM chat c WHERE DATE(c.fecha_enviado) = DATE(ud.fecha_subida)) as mensajes_enviados
    FROM usuarios_documentos ud
    WHERE ud.fecha_subida BETWEEN :fecha_inicio AND DATE_ADD(:fecha_fin, INTERVAL 1 DAY)
    GROUP BY DATE(ud.fecha_subida)
    ORDER BY fecha DESC";

$stmt = $pdo->prepare($sql_actividad);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);
$stmt->execute();
$actividad = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas de documentos por estado
$sql_estados_docs = "SELECT 
    estado,
    COUNT(*) as cantidad,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM usuarios_documentos)), 2) as porcentaje
    FROM usuarios_documentos 
    GROUP BY estado 
    ORDER BY cantidad DESC";
$estados_documentos = $pdo->query($sql_estados_docs)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes del Sistema</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Reportes del Sistema</h1>
            <p>Estadísticas y análisis del sistema de titulación</p>
        </div>

        <!-- Estadísticas Principales -->
        <div class="stats-grid-large">
            <div class="stat-card-large">
                <div class="stat-number-large"><?php echo $estadisticas['total_alumnos']; ?></div>
                <h3>Total Alumnos</h3>
            </div>
            <div class="stat-card-large">
                <div class="stat-number-large"><?php echo $estadisticas['total_revisores']; ?></div>
                <h3>Revisores Activos</h3>
            </div>
            <div class="stat-card-large">
                <div class="stat-number-large"><?php echo $estadisticas['total_documentos']; ?></div>
                <h3>Documentos Subidos</h3>
            </div>
            <div class="stat-card-large">
                <div class="stat-number-large"><?php echo $estadisticas['revisiones_pendientes']; ?></div>
                <h3>Revisiones Pendientes</h3>
            </div>
            <div class="stat-card-large">
                <div class="stat-number-large"><?php echo $estadisticas['mensajes_hoy']; ?></div>
                <h3>Mensajes Hoy</h3>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-form">
            <h3>Filtrar por Fecha</h3>
            <form method="GET" class="form-inline">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha Inicio:</label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha Fin:</label>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $fecha_fin; ?>">
                </div>
                <button type="submit" class="btn-primary">Aplicar Filtros</button>
                <a href="reportes.php" class="btn-secondary">Limpiar</a>
            </form>
        </div>

        <div class="content-grid">
            <!-- Documentos por Tipo -->
            <div class="report-section">
                <h2>Documentos por Tipo</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Tipo de Documento</th>
                            <th>Cantidad</th>
                            <th>Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_docs = $estadisticas['total_documentos'];
                        foreach ($docs_por_tipo as $doc): 
                            $porcentaje = $total_docs > 0 ? ($doc['cantidad'] / $total_docs) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($doc['nombre']); ?></td>
                            <td><?php echo $doc['cantidad']; ?></td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                                </div>
                                <?php echo number_format($porcentaje, 1); ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Estados de Documentos -->
            <div class="report-section">
                <h2>Estados de Documentos</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Cantidad</th>
                            <th>Porcentaje</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estados_documentos as $estado): ?>
                        <tr>
                            <td>
                                <span class="status-badge status-<?php echo $estado['estado']; ?>">
                                    <?php echo ucfirst($estado['estado']); ?>
                                </span>
                            </td>
                            <td><?php echo $estado['cantidad']; ?></td>
                            <td><?php echo $estado['porcentaje']; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Alumnos -->
        <div class="report-section">
            <h2>Top 10 Alumnos con Más Documentos</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Alumno</th>
                        <th>Matrícula</th>
                        <th>Documentos Subidos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alumnos_top as $index => $alumno): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><?php echo htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido_paterno']); ?></td>
                        <td><?php echo htmlspecialchars($alumno['matricula']); ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($alumno['total_documentos'] * 10, 100); ?>%"></div>
                            </div>
                            <?php echo $alumno['total_documentos']; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Revisores Más Activos -->
        <div class="report-section">
            <h2>Revisores Más Activos</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Revisor</th>
                        <th>Revisiones Asignadas</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_revisiones = count($revisores_activos) > 0 ? array_sum(array_column($revisores_activos, 'revisiones_asignadas')) : 1;
                    foreach ($revisores_activos as $revisor): 
                        $porcentaje = ($revisor['revisiones_asignadas'] / $total_revisiones) * 100;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($revisor['nombre'] . ' ' . $revisor['apellido_paterno']); ?></td>
                        <td><?php echo $revisor['revisiones_asignadas']; ?></td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $porcentaje; ?>%"></div>
                            </div>
                            <?php echo number_format($porcentaje, 1); ?>%
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Actividad Reciente -->
        <div class="report-section">
            <h2>Actividad del Sistema (<?php echo date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)); ?>)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Documentos Subidos</th>
                        <th>Mensajes Enviados</th>
                        <th>Total Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($actividad): ?>
                        <?php foreach ($actividad as $dia): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($dia['fecha'])); ?></td>
                            <td><?php echo $dia['documentos_subidos']; ?></td>
                            <td><?php echo $dia['mensajes_enviados']; ?></td>
                            <td><strong><?php echo $dia['documentos_subidos'] + $dia['mensajes_enviados']; ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No hay actividad en el período seleccionado</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Botones de Exportación -->
        <div class="quick-actions">
            <h2>Exportar Reportes</h2>
            <div class="action-buttons">
                <button onclick="window.print()" class="btn-secondary">Imprimir Reporte</button>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>

    <script>
        // Animación de barras de progreso
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
</body>
</html>