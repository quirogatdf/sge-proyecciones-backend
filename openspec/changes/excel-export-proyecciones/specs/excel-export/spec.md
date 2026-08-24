# Excel Export Specification

## Purpose

Generación de archivos Excel a partir de proyecciones, soportando dos formatos de salida (Continuidad y Creación) con filtros por institución, cargo y motivo.

## Requirements

### Requirement: API Endpoint for Excel Export

The system SHALL expose `GET /api/proyecciones/export` that returns a `.xlsx` file download when called with valid query parameters and an authenticated user.

| Parameter     | Type   | Required | Values                         |
|---------------|--------|----------|--------------------------------|
| `id_nivel`    | int    | No       | Any valid nivel ID             |
| `id_institucion` | int | No       | Any valid institucion ID       |
| `id_cargo`    | int    | No       | Any valid cargo ID             |
| `motivo`      | string | No       | `Continuidad` or `Creación`   |

The endpoint SHALL require Sanctum authentication. Unauthenticated requests MUST return 401.

The response Content-Type MUST be `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`. The Content-Disposition header MUST include `filename="proyecciones_{motivo}_{timestamp}.xlsx"`.

#### Scenario: Authenticated user exports Continuidad with all filters

- GIVEN an authenticated user with valid Sanctum token
- WHEN GET `/api/proyecciones/export?motivo=Continuidad&id_nivel=1&id_institucion=2&id_cargo=3`
- THEN the system SHALL return 200 with an `.xlsx` file
- AND the response Content-Type SHALL be the Excel MIME type
- AND the file SHALL contain only records matching all specified filters

#### Scenario: Unauthenticated request returns 401

- GIVEN no Authorization header or invalid token
- WHEN GET `/api/proyecciones/export?motivo=Continuidad`
- THEN the system SHALL return 401

#### Scenario: Missing motivo parameter defaults to Continuidad

- GIVEN an authenticated user
- WHEN GET `/api/proyecciones/export` (no `motivo` param)
- THEN the system SHALL default to `motivo=Continuidad`

#### Scenario: Invalid motivo value returns 422

- GIVEN an authenticated user
- WHEN GET `/api/proyecciones/export?motivo=Invalido`
- THEN the system SHALL return 422 with validation error message

---

### Requirement: Excel File Format — Continuidad

The exported Excel file SHALL contain exactly these columns in this order when `motivo=Continuidad`:

| # | Column            | Source                              | Transform                   |
|---|-------------------|-------------------------------------|-----------------------------|
| 1 | Orden             | Sequential                          | Start at 1, increment by 1  |
| 2 | Cantidad          | `max(proyeccion.cargos, proyeccion.horar)` | Integer, calculated    |
| 3 | Codigo            | `cargo.codigo`                      | Direct                      |
| 4 | Denominacion      | `cargo.nombre`                      | Direct                      |
| 5 | Con Funcion       | `funcion.nombre`                    | Direct, nullable            |
| 6 | Turno             | `turno.sigla`                       | Initial only (e.g. "M", "T", "N") |
| 7 | Destino 2026      | `proyeccion.destino_nuevo`          | Direct, nullable            |
| 8 | Instrumento Legal | `proyeccion.resolucion_ministerial` | Direct, nullable            |
| 9 | Destino 2027      | Always null                         | MUST be empty/blank         |

The header row SHALL be bold. Columns SHALL auto-resize to content. Row numbering (Orden) SHALL start at 1 and increment sequentially based on filtered results order.

#### Scenario: Continuidad export produces correct columns

- GIVEN proyecciones with `motivo='Continuidad'` exist
- WHEN the user exports with `motivo=Continuidad`
- THEN the Excel SHALL have columns: Orden, Cantidad, Codigo, Denominacion, Con Funcion, Turno, Destino 2026, Instrumento Legal, Destino 2027
- AND "Destino 2027" column SHALL be empty for all rows

#### Scenario: Cantidad uses max(cargos, horar) for Contratado

- GIVEN a proyeccion with `tipo='C'` (Contratado), `cargos=3`, `horar=2`
- WHEN exported to Continuidad
- THEN the "Cantidad" column SHALL show `3` (max of 3 and 2)

