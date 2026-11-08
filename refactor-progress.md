# Progreso del refactor

## Estado para retomar

- Última actualización: 2026-11-08, zona Europe/Madrid.
- Trabajo actual: preparación documental completada; implementación del refactor sin iniciar.
- Paso en curso: ninguno.
- Siguiente paso: 01, establecer la base de referencia.
- Próxima acción: abrir una conversación en este repositorio y ejecutar el prompt 01 de `refactor-plan.md`.
- Referencia de preparación: rama `upgrade/laravel-13`, HEAD `ed718be`. Es una referencia histórica; comprobar rama, HEAD y estado actuales al retomar.
- Alcance ejecutado en esta preparación: actualizar `AGENTS.md` y crear `refactor-plan.md` y este archivo. No se corrigió código de aplicación.
- Verificaciones de esta preparación: revisión de los tres documentos y correspondencia de los 16 prompts con el tablero; comprobaciones de whitespace sin incidencias. No se ejecutaron tests ni Pint porque solo se modificó documentación.

## Tablero

Estados: `pendiente`, `en_curso`, `bloqueado`, `completado`. `no_aplica` se usa para componentes condicionales con explicación. Un impedimento temporal de herramientas no permite declarar una verificación exitosa.

| Paso | Trabajo | Estado | Evidencia o pendiente principal |
| --- | --- | --- | --- |
| 01 | Base de referencia | pendiente | Revalidar entorno, contratos y suite |
| 02 | Errores HTTP | pendiente | Preservar 403 y corregir respuestas inconsistentes |
| 03 | Catálogo de permisos | pendiente | Matriz determinista y seeder idempotente |
| 04 | Asignación de privilegios | pendiente | Cerrar escalada y evitar pérdida de asignaciones |
| 05 | Protección de endpoints | pendiente | Inventario completo y casos 401/403/éxito |
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
| D01 | Aprovechar Spatie con `can` para permisos generales | Plan acordado; no crear gates duplicados |
| D02 | Policies solo para restricciones sobre registros confirmadas | No inventar propiedad, estados o multitenencia |
| D03 | Refactor incremental en 16 pasos, una conversación por paso | Detalle y prompts en `refactor-plan.md` |
| D04 | Separar instrucciones, plan y progreso | Solicitud explícita; documentación autorizada |
| D05 | Preservar contrato existente salvo correcciones expresas | Registrar impacto y adaptación del cliente |
| D06 | No usar seeders destructivos ni cuentas elegidas por suposición | Configuración nueva idempotente y pruebas aisladas |

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

Pendiente de construir en 03 y contrastar con las rutas efectivas en 05. Registrar recurso, acción, permiso exacto, guard y roles autorizados. Los nombres `drugs.view`, `drugs.create`, `drugs.update`, `drugs.delete`, `drugs.viewDeleted`, `drugs.restore` son una propuesta, no permisos creados ni asignados.

## Registro de sesiones y verificaciones

### Preparación documental — 2026-11-08

- Solicitud: disponer de prompts para cada paso y contexto persistente para conversaciones distintas.
- Cambios: extensión del `AGENTS.md` existente fuera del bloque Boost; plan con 16 prompts; tablero y bitácora iniciales.
- Decisiones: no ejecutar pasos de implementación durante esta preparación; no marcar pruebas históricas como actuales.
- Verificaciones de los documentos: `git diff --check` sin incidencias; `git diff --no-index --check /dev/null refactor-plan.md` y su equivalente para `refactor-progress.md` sin diagnósticos de whitespace (código 1 esperado por ser archivos nuevos distintos de `/dev/null`). Revisión de 16 secciones y prompts, 16 filas del tablero, referencias locales y conservación del bloque Boost de `AGENTS.md`.
- Pruebas de aplicación y Pint: no ejecutados en esta preparación, al ser cambios exclusivamente documentales. Los resultados de la auditoría permanecen identificados como históricos.
- Siguiente acción: ejecutar 01 en una nueva conversación.

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
