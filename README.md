# Sistema de Gestión de Sucesos

API REST desarrollada con **Laravel 13** para la gestión y visualización de sucesos de drogas, armas y municiones. Incluye autenticación JWT, auditoría completa de operaciones, generación de estadísticas agregadas y visualización geográfica de sucesos.

---

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Requisitos Previos](#-requisitos-previos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Base de Datos](#-base-de-datos)
- [Autenticación JWT](#-autenticación-jwt)
- [Documentación de API](#-documentación-de-api)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Problemas Conocidos](#-problemas-conocidos)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

---

## ✨ Características

### Funcionalidades Principales

- **Autenticación JWT**: Sistema de autenticación basado en tokens JWT almacenados en cookies HttpOnly seguras
- **Gestión de Usuarios**: CRUD completo con sistema de roles y permisos (Spatie Permission)
- **Catálogos**: Gestión de drogas, armas, municiones y presentaciones de drogas
- **Sucesos**: Registro de sucesos con ubicación geográfica (coordenadas, departamento, municipio)
- **Items de Sucesos**: Gestión detallada de drogas, armas y municiones decomisadas por evento
- **Auditoría**: Registro automático de todas las operaciones CRUD (Spatie Activity Log)
- **Soft Deletes**: Eliminación lógica con restauración para todas las entidades
- **Estadísticas**: Endpoints para generación de gráficas con agregación por períodos (día, mes, trimestre, semestre, año)
- **Visualización Geográfica**: Endpoint especializado para visualizar sucesos en mapa
- **API en Español**: Todos los atributos de respuesta transformados al español
- **Documentación Swagger**: Documentación interactiva completa de la API

### Características Técnicas

- **API Resources**: Transformación bidireccional de atributos (inglés ↔ español)
- **Middleware de Transformación**: Conversión automática de requests entrantes
- **Rate Limiting**: Protección contra abuso con throttling global
- **Paginación**: Respuestas paginadas con ordenamiento y búsqueda
- **Caching**: Caché automático de respuestas basado en URL y parámetros
- **Validación**: FormRequests personalizados con reglas detalladas
- **Observers**: Gestión automática de archivos (logos, fotos) mediante eventos de modelo
- **Factory & Seeders**: Datos de prueba completos para desarrollo

---

## 🛠 Tecnologías

- **Framework**: Laravel 13.x
- **PHP**: ^8.3 (Sail utiliza PHP 8.4)
- **Base de Datos**: MySQL 8.0+ / PostgreSQL 13+
- **Autenticación**: Tymon JWT Auth 2.x
- **Permisos**: Spatie Laravel Permission 6.x
- **Auditoría**: Spatie Activity Log 4.x
- **Documentación API**: DarkaOnLine L5-Swagger 8.x
- **Frontend Assets**: Vite, TailwindCSS (para potenciales vistas)

---

## 📦 Requisitos Previos

- PHP >= 8.3
- Extensiones PHP GD (pruebas de imágenes), PDO SQLite (pruebas en memoria) y PDO MySQL (base de datos de la aplicación).
- Composer >= 2.5
- MySQL >= 8.0 o PostgreSQL >= 13
- Node.js >= 18.x y npm >= 9.x (opcional, para assets)
- Git

---

## 🚀 Instalación

### 1. Clonar el Repositorio

```bash
git clone <repository-url>
cd example-app
```

### 2. Instalar Dependencias PHP

```bash
composer install
```

### 3. Instalar Dependencias Node (Opcional)

```bash
npm install
```

### 4. Configurar Variables de Entorno

Copiar el archivo de ejemplo y configurar:

```bash
cp .env.example .env
```

Editar `.env` con tus credenciales:

```env
APP_NAME="Sistema de Sucesos"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sucesos_db
DB_USERNAME=root
DB_PASSWORD=

# Configuración JWT
JWT_SECRET=
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256
JWT_SHOW_BLACKLIST_EXCEPTION=true

# Configuración de Sesión (para cookies JWT)
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=localhost
SESSION_SECURE_COOKIE=false
```

### 5. Generar Clave de Aplicación

```bash
php artisan key:generate
```

### 6. Generar Clave Secreta JWT

```bash
php artisan jwt:secret
```

Esto generará automáticamente `JWT_SECRET` en tu archivo `.env`.

---

## ⚙️ Configuración

### Configuración JWT Personalizada

El archivo `config/jwt.php` incluye configuraciones personalizadas para cookies HttpOnly:

```php
'required_claims' => [
    'iss',
    'iat',
    'exp',
    'nbf',
    'jti',
],

'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
'providers' => [
    'jwt' => Tymon\JWTAuth\Providers\JWT\Lcobucci::class,
    'auth' => Tymon\JWTAuth\Providers\Auth\Illuminate::class,
    'storage' => Tymon\JWTAuth\Providers\Storage\Illuminate::class,
],
```

### Configuración de CORS

Editar `config/cors.php` si necesitas acceso desde frontend:

```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['http://localhost:3000'],
'allowed_headers' => ['*'],
'exposed_headers' => [],
'max_age' => 0,
'supports_credentials' => true, // IMPORTANTE para cookies HttpOnly
```

### Configuración de Almacenamiento

Los archivos (logos de catálogos, fotos de sucesos) se almacenan en:

```
storage/app/public/
├── drug/
├── weapon/
├── ammunition/
├── drugPresentation/
├── drugConfiscation/
├── weaponConfiscation/
└── ammunitionConfiscation/
```

Crear symlink público:

```bash
php artisan storage:link
```

---

## 🗄️ Base de Datos

### 1. Crear la Base de Datos

```sql
CREATE DATABASE sucesos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará todas las tablas necesarias:
- `users`, `roles`, `permissions` (autenticación y permisos)
- `activity_log` (auditoría)
- `drugs`, `weapons`, `ammunitions`, `drug_presentations` (catálogos)
- `confiscations` (sucesos principales)
- `drug_confiscations`, `weapon_confiscations`, `ammunition_confiscations` (items)

### 3. Poblar Base de Datos (Desarrollo)

```bash
php artisan db:seed
```

Esto creará:
- Usuario administrador por defecto
- Roles y permisos básicos
- Datos de ejemplo de catálogos
- Sucesos de prueba con items asociados

**Credenciales por defecto**:
- Email: `admin@example.com`
- Password: `password`

### 4. Refrescar Base de Datos (Desarrollo)

Para resetear completamente la base de datos:

```bash
php artisan migrate:fresh --seed
```

---

## 🔐 Autenticación JWT

### Flujo de Autenticación

1. **Login**: `POST /api/login`
   - Enviar `correo` y `password`
   - Recibe token JWT en cookie HttpOnly `token`
   - Respuesta incluye datos del usuario y token en JSON

2. **Requests Autenticados**:
   - El middleware `AuthenticateJWT` extrae automáticamente el token de la cookie
   - No es necesario enviar header `Authorization`

3. **Refresh Token**: `POST /api/refresh`
   - Renueva el token actual
   - Actualiza cookie `token`

4. **Logout**: `POST /api/logout`
   - Invalida el token (blacklist)
   - Elimina cookie `token`

### Uso con Swagger UI

Para probar endpoints autenticados en Swagger:

1. Hacer login en `/api/login`
2. Copiar el token del campo `token` en la respuesta
3. Hacer clic en "Authorize" en Swagger UI
4. Ingresar: `Bearer {token_copiado}`
5. Ahora todos los requests incluirán el header de autorización

### Configuración de Cookies

Las cookies JWT tienen las siguientes características de seguridad:

```php
// En AuthController
Cookie::make('token', $token, 
    config('jwt.ttl'),           // TTL: 60 minutos
    '/',                          // Path
    null,                         // Domain
    false,                        // Secure (true en producción)
    true,                         // HttpOnly
    false,                        // Raw
    'strict'                      // SameSite
)
```

**IMPORTANTE**: En producción, cambiar `SESSION_SECURE_COOKIE=true` en `.env` para forzar HTTPS.

---

## 📚 Documentación de API

### Generar Documentación Swagger

```bash
php artisan l5-swagger:generate
```

### Acceder a Swagger UI

Una vez generada, acceder en:

```
http://localhost:8000/api/documentation
```

### Estructura de la Documentación

La documentación Swagger incluye:

- **Schemas**: Definiciones de modelos con todos sus campos
- **Endpoints**: Todas las rutas con ejemplos de request/response
- **Autenticación**: Configuración de bearer token JWT
- **Validación**: Reglas de validación para cada endpoint
- **Errores**: Respuestas de error estándar (400, 401, 404, 422, 500)

### Archivos de Documentación

Los archivos Swagger Docs están en:

```
app/Docs/
├── AuthDocs.php
├── UserDocs.php
├── RoleDocs.php
├── PermissionDocs.php
├── ActivityLogDocs.php
├── DrugDocs.php
├── WeaponDocs.php
├── AmmunitionDocs.php
├── DrugPresentationDocs.php
├── ConfiscationDocs.php
├── DrugConfiscationDocs.php
├── WeaponConfiscationDocs.php
└── AmmunitionConfiscationDocs.php
```

---

## 📁 Estructura del Proyecto

```
example-app/
├── app/
│   ├── Docs/                          # Anotaciones Swagger para cada entidad
│   ├── Helpers/                       # Funciones auxiliares (nameEvent.php)
│   ├── Http/
│   │   ├── Controllers/               # Controladores de API
│   │   │   ├── Admin/                 # Auth, User, Role, Permission, LogActivity
│   │   │   └── ...                    # Drug, Weapon, Confiscation, etc.
│   │   ├── Middleware/                # AuthenticateJWT, TransformInput
│   │   ├── Requests/                  # FormRequests con validación
│   │   │   ├── Admin/
│   │   │   ├── Drug/, Weapon/, ...
│   │   │   └── Confiscation/, DrugConfiscation/, ...
│   │   └── Resources/                 # API Resources para transformación
│   │       ├── Admin/
│   │       ├── Drug/, Weapon/, ...
│   │       └── Confiscation/, DrugConfiscation/, ...
│   ├── Models/                        # Modelos Eloquent
│   ├── Observers/                     # Observers para eventos de modelo
│   ├── Providers/                     # Service Providers
│   └── Traits/                        # Activitylog, ApiResponser
├── config/                            # Configuraciones de Laravel
│   ├── jwt.php                        # Configuración JWT
│   ├── permission.php                 # Configuración Spatie Permission
│   ├── activitylog.php                # Configuración Spatie Activity Log
│   └── l5-swagger.php                 # Configuración Swagger
├── database/
│   ├── factories/                     # Factories para testing y seeding
│   ├── migrations/                    # Migraciones de base de datos
│   └── seeders/                       # Seeders de datos
├── routes/
│   ├── api.php                        # Rutas de API (documentadas)
│   ├── web.php                        # Rutas web (vacío)
│   └── console.php                    # Comandos Artisan personalizados
├── storage/
│   └── app/public/                    # Almacenamiento de archivos subidos
├── tests/                             # Tests unitarios y de integración
├── .env.example                       # Plantilla de variables de entorno
├── composer.json                      # Dependencias PHP
├── package.json                       # Dependencias Node
└── README.md                          # Este archivo
```

---

## 🧪 Testing

### Ejecutar Todos los Tests

Con Sail, que incluye las extensiones necesarias:

```bash
./vendor/bin/sail artisan test
```

Con PHP local (requiere las extensiones indicadas en los requisitos):

```bash
php artisan test
```

### Ejecutar Tests Específicos

```bash
# Tests de Feature (integración)
php artisan test --testsuite=Feature

# Tests de Unit (unitarios)
php artisan test --testsuite=Unit

# Test específico
php artisan test tests/Feature/AuthTest.php
```

### Cobertura de Tests

```bash
php artisan test --coverage
```

### Crear Nuevos Tests

```bash
# Test de Feature
php artisan make:test NombreTest

# Test de Unit
php artisan make:test NombreTest --unit
```

### Base de Datos de Testing

Los tests usan una base de datos SQLite en memoria por defecto (`phpunit.xml`):

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

---

## 🚢 Deployment

### Preparación para Producción

1. **Variables de Entorno**:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=true
```

2. **Optimizaciones**:

```bash
# Cachear configuraciones
php artisan config:cache

# Cachear rutas
php artisan route:cache

# Cachear vistas
php artisan view:cache

# Optimizar autoload de Composer
composer install --optimize-autoloader --no-dev
```

3. **Permisos de Almacenamiento**:

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

4. **Configurar Web Server**:

Apuntar document root a `/public` y configurar rewrite rules.

**Nginx Example**:

```nginx
server {
    listen 80;
    server_name api.sucesos.com;
    root /var/www/example-app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Docker (Opcional)

Existe un `docker-compose.yml` en el proyecto. Para usar:

```bash
docker-compose up -d
docker-compose exec app php artisan migrate --seed
```

---

## ⚠️ Problemas Conocidos

### Issues de Validación

Durante la documentación del código se identificaron algunos bugs menores en validaciones:

1. **Permission StorePostRequest** (línea 28):
   - Valida unicidad de `name` contra tabla `roles` en lugar de `permissions`
   - **Fix recomendado**: Cambiar `'unique:roles'` a `'unique:permissions'`

2. **Weapon StorePostRequest** (línea 25):
   - Valida unicidad de `description` contra tabla `ammunitions` en lugar de `weapons`
   - **Fix recomendado**: Cambiar `'unique:ammunitions'` a `'unique:weapons'`

3. **DrugPresentation StorePostRequest** (línea 25):
   - Valida unicidad de `description` contra tabla `ammunitions` en lugar de `drug_presentations`
   - **Fix recomendado**: Cambiar `'unique:ammunitions'` a `'unique:drug_presentations'`

4. **WeaponConfiscation UpdatePutRequest** (línea 25):
   - Valida `amount` como string en lugar de integer (inconsistente con StorePostRequest)
   - **Fix recomendado**: Cambiar `'string'` a `'integer'`

### Observaciones

- Endpoint `/weaponConfiscation/deleted` llama incorrectamente a `indexByConfiscation` en lugar de `indexDeleted`
- Considerar agregar validación de tipo de archivo más estricta (además de extensión PNG, validar MIME type)

---

## 🤝 Contribución

### Workflow de Contribución

1. Fork del repositorio
2. Crear branch de feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push al branch (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

### Estándares de Código

- **PSR-12**: Seguir estándares PSR-12 para PHP
- **PHPDoc**: Documentar todas las clases y métodos públicos en español
- **Tests**: Incluir tests para nuevas funcionalidades
- **Commits**: Mensajes descriptivos en español

### Checklist de Pull Request

- [ ] Tests pasando
- [ ] PHPDoc agregado/actualizado
- [ ] Swagger Docs actualizado si hay cambios en API
- [ ] CHANGELOG.md actualizado
- [ ] Sin conflictos con `main`

---

## 📄 Licencia

Este proyecto está bajo la licencia MIT. Ver archivo `LICENSE` para más detalles.

---

## 📞 Contacto

Para preguntas, sugerencias o reportar bugs, por favor abrir un issue en el repositorio.

---

## 🙏 Agradecimientos

- **Laravel**: Framework PHP excepcional
- **Spatie**: Paquetes de alta calidad (Permission, Activity Log)
- **Tymon**: JWT Auth para Laravel
- **DarkaOnLine**: L5-Swagger para documentación

---

**Versión**: 1.0.0  
**Última Actualización**: Noviembre 2025
