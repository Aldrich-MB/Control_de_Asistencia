<?php
// admin/dashboard.php - Panel principal de administración (PROTEGIDO - solo admins)
require_once '../includes/config.php';

// Forzar zona horaria en MySQL
$pdo->exec("SET time_zone = '-06:00'");

// Verificar que el usuario haya iniciado sesión como administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$admin_usuario = $_SESSION['user_nombre'];

// Obtener estadísticas
$stats = [];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM trabajadores WHERE activo = 1");
    $stats['total_trabajadores'] = $stmt->fetchColumn();
    
    // Registros de hoy (Entrada, Salida, SalidaJustificada)
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fecha_hora) = CURDATE() AND tipo IN ('Entrada','Salida','SalidaJustificada')");
    $stats['registros_hoy'] = $stmt->fetchColumn();
    
    // Entradas hoy
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fecha_hora) = CURDATE() AND tipo = 'Entrada'");
    $stats['entradas_hoy'] = $stmt->fetchColumn();
    
    // Salidas hoy (normal + justificada)
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fecha_hora) = CURDATE() AND tipo IN ('Salida','SalidaJustificada')");
    $stats['salidas_hoy'] = $stmt->fetchColumn();
    
    // Salidas normales hoy
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fecha_hora) = CURDATE() AND tipo = 'Salida'");
    $stats['salidas_normales_hoy'] = $stmt->fetchColumn();
    
    // Salidas justificadas hoy
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE DATE(fecha_hora) = CURDATE() AND tipo = 'SalidaJustificada'");
    $stats['salidas_justificadas_hoy'] = $stmt->fetchColumn();
    
    // Trabajadores en jornada (entrada sin salida)
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT trabajador_id) 
        FROM asistencia 
        WHERE DATE(fecha_hora) = CURDATE() 
        AND tipo = 'Entrada'
        AND trabajador_id NOT IN (
            SELECT DISTINCT trabajador_id 
            FROM asistencia 
            WHERE DATE(fecha_hora) = CURDATE() 
            AND tipo IN ('Salida', 'SalidaJustificada')
        )
    ");
    $stats['en_jornada'] = $stmt->fetchColumn();
    
    // Faltas justificadas (tipo Justificada)
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE tipo = 'Justificada' AND fecha_justificacion = CURDATE()");
    $stats['justificadas_hoy'] = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE tipo = 'Justificada' AND MONTH(fecha_justificacion) = MONTH(CURDATE()) AND YEAR(fecha_justificacion) = YEAR(CURDATE())");
    $stats['justificadas_mes'] = $stmt->fetchColumn();
    
    // Salidas justificadas
    $stmt = $pdo->query("SELECT COUNT(*) FROM asistencia WHERE tipo = 'SalidaJustificada' AND MONTH(fecha_hora) = MONTH(CURDATE()) AND YEAR(fecha_hora) = YEAR(CURDATE())");
    $stats['salidas_justificadas_mes'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    error_log("Error en dashboard.php (stats): " . $e->getMessage());
    $stats = [
        'total_trabajadores' => 0,
        'registros_hoy' => 0,
        'entradas_hoy' => 0,
        'salidas_hoy' => 0,
        'salidas_normales_hoy' => 0,
        'salidas_justificadas_hoy' => 0,
        'en_jornada' => 0,
        'justificadas_hoy' => 0,
        'justificadas_mes' => 0,
        'salidas_justificadas_mes' => 0
    ];
}
// RESUMEN DIARIO 
$resumen_diario = [];
try {
    $sql = "SELECT 
                t.cve,
                CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) as trabajador,
                MAX(CASE WHEN a.tipo = 'Entrada' THEN TIME(a.fecha_hora) END) as hora_entrada,
                MAX(CASE WHEN a.tipo IN ('Salida', 'SalidaJustificada') THEN TIME(a.fecha_hora) END) as hora_salida,
                MAX(CASE WHEN a.tipo = 'SalidaJustificada' THEN 1 ELSE 0 END) as es_salida_justificada
            FROM asistencia a
            JOIN trabajadores t ON a.trabajador_id = t.id
            WHERE DATE(a.fecha_hora) = CURDATE()
                AND a.tipo IN ('Entrada', 'Salida', 'SalidaJustificada')
            GROUP BY t.id
            ORDER BY hora_entrada DESC";
    $stmt = $pdo->query($sql);
    $resumen_diario = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error en dashboard.php (resumen): " . $e->getMessage());
}

// ÚLTIMOS REGISTROS

