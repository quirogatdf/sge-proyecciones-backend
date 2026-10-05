# Búsqueda avanzada en el listado de proyecciones

## Intent

Permitir buscar en el listado de proyecciones eligiendo **un campo concreto** (de la plaza o del instrumento del año) más un texto, cerrando la brecha de los 10 campos migrados en el "Camino 1" que hoy no son buscables. El registro de campos será la **única fuente de verdad en el backend** y alimentará tanto los WHERE como un endpoint de descubrimiento.

Tag de requisitos para la fase de specs: **`BUSQ`** → `REQ-BUSQ-{N}` / `SCENARIO-N`.

## Problem Statement

1. **Brecha de cobertura.** `ProyeccionController::listado()` (417 líneas) busca con un OR hardcodeado sobre 7 columnas `ILIKE` + 3 `whereHas`. De los campos migrados a `proyeccion_instrumentos`, **10 no son buscables**: `resolucion_ministerial_ext`, `disposicion_sgnij`, `rect_disposoco_sgnij`, `resolucion_ministerial_rect1`, `resolucion_ministerial_rect2`, `resolucion_previa_continuidad`, `destino_anterior`, `observaciones`, `fecha_desde`, `fecha_hasta`.
2. **Búsqueda numérica rota.** En `ProyeccionController.php:102-104`, el `if (is_numeric($search)) { $q->where('proyecciones.id', ...) }` convive con el `orWhere` chain. Buscar `123` devuelve la proyección con ese id **más** todo lo que contenga `123`.
3. **Whitelists desincronizadas.** `COLUMNAS_INSTRUMENTO` (13), `COLUMNAS_PLAZA` (2) y `$allowedSorts` (26, `aplicarOrden()` línea 361) se mantienen a mano y ya no coinciden entre sí.
4. **Sin debounce.** `CrudTableComponent.onSearch()` (línea 646) dispara un GET por tecla.
5. **`reloadData()` no resetea `currentPage`** (línea 642): cambiar año/nivel en la página 5 devuelve la página 5 del set nuevo → tabla vacía. Esto afecta los 6 call sites de `reloadData()` en `proyecciones-list.component.ts`.
6. **Endpoint de descubrimiento inexistente** y lista de campos potencialmente duplicada en el frontend.

## Scope

### In Scope

- **Backend**: registro declarativo de campos buscables (value object + enums), que derive los WHERE y un endpoint de descubrimiento.
- **Backend**: `search_field` opcional en el listado; `__all__` conserva el contrato actual de `search`.
- **Backend**: migración del `ILIKE` hardcodeado a `whereLike` portable + cobertura de tests (hoy `search` tiene **cero** tests).
- **Frontend**: dropdown "Buscar en ▾" en el `CrudTable` genérico, alimentado por el endpoint (sin lista hardcodeada).
- **Frontend**: debounce ~350 ms, reset de `currentPage` en `reloadData()`, `search_field` explícito en `ProyeccionesService.getAll()`.
- **Frontend**: mover `SearchableSelectComponent` de `features/shared/` a `shared/` (5 importadores) para no invertir la dirección de dependencias.

### Out of Scope

- Búsqueda multi-campo con AND/OR (decisión: **un campo por vez**).
- Reemplazar la búsqueda **client-side** de los 6 CRUDs que usan `searchFields` (`niveles`, `turnos`, `instituciones`, `funciones`, `cargos`, `resoluciones`): la clave nueva es aditiva y no los toca.
- `pg_trgm` o índices nuevos (ver Riesgos).
- Unificar `COLUMNAS_INSTRUMENTO`/`COLUMNAS_PLAZA`/`$allowedSorts` en el registro (deuda técnica adyacente; se documenta, no se migra).
- Migrar las 3 modales duplicadas ni adoptar una librería de UI.

## Capabilities

### New Capabilities

- `busqueda-campos`: registro declarativo de campos buscables, con origen (`instrumento|plaza|relacion`), columna SQL, tipo (`texto|numero|fecha|enum`) y el sentinel `__all__`.
- `busqueda-por-campo`: filtro `search_field` en el listado de proyecciones.
- `busqueda-descubrimiento`: `GET /api/proyecciones/campos-buscables` que expone el registro como opciones `{key, label, origen, tipo}`.

### Modified Capabilities

None. `backend/openspec/specs/` está vacío (0 archivos versionados): no hay specs previos que modificar.

## Approach

