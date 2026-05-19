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
