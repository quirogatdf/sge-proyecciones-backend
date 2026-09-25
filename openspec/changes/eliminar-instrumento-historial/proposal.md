# Eliminar instrumento del historial

## Intent

Permitir eliminar un snapshot del historial de instrumentos de una proyección, tanto desde el listado (modo edición) como desde el detalle de la proyección.

## Problem Statement

El historial de instrumentos (`proyeccion_instrumentos`) soporta crear (store) y editar (update), pero no eliminar (destroy). No existe endpoint DELETE ni botón en la UI.

## Scope

- **Backend** (Laravel): agregar `destroy` en `ProyeccionInstrumentoController` + ruta DELETE.
- **Frontend** (Angular): agregar `deleteInstrumento` en `ProyeccionesService` y botón "Eliminar" en ambas tablas de historial (listado y detalle), con confirmación.

## Out of Scope

- Borrar la proyección completa (ya existe `ProyeccionController::destroy` con cascade).
- Soft deletes / papelera de reciclaje.

## Approach

1. Endpoint `DELETE /api/proyecciones/{proyeccion}/instrumentos/{instrumento}`.
2. Validación de pertenencia del instrumento a la proyección (404 si no).
3. Regla de negocio: no permitir eliminar el ÚLTIMO instrumento de una proyección (409) — el listado y stats se arman con JOIN contra `proyeccion_instrumentos`; una proyección sin snapshots quedaría huérfana e invisible. Para eliminar la plaza completa se borra la proyección.
4. Frontend: botón con `AlertService.confirm()` (SweetAlert2); deshabilitado cuando queda un solo instrumento.

## Rollback Plan

- Backend: revertir el método `destroy` y la ruta DELETE (cambio aditivo; sin migraciones ni cambios destructivos de datos).
- Frontend: revertir botones y método agregados.
- No hay migraciones nuevas ni cambios de datos.

## Affected Modules

- `backend/app/Http/Controllers/Api/ProyeccionInstrumentoController.php`
- `backend/routes/api.php`
- `backend/tests/Feature/ProyeccionInstrumentoApiTest.php`
- `frontend/src/app/core/services/proyecciones.service.ts` + `.integration.spec.ts`
- `frontend/src/app/features/proyecciones/proyecciones-list.component.ts`
- `frontend/src/app/features/proyecciones/proyeccion-detail.component.ts`

## Risks

- Bajo: el bloqueo 409 del último instrumento es una decisión de negocio; si el usuario quiere dejar la proyección sin historial habría que revisar el modelado (JOIN en listado/stats).