# Plan de refactor de la API de gestión de sucesos

## Objetivo y uso

Corregir autorización, integridad y comportamiento; separar responsabilidades cuando aporte valor; medir rendimiento y preparar un proyecto demostrable ante reclutadores. Mantener un monolito Laravel y aprovechar las dependencias existentes.

Cada sección contiene un prompt completo para iniciar una conversación independiente en este repositorio. Copia el bloque del paso correspondiente. También puedes pedir: «Ejecuta el paso 01 de refactor-plan.md siguiendo AGENTS.md y el estado de refactor-progress.md».

Los prompts remiten a archivos del repositorio: no necesitan pegar conversaciones anteriores. Los nombres de estos documentos son una convención de este proyecto; no son archivos que la herramienta lea automáticamente por su nombre. `AGENTS.md` exige leerlos para este trabajo.

## Protocolo de ejecución

1. Lee las instrucciones aplicables, el resumen actual de `refactor-progress.md`, sus decisiones relevantes y la sección del paso seleccionado. Inspecciona únicamente el código, pruebas y documentación necesarios para ese alcance.
2. Comprueba Git, prerrequisitos y entorno. Confirma versiones antes de usar APIs. Activa las skills correspondientes y consulta Boost cuando cambien APIs o comportamiento del ecosistema Laravel.
3. Explica brevemente el alcance y los defectos observados. Ejecuta los cambios autorizados; solicita aclaración solo cuando una decisión imprescindible no pueda resolverse con el repositorio o las decisiones ya registradas.
4. Añade o ajusta regresiones de comportamiento y ejecuta las pruebas afectadas. Actualizar expectativas exige justificar el cambio de contrato; conserva cobertura útil. No sustituyas pruebas de seguridad por mocks de autorización.
5. Si modificaste PHP, ejecuta `vendor/bin/sail bin pint --dirty --format agent`. Ejecuta `git diff --check` y revisa también archivos nuevos. Amplía pruebas cuando el alcance compartido o un fallo lo justifique.
6. Actualiza estado y evidencia. Si no puedes verificar algo, indícalo y deja el paso sin completar cuando esa verificación sea necesaria. Termina con una entrega breve y una siguiente acción específica.

Los comandos PHP, Artisan, Composer y Node se ejecutan mediante Sail. Las comprobaciones Git y las lecturas o ediciones de archivos usan las herramientas normales del repositorio. No se exige pedir confirmación entre cada acción autorizada.

## Orden y dependencias

Ejecutar 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 → 11 → 12 → 13 → 14 → 15 → 16. El paso anterior debe estar completado, o debe existir una excepción justificada y registrada. El paso 06 puede cerrarse sin nuevas policies si no hay reglas contextuales confirmadas.

Los pasos 02–05 forman un bloque de seguridad: no tratar una etapa intermedia como administración protegida ni desplegar el endurecimiento incompleto. Preparar el acceso administrativo autorizado antes de activar restricciones en datos reales. La identidad y asignación en producción necesitan instrucciones explícitas; no deducirlas de nombres, correos ni del primer usuario.

Si el despliegue automático está activo, adelantar del paso 15 la preparación de la corrección de PHP y la separación entre CI y despliegue antes de integrar cambios. Registrar esta excepción como `15a`; no ejecutarla contra infraestructura ni dar por terminado el paso 15 completo.

## Decisiones de partida

- Spatie administra permisos y roles; `can` aplica permisos generales. Policies solo para condiciones reales sobre registros. Mantener capacidades de policies como `update` diferenciadas de permisos como `confiscations.update` para evitar autorizaciones anticipadas por coincidencia de nombre.
- Mantener el guard `api` mientras siga siendo el guard efectivo; comprobarlo de nuevo si cambia la autenticación en 07.
- Mantener rutas, atributos españoles y estructura JSON salvo cambios explícitos del paso. Documentar el impacto de cierres de seguridad; compatibilidad no significa conservar vulnerabilidades.
- Separar seeders idempotentes de configuración, factories de tests y datos sintéticos de demo. No ejecutar los actuales seeders que vacían tablas.
- Las pruebas y versiones de la auditoría son antecedentes, no garantías vigentes. La bitácora guarda la evidencia histórica y las verificaciones nuevas.
- Quedan por decidir cuando corresponda: distribución real de permisos por rol, acceso inicial autorizado, registro público o restringido, consumidores y autenticación, reglas sobre registros, precisión de pesos, visibilidad de imágenes y topología de despliegue. Registrar las respuestas una sola vez.