1. **Value object + enums** (estilo `app/Enums/MotivoProyeccion.php`): `CampoBuscable` (readonly: `key`, `label`, `origen`, `columna`, `tipo`) + `OrigenCampoBuscable` + `TipoCampoBuscable`.
2. **Registro único** `RegistroCamposBuscables`: la lista de 23 campos + el sentinel `__all__` (24 entradas). De ahí se derivan (a) las cláusulas WHERE y (b) el payload del endpoint. El frontend **nunca** envía un nombre de columna SQL, solo el `key` público.
3. **Aplicación por `tipo`**: `texto|enum` → `whereLike($col, "%$t%", caseSensitive: false)`; `numero` → `where($col, (int) $t)` si el término es numérico; `fecha` → `CAST(col AS TEXT)` + `LIKE` insensible (porque `whereLike` no castea). Los `origen=relacion` siguen el camino `whereHas` ya probado.
4. **Endpoint** `GET /api/proyecciones/campos-buscables` en un `ProyeccionBusquedaController` nuevo (SRP: `ProyeccionController` ya tiene 417 líneas), declarado en `routes/api.php` **antes** de `Route::apiResource('proyecciones', ...)` (línea 40), igual que `opciones-filtro` (línea 32) — si no, `apiResource` captura `campos-buscables` como `{proyeccion}`.
5. **`__all__`**: mueve el bloque `search` actual (líneas 99-126) al registro sin cambiar su semántica OR, incluida la rama numérica. El match numérico **exacto** queda expuesto vía `search_field=id`. `search_field` ausente o `__all__` ⇒ comportamiento idéntico al actual.
6. **Frontend**: `CrudTableConfig` gana `searchFieldOptions` + `searchFieldParamName` + `searchDebounceMs` (nombres nuevos, sin colisionar con `searchFields`); `CrudTableComponent` suma el signal `searchField`, renderiza `app-searchable-select`, inyecta `search_field` en `loadServerSide()` y debouncea. `proyecciones-list.component.ts` pide el endpoint y pasa las opciones.

### Registro de campos (23 + `__all__`)

| `key` | `label` | `origen` | `columna` | `tipo` |
|---|---|---|---|---|
| `__all__` | Todos los campos | — | — | — |
| `id` | ID de proyección | plaza | `proyecciones.id` | numero |
| `id_puesto` | ID de puesto | plaza | `proyecciones.id_puesto` | texto |
| `estado` / `motivo` | Estado / Motivo | instrumento | `pi.estado` / `pi.motivo` | enum |
| `anio` | Año | instrumento | `pi.anio` | texto |
| `n_expediente` | N° de expediente | instrumento | `pi.n_expediente` | texto |
| `resolucion_ministerial` | Resolución ministerial | instrumento | `pi.resolucion_ministerial` | texto |
| **`resolucion_ministerial_ext`** | Resolución ministerial (ext.) | instrumento | `pi.resolucion_ministerial_ext` | texto |
| **`resolucion_ministerial_rect1`** | Resolución ministerial (rect. 1) | instrumento | `pi.resolucion_ministerial_rect1` | texto |
| **`resolucion_ministerial_rect2`** | Resolución ministerial (rect. 2) | instrumento | `pi.resolucion_ministerial_rect2` | texto |
| **`resolucion_previa_continuidad`** | Resolución previa de continuidad | instrumento | `pi.resolucion_previa_continuidad` | texto |
| **`disposicion_sgnij`** | Disposición SGNIJ | instrumento | `pi.disposicion_sgnij` | texto |
| **`rect_disposoco_sgnij`** | Rectificación disposición OCO SGNIJ | instrumento | `pi.rect_disposoco_sgnij` | texto |
| **`destino_anterior`** | Destino anterior | instrumento | `pi.destino_anterior` | texto |
| `destino_nuevo` | Destino nuevo | instrumento | `pi.destino_nuevo` | texto |
| **`observaciones`** | Observaciones | instrumento | `pi.observaciones` | texto |
| **`fecha_desde`** | Fecha desde | instrumento | `pi.fecha_desde` | fecha |
| **`fecha_hasta`** | Fecha hasta | instrumento | `pi.fecha_hasta` | fecha |
| `institucion_nombre` | Institución | relacion | `instituciones.nombre` | texto |
| `institucion_localidad` | Localidad | relacion | `instituciones.localidad` | texto |
| `cargo_nombre` | Cargo | relacion | `cargos.nombre` | texto |
| `cargo_codigo` | Código de cargo | relacion | `cargos.codigo` | texto |
| `resolucion_nombre` | Resolución (nombre) | relacion | `resoluciones.nombre` | texto |

**En negrita** los 10 campos que hoy no son buscables.

## Restricciones duras

