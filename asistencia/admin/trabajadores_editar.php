<?php
// Editar datos de un trabajador existente
require_once '../includes/config.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: trabajadores_lista.php?error=ID no válido');
    exit;
}

// Obtener datos del trabajador
$trabajador = null;
try {
    $sql = "SELECT t.*, c.nombre as cargo_nombre 
            FROM trabajadores t
            LEFT JOIN cargos c ON t.cargo_id = c.id
            WHERE t.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $trabajador = $stmt->fetch();
    
    if (!$trabajador) {
        header('Location: trabajadores_lista.php?error=Trabajador no encontrado');
        exit;
    }
} catch (PDOException $e) {
    header('Location: trabajadores_lista.php?error=' . urlencode($e->getMessage()));
    exit;
}

// Obtener lista de cargos
$cargos = [];
try {
    $stmt = $pdo->query("SELECT id, nombre FROM cargos ORDER BY nombre");
    $cargos = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_cargos = "Error al cargar cargos: " . $e->getMessage();
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cve = trim($_POST['cve'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidoP = trim($_POST['apellidoP'] ?? '');
    $apellidoM = trim($_POST['apellidoM'] ?? '');
    $password = $_POST['password'] ?? '';
    $cargo_id = (int)($_POST['cargo_id'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($cve) || empty($nombre) || empty($apellidoP)) {
        $mensaje = "Los campos CVE, Nombre y Apellido Paterno son obligatorios";
        $tipo_mensaje = "error";
    } else {
        try {
            // Verificar si la CVE ya existe en otro trabajador
            $stmt_check = $pdo->prepare("SELECT id FROM trabajadores WHERE cve = :cve AND id != :id");
            $stmt_check->execute([':cve' => $cve, ':id' => $id]);
            
            if ($stmt_check->fetch()) {
                $mensaje = "La CVE '$cve' ya está registrada en otro trabajador";
                $tipo_mensaje = "error";
            } else {
                // Construir UPDATE dinámico 
                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        $mensaje = "La contraseña debe tener al menos 8 caracteres";
                        $tipo_mensaje = "error";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $sql = "UPDATE trabajadores 
                                SET cve = :cve, nombre = :nombre, apellidoP = :apellidoP, 
                                    apellidoM = :apellidoM, password = :password, 
                                    cargo_id = :cargo_id, activo = :activo 
                                WHERE id = :id";
                        $params = [
                            ':cve' => $cve,
                            ':nombre' => $nombre,
                            ':apellidoP' => $apellidoP,
                            ':apellidoM' => $apellidoM ?: null,
                            ':password' => $password_hash,
                            ':cargo_id' => $cargo_id,
                            ':activo' => $activo,
                            ':id' => $id
                        ];
                    }
                } else {
                    // No actualizar contraseña
                    $sql = "UPDATE trabajadores 
                            SET cve = :cve, nombre = :nombre, apellidoP = :apellidoP, 
                                apellidoM = :apellidoM, cargo_id = :cargo_id, activo = :activo 
                            WHERE id = :id";
                    $params = [
                        ':cve' => $cve,
                        ':nombre' => $nombre,
                        ':apellidoP' => $apellidoP,
                        ':apellidoM' => $apellidoM ?: null,
                        ':cargo_id' => $cargo_id,
                        ':activo' => $activo,
                        ':id' => $id
                    ];
                }
                
                if (!isset($mensaje) || $tipo_mensaje !== "error") {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $mensaje = "Datos actualizados correctamente";
                    $tipo_mensaje = "success";
                    
                    // Recargar datos actualizados
                    $stmt = $pdo->prepare("SELECT * FROM trabajadores WHERE id = :id");
                    $stmt->execute([':id' => $id]);
                    $trabajador = $stmt->fetch();
                }
            }
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Trabajador - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav class="nav">
    <div class="nav-brand">
        <span><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg></span>
        Control de Asistencia - Admin
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Inicio</a>
        <a href="trabajadores_lista.php">Trabajadores</a>
        <a href="trabajadores_nuevo.php">Nuevo Trabajador</a>
        <a href="nuevo_admin.php">Nuevo Admin</a>
        <a href="reportes.php">Reportes</a>
        <a href="../logout.php" onclick="return confirm('¿Cerrar sesión?')">Salir</a>
    </div>
</nav>

<main class="page page-wide">

    <div class="card">
        <div class="card-title">✏️ Editar trabajador</div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> show" style="display:flex; margin-bottom: 1rem;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="campo">
                <label for="cve">CVE del trabajador *</label>
                <input type="text" id="cve" name="cve" placeholder="Ej: TRAB001" maxlength="20" required 
                       value="<?php echo htmlspecialchars($trabajador['cve']); ?>">
                <div class="hint">Identificador único para marcar asistencia</div>
            </div>
            
            <div class="campo">
                <label for="nombre">Nombre(s) *</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan" required 
                       value="<?php echo htmlspecialchars($trabajador['nombre']); ?>">
            </div>
            
            <div class="campo">
                <label for="apellidoP">Apellido Paterno *</label>
                <input type="text" id="apellidoP" name="apellidoP" placeholder="Ej: Pérez" required 
                       value="<?php echo htmlspecialchars($trabajador['apellidoP']); ?>">
            </div>
            
            <div class="campo">
                <label for="apellidoM">Apellido Materno</label>
                <input type="text" id="apellidoM" name="apellidoM" placeholder="Ej: García (opcional)" 
                       value="<?php echo htmlspecialchars($trabajador['apellidoM'] ?? ''); ?>">
            </div>
            
            <div class="campo">
                <label for="password">🔒 Nueva contraseña (opcional)</label>
                <input type="password" id="password" name="password" placeholder="Déjalo vacío para no cambiar" minlength="8">
                <div class="hint">Mínimo 8 caracteres. Solo si quieres cambiar la contraseña actual.</div>
            </div>
            
            <div class="campo">
                <label for="cargo_id">Cargo *</label>
                <select id="cargo_id" name="cargo_id" required>
                    <option value="">-- Selecciona un cargo --</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>" 
                            <?php echo ($trabajador['cargo_id'] == $cargo['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cargo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="campo">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="activo" value="1" <?php echo $trabajador['activo'] == 1 ? 'checked' : ''; ?> style="width: auto;">
                    <span>Trabajador activo</span>
                </label>
                <div class="hint">Si está inactivo, no podrá registrar asistencia</div>
            </div>
            
            <hr class="div">
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">💾 Guardar cambios</button>
                <a href="trabajadores_lista.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

</main>
</body>
</html>