## 01. Establecer la base de referencia

**Foco:** manifiestos, rutas, `phpunit.xml`, tests, Git y configuración de CI. No cambiar lógica de aplicación.

**Aceptación:** base actual reproducible, contratos relevantes y entorno de pruebas descritos en la bitácora; fallos previos diferenciados de cambios nuevos. No registrar secretos ni volcados de datos.

```text
Lee AGENTS.md, refactor-progress.md y el paso 01 de refactor-plan.md. Ejecuta únicamente ese paso con su protocolo.
Comprueba rama, cambios locales, versiones instaladas mediante Sail y aislamiento de la base de pruebas. Revisa rutas efectivas y documenta en la bitácora los contratos que deben preservarse y los riesgos pendientes ya conocidos, contrastándolos con el código actual.
Si el árbol está limpio, crea una rama local de refactor con nombre descriptivo si hace falta; no alteres cambios ajenos ni crees commits. Ejecuta la suite, composer validate --strict y composer audit --locked mediante Sail; registra resultados reales, entorno y referencia Git. Un fallo de entorno no autoriza sustituir Sail por PHP del host.
No corrijas todavía lógica, dependencias ni despliegue. Actualiza refactor-progress.md con la base, limitaciones y entrega del paso 02; no marques la auditoría histórica como una nueva ejecución.
```

## 02. Conservar la semántica de errores HTTP

**Foco:** `bootstrap/app.php` y pruebas de excepciones HTTP/API.

**Aceptación:** errores esperados conservan código y formato; 403 no se convierte en 500; roles/permisos inexistentes no responden 429; errores internos no exponen detalles fuera de debug; throttling conserva cabeceras pertinentes.

```text
Lee AGENTS.md, refactor-progress.md y el paso 02 de refactor-plan.md. Verifica 01 y ejecuta únicamente 02.
Revisa los renderizadores de excepciones y corrige su clasificación sin cambiar innecesariamente el contrato JSON. Conserva 401, 403, 404, 405, 422 y 429; corrige la discrepancia entre cuerpo y estado en errores de roles/permisos. Evita que el renderizador general convierta errores HTTP esperados en 500 y conserva cabeceras necesarias.
Añade o ajusta pruebas de comportamiento de los errores afectados, incluido un rechazo real de autorización. Comprueba también que un fallo interno no revele información con debug desactivado. Sigue las skills y verificaciones aplicables.
Actualiza refactor-progress.md con cambios, comandos, resultados y pendientes; termina con la entrega del paso 03.
```

## 03. Crear permisos y roles estables

**Foco:** modelos y configuración de Spatie, factories, seeders y matriz de rutas.

**Aceptación:** catálogo determinista con guard correcto; permisos para todas las acciones protegidas; ejecución idempotente probada sin truncar datos; asignación administrativa inicial preparada sin elegir identidades arbitrarias. Si faltan decisiones de roles, registrar el punto pendiente y no inventar una matriz final.

```text
Lee AGENTS.md, refactor-progress.md y el paso 03 de refactor-plan.md. Verifica 02 y ejecuta únicamente 03.
Inventaría recursos y acciones para definir nombres estables de permisos, incluidos eliminados, restauraciones, estadísticas, mapa y auditoría. Registra la matriz exacta en refactor-progress.md: será la referencia de 04 y 05. Usa guard api mientras sea el efectivo.
Crea un seeder idempotente separado de los seeders destructivos de demo, mediante el generador Artisan correspondiente. Conserva permisos, roles y asignaciones existentes; no ejecutes configuración sobre datos reales. Prueba repetibilidad y preservación con una base aislada.
Prepara el mecanismo de asignación inicial a un administrador explícitamente designado. Consulta las decisiones sobre roles o identidad solo si son imprescindibles; no concedas permisos a todos ni a la primera cuenta. Registra cualquier decisión todavía pendiente.
Verifica, actualiza la bitácora y entrega el contexto del paso 04.
```

## 04. Cerrar la escalada de privilegios

**Foco:** registro, administración de usuarios/roles, Form Requests y `UserObserver`.

**Aceptación:** registro público sin privilegios controlables; administración de asignaciones autorizada y validada; ausencia de campos conserva asignaciones y arrays vacíos solo las revocan cuando está autorizado; persistencia y sincronización atómicas.

