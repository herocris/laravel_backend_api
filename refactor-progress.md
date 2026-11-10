# Progreso del refactor

## Estado para retomar

- Última actualización: 2026-11-10, zona Europe/Madrid.
- Trabajo actual: solicitud directa de protección mediante middleware de Spatie completada; 85 rutas de módulos protegidas, suite 739 pruebas / 3.325 aserciones sin incidencias.
- Paso en curso: 05 parcialmente adelantado por solicitud directa; su aceptación integral sigue pendiente de los prerrequisitos 03–04. No se han iniciado automáticamente otros pasos.
- Siguiente paso: 03, crear permisos y roles estables.
- Próxima acción: completar 03 con el catálogo singular actual y posteriormente 04 para cerrar la escalada de asignaciones, conservando la protección añadida. Antes de integrar en `main`, preparar `15a` por el workflow de despliegue automático existente.
- Base vigente: rama local `refactor/01-base-referencia`, HEAD `79d9827e32e861859319f5a7ec7d377523849016`; cambios locales en los 12 controladores de módulos/auditoría, `bootstrap/app.php`, pruebas afectadas, nuevo `ModuleAuthorizationTest` y esta bitácora. Árbol limpio al inicio de la solicitud actual. Suite final: 739 pruebas / 3.325 aserciones, todas pasan.
- Referencia de preparación: rama `upgrade/laravel-13`, HEAD `ed718be`. Es una referencia histórica; comprobar rama, HEAD y estado actuales al retomar.
- Alcance ejecutado en esta preparación: actualizar `AGENTS.md` y crear `refactor-plan.md` y este archivo. No se corrigió código de aplicación.
- Verificaciones de esta preparación: revisión de los tres documentos y correspondencia de los 16 prompts con el tablero; comprobaciones de whitespace sin incidencias. No se ejecutaron tests ni Pint porque solo se modificó documentación.

## Tablero

Estados: `pendiente`, `en_curso`, `bloqueado`, `completado`. `no_aplica` se usa para componentes condicionales con explicación. Un impedimento temporal de herramientas no permite declarar una verificación exitosa.

| Paso | Trabajo | Estado | Evidencia o pendiente principal |
| --- | --- | --- | --- |
| 01 | Base de referencia | completado | SQLite aislado; 433 pruebas / 2.790 aserciones; Composer válido y sin advisories; contratos y riesgos registrados |
| 02 | Errores HTTP | completado | 15 regresiones HTTP; suite completa 448 pruebas / 2.834 aserciones; Pint y whitespace correctos |
| 03 | Catálogo de permisos | pendiente | Matriz determinista y seeder idempotente |
| 04 | Asignación de privilegios | pendiente | Cerrar escalada y evitar pérdida de asignaciones |
| 05 | Protección de endpoints | en_curso | 85 rutas con auth + permission, 291 regresiones nuevas; aceptación integral pendiente de 03–04 |
| 06 | Policies contextuales | pendiente | Revisar reglas confirmadas; creación condicional |
| 07 | Autenticación y límites | pendiente | Confirmar consumidores y estrategia |
| 08 | Validación e integridad | pendiente | Invariantes, unidades y soft deletes |
| 09 | Archivos | pendiente | Privacidad, transición y compensación |
| 10 | Entrada y salida HTTP | pendiente | Normalización y desacoplamiento de Resources |
| 11 | Consultas y paginación | pendiente | SQL, relaciones y medidas |
| 12 | Estadísticas y mapa | pendiente | Períodos correctos y pruebas MySQL |
| 13 | Caché | pendiente | TTL, reutilización, claves e invalidación |
| 14 | Limpieza | pendiente | Referencias y suite completa |
| 15 | CI y despliegue | pendiente | Preparación local y validación remota diferenciadas |
| 16 | Portafolio | pendiente | Documentación y demo verificables |

## Antecedentes de la auditoría

Observado en esta conversación el 2026-11-08, antes de implementar el refactor. No equivale a una ejecución en futuras sesiones ni a la aceptación del paso 01.

| Comprobación | Resultado histórico |
| --- | --- |
| `vendor/bin/sail composer show --direct` | Laravel 13.40.0; Boost 2.10.4; Spatie Permission 6.25.0; Activitylog 4.12.3; JWT Auth 2.3.1; Sanctum 4.3.3; PHPUnit 12.5.43; L5-Swagger 11.3.0 |
| Entorno | Sail configurado con PHP 8.4; suite configurada con SQLite `:memory:` |
| `vendor/bin/sail artisan test --compact` | 433 pruebas, 2.790 aserciones, todas pasan; duración reportada 11,28 s |
| `vendor/bin/sail composer validate --strict` | Válido |
| `vendor/bin/sail composer audit --locked --format=json` | Sin advisories ni paquetes abandonados reportados |
| `vendor/bin/sail artisan route:list --except-vendor -v` | 91 rutas; administración con auth/throttle, sin permisos efectivos observados |
| Boost: esquema y permisos | MySQL; permisos activos con nombres aleatorios y guard api; relaciones y vistas revisadas sin modificar datos |

Hallazgos que deben contrastarse con el código al entrar en cada paso:

- `UserObserver` sincroniza asignaciones desde el request, incluso durante registro público, y vacía asignaciones si faltan los campos.
- La gestión administrativa requiere autenticación, pero no comprueba capacidades por operación. `superAdminLogin()` crea un usuario sin rol administrativo.
- Los renderizadores mezclan códigos 422/429 y el general puede convertir errores HTTP esperados en 500.
- El limitador global no segmenta por cliente. La cookie JWT tiene duración fija y la estrategia CSRF debe completarse.
- Los listados cargan y transforman todo antes de paginar; Resources acceden a relaciones sin carga anticipada en varios endpoints.
- `queryPeriod()` admite indirectamente períodos que termina agrupando por día; normalización y validación de filtros JSON son inconsistentes.
- La caché usa TTL 0,5, convertido a cero, y se consulta después del trabajo costoso. Sus tests actuales simulan esa implementación sin probar persistencia.
- Fotos se almacenan en disco público y se elimina la anterior antes de confirmar su sustitución.
- Validaciones insuficientes de tipos, límites, coordenadas y asociaciones soft-deleted.
- CI usa PHP 8.2, incompatible con las dependencias instaladas; despliegue elimina la versión anterior antes de preparar la nueva. Persistencia real del servidor sin comprobar.
- Hay collections sin personalización, helpers duplicados y código aparentemente sin uso que requieren análisis de referencias antes de retirarlos.
- No existe `.ai/rules` en la preparación inicial. Si aparece después, aplicar su índice y reglas antes de editar según `AGENTS.md`.

## Decisiones vigentes

| ID | Decisión | Procedencia / alcance |
| --- | --- | --- |
| D01 | Aprovechar la integración de Spatie con Gate para permisos generales | Inicialmente previsto `can`; D07 concreta el middleware solicitado después por el usuario; sin gates duplicados |
| D02 | Policies solo para restricciones sobre registros confirmadas | No inventar propiedad, estados o multitenencia |
| D03 | Refactor incremental en 16 pasos, una conversación por paso | Detalle y prompts en `refactor-plan.md` |
| D04 | Separar instrucciones, plan y progreso | Solicitud explícita; documentación autorizada |
| D05 | Preservar contrato existente salvo correcciones expresas | Registrar impacto y adaptación del cliente |
| D06 | No usar seeders destructivos ni cuentas elegidas por suposición | Configuración nueva idempotente y pruebas aisladas |
| D07 | Usar `permission:<permiso>,api` de Spatie en los módulos, conservando Gate mediante `canAny()` | Solicitud directa del usuario, 2026-11-10; sustituye el alias `can` previsto en D01 para estas acciones |

Agregar decisiones confirmadas con identificador, fecha, motivo e impacto. Distinguir propuestas de decisiones; conservar el historial si se sustituye alguna.

## Decisiones todavía pendientes

| Tema | Resolver al llegar a | Información necesaria |
| --- | --- | --- |
| Roles y permisos exactos | 03 | Operaciones que corresponden a cada rol; catálogo final y asignaciones iniciales |
| Acceso administrativo real | 03–05 | Identidad explícitamente autorizada y mecanismo de bootstrap, sin guardar credenciales |
| Registro y autenticación | 04 / 07 | Política de registro, consumidores, dominios, necesidad de JWT y compatibilidad con cliente |
| Reglas contextuales | 06 | Restricciones concretas sobre registros; policies pueden no ser necesarias todavía |
| Invariantes de datos | 08 | Unidades y precisión de pesos, límites del dominio y asociaciones eliminadas |
| Privacidad y archivos existentes | 09 | Qué imágenes son restringidas y cómo adaptar URLs y almacenamiento |
| Períodos estadísticos | 12 | Semántica de total y cualquier formato ambiguo que el contrato no establezca |
| Infraestructura | 15 | Topología, persistencia, mecanismo de releases y autorización para validación externa |
| Publicación de demo | 16 | Destino y alcance autorizados; material local puede prepararse antes |

No solicitar todas estas respuestas al iniciar: resolver en su paso y continuar trabajo independiente cuando sea posible.

## Matriz de autorización

Matriz vigente del 2026-11-10, contrastada con `route:list --except-vendor --json`: 91 rutas totales, de las que 85 corresponden a módulos y todas tienen `auth` y `PermissionMiddleware`. Guard `api` en todos los permisos; ninguno depende del nombre del rol. Permisos directos y heredados de roles son válidos. No se asignaron permisos a cuentas reales ni se decidió la distribución entre roles.

`<recurso>` corresponde exactamente a `user`, `role`, `permission`, `drug`, `drugPresentation`, `weapon`, `ammunition`, `confiscation`, `drugConfiscation`, `weaponConfiscation` y `ammunitionConfiscation`. Todos conservan sus URLs y nombres de rutas.

