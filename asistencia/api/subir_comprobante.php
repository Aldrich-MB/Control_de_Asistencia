<?php
// Subir archivo comprobante de justificación
require_once '../includes/config.php';

// Configurar cabecera para JSON
header('Content-Type: application/json');

// Solo aceptar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Verificar que se recibió un archivo
if (!isset($_FILES['comprobante']) || $_FILES['comprobante']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió ningún archivo o hubo un error'
    ]);
    exit;
}

$archivo = $_FILES['comprobante'];
$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
$extensiones_permitidas = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
$tamano_maximo = 5 * 1024 * 1024; // 5 MB

// Validar extensión
if (!in_array($extension, $extensiones_permitidas)) {
    echo json_encode([
        'success' => false,
        'message' => 'Formato no permitido. Solo PDF, JPG, JPEG, PNG, GIF'
    ]);
    exit;
}

// Validar tamaño
if ($archivo['size'] > $tamano_maximo) {
    echo json_encode([
        'success' => false,
        'message' => 'El archivo es demasiado grande. Máximo 5 MB'
    ]);
    exit;
}

// Crear estructura de carpetas por año/mes
$fecha_actual = date('Y/m/d');
$carpeta_destino = '../uploads/justificantes/' . $fecha_actual . '/';
if (!is_dir($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

// Generar nombre único para el archivo
$nombre_unico = date('Ymd_His') . '_' . uniqid() . '.' . $extension;
$ruta_completa = $carpeta_destino . $nombre_unico;

// Mover el archivo
if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
    // Guardar ruta relativa para la base de datos
    $ruta_relativa = 'uploads/justificantes/' . $fecha_actual . '/' . $nombre_unico;
    
    echo json_encode([
        'success' => true,
        'path' => $ruta_relativa,
        'message' => 'Archivo subido correctamente'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar el archivo'
    ]);
}
?>