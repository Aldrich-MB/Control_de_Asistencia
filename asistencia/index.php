<?php
// Registro de Asistencia (solo trabajadores autenticados)
require_once 'includes/config.php';

// Verificar que el usuario haya iniciado sesión como trabajador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'trabajador') {
    header('Location: login.php');
    exit;
}

$nombre_trabajador = $_SESSION['user_nombre'];
$cve_trabajador = $_SESSION['user_cve'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Registro de Asistencia</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e8f0fe;
            padding: 6px 14px;
            border-radius: 30px;
            margin-left: 15px;
        }
        .user-info span {
            font-size: 13px;
            font-weight: 500;
            color: #1a56db;
        }
        .btn-logout-header {
            background: #fee2e2;
            color: #dc2626;
            padding: 6px 14px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-logout-header:hover {
            background: #fecaca;
        }
        .btn-cambiar-password {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 14px;
            border-radius: 7px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            font-family: inherit;
        }
        .btn-cambiar-password:hover {
            background: #fde68a;
        }
        /* Estilos para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(3px);
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 25px;
            border-radius: 16px;
            width: 90%;
            max-width: 420px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
            color: #1f2937;
        }
        .close-modal {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #9ca3af;
            transition: 0.2s;
        }
        .close-modal:hover {
            color: #dc2626;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #1a56db;
            box-shadow: 0 0 0 3px rgba(26,86,219,0.1);
        }
        .form-group .hint {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        .btn-modal {
            background: #1a56db;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: 0.2s;
        }
        .btn-modal:hover {
            background: #1e429f;
        }
        .btn-modal:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }
        .btn-modal-cancel {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: 0.2s;
            margin-top: 8px;
        }
        .btn-modal-cancel:hover {
            background: #d1d5db;
        }
        /* Grid de 4 botones */
        .tipo-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .tipo-btn {
            padding: 16px 8px;
            border-radius: var(--radio-lg);
            border: 2px solid var(--gris-200);
            background: #fff;
            cursor: pointer;
            text-align: center;
            transition: all .15s;
            font-family: 'DM Sans', sans-serif;
            min-height: 80px;
        }
        .tipo-btn:hover { border-color: var(--azul); background: var(--azul-light); }
        .tipo-btn .tipo-icon { font-size: 26px; display: block; margin-bottom: 6px; }
        .tipo-btn .tipo-label { font-size: 14px; font-weight: 600; color: var(--gris-800); display: block; }
        .tipo-btn .tipo-sub { font-size: 11px; color: var(--gris-400); }
        .tipo-btn.entrada-btn:hover { border-color: var(--verde); background: var(--verde-light); }
        .tipo-btn.salida-btn:hover  { border-color: var(--rojo); background: var(--rojo-light); }
        .tipo-btn.justificar-btn:hover { border-color: #f59e0b; background: var(--amber-light); }
        .tipo-btn.salida-justificada-btn {
            border-color: #e5e7eb  ;
            background: #fff;
        }
        .tipo-btn.salida-justificada-btn:hover {
            border-color: #00B8FC;
            background: #A0EBFA;
        }

        /* Ajustes responsive */
        .nav-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: var(--gris-800);
            padding: 4px 8px;
        }
        .nav-toggle:hover {
            color: var(--azul);
        }
        @media (max-width: 767px) {
            .tipo-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .nav-toggle {
                display: block;
            }
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
            .nav-links.open {
                display: flex;
            }
            .nav-links a,
            .nav-links .btn-cambiar-password,
            .nav-links .btn-logout-header {
                padding: 10px 14px;
                font-size: 15px;
                width: 100%;
                text-align: center;
                border-radius: 8px;
            }
            .nav-links .user-info {
                justify-content: center;
                padding: 6px 10px;
                margin: 4px 0;
            }
        }
        @media (max-width: 480px) {
            .tipo-grid {
                grid-template-columns: 1fr 1fr;
                gap: 6px;
            }
            .tipo-btn {
                min-height: 60px;
                padding: 10px 6px;
            }
            .tipo-btn .tipo-icon { font-size: 20px; }
            .tipo-btn .tipo-label { font-size: 12px; }
            .tipo-btn .tipo-sub { font-size: 10px; }
        }
    </style>
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
    
    <button class="nav-toggle" onclick="toggleMenu()" aria-label="Menú">☰</button>
    
    <div class="nav-links" id="navLinks">
        <a href="index.php" class="activo">Registro</a>
        <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($nombre_trabajador); ?></span>
            <span>📋 <?php echo htmlspecialchars($cve_trabajador); ?></span>
        </div>
        <button class="btn-cambiar-password" onclick="abrirModalCambiarPassword()">🔑 Cambiar contraseña</button>
        <a href="logout.php" class="btn-logout-header" onclick="return confirmarCerrarSesion();">🔓 Cerrar sesión</a>
    </div>
</nav>

<!-- CONTENIDO PRINCIPAL -->
<main class="page">

    <!-- RELOJ -->
    <div class="card">
        <div class="card-title">Fecha y hora actual</div>
        <div class="reloj-grande" id="reloj">--:--:--</div>
        <div class="fecha-txt" id="fecha-hoy">cargando...</div>
    </div>

    <!-- DATOS DEL TRABAJADOR -->
    <div class="card">
        <div class="card-title">Datos del trabajador</div>

        <div class="campo">
            <label for="cve">CVE del trabajador</label>
            <input type="text" id="cve" placeholder="<?php echo htmlspecialchars($cve_trabajador); ?>" readonly>
        </div>

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input type="text" id="nombre" value="<?php echo htmlspecialchars($nombre_trabajador); ?>" readonly>
        </div>

        <div class="campo">
            <label for="cargo">Cargo</label>
            <input type="text" id="cargo" placeholder="Cargando..." readonly>
        </div>
    </div>

    <!-- VERIFICACIÓN DE UBICACIÓN -->
    <div class="card">
        <div class="card-title">Verificación de ubicación</div>
        <p style="font-size:13px; color:#6b7280; margin-bottom:1rem;">
            El sistema registra tu ubicación GPS para validar que te encuentras en la oficina.
            Solo se permite el registro dentro de un radio de 10m</strong>.
        </p>
        <button class="btn btn-secondary" id="btn-geo" onclick="verificarUbicacion()">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; margin-right:6px;">
                <circle cx="12" cy="12" r="3"/>
                <path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
            </svg>
            Verificar ubicación
        </button>
        <div style="margin-top: 12px;">
            <div id="geo-status" class="geo-badge geo-wait">
                <span class="geo-dot dot-wait"></span> Pendiente de verificación
            </div>
        </div>
    </div>

    <!-- TIPO DE REGISTRO (GRID DE 4 BOTONES) -->
    <div class="card">
        <div class="card-title">Tipo de registro</div>

        <div class="tipo-grid">
            <button class="tipo-btn entrada-btn" onclick="registrar('entrada')">
                <span class="tipo-icon">🟢</span>
                <span class="tipo-label">Entrada</span>
                <span class="tipo-sub">Inicio de jornada</span>
            </button>
            <button class="tipo-btn salida-btn" onclick="registrar('salida')">
                <span class="tipo-icon">🔴</span>
                <span class="tipo-label">Salida</span>
                <span class="tipo-sub">Fin de jornada</span>
            </button>
            <button class="tipo-btn justificar-btn" onclick="abrirModalJustificar('falta')">
                <span class="tipo-icon">📝</span>
                <span class="tipo-label">Justificar</span>
                <span class="tipo-sub">Falta completa</span>
            </button>
            <button class="tipo-btn salida-justificada-btn" onclick="abrirModalJustificar('salida')">
                <span class="tipo-icon">⏳</span>
                <span class="tipo-label">Salida</span>
                <span class="tipo-sub">Justificada</span>
            </button>
        </div>

        <div id="alerta-registro" class="alert" style="display:none;"></div>
    </div>

</main>

<!-- MODAL PARA JUSTIFICAR (FALTA O SALIDA JUSTIFICADA) -->
<div id="modalJustificar" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalJustificarTitulo">📝 Justificar</h3>
            <span class="close-modal" onclick="cerrarModalJustificar()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="alerta-justificacion" class="alert" style="display:none;"></div>
            
            <!-- Campo oculto para saber el tipo -->
            <input type="hidden" id="tipo_justificacion" value="falta">
            
            <div class="form-group">
                <label for="fecha_falta">Fecha de la falta *</label>
                <input type="date" id="fecha_falta" name="fecha_falta" required>
            </div>
            <div class="form-group">
                <label for="motivo_id">Motivo *</label>
                <select id="motivo_id" name="motivo_id" required>
                    <option value="">-- Cargando motivos --</option>
                </select>
            </div>
            <div class="form-group" id="otro_motivo_group" style="display:none;">
                <label for="motivo_otro">Especificar motivo</label>
                <textarea id="motivo_otro" rows="2" placeholder="Describe el motivo..."></textarea>
            </div>
            <div class="form-group">
                <label for="comprobante">Comprobante (opcional)</label>
                <input type="file" id="comprobante" accept=".pdf,.jpg,.jpeg,.png,.gif">
                <div class="hint">Formatos: PDF, JPG, PNG (máx 5 MB)</div>
            </div>
            <button class="btn-modal" id="btnGuardarJustificacion" onclick="guardarJustificacion()">Guardar justificación</button>
        </div>
    </div>
</div>

<!-- MODAL PARA CAMBIAR CONTRASEÑA -->
<div id="modalCambiarPassword" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3>🔑 Cambiar contraseña</h3>
            <span class="close-modal" onclick="cerrarModalCambiarPassword()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="alerta-cambio" class="alert" style="display:none;"></div>
            
            <div class="form-group">
                <label for="password_actual">Contraseña actual *</label>
                <input type="password" id="password_actual" placeholder="Ingresa tu contraseña actual" required>
            </div>
            <div class="form-group">
                <label for="password_nueva">Nueva contraseña *</label>
                <input type="password" id="password_nueva" placeholder="Mínimo 8 caracteres" required minlength="8">
                <div class="hint">Mínimo 8 caracteres</div>
            </div>
            <div class="form-group">
                <label for="password_confirmar">Confirmar nueva contraseña *</label>
                <input type="password" id="password_confirmar" placeholder="Repite la nueva contraseña" required>
            </div>
            <button class="btn-modal" id="btnCambiarPassword" onclick="cambiarPassword()">Guardar cambios</button>
            <button class="btn-modal-cancel" onclick="cerrarModalCambiarPassword()">Cancelar</button>
        </div>
    </div>
</div>

<script>
    // CVE del trabajador desde sesión
    const trabajadorCVE = '<?php echo $cve_trabajador; ?>';
    let trabajadorActual = null;
    let ubicacionActual = null;
    let ubicacionVerificada = false;

    // Coordenadas de la oficina
    const OFICINA_LAT = 19.557568149493722;
    const OFICINA_LNG = -99.60861103565107;
    const RADIO_PERMITIDO = 30;

    document.addEventListener('DOMContentLoaded', function() {
        actualizarReloj();
        setInterval(actualizarReloj, 1000);
        cargarDatosTrabajador();
        cargarMotivosJustificacion();
    });

    // ==================== MENÚ HAMBURGUESA ====================
    function toggleMenu() {
        const navLinks = document.getElementById('navLinks');
        navLinks.classList.toggle('open');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.getElementById('navLinks');
        const links = navLinks.querySelectorAll('a, button');
        links.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('open');
            });
        });
    });

    // ==================== RELOJ ====================
    function actualizarReloj() {
        const ahora = new Date();
        const horas = String(ahora.getHours()).padStart(2, '0');
        const minutos = String(ahora.getMinutes()).padStart(2, '0');
        const segundos = String(ahora.getSeconds()).padStart(2, '0');
        const relojElement = document.getElementById('reloj');
        const fechaElement = document.getElementById('fecha-hoy');
        if (relojElement) relojElement.textContent = `${horas}:${minutos}:${segundos}`;
        if (fechaElement) {
            const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const fechaFormateada = ahora.toLocaleDateString('es-MX', opciones);
            fechaElement.textContent = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);
        }
    }

    // ==================== DATOS DEL TRABAJADOR ====================
    async function cargarDatosTrabajador() {
        try {
            const response = await fetch(`api/buscar_trabajador.php?cve=${encodeURIComponent(trabajadorCVE)}`);
            const resultado = await response.json();
            if (resultado.success && resultado.data) {
                trabajadorActual = resultado.data;
                const cargoInput = document.getElementById('cargo');
                if (cargoInput) cargoInput.value = trabajadorActual.cargo || 'Sin cargo';
            }
        } catch (error) {
            console.error('Error al cargar trabajador:', error);
        }
    }

    // ==================== JUSTIFICACIÓN ====================
    async function cargarMotivosJustificacion() {
        try {
            const response = await fetch('api/obtener_motivos.php');
            const resultado = await response.json();
            if (resultado.success && resultado.data.length > 0) {
                const select = document.getElementById('motivo_id');
                select.innerHTML = '<option value="">-- Selecciona un motivo --</option>';
                resultado.data.forEach(motivo => {
                    const option = document.createElement('option');
                    option.value = motivo.id;
                    option.textContent = motivo.nombre;
                    select.appendChild(option);
                });
                const optionOtro = document.createElement('option');
                optionOtro.value = 'otro';
                optionOtro.textContent = '📝 Otro (especificar)';
                select.appendChild(optionOtro);
            }
        } catch (error) {
            console.error('Error al cargar motivos:', error);
        }
    }

    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'motivo_id') {
            const otroGroup = document.getElementById('otro_motivo_group');
            if (e.target.value === 'otro') {
                otroGroup.style.display = 'block';
                document.getElementById('motivo_otro').required = true;
            } else {
                otroGroup.style.display = 'none';
                document.getElementById('motivo_otro').required = false;
            }
        }
    });

    // ==================== ABRIR MODAL DE JUSTIFICACIÓN ====================
    function abrirModalJustificar(tipo) {
        if (!trabajadorActual) {
            mostrarAlerta('alerta-registro', 'Error: Datos del trabajador no cargados', 'error');
            return;
        }

        // Configurar el modal según el tipo
        const titulo = document.getElementById('modalJustificarTitulo');
        const tipoInput = document.getElementById('tipo_justificacion');
        
        if (tipo === 'salida') {
            titulo.textContent = '⏳ Registrar Salida Justificada';
            tipoInput.value = 'salida';
        } else {
            titulo.textContent = '📝 Justificar Falta';
            tipoInput.value = 'falta';
        }

        // Resetear campos
        document.getElementById('fecha_falta').value = new Date().toISOString().split('T')[0];
        document.getElementById('motivo_id').value = '';
        document.getElementById('motivo_otro').value = '';
        document.getElementById('comprobante').value = '';
        document.getElementById('otro_motivo_group').style.display = 'none';
        document.getElementById('alerta-justificacion').style.display = 'none';
        
        // Mostrar modal
        document.getElementById('modalJustificar').style.display = 'block';
    }

    function cerrarModalJustificar() {
        document.getElementById('modalJustificar').style.display = 'none';
    }

    // ==================== GUARDAR JUSTIFICACIÓN ====================
    async function guardarJustificacion() {
        const fechaFalta = document.getElementById('fecha_falta').value;
        const motivoSelect = document.getElementById('motivo_id');
        const motivoId = motivoSelect.value;
        const motivoOtro = document.getElementById('motivo_otro').value.trim();
        const comprobanteFile = document.getElementById('comprobante').files[0];
        const tipoJustificacion = document.getElementById('tipo_justificacion').value;

        if (!fechaFalta) {
            mostrarAlertaModal('Selecciona la fecha de la falta', 'error');
            return;
        }
        if (!motivoId) {
            mostrarAlertaModal('Selecciona un motivo', 'error');
            return;
        }
        if (motivoId === 'otro' && motivoOtro === '') {
            mostrarAlertaModal('Escribe el motivo de la falta', 'error');
            return;
        }

        const btn = document.getElementById('btnGuardarJustificacion');
        btn.disabled = true;
        btn.textContent = 'Enviando...';

        try {
            let comprobantePath = null;
            if (comprobanteFile) {
                const formData = new FormData();
                formData.append('comprobante', comprobanteFile);
                const uploadResp = await fetch('api/subir_comprobante.php', {
                    method: 'POST',
                    body: formData
                });
                const uploadResult = await uploadResp.json();
                if (uploadResult.success) {
                    comprobantePath = uploadResult.path;
                } else {
                    mostrarAlertaModal('Error al subir comprobante: ' + uploadResult.message, 'error');
                    btn.disabled = false;
                    btn.textContent = 'Guardar justificación';
                    return;
                }
            }

            // Construir los datos para la API
            let datos = {
                trabajador_id: trabajadorActual.id,
                fecha_justificacion: fechaFalta,
                motivo_id: (motivoId === 'otro') ? null : parseInt(motivoId),
                motivo_otro: (motivoId === 'otro') ? motivoOtro : null,
                comprobante_path: comprobantePath
            };

            // Determinar el tipo
            if (tipoJustificacion === 'salida') {
                // Salida justificada: necesitamos enviar con tipo 'SalidaJustificada'
                // Para mantener compatibilidad, usamos 'Justificada' con un flag en motivo_otro
                // O podemos usar un campo adicional 'sub_tipo'
                datos.tipo = 'SalidaJustificada';
                // También necesitamos latitud/longitud (GPS) para salida justificada
                if (!ubicacionActual) {
                    mostrarAlertaModal('Primero verifica tu ubicación GPS', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Guardar justificación';
                    return;
                }
                datos.latitud = ubicacionActual.lat;
                datos.longitud = ubicacionActual.lng;
            } else {
                // Falta justificada normal
                datos.tipo = 'Justificada';
                // No necesita GPS
            }

            const response = await fetch('api/registrar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const resultado = await response.json();
            
            if (resultado.success) {
                mostrarAlertaModal('✅ ' + resultado.message, 'success');
                setTimeout(() => {
                    cerrarModalJustificar();
                    window.location.reload();
                }, 1500);
            } else {
                mostrarAlertaModal('❌ ' + resultado.message, 'error');
            }
        } catch (error) {
            console.error('Error al guardar justificación:', error);
            mostrarAlertaModal('❌ Error de conexión al servidor', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Guardar justificación';
        }
    }

    function mostrarAlertaModal(mensaje, tipo) {
        const alerta = document.getElementById('alerta-justificacion');
        alerta.textContent = mensaje;
        alerta.className = `alert alert-${tipo} show`;
        alerta.style.display = 'flex';
        setTimeout(() => {
            alerta.style.display = 'none';
        }, 4000);
    }

    // ==================== CIERRE DE MODALES ====================
    window.onclick = function(event) {
        const modalJustificar = document.getElementById('modalJustificar');
        const modalPassword = document.getElementById('modalCambiarPassword');
        if (event.target === modalJustificar) {
            cerrarModalJustificar();
        }
        if (event.target === modalPassword) {
            cerrarModalCambiarPassword();
        }
    }

    // ==================== UBICACIÓN GPS ====================
    function verificarUbicacion() {
        const geoStatus = document.getElementById('geo-status');
        const btnGeo = document.getElementById('btn-geo');
        if (!navigator.geolocation) {
            actualizarEstadoGeo('error', 'Tu navegador no soporta geolocalización');
            return;
        }
        actualizarEstadoGeo('wait', 'Verificando ubicación...');
        if (btnGeo) btnGeo.disabled = true;
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                ubicacionActual = { lat, lng };
                const distancia = calcularDistancia(lat, lng, OFICINA_LAT, OFICINA_LNG);
                if (distancia <= RADIO_PERMITIDO) {
                    ubicacionVerificada = true;
                    actualizarEstadoGeo('ok', 'Ubicación verificada.');
                } else {
                    ubicacionVerificada = false;
                    actualizarEstadoGeo('error', `Fuera del radio permitido.`);
                }
                if (btnGeo) btnGeo.disabled = false;
            },
            function(error) {
                ubicacionVerificada = false;
                let mensaje = '';
                switch(error.code) {
                    case error.PERMISSION_DENIED: mensaje = 'Permiso de ubicación denegado.'; break;
                    case error.POSITION_UNAVAILABLE: mensaje = 'Ubicación no disponible.'; break;
                    case error.TIMEOUT: mensaje = 'Tiempo de espera agotado.'; break;
                    default: mensaje = 'Error al obtener ubicación.';
                }
                actualizarEstadoGeo('error', `${mensaje}`);
                if (btnGeo) btnGeo.disabled = false;
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    }

    function actualizarEstadoGeo(tipo, mensaje) {
        const geoStatus = document.getElementById('geo-status');
        if (!geoStatus) return;
        let clase = '', icono = '';
        switch(tipo) {
            case 'ok': clase = 'geo-ok'; icono = '<span class="geo-dot dot-ok"></span>'; break;
            case 'error': clase = 'geo-err'; icono = '<span class="geo-dot dot-err"></span>'; break;
            case 'warn': clase = 'geo-warn'; icono = '<span class="geo-dot dot-warn"></span>'; break;
            default: clase = 'geo-wait'; icono = '<span class="geo-dot dot-wait"></span>';
        }
        geoStatus.className = `geo-badge ${clase}`;
        geoStatus.innerHTML = `${icono} ${mensaje}`;
    }

    // ==================== REGISTRAR ENTRADA/SALIDA ====================
    async function registrar(tipo) {
        if (!trabajadorActual) {
            mostrarAlerta('alerta-registro', 'Error: Datos del trabajador no cargados', 'error');
            return;
        }
        if (!ubicacionVerificada || !ubicacionActual) {
            mostrarAlerta('alerta-registro', 'Primero verifica tu ubicación GPS', 'error');
            return;
        }

        // ========== VALIDACIÓN PARA SALIDA ==========
        if (tipo === 'salida') {
            try {
                const response = await fetch(`api/obtener_ultima_entrada.php?trabajador_id=${trabajadorActual.id}`);
                const resultado = await response.json();
                
                if (resultado.success && resultado.data) {
                    const entrada = resultado.data;
                    const fechaEntrada = new Date(entrada.fecha_hora);
                    const ahora = new Date();
                    const diffMs = ahora - fechaEntrada;
                    const diffMinutos = Math.floor(diffMs / 60000);
                    const diffHoras = Math.floor(diffMinutos / 60);
                    const diffMinutosRestantes = diffMinutos % 60;
                    
                    let tiempoTexto = '';
                    if (diffHoras > 0) {
                        tiempoTexto = `${diffHoras} horas y ${diffMinutosRestantes} minutos`;
                    } else {
                        tiempoTexto = `${diffMinutos} minutos`;
                    }
                    
                    if (diffMinutos < 2) {
                        const confirmar = confirm(
                            `⚠️ Has estado en la oficina solo ${tiempoTexto}.\n\n` +
                            `¿Estás SEGURO de que deseas registrar tu SALIDA?\n` +
                            `Si fue un error, cancela y espera unos minutos.`
                        );
                        if (!confirmar) return;
                    } else {
                        const confirmar = confirm(
                            `Llevas ${tiempoTexto} en la oficina.\n\n` +
                            `¿Confirmas tu SALIDA?`
                        );
                        if (!confirmar) return;
                    }
                } else {
                    const confirmar = confirm(
                        `⚠️ No se encontró un registro de ENTRADA hoy.\n\n` +
                        `¿Deseas registrar tu SALIDA de todos modos?`
                    );
                    if (!confirmar) return;
                }
            } catch (error) {
                console.error('Error al verificar entrada:', error);
                const confirmar = confirm(
                    `⚠️ No se pudo verificar tu última entrada.\n\n` +
                    `¿Deseas registrar tu SALIDA de todos modos?`
                );
                if (!confirmar) return;
            }
        }

        // ========== CONTINUAR CON EL REGISTRO ==========
        const botones = document.querySelectorAll('.tipo-btn');
        botones.forEach(btn => btn.disabled = true);
        
        try {
            const datos = {
                trabajador_id: trabajadorActual.id,
                tipo: tipo === 'entrada' ? 'Entrada' : 'Salida',
                latitud: ubicacionActual.lat,
                longitud: ubicacionActual.lng
            };
            const response = await fetch('api/registrar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos)
            });
            const resultado = await response.json();
            if (resultado.success) {
                mostrarAlerta('alerta-registro', `${resultado.message}`, 'success');
                setTimeout(() => window.location.reload(), 2000);
            } else {
                mostrarAlerta('alerta-registro', `${resultado.message}`, 'error');
            }
        } catch (error) {
            console.error('Error al registrar:', error);
            mostrarAlerta('alerta-registro', 'Error de conexión al servidor', 'error');
        } finally {
            setTimeout(() => {
                botones.forEach(btn => btn.disabled = false);
            }, 2000);
        }
    }

    // ==================== CAMBIAR CONTRASEÑA ====================
    function abrirModalCambiarPassword() {
        document.getElementById('modalCambiarPassword').style.display = 'block';
        document.getElementById('password_actual').value = '';
        document.getElementById('password_nueva').value = '';
        document.getElementById('password_confirmar').value = '';
        document.getElementById('alerta-cambio').style.display = 'none';
    }

    function cerrarModalCambiarPassword() {
        document.getElementById('modalCambiarPassword').style.display = 'none';
    }

    async function cambiarPassword() {
        const actual = document.getElementById('password_actual').value;
        const nueva = document.getElementById('password_nueva').value;
        const confirmar = document.getElementById('password_confirmar').value;

        if (!actual || !nueva || !confirmar) {
            mostrarAlertaModalPassword('Todos los campos son obligatorios', 'error');
            return;
        }

        if (nueva.length < 8) {
            mostrarAlertaModalPassword('La nueva contraseña debe tener al menos 8 caracteres', 'error');
            return;
        }

        if (nueva !== confirmar) {
            mostrarAlertaModalPassword('Las contraseñas no coinciden', 'error');
            return;
        }

        const btn = document.getElementById('btnCambiarPassword');
        btn.disabled = true;
        btn.textContent = 'Guardando...';

        try {
            const response = await fetch('api/cambiar_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    password_actual: actual,
                    password_nueva: nueva
                })
            });
            const resultado = await response.json();

            if (resultado.success) {
                mostrarAlertaModalPassword(resultado.message, 'success');
                setTimeout(() => {
                    cerrarModalCambiarPassword();
                }, 2000);
            } else {
                mostrarAlertaModalPassword(resultado.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlertaModalPassword('Error de conexión al servidor', 'error');
        } finally {
            btn.disabled = false;
            btn.textContent = 'Guardar cambios';
        }
    }

    function mostrarAlertaModalPassword(mensaje, tipo) {
        const alerta = document.getElementById('alerta-cambio');
        alerta.textContent = mensaje;
        alerta.className = `alert alert-${tipo} show`;
        alerta.style.display = 'flex';
        setTimeout(() => {
            alerta.style.display = 'none';
        }, 4000);
    }

    // ==================== UTILERÍAS ====================
    function mostrarAlerta(elementId, mensaje, tipo) {
        const alertaDiv = document.getElementById(elementId);
        if (!alertaDiv) return;
        alertaDiv.textContent = mensaje;
        alertaDiv.className = `alert alert-${tipo} show`;
        alertaDiv.style.display = 'flex';
        setTimeout(() => {
            if (alertaDiv) alertaDiv.style.display = 'none';
        }, 4000);
    }

    function calcularDistancia(lat1, lon1, lat2, lon2) {
        const radioTierra = 6371000;
        const lat1Rad = lat1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;
        const deltaLat = (lat2 - lat1) * Math.PI / 180;
        const deltaLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
                  Math.cos(lat1Rad) * Math.cos(lat2Rad) *
                  Math.sin(deltaLon / 2) * Math.sin(deltaLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return radioTierra * c;
    }

    function confirmarCerrarSesion() {
        return confirm('¿Estás seguro de que deseas cerrar sesión?');
    }
</script>
</body>
</html>