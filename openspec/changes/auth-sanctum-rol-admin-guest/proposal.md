# Proposal: Auth con Sanctum + Roles Admin/Guest

## Intent

Proteger los 7 recursos API (37 rutas) actualmente públicos. Sanctum token-based, 6h expiry, dos roles: **admin** (CRUD) y **guest** (read-only).

## Scope

### In Scope
- Sanctum instalado (token API, no SPA); columna `role` enum (`admin`, `guest`)
- AuthController (login/logout/me); tokens con `expiresAt(now()->addHours(6))`
- BasePolicyTrait: admin pasa todo, guest solo index/show
- Middleware `auth:sanctum` en todas las rutas API + Form Requests autorizan via Policy
- UserSeeder (admin + guest default); `sanctum:prune-expired` en schedule
- Tests: AuthTest nuevo + tests existentes con `actingAs()`

### Out of Scope
Registro, password reset, email verification, CRUD roles, OAuth, SPA cookies — todos deferidos.

## Capabilities

### New
- `api-auth`: Sanctum login/logout/me con tokens de 6h.
- `role-authorization`: Admin = CRUD, Guest = read-only (index/show).

### Modified
None.

## Approach

1. `composer require laravel/sanctum` + publicar config/migration.
2. `HasApiTokens` en User, columna `role` enum, helper `isAdmin()`.
3. `AuthController::login()` → `user->createToken(..., ['expiresAt' => now()->addHours(6)])`.
4. `BasePolicyTrait::before()`: admin → true, guest → solo `viewAny`/`view`.
5. `Route::middleware('auth:sanctum')->group(...)` en `routes/api.php`.
6. `UserSeeder`: admin@ / guest@ + `UserFactory::new()->admin()->create()`.
7. `$schedule->command('sanctum:prune-expired --hours=6')->daily()`.
8. Tests: `$this->actingAs(User::factory()->admin()->create())` en setUp.

## Affected Areas

| Area | Cambio |
|------|--------|
| `composer.json` | +`laravel/sanctum` |
| `app/Models/User.php` | +`HasApiTokens`, +`role` fillable, +`isAdmin()` |
| `app/Enums/RolUsuario.php` | **Nuevo** |
| `app/Http/Controllers/Api/AuthController.php` | **Nuevo** |
| `app/Policies/BasePolicyTrait.php` | **Nuevo** |
| `app/Http/Requests/*.php` (14) | `authorize()` via Policy |
| `routes/api.php` | Grupo `auth:sanctum` |
| `database/migrations/*_add_role_to_users.php` | **Nueva** |
| `database/seeders/UserSeeder.php` | **Nuevo** |
| `database/factories/UserFactory.php` | Estados admin/guest |
| `tests/Feature/Api/AuthTest.php` | **Nuevo** |
| `tests/Feature/Api/*Test.php` | +`actingAs()` |

## Risks

| Riesgo | Prob. | Mitigación |
|--------|-------|------------|
| Tests existentes fallan | Alta | `actingAs()` en setUp |
| Frontend ignora 401 | Media | Documentar respuesta |
| Token leak | Baja | Sanctum hashea; no loguear |

## Rollback Plan

1. `composer remove laravel/sanctum`
2. Revert migration, routes, composer.json/lock
3. Eliminar archivos nuevos (AuthController, Policies, Enums, Seeder)

## Dependencies

`laravel/sanctum` (first-party Laravel).

## Success Criteria

- [ ] Admin CRUD completo en 7 recursos
- [ ] Guest solo index/show en 7 recursos
- [ ] Token expira a las 6h → devuelve 401
- [ ] Tests existentes pasan con auth