```text
Lee AGENTS.md, refactor-progress.md y el paso 04 de refactor-plan.md. Verifica 03 y usa su catálogo de permisos. Ejecuta únicamente 04.
Separa registro público de creación administrativa; elimina de UserObserver la lectura de roles y permisos desde request(). Protege la gestión de privilegios con autorización explícita y valida sus elementos, guard y estado según la decisión registrada. No introduzcas una asignación administrativa por defecto en registro.
Extrae la operación de persistir y sincronizar asignaciones cuando mejore su atomicidad y claridad. Usa transacciones y conserva roles/permisos si no se enviaron. Aplica la misma disciplina a la sincronización de permisos de roles. Evita acoplar la operación a HTTP para permitir uso desde otros contextos.
Prueba intento de escalada en registro, usuario ordinario intentando administrar, asignaciones inválidas sin cambios parciales y actualización sin pérdida de privilegios. No amplíes el cambio al resto del CRUD todavía.
Verifica, actualiza la bitácora y entrega el paso 05.
```

## 05. Proteger todos los endpoints

**Foco:** `routes/api.php`, middleware de controladores y tests de sus endpoints.

**Aceptación:** inventario ruta–acción–permiso completo y contrastado con rutas efectivas; 401/403/éxito cubiertos en las fronteras afectadas; métodos adicionales protegidos; no se usa un bypass de administrador para ocultar falta de permisos.

```text
Lee AGENTS.md, refactor-progress.md y el paso 05 de refactor-plan.md. Verifica 04 y los permisos y preparación administrativa de 03. Ejecuta únicamente 05.
Mantén auth en las rutas protegidas y aplica can con los nombres exactos del catálogo mediante el patrón HasMiddleware ya usado. Protege CRUD, indexDeleted, restore, indexByConfiscation, graphIndex, mapa y auditoría. Conserva los middleware de transformación. Trata login, registro, me, logout y refresh según sus necesidades reales, sin exigir un permiso administrativo para usar una sesión propia.
Actualiza las pruebas afectadas con permisos explícitos y verifica usuarios invitados, autenticados sin permiso y autorizados. Corrige el significado de helpers de autenticación sin asignar privilegios universales a toda la suite. Comprueba las rutas efectivas mediante Sail y ejecuta la suite completa por el alcance transversal.
Actualiza el inventario en la bitácora con evidencia de cobertura; entrega el paso 06. No asignes privilegios a cuentas reales ni despliegues automáticamente.
```

## 06. Añadir autorización contextual cuando corresponda

**Foco:** reglas de negocio confirmadas, policies y puntos que las invocan.

**Aceptación:** cada regla confirmada tiene comprobación efectiva y pruebas; no se inventan propiedad, estados o multitenencia. Si no hay reglas aplicables, cerrar como revisión completada y registrar policies nuevas como no aplicables por ahora.

```text
Lee AGENTS.md, refactor-progress.md y el paso 06 de refactor-plan.md. Verifica 05 y ejecuta únicamente 06.
Identifica reglas sobre registros que estén demostradas por el dominio o decisiones previas. Si se necesita una decisión del usuario, formula una pregunta concreta y continúa el trabajo independiente; no inventes campos, estados o restricciones.
Implementa policies solo para reglas confirmadas, consultando los permisos de Spatie cuando corresponda. Usa capacidades distintas de los nombres de permisos y asegúrate de invocar la policy desde el endpoint; instalar una policy no la ejecuta por sí solo. Conserva la protección general apropiada sin duplicaciones innecesarias.
Prueba la matriz contextual en la policy y su aplicación real desde HTTP. Si los permisos generales cubren todas las reglas conocidas, documenta esa conclusión verificable y completa la revisión sin añadir clases vacías.
Verifica y registra resultado, decisiones y entrega del paso 07.
```

## 07. Completar autenticación y rate limiting

**Foco:** autenticación, cookies, CSRF, CORS, guards, limitadores y tests.

**Aceptación:** estrategia compatible con consumidores conocidos; protección de solicitudes con cookies definida; expiración/renovación/revocación verificadas; límites segmentados; consecuencias de cualquier cambio de guard o cliente registradas.

