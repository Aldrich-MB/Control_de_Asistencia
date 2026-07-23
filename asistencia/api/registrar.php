<?php
// Registrar entrada, salida, justificación de falta o salida justificada
require_once '../includes/config.php';


// Forzar a una la zona horaria establecida
$pdo->exec("SET time_zone = '-06:00'");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validar trabajador_id
if (!isset($input['trabajador_id']) || empty($input['trabajador_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID del trabajador requerido']);
    exit;
}

$trabajador_id = (int)$input['trabajador_id'];
$tipo = $input['tipo'] ?? '';
$tipos_validos = ['Entrada', 'Salida', 'Justificada', 'SalidaJustificada'];
if (!in_array($tipo, $tipos_validos)) {
    echo json_encode(['success' => false, 'message' => 'Tipo inválido. Debe ser Entrada, Salida, Justificada o SalidaJustificada']);
    exit;
}

$ip = obtenerIP();

try {
    
    //  VALIDACIÓN DE ESTADO DEL DÍA (NUEVA LÓGICA)
    
    // Obtener todos los registros del trabajador hoy
    $sql_estado = "SELECT tipo FROM asistencia 
                   WHERE trabajador_id = :trabajador_id 
                   AND DATE(fecha_hora) = CURDATE()";
    $stmt_estado = $pdo->prepare($sql_estado);
    $stmt_estado->execute([':trabajador_id' => $trabajador_id]);
    $registros_hoy = $stmt_estado->fetchAll(PDO::FETCH_COLUMN);

    $tiene_entrada = in_array('Entrada', $registros_hoy);
    $tiene_salida = in_array('Salida', $registros_hoy);
    $tiene_salida_justificada = in_array('SalidaJustificada', $registros_hoy);
    $tiene_justificacion = in_array('Justificada', $registros_hoy);
    $tiene_cualquier_registro = !empty($registros_hoy);

    // VALIDACIÓN SEGÚN EL TIPO DE REGISTRO SOLICITADO

    // 1. ENTRADA: Solo permitir si NO tiene ningún registro hoy
    if ($tipo === 'Entrada') {
        if ($tiene_cualquier_registro) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Ya tienes un registro hoy. Solo puedes registrar entrada si no has registrado nada.'
            ]);
            exit;
        }
    }

    // 2. SALIDA NORMAL: Debe tener entrada, NO puede tener salida, ni salida justificada, ni justificación
    if ($tipo === 'Salida') {
        if (!$tiene_entrada) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Debes registrar tu ENTRADA primero.'
            ]);
            exit;
        }
        if ($tiene_salida) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Ya registraste tu SALIDA hoy.'
            ]);
            exit;
        }
        if ($tiene_salida_justificada) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Ya registraste una SALIDA JUSTIFICADA hoy. No puedes registrar salida normal.'
            ]);
            exit;
        }
        if ($tiene_justificacion) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Justificaste tu falta hoy. No puedes registrar salida.'
            ]);
            exit;
        }
    }

    // 3. SALIDA JUSTIFICADA: Debe tener entrada, NO puede tener salida, ni salida justificada, ni justificación
    if ($tipo === 'SalidaJustificada') {
        if (!$tiene_entrada) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Debes registrar tu ENTRADA primero.'
            ]);
            exit;
        }
        if ($tiene_salida) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Ya registraste tu SALIDA normal hoy. No puedes registrar salida justificada.'
            ]);
            exit;
        }
        if ($tiene_salida_justificada) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Ya registraste una SALIDA JUSTIFICADA hoy.'
            ]);
            exit;
        }
        if ($tiene_justificacion) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Justificaste tu falta hoy. No puedes registrar salida justificada.'
            ]);
            exit;
        }
    }

    // 4. JUSTIFICACIÓN: Solo permitir si NO tiene ningún registro hoy
    if ($tipo === 'Justificada') {
        if ($tiene_cualquier_registro) {
            echo json_encode([
                'success' => false,
                'message' => '❌ Ya tienes un registro hoy. Solo puedes justificar si no has registrado nada.'
            ]);
            exit;
        }
    }

    // CONTINÚA CON EL RESTO DEL CÓDIGO EXISTENTE

    // ================== JUSTIFICADA (FALTA COMPLETA) ==================
    if ($tipo === 'Justificada') {
        // Validar campos específicos de justificación
        $fecha_justificacion = $input['fecha_justificacion'] ?? '';
        $motivo_id = isset($input['motivo_id']) ? (int)$input['motivo_id'] : null;
        $motivo_otro = trim($input['motivo_otro'] ?? '');
        $comprobante_path = $input['comprobante_path'] ?? null;

        if (empty($fecha_justificacion)) {
            echo json_encode(['success' => false, 'message' => 'La fecha de la falta es requerida']);
            exit;
        }
        if (!$motivo_id && empty($motivo_otro)) {
            echo json_encode(['success' => false, 'message' => 'Debes seleccionar un motivo o especificar "Otro"']);
            exit;
        }

        // Validar que no exista ya una justificación para esa fecha
        $sql_check = "SELECT COUNT(*) FROM asistencia 
                      WHERE trabajador_id = :trabajador_id 
                      AND tipo IN ('Justificada', 'SalidaJustificada')
                      AND DATE(fecha_justificacion) = :fecha";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([
            ':trabajador_id' => $trabajador_id,
            ':fecha' => $fecha_justificacion
        ]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe una justificación para la fecha ' . date('d/m/Y', strtotime($fecha_justificacion))
            ]);
            exit;
        }

        // Insertar justificación de falta
        $sql = "INSERT INTO asistencia 
                (trabajador_id, tipo, fecha_hora, motivo_id, motivo_otro, comprobante_path, fecha_justificacion, ip, latitud, longitud) 
                VALUES 
                (:trabajador_id, :tipo, NOW(), :motivo_id, :motivo_otro, :comprobante_path, :fecha_justificacion, :ip, NULL, NULL)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':trabajador_id' => $trabajador_id,
            ':tipo' => $tipo,
            ':motivo_id' => $motivo_id,
            ':motivo_otro' => $motivo_otro,
            ':comprobante_path' => $comprobante_path,
            ':fecha_justificacion' => $fecha_justificacion,
            ':ip' => $ip
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Falta justificada correctamente',
            'data' => ['id' => $pdo->lastInsertId()]
        ]);
        exit;
    }

    // ================== SALIDA JUSTIFICADA ==================
    if ($tipo === 'SalidaJustificada') {
        // Validar GPS
        if (!isset($input['latitud']) || !isset($input['longitud'])) {
            echo json_encode(['success' => false, 'message' => 'Ubicación GPS requerida para salida justificada']);
            exit;
        }

        $latitud = (float)$input['latitud'];
        $longitud = (float)$input['longitud'];

        $distancia = calcularDistancia($latitud, $longitud, OFICINA_LAT, OFICINA_LNG);
        if ($distancia > RADIO_PERMITIDO_METROS) {
            echo json_encode([
                'success' => false,
                'message' => "Fuera del radio permitido. Distancia: " . round($distancia, 2) . " metros (máx " . RADIO_PERMITIDO_METROS . "m)"
            ]);
            exit;
        }

        // Validar campos específicos de justificación
        $fecha_justificacion = $input['fecha_justificacion'] ?? date('Y-m-d');
        $motivo_id = isset($input['motivo_id']) ? (int)$input['motivo_id'] : null;
        $motivo_otro = trim($input['motivo_otro'] ?? '');
        $comprobante_path = $input['comprobante_path'] ?? null;

        if (!$motivo_id && empty($motivo_otro)) {
            echo json_encode(['success' => false, 'message' => 'Debes seleccionar un motivo o especificar "Otro"']);
            exit;
        }

        // Validaciones de duplicados
        // 1. Verificar que no tenga una salida normal hoy
        $sql_check = "SELECT COUNT(*) FROM asistencia 
                      WHERE trabajador_id = :trabajador_id 
                      AND tipo = 'Salida' 
                      AND DATE(fecha_hora) = CURDATE()";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya registraste una salida normal hoy. No puedes registrar salida justificada.'
            ]);
            exit;
        }

        // 2. Verificar que no tenga ya una salida justificada hoy
        $sql_check = "SELECT COUNT(*) FROM asistencia 
                      WHERE trabajador_id = :trabajador_id 
                      AND tipo = 'SalidaJustificada' 
                      AND DATE(fecha_hora) = CURDATE()";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya registraste una salida justificada hoy.'
            ]);
            exit;
        }

        // 3. Verificar que tenga entrada hoy
        $sql_entrada = "SELECT COUNT(*) FROM asistencia 
                        WHERE trabajador_id = :trabajador_id 
                        AND tipo = 'Entrada' 
                        AND DATE(fecha_hora) = CURDATE()";
        $stmt_entrada = $pdo->prepare($sql_entrada);
        $stmt_entrada->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_entrada->fetchColumn() == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes una entrada registrada hoy. Registra entrada primero.'
            ]);
            exit;
        }

        // 4. Validar tiempo mínimo (2 minutos)
        $sql_ultima_entrada = "SELECT fecha_hora FROM asistencia 
                              WHERE trabajador_id = :trabajador_id 
                              AND tipo = 'Entrada' 
                              AND DATE(fecha_hora) = CURDATE() 
                              ORDER BY fecha_hora DESC 
                              LIMIT 1";
        $stmt_entrada = $pdo->prepare($sql_ultima_entrada);
        $stmt_entrada->execute([':trabajador_id' => $trabajador_id]);
        $entrada = $stmt_entrada->fetch();
        
        if ($entrada) {
            $tiempo_segundos = time() - strtotime($entrada['fecha_hora']);
            if ($tiempo_segundos < 120) {
                $tiempo_minutos = round($tiempo_segundos / 60);
                echo json_encode([
                    'success' => false,
                    'message' => "⚠️ Solo han pasado $tiempo_minutos minuto(s) desde tu entrada. Debes esperar al menos 2 minutos para registrar tu salida justificada."
                ]);
                exit;
            }
        }

        // Insertar salida justificada
        $sql = "INSERT INTO asistencia 
                (trabajador_id, tipo, fecha_hora, motivo_id, motivo_otro, comprobante_path, fecha_justificacion, ip, latitud, longitud) 
                VALUES 
                (:trabajador_id, :tipo, NOW(), :motivo_id, :motivo_otro, :comprobante_path, :fecha_justificacion, :ip, :latitud, :longitud)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':trabajador_id' => $trabajador_id,
            ':tipo' => $tipo,
            ':motivo_id' => $motivo_id,
            ':motivo_otro' => $motivo_otro,
            ':comprobante_path' => $comprobante_path,
            ':fecha_justificacion' => $fecha_justificacion,
            ':ip' => $ip,
            ':latitud' => $latitud,
            ':longitud' => $longitud
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Salida justificada registrada correctamente',
            'data' => ['id' => $pdo->lastInsertId()]
        ]);
        exit;
    }

    // ================== ENTRADA / SALIDA NORMAL ==================
    // Validar GPS
    if (!isset($input['latitud']) || !isset($input['longitud'])) {
        echo json_encode(['success' => false, 'message' => 'Ubicación GPS requerida']);
        exit;
    }

    $latitud = (float)$input['latitud'];
    $longitud = (float)$input['longitud'];

    $distancia = calcularDistancia($latitud, $longitud, OFICINA_LAT, OFICINA_LNG);
    if ($distancia > RADIO_PERMITIDO_METROS) {
        echo json_encode([
            'success' => false,
            'message' => "Fuera del radio permitido. Distancia: " . round($distancia, 2) . " metros (máx " . RADIO_PERMITIDO_METROS . "m)"
        ]);
        exit;
    }

    // Validaciones de duplicados para entrada/salida normal
    if ($tipo === 'Entrada') {
        $sql_check = "SELECT COUNT(*) FROM asistencia 
                      WHERE trabajador_id = :trabajador_id 
                      AND tipo = 'Entrada' 
                      AND DATE(fecha_hora) = CURDATE()";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya registraste tu entrada hoy.'
            ]);
            exit;
        }
    }

    if ($tipo === 'Salida') {
        // Verificar si ya tiene salida normal hoy
        $sql_check = "SELECT COUNT(*) FROM asistencia 
                      WHERE trabajador_id = :trabajador_id 
                      AND tipo = 'Salida' 
                      AND DATE(fecha_hora) = CURDATE()";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya registraste tu salida hoy.'
            ]);
            exit;
        }

        // Verificar si ya tiene salida justificada hoy
        $sql_check = "SELECT COUNT(*) FROM asistencia 
                      WHERE trabajador_id = :trabajador_id 
                      AND tipo = 'SalidaJustificada' 
                      AND DATE(fecha_hora) = CURDATE()";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_check->fetchColumn() > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Ya registraste una salida justificada hoy. No puedes registrar salida normal.'
            ]);
            exit;
        }

        // Verificar que tenga entrada hoy
        $sql_entrada = "SELECT COUNT(*) FROM asistencia 
                        WHERE trabajador_id = :trabajador_id 
                        AND tipo = 'Entrada' 
                        AND DATE(fecha_hora) = CURDATE()";
        $stmt_entrada = $pdo->prepare($sql_entrada);
        $stmt_entrada->execute([':trabajador_id' => $trabajador_id]);
        if ($stmt_entrada->fetchColumn() == 0) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes una entrada registrada hoy. Registra entrada primero.'
            ]);
            exit;
        }

        // Validar tiempo mínimo (2 minutos)
        $sql_ultima_entrada = "SELECT fecha_hora FROM asistencia 
                              WHERE trabajador_id = :trabajador_id 
                              AND tipo = 'Entrada' 
                              AND DATE(fecha_hora) = CURDATE() 
                              ORDER BY fecha_hora DESC 
                              LIMIT 1";
        $stmt_entrada = $pdo->prepare($sql_ultima_entrada);
        $stmt_entrada->execute([':trabajador_id' => $trabajador_id]);
        $entrada = $stmt_entrada->fetch();
        
        if ($entrada) {
            $tiempo_segundos = time() - strtotime($entrada['fecha_hora']);
            if ($tiempo_segundos < 120) {
                $tiempo_minutos = round($tiempo_segundos / 60);
                echo json_encode([
                    'success' => false,
                    'message' => "⚠️ Solo han pasado $tiempo_minutos minuto(s) desde tu entrada. Debes esperar al menos 2 minutos para registrar tu salida."
                ]);
                exit;
            }
        }
    }

    // Insertar registro de entrada/salida normal
    $sql = "INSERT INTO asistencia (trabajador_id, tipo, latitud, longitud, ip, fecha_hora) 
            VALUES (:trabajador_id, :tipo, :latitud, :longitud, :ip, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':trabajador_id' => $trabajador_id,
        ':tipo' => $tipo,
        ':latitud' => $latitud,
        ':longitud' => $longitud,
        ':ip' => $ip
    ]);

    echo json_encode([
        'success' => true,
        'message' => ($tipo == 'Entrada' ? 'Entrada' : 'Salida') . ' registrada correctamente',
        'data' => [
            'id' => $pdo->lastInsertId(),
            'distancia' => round($distancia, 2) . ' metros'
        ]
    ]);

} catch (PDOException $e) {
    error_log("Error en registrar.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error en base de datos: ' . $e->getMessage()
    ]);
}

function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $radioTierra = 6371000;
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);

    $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLon / 2) * sin($deltaLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $radioTierra * $c;
}
?>