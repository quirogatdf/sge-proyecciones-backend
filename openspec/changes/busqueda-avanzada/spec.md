# Spec: Búsqueda avanzada en el listado de proyecciones

## Purpose

Cerrar la brecha de los 10 campos migrados a `proyeccion_instrumentos` que hoy **no son buscables**, y dar
al usuario la elección de **un campo concreto** más un texto. Un registro declarativo en el backend es la
**única fuente de verdad**: de él se derivan las cláusulas `WHERE` **y** el payload del endpoint de
descubrimiento que alimenta el picker "Buscar en ▾". El frontend manda un `key` público, nunca un nombre de
columna SQL.

Tag de requisitos: **`BUSQ`** → `REQ-BUSQ-{N}` / `ESCENARIO-{N}`.

## Capabilities

Las tres capacidades son **NUEVAS**. `backend/openspec/specs/` está vacío: no hay Delta, no hay
`ADDED`/`MODIFIED`/`REMOVED`. Este archivo es la spec completa.

| # | Capability | Objeto |
|---|------------|--------|
| 1 | `busqueda-campos` | Registro declarativo: 23 campos + sentinel `__all__`, semántica por `tipo`, escapado, integridad |
| 2 | `busqueda-por-campo` | Query param `search_field` en `GET /api/proyecciones` |
| 3 | `busqueda-descubrimiento` | `GET /api/proyecciones/campos-buscables` |

> **Alcance de este archivo**: cubre las 3 capacidades **backend** (`REQ-BUSQ-1` … `REQ-BUSQ-15`) y el
> contrato de verificación (`REQ-BUSQ-23` … `REQ-BUSQ-28`). Los requisitos de **frontend**
> (`REQ-BUSQ-16` … `REQ-BUSQ-22`) viven **sólo en Engram** (`sdd/busqueda-avanzada/spec`), porque
> `frontend/openspec/` está en `.gitignore`. La numeración es continua entre ambos artefactos.

## Convenciones

RFC 2119 en prosa española: **DEBE** = MUST · **NO DEBE** = MUST NOT · **DEBERÍA** = SHOULD ·
**NO DEBERÍA** = SHOULD NOT · **PUEDE** = MAY.

Cada ADR del design se codifica acá como requisito **verificable**, no como resumen. Índice:

| ADR | Requisito | ADR | Requisito |
|-----|-----------|-----|-----------|
| ADR-1 registro único | REQ-BUSQ-1, -6 | ADR-7 fallback `__all__` | REQ-BUSQ-10 |
| ADR-2 `__all__` congelado | REQ-BUSQ-7, -8 | ADR-8 post-merge | REQ-BUSQ-20 |
| ADR-3 `whereHas` | REQ-BUSQ-4 | ADR-9 debounce zoneless | REQ-BUSQ-18 |
| ADR-4 `CAST` fecha | REQ-BUSQ-3 | ADR-10 mover select | REQ-BUSQ-16 |
| ADR-5 escapado | REQ-BUSQ-5 | ADR-11 reset página | REQ-BUSQ-19 |
| ADR-6 `numero` exacto | REQ-BUSQ-3, -9 | ADR-12 sin caché | REQ-BUSQ-15, -17 |

---

# 1. `busqueda-campos`

## REQ-BUSQ-1: El registro es la única fuente de verdad

El backend DEBE exponer un registro con **exactamente 23** campos buscables, cada uno con `key` (público,
lo que viaja por HTTP), `label` (español), `origen` (`instrumento|plaza|relacion`), `columna` (calificada
tal como la necesita el punto de aplicación) y `tipo` (`texto|numero|fecha|enum`).

`__all__` **NO** es un campo: es una **constante aparte** (`SENTINEL_TODOS = '__all__'`). DEBE quedar
separada porque un campo siempre tiene `origen` y `tipo` no-nullables y un sentinel no. El registro DEBE
producir **24** opciones para el endpoint (23 + `__all__` sintético primero).

El registro DEBE servir para derivar (a) los `WHERE` y (b) el payload del endpoint. `search_field` DEBE
resolverse **siempre** contra ese array. **NO DEBE** existir ningún camino donde un string de la request
llegue a un nombre de columna.

Los 10 campos hoy no buscables que DEBEN estar presentes: `resolucion_ministerial_ext`,
`disposicion_sgnij`, `rect_disposoco_sgnij`, `resolucion_ministerial_rect1`,
`resolucion_ministerial_rect2`, `resolucion_previa_continuidad`, `destino_anterior`, `observaciones`,
`fecha_desde`, `fecha_hasta`.

`nivel.nombre` NO entra al registro: el nivel ya es un filtro dedicado (`?id_nivel=`) y sumarlo al picker
duplicaría UI.

