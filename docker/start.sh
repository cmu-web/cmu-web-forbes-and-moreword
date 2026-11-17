#!/bin/sh
# Starts the docs site: the built database into memory (/tmp/fam), fresh
# salts that live only as long as the container, then Apache. With
# --prepare, only the salts and folder, for the image build's seed.
set -eu
mkdir -p /tmp/fam
umask 077
php -r '
  $keys = ["AUTH_KEY", "SECURE_AUTH_KEY", "LOGGED_IN_KEY", "NONCE_KEY",
    "AUTH_SALT", "SECURE_AUTH_SALT", "LOGGED_IN_SALT", "NONCE_SALT"];
  echo "<?php\n";
  foreach ($keys as $k) {
    printf("define(%s, %s);\n", var_export($k, true), var_export(bin2hex(random_bytes(32)), true));
  }
' > /tmp/fam/salts.php
[ "${1:-}" = --prepare ] && exit 0
cp /opt/fam/site.sqlite /tmp/fam/site.sqlite
exec apache2-foreground