| Método y ruta | Acción | Permiso |
| --- | --- | --- |
| GET/HEAD `api/<recurso>` | index | `<recurso>.view` |
| GET/HEAD `api/<recurso>/{registro}` | show | `<recurso>.view` |
| GET/HEAD `api/<recurso>/deleted` | indexDeleted | `<recurso>.view` |
| POST `api/<recurso>` | store | `<recurso>.create` |
| PUT/PATCH `api/<recurso>/{registro}` | update | `<recurso>.update` |
| POST `api/<recurso>/restore/{registro}` | restore | `<recurso>.update` |
| DELETE `api/<recurso>/{registro}` | destroy | `<recurso>.delete` |
| GET/HEAD `api/{drug,weapon,ammunition}Confiscation/{idConfiscation}/confiscation` | indexByConfiscation | `{drug,weapon,ammunition}Confiscation.view`, respectivamente |
| GET/HEAD `api/{drug,weapon,ammunition}GraphIndex` | graphIndex | `graph.view` |
| GET/HEAD `api/confiscation/map` | mapConfiscations | `map.view` |
| GET/HEAD `api/activity` | __invoke | `activity.view` |

El catálogo incluye `dashboard.view`, sin endpoint equivalente actual: no se inventa uno ni se impone ese permiso al resto de módulos. `login/register` siguen públicos y con throttle; `me/logout/refresh` siguen con auth y throttle, sin permiso administrativo. La raíz web no pertenece a estos módulos. Se usan 47 permisos distintos del catálogo de 48; la preparación idempotente y asignación inicial de 03 continúan pendientes. Cambio de contrato: un usuario autenticado sin permiso ahora recibe 403 antes de validar entrada o resolver modelos; invitados reciben 401, y usuarios autorizados conservan sus respuestas anteriores. El cliente debe gestionar 403 y disponer de asignaciones explícitas; el nombre `administrador` por sí solo no concede nada.

## Registro de sesiones y verificaciones

### Protección de acciones solicitada — 2026-11-10, Europe/Madrid — completado (alcance directo)

- Inicio: rama `refactor/01-base-referencia`, árbol limpio. Leídos bitácora, apartado 05 del plan y skills de Laravel y pruebas. `.ai/rules` sigue ausente.
- Solicitud actual autoriza proteger acciones con los permisos ya creados. El catálogo vigente de `DatabaseSeeder` usa nombres sin prefijo `frontend.` y guard `api`; no equivale a haber completado el seeder idempotente ni el bootstrap de 03. No se asignarán privilegios a usuarios reales ni se ejecutarán seeders destructivos.
- Inventario inicial mediante `vendor/bin/sail artisan route:list --except-vendor -v`: 91 rutas, módulos con `auth` y throttle, sin comprobación de permisos. Composer confirma Laravel 13.40.0, Spatie Permission 6.25.0 y PHPUnit 12.5.43. Consultado Boost para middleware y recursos; implementación instalada de `PermissionMiddleware` contrastada: usa `$user->canAny()`, conservando Gate.
- Decisión específica de esta solicitud: usar middleware `permission` de Spatie, tal como solicita el usuario, en lugar del alias `can` previsto originalmente por D01. No se añaden gates ni bypass de administrador. `auth` precede a permisos y estos precederán al binding para evitar 404 antes de 403.
- Matriz prevista: recursos `user`, `role`, `permission`, `drug`, `drugPresentation`, `weapon`, `ammunition`, `confiscation`, `drugConfiscation`, `weaponConfiscation`, `ammunitionConfiscation`; `index/show/indexDeleted/indexByConfiscation` → `<recurso>.view`; `store` → `.create`; `update/restore` → `.update`; `destroy` → `.delete`. `graphIndex` → `graph.view`, `mapConfiscations` → `map.view`, auditoría → `activity.view`. No existe endpoint dashboard. Roles autorizados: cualquiera con el permiso explícito, directo o heredado; sin matriz de roles inventada. Autenticación propia y registro/login conservan su contrato actual.
- Pendientes al inicio: implementar middleware, adaptar preparación explícita de pruebas, cubrir 401/403/permiso correcto y revisar inventario efectivo; ejecutar suite completa y Pint. Riesgo previo conocido: la gestión de asignaciones/registro de 04 aún no se ha refactorizado; esta tarea no acredita cerrar esa escalada.
- Hito: regresión inicial de `ModuleAuthorizationTest` falla como se esperaba (GET de usuarios sin permiso devuelve 200 en vez de 403, código 1). Añadidos middlewares por acción a los 11 controladores de recursos y a auditoría usando el patrón `HasMiddleware` existente, conservando transformaciones. `bootstrap/app.php` registra `permission` y lo prioriza antes de `SubstituteBindings`, después de autenticación.
- Matriz HTTP tras implementación: `vendor/bin/sail artisan test --compact tests/Feature/ModuleAuthorizationTest.php`, código 0, **291 passed / 486 assertions**. Cubre 96 combinaciones de ruta/método en tres estados (invitado, sin permiso, permiso exacto), permiso ajeno insuficiente, permiso heredado de rol y rol `administrador` sin privilegios. Para recursos inexistentes, el permiso correcto permite llegar al 404; para altas inválidas al 422; los éxitos CRUD con datos válidos permanecen cubiertos por sus pruebas existentes. Gráficas probadas con período `day` compatible con SQLite, sin acreditar otros períodos MySQL.
- Adaptación: helper `grantPermissions` en `tests/TestCase.php` concede únicamente los permisos explícitos solicitados por cada test; 78 archivos CRUD/auditoría adaptados después de autenticar al actor. El helper `superAdminLogin()` no recibe privilegios implícitos. El listado de permisos incluye ahora el permiso necesario para consultarlo (total 11 con diez fixtures y un permiso de acceso); prueba 404 de binding recibe `drug.view`.
- Suite completa antes de formato: `vendor/bin/sail bin phpunit --display-warnings --no-progress`, código 0, **739 tests / 3325 assertions**, 18,585 s. Inventario final `vendor/bin/sail artisan route:list --except-vendor --json`, código 0: 91 rutas totales; 85 de módulos, sin ninguna carente de auth o permiso; 47 permisos distintos, todos existentes en el catálogo actual. Las cinco rutas de autenticación conservan sus middlewares previos.
- Formato: `vendor/bin/sail bin pint --dirty --format agent`, código 0; además de las adiciones, normaliza formato previo de archivos modificados (imports, espacios, comillas, PHPDoc y nombres PHPUnit). Suite completa posterior al formato, mismo comando PHPUnit, código 0: **739 tests / 3325 assertions**, 18,751 s, sin advertencias. `git diff --check` sin diagnósticos.
- Aislamiento: `phpunit.xml` y `.env.testing` mantienen SQLite `:memory:`, sin configuración cacheada ni cambios de conexión en los tests. No se han ejecutado migraciones/seeders sobre MySQL ni operaciones con datos reales. No se cambian dependencias, seeders, rutas, autenticación ni reglas de asignación. Sin commits, push o despliegue.
- Cierre: solicitud directa satisfecha; archivos funcionales en `app/Http/Controllers/{Admin,}*Controller.php` y `bootstrap/app.php`, preparación en `tests/TestCase.php`, pruebas de módulos/auditoría y `tests/Feature/HttpExceptionResponseTest.php`, nuevo `tests/Feature/ModuleAuthorizationTest.php`, más esta bitácora. Contrato e inventario registrados arriba. Limitaciones: 03 y 04 siguen pendientes; registro/asignaciones todavía pueden permitir escalada y requieren su corrección separada, por lo que no se declara completado el paso 05 integral ni el refactor de seguridad. Siguiente acción concreta: preparar 03 con los permisos vigentes y luego cerrar 04, sin iniciar esos pasos en esta sesión.