```text
Lee AGENTS.md, refactor-progress.md y el paso 07 de refactor-plan.md. Verifica 06 y ejecuta únicamente 07.
Comprueba consumidores, dominios y contrato de sesión antes de elegir entre mantener JWT o aprovechar Sanctum ya instalado. Usa decisiones registradas; si faltan datos imprescindibles para una migración, solicítalos y evita sustituir la autenticación por suposición. Mientras tanto puedes corregir el limitador compartido y otros defectos independientes.
Implementa el flujo decidido: cookies y TTL coherentes, CSRF para autenticación por cookies, CORS por entorno y comportamiento explícito de login, logout y refresh. Si cambia el guard, actualiza coherentemente Spatie y las pruebas sin perder acceso legítimo.
Separa límites de login, registro, consultas y estadísticas mediante claves apropiadas. Prueba aislamiento entre clientes y autenticación entre peticiones independientes, expiración auténtica con tiempo controlado y revocación.
Verifica y registra compatibilidad, cambios necesarios del cliente y entrega del paso 08.
```

## 08. Reforzar validación e integridad

**Foco:** Form Requests, casts, esquema e invariantes del dominio.

**Aceptación:** entradas inválidas reciben errores controlados y no persisten; fecha, coordenadas, cantidades, listas y límites alineados con esquema y negocio; cualquier cambio de precisión tiene una estrategia de conservación de datos.

```text
Lee AGENTS.md, refactor-progress.md y el paso 08 de refactor-plan.md. Verifica 07 y ejecuta únicamente 08.
Inspecciona el esquema con Boost y las reglas existentes. Refuerza fecha, textos, coordenadas, cantidades, peso, listas y rangos temporales. Decide con evidencia o consulta las unidades, precisión y política de asociaciones soft-deleted; no impongas restricciones arbitrarias.
Normaliza listas antes de validarlas, valida sus elementos y procesa únicamente datos autorizados y validados. Usa casts y restricciones de base de datos cuando correspondan. Crea migraciones nuevas; no reescribas migraciones desplegadas ni conviertas columnas con pérdida de datos sin una estrategia revisable.
Añade regresiones para límites y fallos relevantes, incluidas asociaciones inválidas. Conserva el contrato de errores salvo correcciones acordadas.
Verifica y registra invariantes, decisiones de datos y entrega del paso 09.
```

## 09. Gestionar archivos con recuperación y autorización

**Foco:** observers de archivos, servicio de almacenamiento, acceso a fotos y persistencia.

**Aceptación:** fotos restringidas requieren autorización; actualización fallida conserva la anterior; archivos nuevos fallidos se limpian; datos previos tienen transición explícita; comportamiento de soft delete/restauración definido.

```text
Lee AGENTS.md, refactor-progress.md y el paso 09 de refactor-plan.md. Verifica 08 y ejecuta únicamente 09.
Desacopla uploads de request() en observers y crea una operación explícita de almacenamiento y sustitución. Confirma qué imágenes son privadas: los logos de catálogo y las fotos de evidencias pueden necesitar políticas distintas.
Para imágenes restringidas prepara disco privado y descarga autorizada, considerando los permisos de 03–06. Planifica la transición de archivos y URLs existentes sin mover o borrar datos reales. Conserva el archivo anterior hasta confirmar persistencia; compensa uploads nuevos si falla la operación. Una transacción SQL no revierte el filesystem.
Define eliminación y restauración sin borrar evidencias implícitamente. Usa Storage::fake y fallos controlados para verificar persistencia, compensación y acceso autorizado.
Verifica y registra cambios de contrato, transición pendiente y entrega del paso 10.
```

## 10. Separar normalización y representación HTTP

**Foco:** `TransformInput`, requests, resources, modelos y `ApiResponser`.

**Aceptación:** entrada y errores se normalizan de forma explícita; Resources representan salida; contratos relevantes preservados; reducción de acoplamiento comprobada en consumidores reales del código.

```text
Lee AGENTS.md, refactor-progress.md y el paso 10 de refactor-plan.md. Verifica 09 y ejecuta únicamente 10.
Traslada la normalización de entrada al límite de Form Requests y usa atributos/traducciones para errores. Define el tratamiento de claves desconocidas y evita colisiones silenciosas entre nombres internos y externos. Conserva el contrato español y el flujo de archivos ya corregido.
Centra Resources en representación y selecciona los recursos desde la capa HTTP para reducir la dependencia de modelos respecto a $resource. Sustituye el middleware y helpers afectados de forma gradual, comprobando referencias antes de retirarlos. No rehagas estadísticas ni paginación en este paso.
Ajusta pruebas de contratos de entrada, salida y errores, incluidos archivos y campos de privilegios. Mantén las garantías de autorización incorporadas.
Verifica y registra abstracciones retiradas, compatibilidad y entrega del paso 11.
```

