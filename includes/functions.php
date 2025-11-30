<?php
// includes/functions.php

function getProgramasEducativos($pdo) {
    $sql = "SELECT * FROM catalogo_programa_educativo ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUnidadesAcademicas($pdo) {
    $sql = "SELECT * FROM catalogo_unidad_academica ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDocumentosCatalogo($pdo) {
    $sql = "SELECT * FROM catalogo_documentos ORDER BY nombre";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRevisores($pdo) {
    $sql = "SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno 
            FROM usuarios u 
            JOIN roles r ON u.roles = r.id 
            WHERE r.nombre = 'revisor' AND u.activo = 1
            ORDER BY u.nombre, u.apellido_paterno";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAlumnos($pdo) {
    $sql = "SELECT u.id, u.matricula, u.nombre, u.apellido_paterno, u.apellido_materno, 
                   p.nombre as carrera, ua.nombre as unidad_academica
            FROM usuarios u 
            JOIN catalogo_programa_educativo p ON u.carrera = p.id
            JOIN catalogo_unidad_academica ua ON u.unidad_academica = ua.id
            JOIN roles r ON u.roles = r.id 
            WHERE r.nombre = 'alumno' AND u.activo = 1
            ORDER BY u.nombre, u.apellido_paterno";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUsuarioById($pdo, $id) {
    $sql = "SELECT u.*, r.nombre as rol_nombre, p.nombre as carrera_nombre, ua.nombre as unidad_nombre
            FROM usuarios u 
            JOIN roles r ON u.roles = r.id
            LEFT JOIN catalogo_programa_educativo p ON u.carrera = p.id
            LEFT JOIN catalogo_unidad_academica ua ON u.unidad_academica = ua.id
            WHERE u.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getMensajesChat($pdo, $usuario_id, $destino_id) {
    $sql = "SELECT c.*, u.nombre as remitente_nombre 
            FROM chat c 
            JOIN usuarios u ON c.id_usuario_fuente = u.id 
            WHERE (c.id_usuario_fuente = :usuario_id AND c.id_usuario_destino = :destino_id)
               OR (c.id_usuario_fuente = :destino_id AND c.id_usuario_destino = :usuario_id)
            ORDER BY c.fecha_enviado ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->bindParam(':destino_id', $destino_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function enviarMensaje($pdo, $remitente_id, $destino_id, $mensaje) {
    $sql = "INSERT INTO chat (id_usuario_fuente, id_usuario_destino, fecha_enviado, mensaje) 
            VALUES (:remitente, :destino, NOW(), :mensaje)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':remitente', $remitente_id, PDO::PARAM_INT);
    $stmt->bindParam(':destino', $destino_id, PDO::PARAM_INT);
    $stmt->bindParam(':mensaje', $mensaje);
    
    return $stmt->execute();
}

function getConversacionesRecientes($pdo, $usuario_id) {
    $sql = "SELECT DISTINCT 
                CASE 
                    WHEN c.id_usuario_fuente = :usuario_id THEN c.id_usuario_destino 
                    ELSE c.id_usuario_fuente 
                END as contacto_id,
                u.nombre, u.apellido_paterno, u.apellido_materno, u.matricula,
                MAX(c.fecha_enviado) as ultimo_mensaje
            FROM chat c
            JOIN usuarios u ON (u.id = CASE 
                WHEN c.id_usuario_fuente = :usuario_id THEN c.id_usuario_destino 
                ELSE c.id_usuario_fuente 
            END)
            WHERE (c.id_usuario_fuente = :usuario_id OR c.id_usuario_destino = :usuario_id)
            GROUP BY contacto_id, u.nombre, u.apellido_paterno, u.apellido_materno, u.matricula
            ORDER BY ultimo_mensaje DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getEstadisticasAdmin($pdo) {
    $sql = "SELECT 
        (SELECT COUNT(*) FROM usuarios WHERE roles = (SELECT id FROM roles WHERE nombre = 'alumno') AND activo = 1) as total_alumnos,
        (SELECT COUNT(*) FROM usuarios WHERE roles = (SELECT id FROM roles WHERE nombre = 'revisor') AND activo = 1) as total_revisores,
        (SELECT COUNT(*) FROM usuarios_documentos) as total_documentos,
        (SELECT COUNT(*) FROM revisiones WHERE estado IN ('pendiente', 'en_revision')) as revisiones_pendientes,
        (SELECT COUNT(*) FROM chat WHERE DATE(fecha_enviado) = CURDATE()) as mensajes_hoy";
    
    return $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
}

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Función para obtener documentos revisados de una revisión
function getDocumentosRevisados($pdo, $revision_id) {
    $sql = "SELECT dr.*, u.nombre as revisor_nombre, u.apellido_paterno as revisor_apellido
            FROM documentos_revisados dr
            JOIN usuarios u ON dr.id_revisor = u.id
            WHERE dr.id_revision = :revision_id
            ORDER BY dr.fecha_revision DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':revision_id', $revision_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener revisiones con documentos revisados
function getRevisionesConDocumentos($pdo, $revisor_id) {
    $sql = "SELECT r.*, 
                   u.nombre as alumno_nombre, u.apellido_paterno as alumno_apellido,
                   u.matricula as alumno_matricula,
                   cd.nombre as documento_nombre,
                   (SELECT COUNT(*) FROM documentos_revisados dr WHERE dr.id_revision = r.id) as total_revisiones
            FROM revisiones r
            JOIN usuarios u ON r.id_usuario = u.id
            JOIN catalogo_documentos cd ON r.id_documento = cd.id
            WHERE r.id_revisor = :revisor_id
            ORDER BY r.fecha_asignada DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':revisor_id', $revisor_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>