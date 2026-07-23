<?php
// Login unificado para trabajadores y administradores
require_once 'includes/config.php';

// Mostrar mensaje de logout si viene de cerrar sesión
$logout_mensaje = '';
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logout_mensaje = urldecode($_GET['mensaje'] ?? 'Has cerrado sesión correctamente.');
}

// Si ya hay sesión activa, redirigir según el rol
if (isset($_SESSION['user_id']) && isset($_SESSION['user_rol'])) {
    if ($_SESSION['user_rol'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Control de Asistencia</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Estilos específicos para el login */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #662036 0%, #662036 100%);
            padding: 1rem;
        }
        
        .login-card-custom {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            font-size: 14px;
            color: #6b7280;
        }
        
        .login-logo {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #662036 0%, #662036 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .login-logo svg {
            width: 35px;
            height: 35px;
            fill: white;
        }
        
        .campo-input {
            margin-bottom: 1.5rem;
        }
        
        .campo-input label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .campo-input input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .campo-input input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .nombre-preview {
            margin-top: 0.5rem;
            padding: 10px 12px;
            background: #f3f4f6;
            border-radius: 10px;
            font-size: 14px;
            color: #374151;
            display: none;
            align-items: center;
            gap: 8px;
        }
        
        .nombre-preview.show {
            display: flex;
        }
        
        .nombre-preview .avatar {
            width: 24px;
            height: 24px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #662036 0%, #662036 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .footer-login {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }
        
        .alert-custom {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 14px;
        }
        
        .alert-error-custom {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success-custom {
            background: #def7ec;
            color: #057a55;
            border: 1px solid #84e1bc;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card-custom">
            <div class="login-header">
                <div class="login-logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/>
                    </svg>
                </div>
                <h1>Control de Asistencia</h1>
                <p>Ingresa tus credenciales para continuar</p>
            </div>
            
            <!-- Mensaje de logout (éxito) -->
            <?php if ($logout_mensaje): ?>
                <div class="alert-custom alert-success-custom">
                     <?php echo htmlspecialchars($logout_mensaje); ?>
                </div>
            <?php endif; ?>
            
            <div id="alerta-error" class="alert-custom alert-error-custom" style="display: none;"></div>
            
            <form id="loginForm">
                <div class="campo-input">
                    <label for="identificador">📋 CVE / Usuario</label>
                    <input type="text" id="identificador" name="identificador" 
                           placeholder="Ej: TRAB001 o admin" autocomplete="off" required>
                    <div id="nombre-preview" class="nombre-preview">
                        <span class="avatar">👤</span>
                        <span id="nombre-texto"></span>
                    </div>
                </div>
                
                <div class="campo-input">
                    <label for="password">🔒 Contraseña</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Ingresa tu contraseña" required>
                </div>
                
                <button type="submit" id="btn-login" class="btn-login">
                    Iniciar sesión
                </button>
            </form>
            
            <div class="footer-login">
                <p>© 2026 - Sistema de Control de Asistencia</p>
                <p style="margin-top: 5px;">Todos los derechos reservados</p>
            </div>
        </div>
    </div>
    
    <script>
        // Variables
        const identificadorInput = document.getElementById('identificador');
        const nombrePreview = document.getElementById('nombre-preview');
        const nombreTexto = document.getElementById('nombre-texto');
        const passwordInput = document.getElementById('password');
        const loginForm = document.getElementById('loginForm');
        const btnLogin = document.getElementById('btn-login');
        const alertaError = document.getElementById('alerta-error');
        
        let timeoutBusqueda;
        let ultimoIdentificador = '';
        
        // Mostrar error
        function mostrarError(mensaje) {
            alertaError.textContent = mensaje;
            alertaError.style.display = 'block';
            setTimeout(() => {
                alertaError.style.display = 'none';
            }, 4000);
        }
        
        // Ocultar preview
        function ocultarPreview() {
            nombrePreview.classList.remove('show');
            nombreTexto.textContent = '';
        }
        
        // Mostrar preview con nombre
        function mostrarPreview(nombre) {
            nombreTexto.textContent = nombre;
            nombrePreview.classList.add('show');
        }
        
        // Verificar identificador en tiempo real
        identificadorInput.addEventListener('input', function() {
            const identificador = this.value.trim();
            
            if (timeoutBusqueda) clearTimeout(timeoutBusqueda);
            
            if (identificador.length === 0) {
                ocultarPreview();
                return;
            }
            
            if (identificador.length < 3) {
                ocultarPreview();
                return;
            }
            
            if (identificador === ultimoIdentificador) return;
            ultimoIdentificador = identificador;
            
            timeoutBusqueda = setTimeout(async () => {
                try {
                    const response = await fetch('api/login_verificar.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ identificador: identificador })
                    });
                    
                    const resultado = await response.json();
                    
                    if (resultado.success && resultado.data.nombre) {
                        mostrarPreview(resultado.data.nombre);
                    } else {
                        ocultarPreview();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    ocultarPreview();
                }
            }, 500);
        });
        
        // Enviar formulario
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const identificador = identificadorInput.value.trim();
            const password = passwordInput.value.trim();
            
            if (!identificador || !password) {
                mostrarError('Por favor, completa todos los campos');
                return;
            }
            
            btnLogin.disabled = true;
            btnLogin.innerHTML = '<span class="spinner"></span> Verificando...';
            
            try {
                const response = await fetch('api/login_autenticar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        identificador: identificador, 
                        password: password 
                    })
                });
                
                const resultado = await response.json();
                
                if (resultado.success) {
                    window.location.href = resultado.redirect;
                } else {
                    mostrarError(resultado.message);
                    btnLogin.disabled = false;
                    btnLogin.innerHTML = 'Iniciar sesión';
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de conexión. Intenta nuevamente.');
                btnLogin.disabled = false;
                btnLogin.innerHTML = 'Iniciar sesión';
            }
        });
        
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loginForm.dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>