#  Sistema Bancario Seguro - Step-Up Authentication

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat-square&logo=mysql)
![Security](https://img.shields.io/badge/Security-2FA%20%7C%20OTP-0f766e?style=flat-square)

Desarrollado por **w1ll_r00t**. 

Este proyecto es un simulador de entorno transaccional bancario diseñado con un enfoque estricto en la Ciberseguridad. Implementa **Autenticación Escalonada (Step-up Authentication)** y prevención de vulnerabilidades clásicas (OWASP), garantizando la integridad y confidencialidad de las operaciones financieras.

##  Arquitectura de Seguridad Implementada

- **Doble Factor de Autenticación (2FA):** Integración nativa con `multiOTP` para la generación y validación de tokens Time-based One-Time Password (TOTP).
- **Firma Transaccional:** Uso de OTP dinámico exigido *justo antes* de realizar movimientos de dinero (Prevención de robo de sesión).
- **Control de Acceso Basado en Roles (RBAC):** Segmentación estricta entre privilegios de Administrador (Cajero) y Usuario (Cliente).
- **Prevención de IDOR (Insecure Direct Object Reference):** Validación estricta en el backend para asegurar que un usuario solo pueda retirar fondos de las cuentas de las que es propietario legal.
- **Trazabilidad y Auditoría (Logs):** Registro inmutable de acciones críticas (ABM y transferencias) capturando usuario, rol, acción, detalles forenses y Dirección IP real.
- **Prevención SQLi:** Uso exclusivo de sentencias preparadas (Prepared Statements) en todas las consultas a la base de datos.

##  Despliegue en Entorno Local (Laragon / XAMPP)

Sigue estos pasos para ejecutar el proyecto en tu máquina local:

### 1. Preparar la Base de Datos
1. Inicia los servicios de Apache y MySQL.
2. Abre phpMyAdmin o tu gestor de base de datos preferido.
3. Crea una base de datos llamada `login_db`.
4. Importa los archivos SQL que se encuentran en la carpeta `/login_db` en el siguiente orden para respetar las llaves foráneas:
   - `usuarios.sql`
   - `cuentas_bancarias.sql`
   - `transferencias.sql`

### 2. Configurar el Servidor OTP (multiOTP)
1. Descarga el motor [multiOTP (versión 5.10.2.2)](https://www.multiotp.net/) para Windows.
2. Extrae la carpeta y colócala al mismo nivel que la carpeta de tu aplicación (`app1`), de modo que la estructura quede así:
   ```text
   /tu_servidor_web (ej. C:\laragon\www\multiOTP)
   ├── /app1 (Este repositorio)
   └── /multiotp_5.10.2.2
       └── /windows
           └── multiotp.exe
   ```

   ##  Flujo de la Aplicación y Medidas de Seguridad

A continuación se detalla el funcionamiento del sistema y las capas de seguridad implementadas en cada módulo.

### 1. Registro de Nuevo Usuario
El sistema registra las credenciales básicas (encriptadas en SHA1) e interactúa por debajo con el motor `multiOTP` para crear el perfil TOTP (Time-based One-Time Password) del usuario.
<br>
<img width="838" height="625" alt="image" src="https://github.com/user-attachments/assets/4d3891d9-984a-458b-bad6-e6eca5af9b35" />
<br><br>
<br><br>

### 2. Configuración del Doble Factor (2FA)
Al completar el registro, el servidor genera de forma dinámica un código QR único. El usuario debe escanearlo con aplicaciones como FreeOTP o Google Authenticator para vincular su dispositivo criptográfico.
<br>
<img width="849" height="738" alt="image" src="https://github.com/user-attachments/assets/57df3b9c-5d78-492f-8bf4-8a9087fb5277" />
<br><br>

### 3. Autenticación Fase 1 (Credenciales Clásicas)
El primer paso del control de acceso verifica únicamente el nombre de usuario y la contraseña contra la base de datos. Si es correcto, el rol se guarda en una sesión temporal, sin otorgar acceso aún.
<br>
<img width="673" height="579" alt="image" src="https://github.com/user-attachments/assets/71a5d670-d55f-44e1-a0b6-1298c85e5911" />
<br><br>

### 4. Autenticación Fase 2 (Desafío OTP)
Prevención directa contra filtración de contraseñas. El sistema exige el token temporal de 6 dígitos generado por la aplicación del usuario. Solo al validarse contra el servidor multiOTP, se concede el acceso al sistema.
<br>
<img width="1708" height="533" alt="image" src="https://github.com/user-attachments/assets/1cf93a42-3029-450d-b100-1dfd79c091c6" />
<br><br>

### 5. Dashboard (Control de Acceso Basado en Roles - RBAC)
La vista del panel principal varía dinámicamente según el privilegio del usuario. Los administradores tienen acceso completo a las herramientas de gestión (ABM y Auditoría), mientras que los clientes solo ven los módulos operativos.
<br>
[Aquí tu captura del Dashboard, preferiblemente mostrando la vista de Admin]
<br><br>

### 6. Módulo de Transferencias y Prevención IDOR
El simulador bancario implementa una protección estricta contra referencias directas a objetos inseguros (IDOR). Un usuario "Cliente" solo podrá ver y retirar fondos de las cuentas de las que es propietario legalmente verificado en el backend.
<br>
[Aquí tu captura de la pantalla de transferencias (mostrando "Mis Cuentas y Saldos")]
<br><br>

### 7. Firma Transaccional (Step-up Authentication)
Protección contra el secuestro de sesión (Session Hijacking). Al intentar realizar acciones críticas (como transferir dinero o eliminar un usuario), un modal de seguridad intercepta la solicitud y exige una re-autenticación mediante OTP antes de tocar la base de datos.
<br>
[Aquí tu captura del Modal oscuro pidiendo el OTP para autorizar]
<br><br>

### 8. Gestión de Usuarios (Exclusivo Admin)
Panel de control centralizado (ABM) donde el cajero/administrador puede dar de alta, modificar roles o dar de baja a usuarios y sus cuentas TOTP de forma sincronizada. 
<br>
[Aquí tu captura del formulario de ABM Usuarios]
<br><br>

### 9. SIEM Interno: Auditoría y Trazabilidad Forense
Para garantizar el no repudio, todas las acciones críticas quedan registradas de forma inmutable. La tabla de auditoría permite a la gerencia rastrear eventos respondiendo al Quién (Usuario/Rol), Qué (Módulo/Acción), Cuándo (Fecha) y Dónde (Dirección IP de origen).
<br>
[Aquí tu captura de la tabla de Auditoría con las IPs y roles]



