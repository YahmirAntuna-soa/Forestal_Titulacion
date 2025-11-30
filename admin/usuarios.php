<?php
// admin/usuarios.php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/functions.php';

redirectIfNotLoggedIn();
if (getUserRole() != 'admin') {
    header("Location: ../login.php");
    exit();
}

$action = $_GET['action'] ?? 'list';
$rol_filter = $_GET['rol'] ?? '';
$search_term = $_GET['search'] ?? '';
$mensaje = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] == 'add') {
        $matricula = sanitize($_POST['matricula']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $nombre = sanitize($_POST['nombre']);
        $apellido_paterno = sanitize($_POST['apellido_paterno']);
        $apellido_materno = sanitize($_POST['apellido_materno']);
        $carrera = $_POST['carrera'];
        $email = sanitize($_POST['email']);
        $rol = $_POST['rol'];
        $unidad_academica = $_POST['unidad_academica'];
        
        // Para campos de texto, usar empty() en lugar de isset()
        $director_tesis = !empty($_POST['director_tesis']) ? sanitize($_POST['director_tesis']) : null;
        $codirector = !empty($_POST['codirector']) ? sanitize($_POST['codirector']) : null;
        $asesor1 = !empty($_POST['asesor1']) ? sanitize($_POST['asesor1']) : null;
        $asesor2 = !empty($_POST['asesor2']) ? sanitize($_POST['asesor2']) : null;
        $asesor3 = !empty($_POST['asesor3']) ? sanitize($_POST['asesor3']) : null;

        try {
            $sql = "INSERT INTO usuarios (matricula, password, nombre, apellido_paterno, apellido_materno, 
                                         carrera, email, roles, unidad_academica, director_tesis, codirector, asesor1, asesor2, asesor3) 
                    VALUES (:matricula, :password, :nombre, :apellido_paterno, :apellido_materno, 
                           :carrera, :email, :rol, :unidad_academica, :director_tesis, :codirector, :asesor1, :asesor2, :asesor3)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':matricula', $matricula);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':apellido_paterno', $apellido_paterno);
            $stmt->bindParam(':apellido_materno', $apellido_materno);
            $stmt->bindParam(':carrera', $carrera);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':unidad_academica', $unidad_academica);
            $stmt->bindParam(':director_tesis', $director_tesis);
            $stmt->bindParam(':codirector', $codirector);
            $stmt->bindParam(':asesor1', $asesor1);
            $stmt->bindParam(':asesor2', $asesor2);
            $stmt->bindParam(':asesor3', $asesor3);
            
            if ($stmt->execute()) {
                $mensaje = "success:Usuario agregado correctamente";
                $action = 'list';
            }
        } catch (PDOException $e) {
            $mensaje = "error:Error al agregar usuario: " . $e->getMessage();
        }
    }
    elseif ($_POST['action'] == 'edit') {
        $id = $_POST['id'];
        $matricula = sanitize($_POST['matricula']);
        $nombre = sanitize($_POST['nombre']);
        $apellido_paterno = sanitize($_POST['apellido_paterno']);
        $apellido_materno = sanitize($_POST['apellido_materno']);
        $carrera = $_POST['carrera'];
        $email = sanitize($_POST['email']);
        $rol = $_POST['rol'];
        $unidad_academica = $_POST['unidad_academica'];
        
        // Para campos de texto, usar empty() en lugar de isset()
        $director_tesis = !empty($_POST['director_tesis']) ? sanitize($_POST['director_tesis']) : null;
        $codirector = !empty($_POST['codirector']) ? sanitize($_POST['codirector']) : null;
        $asesor1 = !empty($_POST['asesor1']) ? sanitize($_POST['asesor1']) : null;
        $asesor2 = !empty($_POST['asesor2']) ? sanitize($_POST['asesor2']) : null;
        $asesor3 = !empty($_POST['asesor3']) ? sanitize($_POST['asesor3']) : null;
        
        try {
            // Construir la consulta de actualización
            $sql = "UPDATE usuarios SET 
                    matricula = :matricula,
                    nombre = :nombre,
                    apellido_paterno = :apellido_paterno,
                    apellido_materno = :apellido_materno,
                    carrera = :carrera,
                    email = :email,
                    roles = :rol,
                    unidad_academica = :unidad_academica,
                    director_tesis = :director_tesis,
                    codirector = :codirector,
                    asesor1 = :asesor1,
                    asesor2 = :asesor2,
                    asesor3 = :asesor3";
            
            // Si se proporcionó una nueva contraseña, incluirla en la actualización
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql .= ", password = :password";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':matricula', $matricula);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':apellido_paterno', $apellido_paterno);
            $stmt->bindParam(':apellido_materno', $apellido_materno);
            $stmt->bindParam(':carrera', $carrera);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':unidad_academica', $unidad_academica);
            $stmt->bindParam(':director_tesis', $director_tesis);
            $stmt->bindParam(':codirector', $codirector);
            $stmt->bindParam(':asesor1', $asesor1);
            $stmt->bindParam(':asesor2', $asesor2);
            $stmt->bindParam(':asesor3', $asesor3);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if (!empty($_POST['password'])) {
                $stmt->bindParam(':password', $password);
            }
            
            if ($stmt->execute()) {
                $mensaje = "success:Usuario actualizado correctamente";
                $action = 'list';
            }
        } catch (PDOException $e) {
            $mensaje = "error:Error al actualizar usuario: " . $e->getMessage();
        }
    }
    elseif ($_POST['action'] == 'toggle_status') {
        $id = $_POST['id'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $sql = "UPDATE usuarios SET activo = :estado WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':estado', $nuevo_estado, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $accion = $nuevo_estado ? 'activado' : 'desactivado';
            $mensaje = "success:Usuario $accion correctamente";
        } else {
            $mensaje = "error:Error al cambiar estado del usuario";
        }
    }
}

