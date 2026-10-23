#!/bin/sh
set -eu
# Environment-specific caches must be built inside each actual runtime container.
# Database migrations and data imports remain explicit operator actions.
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
exec "$@"
