<?php
// alumno/chat.php
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

// Obtener revisores asignados
$sql_revisores = "SELECT DISTINCT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.matricula
                  FROM revisiones r
                  JOIN usuarios u ON r.id_revisor = u.id
                  WHERE r.id_usuario = :usuario_id
                  ORDER BY u.nombre, u.apellido_paterno";
$stmt = $pdo->prepare($sql_revisores);
$stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
$stmt->execute();
$revisores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$destino_id = $_GET['destino'] ?? ($revisores[0]['id'] ?? null);
$mensaje = '';

// Procesar envío de mensaje
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mensaje']) && $destino_id) {
    $mensaje_texto = trim($_POST['mensaje']);
    if (!empty($mensaje_texto)) {
        if (enviarMensaje($pdo, $usuario_id, $destino_id, $mensaje_texto)) {
            $mensaje = "success:Mensaje enviado correctamente";
            // Redirigir para evitar reenvío al recargar
            header("Location: chat.php?destino=" . $destino_id);
            exit();
        } else {
            $mensaje = "error:Error al enviar mensaje";
        }
    } else {
        $mensaje = "error:El mensaje no puede estar vacío";
    }
}

// Obtener mensajes
$mensajes_chat = [];
if ($destino_id) {
    $mensajes_chat = getMensajesChat($pdo, $usuario_id, $destino_id);
}

