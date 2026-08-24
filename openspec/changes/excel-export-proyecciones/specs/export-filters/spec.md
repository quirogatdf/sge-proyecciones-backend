# Export Filters Specification

## Purpose

Dialog/modal de frontend para que el usuario seleccione filtros antes de exportar proyecciones a Excel. Se muestra al hacer clic en el botón de exportación.

## Requirements

### Requirement: Export Button Visibility

An "Exportar Excel" button MUST be visible in the ProyeccionesListComponent. The button SHALL be placed in the toolbar/header area, alongside existing action buttons.

#### Scenario: Export button visible on list page

- GIVEN the user is on the ProyeccionesListComponent
- WHEN the component renders
- THEN an "Exportar Excel" button SHALL be visible

---

### Requirement: Filter Dialog Display

When the user clicks the export button, a dialog/modal SHALL open with filter options. The dialog MUST NOT immediately trigger the export — it SHALL wait for explicit confirmation.

| Control              | Type            | Options                                      |
|----------------------|-----------------|----------------------------------------------|
| Institución          | Select dropdown | "Todas" + list of instituciones from API      |
| Cargo                | Select dropdown | "Todos" + list of cargos from API             |
| Tipo de exportación  | Radio buttons   | "Continuidad" (default), "Creación"          |
| Confirmar            | Primary button  | Triggers export                              |
| Cancelar             | Secondary button| Closes dialog without action                 |

The dialog SHALL have a title: "Exportar Proyecciones a Excel".

#### Scenario: Clicking export button opens dialog

- GIVEN the user is on the ProyeccionesListComponent
- WHEN the user clicks the "Exportar Excel" button
- THEN a dialog SHALL open with filter options
- AND NO export SHALL be triggered yet

#### Scenario: Dialog defaults to Continuidad

- GIVEN the filter dialog is open
- WHEN the dialog renders
- THEN "Continuidad" radio button SHALL be selected by default
- AND both select dropdowns SHALL default to "Todas"/"Todos"

---

### Requirement: Institution Dropdown Population

The institución dropdown SHALL be populated from the existing instituciones API endpoint. The list MUST include a "Todas" option as the first item with a null/empty value.

#### Scenario: Institution dropdown loads from API

- GIVEN the filter dialog opens
- WHEN the instituciones data loads
- THEN the dropdown SHALL show "Todas" as first option
- AND all available instituciones SHALL appear below it
- AND each option SHALL display the institucion name

#### Scenario: Institution dropdown handles API error

- GIVEN the filter dialog opens
- WHEN the instituciones API call fails
- THEN the dropdown SHALL show "Todas" as the only option
- AND a warning message MAY be shown

---

### Requirement: Cargo Dropdown Population

The cargo dropdown SHALL be populated from the existing cargos API endpoint. The list MUST include a "Todos" option as the first item with a null/empty value.

#### Scenario: Cargo dropdown loads from API

- GIVEN the filter dialog opens
- WHEN the cargos data loads
- THEN the dropdown SHALL show "Todos" as first option
- AND all available cargos SHALL appear below it
- AND each option SHALL display the cargo name

---

### Requirement: Export Type Selection

The user MUST select one of two export types via radio buttons: "Continuidad" or "Creación". Only one option MUST be selected at all times (no deselect).

| Value        | Enum value sent to API |
|--------------|------------------------|
| Continuidad  | `Continuidad`          |
| Creación     | `Creación`             |

#### Scenario: User selects Creación

- GIVEN the filter dialog is open with Continuidad selected
- WHEN the user clicks the "Creación" radio button
- THEN Continuidad SHALL be deselected
- AND Creación SHALL be selected

---

### Requirement: Confirm Export Action

When the user clicks "Confirmar", the system SHALL:
1. Close the dialog
2. Call `GET /api/proyecciones/export` with the selected filters as query params
3. Trigger a browser file download of the returned `.xlsx` file
4. Show a success toast/notification after download initiates

The export request SHALL show a loading indicator while in progress.

#### Scenario: Successful export triggers download

- GIVEN the user selected Institucion X, Cargo Y, and Continuidad
- WHEN the user clicks "Confirmar"
- THEN the dialog SHALL close
- AND a loading spinner SHALL appear
- AND GET `/api/proyecciones/export?id_institucion=X&id_cargo=Y&motivo=Continuidad` SHALL be called
- AND the browser SHALL download the returned file
- AND a success toast SHALL appear

#### Scenario: Export with no filters exports all

- GIVEN the user left both dropdowns on "Todas"/"Todos"
- WHEN the user clicks "Confirmar"
- THEN GET `/api/proyecciones/export?motivo=Continuidad` SHALL be called (no institucion/cargo params)

#### Scenario: Export failure shows error toast

- GIVEN the user clicks "Confirmar"
- WHEN the API returns an error (4xx or 5xx)
- THEN the loading indicator SHALL disappear
- AND an error toast SHALL appear with the error message
- AND the dialog MAY remain closed

---

### Requirement: Cancel Export Action

When the user clicks "Cancelar" or clicks outside the dialog, the dialog SHALL close without triggering any export request. No API call SHALL be made.

#### Scenario: Cancel closes dialog without export

- GIVEN the filter dialog is open
- WHEN the user clicks "Cancelar"
- THEN the dialog SHALL close
- AND NO API call SHALL be made

---

### Requirement: Large Result Warning

If the user selects filters that may return a large dataset, the system SHOULD display a warning before export. This is a soft recommendation — the system MAY skip this if the backend handles the 413 response gracefully.

#### Scenario: Backend returns 413 for too many records

- GIVEN the user's filters match 15,000+ records
- WHEN the export request is sent
- THEN the backend SHALL return 413
- AND the frontend SHALL display the error message: "Demasiados registros para exportar. Use filtros más específicos."
