<?php
//Generar reportes de asistencia en Excel y PDF
require_once '../includes/config.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// LIBRERÍA PARA PDF 
use Dompdf\Dompdf;
use Dompdf\Options;

// Obtener lista de trabajadores para el filtro
$trabajadores = [];
try {
    $stmt = $pdo->query("SELECT id, cve, CONCAT(nombre, ' ', apellidoP, ' ', COALESCE(apellidoM, '')) as nombre_completo FROM trabajadores ORDER BY nombre");
    $trabajadores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
}

$tipos_reporte = [
    'diario' => '📅 Reporte Diario',
    'semanal' => '📊 Reporte Semanal',
    'quincenal' => '📑 Reporte Quincenal',
    'mensual' => '📈 Reporte Mensual',
    'trimestral' => '📉 Reporte Trimestral',
    'semestral' => '📋 Reporte Semestral',
    'anual' => '🗓️ Reporte Anual'
];

$mensaje = '';
$tipo_mensaje = '';

// PROCESAR GENERACIÓN DE REPORTE (EXCEL O PDF)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_reporte = $_POST['tipo_reporte'] ?? 'diario';
    $trabajador_id = $_POST['trabajador_id'] ?? '';
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
    $fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
    $formato = $_POST['formato'] ?? 'excel'; // 'excel' o 'pdf'
    
    try {
        // Calcular fechas según tipo de reporte
        if ($tipo_reporte !== 'diario') {
            switch ($tipo_reporte) {
                case 'semanal':
                    $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
                    $fecha_fin = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'quincenal':
                    $dia = date('d');
                    if ($dia <= 15) {
                        $fecha_inicio = date('Y-m-01');
                        $fecha_fin = date('Y-m-15');
                    } else {
                        $fecha_inicio = date('Y-m-16');
                        $fecha_fin = date('Y-m-t');
                    }
                    break;
                case 'mensual':
                    $fecha_inicio = date('Y-m-01');
                    $fecha_fin = date('Y-m-t');
                    break;
                case 'trimestral':
                    $mes_actual = date('n');
                    $trimestre = ceil($mes_actual / 3);
                    $mes_inicio = ($trimestre - 1) * 3 + 1;
                    $mes_fin = $trimestre * 3;
                    $fecha_inicio = date('Y-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01');
                    $fecha_fin = date('Y-' . str_pad($mes_fin, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime(date('Y') . '-' . $mes_fin . '-01')));
                    break;
                case 'semestral':
                    $semestre = ceil(date('n') / 6);
                    $mes_inicio = ($semestre - 1) * 6 + 1;
                    $mes_fin = $semestre * 6;
                    $fecha_inicio = date('Y-' . str_pad($mes_inicio, 2, '0', STR_PAD_LEFT) . '-01');
                    $fecha_fin = date('Y-' . str_pad($mes_fin, 2, '0', STR_PAD_LEFT) . '-' . date('t', strtotime(date('Y') . '-' . $mes_fin . '-01')));
                    break;
                case 'anual':
                    $fecha_inicio = date('Y-01-01');
                    $fecha_fin = date('Y-12-31');
                    break;
            }
        }
        
        // CONSULTA DE DATOS

        $sql = "
            -- Entradas y salidas 
            SELECT 
                t.cve COLLATE utf8mb4_unicode_ci AS cve,
                CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) COLLATE utf8mb4_unicode_ci AS trabajador,
                COALESCE(c.nombre, 'Sin cargo') COLLATE utf8mb4_unicode_ci AS cargo,
                DATE(a.fecha_hora) AS fecha,
                a.tipo,
                TIME(a.fecha_hora) AS hora,
                NULL AS motivo,
                NULL AS comprobante,
                NULL AS fecha_justificacion,
                a.latitud,
                a.longitud,
                a.ip
            FROM asistencia a
            JOIN trabajadores t ON a.trabajador_id = t.id
            LEFT JOIN cargos c ON t.cargo_id = c.id
            WHERE a.tipo IN ('Entrada', 'Salida', 'SalidaJustificada')
                AND DATE(a.fecha_hora) BETWEEN :fecha_inicio AND :fecha_fin
                " . (!empty($trabajador_id) ? "AND t.id = :trabajador_id" : "") . "
            
            UNION ALL
            
            -- Justificaciones ()
            SELECT 
                t.cve COLLATE utf8mb4_unicode_ci AS cve,
                CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) COLLATE utf8mb4_unicode_ci AS trabajador,
                COALESCE(c.nombre, 'Sin cargo') COLLATE utf8mb4_unicode_ci AS cargo,
                DATE(a.fecha_hora) AS fecha,
                a.tipo,
                TIME(a.fecha_hora) AS hora,
                CASE 
                    WHEN a.motivo_id IS NOT NULL THEN m.nombre
                    ELSE a.motivo_otro
                END COLLATE utf8mb4_unicode_ci AS motivo,
                a.comprobante_path AS comprobante,
                a.fecha_justificacion,
                NULL AS latitud,
                NULL AS longitud,
                a.ip
            FROM asistencia a
            JOIN trabajadores t ON a.trabajador_id = t.id
            LEFT JOIN cargos c ON t.cargo_id = c.id
            LEFT JOIN motivos_justificacion m ON a.motivo_id = m.id
            WHERE a.tipo = 'Justificada'
                AND DATE(a.fecha_justificacion) BETWEEN :fecha_inicio2 AND :fecha_fin2
                " . (!empty($trabajador_id) ? "AND t.id = :trabajador_id2" : "") . "
            
            ORDER BY fecha DESC, hora DESC
        ";
        
        $params = [
            ':fecha_inicio' => $fecha_inicio,
            ':fecha_fin' => $fecha_fin,
            ':fecha_inicio2' => $fecha_inicio,
            ':fecha_fin2' => $fecha_fin
        ];
        if (!empty($trabajador_id)) {
            $params[':trabajador_id'] = $trabajador_id;
            $params[':trabajador_id2'] = $trabajador_id;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $registros = $stmt->fetchAll();
        
        if (empty($registros)) {
            $mensaje = "No hay datos para el período seleccionado.";
            $tipo_mensaje = "warning";
        } else {
            
            // GENERAR EXCEL
            if ($formato === 'excel') {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                $titulo = $tipos_reporte[$tipo_reporte] . " - " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
                $sheet->setCellValue('A1', $titulo);
                $sheet->mergeCells('A1:L1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                $encabezados = [
                    'CVE', 'Trabajador', 'Cargo', 'Fecha', 'Hora', 'Tipo', 
                    'Motivo/Justificación', 'Comprobante', 'Fecha de la falta', 
                    'Latitud', 'Longitud', 'IP'
                ];
                $columna = 'A';
                foreach ($encabezados as $encabezado) {
                    $sheet->setCellValue($columna . '3', $encabezado);
                    $sheet->getStyle($columna . '3')->getFont()->setBold(true);
                    $sheet->getStyle($columna . '3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
                    $columna++;
                }
                
                $fila = 4;
                foreach ($registros as $row) {
                    $tipo_mostrar = ($row['tipo'] == 'Entrada') ? 'Entrada' : (($row['tipo'] == 'Salida') ? 'Salida' : (($row['tipo'] == 'SalidaJustificada') ? 'Salida Justificada' : 'Justificada'));
                    $motivo_mostrar = '';
                    if ($row['tipo'] == 'Justificada' || $row['tipo'] == 'SalidaJustificada') {
                        $motivo_mostrar = $row['motivo'] ?? 'Sin especificar';
                    }
                    $fecha_falta = ($row['tipo'] == 'Justificada' && $row['fecha_justificacion']) ? date('d/m/Y', strtotime($row['fecha_justificacion'])) : '';
                    $comprobante = $row['comprobante'] ?? '';
                    if (!empty($comprobante)) {
                        $comprobante = basename($comprobante);
                    }
                    
                    $sheet->setCellValue('A' . $fila, $row['cve']);
                    $sheet->setCellValue('B' . $fila, trim($row['trabajador']));
                    $sheet->setCellValue('C' . $fila, $row['cargo'] ?? 'Sin cargo');
                    $sheet->setCellValue('D' . $fila, date('d/m/Y', strtotime($row['fecha'])));
                    $sheet->setCellValue('E' . $fila, $row['hora'] ?? '--:--');
                    $sheet->setCellValue('F' . $fila, $tipo_mostrar);
                    $sheet->setCellValue('G' . $fila, $motivo_mostrar);
                    $sheet->setCellValue('H' . $fila, $comprobante);
                    $sheet->setCellValue('I' . $fila, $fecha_falta);
                    $sheet->setCellValue('J' . $fila, $row['latitud'] ?? '');
                    $sheet->setCellValue('K' . $fila, $row['longitud'] ?? '');
                    $sheet->setCellValue('L' . $fila, $row['ip'] ?? '');
                    $fila++;
                }
                
                foreach (range('A', 'L') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                $styleArray = [
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FF000000']]
                    ]
                ];
                $sheet->getStyle('A3:L' . ($fila - 1))->applyFromArray($styleArray);
                
                $writer = new Xlsx($spreadsheet);
                $fecha_archivo = date('Y-m-d_H-i-s');
                $nombre_archivo = "reporte_{$tipo_reporte}_{$fecha_archivo}.xlsx";
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment;filename="' . $nombre_archivo . '"');
                header('Cache-Control: max-age=0');
                
                $writer->save('php://output');
                exit;
            }
            
            // GENERAR PDF (NUEVO)
            if ($formato === 'pdf') {
                $titulo = $tipos_reporte[$tipo_reporte] . " - " . date('d/m/Y', strtotime($fecha_inicio)) . " al " . date('d/m/Y', strtotime($fecha_fin));
                
                // Construir HTML para el PDF
                $html = '<!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <style>
                        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
                        h1 { text-align: center; font-size: 16px; margin-bottom: 20px; color: #1a56db; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                        th { background: #f3f4f6; font-weight: bold; padding: 8px 6px; border: 1px solid #ddd; text-align: left; font-size: 10px; }
                        td { padding: 6px 6px; border: 1px solid #ddd; font-size: 10px; }
                        .header { text-align: center; margin-bottom: 15px; }
                        .fecha { text-align: center; font-size: 12px; color: #6b7280; margin-bottom: 15px; }
                        .footer { text-align: center; font-size: 9px; color: #9ca3af; margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 10px; }
                        .badge-entrada { color: #057a55; font-weight: bold; }
                        .badge-salida { color: #c81e1e; font-weight: bold; }
                        .badge-salida-justificada { color: #5b21b6; font-weight: bold; }
                        .badge-justificada { color: #92400e; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>' . $titulo . '</h1>
                        <div class="fecha">Generado: ' . date('d/m/Y H:i:s') . '</div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>CVE</th>
                                <th>Trabajador</th>
                                <th>Cargo</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>Motivo/Justificación</th>
                                <th>Comprobante</th>
                                <th>Fecha Falta</th>
                            </tr>
                        </thead>
                        <tbody>';
                
                foreach ($registros as $row) {
                    $tipo_mostrar = ($row['tipo'] == 'Entrada') ? 'Entrada' : (($row['tipo'] == 'Salida') ? 'Salida' : (($row['tipo'] == 'SalidaJustificada') ? 'Salida Justificada' : 'Justificada'));
                    
                    $clase_tipo = '';
                    if ($row['tipo'] == 'Entrada') $clase_tipo = 'badge-entrada';
                    elseif ($row['tipo'] == 'Salida') $clase_tipo = 'badge-salida';
                    elseif ($row['tipo'] == 'SalidaJustificada') $clase_tipo = 'badge-salida-justificada';
                    else $clase_tipo = 'badge-justificada';
                    
                    $motivo_mostrar = '';
                    if ($row['tipo'] == 'Justificada' || $row['tipo'] == 'SalidaJustificada') {
                        $motivo_mostrar = $row['motivo'] ?? 'Sin especificar';
                    }
                    $fecha_falta = ($row['tipo'] == 'Justificada' && $row['fecha_justificacion']) ? date('d/m/Y', strtotime($row['fecha_justificacion'])) : '';
                    $comprobante = $row['comprobante'] ?? '';
                    if (!empty($comprobante)) {
                        $comprobante = basename($comprobante);
                    }
                    
                    $html .= '<tr>
                        <td>' . htmlspecialchars($row['cve']) . '</td>
                        <td>' . htmlspecialchars(trim($row['trabajador'])) . '</td>
                        <td>' . htmlspecialchars($row['cargo'] ?? 'Sin cargo') . '</td>
                        <td>' . date('d/m/Y', strtotime($row['fecha'])) . '</td>
                        <td>' . ($row['hora'] ?? '--:--') . '</td>
                        <td class="' . $clase_tipo . '">' . $tipo_mostrar . '</td>
                        <td>' . htmlspecialchars($motivo_mostrar) . '</td>
                        <td>' . htmlspecialchars($comprobante) . '</td>
                        <td>' . $fecha_falta . '</td>
                    </tr>';
                }
                
                $html .= '</tbody></table>
                    <div class="footer">Sistema de Control de Asistencia - Reporte generado automáticamente</div>
                </body>
                </html>';
                
                // Configurar Dompdf
                $options = new Options();
                $options->set('defaultFont', 'DejaVu Sans');
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', true);
                
                $dompdf = new Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();
                
                $fecha_archivo = date('Y-m-d_H-i-s');
                $nombre_archivo = "reporte_{$tipo_reporte}_{$fecha_archivo}.pdf";
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment;filename="' . $nombre_archivo . '"');
                header('Cache-Control: max-age=0');
                
                echo $dompdf->output();
                exit;
            }
        }
    } catch (Exception $e) {
        $mensaje = "Error al generar el reporte: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Control de Asistencia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-pdf {
            background: #dc2626;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .btn-pdf:hover {
            background: #b91c1c;
        }
        .btn-excel {
            background: #16a34a;
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .btn-excel:hover {
            background: #15803d;
        }
        .formato-botones {
            display: flex;
            gap: 12px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-brand">
        <span>📋</span>
        Control de Asistencia - Admin
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Inicio</a>
        <a href="trabajadores_lista.php">Trabajadores</a>
        <a href="trabajadores_nuevo.php">Nuevo Trabajador</a>
        <a href="nuevo_admin.php">Nuevo Admin</a>
        <a href="reportes.php" class="activo">Reportes</a>
        <a href="../logout.php" onclick="return confirm('¿Cerrar sesión?')">Salir</a>
    </div>
</nav>

<main class="page page-wide">
    <div class="card">
        <div class="card-title">📊 Generar reportes de asistencia</div>
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> show" style="display:flex; margin-bottom: 1rem;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="campo">
                <label for="tipo_reporte">📋 Tipo de reporte</label>
                <select id="tipo_reporte" name="tipo_reporte" required>
                    <?php foreach ($tipos_reporte as $valor => $label): ?>
                        <option value="<?php echo $valor; ?>" <?php echo (($_POST['tipo_reporte'] ?? 'diario') == $valor) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="campo">
                <label for="trabajador_id">👤 Trabajador (opcional)</label>
                <select id="trabajador_id" name="trabajador_id">
                    <option value="">-- Todos los trabajadores --</option>
                    <?php foreach ($trabajadores as $t): ?>
                        <option value="<?php echo $t['id']; ?>" <?php echo (($_POST['trabajador_id'] ?? '') == $t['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['cve'] . ' - ' . trim($t['nombre_completo'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="fechas_container" class="campo">
                <label for="fecha_inicio">📅 Rango de fechas (solo para reporte diario)</label>
                <div style="display: flex; gap: 10px;">
                    <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $_POST['fecha_inicio'] ?? date('Y-m-d'); ?>">
                    <span>hasta</span>
                    <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $_POST['fecha_fin'] ?? date('Y-m-d'); ?>">
                </div>
                <div class="hint">Para reportes periódicos las fechas se calculan automáticamente</div>
            </div>
            
            <hr class="div">
            
            <div class="formato-botones">
                <button type="submit" name="formato" value="excel" class="btn-excel">
                    📥 Generar Excel
                </button>
                <button type="submit" name="formato" value="pdf" class="btn-pdf">
                    📄 Generar PDF
                </button>
            </div>
        </form>
    </div>
    <div class="card">
        <div class="card-title">ℹ️ Información de reportes</div>
        <ul style="margin-left: 1.5rem; color: #6b7280; font-size: 14px;">
            <li><strong>📅 Diario:</strong> Selecciona una fecha específica</li>
            <li><strong>📊 Semanal:</strong> Reporte de la semana actual (lunes a domingo)</li>
            <li><strong>📑 Quincenal:</strong> Reporte de la quincena actual</li>
            <li><strong>📈 Mensual:</strong> Reporte del mes actual</li>
            <li><strong>📉 Trimestral:</strong> Reporte del trimestre actual</li>
            <li><strong>📋 Semestral:</strong> Reporte del semestre actual</li>
            <li><strong>🗓️ Anual:</strong> Reporte del año actual</li>
        </ul>
        <p style="margin-top: 1rem; font-size: 13px; color: #9ca3af;">Los reportes incluyen todos los registros de entrada, salida, salida justificada y justificaciones de faltas. Para justificaciones se muestra el motivo y el comprobante.</p>
    </div>
</main>

<script>
document.getElementById('tipo_reporte').addEventListener('change', function() {
    var container = document.getElementById('fechas_container');
    if (this.value === 'diario') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
});
</script>
</body>
</html>