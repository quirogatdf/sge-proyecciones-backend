# Exploration: Auth con Sanctum + Roles Admin/Guest

## Current State

### Authentication
- **CERO autenticación** en toda la API. No hay Sanctum, Passport, ni JWT.
- `config/auth.php` solo tiene el guard `web` con driver `session`. No hay guard `api`.
- `bootstrap/app.php` no registra ningún middleware de auth para rutas API.
- `cors.php` ya referencia `sanctum/csrf-cookie` (residual del skeleton de Laravel), pero Sanctum no está instalado.

### Routes (`routes/api.php`)
- **7 `apiResource` completamente abiertas**: niveles, cargos, turnos, funciones, instituciones, proyecciones, resoluciones.
- Proyecciones tiene 2 rutas adicionales: `stats/by-institucion` y `nivel/{idNivel}`.
- **NINGUNA ruta tiene middleware `auth:sanctum`**.
- Sin prefijo de grupo, sin middleware global de auth.

### Controllers (`app/Http/Controllers/Api/`)
- **7 controllers final** con patrón **100% consistente**:
  - `index()` → `Model::all()` → response JSON con `Resource::collection()`
  - `show(int $id)` → `Model::findOrFail($id)` → response JSON con `new Resource()`
  - `store(StoreXRequest $request)` → `Model::create($validated)` → response 201
  - `update(UpdateXRequest $request, int $id)` → `findOrFail + update` → response 200
  - `destroy(int $id)` → `findOrFail + delete` → `response()->noContent()`
  - Proyección tiene extras: `byNivel()`, `statsByInstitucion()`
- Todos heredan de `App\Http\Controllers\Controller` (abstract, vacío).

### Form Requests (`app/Http/Requests/`)
- **14 Form Requests** (Store/Update para cada resource).
- Todos tienen `authorize(): bool { return true; }` — **sin autorización**.
- Todos tienen `failedValidation()` que lanza `HttpResponseException` con JSON 422.
- Mensajes de error en español, strings literales.

### User Model
- `app/Models/User.php` — estándar Authenticatable.
- Sin campo `role`. Solo `name`, `email`, `password`, `remember_token`, timestamps.
- `#[Fillable(['name', 'email', 'password'])]`
- Password cast a `hashed`.

### User Migration
- Tabla `users` estándar: id, name, email, email_verified_at, password, remember_token, timestamps.
- Sin columna `role`.

### User Factory
- `database/factories/UserFactory.php` — estándar, faker, password hasheado por defecto.
- Sin estado `admin()` o `guest()`.

### Seeders
- `DatabaseSeeder` solo corre `NivelSeeder` y `CargoSeeder`.
- No hay seeder de usuarios.

### Tests
- **Feature tests**: `NivelApiTest`, `CargoApiTest`, `ResolucionApiTest` (con `RefreshDatabase`), `ProyeccionControllerTest` (migraciones manuales).
- **Unit tests**: solo `ExampleTest`.
- Patrón de tests: Given/When/Then implícito. Usan `$this->getJson()`, `postJson()`, `putJson()`, `deleteJson()`.
- **No hay tests de auth**.

## Affected Areas

### Archivos a CREAR
- `app/Http/Controllers/Api/AuthController.php` — Login/logout/register/me
- `app/Http/Requests/Auth/LoginRequest.php` — Validación de login
- `app/Http/Requests/Auth/RegisterRequest.php` — Validación de registro
- `database/migrations/xxxx_xx_xx_xxxxxx_add_role_to_users_table.php` — Columna role
- `app/Enums/RolUsuario.php` — Enum Admin/Guest
- `config/sanctum.php` — Config de Sanctum (via vendor:publish)
- `routes/auth.php` o rutas de auth en `api.php`
- `tests/Feature/Api/AuthTest.php` — Tests de auth
- `tests/Feature/Api/Auth/LoginTest.php` (si se separan)

### Archivos a MODIFICAR
- `composer.json` — Agregar `laravel/sanctum`
- `bootstrap/app.php` — Registrar middleware `EnsureFrontendRequestsAreStateful` si es SPA; configurar sanctum
- `app/Models/User.php` — Agregar `HasApiTokens`, agregar `role` a `$fillable`, agregar método `isAdmin()`
- `routes/api.php` — Agregar grupo de rutas auth, proteger rutas existentes con middleware
- `config/auth.php` — Agregar guard `sanctum` (lo hace automático al instalar)
- `.env.example` — Agregar variables Sanctum si es necesario
- `database/factories/UserFactory.php` — Agregar estados `admin()` y `guest()`
- `database/seeders/DatabaseSeeder.php` — Agregar `UserSeeder` (admin inicial)

### Archivos a MODIFICAR (Form Requests) — TODOS
- Los 14 Form Requests: cambiar `authorize()` de `return true` a lógica con roles (si aplica policies)

### Archivos a MODIFICAR (Controllers) — TODOS
- Los 7 controllers: agregar middleware `auth:sanctum` y verificar roles en métodos específicos

### Opcionales
- `app/Policies/` — Policies por resource si se opta por ese approach

## Approaches

### 1. Sanctum + Policies + role column ★ RECOMENDADO