### Paso 02 — 2026-11-08, Europe/Madrid — completado

- Inicio: rama `refactor/01-base-referencia`, HEAD `9bbcefff060ce8b424af4c7548cf9673dc7c229d`; único cambio previo: bitácora del paso 01, conservada. Sin commits, push ni despliegues.
- Prerrequisitos: 01 completado según evidencia registrada y código actual contrastado; leídos `AGENTS.md`, plan 02 y skills de Laravel/pruebas con sus reglas aplicables. `.ai/rules` sigue ausente, sin reglas adicionales por ruta o palabra clave que consultar.
- Entorno: `vendor/bin/sail composer show --direct` falló dentro del sandbox con `Docker or Podman is not running.`; repetido con acceso autorizado a Docker, código 0: Laravel 13.40.0, Spatie Permission 6.25.0 y PHPUnit 12.5.43. Boost `search-docs` consultado para renderizado de excepciones y aserciones HTTP; contrastada la normalización de `AuthorizationException` a excepción HTTP en el framework instalado.
- Aislamiento reconfirmado antes de pruebas: `phpunit.xml` y `.env.testing` usan SQLite `:memory:`; sin configuración cacheada, `DB_URL`, conexión alternativa de Activitylog ni cambios de conexión/seeders en tests. `config:show ... --env=testing --no-interaction` mediante Sail: `database.default=sqlite`, `database.connections.sqlite.database=:memory:`, `activitylog.database_connection=null` (código 0 en los tres).
- Defectos contrastados: renderizador general convierte rechazos de autorización en 500; roles/permisos inexistentes devuelven HTTP 429 con cuerpo 422; renderizadores 405/404/throttle descartan cabeceras. Alcance: `bootstrap/app.php`, regresiones de errores HTTP y esta bitácora, sin proteger todavía rutas productivas ni definir catálogo de permisos.
- Hito de regresiones: generado `tests/Feature/HttpExceptionResponseTest.php` con `artisan make:test --phpunit HttpExceptionResponseTest --no-interaction` (código 0). Primera ejecución: 9 fallos y 6 éxitos (24 aserciones); dos fallos de preparación se debían a crear permisos sin actor, defecto de auditoría ya registrado para 14. Ajustada la preparación para autenticar al usuario antes de crear el permiso, sin desactivar eventos ni autorización. Reejecución del rechazo real de `can`: falla con HTTP 500 en lugar de 403, código 1, antes de corregir aplicación.
- Implementación: renderizador de `HttpExceptionInterface` antes de `Throwable`, preservando estado, cabeceras y forma `error`/`code`; mensajes 5xx genéricos sin detalles con debug desactivado. Renderizadores específicos 404/405/throttle transfieren sus cabeceras; roles/permisos inexistentes responden HTTP 422 coherente con el cuerpo. Contrato de diagnóstico del renderizador general con debug activado conservado.
- Pruebas tras corregir: `vendor/bin/sail artisan test --compact tests/Feature/HttpExceptionResponseTest.php`, código 0, **15 passed (44 assertions)**, 0,47 s. Cubren 401 real, 403 mediante `auth` + `can` con Spatie (sin mocks ni gates duplicados), éxito con permiso explícito, 404 de URL y binding, 405 con `Allow`, validación 422, asignaciones Spatie inexistentes 422, throttle real 429 con tiempo congelado y cabeceras, excepción HTTP genérica con challenge, mensaje vacío y errores 500/503 con debug desactivado y diagnóstico debug activado. Las rutas auxiliares solo se registran dentro de los tests.
- Verificaciones finales mediante Sail: `vendor/bin/sail bin pint --dirty --format agent`, código 0, aplica formato a `bootstrap/app.php`; comprobación explícita del archivo nuevo con `vendor/bin/sail bin pint tests/Feature/HttpExceptionResponseTest.php --format agent`, código 0, sin cambios. `vendor/bin/sail artisan test --compact` después del formato, código 0: **448 passed (2834 assertions), Duration: 11.42s**. La suite incluye las 15 regresiones nuevas y las 433 pruebas previas; sin fallos pendientes.
- Revisión final: `git diff --check` sin incidencias y comprobación de whitespace del archivo nuevo con `git diff --no-index --check /dev/null tests/Feature/HttpExceptionResponseTest.php` sin diagnósticos (código 1 esperado por archivo nuevo). Revisados diff, archivo nuevo y reglas de skills aplicables. Archivos finales: `bootstrap/app.php`, `tests/Feature/HttpExceptionResponseTest.php` y `refactor-progress.md`. Cambios de estilo en bootstrap aplicados por Pint, incluida retirada de un import sin uso.
- Impacto de contrato autorizado en 02: rechazos de autorización devuelven 403 en lugar del 500 defectuoso; roles/permisos inexistentes devuelven HTTP 422, ya presente en su cuerpo, en lugar de 429. Otros errores HTTP antes capturados como 500 conservan su estado. El cliente debe tratar 403 como falta de autorización, 422 como entrada inválida y reservar reintentos por límite para 429 con `Retry-After`; se mantiene la forma JSON y los textos existentes de los renderizadores específicos. Los errores HTTP 5xx ocultan mensajes internos con debug desactivado y mantienen estado/cabeceras. No se modificó ningún cliente.
- Cierre: aceptación de 02 satisfecha. Sin bloqueos ni verificaciones pendientes de este paso. No se modificaron rutas productivas, modelos, dependencias, datos reales ni workflow; sin commits, push o despliegue. La protección efectiva de administración sigue pendiente de 03–05; la suite verde de 02 no acredita esa protección. Se conserva la limitación de auditoría sin actor para 14.
- Entrega de 03: inventariar acciones y catálogo determinista con guard `api`, preparar seeder idempotente y mecanismo de asignación inicial sin ejecutar sobre datos reales; resolver decisiones de roles/identidad solo cuando sean necesarias. `15a` sigue pendiente antes de integración en `main`. No se inicia 03 en esta sesión.

