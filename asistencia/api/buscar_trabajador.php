<?php
//Buscar trabajador por CVE

// Incluir configuración
require_once '../includes/config.php';

// Configurar cabecera para JSON
header('Content-Type: application/json');

// Verificar que se recibió una CVE
if (!isset($_GET['cve']) || empty(trim($_GET['cve']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Debes proporcionar una CVE'
    ]);
    exit;
}

$cve = trim($_GET['cve']);

try {
    // Consultar trabajador activo por CVE
    $sql = "SELECT 
                t.id, 
                t.cve, 
                CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) AS nombre_completo,
                c.nombre AS cargo
            FROM trabajadores t
            LEFT JOIN cargos c ON t.cargo_id = c.id
            WHERE t.cve = :cve AND t.activo = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cve' => $cve]);
    $trabajador = $stmt->fetch();

    if ($trabajador) {
        // Limpiar espacios extra en nombre_completo
        $trabajador['nombre_completo'] = trim(preg_replace('/\s+/', ' ', $trabajador['nombre_completo']));
        
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $trabajador['id'],
                'cve' => $trabajador['cve'],
                'nombre_completo' => $trabajador['nombre_completo'],
                'cargo' => $trabajador['cargo']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Trabajador no encontrado o está inactivo'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Error en buscar_trabajador.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al buscar el trabajador'
    ]);
}
?>