**Descripción**: Laravel Sanctum para token-based API auth. Columna `role` (enum: admin, guest) en users. Policies de autorización para cada resource. Tokens con expiración de 6 horas.

**Pros**:
- Solución nativa de Laravel (first-party, sin dependencias externas)
- Simple de implementar y mantener
- Policies permiten autorización granular sin tocar controllers
- Token expiration de 6 horas es nativa de Sanctum (`expires_at`)
- `HasApiTokens` trait da todo lo necesario para manejo de tokens
- Fácil de testear con `actingAs()`

**Cons**:
- Roles fijos (admin/guest), no escala a muchos roles sin Spatie
- Hay que modificar cada Form Request para autorización
- Si en futuro se necesitan permisos más granulares, habría que migrar a Spatie

**Esfuerzo**: Medio (2-3 días de implementación + tests)

### 2. Sanctum + Spatie Laravel Permissions

**Descripción**: Sanctum para auth + Spatie Laravel Permissions para roles y permisos. En lugar de columna `role` simple, se usan las tablas de Spatie (roles, permissions, model_has_roles, etc).

**Pros**:
- Escalable a muchos roles y permisos granulares
- Permite asignar múltiples roles a un usuario
- Gates y Policies integrados con Spatie
- Blade directives (`@can`, `@role`) si se agrega web
- API rica para manejo de permisos

**Cons**:
- **OVERKILL para solo 2 roles** (admin/guest)
- Agrega 4 tablas + Migrations + dependencia externa
- Complejidad innecesaria para el caso de uso actual
- Más superficie de testing
- Curva de aprendizaje para el equipo

**Esfuerzo**: Alto (3-4 días + testing)

### 3. JWT (tymon/jwt-auth)

**Descripción**: Reemplazar Sanctum por JWT manual con `tymon/jwt-auth`. Tokens JWT con expiración en payload.

**Pros**:
- Stateless puro (no toca DB para validar tokens)
- Ideal para microservicios o APIs distribuidas
- Estandar JWT ampliamente conocido

**Cons**:
- **Dependencia externa** no oficial de Laravel
- No tiene integración nativa con Laravel auth (`auth:sanctum` middleware no funciona)
- Más boilerplate: refresh tokens, blacklisting, etc.
- Laravel ya incluye Sanctum por defecto desde 7.x
- Mayor superficie de seguridad (manejo de signing keys, refresh rotation)
- El equipo tendría que aprender JWT + la librería

**Esfuerzo**: Medio-Alto (3-4 días)

## Recommendation

**Approach 1: Sanctum + Policies + role column.**

Las razones son claras:

1. **Es la solución first-party de Laravel** — cero dependencias externas, el framework ya viene preparado para esto.
2. **Solo tenemos 2 roles** — meterse con Spatie para admin/guest es usar un lanzallamas para matar una hormiga. Si en el futuro se necesitan más roles, se migra a Spatie.
3. **Policies** mantienen la autorización fuera de los controllers, siguiendo el principio de responsabilidad única.
4. **Token expiration de 6 horas** es nativa: al crear un token se le pasa `expiresAt(Carbon::now()->addHours(6))`.
5. **`actingAs()` en tests** permite testear rutas protegidas sin tener que generar tokens manualmente en cada test.
6. **Es el approach que ya discutiste con el usuario** — no hay sopresas.

La implementación concreta:
- `User::isAdmin()` → `$this->role === 'admin'`
- Policies: `NivelPolicy`, `CargoPolicy`, etc. — guest solo puede `index` y `show`, admin puede todo.
- Los Form Requests existentes se quedan igual (validación de datos), y la autorización se delega a las Policies + middleware.

## Risks

1. **Token storage**: Los tokens Sanctum se guardan en `personal_access_tokens` con hash SHA-256. Si migran a DB diferente o hacen backup, los tokens no se migran como texto plano. **Mitigación**: Sanctum maneja esto automáticamente con hashing.

2. **Expiración de 6 horas**: Si el frontend no maneja refresh/relogin, usuarios guest pueden perder acceso en medio de una sesión. **Mitigación**: El frontend debe detectar 401 y redirigir a login. Considerar 12-24 horas si es muy restrictivo.

3. **Tests existentes**: Los tests actuales (`NivelApiTest`, `CargoApiTest`, etc.) no usan auth — todos fallarán cuando se agregue middleware. **Mitigación**: Actualizar tests existentes para usar `$this->actingAs($adminUser)` o crear usuario admin en `setUp()`.

4. **Ruta de registro**: Definir si guest puede registrarse solo o si requiere admin. **Riesgo de seguridad** si se deja registro abierto. **Mitigación**: Por defecto, solo registro via `RegisterRequest` si se requiere; o solo admin puede crear usuarios.

5. **CORS con `http://localhost:4200`**: Si el frontend Angular envía tokens en headers, no hay problema. Si se opta por SPA Sanctum (cookies), requiere `supports_credentials: true` en CORS. **Mitigación**: Usar API token-based Sanctum, no SPA, para evitar complejidad de CORS con cookies.

## Ready for Proposal

**Sí** — la dirección está clara y alineada con lo discutido. El approach Sanctum + role column + Policies es el correcto para el caso de uso. Se puede proceder directamente al proposal y luego specs.