### Preparación documental — 2026-11-08

- Solicitud: disponer de prompts para cada paso y contexto persistente para conversaciones distintas.
- Cambios: extensión del `AGENTS.md` existente fuera del bloque Boost; plan con 16 prompts; tablero y bitácora iniciales.
- Decisiones: no ejecutar pasos de implementación durante esta preparación; no marcar pruebas históricas como actuales.
- Verificaciones de los documentos: `git diff --check` sin incidencias; `git diff --no-index --check /dev/null refactor-plan.md` y su equivalente para `refactor-progress.md` sin diagnósticos de whitespace (código 1 esperado por ser archivos nuevos distintos de `/dev/null`). Revisión de 16 secciones y prompts, 16 filas del tablero, referencias locales y conservación del bloque Boost de `AGENTS.md`.
- Pruebas de aplicación y Pint: no ejecutados en esta preparación, al ser cambios exclusivamente documentales. Los resultados de la auditoría permanecen identificados como históricos.
- Siguiente acción: ejecutar 01 en una nueva conversación.

### Paso 01 — 2026-11-08, Europe/Madrid — completado

- Inicio: árbol limpio en `upgrade/laravel-13`, HEAD `9bbcefff060ce8b424af4c7548cf9673dc7c229d` (`plan de refactor para app`). Rama local creada: `refactor/01-base-referencia` (el primer intento encontró restricciones de escritura en `.git`; repetido con autorización, código 0). Sin commits, push ni despliegues.
- Prerrequisitos: leídos `AGENTS.md`, plan 01 y bitácora; `.ai/rules` no existe. Activadas las skills de Laravel y pruebas para revisar configuración, contratos y aislamiento.
- Alcance: base documental y verificaciones actuales; sin cambios de lógica, dependencias, tests o despliegue. Único archivo de trabajo previsto: `refactor-progress.md`.
- Entorno: el primer `vendor/bin/sail composer show --direct` dentro del sandbox devolvió `Docker or Podman is not running.`. Repetido con acceso autorizado a Docker fuera del sandbox: código 0; Laravel 13.40.0, Boost 2.10.4, Spatie Permission 6.25.0, Activitylog 4.12.3, JWT Auth 2.3.1, Sanctum 4.3.3, PHPUnit 12.5.43, Pint 1.32.1, Sail 1.69.0 y L5-Swagger 11.3.0.
- Hito previo a la suite: `phpunit.xml` y `.env.testing` seleccionan SQLite `:memory:`; no hay `bootstrap/cache/config.php`, `DB_URL` ni conexión alternativa de Activitylog en los archivos de entorno revisados. Consultas Sail `config:show ... --env=testing --no-interaction`: `database.default=sqlite`, `database.connections.sqlite.database=:memory:`, `activitylog.database_connection=null`. Tests sin cambios de conexión ni llamadas a seeders; `RefreshDatabase` opera únicamente sobre la base efímera. Se puede ejecutar la suite sin preparar ni tocar la base MySQL de la aplicación.
- Verificaciones ya ejecutadas mediante Sail: PHP CLI 8.4.27; `composer validate --strict` válido; `composer audit --locked --format=json` sin advisories, abandonados, filtros ni cooldown. `artisan route:list --except-vendor -v --no-interaction`: 91 rutas; `auth` resuelve al middleware de Laravel, guard configurado `api` con driver JWT.
- CI contrastada: `.github/workflows/deploy.yml` contiene despliegue automático al push a `main`, PHP 8.2 y borrado de la versión anterior. Solo inspeccionado; antes de integrar en `main` preparar la excepción `15a` del plan. No se modifica ni ejecuta el workflow en 01.
- Suite: primer intento `vendor/bin/sail artisan test --compact --no-interaction`, código 2: PHPUnit rechaza `--no-interaction` reenviado por el comando de tests; no ejecutó pruebas. Repetido como `vendor/bin/sail artisan test --compact`, código 0: **433 passed (2790 assertions), Duration: 11.36s**. Esta excepción de sintaxis no cambia configuración ni cobertura. Sin fallos de aplicación observados en la base actual.