1. **Portable por driver — `phpunit.xml` corre SQLite `:memory:`, producción es PostgreSQL.** El `ILIKE` de las líneas 106-122 es Postgres-only. Todo el código nuevo **DEBE** usar el builder `whereLike($column, $value, caseSensitive: false)` de Laravel 11+ (verificado en 13.6.0), que compila `ILIKE` en Postgres (`PostgresGrammar::whereLike`) y `LIKE` en SQLite (`SQLiteGrammar::whereLike`). **DEBE** haber un test que pruebe la búsqueda en verde sobre SQLite. El bloque `ILIKE` existente **DEBE** migrarse a `whereLike` en el mismo cambio: si no, el camino `__all__` queda intestable. Ojo: `toSql()` **no** valida esto (compila `ILIKE` sin quejarse); el fallo solo aparece al **ejecutar** contra SQLite — el test tiene que ejecutar la query, no solo inspeccionar el SQL. Además `whereLike` **no** escapa `%` ni `_` del valor ⇒ el término **DEBE** escaparse antes de enveloparlo con `%…%`.
2. **`strict_tdd: true`** en ambos `openspec/config.yaml`. Tests **DEBEN** cubrir el comportamiento nuevo. Hoy `search` tiene cobertura cero. Baselines verificados al 2026-10-05:
   - Backend: **GREEN 149/149, 476 assertions** (`php artisan test`). Cualquier falla posterior es regresión de este cambio.
   - Frontend: **RED 29 passed / 10 failed** (`bun run test`). Las 10 fallas son **preexistentes** y NO regresiones. Desglose exacto (el "piso" que verify no debe blamear a este cambio): **5** `should delete {institucion,cargo,nivel,funcion,turno}` → `AssertionError: expected null to be undefined` (la spec asume `undefined`, HttpClient emite `null` en 204) y **5** `should handle HTTP error gracefully` → `expectAsync()` existe solo en Jasmine, no en Vitest 4.1.5 (`tsconfig.integration.json:6` sigue con `types: ["jasmine"]`). Ninguna toca búsqueda. Cualquier test nuevo **DEBE** pasar en verde y el conteo de fallas **DEBE** quedar en ≤ 10.

## Affected Modules

### Backend (`backend/`)

| Archivo | Acción | Descripción |
|---|---|---|
| `app/Enums/OrigenCampoBuscable.php` | Nuevo | Enum `instrumento\|plaza\|relacion` |
| `app/Enums/TipoCampoBuscable.php` | Nuevo | Enum `texto\|numero\|fecha\|enum` |
| `app/Services/CampoBuscable.php` | Nuevo | Value object readonly (`key`, `label`, `origen`, `columna`, `tipo`) |
| `app/Services/RegistroCamposBuscables.php` | Nuevo | Fuente de verdad: lista + derivación de WHERE + payload del endpoint |
| `app/Http/Controllers/Api/ProyeccionBusquedaController.php` | Nuevo | `GET /api/proyecciones/campos-buscables` |
| `app/Http/Controllers/Api/ProyeccionController.php` | Modificado | Líneas 99-126 → delegación al registro; `ILIKE` → `whereLike`; `whereHas` de relaciones vía `origen` |
| `routes/api.php` | Modificado | Nueva ruta **antes** de `apiResource('proyecciones')` (línea 40) |
| `tests/Unit/Services/RegistroCamposBuscablesTest.php` | Nuevo | Integridad del registro: `key` únicos, `columna` existe en el esquema, los 10 nuevos presentes, `__all__` |
| `tests/Feature/Api/ProyeccionBusquedaTest.php` | Nuevo | `search_field` por campo; `__all__` equivalente al actual; `id` numérico exacto; `key` desconocido; **prueba de portabilidad en SQLite** |

### Frontend (`frontend/`)

| Archivo | Acción | Descripción |
|---|---|---|
| `src/app/features/shared/components/searchable-select/searchable-select.ts` | **Movido** | → `src/app/shared/components/searchable-select/searchable-select.ts` (`git mv`) |
| 5 importadores del select | Modificado | `proyecciones-list`, `export-dialog`, `agregar-instrumento-dialog`, `instituciones.page`, `dashboard.page` |
| `src/app/shared/interfaces/crud-config.interface.ts` | Modificado | `SearchFieldOption` + `searchFieldOptions?`, `searchFieldParamName?`, `searchDebounceMs?` (aditivo; **no** toca `searchFields`) |
| `src/app/shared/components/crud-table/crud-table.component.ts` | Modificado | Signal `searchField`; `app-searchable-select` junto al input (línea 57-65); `search_field` en `loadServerSide()`; debounce ~350 ms; `reloadData()` resetea `currentPage` a 1 |
| `src/app/core/services/proyecciones.service.ts` | Modificado | `getCamposBuscables()`; `search_field` explícito en la lista de claves conocidas (deja de depender del catch-all de líneas 68-73) |
| `src/app/features/proyecciones/proyecciones-list.component.ts` | Modificado | Fetch del endpoint + opciones al `CrudTable`; tipar el `@ViewChild('crudTable') crudTable?: any` (línea 1025) |
| `src/app/shared/components/crud-table/crud-table.integration.spec.ts` | Nuevo | Debounce, `search_field` en los params, `reloadData()` resetea página |

