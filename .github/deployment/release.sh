#!/usr/bin/env bash
set -euo pipefail

# Filesystem contract only: the operator supplies a runtime adapter, never eval.
# prepare ROOT ID ARCHIVE | activate ROOT ID ADAPTER | rollback ROOT ADAPTER
action=${1:?Missing action}
root=${2:?Missing absolute release root}
[[ "$root" = /* && -d "$root/shared/storage" && -f "$root/shared/.env" ]] || {
    echo 'Provide an absolute root with shared/.env and shared/storage already provisioned.' >&2; exit 1;
}
root=$(realpath "$root")
mkdir -p "$root/releases"
exec 9> "$root/.release.lock"
flock -n 9 || { echo 'Another release operation is running.' >&2; exit 1; }

switch_current() {
    ln -s "$1" "$root/.current-next"
    mv -Tf "$root/.current-next" "$root/current"
}

case "$action" in
    prepare)
        release_id=${3:?Missing release ID}
        [[ "$release_id" =~ ^[a-zA-Z0-9][a-zA-Z0-9._-]*$ ]] || exit 1
        archive=$(realpath "${4:?Missing trusted archive}")
        release="$root/releases/$release_id"
        [[ ! -e "$release" && ! -L "$release" ]] || { echo 'Release already exists.' >&2; exit 1; }
        # Only accept the fixed checksum format emitted by package.sh.
        [[ "$(cat "$archive.sha256")" =~ ^[a-f0-9]{64}[[:space:]][[:space:]]backend.tar.gz$ ]] || exit 1
        (cd "$(dirname "$archive")" && sha256sum --check --status "$(basename "$archive").sha256")
        tar -tzf "$archive" | while IFS= read -r entry; do
            [[ "$entry" != /* && "/$entry/" != *'/../'* ]] || exit 1
        done
        mkdir "$release"
        tar -xzf "$archive" --no-same-owner --no-same-permissions -C "$release"
        [[ -f "$release/artisan" && -f "$release/vendor/autoload.php" && -f "$release/REVISION" ]]
        [[ ! -e "$release/.env" && ! -L "$release/.env" && ! -e "$release/storage" && ! -L "$release/storage" ]]
        mkdir -p "$root/shared/storage/app/"{public,private/evidence} \
            "$root/shared/storage/framework/"{cache/data,sessions,views} "$root/shared/storage/logs" "$release/bootstrap/cache"
        ln -s "$root/shared/.env" "$release/.env"
        ln -s "$root/shared/storage" "$release/storage"
        ln -s "$root/shared/storage/app/public" "$release/public/storage"
        touch "$release/.prepared"
        ;;
    activate|rollback)
        if [[ "$action" = activate ]]; then
            release_id=${3:?Missing release ID}
            [[ "$release_id" =~ ^[a-zA-Z0-9][a-zA-Z0-9._-]*$ ]] || exit 1
            target="$root/releases/$release_id"
            adapter=${4:?Missing runtime adapter}
        else
            target=$(readlink -f "$root/previous")
            adapter=${3:?Missing runtime adapter}
        fi
        [[ "$adapter" = /* && -x "$adapter" && -d "$target" && ! -L "$target" && "$target" = "$root/releases/"* ]]
        [[ -f "$target/.prepared" ]]
        [[ -L "$target/storage" && "$(readlink -f "$target/storage")" = "$root/shared/storage" ]]
        [[ -L "$target/.env" && "$(readlink -f "$target/.env")" = "$root/shared/.env" ]]
        [[ ! -e "$root/current" || -L "$root/current" ]]
        previous=
        if [[ -L "$root/current" ]]; then
            previous=$(readlink -e "$root/current")
            [[ "$previous" = "$root/releases/"* && -f "$previous/.prepared" ]]
        fi
        [[ "$previous" != "$target" ]] || { echo 'Release is already current.' >&2; exit 1; }
        "$adapter" check "$target"
        if [[ "$action" = activate ]]; then
            "$adapter" migrate "$target"
        fi
        "$adapter" warm "$target"
        switch_current "$target"
        if ! "$adapter" reload "$target" || ! "$adapter" health "$target"; then
            if [[ -n "$previous" ]]; then
                switch_current "$previous"
                "$adapter" reload "$previous"
                "$adapter" health "$previous"
            else
                unlink "$root/current"
            fi
            echo 'Activation failed; code pointer restored. Database was not rolled back.' >&2
            exit 1
        fi
        if [[ -n "$previous" ]]; then
            ln -s "$previous" "$root/.previous-next"
            mv -Tf "$root/.previous-next" "$root/previous"
        fi
        ;;
    *) echo 'Unknown action.' >&2; exit 1 ;;
esac
