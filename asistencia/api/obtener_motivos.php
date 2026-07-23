<?php
//Obtener lista de motivos de justificación
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, nombre FROM motivos_justificacion WHERE activo = 1 ORDER BY nombre");
    $motivos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $motivos
    ]);
} catch (PDOException $e) {
    error_log("Error en obtener_motivos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar los motivos'
    ]);
}
?>