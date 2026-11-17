#!/usr/bin/env bash
set -euo pipefail

# Regression tests for the filesystem lifecycle; runtime phases use a test double.
script_dir=$(cd "$(dirname "$0")" && pwd)
work=$(mktemp -d)
trap 'rm -rf -- "$work"' EXIT
root="$work/deployment"
mkdir -p "$root/shared/storage" "$work/source/"{vendor,public,bootstrap/cache}
printf 'persistent-secret\n' > "$root/shared/.env"
printf 'persistent-evidence\n' > "$root/shared/storage/evidence"
touch "$work/source/artisan" "$work/source/vendor/autoload.php"
printf 'test-revision\n' > "$work/source/REVISION"
tar -czf "$work/backend.tar.gz" -C "$work/source" .
(cd "$work" && sha256sum backend.tar.gz > backend.tar.gz.sha256)
export RELEASE_TEST_LOG="$work/phases" RELEASE_TEST_FAILURE_FILE="$work/failure"
cat > "$work/adapter" <<'ADAPTER'
#!/usr/bin/env bash
set -euo pipefail
phase=$1
release_id=$(basename "$2")
printf '%s %s\n' "$phase" "$release_id" >> "$RELEASE_TEST_LOG"
if [[ -f "$RELEASE_TEST_FAILURE_FILE" && "$(cat "$RELEASE_TEST_FAILURE_FILE")" = "$phase $release_id" ]]; then
    exit 1
fi
ADAPTER
chmod +x "$work/adapter"

expect_failure() {
    if "$@"; then
        echo 'Expected operation to fail.' >&2
        exit 1
    fi
}
prepare() { bash "$script_dir/release.sh" prepare "$root" "$1" "$work/backend.tar.gz"; }
activate() { bash "$script_dir/release.sh" activate "$root" "$1" "$work/adapter"; }
assert_current() { [[ "$(readlink -e "$root/current")" = "$root/releases/$1" ]]; }
assert_persistence() {
    [[ "$(cat "$root/current/.env")" = persistent-secret ]]
    [[ "$(cat "$root/current/storage/evidence")" = persistent-evidence ]]
    [[ "$(readlink -e "$root/current/public/storage")" = "$root/shared/storage/app/public" ]]
}

expect_failure prepare ../escape
prepare v1
expect_failure prepare v1
expect_failure bash "$script_dir/release.sh" activate "$root" v1 /missing/adapter
activate v1
assert_current v1
assert_persistence
prepare v2
activate v2
assert_current v2
[[ "$(readlink -e "$root/previous")" = "$root/releases/v1" ]]
bash "$script_dir/release.sh" rollback "$root" "$work/adapter"
assert_current v1
assert_persistence
[[ "$(awk '/^migrate / { count++ } END { print count }' "$work/phases")" = 2 ]]

prepare v3
for phase in check migrate warm reload health; do
    printf '%s v3\n' "$phase" > "$work/failure"
    expect_failure activate v3
    assert_current v1
    assert_persistence
done
[[ -d "$root/releases/v1" && -d "$root/releases/v2" && -d "$root/releases/v3" ]]

# A corrupt archive must fail before creating a release directory.
printf 'corrupt\n' >> "$work/backend.tar.gz"
expect_failure prepare corrupt
[[ ! -e "$root/releases/corrupt" ]]

# A concurrent release is rejected while the same root is locked.
(
    flock -x 8
    expect_failure activate v2
) 8> "$root/.release.lock"

# A failed first activation restores the state without a current pointer.
first_root="$work/first"
mkdir -p "$first_root/shared/storage"
cp "$root/shared/.env" "$first_root/shared/.env"
tar -czf "$work/backend.tar.gz" -C "$work/source" .
(cd "$work" && sha256sum backend.tar.gz > backend.tar.gz.sha256)
bash "$script_dir/release.sh" prepare "$first_root" first "$work/backend.tar.gz"
printf 'health first\n' > "$work/failure"
expect_failure bash "$script_dir/release.sh" activate "$first_root" first "$work/adapter"
[[ ! -e "$first_root/current" && ! -L "$first_root/current" ]]
printf 'Release filesystem regression checks passed. Runtime and database recovery require separate verification.\n'