#### Entorno reproducible y verificaciones actuales

Todos los comandos PHP/Artisan/Composer se ejecutaron en el contenedor Sail con acceso autorizado a Docker; no se usó PHP del host, ni se instalaron o actualizaron dependencias. Sail ya estaba operativo; no fue necesario iniciar ni detener servicios.

| Comando / configuración | Resultado observado |
| --- | --- |
| `vendor/bin/sail php --version` | PHP 8.4.27 CLI, Xdebug 3.5.3 |
| `vendor/bin/sail composer show --direct` | Versiones instaladas enumeradas en el inicio de esta sesión; Laravel 13.40.0 y PHPUnit 12.5.43 |
| `vendor/bin/sail artisan config:show database.default --env=testing --no-interaction` | `sqlite` |
| `vendor/bin/sail artisan config:show database.connections.sqlite.database --env=testing --no-interaction` | `:memory:` |
| `vendor/bin/sail artisan config:show activitylog.database_connection --env=testing --no-interaction` | `null`, utiliza conexión por defecto |
| `vendor/bin/sail artisan route:list --except-vendor -v --no-interaction` | 91 rutas, con middleware expandido |
| `vendor/bin/sail artisan route:list --except-vendor --json --no-interaction` | 90 rutas API y raíz web; ninguna con middleware de autorización `can`, roles o permisos |
| `vendor/bin/sail artisan test --compact` | Código 0, 433 pruebas y 2.790 aserciones; 11,36 s reportados por el runner |
| `vendor/bin/sail composer validate --strict` | `./composer.json is valid`, sin diagnósticos |
| `vendor/bin/sail composer audit --locked --format=json` | Sin advisories, paquetes abandonados, filtros ni cooldown; código 0 |
| `git diff --check` y revisión del diff / `git status --short` | Sin incidencias de whitespace; solo `refactor-progress.md` modificado, sin archivos nuevos ni cambios de aplicación |

- Reproducción: usar dependencias instaladas según `composer.lock`, contenedores Sail y `phpunit.xml` actuales. `docker-compose.yml` configura runtime PHP 8.4 y MySQL 8.0 para la aplicación; la suite usa SQLite en memoria. Antes de repetirla, comprobar que no apareció configuración cacheada ni variables externas `DB_*`/Activitylog que sustituyan el destino: los `<env>` de PHPUnit no llevan `force`.
- `phpunit.xml`: suites `Unit` y `Feature`, bootstrap `vendor/autoload.php`, fuente `app`; `APP_ENV=testing`, bcrypt 4, caché/sesión/correo `array`, cola `sync`. `.env.testing` también apunta a SQLite en memoria, aunque sus valores de caché/sesión/cola son `database`: prevalecen los de PHPUnit durante la suite.
- `tests/TestCase.php` usa `RefreshDatabase`; no se invocaron seeders destructivos, el script `composer migrate:testing`, truncados ni comandos manuales de migración. Las migraciones automáticas de tests se ejecutan sobre SQLite efímero. `tests/UnitTestCase.php` arranca Laravel para probar helpers; no equivale a una suite unitaria pura.
- Manifiestos conservados: PHP `^8.3` y plataforma Composer 8.3.0, Laravel `^13.0`, PHPUnit `^12.0`; ejecución real en PHP 8.4.27. `package.json` declara Vite `^6.0`, plugin Laravel Vite `^1.0`, Axios `^1.7.4`, Tailwind `^3.4.13`; son restricciones del manifiesto, no nuevas comprobaciones de paquetes JS instalados. No se ejecutó build al no cambiar frontend.

