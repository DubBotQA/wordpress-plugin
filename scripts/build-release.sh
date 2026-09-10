#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
manifest="$root_dir/plugin-files.txt"
output_dir="$root_dir/dist"
stage_dir=""

usage() {
  cat <<'EOF'
Usage: scripts/build-release.sh [--output-dir DIR] [--stage-dir DIR]

Builds dist/dubbot-VERSION.zip.  --stage-dir also writes the exact plugin
payload there, which is used by the WordPress.org publishing script.
EOF
}

while (($#)); do
  case "$1" in
    --output-dir) output_dir="$2"; shift 2 ;;
    --stage-dir) stage_dir="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
  esac
done

plugin_version="$(awk -F': *' '/^Version:/{print $2; exit}' "$root_dir/dubbot.php")"
stable_tag="$(awk -F': *' '/^Stable tag:/{print $2; exit}' "$root_dir/readme.txt")"

if [[ -z "$plugin_version" || -z "$stable_tag" ]]; then
  echo "Could not determine the plugin version and Stable tag." >&2
  exit 1
fi
if [[ "$plugin_version" != "$stable_tag" ]]; then
  echo "Version mismatch: dubbot.php is $plugin_version but readme.txt is $stable_tag." >&2
  exit 1
fi
if [[ "$stable_tag" == "trunk" ]]; then
  echo "Stable tag must be a release version, not trunk." >&2
  exit 1
fi

git -C "$root_dir" diff --check

while IFS= read -r php_file; do
  php -l "$php_file" >/dev/null
done < <(find "$root_dir" -path "$root_dir/.git" -prune -o -name '*.php' -type f -print)

temporary_stage=""
if [[ -z "$stage_dir" ]]; then
  temporary_stage="$(mktemp -d)"
  stage_dir="$temporary_stage"
else
  mkdir -p "$stage_dir"
  if [[ -n "$(find "$stage_dir" -mindepth 1 -maxdepth 1 -print -quit)" ]]; then
    echo "Stage directory must be empty: $stage_dir" >&2
    exit 1
  fi
fi

cleanup() {
  if [[ -n "$temporary_stage" ]]; then
    rm -rf "$temporary_stage"
  fi
}
trap cleanup EXIT

while IFS= read -r entry; do
  [[ -z "$entry" || "$entry" == \#* ]] && continue
  if [[ ! -e "$root_dir/$entry" ]]; then
    echo "Plugin manifest entry is missing: $entry" >&2
    exit 1
  fi
  # Do not accidentally ship macOS metadata, VCS files, or other hidden files.
  rsync -a --exclude='.*' "$root_dir/$entry" "$stage_dir/"
done < "$manifest"

mkdir -p "$output_dir"
output_zip="$output_dir/dubbot-$stable_tag.zip"
rm -f "$output_zip"
(cd "$stage_dir" && zip -qr "$output_zip" .)

echo "Built $output_zip"
if [[ -z "$temporary_stage" ]]; then
  echo "Staged plugin files in $stage_dir"
fi
