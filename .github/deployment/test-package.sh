#!/usr/bin/env bash
set -euo pipefail

# Run in Sail locally or the PHP CI runner. Uses only a new temporary SQLite DB.
script_dir=$(cd "$(dirname "$0")" && pwd)
archive=$(realpath "${1:?Usage: test-package.sh /path/to/backend.tar.gz}")
work=$(mktemp -d)
trap 'rm -rf -- "$work"' EXIT
root="$work/deployment"
mkdir -p "$root/shared/storage"
export RELEASE_TEST_DATABASE="$root/shared/test.sqlite"
touch "$RELEASE_TEST_DATABASE"
php -r '
    $settings = [
        "APP_ENV=production", "APP_DEBUG=false", "APP_KEY=base64:".base64_encode(random_bytes(32)),
        "JWT_SECRET=".bin2hex(random_bytes(32)), "DB_CONNECTION=sqlite", "DB_URL=",
        "DB_DATABASE=".getenv("RELEASE_TEST_DATABASE"), "CACHE_STORE=database",
        "SESSION_DRIVER=array", "QUEUE_CONNECTION=sync", "MAIL_MAILER=array",
    ];
    file_put_contents($argv[1], implode(PHP_EOL, $settings).PHP_EOL);
' "$root/shared/.env"
chmod 600 "$root/shared/.env"
printf 'persistent private fixture\n' > "$root/shared/storage/private-fixture"

cat > "$work/adapter" <<'ADAPTER'
#!/usr/bin/env bash
set -euo pipefail
cd "$2"
case "$1" in
    check)
        test ! -e .env.testing
        test ! -d tests
        test ! -d .github
        test ! -e docker-compose.yml
        test ! -d vendor/phpunit
        test ! -d vendor/laravel/sail
        php -r '
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            if (! $app->environment("production") || config("app.debug") !== false
                || config("database.default") !== "sqlite"
                || config("database.connections.sqlite.database") !== getenv("RELEASE_TEST_DATABASE")) {
                exit(1);
            }
            Illuminate\Support\Facades\DB::select("SELECT 1");
        '
        ;;
    migrate) php artisan migrate --force --no-interaction ;;
    warm)
        php artisan config:cache --no-interaction
        php artisan route:cache --no-interaction
        php artisan view:cache --no-interaction
        php artisan l5-swagger:generate --no-interaction
        ;;
    reload) : ;; # CLI processes boot on every check; no persistent server in this test.
    health)
        php -r '
            require "vendor/autoload.php";
            $app = require "bootstrap/app.php";
            $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
            $request = Illuminate\Http\Request::create("/up");
            $response = $kernel->handle($request);
            $kernel->terminate($request, $response);
            Illuminate\Support\Facades\DB::select("SELECT 1");
            Illuminate\Support\Facades\Cache::put("release-health", "ok", 30);
            $cacheIsHealthy = Illuminate\Support\Facades\Cache::get("release-health") === "ok";
            Illuminate\Support\Facades\Cache::forget("release-health");
            json_decode(file_get_contents(storage_path("api-docs/api-docs.json")), true, flags: JSON_THROW_ON_ERROR);
            exit($response->getStatusCode() === 200 && $cacheIsHealthy ? 0 : 1);
        '
        ;;
    *) exit 1 ;;
esac
ADAPTER
chmod +x "$work/adapter"

for release_id in v1 v2; do
    bash "$script_dir/release.sh" prepare "$root" "$release_id" "$archive"
    bash "$script_dir/release.sh" activate "$root" "$release_id" "$work/adapter"
    [[ "$(readlink -e "$root/current")" = "$root/releases/$release_id" ]]
    [[ "$(cat "$root/current/storage/private-fixture")" = 'persistent private fixture' ]]
done
bash "$script_dir/release.sh" rollback "$root" "$work/adapter"
[[ "$(readlink -e "$root/current")" = "$root/releases/v1" ]]
[[ "$(cat "$root/current/storage/private-fixture")" = 'persistent private fixture' ]]
printf 'Production package boots, migrates, caches, passes /up and rolls back code locally. No external server or database restore tested.\n'
