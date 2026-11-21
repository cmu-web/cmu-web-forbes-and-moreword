#!/usr/bin/env bash
# A local copy of the docs site, at http://127.0.0.1:3800/.
#   dev/run.sh up        start from an empty database and seed it, with the demo posts
#   dev/run.sh editor    LOCAL ONLY: give fam-admin a demo password, for block
#                        editor screenshots; the hosted site has no way in
#   dev/run.sh check     check the theme against the local WordPress site
#   dev/run.sh browser   check the pages in Chromium, in Playwright's Docker image
#                        and as you, so test-results/ stays yours
#   dev/run.sh wp ...    run WP-CLI
#   dev/run.sh down      stop and throw everything away
set -euo pipefail
cd "$(dirname "$0")"
compose() { docker compose -f compose.yml "$@"; }
wp() { compose run --rm -T cli wp "$@"; }
case "${1:-}" in
  up)
    compose down -v >/dev/null 2>&1 || true
    compose up -d --wait
    # The WordPress image copies its files into the volume on first start
    until compose exec -T wordpress test -f /var/www/html/wp-config.php; do sleep 1; done
    compose run --rm -T cli bash /docs/seed.sh http://127.0.0.1:3800 --demo
    echo "Forbes & Moreword on http://127.0.0.1:3800/"
    ;;
  editor)
    wp user update fam-admin --user_pass=local-demo-only --skip-email --quiet
    echo "LOCAL ONLY: sign in at http://127.0.0.1:3800/wp-login.php as fam-admin, password local-demo-only"
    ;;
  check) wp eval-file /tests/integration.php ;;
  browser)
    # The image matching the playwright version in package-lock.json. It runs
    # as your user and group, so its screenshots aren't owned by root.
    version=$(node -p "require('../package-lock.json').packages['node_modules/playwright'].version")
    docker run --rm --network host --user "$(id -u):$(id -g)" -e HOME=/tmp \
      -e FAM_TEST_URL -e FAM_TEST_RESULTS \
      -v "$(cd .. && pwd)":/work -w /work "mcr.microsoft.com/playwright:v$version-noble" \
      node tests/browser.cjs
    ;;
  wp) shift; wp "$@" ;;
  down) compose down -v ;;
  *) sed -n '2,10p' "$0"; exit 1 ;;
esac