#### Scenario: Cantidad uses max(cargos, horar) for Honorario

- GIVEN a proyeccion with `tipo='H'` (Honorario), `cargos=1`, `horar=5`
- WHEN exported to Continuidad
- THEN the "Cantidad" column SHALL show `5` (max of 1 and 5)

#### Scenario: Turno shows sigla not full name

- GIVEN a proyeccion linked to a turno with `sigla='M'` and name='Matutino'
- WHEN exported
- THEN the "Turno" column SHALL show `M`, not `Matutino`

---

### Requirement: Excel File Format — Creación

The exported Excel file SHALL follow the same column structure as Continuidad when `motivo=Creación`. All transformation rules from Continuidad apply identically.

#### Scenario: Creación export produces same column structure

- GIVEN proyecciones with `motivo='Creación'` exist
- WHEN the user exports with `motivo=Creación`
- THEN the Excel SHALL have the same 9 columns as Continuidad format

---

### Requirement: Orden Sequential Numbering

The "Orden" column MUST be a sequential integer starting at 1, incrementing by 1 for each row. The order SHALL be determined by the default query sort order (by `orden` field in the proyecciones table, then by `id`).

#### Scenario: Orden resets for each export

- GIVEN 50 filtered proyecciones
- WHEN exported
- THEN the first row Orden SHALL be 1 and the last row Orden SHALL be 50

#### Scenario: Orden respects filtered result set

- GIVEN 100 total proyecciones but filters reduce to 15
- WHEN exported
- THEN only 15 rows SHALL appear, with Orden 1 through 15

---

### Requirement: Filter Application

Filters SHALL be AND-combined. If no filter is provided, all records for the given motivo SHALL be included.

| Filter         | Behavior                                      |
|----------------|-----------------------------------------------|
| `id_nivel`     | Filter by `proyeccion.id_nivel`               |
| `id_institucion` | Filter by `proyeccion.id_institucion`       |
| `id_cargo`     | Filter by `proyeccion.id_cargo`               |
| `motivo`       | Filter by `proyeccion.motivo`                 |

#### Scenario: All filters applied

- GIVEN proyecciones across 3 niveles, 5 instituciones, 10 cargos
- WHEN exporting with `id_nivel=1&id_institucion=2&id_cargo=3&motivo=Continuidad`
- THEN ONLY proyecciones matching ALL four conditions SHALL appear

#### Scenario: No filters returns all for motivo

- GIVEN 200 proyecciones with motivo='Continuidad'
- WHEN exporting with only `motivo=Continuidad`
- THEN all 200 rows SHALL appear

---

### Requirement: Empty Result Set

When no proyecciones match the specified filters, the system SHALL still return a valid `.xlsx` file with only the header row and no data rows. The system MUST NOT return an error or empty response.

#### Scenario: No records match filters

- GIVEN no proyecciones match `id_cargo=999`
- WHEN exporting with `id_cargo=999&motivo=Continuidad`
- THEN the system SHALL return 200 with a valid `.xlsx` file
- AND the file SHALL contain only the header row

---

### Requirement: Large Dataset Handling

The system SHALL support exporting up to 10,000 records per request. If the filtered result exceeds 10,000 records, the system MUST return 413 with a message indicating the result set is too large and suggesting narrower filters.

#### Scenario: Export within limit succeeds

- GIVEN 5,000 proyecciones match filters
- WHEN exporting
- THEN the system SHALL return the complete Excel file with 5,000 data rows

#### Scenario: Export exceeds limit returns 413

- GIVEN 15,000 proyecciones match filters
- WHEN exporting
- THEN the system SHALL return 413 with error message "Demasiados registros para exportar. Use filtros más específicos."
- AND NO file SHALL be generated

---

### Requirement: Export Failure Handling

If the Excel generation process fails (e.g., Maatwebsite exception, memory limit), the system MUST return 500 with a generic error message. The system MUST NOT expose internal error details to the client.

#### Scenario: Excel generation throws exception

- GIVEN a valid request with filters
- WHEN Maatwebsite/Excel throws a PhpSpreadsheet exception during generation
- THEN the system SHALL return 500 with message "Error al generar el archivo Excel"
- AND the error SHALL be logged with full stack trace
