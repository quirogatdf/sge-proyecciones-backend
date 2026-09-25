#!/usr/bin/env bash
#
# Pre-Deploy Command de Railway: corre UNA vez por deploy, ANTES de arrancar
# la versión nueva. Si este script falla, Railway aborta el deploy y la
# versión actual sigue funcionando.
#
# Qué hace:
#   1. migrate --force  -> aplica migraciones pendientes (incluye el guard
#                          anti-pérdida de 000003: si hay una proyección sin
#                          año, falla y el deploy se aborta).
#   2. optimize:clear   -> limpia cachés de config/rutas/vistas para el
#                          entorno de producción.
#
set -euo pipefail

# El script vive en railway/, el artisan en la raíz del backend.
cd "$(dirname "$0")/.."

php artisan migrate --force --ansi
php artisan optimize:clear