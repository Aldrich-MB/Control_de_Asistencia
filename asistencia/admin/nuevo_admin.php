<?php
// Registrar nuevos administradores
require_once '../includes/config.php';

// Verificar que el usuario actual es administrador
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_usuario'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';
    
    // Validaciones
    if (empty($usuario) || empty($password)) {
        $error = 'Usuario y contraseña son obligatorios';
    } elseif (strlen($usuario) < 3) {
        $error = 'El usuario debe tener al menos 3 caracteres';
    } elseif (strlen($password) < 8) {  // Mínimo 8 caracteres
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } elseif ($password !== $confirmar_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        try {
            // Verificar si el usuario ya existe
            $stmt_check = $pdo->prepare("SELECT id FROM admins WHERE usuario = :usuario");
            $stmt_check->execute([':usuario' => $usuario]);
            
            if ($stmt_check->fetch()) {
                $error = "El usuario '$usuario' ya existe";
            } else {
                // Crear hash de la contraseña
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertar nuevo administrador (usando columna "password")
                $sql = "INSERT INTO admins (usuario, password, created_at) 
                        VALUES (:usuario, :password, NOW())";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':usuario' => $usuario,
                    ':password' => $password_hash
                ]);
                
                $success = "Administrador '$usuario' creado correctamente";
                
                // Limpiar formulario
                $_POST = [];
            }
        } catch (PDOException $e) {
            error_log("Error en nuevo_admin.php: " . $e->getMessage());
            $error = 'Error al crear el administrador';
        }
    }
}

// Obtener lista de administradores existentes
$admins = [];
try {
    $stmt = $pdo->query("SELECT id, usuario, created_at FROM admins ORDER BY id DESC");
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_lista = "Error al cargar administradores";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<!-- NAVEGACIÓN -->
<nav class="nav">
    <div class="nav-brand">
        <span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
            </svg>
        </span>
        Control de Asistencia
    </div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="nuevo_admin.php" class="activo">Nuevo Admin</a>
        <a href="reportes.php">Reportes</a>
        <a href="logout.php">Salir</a>
    </div>
</nav>

<main class="page page-wide">

    <div class="card">
        <div class="card-title">➕ Registrar nuevo administrador</div>
        
        <?php if ($error): ?>
            <div class="alert alert-error show" style="display:flex; margin-bottom: 1rem;">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success show" style="display:flex; margin-bottom: 1rem;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="campo">
                <label for="usuario">Usuario *</label>
                <input type="text" id="usuario" name="usuario" placeholder="Ej: admin2" required 
                       value="<?php echo htmlspecialchars($_POST['usuario'] ?? ''); ?>">
                <div class="hint">Mínimo 3 caracteres</div>
            </div>
            
            <div class="campo">
                <label for="password">Contraseña *</label>
                <input type="password" id="password" name="password" placeholder="Mínimo 8 caracteres" required>
                <div class="hint">La contraseña debe tener al menos 8 caracteres</div>
            </div>
            
            <div class="campo">
                <label for="confirmar_password">Confirmar contraseña *</label>
                <input type="password" id="confirmar_password" name="confirmar_password" placeholder="Repite la contraseña" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"/>
                    <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/>
                </svg>
                Crear administrador
            </button>
        </form>
    </div>
    
    <!-- Lista de administradores existentes -->
    <div class="card">
        <div class="card-title">📋 Administradores registrados</div>
        
        <?php if (empty($admins)): ?>
            <p style="color: #9ca3af; text-align: center; padding: 1rem;">No hay administradores registrados</p>
        <?php else: ?>
            <div class="tabla-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Fecha de creación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?php echo $admin['id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['usuario']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($admin['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</main>

</body>
</html>