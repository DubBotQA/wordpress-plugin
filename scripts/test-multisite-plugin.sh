#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
project_name="dubbot-plugin-multisite-test"
compose=(docker compose --project-name "$project_name" --file "$root_dir/docker-compose.yml" --file "$root_dir/docker-compose.multisite.yml")

if [[ "${1:-}" == "--clean" ]]; then
  "${compose[@]}" down --volumes --remove-orphans
  echo "Removed the isolated multisite test containers and volumes."
  exit 0
fi
if [[ $# -ne 0 ]]; then
  echo "Usage: scripts/test-multisite-plugin.sh [--clean]" >&2
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

wait_for_wordpress_files() {
  for _ in {1..30}; do
    if wp core version >/dev/null 2>&1 && wp config get DB_NAME --type=constant >/dev/null 2>&1; then
      return
    fi

    sleep 1
  done

  echo "WordPress files were not ready after 30 seconds." >&2
  exit 1
}

wait_for_wordpress_files

if ! wp core is-installed >/dev/null 2>&1; then
  wp core multisite-install \
    --url="http://localhost:${WORDPRESS_PORT:-8088}" \
    --title="DubBot Multisite Plugin Test" \
    --admin_user=admin \
    --admin_password=admin \
    --admin_email=admin@example.test \
    --skip-email
fi

wp config set MULTISITE true --raw
wp config set SUBDOMAIN_INSTALL false --raw
wp config set DOMAIN_CURRENT_SITE "localhost:${WORDPRESS_PORT:-8088}"
wp config set PATH_CURRENT_SITE /
wp config set SITE_ID_CURRENT_SITE 1 --raw
wp config set BLOG_ID_CURRENT_SITE 1 --raw

if ! wp site list --field=path | grep --fixed-strings --quiet '/secondary/'; then
  wp site create --slug=secondary --title="DubBot Secondary Site" --email=admin@example.test
fi

wp plugin activate dubbot --network
wp site option update dubbot_embed_key test-key
wp site option update dubbot_api_url "http://host.docker.internal:${MOCK_API_PORT:-8089}"
wp site option update dubbot_editor_selector '#editor iframe'
wp --url="http://localhost:${WORDPRESS_PORT:-8088}/secondary/" eval-file wp-content/plugins/dubbot/tests/multisite-smoke.php

echo
echo "Multisite smoke test passed. Inspect the network at http://localhost:${WORDPRESS_PORT:-8088}/wp-admin/network/"
echo "Login: admin / admin"
echo "Run scripts/test-multisite-plugin.sh --clean when you are finished."