// Obtener datos
$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
$programas = getProgramasEducativos($pdo);
$unidades = getUnidadesAcademicas($pdo);

// Obtener usuario específico para edición
$usuario_editar = null;
if ($action == 'edit' && isset($_GET['id'])) {
    $id_editar = $_GET['id'];
    $sql_usuario = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql_usuario);
    $stmt->bindParam(':id', $id_editar, PDO::PARAM_INT);
    $stmt->execute();
    $usuario_editar = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario_editar) {
        $mensaje = "error:Usuario no encontrado";
        $action = 'list';
    }
}

// Obtener usuarios según filtro
$sql_usuarios = "SELECT u.*, r.nombre as rol_nombre, p.nombre as carrera_nombre, 
                        ua.nombre as unidad_nombre
                 FROM usuarios u 
                 JOIN roles r ON u.roles = r.id 
                 LEFT JOIN catalogo_programa_educativo p ON u.carrera = p.id
                 LEFT JOIN catalogo_unidad_academica ua ON u.unidad_academica = ua.id
                 WHERE 1=1";
                 
if ($rol_filter) {
    $sql_usuarios .= " AND r.nombre = :rol";
}

if ($search_term) {
    $sql_usuarios .= " AND (u.matricula LIKE :search OR CONCAT(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) LIKE :search OR u.email LIKE :search)";
}

$sql_usuarios .= " ORDER BY u.nombre, u.apellido_paterno";