#### ESCENARIO-1: El registro tiene 23 campos y el sentinel es una constante aparte

- **Given** el registro de campos buscables
- **When** se solicita la lista de campos
- **Then** DEBE devolver exactamente 23 entradas
- **And** `__all__` NO DEBE aparecer entre ellas
- **And** `SENTINEL_TODOS` DEBE valer `'__all__'`

#### ESCENARIO-2: El sentinel se antepone sintético y las 10 brechas están cargadas

- **Given** el registro de campos buscables
- **When** se generan las opciones para el endpoint
- **Then** DEBE devolver 24 entradas, con `{key:"__all__", label:"Todos los campos", origen:null, tipo:null}` en la posición 0
- **And** DEBE contener los 10 `key` listados en REQ-BUSQ-1

## REQ-BUSQ-2: La integridad del registro es verificable, no una promesa

`key` DEBE ser único. Cada `columna` DEBE existir en el esquema (verificada contra la tabla del prefijo
`pi.` / `proyecciones.`). El registro DEBE estar testeado contra el esquema, no solamente documentado —
un test DEBE usar `Schema::hasColumn` tras `RefreshDatabase`.

#### ESCENARIO-3: Test de integridad contra el esquema

- **Given** la BD de test migrada (SQLite `:memory:`)
- **When** se corre el test de integridad del registro
- **Then** todos los `key` DEBEN ser únicos
- **And** para cada campo, `Schema::hasColumn($tabla, $columna)` DEBE ser `true`
- **And** el conteo DEBE ser 23
- **And** los 10 `key` de REQ-BUSQ-1 DEBEN estar presentes

## REQ-BUSQ-3: Semántica de aplicación por `tipo`

El aplicador DEBE derivar el SQL del `tipo` del campo, según esta tabla **exacta**:

| `tipo` | SQL emitido | Efecto |
|--------|-------------|--------|
| `texto` | `whereLike($col, "%$patron%", caseSensitive: false)` | contención insensible |
| `enum` | **idéntico a `texto`** | contención insensible |
| `numero` + término numérico | `where($col, (int) $termino)` | **igualdad exacta** |
| `numero` + término no numérico | `whereRaw('0 = 1')` | conjunto vacío |
| `fecha` | `whereLike(DB::raw("CAST($col AS TEXT)"), "%$patron%", false)` | contención sobre el texto de la fecha |

`enum` y `texto` **NO** DEBEN tener diferencia de comportamiento: `enum` es documentación y futuro de UI
(un `datalist`), **NO** SQL. La distinción DEBE declararse explícitamente para que nadie la lea como
comportamiento.

`tipo=fecha` DEBE emitir el `CAST(... AS TEXT)` **explícito** y NO DEBE confiar en el `::text` implícito
de PostgreSQL: `PostgresGrammar::whereBasic` (líneas 56-62) lo inyecta, SQLiteGrammar no ⇒ relying on it
rompe el camino justo donde corren los tests.

`numero` con término no numérico DEBE devolver conjunto vacío y **NO DEBE** ignorar el filtro: ignorar el
filtro devolvería todas las filas ante un error de tipeo, peor que una lista vacía porque el usuario cree
que filtró.

#### ESCENARIO-4: `enum` y `texto` producen SQL idéntico

- **Given** los campos `estado` (`enum`, `pi.estado`) e `id_puesto` (`texto`, `proyecciones.id_puesto`)
- **When** se aplica el mismo término `aut` a cada uno
- **Then** los bindings DEBEN ser ambos `['%aut%']`
- **And** el SQL DEBE usar el operador `like`/`ilike` en ambos casos
- **And** NO DEBE existir ninguna rama de código que diferencie `enum` de `texto`

#### ESCENARIO-5: `numero` con término numérico produce igualdad exacta

- **Given** proyecciones con `id` 1, 12 y 123
- **When** se aplica `aplicar()` con `campo=id` (`tipo=numero`, `columna=proyecciones.id`) y `termino='12'`
- **Then** el SQL DEBE contener `= ?` (no `like`) sobre `proyecciones.id`
- **And** el binding DEBE ser `[12]`

#### ESCENARIO-6: `numero` con término no numérico devuelve conjunto vacío

- **Given** 5 proyecciones en la BD
- **When** se pide `GET /api/proyecciones?search=abc&search_field=id`
- **Then** DEBE responder 200 con `total: 0`
- **And** NO DEBE devolver las 5 filas

#### ESCENARIO-7: `fecha` emite el `CAST` explícito

- **Given** el campo `fecha_desde` (`tipo=fecha`, `columna=pi.fecha_desde`)
- **When** se aplica `aplicar()` con `termino='2025-03'`
- **Then** el SQL DEBE contener `CAST(pi.fecha_desde AS TEXT)`
- **And** la query DEBE **ejecutar** sin error sobre SQLite (assert de status 200, no inspección de string)