> Los tests frontend **DEBEN** llamarse `*.integration.spec.ts`: `tsconfig.integration.json` solo incluye `src/**/*.integration.spec.ts`, así que cualquier otro nombre no se ejecuta.

## Risks

| Riesgo | Prob. | Mitigación |
|---|---|---|
| `ILIKE` nuevo hardcodeado pasa en Postgres y falla en CI (SQLite) | Alta | Restricción dura 1: `whereLike` + test que **ejecuta** la búsqueda sobre SQLite |
| El volumen real en Railway es **desconocido** (local: 6 proyecciones / 9 instrumentos, años 2024-2027) | Media | No bloquea. `LIKE '%…%'` con wildcard inicial no usa índice btree de todas formas. **Revisar con `pg_trgm` + GIN cuando el snapshot del año en foco supere ~20.000 filas** o el p95 del listado pase 500 ms en los logs de Railway |
| Romper `export-dialog` u otros callers del contrato `search` | Baja | `__all__` conserva la semántica OR actual; `search_field` es opcional y se omite cuando vale `__all__`; test de equivalencia |
| El registro queda desincronizado de nuevo al agregar columnas | Media | Test de integridad que valida cada `columna` contra el esquema |
| Romper los 6 CRUDs client-side al tocar el `CrudTable` genérico | Media | Claves nuevas aditivas; los 7 consumidores heredan debounce y el fix de `currentPage`, que son correcciones; verificar el conteo de fallas |
| Mover `SearchableSelectComponent` rompe 5 importadores | Baja | `git mv` + actualizar los 5 imports en la misma fase |
| `getExtraParams()` duck-typed (no está en ninguna interfaz) | Baja | El `search_field` lo arma el `CrudTable`, no el wrapper; no se amplía la duck-typing |

## Rollback Plan

Cambio **aditivo**: clase de registro nueva, endpoint nuevo, query param opcional. **Sin migraciones, sin cambios de datos, sin feature flag.**

1. Backend: revertir `ProyeccionController.php`, `routes/api.php`; eliminar los 4 archivos nuevos (`CampoBuscable`, `RegistroCamposBuscables`, los 2 enums, `ProyeccionBusquedaController`) y los 2 tests nuevos.
2. Frontend: revertir el `CrudTableComponent`, `crud-config.interface.ts`, `proyecciones.service.ts`, `proyecciones-list.component.ts`; deshacer el `git mv` de `searchable-select` y restaurar los 5 imports.
3. **Contrato preservado**: no enviar `search_field` reproduce exactamente el comportamiento de hoy. Un cliente que nunca conozca el param sigue funcionando — el rollback es un revert de código, sin estado que restaurar.

## Dependencies

- Sin dependencias nuevas. No se agregan paquetes ni migraciones.
- `SearchableSelectComponent` (signal-based, `selector: 'app-searchable-select'`) se reutiliza tal cual.
- Autenticación Sanctum ya vigente en el grupo de `routes/api.php:20`.

## Success Criteria

- [ ] `GET /api/proyecciones/campos-buscables` devuelve las 24 entradas (`__all__` + 23) con `key`/`label`/`origen`/`tipo`.
- [ ] Los 10 campos hoy no buscables responden filas cuando se buscan por `search_field`.
- [ ] `search_field` ausente o `__all__` ⇒ resultados idénticos a la implementación actual.
- [ ] `search_field=id` + término numérico ⇒ match exacto, sin el ruido del OR actual.
- [ ] Un `search_field` desconocido se rechaza (422) y **nunca** se interpola como columna SQL.
- [ ] La suite backend corre **verde sobre SQLite** (sin `ILIKE`), y el total es ≥ 149 con los nuevos tests.
- [ ] El "Buscar en ▾" se puebla desde el endpoint; el frontend **no** hardcodea la lista de campos.
- [ ] El debounce evita un GET por tecla; `reloadData()` vuelve a la página 1.
- [ ] Los 5 importadores del select compilan tras el `git mv`.
- [ ] Frontend: tests nuevos en verde y **≤ 10 fallas** (el piso preexistente).
- [ ] `./vendor/bin/pint --test` limpio.
