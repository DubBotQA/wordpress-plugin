#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
project_name="dubbot-plugin-test"
compose=(docker compose --project-name "$project_name" --file "$root_dir/docker-compose.yml")

if [[ "${1:-}" == "--clean" ]]; then
  "${compose[@]}" down --volumes --remove-orphans
  echo "Removed the isolated test containers and volumes."
  exit 0
fi
if [[ $# -ne 0 ]]; then
  echo "Usage: scripts/test-plugin.sh [--clean]" >&2
  exit 2
fi

if ! docker info >/dev/null 2>&1; then
  echo "Docker Desktop must be running before testing." >&2
  exit 1
fi

"${compose[@]}" up --detach --wait db wordpress mock-api

wp() {
  "${compose[@]}" run --rm --no-deps --no-TTY wpcli "$@"
}

if ! wp core is-installed >/dev/null 2>&1; then
  wp core install \
    --url="http://localhost:${WORDPRESS_PORT:-8088}" \
    --title="DubBot Plugin Test" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test \
    --skip-email
fi

wp plugin activate dubbot
wp option update dubbot_embed_key test-key
wp option update dubbot_api_url "http://host.docker.internal:${MOCK_API_PORT:-8089}"
wp option update dubbot_editor_selector '#editor iframe'
wp eval-file wp-content/plugins/dubbot/tests/smoke.php

echo
echo "Smoke test passed. Inspect the plugin at http://localhost:${WORDPRESS_PORT:-8088}/wp-admin/"
echo "Login: admin / admin"
echo "Run scripts/test-plugin.sh --clean when you are finished."
