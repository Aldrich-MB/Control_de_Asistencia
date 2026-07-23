// Funcionalidad completa del registro de asistencia

// ==================== VARIABLES GLOBALES ====================
let trabajadorActual = null;
let ubicacionActual = null;
let ubicacionVerificada = false;

// Coordenadas de la oficina
const OFICINA_LAT = 19.557568149493722;   
const OFICINA_LNG = -99.60861103565107;  
const RADIO_PERMITIDO = 20;      

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
// Iniciar reloj
actualizarReloj();
setInterval(actualizarReloj, 1000);
});

// ==================== RELOJ EN TIEMPO REAL ====================
function actualizarReloj() {
const ahora = new Date();
const horas = String(ahora.getHours()).padStart(2, '0');
const minutos = String(ahora.getMinutes()).padStart(2, '0');
const segundos = String(ahora.getSeconds()).padStart(2, '0');

const relojElement = document.getElementById('reloj');
const fechaElement = document.getElementById('fecha-hoy');

if (relojElement) {
    relojElement.textContent = `${horas}:${minutos}:${segundos}`;
}

if (fechaElement) {
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fechaFormateada = ahora.toLocaleDateString('es-MX', opciones);
    fechaElement.textContent = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);
}
}

// ==================== BUSCAR TRABAJADOR POR CVE ====================
async function buscarTrabajador() {
const cveInput = document.getElementById('cve');
const cve = cveInput ? cveInput.value.trim() : '';

const nombreInput = document.getElementById('nombre');
const cargoInput = document.getElementById('cargo');
const infoDiv = document.getElementById('info-trabajador');

if (cve.length === 0) {
    // Limpiar campos
    if (nombreInput) nombreInput.value = '';
    if (cargoInput) cargoInput.value = '';
    if (infoDiv) infoDiv.style.display = 'none';
    trabajadorActual = null;
    return;
}

if (cve.length < 3) {
    // No buscar hasta tener al menos 3 caracteres
    return;
}

try {
    const response = await fetch(`api/buscar_trabajador.php?cve=${encodeURIComponent(cve)}`);
    const resultado = await response.json();

    if (resultado.success && resultado.data) {
        // Trabajador encontrado
        trabajadorActual = resultado.data;
        if (nombreInput) nombreInput.value = trabajadorActual.nombre_completo;
        if (cargoInput) cargoInput.value = trabajadorActual.cargo || 'Sin cargo';
        
        // Mostrar mensaje de éxito
        mostrarAlerta('info-trabajador', `Trabajador encontrado`, 'success');
        
        // Verificar si ya registró entrada/salida hoy
        // (opcional, se puede agregar después)
    } else {
        // Trabajador no encontrado
        trabajadorActual = null;
        if (nombreInput) nombreInput.value = '';
        if (cargoInput) cargoInput.value = '';
        mostrarAlerta('info-trabajador', ` ${resultado.message || 'Trabajador no encontrado'}`, 'error');
    }
} catch (error) {
    console.error('Error al buscar trabajador:', error);
    trabajadorActual = null;
    mostrarAlerta('info-trabajador', ' Error de conexión al servidor', 'error');
}
}

// ==================== VERIFICAR UBICACIÓN GPS ====================
function verificarUbicacion() {
const geoStatus = document.getElementById('geo-status');
const btnGeo = document.getElementById('btn-geo');

if (!navigator.geolocation) {
    actualizarEstadoGeo('error', 'Tu navegador no soporta geolocalización');
    return;
}

// Cambiar estado a "verificando"
actualizarEstadoGeo('wait', 'Verificando ubicación...');
if (btnGeo) btnGeo.disabled = true;

navigator.geolocation.getCurrentPosition(
    // Éxito
    function(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        
        ubicacionActual = { lat, lng };
        
        // Calcular distancia a la oficina
        const distancia = calcularDistancia(lat, lng, OFICINA_LAT, OFICINA_LNG);
        
        if (distancia <= RADIO_PERMITIDO) {
            ubicacionVerificada = true;
            actualizarEstadoGeo('ok', `Ubicación verificada. Distancia: ${distancia.toFixed(2)} metros`);
        } else {
            ubicacionVerificada = false;
            actualizarEstadoGeo('error', `Fuera del radio permitido. Distancia: ${distancia.toFixed(2)} metros (máx ${RADIO_PERMITIDO}m)`);
        }
        
        if (btnGeo) btnGeo.disabled = false;
    },
    // Error
    function(error) {
        ubicacionVerificada = false;
        let mensaje = '';
        
        switch(error.code) {
            case error.PERMISSION_DENIED:
                mensaje = 'Permiso de ubicación denegado. Actívalo para continuar.';
                break;
            case error.POSITION_UNAVAILABLE:
                mensaje = 'Información de ubicación no disponible.';
                break;
            case error.TIMEOUT:
                mensaje = 'Tiempo de espera agotado.';
                break;
            default:
                mensaje = 'Error al obtener ubicación.';
        }
        
        actualizarEstadoGeo('error', ` ${mensaje}`);
        if (btnGeo) btnGeo.disabled = false;
    },
    {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    }
);
}