#### Contratos relevantes que conservar

- Rutas actuales sin segmento de versión, prefijo `/api`, nombres y mayúsculas existentes. Recursos `user`, `permission`, `role`, `ammunition`, `confiscation`, `drugPresentation`, `drug`, `weapon`, `ammunitionConfiscation`, `drugConfiscation`, `weaponConfiscation`: `GET`/`POST` colección, `GET`/`PUT|PATCH`/`DELETE` por identificador; además `GET /{recurso}/deleted` y `POST /{recurso}/restore/{parametro}` con binding `withTrashed`. Son 77 rutas de recursos.
- Autenticación: `POST /api/login` y `/api/register` públicos con `throttle:global`; `GET /api/me`, `POST /api/logout` y `/api/refresh` autenticados. En total, 88 rutas API autenticadas y dos públicas. El alias efectivo `auth` usa el middleware Laravel y guard `api`/driver `jwt`, provider `users`. `jwt.auth` apunta a `AuthenticateJWT`, pero no se aplica a estas rutas; los comentarios que atribuyen todo `auth` a lectura de cookie no describen el cableado real.
- Respuesta de sesión: `token_type`, `expires_in` en segundos, `user` con `name`/`email`, `roles` y `permissions` por nombre; JWT en cookie `token` con duración literal de 60 minutos. `me()` devuelve el usuario serializado directamente; logout devuelve `message: Logout successful` y olvida cookie. Login inválido: 401 con `error: Credenciales incorrectas`. El flujo y atributos de registro no se normalizaron en 01.
- Operaciones especiales: `GET /api/activity`; `GET /api/confiscation/map` (sin nombre de ruta); `GET /api/{ammunition,drug,weapon}GraphIndex`; `GET /api/{ammunition,drug,weapon}Confiscation/{idConfiscation}/confiscation`. Mantener sus URIs y nombres definidos en `routes/api.php`.
- Entrada/salida mediante Resources españoles y `TransformInput` en `store`/`update`: por ejemplo droga `identificador`, `descripcion`, `logo`; usuario `identificador`, `nombre`, `correo`, arrays de IDs `roles`/`permisos`; decomiso `identificador`, `fecha`, `observacion`, `direccion`, `departamento`, `municipalidad`, `latitud`, `longitud`. Detalle de droga: `cantidad`, `peso`, `foto`, objetos `decomiso`, `droga`, `presentacion`. No renombrar a los atributos internos ni introducir un wrapper adicional sin transición explícita.
- `showOne()` devuelve el recurso directamente, HTTP 200 por defecto, también en altas, actualizaciones, eliminaciones y restauraciones revisadas. `showAll()` devuelve `data` y metadatos del paginator cuando hay elementos; colección inicialmente vacía devuelve solo `{"data":[]}`. Parámetros `page`, `per_page` (10 por defecto, validación entero 2–100), `sort_by`, `type=asc` y filtros parciales por columnas transformadas; los enlaces conservan query string. Confirmado con `ApiResponser` y pruebas de listado/alta de drogas.
- Gráficas: entrada `period`, `start_date`, `end_date`, `typeGraph` y selección de IDs; salida `lineBarData` (`name` más descripciones dinámicas) y `pieData` (`name`, `value`). Algunos controladores decodifican selecciones JSON mientras sus requests no exigen array; se registra la inconsistencia para 10/12, sin inventar un contrato corregido.
- Errores compartidos: `{"error": mensaje_o_errores,"code": estado}`; 401 `No autenticado`, 404 por URL o modelo, 405 y validación 422. El comportamiento defectuoso de 403, roles/permisos y cabeceras se corrige en 02; preservar la forma JSON no obliga a conservar esos defectos.

#### Riesgos actuales contrastados y límites de esta base