$stmt = $pdo->prepare($sql_usuarios);
if ($rol_filter) {
    $stmt->bindParam(':rol', $rol_filter);
}
if ($search_term) {
    $search_like = '%' . $search_term . '%';
    $stmt->bindParam(':search', $search_like);
}
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="../css/style.css">
    <script>
        function toggleAsesoresSection() {
            const rolSelect = document.getElementById('rol');
            const asesoresSection = document.getElementById('asesores-section');
            const selectedRol = rolSelect.options[rolSelect.selectedIndex].text;
            
            // Mostrar sección de asesores solo para alumnos
            if (selectedRol.toLowerCase() === 'alumno') {
                asesoresSection.style.display = 'block';
            } else {
                asesoresSection.style.display = 'none';
            }
        }

        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            toggleAsesoresSection();
        });
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content">
        <div class="page-header">
            <h1>Gestión de Usuarios</h1>
            <div class="page-actions">
                <a href="?action=add" class="btn-primary">Agregar Usuario</a>
            </div>
        </div>

        <?php if (isset($tipo) && isset($texto)): ?>
            <div class="<?php echo $tipo == 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $texto; ?>
            </div>
        <?php endif; ?>

        <?php if ($action == 'list'): ?>
            <!-- Código de la lista de usuarios -->
            <div class="filters">
                <h3>Filtrar por rol:</h3>
                <div class="filter-buttons">
                    <a href="?<?php if($search_term) echo 'search=' . urlencode($search_term); ?>" class="btn-secondary <?php echo !$rol_filter ? 'active' : ''; ?>">Todos</a>
                    <a href="?rol=admin<?php if($search_term) echo '&search=' . urlencode($search_term); ?>" class="btn-secondary <?php echo $rol_filter == 'admin' ? 'active' : ''; ?>">Administradores</a>
                    <a href="?rol=revisor<?php if($search_term) echo '&search=' . urlencode($search_term); ?>" class="btn-secondary <?php echo $rol_filter == 'revisor' ? 'active' : ''; ?>">Revisores</a>
                    <a href="?rol=alumno<?php if($search_term) echo '&search=' . urlencode($search_term); ?>" class="btn-secondary <?php echo $rol_filter == 'alumno' ? 'active' : ''; ?>">Alumnos</a>
                </div>
            </div>

            <div class="filters">
                <h3>Buscar Usuarios</h3>
                <form method="GET" class="form-inline">
                    <?php if ($rol_filter): ?>
                        <input type="hidden" name="rol" value="<?php echo htmlspecialchars($rol_filter); ?>">
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="search">Buscar por matrícula, nombre o email:</label>
                        <input type="text" id="search" name="search" placeholder="Buscar..." value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <button type="submit" class="btn-primary">Buscar</button>
                </form>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Matrícula</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Carrera</th>
                            <th>Unidad Académica</th>
                            <th>Director</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['matricula']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido_paterno'] . ' ' . $usuario['apellido_materno']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $usuario['rol_nombre']; ?>">
                                    <?php echo htmlspecialchars($usuario['rol_nombre']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['carrera_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($usuario['unidad_nombre'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($usuario['director_tesis'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?action=edit&id=<?php echo $usuario['id']; ?>" class="btn-small btn-primary">Editar</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $usuario['id']; ?>">
                                        <input type="hidden" name="nuevo_estado" value="<?php echo $usuario['activo'] ? '0' : '1'; ?>">
                                        <button type="submit" class="btn-small <?php echo $usuario['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $usuario['activo'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($action == 'add' || $action == 'edit'): ?>
            <div class="form-container">
                <h2><?php echo $action == 'add' ? 'Agregar Nuevo Usuario' : 'Editar Usuario'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($action == 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $usuario_editar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="matricula">Matrícula:</label>
                        <input type="text" id="matricula" name="matricula" 
                               value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['matricula']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" 
                               placeholder="<?php echo $action == 'edit' ? 'Dejar vacío para mantener la actual' : ''; ?>"
                               <?php echo $action == 'add' ? 'required' : ''; ?>>
                        <?php if ($action == 'edit'): ?>
                            <small class="form-help">Solo llenar si desea cambiar la contraseña</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" 
                               value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['nombre']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido_paterno">Apellido Paterno:</label>
                        <input type="text" id="apellido_paterno" name="apellido_paterno" 
                               value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['apellido_paterno']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido_materno">Apellido Materno:</label>
                        <input type="text" id="apellido_materno" name="apellido_materno"
                               value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['apellido_materno']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['email']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol" required onchange="toggleAsesoresSection()">
                            <option value="">Seleccionar rol</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?php echo $rol['id']; ?>" 
                                    <?php if ($action == 'edit' && $usuario_editar['roles'] == $rol['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($rol['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="carrera">Carrera:</label>
                        <select id="carrera" name="carrera" required>
                            <option value="">Seleccionar carrera</option>
                            <?php foreach ($programas as $programa): ?>
                                <option value="<?php echo $programa['id']; ?>"
                                    <?php if ($action == 'edit' && $usuario_editar['carrera'] == $programa['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($programa['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="unidad_academica">Unidad Académica:</label>
                        <select id="unidad_academica" name="unidad_academica" required>
                            <option value="">Seleccionar unidad</option>
                            <?php foreach ($unidades as $unidad): ?>
                                <option value="<?php echo $unidad['id']; ?>"
                                    <?php if ($action == 'edit' && $usuario_editar['unidad_academica'] == $unidad['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($unidad['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sección de asesores (solo visible para alumnos) -->
                    <div id="asesores-section" style="display: none;">
                        <h3>Asesores de Tesis</h3>
                        <div class="form-group">
                            <label for="director_tesis">Director de Tesis:</label>
                            <input type="text" id="director_tesis" name="director_tesis" 
                                   value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['director_tesis'] ?? '') : ''; ?>"
                                   placeholder="Escriba el nombre del director">
                        </div>
                        <div class="form-group">
                            <label for="codirector">Codirector:</label>
                            <input type="text" id="codirector" name="codirector" 
                                   value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['codirector'] ?? '') : ''; ?>"
                                   placeholder="Escriba el nombre del codirector">
                        </div>
                        <div class="form-group">
                            <label for="asesor1">Asesor 1:</label>
                            <input type="text" id="asesor1" name="asesor1" 
                                   value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['asesor1'] ?? '') : ''; ?>"
                                   placeholder="Escriba el nombre del asesor 1">
                        </div>
                        <div class="form-group">
                            <label for="asesor2">Asesor 2:</label>
                            <input type="text" id="asesor2" name="asesor2" 
                                   value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['asesor2'] ?? '') : ''; ?>"
                                   placeholder="Escriba el nombre del asesor 2">
                        </div>
                        <div class="form-group">
                            <label for="asesor3">Asesor 3:</label>
                            <input type="text" id="asesor3" name="asesor3" 
                                   value="<?php echo $action == 'edit' ? htmlspecialchars($usuario_editar['asesor3'] ?? '') : ''; ?>"
                                   placeholder="Escriba el nombre del asesor 3">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <?php echo $action == 'add' ? 'Agregar Usuario' : 'Actualizar Usuario'; ?>
                        </button>
                        <a href="usuarios.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>