// Actualizar el badge de estado de geolocalización
function actualizarEstadoGeo(tipo, mensaje) {
const geoStatus = document.getElementById('geo-status');
if (!geoStatus) return;

let clase = '';
let icono = '';

switch(tipo) {
    case 'ok':
        clase = 'geo-ok';
        icono = '<span class="geo-dot dot-ok"></span>';
        break;
    case 'error':
        clase = 'geo-err';
        icono = '<span class="geo-dot dot-err"></span>';
        break;
    case 'warn':
        clase = 'geo-warn';
        icono = '<span class="geo-dot dot-warn"></span>';
        break;
    default:
        clase = 'geo-wait';
        icono = '<span class="geo-dot dot-wait"></span>';
}

geoStatus.className = `geo-badge ${clase}`;
geoStatus.innerHTML = `${icono} ${mensaje}`;
}

// ==================== REGISTRAR ENTRADA/SALIDA ====================
async function registrar(tipo) {
// Validaciones previas
if (!trabajadorActual) {
    mostrarAlerta('alerta-registro', '❌ Primero busca y selecciona un trabajador', 'error');
    return;
}

if (!ubicacionVerificada || !ubicacionActual) {
    mostrarAlerta('alerta-registro', '❌ Primero verifica tu ubicación GPS', 'error');
    return;
}

// Deshabilitar botones mientras se procesa
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
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datos)
    });
    
    const resultado = await response.json();
    
    if (resultado.success) {
        // Éxito
        mostrarAlerta('alerta-registro', ` ${resultado.message}`, 'success');
        
        // Limpiar campos para el siguiente registro
        setTimeout(() => {
            // Limpiar CVE y datos del trabajador
            const cveInput = document.getElementById('cve');
            const nombreInput = document.getElementById('nombre');
            const cargoInput = document.getElementById('cargo');
            
            if (cveInput) cveInput.value = '';
            if (nombreInput) nombreInput.value = '';
            if (cargoInput) cargoInput.value = '';
            
            trabajadorActual = null;
            ubicacionVerificada = false;
            ubicacionActual = null;
            
            // Resetear estado de ubicación
            actualizarEstadoGeo('wait', 'Pendiente de verificación');
            
            // Ocultar alerta después de 3 segundos
            setTimeout(() => {
                ocultarAlerta('alerta-registro');
            }, 3000);
        }, 2000);
    } else {
        // Error
        mostrarAlerta('alerta-registro', ` ${resultado.message}`, 'error');
    }
} catch (error) {
    console.error('Error al registrar:', error);
    mostrarAlerta('alerta-registro', ' Error de conexión al servidor', 'error');
} finally {
    // Rehabilitar botones
    setTimeout(() => {
        botones.forEach(btn => btn.disabled = false);
    }, 2000);
}
}

// ==================== FUNCIONES UTILERIA ====================

// Mostrar alerta en un contenedor específico
function mostrarAlerta(elementId, mensaje, tipo) {
const alertaDiv = document.getElementById(elementId);
if (!alertaDiv) return;

alertaDiv.textContent = mensaje;
alertaDiv.className = `alert alert-${tipo} show`;
alertaDiv.style.display = 'flex';
}

// Ocultar alerta
function ocultarAlerta(elementId) {
const alertaDiv = document.getElementById(elementId);
if (!alertaDiv) return;

alertaDiv.style.display = 'none';
alertaDiv.className = 'alert';
}

// Calcular distancia entre dos puntos GPS (fórmula de Haversine)
function calcularDistancia(lat1, lon1, lat2, lon2) {
const radioTierra = 6371000; // metros

const lat1Rad = toRadians(lat1);
const lat2Rad = toRadians(lat2);
const deltaLat = toRadians(lat2 - lat1);
const deltaLon = toRadians(lon2 - lon1);

const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
          Math.cos(lat1Rad) * Math.cos(lat2Rad) *
          Math.sin(deltaLon / 2) * Math.sin(deltaLon / 2);

const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

return radioTierra * c;
}

function toRadians(grados) {
return grados * (Math.PI / 180);
}