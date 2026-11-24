# GraphMapApp · API de decomisos

Backend de portafolio para registrar decomisos, sus cantidades de drogas, armas y municiones, consultar estadísticas y obtener datos geográficos. Es un monolito Laravel con contratos JSON en español, permisos por operación, auditoría y evidencias privadas. Este repositorio contiene la API y Swagger UI; el mapa y las gráficas se entregan como datos, sin un cliente visual propio.

El refactor de 16 pasos está documentado en [el plan](refactor-plan.md) y [la bitácora](refactor-progress.md). CI y releases se prepararon y ensayaron localmente. La infraestructura, la ejecución remota y la publicación de la demo están pendientes; no hay un servidor de producción acreditado por esta documentación.

## Entorno comprobado

Versiones instaladas verificadas el 17 de noviembre de 2026; `composer.lock` fija las dependencias PHP.

| Componente | Versión / alcance |
| --- | --- |
| PHP | 8.4.27 en Sail; runtime y CI configurados para 8.4 |
| Laravel | 13.40.0 |
| JWT Auth | 2.3.1, guard `api` |
| Spatie Permission / Activitylog | 6.25.0 / 4.12.3 |
| L5-Swagger | 11.3.0; fuentes en `app/Docs`, JSON en `storage/api-docs` |
| PHPUnit / Pint / Sail | 12.5.43 / 1.32.1 / 1.69.0 |
| MySQL | 8.0 en Compose; estadísticas y vistas contrastadas en esquema exclusivo de pruebas |
| SQLite | Suite en memoria y demo local en archivo exclusivo |

MySQL 8.0 y SQLite son los motores verificados. Las funciones de agrupación temporal tienen implementaciones específicas para ambos; la presencia de otros drivers en `config/database.php` no acredita su soporte. La restricción PHP del manifiesto (`^8.3`) y su plataforma Composer (`8.3.0`) no sustituyen el runtime 8.4 comprobado ni `composer check-platform-reqs`.

## Instalación local con Sail

Requisitos: Git, Bash, Docker con Compose y acceso a las imágenes/dependencias. Usar un checkout nuevo para la instalación o demo. No copiar `.env`, bases, archivos ni credenciales de otro entorno.

```bash
git clone https://github.com/herocris/laravel_backend_api.git
cd laravel_backend_api
umask 077
cp .env.example .env
```

Seleccionar la revisión revisada que incluya el refactor antes de continuar; cambios locales sin commit/push todavía no estarán disponibles al clonar la rama remota.