## 11. Llevar paginación y filtros a SQL

**Foco:** listados, relaciones, Resources, ordenamiento e índices justificados.

**Aceptación:** paginación acotada en SQL, relaciones necesarias cargadas, orden estable, filtros y ordenamiento con lista permitida; medidas reproducibles antes/después y contratos de páginas vacías y metadatos conservados o adaptados explícitamente.

```text
Lee AGENTS.md, refactor-progress.md y el paso 11 de refactor-plan.md. Verifica 10 y ejecuta únicamente 11.
Sustituye all()/get() seguidos de filtros y paginación en memoria por builders con filtros, orden estable y paginación en SQL. Carga anticipadamente relaciones utilizadas por Resources. Mantén parámetros públicos, límites y estructura de respuesta compatibles; valida nombres dinámicos de filtros y ordenación.
Mide consultas y volumen procesado con datos sintéticos aislados y registra tamaño, método y resultados. Examina esquema y planes antes de añadir índices mediante migraciones nuevas; no atribuyas mejoras a una caché todavía defectuosa.
Prueba filtros, páginas vacías, límites, relaciones y estabilidad del orden. Verifica especialmente helpers compartidos y autorización de listados; no escribas benchmarks permanentes innecesarios.
Verifica y entrega la evidencia y el contexto del paso 12 en la bitácora.
```

## 12. Corregir estadísticas y mapa

**Foco:** consultas agregadas, períodos, vistas SQL, endpoints de gráficas y mapa.

**Aceptación:** períodos admitidos tienen semántica y totales comprobados; filtros no omiten validación; pruebas HTTP cubren resultados conocidos; SQL específico se verifica en MySQL aislado; soporte de motores descrito fielmente.

```text
Lee AGENTS.md, refactor-progress.md y el paso 12 de refactor-plan.md. Verifica 11 y ejecuta únicamente 12.
Extrae consultas complejas a clases enfocadas cuando aporte claridad. Define y aplica semántica de day, month, quarter, semester, year y total; confirma cualquier ambigüedad del contrato. Normaliza filtros y comprueba rangos temporales, soft deletes y agrupaciones por identidad para evitar mezclar descripciones repetidas.
Mantén tipos y unidades de cantidades/pesos según las decisiones de 08. Prueba los endpoints completos con datos y totales conocidos, además de vistas. Conserva permisos de estadísticas y mapa.
Ejecuta los casos que dependan de funciones SQL contra una base MySQL de pruebas aislada, sin cambiar la base real ni ejecutar seeders destructivos. No declares esa compatibilidad verificada usando solo SQLite ni prometas PostgreSQL sin comprobarlo.
Verifica y registra evidencia, límites de motor y entrega del paso 13.
```

## 13. Implementar caché útil y coherente

**Foco:** operaciones costosas medidas, TTL, claves e invalidación.

**Aceptación:** trabajo costoso omitido en hits; TTL positivo probado; cambios relevantes invalidan; claves respetan contexto de autorización; no se cachean errores o respuestas entre ámbitos incompatibles.

```text
Lee AGENTS.md, refactor-progress.md y el paso 13 de refactor-plan.md. Verifica 12 y ejecuta únicamente 13.
Usa las medidas de 11–12 para decidir qué merece caché. Corrige el TTL fraccionario y mueve el trabajo costoso dentro del callback. Si una operación no obtiene beneficio, retira su caché innecesaria con justificación.
Diseña claves estables con filtros y contexto de acceso necesario. Define invalidación por creación, actualización, eliminación, restauración y cambios de permisos cuando afecten resultados. Evita invalidar indiscriminadamente toda la caché o mezclar respuestas restringidas.
Sustituye expectativas que perpetúen el defecto por pruebas de comportamiento con un store de pruebas real y tiempo controlado: reutilización, expiración, invalidación y separación de contextos. Conserva cobertura útil y explica las expectativas modificadas.
Verifica y registra decisiones, evidencia y entrega del paso 14.
```

## 14. Retirar código sobrante y cerrar responsabilidades

**Foco:** referencias, imports, collections, helpers, observers y código ya sustituido.

