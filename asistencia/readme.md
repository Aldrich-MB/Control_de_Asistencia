SISTEMA DE CONTROL DE ASISTENCIA
================================

Sistema web para el control de asistencia laboral con geolocalización, 
registro de entrada y salida, justificación de faltas, salidas justificadas 
y generación de reportes en Excel y PDF.

El sistema permite a los trabajadores registrar su asistencia desde sus 
dispositivos móviles o computadoras, validando su ubicación mediante GPS 
para asegurar que se encuentren dentro del radio permitido de la oficina. 
Los administradores pueden gestionar trabajadores, cargos, visualizar 
estadísticas en tiempo real y generar reportes personalizados.


Comenzando 🚀
==============

Estas instrucciones te permitirán obtener una copia del proyecto en 
funcionamiento en tu máquina local para propósitos de desarrollo y pruebas.

Para obtener una copia del proyecto, clona el repositorio desde GitHub 
o descarga el código fuente en formato ZIP.


Pre-requisitos 📋
==================

Para instalar y ejecutar el sistema, necesitas tener instalado lo siguiente:

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer (gestor de dependencias de PHP)
- Servidor web (Apache / Nginx / WAMP / XAMPP)

Además, necesitarás:
- Un navegador web moderno (Chrome, Firefox, Edge)
- Conexión a internet para descargar dependencias
- Acceso a phpMyAdmin o línea de comandos para gestionar la base de datos


Instalación 🔧
===============

Sigue estos pasos para tener un entorno de desarrollo ejecutándose:

Paso 1: Clonar el repositorio
-------------------------------
Clona el repositorio en tu máquina local:

git clone https://github.com/tu-usuario/asistencia.git
cd asistencia


Paso 2: Instalar dependencias con Composer
-------------------------------------------
Ejecuta el siguiente comando para instalar todas las dependencias necesarias:

composer install

Esto instalará:
- PhpSpreadsheet (para reportes Excel)
- Dompdf (para reportes PDF)
- Otras dependencias requeridas


Paso 3: Crear la base de datos
-------------------------------
1. Abre phpMyAdmin o tu cliente MySQL
2. Crea una nueva base de datos (ej: asistencia_db)
3. Importa el archivo de estructura:

   Desde phpMyAdmin:
   - Selecciona la base de datos creada
   - Ve a la pestaña "Importar"
   - Selecciona el archivo sql/database.sql
   - Haz clic en "Continuar"

   Desde línea de comandos:
   mysql -u usuario -p nombre_bd < sql/database.sql


Paso 4: Configurar el archivo de conexión
------------------------------------------
Modifica el archivo de configuración:

Edita el archivo includes/config.php con tus credenciales:

define('DB_HOST', 'localhost');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('DB_NAME', 'tu_base_datos');

También configura las coordenadas de tu oficina:

define('OFICINA_LAT', 99.99999949999999); // Latitud real de la oficina
define('OFICINA_LNG', -99.99999999999999); // Longitud real de la oficina
define('RADIO_PERMITIDO_METROS', 00); // Radio permitido en metros


Paso 5: Configurar el servidor web
-----------------------------------
- Si usas WAMP: Coloca el proyecto en C:\wamp64\www\asistencia
- Si usas XAMPP: Coloca el proyecto en C:\xampp\htdocs\asistencia
- Si usas Apache/Nginx: Configura el DocumentRoot a la carpeta del proyecto

Paso 6: Acceder al sistema
---------------------------
Abre tu navegador y accede a:

http://localhost/asistencia/login.php


Credenciales de prueba
-----------------------
- Administrador: usuario: admin / contraseña: admin1234


Ejecutando las pruebas ⚙️
==========================

Actualmente el sistema cuenta con pruebas manuales recomendadas:

Pruebas de funcionalidad:
- Iniciar sesión como administrador y verificar el dashboard
- Registrar a un nuevo trabajdor
- Registrar entrada y salida con GPS válido
- Justificar una falta con motivo y comprobante
- Registrar una salida justificada
- Generar reportes en Excel y PDF

Pruebas de validación:
- Intentar registrar salida sin entrada previa
- Intentar registrar salida antes de 2 minutos
- Intentar justificar con motivo vacío
- Intentar acceder al panel admin sin permisos


Analice las pruebas end-to-end 🔩
==================================

Las pruebas end-to-end verifican el flujo completo del sistema:

1. Autenticación de usuarios
   - Verifica que solo usuarios registrados puedan acceder
   - Valida que las contraseñas sean correctas
   - Verifica la redirección según el rol (trabajador/admin)

