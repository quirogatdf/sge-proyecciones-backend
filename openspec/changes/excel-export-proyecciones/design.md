# Design: Excel Export para Proyecciones

## Technical Approach

Implement Excel export functionality using Maatwebsite/Excel library for Laravel backend and a standalone dialog component in Angular frontend. The export will generate `.xlsx` files with two formats (Continuidad/Creacion) based on user-selected filters. Backend will handle data transformation, validation, and streaming; frontend will provide filter UI and trigger download.

**Memory Management**: Use chunked reading (`->chunk(1000)`) in export class to avoid loading all records into memory. Enforce 10,000 record limit in service before passing to export. Stream response using `StreamedResponse` to prevent PHP memory exhaustion.

## Architecture Decisions

### Decision: Controller Separation

**Choice**: Create new `ProyeccionExportController` instead of adding to existing `ProyeccionController`
**Alternatives considered**: Add export method to existing controller
**Rationale**: Single Responsibility Principle - export logic is distinct from CRUD operations. Existing controller already has 6 methods; adding export would increase complexity. Separate controller allows independent testing and maintenance.

### Decision: Service Layer for Data Transformation

**Choice**: Create `ProyeccionExportService` for data transformation logic
**Alternatives considered**: Put transformation logic directly in Export class or Controller
**Rationale**: Centralizes business rules (Cantidad calculation, field mapping) for reuse and testability. Export class focuses on Excel formatting, controller handles HTTP concerns.

### Decision: Export Class Pattern

**Choice**: Single `ProyeccionExport` class implementing `FromCollection` with dynamic format switching
**Alternatives considered**: Separate classes for Continuidad and Creacion formats
**Rationale**: Both formats share same column structure (per spec section 3.1). Only difference is filter logic (motivo). Single class reduces duplication; format parameter controls query filtering.

### Decision: Frontend Dialog Integration

**Choice**: Standalone `ExportDialogComponent` within proyecciones feature, imported by `ProyeccionesListComponent`
**Alternatives considered**: Embed dialog logic directly in list component
**Rationale**: Follows existing pattern (SearchableSelectComponent is standalone). Keeps list component focused on table logic. Dialog can be reused if export needed elsewhere.

### Decision: State Management Approach

**Choice**: Signals for dialog state (open/close, filter selections)
**Alternatives considered**: Observables or component state
**Rationale**: Matches existing codebase pattern (signals used throughout). Simpler mental model for boolean state and form values. Angular 21 signals are stable.

### Decision: Route Placement

**Choice**: Add new route `Route::get('proyecciones/export', ...)` inside auth:sanctum middleware group
**Alternatives considered**: Create separate route group or use apiResource with custom action
**Rationale**: Follows existing pattern for custom proyecciones routes (stats, byNivel). Keeps export route discoverable alongside other proyecciones endpoints.

## Data Flow

### Backend Flow

```
┌─────────────────┐    ┌─────────────────────┐    ┌──────────────────────┐
│ ProyeccionExport │───→│ ProyeccionExportService│───→│ Proyeccion (Eloquent)│
│ Controller       │    │                      │    │                      │
└─────────────────┘    └──────────────────────┘    └──────────────────────┘
         │                        │
         │                        ↓
         │               ┌──────────────────────┐
         │               │ ProyeccionExport      │
         │               │ (Maatwebsite/Excel)   │
         │               └──────────────────────┘
         │                        │
         ↓                        ↓
┌─────────────────┐    ┌──────────────────────┐
│ StreamedResponse│    │ Excel File (xlsx)     │
└─────────────────┘    └──────────────────────┘
```

### Frontend Flow

```
User clicks "Exportar Excel"
        ↓
ExportDialogComponent opens (signal: isOpen = true)
        ↓
User selects filters (motivo, institucion, cargo)
        ↓
User clicks "Exportar"
        ↓
Dialog closes, loading signal = true
        ↓
ProyeccionesService.exportExcel(params) called
        ↓
HttpClient GET /api/proyecciones/export?...
        ↓
Backend returns Blob (application/vnd.openxmlformats...)
        ↓
Browser downloads file (saveAs from file-saver or native anchor)
        ↓
Success toast shown
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `backend/composer.json` | Modify | Add `maatwebsite/excel` dependency |
| `backend/app/Http/Controllers/Api/ProyeccionExportController.php` | Create | Export endpoint with validation |
| `backend/app/Services/ProyeccionExportService.php` | Create | Data transformation and query logic |
| `backend/app/Exports/ProyeccionExport.php` | Create | Excel export class implementing FromCollection |
| `backend/routes/api.php` | Modify | Add export route |
| `frontend/src/app/features/proyecciones/export-dialog.component.ts` | Create | Filter dialog component |
| `frontend/src/app/core/services/proyecciones.service.ts` | Modify | Add exportExcel method |
| `frontend/src/app/features/proyecciones/proyecciones-list.component.ts` | Modify | Add export button and dialog integration |

## Interfaces / Contracts

### Backend API Contract

```php
// Route: GET /api/proyecciones/export
// Query Parameters:
//   - motivo: 'Continuidad' | 'Creacion' (default: 'Continuidad')
//   - id_nivel: integer (optional)
//   - id_institucion: integer (optional)
//   - id_cargo: integer (optional)

// Response: 200 OK
// Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet
// Content-Disposition: attachment; filename="proyecciones_{motivo}_{timestamp}.xlsx"

// Response: 413 Payload Too Large
// { "message": "Demasiados registros para exportar. Use filtros más específicos." }
```

### Frontend Service Interface

```typescript
// In ProyeccionesService
exportExcel(params: {
  motivo?: 'Continuidad' | 'Creacion';
  id_nivel?: number;
  id_institucion?: number;
  id_cargo?: number;
}): Observable<Blob>
```

### Export Dialog Interface

```typescript
interface ExportFilters {
  motivo: 'Continuidad' | 'Creacion';
  id_institucion: number | null;
  id_cargo: number | null;
}
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | ProyeccionExportService data transformation | Mock Proyeccion models, verify Cantidad calculation, field mapping |
| Unit | ProyeccionExport Excel generation | Use Maatwebsite/Excel testing helpers, verify columns and formatting |
| Feature | Export endpoint with filters | HTTP tests with Sanctum auth, verify response headers and content |
| Feature | Large dataset limit (10,000) | Factory with 10,001 records, verify 413 response |
| E2E | Dialog filter selection → download | Playwright: click export, select filters, verify file download |

## Migration / Rollout

No database migration required. Feature flag not needed - export is additive functionality.

**Rollback Plan** (from proposal):
1. `composer remove maatwebsite/excel`
2. Revert route changes
3. Delete new files (Controller, Service, Export Class, Dialog Component)
4. Revert changes to existing files

## Open Questions

- [ ] Should we use `file-saver` npm package for browser download or native anchor element?
- [ ] Should export endpoint return empty Excel with headers when no records found, or 404?
- [ ] Should we cache institution/cargo lists in frontend or fetch fresh each dialog open?
