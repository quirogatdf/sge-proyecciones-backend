# Proposal: Excel Export para Proyecciones

## Intent

Agregar funcionalidad de exportación a Excel para las proyecciones, permitiendo generar archivos en formato Continuidad y Creación con filtros por institución y cargo. Los usuarios necesitan exportar datos para análisis externo, reportes a superiores y procesos administrativos que requieren documentos en formato Excel.

## Scope

### In Scope
- Endpoint API para exportación de proyecciones a Excel
- Generación de archivos Excel con formato específico (Continuidad/Creación)
- Dialog/modal de filtros en frontend para seleccionar institución y cargo
- Manejo de campos calculados (Cantidad = max(cargos, horar))
- Soporte para tipos de cargo 'C' (Contratado) y 'H' (Honorario)
- Endpoint con autenticación Sanctum

### Out of Scope
- Exportación a otros formatos (PDF, CSV)
- Exportación masiva sin límites
- Programación de exportaciones automáticas
- Historial de exportaciones

## Capabilities

### New Capabilities
- `excel-export`: Generación de archivos Excel con formato Continuidad/Creación
- `export-filters`: Dialog de selección de filtros para exportación

### Modified Capabilities
None.

## Approach

1. **Backend**: Instalar `maatwebsite/excel` para generación de Excel
2. **Export Controller**: Crear `ProyeccionExportController` con endpoint `GET /api/proyecciones/export`
3. **Export Service**: Implementar `ProyeccionExportService` con lógica de transformación de datos
4. **Excel Export Class**: Crear `ProyeccionExport` que implemente `FromCollection` con formato específico
5. **Frontend Dialog**: Crear `ExportDialogComponent` con selects de institución y cargo
6. **API Service**: Agregar método `exportExcel()` en `ProyeccionesService`
7. **UI Integration**: Agregar botón de exportación en `ProyeccionesListComponent`

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `backend/composer.json` | Modified | +`maatwebsite/excel` |
| `backend/app/Http/Controllers/Api/ProyeccionExportController.php` | New | Endpoint de exportación |
| `backend/app/Services/ProyeccionExportService.php` | New | Lógica de transformación |
| `backend/app/Exports/ProyeccionExport.php` | New | Clase de exportación Excel |
| `backend/routes/api.php` | Modified | Nueva ruta de exportación |
| `frontend/src/app/features/proyecciones/export-dialog.component.ts` | New | Dialog de filtros |
| `frontend/src/app/core/services/proyecciones.service.ts` | Modified | Método exportExcel |
| `frontend/src/app/features/proyecciones/proyecciones-list.component.ts` | Modified | Botón de exportación |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Maatwebsite/excel incompatible con Laravel 13 | Low | Verificar compatibilidad antes de instalar |
| Archivos Excel muy grandes causen timeout | Medium | Implementar límites y paginación |
| Campos calculados generen inconsistencias | Low | Tests unitarios para lógica de Cantidad |

## Rollback Plan

1. `composer remove maatwebsite/excel`
2. Revertir cambios en `routes/api.php`
3. Eliminar archivos nuevos (Controller, Service, Export Class, Dialog Component)
4. Revertir cambios en `proyecciones-list.component.ts` y `proyecciones.service.ts`

## Dependencies

- `maatwebsite/excel` (primera parte, Laravel compatible)
- Autenticación Sanctum existente

## Success Criteria

- [ ] Endpoint `GET /api/proyecciones/export` genera archivo Excel válido
- [ ] Formato Continuidad incluye todas las columnas requeridas
- [ ] Formato Creación incluye todas las columnas requeridas
- [ ] Filtros por institución y cargo funcionan correctamente
- [ ] Campo Cantidad calcula correctamente max(cargos, horar)
- [ ] Turno muestra sigla en lugar de nombre completo
- [ ] Destino 2027 siempre es null
- [ ] Dialog de filtros se muestra al hacer clic en exportar
- [ ] Archivo Excel se descarga automáticamente en el navegador