En ese `.env` nuevo, completar la conexión de desarrollo y un secreto local de MySQL antes de iniciar Compose:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=sucesos_local
DB_USERNAME=sail
DB_PASSWORD=REEMPLAZAR_POR_UN_SECRETO_LOCAL
```

Si aún no existe `vendor`, instalar el lock con el contenedor de Composer de Sail (arranque previo a disponer de `vendor/bin/sail`):

```bash
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$PWD:/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest composer install --no-interaction --prefer-dist
```

Después, ejecutar PHP, Composer y Artisan mediante Sail:

```bash
vendor/bin/sail up -d laravel.test mysql
vendor/bin/sail composer validate --strict
vendor/bin/sail composer check-platform-reqs
vendor/bin/sail artisan key:generate --no-interaction
vendor/bin/sail artisan jwt:secret --no-interaction
vendor/bin/sail artisan migrate --no-interaction
vendor/bin/sail artisan db:seed --class=PermissionCatalogSeeder --no-interaction
vendor/bin/sail artisan l5-swagger:generate --no-interaction
vendor/bin/sail artisan route:list --except-vendor -v --no-interaction
```

Esperar a que MySQL esté saludable antes de migrar. Las migraciones anteriores son para una base nueva propia. `PermissionCatalogSeeder` añade 48 permisos `api` y tres roles sin truncar tablas; no crea usuarios. Si un nombre requerido está eliminado, aborta para exigir una decisión explícita. El registro público crea cuentas sin privilegios. Para otro entorno, designar un usuario existente por su ID exacto y usar `authorization:assign-administrator ID`; no se elige una identidad por nombre ni por ser el primer registro.

No ejecutar `DatabaseSeeder`, `TestingSeeder`, `composer migrate:testing` ni `migrate:fresh` para instalar o demostrar el proyecto: contienen operaciones destructivas o reinicios que no necesita este recorrido. `RolePermissionsSeeder` reemplaza las listas de sus tres roles; reservarlo para una sincronización deliberada.

La API no necesita assets Node para este recorrido. `package.json` contiene herramientas de la portada; no hay lock JS que permita prometer un `npm ci` reproducible. MySQL, Redis, phpMyAdmin y Vite de Compose son servicios de desarrollo: esta configuración no es un despliegue de producción. Para detener los servicios sin borrar volúmenes: `vendor/bin/sail stop`.

Las mutaciones administrativas de usuarios, roles y permisos comparten un mutex SQL en `authorization_locks`, dentro de la misma transacción que la autorización, los cambios y su auditoría. El bloqueo se obtiene antes de Gate, el binding y la validación; el actor y los permisos se consultan de nuevo sin reutilizar la caché compartida de Spatie. También participan las Actions, los seeders no destructivos y `authorization:assign-administrator`. Aplicar las migraciones antes de usar esta versión (`vendor/bin/sail artisan migrate --no-interaction`); si falta la fila de bloqueo, la operación falla sin escribir. Las lecturas no adquieren este mutex. Las escrituras de autorización añadidas fuera de estos puntos deben usar `AdministrativeTransaction` desde su primera lectura de autorización; SQL manual y seeders destructivos no son mecanismos de administración en servicio.

## Autenticación y permisos

JWT permanece en el guard `api`; Sanctum está instalado pero no gobierna este flujo. Login, registro y refresh entregan el JWT en la cookie `token` HttpOnly. El JSON contiene `token_type`, `expires_in` (segundos), `user`, `roles` y `permissions`; no contiene `access_token`. La expiración de la cookie sigue `exp`. Refresh rota e invalida el JWT anterior; logout lo invalida y elimina la cookie.

1. Pedir `GET /api/csrf-cookie`, conservando las cookies de sesión y `XSRF-TOKEN`.
2. Enviar `POST /api/login` con `email` y `password`, cookies y `X-XSRF-TOKEN` con el valor URL-decodificado de `XSRF-TOKEN`. Registro usa `name`, `email`, `password` y también exige CSRF; descarta privilegios enviados por el cliente.
3. Con cookie JWT, enviar las mismas cookies y CSRF en todas las escrituras, incluidos refresh y logout. En el navegador usar credenciales y el origen permitido por `CORS_ALLOWED_ORIGINS`.
4. Los clientes con `Authorization: Bearer <JWT>` pueden consultar/escribir sin CSRF, excepto login y registro, que siempre inicializan sesión y lo exigen. El cliente debe obtener su JWT mediante un flujo autorizado; Swagger no lo extrae de un campo JSON inexistente.

`GET /api/me` devuelve HTTP 200 con este contrato exacto para cualquier usuario autenticado, sin exigir permisos administrativos:

```json
{
  "user": {
    "name": "Nombre",
    "email": "usuario@example.com"
  },
  "roles": [],
  "permissions": []
}
```

`roles` contiene los nombres de los roles vigentes; `permissions`, los nombres de los permisos efectivos, tanto directos como heredados. Ambas listas eliminan duplicados y se serializan como arrays JSON incluso vacías; su orden no forma parte del contrato. Las relaciones se recargan desde la base de datos en cada consulta. Las asignaciones mediante las APIs de Spatie y las operaciones administrativas del proyecto se reflejan en la siguiente petición con el mismo JWT. Spatie invalida la caché de permisos al modificar roles/permisos mediante sus APIs; las asignaciones de usuario se mantienen en las relaciones de cada instancia. `/me` no cachea esta respuesta ni vacía la caché global.

Admite cookie `token` HttpOnly o `Authorization: Bearer`; solo consulta y no emite cookies JWT, renueva, rota ni revoca tokens. No devuelve tokens, hashes, contraseñas, IDs ni fechas internas. Credenciales ausentes, inválidas, expiradas o revocadas conservan HTTP 401 con `{"error":"No autenticado","code":401}`. El frontend puede consultar la autorización después de un 403 sin llamar a `/refresh`.

Cambio incompatible respecto al contrato anterior: `name` y `email` pasan a `user.name` y `user.email`; se retiran `id` y fechas y se añaden `roles` y `permissions`. Los consumidores del modelo directo deben adaptar esas lecturas; el frontend actual ya admite este formato. El CRUD administrativo de usuarios conserva `identificador`, `nombre`, `correo`, `roles` y `permisos`.

Los módulos usan `auth` + `can:<permiso>` y la integración de Spatie con Gate. No hay privilegios universales por nombre de rol. Las reglas actuales son generales: no se han inventado propiedad por usuario, multitenencia ni aprobación de decomisos.

| Acción | Permiso |
| --- | --- |
| Listar, ver, listar eliminados | `<recurso>.view` |
| Crear | `<recurso>.create` |
| Actualizar o restaurar | `<recurso>.update` |
| Eliminar lógicamente | `<recurso>.delete` |
| Descargar evidencia | `<tipo>Confiscation.view` |
| Gráficas / mapa / auditoría | `graph.view` / `map.view` / `activity.view` |

Recursos: `user`, `role`, `permission`, `drug`, `drugPresentation`, `weapon`, `ammunition`, `confiscation`, `drugConfiscation`, `weaponConfiscation`, `ammunitionConfiscation`. `dashboard.view` existe en el catálogo sin endpoint propio.

| Rol configurado | Permisos en una base nueva |
| --- | --- |
| analista | 11: lectura de los ocho módulos de negocio y dashboard/gráficas/mapa |
| operador | 27: lo anterior y crear/actualizar negocio; restaurar usa actualizar |
| administrador | 48: catálogo completo; recibe los permisos activos `api` al inicializar/sincronizar |

Analista y operador no borran, administran usuarios/roles/permisos ni consultan auditoría. Las asignaciones administrativas usan IDs activos del guard `api`; omitir una lista conserva asignaciones, `[]` las revoca; `null`, duplicados, nombres, otro guard y eliminados se rechazan. Un permiso creado posteriormente no se concede por un bypass implícito.
La delegación administrativa comprueba además los permisos efectivos actuales del actor (`api`), directos o heredados. No permite asignar IDs de permisos ni roles con capacidades que el actor no posee. Modificar, borrar o restaurar usuarios/roles exige cubrir todos sus privilegios, incluidos los dormidos por soft delete; se impide así tomar control de cuentas superiores. Se conservan IDs, nombres de campos, respuestas de éxito y listas omitidas/vacías; las operaciones fuera del alcance del actor devuelven 403 con `{"error": mensaje, "code": 403}`. No se añade un permiso de asignación ni un bypass por llamarse administrador.

Los cambios de permisos también comprueban a sus usuarios y roles afectados. Renombrar un permiso asignado hacia una capacidad no poseída y restaurar un permiso eliminado que conserva asignaciones quedan prohibidos. Un permiso eliminado no forma parte de las capacidades efectivas del actor: recuperar esas asignaciones requiere una operación administrativa explícita fuera de este flujo; los permisos sin asignaciones siguen pudiendo restaurarse. El catálogo y las asignaciones existentes no se modifican como parte de esta adaptación.

Límites por minuto: login 5 por cuenta/IP y 30 por IP; registro 5 por IP; CSRF 30 por IP; sesión 30 por usuario; API 60 por usuario; estadísticas/mapa 10 por usuario. El 429 conserva `Retry-After`.

## Contratos API

Las rutas conservan `/api`, nombres singulares y mayúsculas existentes; no se ha añadido un prefijo de versión. Consultar [OpenAPI](storage/api-docs/api-docs.json), sus [fuentes](app/Docs) y el inventario de Artisan. Swagger UI se configura en `api/documentation`; volver a generar el JSON tras editar atributos. El servidor OpenAPI es relativo al origen actual, sin dominios publicados supuestos. Los navegadores administran la cookie HttpOnly; el botón Authorize no permite establecerla manualmente.

- CRUD: `GET/POST /api/<recurso>`, `GET/PUT/PATCH/DELETE /api/<recurso>/{id}`, `GET /api/<recurso>/deleted`, `POST /api/<recurso>/restore/{id}`. Las actualizaciones conservan sus campos obligatorios, también con PATCH.
- Altas, actualizaciones, bajas y restauraciones devuelven el objeto directamente con HTTP **200**, sin wrapper `data`. Los listados devuelven el paginator con `data`, `total`, `current_page`, `per_page`, enlaces y demás metadatos, también cuando están vacíos.
- Listados: `page>=1`, `per_page` entre 2 y 100 (10 por defecto), `sort_by` público permitido por `LIST_FIELDS`, `type=asc|desc` (desc si se indica orden sin dirección). Desempate por ID y, sin orden solicitado, ID ascendente. Filtros parciales por campos escalares públicos permitidos; `%` y `_` son literales. Objetos y arrays de relaciones no son filtros escalares.
- Escrituras de negocio usan atributos españoles: decomiso `fecha`, `observacion`, `direccion`, `departamento`, `municipalidad`, `latitud`, `longitud`; detalle de droga `cantidad`, `peso`, `foto`, `decomiso`, `droga`, `presentacion`. En salida, las relaciones de detalle son objetos anidados. Los nombres internos (`amount`, `confiscation_id`, etc.) producen 422, también junto al nombre público. Campos desconocidos o de solo lectura no se persisten.
- Cantidad: entero positivo hasta 2.147.483.647. Peso: gramos positivos con hasta dos decimales, almacenados en DOUBLE sin migrar ni redondear datos históricos. Coordenadas: latitud −90…90 y longitud −180…180. Las escrituras exigen asociaciones activas; las lecturas históricas admiten catálogos eliminados.
- Gráficas: `GET /api/{drug,weapon,ammunition}GraphIndex`, `period=month|quarter|semester|year`, `typeGraph=bar|line|pie`, fechas inclusivas `start_date/end_date` en `YYYY-MM-DD` e IDs en `drugs/weapons/ammunitions` según el tipo. Admiten lista JSON o array nativo; lista vacía devuelve series vacías. Se rechazan `day`, `total`, fechas invertidas, IDs inexistentes o duplicados. Respuesta: `lineBarData` y `pieData`, cantidades enteras, períodos de calendario y etiquetas homónimas con `(#ID)`.
- Mapa: `GET /api/confiscation/map` con fechas e IDs por tipo. Omitir un tipo o enviar `[]` selecciona ninguno de ese tipo; los tipos combinan con OR. Salida `mapItems` con `id`, `fecha`, `observacion`, coordenadas y relaciones `drug_confiscations`, `weapon_confiscations`, `ammunition_confiscations`. Conserva claves internas de proyección como `confiscation_id`, además de alias; no usa el Resource del CRUD. Sin selecciones devuelve `mapItems: []`.
- Logos: disco público. Evidencias: PNG hasta 2 MiB en `storage/app/private/evidence`; `foto` es una ruta relativa opaca, no una URL pública. Descargar por `GET /api/{drug,weapon,ammunition}Confiscation/{id}/photo`, autenticado y con permiso de lectura; admite soft-deleted. Responde attachment PNG, `private, no-store` y `nosniff`; ausente/no migrada devuelve 404. La demo no necesita ni crea imágenes. Para subir archivos usar multipart; para actualizar con archivos usar POST multipart con `_method=PUT` por el procesamiento de PHP.
- Los errores compartidos usan `{"error": mensaje_o_errores, "code": estado}`: 401 sin autenticación, 403 sin permiso, 404 ausente, 405 método, 419 CSRF, 422 validación y 429 límite. Login con credenciales incorrectas conserva `{"error":"Credenciales incorrectas"}` sin `code`. La autorización precede al binding y validación; los 5xx ocultan detalles con debug desactivado.

## Demo sintética aislada

Preparar la demo en el checkout local con Sail operativo. No cambia `.env` ni utiliza MySQL. `.env.demo` y `database/portfolio-demo.sqlite*` están ignorados por Git. La plantilla no contiene secretos. Comprobar que no hay configuración cacheada ni variables externas `APP_*`, `DB_*`, `SESSION_*` o `CACHE_*` que sustituyan estos valores.

```bash
umask 077
test ! -f bootstrap/cache/config.php
test ! -e .env.demo && cp .env.demo.example .env.demo
test ! -e database/portfolio-demo.sqlite && touch database/portfolio-demo.sqlite
vendor/bin/sail artisan key:generate --env=demo --no-interaction
vendor/bin/sail artisan jwt:secret --env=demo --no-interaction
vendor/bin/sail artisan config:show database.default --env=demo --no-interaction
vendor/bin/sail artisan config:show database.connections.sqlite.database --env=demo --no-interaction
vendor/bin/sail artisan config:show activitylog.database_connection --env=demo --no-interaction
```

No volver a generar claves si `.env.demo` ya existe; retomar con sus claves y datos actuales. Artisan selecciona `APP_ENV=demo` al usar `--env=demo`.

**Continuar solo si se observa `sqlite`, `/var/www/html/database/portfolio-demo.sqlite` y auditoría `null`.** La ruta corresponde al montaje de Sail. Después:

```bash
vendor/bin/sail artisan migrate --env=demo --no-interaction
vendor/bin/sail artisan db:seed --class=PortfolioDemoSeeder --env=demo --no-interaction
```

El seeder comprueba entorno local/demo, motor SQLite, archivo exclusivo y auditoría en la conexión por defecto antes de escribir. Solo permite memoria durante testing. Rechaza usuarios, catálogos, permisos o auditoría preexistentes; una segunda ejecución falla sin reiniciar ni cambiar contraseñas. No forma parte de `DatabaseSeeder` ni del despliegue.

Crea tres cuentas `@demo.invalid` con roles explícitos y contraseñas aleatorias distintas, mostradas únicamente en la consola local; no hay contraseña compartida en el repositorio. Incluye un catálogo ficticio de cada tipo y tres decomisos en enero/abril/julio de 2026, con nueve detalles. Cantidades de droga 1/2/3 (total 6), pesos 12,50/25/37,50 g; armas 1/2/3 y municiones 10/20/30. Textos y coordenadas son inventados. No importa archivos ni datos reales; la auditoría de carga es `Sistema` sin actor.

Iniciar un servidor exclusivo en otra terminal; escucha **solo en el loopback del contenedor**, sin publicar otro puerto ni alterar el servicio principal:

```bash
vendor/bin/sail artisan serve --env=demo --host=127.0.0.1 --port=8016 --no-reload --no-interaction
```

En la terminal del recorrido:

```bash
demo_curl() {
  vendor/bin/sail exec -T --user sail laravel.test sh -c 'umask 077; exec curl -sS "$@"' sh "$@"
}
DEMO_BASE=http://127.0.0.1:8016
DEMO_JAR=/tmp/portfolio-demo-analista.cookies
demo_xsrf() {
  vendor/bin/sail php -r 'foreach (file($argv[1]) as $line) { $f = explode("\t", trim($line)); if (($f[5] ?? null) === "XSRF-TOKEN") { echo rawurldecode($f[6]); } }' "$DEMO_JAR"
}
demo_curl -c "$DEMO_JAR" "$DEMO_BASE/api/csrf-cookie"
read -rsp 'Contraseña local del analista: ' DEMO_PASSWORD
printf '\n'
demo_curl -b "$DEMO_JAR" -c "$DEMO_JAR" -H 'Accept: application/json' \
  -H "X-XSRF-TOKEN: $(demo_xsrf)" \
  --data-urlencode 'email=analista@demo.invalid' --data-urlencode "password=$DEMO_PASSWORD" \
  "$DEMO_BASE/api/login"
unset DEMO_PASSWORD
```

Mantener el cookie jar privado; no copiar tokens/contraseñas a capturas, incidencias o commits. Las llamadas de cURL se ejecutan dentro de Sail y apuntan al servidor demo; no al MySQL ni a la API principal. Detener el servidor con Ctrl+C al terminar.

| Recorrido | Resultado esperado |
| --- | --- |
| Sin cookies: `GET /api/confiscation` | 401 |
| Analista: `GET /api/confiscation?per_page=2` | 200, dos filas, total 3 |
| Analista: mapa con fechas de 2026 y `drugs=[1]` | 200, tres ubicaciones y solo detalles de droga |
| Analista: gráfica trimestral con `drugs=[1]` | Q1/Q2/Q3 con 1/2/3; pie total 6 |
| Analista: crear decomiso o consultar usuarios/auditoría | 403, sin escritura |
| Operador: crear decomiso válido | 200, objeto creado y evento con actor operador |
| Operador: borrar o consultar auditoría | 403 |
| Administrador: consultar auditoría ordenada por ID desc | Evento de creación con `id_usuario` del operador |

Consultas de lectura listas para ejecutar con el jar del analista:

```bash
demo_curl -b "$DEMO_JAR" "$DEMO_BASE/api/confiscation?per_page=2"
demo_curl -b "$DEMO_JAR" --get "$DEMO_BASE/api/confiscation/map" \
  --data-urlencode start_date=2026-01-01 --data-urlencode end_date=2026-12-31 \
  --data-urlencode 'drugs=[1]'
demo_curl -b "$DEMO_JAR" --get "$DEMO_BASE/api/drugGraphIndex" \
  --data-urlencode period=quarter --data-urlencode typeGraph=bar \
  --data-urlencode start_date=2026-01-01 --data-urlencode end_date=2026-12-31 \
  --data-urlencode 'drugs=[1]'
demo_curl -b "$DEMO_JAR" "$DEMO_BASE/api/activity"
```

Para operador y administrador repetir CSRF/login con un jar nuevo (`/tmp/portfolio-demo-operador.cookies` o `/tmp/portfolio-demo-administrador.cookies`), cambiando el correo y la contraseña solicitada. No reutilizar el jar del analista para cambiar de identidad. Crear con el operador y repetir la misma petición con analista para mostrar 403:

```bash
demo_curl -b "$DEMO_JAR" -H 'Accept: application/json' \
  -H 'Content-Type: application/json' -H "X-XSRF-TOKEN: $(demo_xsrf)" \
  --data '{"fecha":"2026-10-15","observacion":"DEMO creada por operador","direccion":"Dirección ficticia","departamento":"Región DEMO","municipalidad":"Municipio ficticio","latitud":14.2,"longitud":-87.2}' \
  "$DEMO_BASE/api/confiscation"
# Con el jar del administrador:
demo_curl -b "$DEMO_JAR" "$DEMO_BASE/api/activity?tipo_de_evento=created&sort_by=identificador&type=desc&per_page=2"
```

Los IDs 1 de los ejemplos corresponden exclusivamente a la demo recién creada. El recorrido altera solo la base sintética. No hay reset público ni endpoint privilegiado de demo. La protección frente a cambios abusivos consiste en mantenerla local y usar el analista para lectura; las cuentas de escritura quedan privadas. Una demo pública requiere otro alcance aprobado: destino, restricción de acceso, gestión de registro público, cuotas y reinicio aislado; no publicar estas cuentas ni esta configuración de desarrollo.

## Verificación y resultados

La suite `phpunit.mysql.xml` incluye pruebas administrativas con dos procesos y conexiones independientes: revocaciones durante la espera, cambios de privilegios en objetivos/roles/permisos, bajas simultáneas y una delegación permitida. Solo se ejecutan en `refactor_statistics_test`; usan `migrate` y limpian sus propios fixtures, sin reiniciar esa base. Requieren las extensiones `pcntl` y `posix`, presentes en Sail.

Antes de tests, comprobar SQLite `:memory:`, auditoría `null`, ausencia de config cacheada y de variables externas que sobrescriban PHPUnit. `tests/TestCase.php` usa `RefreshDatabase` sobre esa base efímera; no se ejecutan seeders destructivos.

```bash
vendor/bin/sail artisan config:show database.default --env=testing --no-interaction
vendor/bin/sail artisan config:show database.connections.sqlite.database --env=testing --no-interaction
vendor/bin/sail artisan config:show activitylog.database_connection --env=testing --no-interaction
vendor/bin/sail bin phpunit tests/Feature/PortfolioDemoTest.php tests/Feature/ApiDocumentationTest.php --no-progress
vendor/bin/sail bin phpunit --no-progress --display-warnings --fail-on-warning
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail composer audit --locked --no-interaction
```

La prueba de documentación regenera en almacenamiento falso, contrasta el JSON versionado y las operaciones/métodos/permisos del router; la de demo recorre permisos, datos, mapa, gráficas y auditoría y prueba rechazos del seeder. El código no tiene un porcentaje de cobertura publicado.

Resultado del paso 16 (17/11/2026): **1.418 pruebas / 8.867 aserciones en SQLite**, sin warnings del runner, y recorrido HTTP real en la demo aislada. OpenAPI se regenera y contrasta con el router. Evidencia MySQL del paso 15: **69 / 720**; no se repitió al no cambiar consultas ni migraciones. Consultar la bitácora para comandos y límites. MySQL se ejecuta con `phpunit.mysql.xml`, esquema dedicado `refactor_statistics_test` en el host de Sail `mysql`; `StatisticsTestCase` rechaza otro destino antes de migrar. El usuario de tests debe tener permiso solo para ese esquema. No sustituirlo por la base de aplicación.

Medición del paso 11: página de 10 sobre 200 detalles sintéticos, SQLite en memoria, de **604 a 8 consultas** y de **800 a 13 modelos hidratados**, contando autorización. Muestras de 159,720 ms y 36,943 ms en dos procesos; son una observación local, no una garantía de latencia ni un benchmark de producción.

Decisiones relevantes: paginar/filtrar en SQL y cargar relaciones antes de representar; Form Requests normalizan entrada y Resources representan salida; imágenes con compensación ante fallo SQL; archivos anteriores conservados para recuperación; gráficas/mapa con caché de 30 segundos por usuario/filtros e invalidación por dominio tras commit. Se mantuvo el monolito y las dependencias existentes. No se añadieron reglas de aprobación ni policies sin condiciones de negocio confirmadas.

## CI, releases y pendientes externos

[checks.yml](.github/workflows/checks.yml) prepara PHP 8.4, Composer/lock/auditoría, formato incremental, sintaxis, SQLite y MySQL aislado. [deploy.yml](.github/workflows/deploy.yml) es manual y prepara un artefacto tras checks; no contacta servidores. La configuración y sus comandos se validaron localmente; no se afirma una ejecución de GitHub Actions ni se muestran badges verdes.

[package.sh](.github/deployment/package.sh) empaqueta código **committed de HEAD** y dependencias de producción, sin `.env`, datos de storage, Compose ni herramientas de desarrollo. Cambios locales sin commit no entran en el paquete. [release.sh](.github/deployment/release.sh) prepara releases conservadas, enlaces a persistencia, activación atómica y rollback de código. Los ensayos locales comprobaron archivos/persistencia y fallo de fases con runtime simulado; también hubo ensayo CLI del paquete con SQLite, migraciones y salud SQL/caché. No acreditan rollback de base de datos, HTTP externo ni recuperación de servidor.

El [procedimiento de 15](refactor-progress.md#procedimiento-operativo-preparado-en-15-y-pendiente-externo) detalla el contrato de runtime pendiente. Antes de desplegar quedan infraestructura y adaptador, secretos persistentes, backups/restauración, HTTPS/CORS/CSRF del cliente, acceso administrativo designado y transición de evidencias históricas públicas a almacenamiento privado (inventario, bloqueo público/CDN, copia y checksum). Publicar o ejecutar acciones remotas requiere autorización explícita. El cierre local del refactor no equivale a producción ni a demo publicada.
