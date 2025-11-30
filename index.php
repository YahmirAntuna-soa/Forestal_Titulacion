<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Automatización para Titulación</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <h1>Sistema de Titulación</h1>
                <p>Facultad de Ciencias Forestales y Ambientales</p>
            </div>
            
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php" class="active">Inicio</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Inicio</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <main class="main-content">
        <div class="hero-section">
            <h1>Sistema de Titulación</h1>
            <p>Facultad de Ciencias Forestales y Ambientales</p>
            <p>Universidad Juarez del Estado de Durango</p>
            
            <div class="cta-buttons">
                <a href="login.php" class="btn-primary">Iniciar Sesión</a>
            </div>
        </div>
        
                <div class="side-by-side">
            <div id="ficha" class="features-section">
                <h2>Ficha Tecnica</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>Formato de Ficha Tecnica</h3>
                        <p>Formato Necesario para su tramite de Titulación</p>
                        <div class="document-info">
                            <span class="file-type">WORD</span>
                        </div>
                        <a href="./uploads/ficha_tecnica_AVR.docx" class="btn-primary" download>
                            Descargar Formato
                        </a>
                    </div>
                </div>
            </div>

            <div id="reglamento" class="features-section">
                <h2>Reglamento Titulación</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <h3>Reglamento</h3>
                        <p>Porfavor tengan en cuenta este reglamento y siganlo al pie de la letra</p>
                        <div class="document-info">
                            <span class="file-type">PDF</span>
                        </div>
                        <a href="./uploads/REGLAMENTO DE TITULACION FCF Febrero 2020.pdf" class="btn-primary" download>
                            Descargar Reglamento
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </main>
    
    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> Sistema de Titulación - Facultad de Ciencias Forestales y Ambientales</p>
            <p>Universidad Juarez del Estado de Durango</p>
        </div>
    </footer>
</body>
</html>