#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
svn_dir="${WPORG_SVN_DIR:-$root_dir/../dubbot-wordpress-svn}"
commit=false

usage() {
  cat <<'EOF'
Usage: scripts/deploy-wporg.sh [--svn-dir DIR] [--commit]

Prepares WordPress.org trunk and tags/VERSION from the current source.
Without --commit it only prints the pending SVN changes.  --commit publishes
the release using the SVN credentials configured for that checkout.
EOF
}

while (($#)); do
  case "$1" in
    --svn-dir) svn_dir="$2"; shift 2 ;;
    --commit) commit=true; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1" >&2; usage >&2; exit 2 ;;
  esac
done

if [[ ! -d "$svn_dir/.svn" ]]; then
  echo "Not an SVN working copy: $svn_dir" >&2
  exit 1
fi

svn_url="$(svn info --show-item url "$svn_dir")"
if [[ "$svn_url" != "https://plugins.svn.wordpress.org/dubbot" ]]; then
  echo "Refusing to publish: expected the DubBot WordPress.org checkout, got $svn_url." >&2
  exit 1
fi
if [[ -n "$(svn status "$svn_dir")" ]]; then
  echo "SVN working copy has uncommitted changes; inspect or revert them first." >&2
  svn status "$svn_dir" >&2
  exit 1
fi

version="$(awk -F': *' '/^Stable tag:/{print $2; exit}' "$root_dir/readme.txt")"
if [[ -z "$version" || "$version" == "trunk" ]]; then
  echo "readme.txt must contain a numbered Stable tag." >&2
  exit 1
fi
stage_dir="$(mktemp -d)"
cleanup() { rm -rf "$stage_dir"; }
trap cleanup EXIT
"$root_dir/scripts/build-release.sh" --stage-dir "$stage_dir" >/dev/null

if [[ "$commit" == false ]]; then
  echo "Dry run for WordPress.org release $version (no SVN files changed):"
  rsync -ani --delete --exclude '.svn' "$stage_dir/" "$svn_dir/trunk/"
  echo "Would create tags/$version from trunk."
  echo
  echo "After reviewing, publish with:"
  echo "  $0 --svn-dir $(printf '%q' "$svn_dir") --commit"
else
  svn update "$svn_dir"
  if [[ -n "$(svn status "$svn_dir")" ]]; then
    echo "SVN update left local changes; inspect them before publishing." >&2
    svn status "$svn_dir" >&2
    exit 1
  fi
  if svn list "$svn_url/tags/$version" >/dev/null 2>&1; then
    echo "Tag $version already exists. Bump the version before publishing." >&2
    exit 1
  fi

  mkdir -p "$svn_dir/trunk" "$svn_dir/tags"
  rsync -a --delete --exclude '.svn' "$stage_dir/" "$svn_dir/trunk/"
  while IFS= read -r line; do
    [[ "${line:0:1}" == '!' ]] || continue
    path="${line:7}"
    path="${path#"${path%%[![:space:]]*}"}"
    svn delete --force "$path"
  done < <(svn status "$svn_dir/trunk")
  svn add --force "$svn_dir/trunk" >/dev/null
  svn copy "$svn_dir/trunk" "$svn_dir/tags/$version"

  echo "Publishing WordPress.org release $version:"
  svn status "$svn_dir"
  svn commit "$svn_dir" -m "Release DubBot $version"
  echo "Published DubBot $version to WordPress.org."
fi
