# Sistema de Solicitud de Constancias

API REST para administrar usuarios y solicitudes de constancias. Los usuarios
pueden solicitar sus constancias y consultar su estado; los administradores
pueden gestionarlas, generar códigos QR, enviarlas por correo y consultar
reportes.

## Tecnologías

- PHP 8.3 y Laravel 10
- PostgreSQL 13
- Laravel Sanctum
- Autenticación de dos factores (TOTP)
- Swagger / OpenAPI
- Docker Compose

## Funcionalidades principales

- Inicio de sesión con correo, contraseña y código TOTP.
- Cambio obligatorio de la contraseña temporal en el primer acceso.
- Roles de administrador y usuario.
- Creación, consulta, actualización y eliminación de constancias.
- Consulta de constancias propias para el usuario.
- Generación de códigos QR.
- Envío de constancias por correo electrónico.
- Reportes mensuales, PDF, CSV y datos para gráficos.

## Requisitos

- Git
- Docker Desktop con Docker Compose

No es necesario instalar PHP, Composer ni PostgreSQL localmente cuando se
utilizan los contenedores.

## Instalación

1. Clonar el repositorio y entrar en la carpeta:

```bash
git clone https://github.com/jr5406536-svg/Parcial3_Backend_MR24207.git
cd Parcial3_Backend_MR24207
```

2. Crear el archivo de configuración.

En Git Bash:

```bash
cp .env.example .env
```

En PowerShell:

```powershell
Copy-Item .env.example .env
```

3. Iniciar los contenedores e instalar las dependencias:

```bash
docker compose up -d
docker compose exec api composer install
docker compose exec api php artisan key:generate
docker compose exec api php artisan migrate --seed
docker compose exec api php artisan l5-swagger:generate
```

La API quedará disponible en `http://localhost:8081`.

Para reconstruir por completo la base de datos de desarrollo se puede usar:

```bash
docker compose exec api php artisan migrate:fresh --seed
```

> Advertencia: `migrate:fresh` elimina todas las tablas y sus datos.

## Base de datos

La configuración de desarrollo está definida en `.env.example` y
`docker-compose.yml`.

- Motor: PostgreSQL 13
- Base de datos: `constancias`
- Usuario: `postgres`
- Contraseña de desarrollo: `postgres`
- Acceso desde los contenedores: `postgresql13:5432`
- Acceso desde la computadora: `localhost:5433`

## Usuarios de prueba

Los siguientes usuarios se crean al ejecutar los seeders:

### Administrador

- Correo: `admin@constancias.com`
- Contraseña temporal: `admin123`
- Rol: `administrador`

### Usuario

- Correo: `usuario@constancias.com`
- Contraseña temporal: `usuario123`
- Rol: `usuario`

Estas credenciales son exclusivamente para desarrollo. Después de completar el
primer acceso, la contraseña temporal cambia y las credenciales anteriores
dejan de ser válidas. Para recuperar los datos iniciales en desarrollo se puede
ejecutar `php artisan migrate:fresh --seed` dentro del contenedor.

## Flujo del primer inicio de sesión

1. Enviar el correo y la contraseña a `POST /api/login`.
2. Escanear el QR de enrolamiento con Google Authenticator, Microsoft
   Authenticator u otra aplicación TOTP.
3. Enviar el código de seis dígitos a `POST /api/verificar-codigo`, usando el
   token Bearer recibido durante el inicio de sesión.
4. Cambiar la contraseña temporal mediante `POST /api/cambiar-password`.
5. Usar el nuevo token Bearer para acceder a los recursos autorizados.

La nueva contraseña debe tener al menos ocho caracteres e incluir letras y
números.

## Documentación de la API

Swagger UI está disponible en:

`http://localhost:8081/api/documentation`

La URL base de los endpoints es:

`http://localhost:8081/api`

Entre los endpoints principales se encuentran:

- `POST /api/login`
- `POST /api/verificar-codigo`
- `POST /api/cambiar-password`
- `GET /api/me`
- `GET|POST /api/mis-constancias`
- `GET|POST /api/constancias`
- `GET /api/reportes/pdf`
- `GET /api/reportes/csv`
- `GET /api/reportes/por-mes`
- `GET /api/reportes/grafico`

Swagger muestra el detalle de los parámetros, respuestas y permisos de cada
operación.

## Configuración de correo

Para habilitar el envío de constancias se deben completar las variables
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` y `MAIL_TO` en el archivo
`.env`. No se deben publicar credenciales reales ni subir el archivo `.env` al
repositorio.

## Detener el proyecto

```bash
docker compose down
```
