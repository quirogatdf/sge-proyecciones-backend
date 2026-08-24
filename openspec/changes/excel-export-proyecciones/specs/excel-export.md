# Specification: Excel Export para Proyecciones

## 1. API Endpoint

### 1.1 Route Definition
- **Method**: GET
- **URI**: `/api/proyecciones/export`
- **Middleware**: `auth:sanctum`
- **Response**: Binary Excel file (`application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`)

### 1.2 Query Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `motivo` | string | No | `"Continuidad"` | Export type: `"Continuidad"` or `"Creacion"` |
| `id_nivel` | integer | No | null | Filter by nivel ID |
| `id_institucion` | integer | No | null | Filter by institution ID. `null` = all institutions |
| `id_cargo` | integer | No | null | Filter by cargo ID. `null` = all cargos |

### 1.3 Response Codes

| Code | Condition |
|------|-----------|
| 200 | Successful export (even if 0 records) |
| 401 | Unauthenticated |
| 422 | Invalid parameters |
| 500 | Server error during generation |

### 1.4 Validation Rules
- `motivo` MUST be one of: `"Continuidad"`, `"Creacion"`
- `id_nivel` MUST exist in `niveles` table if provided
- `id_institucion` MUST exist in `instituciones` table if provided
- `id_cargo` MUST exist in `cargos` table if provided

---

## 2. Excel Format: Continuidad

### 2.1 Column Structure

| # | Column Header | Source | Transform | Notes |
|---|---------------|--------|-----------|-------|
| 1 | Orden | Sequential | Auto-increment from 1 | Based on filtered result order |
| 2 | Cantidad | `proyecciones.cargos` or `proyecciones.horar` | `MAX(cargos, horar)` | See Cantidad Calculation Rule |
| 3 | Codigo | `cargos.codigo` | Direct | 4-char code |
| 4 | Denominacion | `cargos.nombre` | Direct | Full cargo name |
| 5 | Con Funcion | `funciones.nombre` | Direct | Full function name |
| 6 | Turno | `turnos.sigla` | Direct | Initials only (e.g., "MT", "TT") |
| 7 | Destino 2026 | `proyecciones.destino_nuevo` | Direct | New destination field |
| 8 | Instrumento Legal | `proyecciones.resolucion_ministerial` | Direct | Ministerial resolution |
| 9 | Destino 2027 | `null` | Always null | Reserved for future |

### 2.2 Cantidad Calculation Rule

```
IF cargos.tipo = 'H' (Honorario):
    Cantidad = proyecciones.horar
ELSE IF cargos.tipo = 'C' (Contratado):
    Cantidad = proyecciones.cargos
ELSE:
    Cantidad = MAX(proyecciones.cargos, proyecciones.horar)
```

### 2.3 Sorting
Results MUST be sorted by:
1. `instituciones.nombre` (ASC)
2. `proyecciones.orden` (ASC)

---

## 3. Excel Format: Creacion

### 3.1 Column Structure
Same as Continuidad format. The `motivo` parameter determines which records are exported, not the column structure.

### 3.2 Filter Logic
When `motivo=Creacion`:
```sql
WHERE proyecciones.motivo = 'Creacion'
```

When `motivo=Continuidad`:
```sql
WHERE proyecciones.motivo = 'Continuidad'
```

---

## 4. Data Transformation Rules

### 4.1 Empty Results
- SHALL return valid `.xlsx` file with headers only
- MUST NOT return error for legitimate zero-result queries

### 4.2 Large Datasets
- MUST limit to 10,000 records maximum
- If exceeded, return 413 with message: `"Demasiados registros para exportar. Use filtros más específicos."`

### 4.3 Null Handling
- `resolucion_ministerial` when null → empty cell
- `destino_nuevo` when null → empty cell
- `destino_2027` → always null (empty cell)

---

## 5. Filter Dialog