**Aceptación:** retiradas justificadas por referencias y comportamiento; sin abstracciones vacías; auditoría funciona también sin request o usuario autenticado donde corresponda; suite completa pasa.

```text
Lee AGENTS.md, refactor-progress.md y el paso 14 de refactor-plan.md. Verifica 13 y ejecuta únicamente 14.
Comprueba referencias, autoload y uso dinámico antes de retirar collections sin personalización, helper eventName duplicado, middleware y requests sin uso, métodos vacíos, imports y depuración comentada. Elimina solo código demostrado innecesario después de los pasos previos.
Revisa las responsabilidades que queden en ApiResponser y observers. Mantén auditoría independiente de un request y con un actor de sistema definido para procesos sin sesión. No introduzcas repositorios genéricos o capas sin una necesidad concreta.
Identifica dependencias posiblemente sobrantes según la autenticación elegida, pero solicita la autorización exigida por el proyecto antes de modificarlas. No borres pruebas útiles para ajustar el resultado.
Ejecuta verificaciones y suite completa. Actualiza la bitácora con retiradas, evidencia y entrega del paso 15.
```

## 15. Actualizar CI y preparar despliegue recuperable

**Foco:** `.github/workflows/deploy.yml`, configuración operativa y persistencia.

**Aceptación:** CI usa PHP compatible y checks reales; preparación local de releases, persistencia, migraciones, salud y rollback revisable; pruebas MySQL integradas cuando correspondan. Estado de ejecución remota separado de la preparación local.

```text
Lee AGENTS.md, refactor-progress.md y el paso 15 de refactor-plan.md. Verifica 14 y cualquier trabajo 15a ya registrado. Ejecuta únicamente 15.
Actualiza CI para PHP compatible con manifiesto, lock y entorno elegido; ejecuta pruebas, formato y auditoría. Incluye los casos MySQL de 12 con una base aislada. No uses Sail sin preparar Docker y dependencias ni copies comandos locales a CI sin comprobar su viabilidad. Evalúa análisis estático sin instalar paquetes no autorizados.
Separa checks de despliegue y prepara releases, almacenamiento persistente, migraciones, comprobación de salud y rollback. Revisa empaquetado, manejo de secretos y exposición de servicios de desarrollo. No asumas montajes ni topología: pide únicamente datos imprescindibles y nunca credenciales en la bitácora.
Prepara y verifica todo lo posible localmente. No hagas push, ejecutes workflows remotos ni despliegues sin autorización explícita. Registra por separado qué está preparado y qué falta validar en infraestructura; no afirmes un rollback probado si solo está descrito.
Actualiza la bitácora y entrega el paso 16.
```

## 16. Preparar documentación y demostración de portafolio

**Foco:** README, documentación API existente, datos sintéticos y presentación.

**Aceptación:** instrucciones verificables, versiones y motores fieles, decisiones técnicas y resultados medidos; demo preparada sin datos reales ni credenciales comprometidas; publicación diferenciada del trabajo local.

```text
Lee AGENTS.md, refactor-progress.md y el paso 16 de refactor-plan.md. Verifica 15 y distingue sus comprobaciones locales de las remotas. Ejecuta únicamente 16.
Actualiza README y documentación API existente con instalación Sail reproducible, autenticación elegida, permisos, motores soportados, contratos relevantes y pruebas reales. Usa medidas registradas, sin inventar cobertura, métricas, badges verdes ni garantías de producción.
Prepara datos sintéticos aislados y un recorrido de demostración de permisos, decomisos, mapa y auditoría, con una explicación breve de decisiones y mejoras. Si se desea una funcionalidad nueva como aprobación de decomisos, trátala como alcance separado: no inventes sus reglas para completar documentación.
Contrasta instrucciones y ejemplos con el código; protege la demo frente a cambios abusivos. No publiques sitios, infraestructura, credenciales ni datos sin autorización explícita. Si faltan recursos externos, deja el material local revisable y registra el pendiente.
Actualiza refactor-progress.md con resultados, riesgos restantes y un cierre del refactor que distinga implementación verificada de despliegue o publicación pendientes.
```

## Referencia del método de continuidad

La separación entre instrucciones estables, plan y evidencia de ejecución adapta la guía oficial [Iterating Development Workflows with Codex](https://developers.openai.com/cookbook/examples/codex/iterating-development-workflows-with-codex). Aquí se conservan los nombres solicitados y se utiliza un único archivo de progreso para evitar dispersar el contexto.
