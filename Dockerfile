# The docs site at themes.andrew.cmu.edu/forbes-and-moreword as one image: WordPress,
# the theme, the site's must-use plugin and its content, built from site/ at
# build time into a SQLite database. Nothing is edited in WordPress, so the
# container's root filesystem is read-only; at start it copies the built
# database into memory (/tmp), and a restart puts it back as built.
#
#   docker build -t forbes-and-moreword .
#   docker run --rm --read-only --tmpfs /tmp --cap-drop=ALL \
#     --security-opt no-new-privileges -p 127.0.0.1:3804:8080 \
#     -e FAM_URL=http://127.0.0.1:3804 forbes-and-moreword

# wordpress:7.0-php8.3-apache (WordPress 7.0, PHP 8.3), the same image as
# dev/compose.yml. Apache with mod_php serves the pages and the files in one
# process, so the host only proxies to it.
FROM docker.io/library/wordpress@sha256:b427cec767f5de2aa649390cb8805aa1fe320e1e0d57fc1f467754edb6cc0a49 AS wordpress
# wordpress:cli-2.12.0-php8.3, only for its WP-CLI
FROM docker.io/library/wordpress@sha256:e4980e3ef763ce7535049c05a54e906d95b16370f28ca02fa329a079eebf3f65 AS cli

# WordPress, the theme and the content, seeded into SQLite. The seed imports
# PlaceMellon photos, so this stage needs the network.
FROM wordpress AS site
# The WordPress Performance team's SQLite Database Integration, as the
# db.php drop-in. Kept outside wp-content/plugins, where seed.sh deletes
# every plugin.
ARG SQLITE_VERSION=3.1.3
ARG SQLITE_SHA256=f6ce35e8419d0b9326221a907627d905e25c2a5ba2afc86492811f14c851efe0
COPY --from=cli /usr/local/bin/wp /usr/local/bin/wp
WORKDIR /var/www/html
RUN set -eux; \
    cp -a /usr/src/wordpress/. /var/www/html/; \
    curl -fsSL -o /tmp/sqlite.zip "https://downloads.wordpress.org/plugin/sqlite-database-integration.${SQLITE_VERSION}.zip"; \
    echo "${SQLITE_SHA256}  /tmp/sqlite.zip" | sha256sum -c -; \
    php -r '$z = new ZipArchive(); $z->open("/tmp/sqlite.zip") === true || exit(1); $z->extractTo("/var/www/html/wp-content"); $z->close();'; \
    rm /tmp/sqlite.zip; \
    sed -e "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#/var/www/html/wp-content/sqlite-database-integration#" \
        -e "s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
        wp-content/sqlite-database-integration/db.copy > wp-content/db.php
COPY forbes-and-moreword wp-content/themes/forbes-and-moreword
COPY site/mu-plugins wp-content/mu-plugins
COPY docker/wp-config.php wp-config.php
COPY site /opt/fam/site
COPY docker/start.sh /opt/fam/start.sh
# The build's database and salts are thrown away with this stage's /tmp; the
# seeded database moves to /opt/fam. FAM_BUILD lets WordPress reach PlaceMellon.
RUN set -eux; \
    export WP_CLI_ALLOW_ROOT=1 FAM_BUILD=1; \
    sh /opt/fam/start.sh --prepare; \
    bash /opt/fam/site/seed.sh https://themes.andrew.cmu.edu/forbes-and-moreword; \
    mv /tmp/fam/site.sqlite /opt/fam/site.sqlite; \
    rm -rf /tmp/fam /opt/fam/site/demo; \
    cp -a /opt/fam/site/static/images /opt/fam/images

FROM wordpress AS production
# WP-CLI, for looking around with podman exec; what it changes lasts only
# until the container restarts
COPY --from=cli /usr/local/bin/wp /usr/local/bin/wp
COPY --from=site /var/www/html /var/www/html
COPY --from=site /opt/fam/site.sqlite /opt/fam/site.sqlite
COPY --from=site /opt/fam/images /opt/fam/images
COPY docker/start.sh /opt/fam/start.sh
# After docker-php.conf (which hands .php to PHP), so its AllowOverride None wins
COPY docker/apache.conf /etc/apache2/conf-enabled/zz-forbes-and-moreword.conf
# Port 8080, so Apache runs as www-data from the start, with no root and no
# capabilities; its run files go in /tmp
RUN set -eux; \
    sed -i 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf; \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-enabled/000-default.conf; \
    chown -R root:root /var/www/html /opt/fam; \
    chmod -R a+rX,go-w /var/www/html /opt/fam
ENV APACHE_RUN_DIR=/tmp/apache2/run \
    APACHE_LOCK_DIR=/tmp/apache2/lock \
    APACHE_PID_FILE=/tmp/apache2/apache2.pid
USER www-data
EXPOSE 8080
# Podman ignores HEALTHCHECK in OCI-format images; the fleet's Quadlet unit
# repeats this command as HealthCmd=.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
  CMD ["curl", "-fsS", "-o", "/dev/null", "http://127.0.0.1:8080/"]
ENTRYPOINT ["/opt/fam/start.sh"]