### 5.1 Trigger
- User clicks "Exportar Excel" button in `ProyeccionesListComponent`
- Dialog opens BEFORE export request

### 5.2 Dialog Structure

```
┌─────────────────────────────────────────┐
│         Exportar Proyecciones            │
├─────────────────────────────────────────┤
│                                         │
│  Tipo de Exportación:                   │
│  ○ Continuidad  ○ Creación              │
│                                         │
│  Institución:                           │
│  [Todas las instituciones ▼]            │
│                                         │
│  Cargo:                                 │
│  [Todos los cargos ▼]                   │
│                                         │
├─────────────────────────────────────────┤
│         [Cancelar]  [Exportar]          │
└─────────────────────────────────────────┘
```

### 5.3 Dropdown Options

#### Institución Dropdown
- First option: `"Todas las instituciones"` (value: null)
- Remaining options: All institutions from API, sorted by nombre

#### Cargo Dropdown
- First option: `"Todos los cargos"` (value: null)
- Remaining options: All cargos from API, sorted by nombre

### 5.4 Validation
- Export button disabled until tipo is selected
- Show loading spinner during export

### 5.5 User Flow
1. Click "Exportar Excel" button
2. Dialog opens with defaults: Continuidad, Todas, Todos
3. User adjusts filters as needed
4. Click "Exportar"
5. Dialog closes, loading indicator shows
6. Excel file downloads automatically
7. Success toast: `"Exportación completada"`

---

## 6. Error Handling

### 6.1 No Records Found
- Return valid Excel with headers only
- Show info toast: `"No se encontraron registros con los filtros seleccionados"`

### 6.2 Export Failure
- Show error toast: `"Error al generar el archivo Excel"`
- Log error in backend

### 6.3 Authentication Error
- Redirect to login (existing behavior)

---

## 7. Scenarios

### Scenario 1: Export all Continuidad records
```
GIVEN the user is authenticated
WHEN GET /api/proyecciones/export?motivo=Continuidad
THEN return Excel file with all Continuidad records
AND columns match Continuidad format
AND Orden starts at 1
```

### Scenario 2: Export filtered by institution
```
GIVEN the user is authenticated
WHEN GET /api/proyecciones/export?motivo=Continuidad&id_institucion=5
THEN return Excel file with only records from institution 5
```

### Scenario 3: Export filtered by cargo
```
GIVEN the user is authenticated
WHEN GET /api/proyecciones/export?motivo=Creacion&id_cargo=3
THEN return Excel file with only Creacion records for cargo 3
```

### Scenario 4: Export with no results
```
GIVEN the user is authenticated
WHEN GET /api/proyecciones/export?motivo=Continuidad&id_institucion=999
THEN return Excel file with headers only
AND show info toast
```

### Scenario 5: Export exceeds limit
```
GIVEN the user is authenticated
WHEN GET /api/proyecciones/export?motivo=Continuidad
AND result exceeds 10,000 records
THEN return 413 with error message
```

### Scenario 6: Dialog filter selection
```
GIVEN the export dialog is open
WHEN user selects Institución="Escuela A" and Cargo="Todos"
AND clicks "Exportar"
THEN API request includes id_institucion=Escuela_A_id
AND id_cargo is not included (null)
```

---

## 8. Acceptance Criteria

- [ ] Endpoint returns valid Excel file
- [ ] Continuidad format has all 9 columns
- [ ] Creacion format has all 9 columns
- [ ] Orden is sequential starting from 1
- [ ] Cantidad calculates correctly based on cargo type
- [ ] Turno shows sigla (initials) not full name
- [ ] Destino 2027 is always null
- [ ] Dialog shows before export
- [ ] Filters work correctly (institution, cargo, both)
- [ ] "Todas"/"Todos" options work as null filters
- [ ] Empty results return valid Excel with headers
- [ ] Large dataset limit (10,000) enforced
- [ ] File downloads automatically in browser
