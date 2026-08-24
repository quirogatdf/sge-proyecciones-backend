# Tasks: Excel Export para Proyecciones

## Phase 1: Infrastructure (Backend)

### 1.1 Install Maatwebsite/Excel
- **Description**: Add `maatwebsite/excel` package to Laravel project
- **Command**: `composer require maatwebsite/excel`
- **Verify**: Run `php artisan --version` and check `composer.json` for dependency
- **Complexity**: Low

### 1.2 Configure Excel Export Settings
- **Description**: Publish and configure Maatwebsite/Excel config if needed
- **Command**: `php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config`
- **Verify**: Config file exists at `config/excel.php`
- **Complexity**: Low

---

## Phase 2: Backend Implementation

### 2.1 Create ProyeccionExportService
- **Description**: Service class for data transformation and query logic
- **File**: `backend/app/Services/ProyeccionExportService.php`
- **Responsibilities**:
  - Build query with filters (motivo, id_nivel, id_institucion, id_cargo)
  - Apply 10,000 record limit
  - Transform data: Cantidad calculation, Turno sigla, Orden sequential
  - Return transformed collection
- **Complexity**: Medium

### 2.2 Create ProyeccionExport Class
- **Description**: Maatwebsite/Excel export class implementing FromCollection
- **File**: `backend/app/Exports/ProyeccionExport.php`
- **Responsibilities**:
  - Implement `FromCollection`, `WithHeadings`, `WithMapping`
  - Map columns: Orden, Cantidad, Codigo, Denominacion, Con Funcion, Turno, Destino 2026, Instrumento Legal, Destino 2027
  - Use chunked reading for memory efficiency
- **Complexity**: Medium

### 2.3 Create ProyeccionExportController
- **Description**: Controller for export endpoint
- **File**: `backend/app/Http/Controllers/Api/ProyeccionExportController.php`
- **Responsibilities**:
  - Validate query parameters
  - Call ProyeccionExportService
  - Return StreamedResponse with Excel file
  - Handle empty results (return valid Excel with headers)
  - Handle limit exceeded (return 413)
- **Complexity**: Medium

### 2.4 Add Export Route
- **Description**: Register GET route in api.php
- **File**: `backend/routes/api.php`
- **Route**: `Route::get('proyecciones/export', [ProyeccionExportController::class, 'export'])`
- **Middleware**: auth:sanctum
- **Verify**: `php artisan route:list --path=proyecciones` shows new route
- **Complexity**: Low

---

## Phase 3: Frontend Implementation

### 3.1 Add Export Service Method
- **Description**: Add exportExcel method to ProyeccionesService
- **File**: `frontend/src/app/core/services/proyecciones.service.ts`
- **Signature**: `exportExcel(params: { motivo?, id_nivel?, id_institucion?, id_cargo? }): Observable<Blob>`
- **Verify**: Service compiles without errors
- **Complexity**: Low

### 3.2 Create ExportDialogComponent
- **Description**: Standalone dialog for export filter selection
- **File**: `frontend/src/app/features/proyecciones/export-dialog.component.ts`
- **Features**:
  - Signal-based state (isOpen, filters)
  - Radio buttons for motivo (Continuidad/Creacion)
  - Dropdown for institucion (with "Todas" option)
  - Dropdown for cargo (with "Todos" option)
  - Cancel/Export buttons
  - Loading state during export
- **Complexity**: High

### 3.3 Integrate Export Button in List Component
- **Description**: Add export button and dialog to ProyeccionesListComponent
- **File**: `frontend/src/app/features/proyecciones/proyecciones-list.component.ts`
- **Changes**:
  - Import ExportDialogComponent
  - Add "Exportar Excel" button in toolbar/header
  - Wire button to open dialog
  - Handle dialog close with export trigger
- **Verify**: Button visible, dialog opens on click
- **Complexity**: Medium

---

## Phase 4: Testing

### 4.1 Backend Unit Tests
- **Description**: Test ProyeccionExportService data transformation
- **File**: `backend/tests/Unit/Services/ProyeccionExportServiceTest.php`
- **Tests**:
  - Cantidad calculation for tipo 'C' (uses cargos)
  - Cantidad calculation for tipo 'H' (uses horar)
  - Turno returns sigla not nombre
  - Orden is sequential
  - Destino 2027 is always null
- **Complexity**: Medium

### 4.2 Backend Feature Test
- **Description**: Test export endpoint with auth and filters
- **File**: `backend/tests/Feature/ProyeccionExportTest.php`
- **Tests**:
  - 200 response with valid auth
  - 401 without auth
  - Returns valid Excel content-type
  - Filters work (motivo, id_institucion, id_cargo)
  - 413 when exceeding 10,000 records
  - Empty results return valid Excel with headers
- **Complexity**: Medium

### 4.3 Frontend E2E Test (Optional)
- **Description**: Test export dialog workflow
- **Tool**: Playwright
- **Tests**:
  - Export button opens dialog
  - Filter selections work
  - Export triggers download
- **Complexity**: Low (optional)

---

## Dependency Graph

```
1.1 → 2.1 → 2.2 → 2.3 → 2.4
                           ↓
                        3.1 → 3.2 → 3.3
                                    ↓
                                 4.1, 4.2
```

## Summary

| Phase | Tasks | Complexity |
|-------|-------|------------|
| Infrastructure | 2 | Low |
| Backend | 4 | Medium |
| Frontend | 3 | Medium-High |
| Testing | 3 | Medium |
| **Total** | **12** | |

## Estimated Effort
- Phase 1: ~15 min
- Phase 2: ~2 hours
- Phase 3: ~2 hours
- Phase 4: ~1.5 hours
- **Total**: ~6 hours