2. Registro de asistencia
   - Valida que la ubicación GPS esté dentro del radio permitido
   - Verifica que no se registre doble entrada o salida
   - Valida que se registre correctamente el tipo de asistencia

3. Justificaciones
   - Verifica que se pueda justificar una falta completa
   - Verifica que se pueda registrar una salida justificada
   - Valida que se guarde el motivo y comprobante

4. Reportes
   - Verifica que los reportes Excel contengan todos los datos
   - Verifica que los reportes PDF se generen correctamente
   - Valida los filtros por fecha y trabajador


Pruebas de estilo de codificación ⌨️
=====================================

El sistema sigue las siguientes convenciones:

- PHP: Estilo PSR-12 (llaves en nueva línea, indentación 4 espacios)
- JavaScript: CamelCase para variables, PascalCase para clases
- CSS: Clases en kebab-case (ej: .nombre-clase)
- Nombres de archivos: minúsculas y guiones bajos (ej: nuevo_trabajador.php)
- Nombres de tablas: minúsculas y plural (ej: trabajadores)
- Nombres de columnas: minúsculas y snake_case (ej: fecha_hora)


Despliegue 📦
=============

Para desplegar el sistema en un servidor de producción:

1. Sube todos los archivos al servidor via FTP o Git

2. En el servidor, ejecuta:
   composer install --no-dev

3. Crea el archivo includes/config.php con las credenciales reales:
   - Usar las credenciales de producción en lugar de locales
   - Las coordenadas de la oficina deben ser las reales

4. Importa la base de datos en el servidor:
   - Exporta tu base de datos local desde phpMyAdmin
   - Importa el archivo en el servidor

5. Configura el servidor web para apuntar a la carpeta pública

6. Recomendaciones de seguridad:
   - Cambia las contraseñas por defecto
   - Protege el archivo includes/config.php
   - Asegura que las carpetas uploads/ y exports/ tengan permisos adecuados


Construido con 🛠️
==================

- PHP 7.4+ - Lenguaje de programación backend
- MySQL 5.7+ - Base de datos relacional
- JavaScript (ES6) - Interactividad frontend
- Chart.js 3.x - Gráficas interactivas en dashboard
- PhpSpreadsheet 1.x - Generación de reportes Excel
- Dompdf 2.x - Generación de reportes PDF
- HTML5/CSS3 - Estructura y estilos responsive
- WAMP - Entorno de desarrollo local
- Git - Control de versiones


Contribuyendo 🖇️
=================

Si deseas contribuir al proyecto, por favor sigue estos pasos:

1. Haz un fork del repositorio
2. Crea una rama para tu funcionalidad:
   git checkout -b feature/nueva-funcionalidad
3. Realiza tus cambios y haz commit:
   git commit -m "Agregada nueva funcionalidad"
4. Sube tus cambios:
   git push origin feature/nueva-funcionalidad
5. Abre un Pull Request en GitHub

Para detalles del código de conducta, consulta el archivo CONTRIBUTING.md


Wiki 📖
=======

Puedes encontrar más información sobre el sistema en nuestra Wiki:

- Manual de usuario
- Guía de administración
- Preguntas frecuentes
- Guía de instalación avanzada

URL: https://github.com/tu-usuario/asistencia/wiki


Versionado 📌
=============

Usamos SemVer (Semantic Versioning) para el versionado:

- v1.0.0 - Versión inicial estable
- v1.1.0 - Nuevas funcionalidades menores
- v2.0.0 - Cambios mayores (breaking changes)

Para todas las versiones disponibles, mira los tags en el repositorio:
https://github.com/tu-usuario/asistencia/tags


Autores ✒️
==========

- Aldrich Medina - Trabajo Inicial - (https://github.com/tu-usuario)

También puedes mirar la lista de todos los contribuyentes quíenes han 
participado en este proyecto en:
https://github.com/tu-usuario/asistencia/contributors


Licencia 📄
===========

Este proyecto está bajo la Licencia MIT - mira el archivo LICENSE.md 
para más detalles.

MIT License

Copyright (c) 2026 Aldrich Medina

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.


Expresiones de Gratitud 🎁
==========================

¡Gracias por utilizar este sistema! 

Si te ha sido útil, considera:

- Comentar sobre este proyecto en tus redes sociales 📢
- Invitar una cerveza 🍺 o un café ☕ al equipo.
- Dar las gracias públicamente 🤓.
- Reportar issues o sugerir mejoras

Si deseas apoyar el desarrollo, puedes realizar una donación:
- Bitcoin: 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa
- Ethereum: 0xf253fc233333078436d111175e5a76a649890000

---

Sistema de Control de Asistencia - v1.0.0
Desarrollado con ❤️ en México