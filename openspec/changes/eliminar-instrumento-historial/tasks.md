# Tasks: Eliminar instrumento del historial

## Fase 1: Infraestructura (backend)

- [x] **1.1** Agregar método `destroy(Proyeccion $proyeccion, ProyeccionInstrumento $instrumento)` en `ProyeccionInstrumentoController`:
  - [x] 404 si el instrumento no pertenece a la proyección.
  - [x] 409 si es el último instrumento.
  - [x] 204 + delete en caso exitoso.
- [x] **1.2** Registrar ruta `DELETE proyecciones/{proyeccion}/instrumentos/{instrumento}`.

## Fase 2: Implementación (frontend)

- [x] **2.1** Agregar `deleteInstrumento(proyeccionId, instrumentoId)` en `ProyeccionesService`.
- [x] **2.2** Botón "🗑 Eliminar" en tabla de historial del listado (`proyecciones-list.component.ts`):
  - [x] Confirmación con `AlertService.confirm()`.
  - [x] `[disabled]` cuando `historialInstrumentos().length <= 1`.
  - [x] Recargar historial + tabla tras éxito; mostrar error del backend si falla.
  - [x] Estilos `.btn-delete` y `.actions-cell`.
- [x] **2.3** Botón "🗑 Eliminar" en tabla de historial del detalle (`proyeccion-detail.component.ts`):
  - [x] Inyectar `AlertService`.
  - [x] Confirmación + disabled cuando `instrumentos().length <= 1`.
  - [x] Recargar historial tras éxito; mostrar error del backend si falla.
  - [x] Fusionar PDF + Editar + Eliminar en una sola celda de acciones y corregir `colspan` (12 → 11).

## Fase 3: Testing

- [x] **3.1** Backend (`ProyeccionInstrumentoApiTest`):
  - [x] destroy elimina un instrumento.
  - [x] destroy 404 si instrumento ajeno.
  - [x] destroy 409 si es el único.
  - [x] destroy 404 si no existe.
- [x] **3.2** Frontend (`proyecciones.service.integration.spec.ts`): deleteInstrumento hace DELETE y maneja 204.

## Estado

- Backend: 149/149 tests ✅
- Frontend: `ngc` EXIT 0 ✅, spec service 4/4 ✅
- Pendiente: commit + push (requiere OK del usuario).