$ultimos_registros = [];
try {
    $sql = "SELECT 
                a.id,
                t.cve,
                CONCAT(t.nombre, ' ', t.apellidoP, ' ', COALESCE(t.apellidoM, '')) AS nombre_completo,
                c.nombre AS cargo,
                DATE(a.fecha_hora) AS fecha,
                TIME(a.fecha_hora) AS hora,
                a.tipo,
                a.motivo_id,
                a.motivo_otro,
                a.comprobante_path,
                a.fecha_justificacion,
                m.nombre AS motivo_nombre
            FROM asistencia a
            JOIN trabajadores t ON a.trabajador_id = t.id
            LEFT JOIN cargos c ON t.cargo_id = c.id
            LEFT JOIN motivos_justificacion m ON a.motivo_id = m.id
            WHERE a.tipo IN ('Entrada', 'Salida', 'Justificada', 'SalidaJustificada')
            ORDER BY a.fecha_hora DESC 
            LIMIT 15";
    $stmt = $pdo->query($sql);
    $ultimos_registros = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error en dashboard.php (ultimos): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Control de Asistencia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .pill-justificada { background: #fef3c7; color: #92400e; }
        .pill-salida-justificada { background: #ede9fe; color: #5b21b6; border: 1px solid #c4b5fd; }
        .motivo-tooltip { cursor: help; border-bottom: 1px dashed #9ca3af; }
        .btn-comprobante { background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 5px; text-decoration: none; font-size: 12px; display: inline-block; }
        .btn-comprobante:hover { background: #bae6fd; }
        
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--gris-800);
            padding: 4px 8px;
        }
        .nav-toggle:hover { color: var(--azul); }
        
        @media (max-width: 767px) {
            .nav-toggle { display: block; }
            .nav-links {
                display: none;
                width: 100%;
                flex-direction: column;
                align-items: stretch;
                background: #fff;
                padding: 0.5rem 0 1rem;
                border-top: 1px solid var(--gris-200);
                gap: 6px;
            }
            .nav-links.open { display: flex; }
            .nav-links a {
                padding: 10px 14px;
                font-size: 15px;
                width: 100%;
                text-align: center;
                border-radius: 8px;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }
            .stat-card { padding: 0.8rem; }
            .stat-value { font-size: 20px; }
        }
        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-brand">
        <span>📋</span>
        Control de Asistencia
    </div>
    <button class="nav-toggle" onclick="toggleMenuAdmin()" aria-label="Menú">☰</button>
    <div class="nav-links" id="navLinksAdmin">
        <a href="dashboard.php" class="activo">Inicio</a>
        <a href="trabajadores_lista.php">Trabajadores</a>
        <a href="trabajadores_nuevo.php">Nuevo Trabajador</a>
        <a href="nuevo_admin.php">Nuevo Admin</a>
        <a href="reportes.php">Reportes</a>
        <a href="../logout.php" onclick="return confirm('¿Estás seguro de cerrar sesión?')">Salir</a>
    </div>
</nav>

<main class="page page-wide">

    <div style="margin-bottom: 1.5rem;">
        <h1 style="font-size: 24px; font-weight: 600; margin-bottom: 4px;">Bienvenido, <?php echo htmlspecialchars($admin_usuario); ?></h1>
        <p style="color: var(--gris-400);">Resumen de asistencia del día</p>
    </div>

    <!-- ==================== TARJETAS DE ESTADÍSTICAS ==================== -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-label">👥 Trabajadores activos</div><div class="stat-value"><?php echo $stats['total_trabajadores']; ?></div></div>
        <div class="stat-card"><div class="stat-label">📋 Registros hoy</div><div class="stat-value"><?php echo $stats['registros_hoy']; ?></div></div>
        <div class="stat-card"><div class="stat-label">🟢 Entradas hoy</div><div class="stat-value"><?php echo $stats['entradas_hoy']; ?></div></div>
        <div class="stat-card"><div class="stat-label">🔴 Salidas hoy</div><div class="stat-value"><?php echo $stats['salidas_hoy']; ?></div></div>
        <div class="stat-card"><div class="stat-label">⏳ En jornada</div><div class="stat-value"><?php echo $stats['en_jornada']; ?></div><div class="stat-sub">Entraron, no salieron</div></div>
        
        <!-- Faltas justificadas -->
        <div class="stat-card" style="background: #fef3c7;">
            <div class="stat-label">📝 Faltas justificadas hoy</div>
            <div class="stat-value" style="color:#92400e;"><?php echo $stats['justificadas_hoy']; ?></div>
        </div>
        <div class="stat-card" style="background: #fef3c7;">
            <div class="stat-label">📅 Justificadas en el mes</div>
            <div class="stat-value" style="color:#92400e;"><?php echo $stats['justificadas_mes']; ?></div>
        </div>
        
        <!-- Salidas justificadas -->
        <div class="stat-card" style="background: #ede9fe;">
            <div class="stat-label">⏳ Salidas justificadas hoy</div>
            <div class="stat-value" style="color:#5b21b6;"><?php echo $stats['salidas_justificadas_hoy']; ?></div>
        </div>
        <div class="stat-card" style="background: #ede9fe;">
            <div class="stat-label">📅 Salidas justificadas en mes</div>
            <div class="stat-value" style="color:#5b21b6;"><?php echo $stats['salidas_justificadas_mes']; ?></div>
        </div>
    </div>

    <!-- ==================== RESUMEN DIARIO ==================== -->
    <div class="card">
        <div class="card-title">📊 Resumen de asistencia - Hoy</div>
        <?php if (empty($resumen_diario)): ?>
            <p style="color: #9ca3af; text-align: center; padding: 1rem;">No hay registros de asistencia hoy</p>
        <?php else: ?>
            <div class="tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>CVE</th>
                            <th>Trabajador</th>
                            <th>Hora Entrada</th>
                            <th>Hora Salida</th>
                            <th>Tipo Salida</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resumen_diario as $row): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['cve']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['trabajador']); ?></td>
                            <td>
                                <?php 
                                    if (!empty($row['hora_entrada'])) {
                                        echo '<span class="pill pill-verde">' . $row['hora_entrada'] . '</span>';
                                    } else {
                                        echo '<span class="pill pill-rojo">--:--</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($row['hora_salida'])) {
                                        if ($row['es_salida_justificada'] == 1) {
                                            echo '<span class="pill pill-salida-justificada">' . $row['hora_salida'] . '</span>';
                                        } else {
                                            echo '<span class="pill pill-azul">' . $row['hora_salida'] . '</span>';
                                        }
                                    } else {
                                        echo '<span class="pill pill-rojo">--:--</span>';
                                    }
                                ?>
                            </td>
                            <td>
                                <?php 
                                    if (!empty($row['hora_salida'])) {
                                        if ($row['es_salida_justificada'] == 1) {
                                            echo '<span class="pill pill-salida-justificada">Justificada</span>';
                                        } else {
                                            echo '<span class="pill pill-azul">Normal</span>';
                                        }
                                    } else {
                                        echo '--';
                                    }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ==================== ÚLTIMOS REGISTROS ==================== -->
    <div class="card">
        <div class="card-title">🕐 Últimos registros</div>
        <?php if (empty($ultimos_registros)): ?>
            <p style="color: #9ca3af; text-align: center; padding: 1rem;">No hay registros aún</p>
        <?php else: ?>
            <div class="tabla-wrap">
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimos_registros as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['cve']); ?></td>
                            <td><?php echo htmlspecialchars($row['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($row['cargo'] ?? 'N/A'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                            <td><?php echo $row['hora']; ?></td>
                            <td>
                                <?php if ($row['tipo'] == 'Entrada'): ?>
                                    <span class="pill pill-verde">Entrada</span>
                                <?php elseif ($row['tipo'] == 'Salida'): ?>
                                    <span class="pill pill-rojo">Salida</span>
                                <?php elseif ($row['tipo'] == 'SalidaJustificada'): ?>
                                    <span class="pill pill-salida-justificada">Salida Justificada</span>
                                <?php else: ?>
                                    <span class="pill pill-justificada">Justificada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $motivo = '';
                                    if ($row['tipo'] == 'Justificada' || $row['tipo'] == 'SalidaJustificada') {
                                        if ($row['motivo_nombre']) {
                                            $motivo = $row['motivo_nombre'];
                                        } elseif ($row['motivo_otro']) {
                                            $motivo = 'Otro: ' . $row['motivo_otro'];
                                        } else {
                                            $motivo = 'Sin especificar';
                                        }
                                    }
                                ?>
                                <?php if ($motivo): ?>
                                    <span class="motivo-tooltip" title="Fecha de falta: <?php echo date('d/m/Y', strtotime($row['fecha_justificacion'] ?? $row['fecha'])); ?>">
                                        📝 <?php echo htmlspecialchars($motivo); ?>
                                    </span>
                                <?php else: ?>
                                    --
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($row['tipo'] == 'Justificada' || $row['tipo'] == 'SalidaJustificada') && !empty($row['comprobante_path'])): ?>
                                    <a href="../<?php echo htmlspecialchars($row['comprobante_path']); ?>" target="_blank" class="btn-comprobante">📎 Ver comprobante</a>
                                <?php else: ?>
                                    --
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <a href="trabajadores_nuevo.php" class="btn btn-secondary" style="text-align: center; justify-content: center;">👤 Registrar nuevo trabajador</a>
        <a href="reportes.php" class="btn btn-primary" style="text-align: center; justify-content: center;">📄 Generar reportes Excel</a>
    </div>

</main>

<script>
function toggleMenuAdmin() {
    document.getElementById('navLinksAdmin').classList.toggle('open');
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('navLinksAdmin').querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            document.getElementById('navLinksAdmin').classList.remove('open');
        });
    });
});

document.addEventListener('click', function(event) {
    var nav = document.querySelector('.nav');
    var toggle = document.querySelector('.nav-toggle');
    var menu = document.getElementById('navLinksAdmin');
    
    if (!nav.contains(event.target) && menu.classList.contains('open')) {
        menu.classList.remove('open');
    }
});
</script>
</body>
</html>