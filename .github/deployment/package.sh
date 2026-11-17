#!/usr/bin/env bash
set -euo pipefail

# Run inside Sail locally, or in the PHP 8.4 CI runner. Never reuse local vendor.
source_root=$(git rev-parse --show-toplevel)
output=${1:?Usage: package.sh /absolute/output/directory}
[[ "$output" = /* && ! -e "$output" ]] || { echo 'Output must be a new absolute directory.' >&2; exit 1; }
revision=$(git -C "$source_root" rev-parse HEAD)
stage=$(mktemp -d)
trap 'rm -rf -- "$stage"' EXIT
mkdir -p "$output"

git -C "$source_root" archive HEAD -- app artisan bootstrap config database public resources routes composer.json composer.lock |
    tar -x -C "$stage"
find "$stage/bootstrap/cache" -type f ! -name .gitignore -delete
mkdir -p "$stage/storage/framework/"{cache/data,sessions,views} "$stage/storage/logs"
(
    cd "$stage"
    export APP_ENV=production APP_DEBUG=false CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync
    export DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL=
    composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
    composer check-platform-reqs --no-dev
    composer audit --locked --no-dev --no-interaction
)
# Discovery caches contain build paths and must be rebuilt by the target runtime.
find "$stage/bootstrap/cache" -type f ! -name .gitignore -delete
printf '%s\n' "$revision" > "$stage/REVISION"
tar -czf "$output/backend.tar.gz" --exclude='./storage' -C "$stage" .
(
    cd "$output"
    sha256sum backend.tar.gz > backend.tar.gz.sha256
)
printf 'Prepared revision %s\n' "$revision"