## REQ-BUSQ-4: Los campos de relación se resuelven con EXISTS, nunca con JOIN

Los campos `institucion_nombre`, `institucion_localidad`, `cargo_nombre`, `cargo_codigo` y
`resolucion_nombre` NO son columnas del listado: DEBEN resolverse con `whereHas` anidado. Un `JOIN` a
`instituciones`/`cargos` multiplicaría filas (proyecciones × instrumentos) y rompería
`paginate()->total()` y `select('proyecciones.*')` — el listado muestra una fila por plaza, no una por
combinación. `EXISTS` no duplica y NO DEBE requerir `distinct`.

`cargo` y `resolucion` DEBEN alcanzarse por `['instrumentos', …]` con **acote de año activo**: la relación
`instrumentos` abarca todos los años y sin acotar matchearía el cargo de 2023 desde un listado de 2025.
`institucion` es atributo de la plaza ⇒ `['institucion']`, sin acotar.

Dentro del `EXISTS`, `columna` DEBE ir **sin calificar**: el scope ya es la tabla del último salto.

#### ESCENARIO-8: Buscar por un campo de relación no duplica filas

- **Given** una proyección con 3 instrumentos, cada uno con cargo `Analista`
- **When** se pide `GET /api/proyecciones?search=Analista&search_field=cargo_nombre`
- **Then** DEBE devolver **1** fila (la proyección), no 3
- **And** `meta.total` DEBE ser 1
- **And** la query DEBE usar `exists` y NO DEBE usar `join`

#### ESCENARIO-9: El campo de relación por cargo respeta el año en foco

- **Given** una proyección con instrumento 2025 cuyo cargo es `Analista` y con instrumento 2024 cuyo cargo es `Director`
- **When** se pide `GET /api/proyecciones?anio=2025&search=Director&search_field=cargo_nombre`
- **Then** DEBE devolver `total: 0`
- **And** con `search=Analista` en el mismo año DEBE devolver la proyección

## REQ-BUSQ-5: Escapado de wildcards en un punto único (corrección, no performance)

`whereLike` **NO** escapa `%` ni `_` (`SQLiteGrammar::prepareWhereLikeBinding`, líneas 71-78, sólo
reescribe cuando `caseSensitive === true`). El término del usuario DEBE escaparse **antes** de envolverlo
en `%…%`, y en **ambos** caminos (`__all__` y campo único).

El escapado DEBE vivir en **un único punto** del registro (`escaparLike(string $termino): string`), y
**NO DEBE** aparecer inline en el controller: dos puntos de escapado significan que ninguno es auditable.