// Procesar mensaje de estado
if ($mensaje) {
    list($tipo, $texto) = explode(':', $mensaje, 2);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat con Revisores</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .chat-page {
            display: flex;
            flex-direction: column;
            height: calc(100vh - 200px);
            min-height: 500px;
        }
        
        .chat-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
            flex: 1;
            min-height: 0;
            overflow: hidden;
        }
        
        .contacts-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .contacts-header {
            margin-bottom: 15px;
        }
        
        .contacts-scroll {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
        }
        
        .contact {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .contact:hover {
            background-color: var(--light-color);
            border-color: var(--primary-color);
        }
        
        .contact.active {
            background-color: var(--light-color);
            border-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(46, 125, 50, 0.2);
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
        }
        
        .contact-name {
            font-weight: bold;
            margin-bottom: 5px;
            color: var(--text-color);
        }
        
        .contact-role {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        
        .chat-window {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
            flex-shrink: 0;
        }
        
        .chat-header h3 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .messages-container {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #f8f9fa;
            min-height: 0;
        }
        
        .message {
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 12px;
            max-width: 70%;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            word-wrap: break-word;
        }
        
        .message.own {
            background-color: var(--light-color);
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }
        
        .message.other {
            background-color: white;
            border: 1px solid var(--border-color);
            border-bottom-left-radius: 4px;
        }
        
        .message-sender {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: var(--primary-color);
        }
        
        .message-content {
            margin-bottom: 5px;
            line-height: 1.4;
            color: var(--text-color);
        }
        
        .message-time {
            font-size: 0.75rem;
            color: var(--text-light);
            text-align: right;
        }
        
        .chat-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-shrink: 0;
        }
        
        .chat-form input {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .chat-form input:focus {
            border-color: var(--primary-color);
        }
        
        .no-messages {
            text-align: center;
            color: var(--text-light);
            padding: 2rem;
            font-style: italic;
        }
        
        .no-chat-selected {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
        }
        
        .last-message {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .refresh-btn {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .refresh-btn:hover {
            background: var(--primary-color);
        }
        
        /* Scrollbar personalizado */
        .messages-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .messages-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .messages-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .messages-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .contacts-scroll::-webkit-scrollbar {
            width: 6px;
        }
        
        .contacts-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .contacts-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        
        .contacts-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main class="main-content" style="margin-bottom: 0; padding-bottom: 0;">
        <div class="page-header">
            <h1>Chat con Revisores</h1>
            <p>Comunicación directa con tus revisores asignados</p>
        </div>

        <?php if (isset($tipo) && isset($texto)): ?>
            <div class="<?php echo $tipo == 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $texto; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($revisores)): ?>
            <div class="empty-state">
                <h2>No tienes revisores asignados</h2>
                <p>Contacta al administrador para que te asigne un revisor.</p>
                <a href="../alumno/index.php" class="btn-primary">Volver al Dashboard</a>
            </div>
        <?php else: ?>
            <div class="chat-page">
                <div class="chat-container">
                    <div class="contacts-list">
                        <div class="contacts-header">
                            <h3>Revisores Asignados</h3>
                            <button type="button" class="refresh-btn" onclick="location.reload()">
                                🔄 Actualizar Mensajes
                            </button>
                        </div>
                        <div class="contacts-scroll">
                            <?php 
                            // Obtener último mensaje de cada conversación
                            $conversaciones = getConversacionesRecientes($pdo, $usuario_id);
                            ?>
                            <?php foreach ($revisores as $revisor): 
                                $ultimo_mensaje = '';
                                foreach ($conversaciones as $conv) {
                                    if ($conv['contacto_id'] == $revisor['id']) {
                                        $ultimo_mensaje = $conv['ultimo_mensaje'];
                                        break;
                                    }
                                }
                            ?>
                                <div class="contact <?php echo $revisor['id'] == $destino_id ? 'active' : ''; ?>" 
                                     onclick="window.location.href='?destino=<?php echo $revisor['id']; ?>'">
                                    <div class="contact-info">
                                        <div class="contact-name">
                                            <?php echo htmlspecialchars($revisor['nombre'] . ' ' . $revisor['apellido_paterno']); ?>
                                        </div>
                                        <div class="contact-role">
                                            Revisor
                                        </div>
                                        <?php if ($ultimo_mensaje): ?>
                                            <div class="last-message">
                                                Último: <?php echo date('d/m H:i', strtotime($ultimo_mensaje)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="chat-window">
                        <?php if ($destino_id): ?>
                            <?php 
                            $current_revisor = array_filter($revisores, function($r) use ($destino_id) {
                                return $r['id'] == $destino_id;
                            });
                            $revisor = $current_revisor ? array_values($current_revisor)[0] : null;
                            ?>
                            
                            <div class="chat-header">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3>
                                        Conversación con 
                                        <?php echo htmlspecialchars($revisor['nombre'] . ' ' . $revisor['apellido_paterno']); ?>
                                        <small>(Revisor)</small>
                                    </h3>
                                    <button type="button" class="refresh-btn" onclick="location.reload()">
                                        🔄 Actualizar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="messages-container" id="messagesContainer">
                                <?php if (empty($mensajes_chat)): ?>
                                    <p class="no-messages">No hay mensajes aún. ¡Inicia la conversación!</p>
                                <?php else: ?>
                                    <?php foreach ($mensajes_chat as $mensaje): ?>
                                        <div class="message <?php echo $mensaje['id_usuario_fuente'] == $usuario_id ? 'own' : 'other'; ?>">
                                            <div class="message-sender">
                                                <?php echo $mensaje['id_usuario_fuente'] == $usuario_id ? 'Tú' : htmlspecialchars($mensaje['remitente_nombre']); ?>
                                            </div>
                                            <div class="message-content"><?php echo htmlspecialchars($mensaje['mensaje']); ?></div>
                                            <div class="message-time">
                                                <?php echo date('d/m/Y H:i', strtotime($mensaje['fecha_enviado'])); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <form method="POST" class="chat-form">
                                <input type="text" name="mensaje" placeholder="Escribe tu mensaje..." required 
                                       id="messageInput" autocomplete="off">
                                <button type="submit" class="btn-primary">Enviar</button>
                            </form>
                        <?php else: ?>
                            <div class="no-chat-selected">
                                <h3>Selecciona un revisor</h3>
                                <p>Elige un revisor de la lista para comenzar a chatear</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Auto-scroll to bottom of messages
        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
        
        // Scroll al cargar la página
        document.addEventListener('DOMContentLoaded', scrollToBottom);
        
        // Focus en el input del mensaje
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.focus();
            }
        });
        
        // Enviar mensaje con Enter
        document.addEventListener('DOMContentLoaded', function() {
            const messageInput = document.getElementById('messageInput');
            const chatForm = document.querySelector('.chat-form');
            
            if (messageInput && chatForm) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        chatForm.submit();
                    }
                });
            }
        });

        // Ajustar altura del chat cuando cambia el tamaño de la ventana
        window.addEventListener('resize', function() {
            const chatPage = document.querySelector('.chat-page');
            const headerHeight = document.querySelector('.main-header').offsetHeight;
            const footerHeight = document.querySelector('.main-footer').offsetHeight;
            const pageHeaderHeight = document.querySelector('.page-header').offsetHeight;
            
            const availableHeight = window.innerHeight - headerHeight - footerHeight - pageHeaderHeight - 40;
            chatPage.style.height = Math.max(availableHeight, 500) + 'px';
        });

        // Inicializar altura al cargar
        window.dispatchEvent(new Event('resize'));
    </script>
</body>
</html>