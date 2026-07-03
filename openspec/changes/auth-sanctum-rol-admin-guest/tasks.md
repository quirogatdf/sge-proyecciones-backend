# Tasks: Auth con Sanctum + Roles Admin/Guest

## Phase 1: Infrastructure

- [x] 1.1 `composer require laravel/sanctum`
- [x] 1.2 `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` (config + migration)
- [x] 1.3 `php artisan migrate` (personal_access_tokens table)
- [x] 1.4 Crear `database/migrations/*_add_role_to_users_table.php` — columna `role` enum: admin, guest, default guest
- [x] 1.5 Crear `app/Enums/RolUsuario.php` — BackedEnum string (Admin, Guest)

## Phase 2: Core

- [x] 2.1 Modificar `app/Models/User.php` — +HasApiTokens, +`role` fillable, +`isAdmin()`, casteo a RolUsuario
- [x] 2.2 Crear `app/Policies/BasePolicyTrait.php` — `before()`: admin → true, guest → solo viewAny/view
- [x] 2.3 Registrar policies en `AppServiceProvider` via `Gate::guessPolicyNamesUsing` o `Gate::define`
- [x] 2.4 Crear `app/Http/Controllers/Api/AuthController.php` — login() con expiresAt(+6h), logout(), me()

## Phase 3: Integration

- [x] 3.1 Modificar `routes/api.php` — login/logout/me sin middleware; apiResources dentro de `auth:sanctum`
- [x] 3.2 Actualizar 14 Form Requests — `authorize()` delegando a Policy (admin vs guest)
- [x] 3.3 Agregar `sanctum:prune-expired` a `routes/console.php` (Schedule)

## Phase 4: Seeders & Factories

- [x] 4.1 Modificar `database/factories/UserFactory.php` — +`admin()` state (+role admin), +`guest()` state (+role guest)
- [x] 4.2 Crear `database/seeders/UserSeeder.php` — admin: admin@sge.gob.ar / guest: guest@sge.gob.ar
- [x] 4.3 Modificar `database/seeders/DatabaseSeeder.php` — llamar a UserSeeder

## Phase 5: Testing

- [x] 5.1 Crear `tests/Feature/Api/AuthTest.php` — login OK, login inválido, me sin token, me con token, logout revoca, token expirado
- [x] 5.2 Actualizar `NivelApiTest.php` — +actingAs(admin) en setUp, +test guest 403 en POST/PUT/DELETE
- [x] 5.3 Actualizar `CargoApiTest.php`, `ResolucionApiTest.php`, `ProyeccionControllerTest.php` — +actingAs(admin) en setUp
- [x] 5.4 `composer test` — verificar que todos los tests pasan

## Phase 6: Cleanup

- [x] 6.1 Verificar que `composer test` pasa completo — 98 passed, 0 failures, 0 risky ✅
- [ ] 6.2 Commit: `feat: add Sanctum auth with admin/guest roles and 6h token expiry`
