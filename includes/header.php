<?php
// includes/header.php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="main-header">
    <div class="header-container">
        <div class="logo-section">
            <h1><a href="../index.php">Sistema de Titulación</a></h1>
            <p>Facultad de Ciencias Forestales y Ambientales</p>
        </div>
        
        <nav class="main-nav">
            <ul>
                <!-- <li><a href="../index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">Inicio</a></li> -->
                
                <?php if (isLoggedIn()): ?>
                    <li><a href="../dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">Inicio</a></li>
                    
                    <?php if (getUserRole() == 'admin'): ?>
                        <li><a href="../admin/usuarios.php" class="<?php echo ($current_page == 'usuarios.php') ? 'active' : ''; ?>">Usuarios</a></li>
                        <li><a href="../admin/asignaciones.php" class="<?php echo ($current_page == 'asignaciones.php') ? 'active' : ''; ?>">Asignaciones</a></li>
                        <li><a href="../admin/reportes.php" class="<?php echo ($current_page == 'reportes.php') ? 'active' : ''; ?>">Reportes</a></li>
                    <?php elseif (getUserRole() == 'revisor'): ?>
                        <li><a href="../revisor/revisiones.php" class="<?php echo ($current_page == 'revisiones.php') ? 'active' : ''; ?>">Revisiones</a></li>
                        <li><a href="../revisor/chat.php" class="<?php echo ($current_page == 'chat.php') ? 'active' : ''; ?>">Chat</a></li>
                    <?php elseif (getUserRole() == 'alumno'): ?>
                        <li><a href="../alumno/documentos.php" class="<?php echo ($current_page == 'documentos.php') ? 'active' : ''; ?>">Documentos</a></li>
                        <li><a href="../alumno/revisiones_recibidas.php" class="<?php echo ($current_page == 'revisiones_recibidas.php') ? 'active' : ''; ?>">Revisiones</a></li>
                        <li><a href="../alumno/chat.php" class="<?php echo ($current_page == 'chat.php') ? 'active' : ''; ?>">Chat</a></li>
                    <?php endif; ?>
                    
                    <li class="user-menu">
                        <span class="user-info">Bienvenido, <?php echo getUserName(); ?> (<?php echo getUserRole(); ?>)</span>
                        <a href="../logout.php" class="logout-btn">Cerrar Sesión</a>
                    </li>
                <?php else: ?>
                    <li><a href="../login.php" class="<?php echo ($current_page == 'login.php') ? 'active' : ''; ?>">Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>