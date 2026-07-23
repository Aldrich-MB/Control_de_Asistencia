<?php
// Formulario para registrar nuevos trabajadores (solo admins)
require_once '../includes/config.php';

// Verificar que el usuario es administrador
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    header('Location: ../login.php');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cve = trim($_POST['cve'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidoP = trim($_POST['apellidoP'] ?? '');
    $apellidoM = trim($_POST['apellidoM'] ?? '');
    $password = $_POST['password'] ?? '';
    $cargo_id = (int)($_POST['cargo_id'] ?? 0);
    $nuevo_cargo = trim($_POST['nuevo_cargo'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // Validaciones
    if (empty($cve) || empty($nombre) || empty($apellidoP)) {
        $mensaje = "Los campos CVE, Nombre y Apellido Paterno son obligatorios";
        $tipo_mensaje = "error";
    } elseif (empty($password)) {
        $mensaje = "La contraseña es obligatoria";
        $tipo_mensaje = "error";
    } elseif (strlen($password) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres";
        $tipo_mensaje = "error";
    } else {
        try {
            // Crear nuevo cargo si es necesario
            if (!empty($nuevo_cargo)) {
                $stmt_check = $pdo->prepare("SELECT id FROM cargos WHERE nombre = :nombre");
                $stmt_check->execute([':nombre' => $nuevo_cargo]);
                $cargo_existente = $stmt_check->fetch();
                
                if ($cargo_existente) {
                    $cargo_id = $cargo_existente['id'];
                } else {
                    $stmt_insert = $pdo->prepare("INSERT INTO cargos (nombre) VALUES (:nombre)");
                    $stmt_insert->execute([':nombre' => $nuevo_cargo]);
                    $cargo_id = $pdo->lastInsertId();
                }
            }
            
            if ($cargo_id <= 0) {
                $mensaje = "Selecciona un cargo o escribe uno nuevo";
                $tipo_mensaje = "error";
            } else {
                // Verificar si la CVE ya existe
                $stmt_check = $pdo->prepare("SELECT id FROM trabajadores WHERE cve = :cve");
                $stmt_check->execute([':cve' => $cve]);
                
                if ($stmt_check->fetch()) {
                    $mensaje = "La CVE '$cve' ya está registrada";
                    $tipo_mensaje = "error";
                } else {
                    // Generar hash de la contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertar nuevo trabajador
                    $sql = "INSERT INTO trabajadores (cve, nombre, apellidoP, apellidoM, password, cargo_id, activo) 
                            VALUES (:cve, :nombre, :apellidoP, :apellidoM, :password, :cargo_id, :activo)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':cve' => $cve,
                        ':nombre' => $nombre,
                        ':apellidoP' => $apellidoP,
                        ':apellidoM' => $apellidoM ?: null,
                        ':password' => $password_hash,
                        ':cargo_id' => $cargo_id,
                        ':activo' => $activo
                    ]);
                    
                    $mensaje = "Trabajador registrado correctamente con CVE: $cve";
                    $tipo_mensaje = "success";
                    
                    // Limpiar formulario
                    echo '<script>
                        setTimeout(function() {
                            document.getElementById("form-trabajador").reset();
                        }, 2000);
                    </script>';
                }
            }
        } catch (PDOException $e) {
            $mensaje = "Error al registrar: " . $e->getMessage();
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
    <title>Registrar Nuevo Trabajador - Admin</title>
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
        <a href="trabajadores_nuevo.php" class="activo">Nuevo Trabajador</a>
        <a href="nuevo_admin.php">Nuevo Admin</a>
        <a href="reportes.php">Reportes</a>
        <a href="../logout.php" onclick="return confirm('¿Cerrar sesión?')">Salir</a>
    </div>
</nav>

<main class="page page-wide">

    <div class="card">
        <div class="card-title">📝 Registrar nuevo trabajador</div>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?> show" style="display:flex; margin-bottom: 1rem;">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>
        
        <form id="form-trabajador" method="POST" action="">
            <div class="campo">
                <label for="cve">CVE del trabajador *</label>
                <input type="text" id="cve" name="cve" placeholder="Ej: TRAB001" maxlength="20" required 
                       value="<?php echo htmlspecialchars($_POST['cve'] ?? ''); ?>">
                <div class="hint">Identificador único para marcar asistencia</div>
            </div>
            
            <div class="campo">
                <label for="nombre">Nombre(s) *</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ej: Juan" required 
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>">
            </div>
            
            <div class="campo">
                <label for="apellidoP">Apellido Paterno *</label>
                <input type="text" id="apellidoP" name="apellidoP" placeholder="Ej: Pérez" required 
                       value="<?php echo htmlspecialchars($_POST['apellidoP'] ?? ''); ?>">
            </div>
            
            <div class="campo">
                <label for="apellidoM">Apellido Materno</label>
                <input type="text" id="apellidoM" name="apellidoM" placeholder="Ej: García (opcional)" 
                       value="<?php echo htmlspecialchars($_POST['apellidoM'] ?? ''); ?>">
            </div>
            
            <div class="campo">
                <label for="password">🔒 Contraseña *</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required minlength="8">
                <div class="hint">Mínimo 8 caracteres. El trabajador usará esta contraseña para iniciar sesión.</div>
            </div>
            
            <div class="campo">
                <label for="cargo_id">Cargo *</label>
                <select id="cargo_id" name="cargo_id" onchange="mostrarNuevoCargo(this)">
                    <option value="">-- Selecciona un cargo --</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>" 
                            <?php echo (($_POST['cargo_id'] ?? '') == $cargo['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cargo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                    <option value="nuevo">+ Agregar nuevo cargo...</option>
                </select>
            </div>
            
            <div id="div_nuevo_cargo" class="campo" style="display: none;">
                <label for="nuevo_cargo">Nuevo cargo</label>
                <input type="text" id="nuevo_cargo" name="nuevo_cargo" placeholder="Ej: Gerente">
            </div>
            
            <div class="campo">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="activo" value="1" checked style="width: auto;">
                    <span>Trabajador activo</span>
                </label>
            </div>
            
            <hr class="div">
            
            <div style="display: flex; gap: 12px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Registrar trabajador</button>
                <a href="trabajadores_lista.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

</main>

<script>
function mostrarNuevoCargo(select) {
    var div = document.getElementById('div_nuevo_cargo');
    var input = document.getElementById('nuevo_cargo');
    if (select.value === 'nuevo') {
        div.style.display = 'block';
        input.required = true;
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}
</script>
</body>
</html>