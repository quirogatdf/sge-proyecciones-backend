# Spec: Eliminar instrumento del historial

## Requirements

- **REQ-DELETE-1**: El backend DEBE exponer `DELETE /api/proyecciones/{proyeccion}/instrumentos/{instrumento}` autenticado.
- **REQ-DELETE-2**: El backend DEBE devolver 404 si el instrumento no existe o no pertenece a la proyección indicada.
- **REQ-DELETE-3**: El backend DEBE devolver 409 (conflict) y NO borrar si el instrumento es el último de la proyección.
- **REQ-DELETE-4**: El backend DEBE devolver 204 al eliminar correctamente.
- **REQ-DELETE-5**: El frontend DEBE mostrar un diálogo de confirmación antes de eliminar.
- **REQ-DELETE-6**: El frontend DEBE deshabilitar el botón Eliminar cuando la proyección tiene un solo instrumento.
- **REQ-DELETE-7**: El frontend DEBE recargar el historial y el listado tras eliminar.
- **REQ-DELETE-8**: El frontend DEBE mostrar el mensaje de error del backend si falla (ej. 409).

## Scenarios

### SCENARIO-1: Eliminación exitosa

**Given** una proyección con 2 instrumentos (2025 y 2026)
**When** el usuario confirma eliminar el instrumento 2026
**Then** el backend elimina el registro
**And** responde 204
**And** el frontend recarga el historial mostrando solo 2025

### SCENARIO-2: Último instrumento protegido

**Given** una proyección con 1 solo instrumento (2025)
**When** se intenta eliminar ese instrumento
**Then** el backend responde 409 con mensaje "No se puede eliminar el último instrumento de la proyección."
**And** el registro NO se elimina
**And** el frontend muestra el error y/o el botón está deshabilitado

### SCENARIO-3: Instrumento ajeno a la proyección

**Given** el instrumento pertenece a otra proyección
**When** se intenta eliminar vía la URL de una proyección distinta
**Then** el backend responde 404
**And** el registro NO se elimina

### SCENARIO-4: Cancelación de confirmación

**Given** el usuario abre el diálogo de confirmación
**When** el usuario cancela
**Then** no se realiza ninguna llamada DELETE
**And** el historial no cambia