El carácter DEBE ser `\` y la sustitución DEBE ser `\ → \\`, `% → \%`, `_ → \_`. En PostgreSQL el
backslash es el escape por defecto ⇒ NO DEBE emitirse cláusula `ESCAPE`.

> Esto es un requisito de **CORRECCIÓN**, no de performance: el seq scan ocurre igual con término escapado
> o no (el wildcard inicial ya lo fuerza). El proposal lo enunció como riesgo de performance y queda
> **corregido** acá. Sin escapado, un `_` en la caja matchea cualquier carácter y un `%` devuelve todo.

El escapado DEBE degradar en SQLite, donde `\` es literal (`LIKE '%100\%%'` devuelve `[]`). El
comportamiento **NO DEBE** ramificarse por driver: ramificar significaría testear la rama de SQLite y
dejar la de producción sin cubrir.

#### ESCENARIO-10: `escaparLike()` es una función pura y exacta

- **Given** el método estático `escaparLike()`
- **When** se prueba como función pura, independiente del driver
- **Then** `'100%'` DEBE devolver `'100\%'`
- **And** `'a_b'` DEBE devolver `'a\_b'`
- **And** `'a\b'` DEBE devolver `'a\\b'`
- **And** `'texto normal'` DEBE devolver `'texto normal'` (sin cambios)
- **And** NO DEBE tocar `%` ni `_` que provengan del wrapping `%…%`

#### ESCENARIO-11: Un término con wildcards ejecuta y devuelve un conjunto acotado

- **Given** proyecciones con `resolucion_ministerial = 'SM-2025-001'` y `'SM-2025-002'`
- **When** se pide `GET /api/proyecciones?search=SM-2025-00_&search_field=resolucion_ministerial` sobre SQLite
- **Then** DEBE responder **200** (la query se ejecutó de verdad)
- **And** DEBE devolver 2 filas (el `_` se trató como literal, no como comodín)
- **And** este test NO DEBE afirmar la semántica literal del escapado en SQLite: sólo que la query ejecuta

#### ESCENARIO-12: El escapado también se aplica en el camino `__all__`

- **Given** el mismo dataset que ESCENARIO-11
- **When** se pide `GET /api/proyecciones?search=SM-2025-00_` (sin `search_field`)
- **Then** DEBE responder 200
- **And** el término DEBE haber pasado por el mismo `escaparLike()`
- **And** un término con `%` DEBE devolver un conjunto acotado, no el total

## REQ-BUSQ-6: `aplicar()` es el único punto de contacto con nombres de columna

La resolución del `key` a columna DEBE ocurrir **dentro** del registro. Un controller que concatenara
columnas volvería a abrir el hueco que el registro cierra. Un `key` desconocido DEBE devolver `null` del
lookup y nunca derivarse por convención (`'pi.' . $key`).

#### ESCENARIO-13: Un `key` hostil nunca alcanza un nombre de columna

- **Given** un `key` que no existe en el registro
- **When** se aplica la búsqueda
- **Then** el lookup DEBE devolver `null`
- **And** el código DEBE caer en la rama congelada `__all__`
- **And** NO DEBE ejecutarse ninguna consulta que contenga el texto del `key` como columna

---

# 2. `busqueda-por-campo`

## REQ-BUSQ-7: `search_field` es opcional y su ausencia equivale a `__all__`

`GET /api/proyecciones` DEBE aceptar `search_field`. `search` sin `search_field` DEBE producir **SQL
byte-idéntico** al actual y el **mismo conjunto de filas**.

El literal `OR` heredado DEBE estar **congelado** en un método propio, **NO DEBE** reconstruirse desde el
registro. Razón: la cadena heredada **NO es un subconjunto** del registro — tiene la rama `is_numeric` y
excluye deliberadamente los 10 campos nuevos. Reconstruirla obligaría a elegir entre cambiar `__all__`
o perder la rama numérica; ambas son cambios de producto.

El único cambio permitido dentro del literal es `ILIKE → whereLike` y el escapado.

#### ESCENARIO-14: Ausencia de `search_field` produce SQL byte-idéntico

- **Given** un término de búsqueda y una distribución conocida de filas
- **When** se compara el SQL y los bindings generados por `search=X` **sin** `search_field`
  contra el SQL congelado de referencia
- **Then** `toSql()` DEBE ser **string idéntico** byte a byte
- **And** los bindings DEBE ser idénticos
- **And** NO DEBE aparecer ninguna columna de los 10 campos nuevos

#### ESCENARIO-15: `search_field=__all__` equivale a la ausencia

- **Given** un término `SM` y un filtro de año
- **When** se piden las tres variantes `search=SM`, `search=SM&search_field=__all__` e
  `search=SM&search_field=%20__all__%20` (con espacios alrededor)
- **Then** las tres DEBEN devolver el mismo `meta.total` y el mismo conjunto de `id`

## REQ-BUSQ-8: `__all__` es un literal congelado, incluidos sus defectos

La rama `if (is_numeric($search)) { … where('proyecciones.id', …) }` que hoy convive con el `orWhere`
chain DEBE **quedar intacta**. Es SQL válido; el problema es de **producto** (buscar `123` devuelve la
proyección 123 **más** todo lo que contenga `123`), no de sintaxis. El match exacto se expone por
`search_field=id` (REQ-BUSQ-9). `search` sin `search_field` tiene otros consumidores (`export-dialog`,
cualquier cliente futuro) y cambiar su semántica sería un cambio de producto disfrazado de refactor.

Los 10 campos nuevos DEBEN quedar **excluidos** de `__all__`.

#### ESCENARIO-16: La rama `is_numeric` se preserva en `__all__`

- **Given** proyecciones con `id=123`, `id=1` e `id=45`, e instrumentos con `n_expediente` que contienen `123`
- **When** se pide `GET /api/proyecciones?search=123` (sin `search_field`)
- **Then** DEBE devolver la proyección `id=123` **y** las que contienen `123` en otros campos
- **And** `meta.total` DEBE ser mayor que 1 (es el comportamiento heredado, congelado a propósito)

#### ESCENARIO-17: `__all__` no cubre los 10 campos nuevos

- **Given** una proyección cuyo único dato distintivo está en `observaciones = 'nota única 777'`
- **When** se pide `GET /api/proyecciones?search=777`
- **Then** DEBE devolver `total: 0` (el campo no participa de `__all__`)
- **And** con `search_field=observaciones` DEBE devolver 1 fila

## REQ-BUSQ-9: `search_field=id` expone el match numérico exacto

`search_field=id` DEBE usar `tipo=numero` ⇒ igualdad. Es el mecanismo que **arregla el problema de
producto** del `is_numeric` sin tocar `__all__`.

#### ESCENARIO-18: Búsqueda exacta por id sin el ruido del OR

- **Given** proyecciones con `id=123`, `id=1234` y `id=45`
- **When** se pide `GET /api/proyecciones?search=123&search_field=id`
- **Then** DEBE devolver **exactamente 1** fila, la de `id=123`
- **And** `meta.total` DEBE ser 1

## REQ-BUSQ-10: `search_field` desconocido degrada a `__all__` con `Log::warning` — NO 422

> **OVERRIDE EXPLÍCITO DE UN CRITERIO DE ÉXITO DEL PROPOSAL.** El proposal dice "un `search_field`
> desconocido se rechaza (422)". Este requisito **reemplaza** ese criterio: el sistema **NO DEBE**
> devolver 422. La fase de specs codifica el **fallback**, no el rechazo. Justificación verificada:
> 1. `CrudTable` **no tiene estado de error**: `loadServerSide()` (líneas 635-638) sólo hace
>    `console.error` + `serverLoading(false)`; un 422 se renderiza como tabla vacía sin mensaje.
> 2. El único productor es el picker, alimentado por el endpoint: un key desconocido sólo puede venir de
>    un cliente stale (bundle viejo contra endpoint nuevo). En ese caso el 422 rompe una página que
>    **funcionaba**; el fallback la degrada al comportamiento de hoy.
> 3. La seguridad **NO** depende de la política: el key se resuelve contra un array fijo y un miss
>    devuelve `null`. **NO DEBE** haber interpolación (§REQ-BUSQ-6).
> 4. Precedente en el mismo archivo: `aplicarOrden()` ya degrada en silencio `sort_by` desconocido a
>    `'id'` (líneas 368-371). Es la convención de la casa.

El sistema DEBE emitir un `Log::warning` con el `key` recibido. Eso convierte "lenient" en
"responsable": la deriva se ve en los logs de Railway.

La entrada DEBE normalizarse antes de buscar: `is_string($raw) ? trim($raw) : null` — el `is_string`
evita que `?search_field[]=x` reviente.

#### ESCENARIO-19: Un `search_field` desconocido NO devuelve 422

- **Given** un cliente que manda `search_field=columna_inexistente`
- **When** se pide `GET /api/proyecciones?search=SM&search_field=columna_inexistente`
- **Then** DEBE responder **200**, NO 422
- **And** el conjunto de filas DEBE ser idéntico al de `search=SM` sin `search_field`
- **And** NO DEBE ejecutarse ninguna consulta que contenga el texto recibido como columna

#### ESCENARIO-20: La deriva queda registrada

- **Given** `Log` en nivel `warning` capturado por el test
- **When** se pide `GET /api/proyecciones?search=x&search_field=no_existe`
- **Then** DEBE haberse emitido exactamente un `Log::warning` mencionando `search_field`
- **And** el mensaje DEBE incluir el `key` recibido

## REQ-BUSQ-11: Robustez ante `search_field` no-string

`?search_field[]=a&search_field[]=b` (inyección de array) NO DEBE producir 500. DEBE tratarse como
ausente ⇒ `__all__`.

#### ESCENARIO-21: Inyección de array degrada a `__all__`

- **Given** el mismo término que ESCENARIO-15
- **When** se pide `GET /api/proyecciones?search=SM&search_field[]=a&search_field[]=b`
- **Then** DEBE responder 200
- **And** `meta.total` DEBE coincidir con el de `search=SM` sin `search_field`

## REQ-BUSQ-12: Portabilidad por driver — `whereLike`, nunca `ILIKE` crudo

`phpunit.xml` (líneas 26-27) fuerza `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:`; producción es
**PostgreSQL**. El bloque `ILIKE` heredado (líneas 106-122) es Postgres-only. Todo el código nuevo y el
migrado DEBE usar el constructor portable:

```
whereLike($columna, "%$patron%", caseSensitive: false)
```

que compila `ILIKE` en Postgres (`PostgresGrammar::whereLike`) y `LIKE` en SQLite
(`SQLiteGrammar::whereLike`). **NO DEBE** usarse `ILIKE` crudo en ningún punto del camino de búsqueda.

La migración del bloque heredado a `whereLike` DEBE ocurrir **en el mismo cambio**: sin ella el camino
`__all__` queda intestable en SQLite.

#### ESCENARIO-22: La búsqueda por campo único ejecuta en SQLite

- **Given** la suite corriendo con `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`
- **When** se pide `GET /api/proyecciones?search=aut&search_field=estado` y se asserta el status
- **Then** DEBE responder **200**
- **And** el SQL emitido NO DEBE contener `ILIKE` literal
- **And** este es el test que hoy fallaría: sobre SQLite el `ILIKE` **compila** en `toSql()` y sólo
  revienta al ejecutar con `SQLSTATE[HY000] … near "ILIKE": syntax error`

## REQ-BUSQ-13: Los 10 campos antes no buscables devuelven filas

Cada uno de los 10 `key` de REQ-BUSQ-1 DEBE devolver filas cuando se busca por él, incluyendo los de
`tipo=fecha` y los de `origen=relacion`.

#### ESCENARIO-23: Los 10 campos son alcanzables individualmente

- **Given** un dataset con un valor distintivo en cada uno de los 10 campos no buscables
- **When** se itera la lista de 10 `key` y para cada uno se pide `GET /api/proyecciones?search=<valor>&search_field=<key>`
- **Then** cada respuesta DEBE ser 200 con `total >= 1`
- **And** con el mismo término y `search_field=__all__` el total DEBE ser 0

#### ESCENARIO-24: Los dos `tipo=fecha` son alcanzables

- **Given** un instrumento con `fecha_desde = '2025-03-15'`
- **When** se pide `GET /api/proyecciones?search=2025-03&search_field=fecha_desde`
- **Then** DEBE responder 200 con `total >= 1`
- **And** el SQL DEBE contener `CAST(pi.fecha_desde AS TEXT)`

---

# 3. `busqueda-descubrimiento`

## REQ-BUSQ-14: `GET /api/proyecciones/campos-buscables`

El backend DEBE exponer `GET /api/proyecciones/campos-buscables`, autenticado por Sanctum (el middleware
ya vigente en el grupo de `routes/api.php:20`). Sin autenticar DEBE responder 401.

| Código | Cuerpo |
|--------|--------|
| 200 | `{"data": [ …24 entradas… ]}` en el orden del registro, `__all__` primero |
| 401 | cuerpo de la autenticación fallida |

Cada entrada DEBE tener exactamente `{key, label, origen, tipo}`. En el sentinel, `origen` y `tipo` DEBEN
ser `null`. El orden DEBE ser el del registro, **sin** campo `grupo`: `SearchableSelectComponent` es una
lista plana y no soporta `optgroup`.

El payload DEBE derivarse del registro, NO DEBE ser una lista escrita a mano.

#### ESCENARIO-25: El endpoint devuelve las 24 entradas con el sentinel primero

- **Given** un usuario autenticado
- **When** se hace `GET /api/proyecciones/campos-buscables`
- **Then** DEBE responder 200 con `data` de longitud **24**
- **And** `data[0]` DEBE ser `{key:"__all__", label:"Todos los campos", origen:null, tipo:null}`
- **And** `data[1]` DEBE ser `{key:"id", label:"ID de proyección", origen:"plaza", tipo:"numero"}`
- **And** las 24 DEBEN coincidir con el registro (mismo orden)

#### ESCENARIO-26: El endpoint exige autenticación

- **Given** una request sin `Authorization` o con token inválido
- **When** se hace `GET /api/proyecciones/campos-buscables`
- **Then** DEBE responder 401

## REQ-BUSQ-15: Orden de rutas y ausencia de caché de servidor

La ruta DEBE declararse **antes** de `Route::apiResource('proyecciones', …)` (`routes/api.php:40`).
`campos-buscables` es un string válido que matchea el placeholder `{proyeccion}`; si la ruta se declara
después, Laravel la resuelve como `{proyeccion}` y responde 404 o intenta model binding. Punto de
inserción: después de `proyecciones/opciones-filtro` (línea 32). DEBE existir un test de regresión de
ruteo, porque un reordenamiento futuro del archivo rompe esto en silencio.

El endpoint **NO DEBE** usar `Cache::remember` ni ETag. El payload sale de una constante de clase: el
costo de CPU es ~0 y cachear en servidor metería un problema de invalidación a cambio de nada. El
cacheo, si acaso, es del lado cliente (§REQ-BUSQ-17).

#### ESCENARIO-27: La ruta no es capturada por el `apiResource`

- **Given** un usuario autenticado
- **When** se hace `GET /api/proyecciones/campos-buscables`
- **Then** DEBE responder 200 con 24 entradas
- **And** NO DEBE responder 404
- **And** NO DEBE intentar resolver un modelo `Proyeccion` con id `"campos-buscables"`
- **And** en `routes/api.php`, el índice de `Route::get('proyecciones/campos-buscables'…)` DEBE ser
  menor que el de `Route::apiResource('proyecciones'…)`

---

# 4. Verificación (`strict_tdd: true`)

## REQ-BUSQ-23: El comportamiento y sus tests se entregan juntos

`strict_tdd: true` está activo en `backend/openspec/config.yaml`. Todo comportamiento nuevo DEBE tener
test en el mismo cambio. Hoy la búsqueda tiene **cero** cobertura (`grep -r "search" tests/` → 0
resultados), así que el camino `__all__` completo queda inexistente como red de seguridad.

#### ESCENARIO-28: La suite nueva cubre los caminos nuevos y el heredado

- **Given** el árbol de tests del backend
- **When** se corre `php artisan test`
- **Then** DEBE existir `tests/Unit/Services/RegistroCamposBuscablesTest.php` y
  `tests/Feature/Api/ProyeccionBusquedaTest.php`
- **And** NO DEBE existir ningún test que dependa de `ILIKE` crudo

## REQ-BUSQ-24: El test de portabilidad EJECUTA la query — prohibido inspeccionar el string

El test de portabilidad DEBE assertar el **status HTTP** de una request real (`assertOk()`) contra
`DB_CONNECTION=sqlite`. Sólo un 200 prueba que la query corrió.

**NO DEBE** afirmarse `assertStringContainsString('ILIKE', $query->toSql())` ni ninguna variante que
inspeccione el SQL compilado: sobre SQLite `ILIKE` **compila** en `toSql()` y sólo falla al ejecutar, así
que ese test daría **verde en dev y rojo en CI** — justo al revés de lo que pretende. En `aplicar()` por
tipo (REQ-BUSQ-3) la inspección de `toSql()` es válida porque se asserta la **forma** del SQL (`= ?`,
`CAST(...)`, `like ?`), no la **compatibilidad** del motor.

#### ESCENARIO-29: La aserción es el status HTTP, no el string SQL

- **Given** la suite configurada con SQLite `:memory:`
- **When** se pide `GET /api/proyecciones?search=<término>`
- **Then** el test DEBE assertar `assertOk()`
- **And** NO DEBE contener `assertStringContainsString` sobre `ILIKE`
- **And** si alguien reintrodujera `ILIKE`, el test DEBE fallar por error de sintaxis al ejecutar

## REQ-BUSQ-25: Baseline backend GREEN, sin regresiones

Baseline verificado al **2026-10-05**: backend **GREEN 149/149**, 476 assertions (`php artisan test`).
Cualquier falla posterior es **regresión de este cambio**. El total final DEBE ser **≥ 149**.

#### ESCENARIO-30: La suite completa sigue en verde y crece

- **Given** el baseline GREEN 149/149
- **When** se corre `php artisan test` con los tests nuevos
- **Then** DEBE terminar en verde, 0 fallas
- **And** el total DEBE ser ≥ 149
- **And** `./vendor/bin/pint --test` DEBE estar limpio

## REQ-BUSQ-26: El piso de fallas frontend es preexistente

Baseline verificado al **2026-10-05**: frontend **RED 29 passed / 10 failed** (`bun run test`). Las 10
fallas son **preexistentes** y **NO DEBEN** atribuirse a este cambio. Ninguna toca búsqueda. Desglose
exacto:

| Cantidad | Test | Error | Causa |
|----------|------|-------|-------|
| 5 | `should delete {institucion,cargo,nivel,funcion,turno}` | `AssertionError: expected null to be undefined` | la spec asume `undefined`, HttpClient emite `null` en 204 |
| 5 | `should handle HTTP error gracefully` | `expectAsync() is not defined` | `expectAsync` existe sólo en Jasmine; `tsconfig.integration.json:6` sigue con `types: ["jasmine"]` bajo Vitest 4.1.5 |

Los tests nuevos **DEBEN** pasar en verde y el conteo de fallas **DEBE** quedar en **≤ 10**.

#### ESCENARIO-31: Las fallas preexistentes siguen siendo 10 y las nuevas son 0

- **Given** el baseline frontend de 29 passed / 10 failed
- **When** se corre `bun run test` con los tests nuevos de búsqueda
- **Then** los tests nuevos **DEBEN** pasar en verde
- **And** el conteo total de fallas DEBE quedar en ≤ 10
- **And** las 10 fallas DEBEN seguir siendo las de la tabla (ninguna `DEBE` tocar búsqueda)
- **And** el reporte de verificación **NO DEBE** atribuir esas 10 a este cambio

## REQ-BUSQ-27: Los tests frontend DEBEN llamarse `*.integration.spec.ts`

`tsconfig.integration.json` sólo incluye el glob `src/**/*.integration.spec.ts`, y
`angular.json:71-77` corre `@angular/build:unit-test` con ese tsconfig. Un test con cualquier otro
nombre **no se ejecuta**: verde por ausencia.

#### ESCENARIO-32: Un test con el nombre correcto se ejecuta

- **Given** `src/app/shared/components/crud-table/crud-table.integration.spec.ts`
- **When** se corre `bun run test`
- **Then** el archivo **DEBE** ser recolectado por Vitest
- **And** sus asserts **DEBEN** ejecutarse
- **And** ningún test de este cambio DEBE usar un sufijo distinto de `.integration.spec.ts`

## REQ-BUSQ-28: Sin `pg_trgm` ni índices nuevos; umbral de revisita explícito

Este cambio **NO DEBE** agregar `pg_trgm` **NI** índices nuevos. Hallazgo verificado con `EXPLAIN`
contra 20.000 filas: un índice **btree** sobre una columna `varchar` **NO** elimina el `Seq Scan`, porque
`PostgresGrammar::whereBasic` (líneas 56-62) inyecta un cast `%s::text` en **todo** operador
`like`/`ilike`; el plan queda `Filter: ((estado)::text ~~ '…')` + `Seq Scan`. El cast implícito
`varchar::text` — no el wildcard inicial — es el motivo real.

Por lo tanto la mitigación futura DEBE ser **trigram + GIN**, y para columnas `fecha` un índice sobre la
**expresión** (`GIN ((fecha_desde::text) gin_trgm_ops)`), porque `date::text` **NO** es un cast removible.

#### ESCENARIO-33: Esta fase no agrega índices y deja el umbral escrito

- **Given** este cambio
- **When** se revisan las migraciones y el diff
- **Then** NO DEBE existir ninguna migración nueva ni ningún `CREATE INDEX`/`CREATE EXTENSION pg_trgm`
- **And** este requisito DEBE quedar documentado con los umbrales: **R1** filas del snapshot del año en
  foco **> 20.000**, o **R2** p95 de `GET /api/proyecciones` en los logs de Railway **> 500 ms**
- **And** al dispararse cualquiera de las dos, la acción DEBE ser trigram + GIN (índice sobre la
  **expresión** para `fecha`), NO "agregar un índice más"

---

## Trazabilidad de los ADRs del design

| ADR | Verificado | Codificado en |
|-----|-----------|---------------|
| ADR-1 registro único, el front nunca manda columnas | ✓ | REQ-BUSQ-1, ESCENARIO-13 |
| ADR-2 `__all__` literal congelado | ✓ | REQ-BUSQ-7/-8, ESCENARIO-14/15/16/17 |
| ADR-3 `whereHas` (EXISTS), nunca JOIN | ✓ | REQ-BUSQ-4, ESCENARIO-8/9 |
| ADR-4 `CAST(... AS TEXT)` explícito en `fecha` | ✓ | REQ-BUSQ-3, ESCENARIO-7 |
| ADR-5 escapado backslash, un punto, límite SQLite | ✓ | REQ-BUSQ-5, ESCENARIO-10/11/12 |
| ADR-6 `numero` ⇒ igualdad; no numérico ⇒ vacío | ✓ | REQ-BUSQ-3/-9, ESCENARIO-4/5/6/18 |
| ADR-7 desconocido ⇒ `__all__` + warning, **no 422** | ✓ | REQ-BUSQ-10, ESCENARIO-19/20 |
| ADR-8 `search`/`search_field` post-merge | ✓ | REQ-BUSQ-20 (Engram), ESCENARIO-42 |
| ADR-9 debounce con `setTimeout`, no `effect()` | ✓ | REQ-BUSQ-18 (Engram), ESCENARIO-37/38/39 |
| ADR-10 `SearchableSelectComponent` a `shared/` | ✓ | REQ-BUSQ-16 (Engram), ESCENARIO-34/35 |
| ADR-11 `reloadData()` resetea `currentPage` | ✓ | REQ-BUSQ-19 (Engram), ESCENARIO-34/35 |
| ADR-12 endpoint sin caché | ✓ | REQ-BUSQ-15/-17 |

## Correcciones al proposal registradas acá

| # | Proposal decía | Spec dice | Razón |
|---|----------------|-----------|-------|
| 1 | `search_field` desconocido ⇒ **422** | ⇒ fallback `__all__` + `Log::warning` | ADR-7: `CrudTable` no tiene estado de error; precedente en `aplicarOrden()` |
| 2 | Escapar `%`/`_` es un riesgo de **performance** | Es un requisito de **corrección** | El seq scan ocurre igual (wildcard inicial); lo que se compra es resultado correcto |
| 3 | "Mueve el bloque `search` al registro" | `__all__` es literal **congelado**, no derivado | La cadena heredada no es subconjunto del registro (rama `is_numeric` + exclusión de los 10) |
| 4 | `strict_tdd` + portabilidad con `whereLike` | Se prohíbe explícitamente `assertStringContainsString('ILIKE', toSql())` | `ILIKE` compila en SQLite y sólo falla al ejecutar: verde en dev, rojo en CI |