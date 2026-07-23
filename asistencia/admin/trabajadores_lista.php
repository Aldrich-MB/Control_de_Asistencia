<?php
// Lista de trabajadores con CRUD completo
require_once '../includes/config.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar acciones
$accion = $_GET['accion'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($accion && $id) {
    try {
        if ($accion === 'desactivar') {
            $sql = "UPDATE trabajadores SET activo = 0 WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $mensaje = "Trabajador desactivado correctamente";
            $tipo_mensaje = "success";
        } 
        elseif ($accion === 'activar') {
            $sql = "UPDATE trabajadores SET activo = 1 WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $mensaje = "Trabajador activado correctamente";
            $tipo_mensaje = "success";
        }
        elseif ($accion === 'eliminar') {
            // 1. Eliminar registros de asistencia del trabajador
            $sql_asistencia = "DELETE FROM asistencia WHERE trabajador_id = :id";
            $stmt_asistencia = $pdo->prepare($sql_asistencia);
            $stmt_asistencia->execute([':id' => $id]);
            
            // 2. Eliminar el trabajador
            $sql_trabajador = "DELETE FROM trabajadores WHERE id = :id";
            $stmt_trabajador = $pdo->prepare($sql_trabajador);
            $stmt_trabajador->execute([':id' => $id]);
            
            $mensaje = "Trabajador eliminado permanentemente";
            $tipo_mensaje = "warning";
        }
    } catch (PDOException $e) {
        $mensaje = "Error: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener lista de trabajadores
$trabajadores = [];
try {
    $sql = "SELECT t.*, c.nombre as cargo_nombre 
            FROM trabajadores t
            LEFT JOIN cargos c ON t.cargo_id = c.id
            ORDER BY t.id DESC";
    $stmt = $pdo->query($sql);
    $trabajadores = $stmt->fetchAll();
} catch (PDOException $e) {
    $mensaje = "Error al cargar trabajadores: " . $e->getMessage();
    $tipo_mensaje = "error";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Trabajadores - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-accion { 
            padding: 4px 10px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            transition: all 0.2s;
            border: 1px solid transparent;
            display: inline-block;
        }
        .btn-accion:hover { opacity: 0.8; }
        
        .btn-editar {
            background: #fef3c7;
            color: #92400e;
            border-color: #fcd34d;
        }
        .btn-editar:hover {
            background: #fde68a;
        }
        
        .btn-desactivar {
            background: #e8f0fe;
            color: #1a56db;
            border-color: #93c5fd;
        }
        .btn-desactivar:hover {
            background: #dbeafe;
        }
        
        .btn-activar {
            background: #def7ec;
            color: #057a55;
            border-color: #84e1bc;
        }
        .btn-activar:hover {
            background: #bcf0da;
        }
        
        .btn-eliminar {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fca5a5;
        }
        .btn-eliminar:hover {
            background: #fecaca;
        }
        
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
        }
    </style>
</head>
<body>

<nav class="nav">
    <div class="nav-brand">
        <span><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg></span>
        Control de Asistencia - Admin
    </div>
    
    <button class="nav-toggle" onclick="toggleMenu()" aria-label="Menú">☰</button>
    
    <div class="nav-links" id="navLinks">
        <a href="dashboard.php">Inicio</a>
        <a href="trabajadores_lista.php" class="activo">Trabajadores</a>
        <a href="trabajadores_nuevo.php">Nuevo Trabajador</a>
        <a href="nuevo_admin.php">Nuevo Admin</a>
        <a href="reportes.php">Reportes</a>
        <a href="../logout.php" onclick="return confirm('¿Cerrar sesión?')">Salir</a>
    </div>
</nav>

<main class="page page-wide">

    <div class="card">
        <div class="card-title">Lista de trabajadores</div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> show" style="display:flex; margin-bottom: 1rem;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($trabajadores)): ?>
            <p style="color: #9ca3af; text-align: center; padding: 2rem;">No hay trabajadores registrados.</p>
        <?php else: ?>
            <div class="tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>CVE</th>
                            <th>Nombre completo</th>
                            <th>Cargo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trabajadores as $t): ?>
                            <?php 
                                $nombre_completo = trim($t['nombre'] . ' ' . $t['apellidoP'] . ' ' . ($t['apellidoM'] ?? ''));
                                $activo = $t['activo'] == 1;
                            ?>
                            <tr>
                                <td><?php echo $t['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($t['cve']); ?></strong></td>
                                <td><?php echo htmlspecialchars($nombre_completo); ?></td>
                                <td><?php echo htmlspecialchars($t['cargo_nombre'] ?? 'Sin cargo'); ?></td>
                                <td>
                                    <?php if ($activo): ?>
                                        <span class="pill pill-verde">Activo</span>
                                    <?php else: ?>
                                        <span class="pill pill-rojo">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <a href="trabajadores_editar.php?id=<?php echo $t['id']; ?>" class="btn-accion btn-editar">Editar</a>
                                    
                                    <?php if ($activo): ?>
                                        <a href="?accion=desactivar&id=<?php echo $t['id']; ?>" class="btn-accion btn-desactivar" onclick="return confirm('¿Desactivar este trabajador?')">Desactivar</a>
                                    <?php else: ?>
                                        <a href="?accion=activar&id=<?php echo $t['id']; ?>" class="btn-accion btn-activar" onclick="return confirm('¿Activar este trabajador?')">Activar</a>
                                    <?php endif; ?>
                                    
                                    <a href="?accion=eliminar&id=<?php echo $t['id']; ?>" 
                                       class="btn-accion btn-eliminar" 
                                       onclick="return confirmarEliminar(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($nombre_completo); ?>');">
                                       Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

<script>
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.getElementById('navLinks');
        const links = navLinks.querySelectorAll('a');
        links.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('open');
            });
        });
    });

    function confirmarEliminar(id, nombre) {
        if (!confirm('¿Estas seguro de que deseas eliminar al trabajador "' + nombre + '" (ID: ' + id + ')?\n\nEsta accion es permanente e irreversible.\nSe eliminaran TODOS los registros de asistencia de este trabajador.')) {
            return false;
        }
        if (!confirm('Confirmacion final:\n¿Estas absolutamente seguro de que quieres eliminar a "' + nombre + '"?\nEsta accion NO se puede deshacer.')) {
            return false;
        }
        return true;
    }
</script>
</body>
</html>