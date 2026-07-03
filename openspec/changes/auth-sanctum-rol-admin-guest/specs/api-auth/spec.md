# API Authentication Specification

## Purpose

Authenticate API consumers via Sanctum token-based auth. Tokens expire after 6h. Unauthenticated requests return 401.

## Requirements

### Requirement: Login returns a Sanctum token

`POST /api/login` with `email` + `password`. Valid credentials return a Sanctum plain-text token.

#### Scenario: Valid credentials

- GIVEN a user with email `admin@example.com` and password `password`
- WHEN they POST `/api/login` with valid credentials
- THEN the response MUST be 200
- AND the body MUST contain a `token` field with a non-empty string

#### Scenario: Invalid credentials

- GIVEN a non-existent email or wrong password
- WHEN they POST `/api/login`
- THEN the response MUST be 401

### Requirement: Token expires after 6 hours

Each token MUST set `expires_at` to `now()->addHours(6)`. Expired tokens MUST NOT authenticate.

#### Scenario: Expired token

- GIVEN a token created 6h + 1min ago
- WHEN a request with `Authorization: Bearer <token>` hits any protected route
- THEN the response MUST be 401

### Requirement: Logout revokes the current token

`POST /api/logout` revokes (deletes) the current token from the `Authorization` header.

#### Scenario: Token invalidated

- GIVEN an authenticated user with a valid token
- WHEN they POST `/api/logout`
- THEN the response MUST be 200
- AND the token MUST no longer be valid for subsequent requests

### Requirement: User profile returns authenticated user

`GET /api/user` returns the authenticated user's `name`, `email`, and `role`.

#### Scenario: Profile access

- GIVEN an authenticated user
- WHEN they GET `/api/user`
- THEN the response MUST be 200
- AND the body MUST contain `name`, `email`, and `role`

### Requirement: Unauthenticated requests return 401

All routes under `auth:sanctum` middleware MUST return 401 when no valid token is present.

#### Scenario: No token

- GIVEN no `Authorization` header
- WHEN a request hits any protected route
- THEN the response MUST be 401
