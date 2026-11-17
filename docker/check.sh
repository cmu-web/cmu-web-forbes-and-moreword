#!/bin/sh
# Checks a running docs-site container: its pages and files answer, and every
# path that edits or signs in is a 404.
#   docker/check.sh http://127.0.0.1:8080
set -u
base=${1:?usage: check.sh URL}
fail=0
expect() { # status, path
  for _ in 1 2 3 4 5 6 7 8 9 10; do
    code=$(curl -s -o /dev/null -w '%{http_code}' "$base$2") && [ "$code" != 000 ] && break
    sleep 1
  done
  if [ "$code" = "$1" ]; then echo "ok   $code $2"; else echo "FAIL $code $2 (wanted $1)"; fail=1; fi
}
for p in / /install/ /patterns/ /news/ /about/ /images/preview.jpg; do expect 200 "$p"; done
for p in /wp-admin/ /wp-admin/admin-ajax.php /wp-login.php /xmlrpc.php /wp-json/ \
  /wp-json/wp/v2/users '/?rest_route=/wp/v2/users' /wp-cron.php /readme.html \
  /wp-config.php /wp-content/db.php /wp-includes/version.php /no-such-page/; do
  expect 404 "$p"
done
exit $fail
