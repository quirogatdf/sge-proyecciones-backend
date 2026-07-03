# Design: Auth con Sanctum + Roles Admin/Guest

## Technical Approach

Sanctum token-based API auth con role column enum (admin/guest). Los tokens expiran a las 6h. Un `BasePolicyTrait` centraliza la lógica de autorización: admin pasa todo, guest solo `viewAny/view`. Middleware `auth:sanctum` protege las 37 rutas API existentes.

## Architecture Decisions

### Token-based API vs SPA Cookies

| Opción | Tradeoff | Decisión |
|--------|----------|----------|
| **Token API (Personal Access Tokens)** | Stateless, frontend desacoplado (React/RN/curl), sin CSRF, expiración explícita | ✅ Elegido |
| SPA Cookies (stateful) | Requiere `web` middleware + CSRF + mismo dominio, acopla frontend al backend | ❌ Descartado |

**Rationale**: El frontend es Angular standalone (proyecto separado), no Laravel Inertia/Blade. Tokens API eliminan dependencia de cookies y CSRF.

### Role column vs Spatie Laravel Permissions

| Opción | Tradeoff | Decisión |
|--------|----------|----------|
| **Columna `role` enum** | Simple, 2 roles fijos, sin overhead de tablas extra, migración trivial | ✅ Elegido |
| Spatie Permission | Ideal para RBAC complejo (>3 roles, permisos granulares), pero agrega 5 tablas + caching | ❌ Descartado |

**Rationale**: Solo hay dos roles con comportamientos claros (CRUD vs read-only). Spatie es overkill para este alcance.

### BasePolicyTrait vs Policies individuales por recurso

| Opción | Tradeoff | Decisión |
|--------|----------|----------|
| **BasePolicyTrait** | `before()` centralizado decide según rol; 0 líneas por recurso; los 7 recursos heredan automáticamente | ✅ Elegido |
| Policy por recurso (7 clases) | DRY-violation, 7 archivos repitiendo misma lógica, mantenimiento innecesario | ❌ Descartado |

**Rationale**: La lógica es idéntica para todos los recursos (admin=true, guest=viewAny/view). No tiene sentido duplicarla.

### Enum `RolUsuario` vs string

| Opción | Tradeoff | Decisión |
|--------|----------|----------|
| **Enum `RolUsuario` (backed string)** | Type-safe, autocompletable, sigue el patrón existente (`EstadoProyeccion`, `MotivoProyeccion`), validación en DB y código | ✅ Elegido |
| String literal | Propenso a typos, sin autocompletado, inconsistente con el codebase actual | ❌ Descartado |

**Rationale**: El proyecto ya usa `BackedEnum` de PHP 8.1 para estados y motivos. `RolUsuario` sigue exactamente ese patrón.

### AuthController vs Fortify/Breeze

| Opción | Tradeoff | Decisión |
|--------|----------|----------|
| **AuthController manual** | 3 métodos (login/logout/me), control total sobre respuesta JSON, sin dependencias extra | ✅ Elegido |
| Laravel Fortify | Trae registro, password reset, 2FA — todo out of scope | ❌ Descartado |
| Laravel Breeze | Blade + Vite, pensado para SPA con frontend monolítico | ❌ Descartado |

**Rationale**: Necesitamos exactamente 3 endpoints JSON. Fortify/Breeze agregan features que no vamos a usar y habría que overridear.

## Data Flow

```
Client ──POST /api/login──→ AuthController::login()
                                │
                        Valida credentials
                        email + password
                                │
                    User::whereEmail($email)->first()
                    Hash::check($password, $user->password)
                                │
                        $user->createToken('auth', expiresAt:+6h)
                                │
                        Responde: { token, user }

Client ──GET /api/me──→ AuthController::me()
  Header: Authorization: Bearer {token}
                          │
                  auth:sanctum middleware
                  verifica token en DB
                  check expires_at
                          │
                  Responde: { user }

Client ──POST /api/logout──→ AuthController::logout()
  Header: Authorization: Bearer {token}
                          │
                  Revoca token actual
                          │
                  Responde: 200 { message }

Client ──GET /api/niveles──→ NivelController::index()
  Header: Authorization: Bearer {token}
                          │
                  BasePolicyTrait::before()
                  ├── admin → true
                  └── guest → solo viewAny/view autorizados
```