| Paso destino | Evidencia actual y pendiente |
| --- | --- |
| 02 | `bootstrap/app.php`: renderer general de `Throwable` responde 500 sin rama para errores HTTP esperados; roles/permisos inexistentes incluyen `code=422` con HTTP 429. Throttle no transfiere cabeceras de la excepción. `ErrorResponseTest` verifica el helper, no el rechazo HTTP real de autorización. Añadir regresiones de 403, clasificación, debug desactivado y cabeceras en 02. |
| 03–05 | Rutas efectivas sin autorización por capacidad; requests revisados devuelven `authorize=true`. `UserObserver` sincroniza roles/permisos desde el request y usa arrays vacíos si faltan. Registro público crea `User` con ese observer. `superAdminLogin()` solo crea un usuario, sin privilegios. Factory de permisos usa nombres aleatorios y guard `api`; ambos seeders existentes truncan tablas. No se releyeron asignaciones de usuarios reales ni se creó una matriz final. |
| 07 | `AppServiceProvider` usa `Limit::perMinute(60)` sin `by`; cookie con duración fija y atributos efectivos dependientes de configuración. Revisar consumidores, transporte JWT y CSRF antes de cambiar estrategia; la presencia de Sanctum no demuestra que se use. |
| 08–09 | Requests de decomisos exigen coordenadas sin rangos; pesos numéricos sin límites y relaciones con `exists` sin excluir eliminados. `DrugConfiscationObserver` almacena `foto` en disco público y borra la anterior antes de guardar sustitución. Unidades, privacidad y transición siguen pendientes. |
| 10–11 | `TransformInput` reemplaza las claves mediante Resources; listados revisados hacen `all()`/`get()` antes de transformar y paginar. `UserResource` y `DrugConfiscationResource` acceden a relaciones directamente. N+1 y coste deben medirse en sus pasos; no hay medidas nuevas de rendimiento SQL. |
| 12 | `queryPeriod()` solo distingue mes/año; día, trimestre, semestre y total caen en `DATE(full_date)`. Los requests admiten esos períodos; controladores usan funciones MySQL. Las pruebas actuales de vistas cubren agregados y recreación en SQLite, sin demostrar equivalencia en MySQL. |
| 13 | `cacheResponse()` consulta después de cargar/transformar/paginar y pasa TTL `30 / 60` (0,5). `CacheResponseTest` simula `Cache::remember` con ese valor y no demuestra persistencia real. No se modificó ni se midió la caché en 01. |
| 15 / 15a | Workflow automático en `main`, PHP 8.2 incompatible con manifiestos actuales, comentario de PHPUnit 11 frente a 12 instalado; tests y despliegue en el mismo job, empaquetado amplio y borrado previo de versión anterior. Preparar PHP compatible y separación CI/despliegue como `15a` antes de integrar; no implementado en 01 por su alcance explícito. Estado remoto, persistencia del servidor y rollback sin verificar. |

- Suite verde significa base reproducida, no seguridad corregida. No se hicieron pruebas HTTP manuales contra datos reales, suite MySQL, revisión exhaustiva de componentes sin uso (14), cambios de configuración ni validación remota. Los hallazgos de componentes no inspeccionados siguen como antecedentes, no nuevas conclusiones.
- Cambios de contrato o transición de datos en 01: ninguno. No se añadieron pruebas porque no cambió comportamiento. Pint no ejecutado: ningún archivo PHP modificado. No se necesitó `search-docs`: el único cambio es documental, sin adoptar ni modificar APIs del ecosistema.
- Decisiones: se mantienen D01–D06; no se eligieron roles, identidad administrativa, consumidores, reglas de negocio ni infraestructura. La preparación de `15a` queda pendiente de una sesión específica antes de integración; no bloquea el refactor local de 02.
- Cierre: aceptación de 01 satisfecha mediante entorno, aislamiento, contratos, riesgos y resultados actuales documentados. Único archivo modificado: `refactor-progress.md`. Siguiente acción: leer `bootstrap/app.php`, pruebas HTTP existentes, `tests/Unit/ApiResponser/ErrorResponseTest.php`, esta sesión y el prompt 02; corregir únicamente la semántica de errores y añadir regresiones reales. No se ha iniciado 02.

### Plantilla para cada sesión de un paso

Duplicar esta sección al comenzar y sustituir los campos. Mantener un resumen breve para retomar y conservar evidencias históricas suficientes sin pegar logs completos.

```text
Fecha y zona:
Paso / estado al iniciar:
Rama y referencia Git (o cambios locales si no hay commit):
Prerrequisitos contrastados:
Objetivo y alcance de esta sesión:
Archivos modificados y motivo:
Decisiones confirmadas (IDs) / propuestas pendientes:
Cambios de contrato o transición de datos:
Verificaciones: comando exacto, entorno, resultado observado y resumen de fallos:
Verificaciones no ejecutadas y motivo:
Criterios de aceptación satisfechos / pendientes:
Estado al cerrar (actualizar también tablero y resumen superior):
Bloqueo y dato o acción necesarios, si corresponde:
Próxima acción concreta y archivos que debe leer la siguiente sesión:
```

## Reanudación tras una interrupción

Lee el resumen superior y la última sesión del paso. Contrasta Git y los archivos afectados antes de continuar. Repite verificaciones únicamente si están pendientes, quedaron interrumpidas, cambiaron los archivos relevantes o existe una duda concreta sobre su vigencia. No reconstruyas todo el proyecto ni vuelvas a ejecutar trabajo ya comprobado por rutina.

Si no hubo oportunidad de registrar la última acción, comprueba el diff y el estado real de procesos y archivos; no supongas que un comando interrumpido terminó correctamente. Registra lo confirmado y deja explícito lo que no pudo comprobarse.
