# Role Authorization Specification

## Purpose

Enforce two-tier authorization: **admin** SHALL have full CRUD on all 7 resources; **guest** SHALL be read-only (index/show). Unauthorized requests return 403.

## Requirements

### Requirement: Admin has full CRUD on all resources

| Action | Endpoint | Expected |
|--------|----------|----------|
| Create | POST `/*` | 201 |
| Read (list) | GET `/*` | 200 |
| Read (show) | GET `/*/*` | 200 |
| Update | PUT/PATCH `/*/*` | 200 |
| Delete | DELETE `/*/*` | 200/204 |

- GIVEN a user with role `admin`
- WHEN they perform any CRUD action on any resource
- THEN the response MUST be the expected HTTP status

### Requirement: Guest is read-only on all resources

| Action | Endpoint | Expected |
|--------|----------|----------|
| Create | POST `/*` | 403 |
| Read (list) | GET `/*` | 200 |
| Read (show) | GET `/*/*` | 200 |
| Update | PUT/PATCH `/*/*` | 403 |
| Delete | DELETE `/*/*` | 403 |

- GIVEN a user with role `guest`
- WHEN they perform the listed action on any resource
- THEN the response MUST be the expected HTTP status

### Requirement: Form Requests authorize via Policies

The system MUST authorize every Form Request through a Policy. The `BasePolicyTrait::before()` method SHALL govern the gate.

#### Scenario: Policy routes correctly

- GIVEN a Form Request calling `authorize()`
- WHEN the Policy `before()` evaluates the user's role
- THEN admin MUST pass any action
- AND guest MUST pass only `viewAny` / `view`

### Requirement: Unauthorized requests return 403

The system MUST return 403 with a JSON error body when a user lacks permission.

#### Scenario: Forbidden response format

- GIVEN an authenticated guest user
- WHEN they attempt any mutating action
- THEN the response MUST be 403
- AND the body MUST contain an `error` or `message` field