## File Changes

| File | Acción | Descripción |
|------|--------|-------------|
| `composer.json` | Modificar | +`laravel/sanctum` |
| `config/sanctum.php` | Crear | `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` |
| `database/migrations/*_create_personal_access_tokens_table.php` | Crear | Migración de Sanctum |
| `database/migrations/*_add_role_to_users_table.php` | Crear | Columna `role` enum: admin, guest |
| `app/Enums/RolUsuario.php` | Crear | `BackedEnum` string: Admin, Guest |
| `app/Models/User.php` | Modificar | +`HasApiTokens`, +`role` fillable, +`isAdmin()`, casteo a `RolUsuario` |
| `app/Http/Controllers/Api/AuthController.php` | Crear | login(), logout(), me() |
| `app/Policies/BasePolicyTrait.php` | Crear | `before()` con lógica admin/guest |
| `app/Providers/AppServiceProvider.php` | Modificar | Registrar policies con `Gate::guessPolicyNamesUsing` |
| `routes/api.php` | Modificar | Envolver rutas en `Route::middleware('auth:sanctum')->group(...)` |
| `bootstrap/app.php` | Modificar | Agregar middleware `EnsureFrontendRequestsAreStateful` si aplica |
| `database/factories/UserFactory.php` | Modificar | +`admin()` state, +`guest()` state |
| `database/seeders/UserSeeder.php` | Crear | Admin: admin@sistema.test / Guest: guest@sistema.test |
| `database/seeders/DatabaseSeeder.php` | Modificar | Llamar a `UserSeeder` |
| `routes/console.php` | Modificar | `sanctum:prune-expired` daily |
| `tests/Feature/Api/AuthTest.php` | Crear | Login, logout, me, token expiry |
| `tests/Feature/NivelApiTest.php` | Modificar | +`actingAs()` en setUp + tests de autorización guest |
| `tests/Feature/CargoApiTest.php` | Modificar | +`actingAs()` |
| `tests/Feature/ResolucionApiTest.php` | Modificar | +`actingAs()` |

## Interfaces

### `RolUsuario` Enum

```php
<?php declare(strict_types=1);
namespace App\Enums;
enum RolUsuario: string {
    case Admin = 'admin';
    case Guest = 'guest';
}
```

### AuthController responses

| Endpoint | Método | Request | Response (200) | Response (401) |
|----------|--------|---------|----------------|----------------|
| `POST /api/login` | `login(LoginRequest)` | `{ email, password }` | `{ token, user }` | `{ message }` (422/401) |
| `GET /api/me` | `me()` | — | `{ user }` | `{ message }` |
| `POST /api/logout` | `logout()` | — | `{ message }` | `{ message }` |

### Token expiration

```php
$token = $user->createToken('auth-token')->plainTextToken;
$user->tokens()->latest()->first()->forceFill([
    'expires_at' => now()->addHours(6),
])->save();
```

## Testing Strategy

| Capa | Qué testear | Approach |
|------|-------------|----------|
| Unit | `RolUsuario` enum | Casos existen, valores correctos (patrón existente) |
| Unit | `User::isAdmin()` | Factory con role admin, guest, assert boolean |
| Feature | AuthController | Login OK, login inválido, me sin token, me con token, logout revoca, token expirado |
| Feature | Autorización admin | CRUD completo en `/api/niveles` con actingAs(admin) |
| Feature | Autorización guest | POST/PUT/DELETE → 403, GET → 200 |
| Feature | Tests existentes | Pasan con `actingAs(admin)` en setUp |

## Migration / Rollout

**No migration de datos requerida** — la columna `role` tiene default `'guest'`. Los usuarios existentes quedan como guest hasta que un admin los promueva manualmente vía base de datos o Tinker. Los seeders son para desarrollo.

## Open Questions

None.
