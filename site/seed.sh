#!/usr/bin/env bash
# Builds the Forbes & Moreword docs site from this folder with WP-CLI, on an
# empty WordPress database. Everything on the site comes from here: the pages
# in pages/, the News posts in posts/, the site's own footer and 404 in parts/
# and templates/, the images in images/, and with --demo the made-up posts in
# demo/. Nothing is edited in WordPress, so the database can be thrown away
# and rebuilt at any time.
#
#   seed.sh URL [--demo]
#
# Run it in the WordPress folder (WP-CLI's working directory), with the theme
# in wp-content/themes/forbes-and-moreword and site/mu-plugins in
# wp-content/mu-plugins. The admin account WordPress requires gets a random
# password that is never printed or saved: nobody signs in.
set -euo pipefail

here=$(cd "$(dirname "$0")" && pwd)
url=${1:?usage: seed.sh URL [--demo]}
demo=${2:-}
random() { head -c 48 /dev/urandom | base64 | tr -d '\n/+='; }

if wp core is-installed 2>/dev/null; then
  echo "seed.sh: WordPress is already installed here; seed an empty database." >&2
  exit 1
fi
# The password goes in on standard input, never on the command line
random | wp core install --url="$url" --title="Forbes & Moreword" \
  --admin_user=fam-admin --admin_email=fam-admin@example.edu \
  --skip-email --prompt=admin_password >/dev/null
wp user update fam-admin --display_name="CMU Web" --quiet

wp option update blogdescription "You have the write of way." --quiet
wp option update timezone_string America/New_York --quiet
wp option update default_comment_status closed --quiet
wp option update default_ping_status closed --quiet
wp option update users_can_register 0 --quiet
wp rewrite structure '/%postname%/' --quiet
wp theme activate forbes-and-moreword --quiet
wp plugin delete --all --quiet 2>/dev/null || true
wp theme delete $(wp theme list --status=inactive --field=name) --quiet 2>/dev/null || true
wp post delete $(wp post list --post_type=post,page --post_status=any --format=ids) --force --quiet 2>/dev/null || true

# Images: into the media library, then {{image:name}} in a page is its address
declare -A image image_id
for f in "$here"/images/*; do
  [ -f "$f" ] || continue
  id=$(wp media import "$f" --porcelain)
  image[$(basename "$f")]=$(wp post get "$id" --field=guid)
  image_id[$(basename "$f")]=$id
done

# A file's header, between <!-- and -->, holds "name: value" lines
field() { sed -n '1,/^-->/p' "$1" | sed -n "s/^$2: *//p" | head -1; }
body() {
  local text
  text=$(sed '1,/^-->/d' "$1")
  for name in "${!image[@]}"; do text=${text//"{{image:$name}}"/${image[$name]}}; done
  printf '%s\n' "$text"
}
create() { # file, post type
  local file=$1 type=$2 args=() tmp
  tmp=$(mktemp)
  body "$file" >"$tmp"
  args=(--post_type="$type" --post_status=publish --post_title="$(field "$file" title)"
    --post_name="$(field "$file" slug)" --porcelain)
  [ -n "$(field "$file" order)" ] && args+=(--menu_order="$(field "$file" order)")
  [ -n "$(field "$file" template)" ] && args+=(--page_template="$(field "$file" template)")
  [ -n "$(field "$file" date)" ] && args+=(--post_date="$(field "$file" date)")
  [ -n "$(field "$file" author)" ] && args+=(--post_author="$(wp user get "$(field "$file" author)" --field=ID)")
  [ -n "$(field "$file" category)" ] && args+=(--post_category="$(field "$file" category)")
  [ -n "$(field "$file" excerpt)" ] && args+=(--post_excerpt="$(field "$file" excerpt)")
  local id
  id=$(wp post create "$tmp" "${args[@]}")
  rm -f "$tmp"
  # A lead photo: one of images/, with its caption
  local photo
  photo=$(field "$file" image)
  if [ -n "$photo" ]; then
    wp post meta update "$id" _thumbnail_id "${image_id[$photo]:?no images/$photo}" --quiet
    [ -n "$(field "$file" caption)" ] && wp post update "${image_id[$photo]}" --post_excerpt="$(field "$file" caption)" --quiet
  fi
  # A post's picture when it has no photo of its own: a PlaceMellon
  # placeholder photo in the header's category, for the news grid; random, so
  # two posts in one category don't get the same photo
  local placeholder
  placeholder=$(field "$file" placeholder)
  if [ -n "$placeholder" ]; then
    local photo_id
    photo_id=$(wp media import "https://placemellon.andrew.cmu.edu/1200/800.jpg?category=$placeholder&random" \
      --title="Placeholder photo" --alt="Placeholder photo of the Carnegie Mellon campus." --porcelain)
    wp post meta update "$id" _thumbnail_id "$photo_id" --quiet
  fi
  echo "$id"
}

for f in "$here"/pages/*.html; do
  id=$(create "$f" page)
  [ "$(field "$f" slug)" = home ] && front=$id
done
# The site's own template parts, such as its footer, in place of the theme's
for f in "$here"/parts/*.html; do
  [ -f "$f" ] || continue
  part=$(basename "$f" .html)
  id=$(wp post create "$f" --post_type=wp_template_part --post_status=publish \
    --post_title="$part" --post_name="$part" --porcelain)
  wp post term set "$id" wp_theme forbes-and-moreword --quiet
  wp post term set "$id" wp_template_part_area "$part" --quiet
done
# The site's menu, which the navigation shows: a tab's label is the menu's,
# not the page's title, so the two can differ. Full addresses under the
# site's root, since the menu shows at every depth
menu=""
for tab in "install:Install" "patterns:Patterns" "news:News" "about:About"; do
  menu+="<!-- wp:navigation-link {\"label\":\"${tab#*:}\",\"url\":\"${url%/}/${tab%%:*}/\",\"kind\":\"custom\"} /-->"
done
wp post create --post_type=wp_navigation --post_status=publish --post_title="Main Menu" \
  --post_content="$menu" --porcelain >/dev/null
# The site's own templates, such as its 404 page, in place of the theme's
for f in "$here"/templates/*.html; do
  [ -f "$f" ] || continue
  name=$(basename "$f" .html)
  sed "s#{{url}}#${url%/}#g" "$f" >"$f.tmp"
  id=$(wp post create "$f.tmp" --post_type=wp_template --post_status=publish \
    --post_title="$name" --post_name="$name" --porcelain)
  rm -f "$f.tmp"
  wp post term set "$id" wp_theme forbes-and-moreword --quiet
done
wp option update show_on_front page --quiet
wp option update page_on_front "${front:?pages/ needs a page with slug: home}" --quiet

# News: posts about the theme, by a made-up writer whose password is never
# saved, in the category named in each post's header
wp user create mm00 mm00@example.edu --display_name="Margaret Morrison" --role=author --user_pass="$(random)" --porcelain >/dev/null
for f in "$here"/posts/*.html; do
  c=$(field "$f" category)
  [ -n "$c" ] && { wp term create category "$c" --porcelain >/dev/null 2>&1 || true; }
  create "$f" post >/dev/null
done
wp option update page_for_posts "$(wp post list --post_type=page --name=news --field=ID)" --quiet

if [ "$demo" = --demo ]; then
  # Made-up people and posts, for trying the theme; never on the docs site
  wp user create ac00 ac00@example.edu --display_name="Andrew Carnegie" --role=author --user_pass="$(random)" --porcelain >/dev/null
  wp user create am00 am00@example.edu --display_name="Andrew Mellon" --role=author --user_pass="$(random)" --porcelain >/dev/null
  for f in "$here"/demo/posts/*.html; do create "$f" post >/dev/null; done
  # A placeholder logo from PlaceMellon (Scotty), never a real unit's logo
  logo=$(wp media import "https://placemellon.andrew.cmu.edu/110.png?scotty=bust" \
    --title="Logo placeholder" --alt="Logo placeholder" --porcelain)
  wp option update site_logo "$logo" --quiet
  wp theme mod set custom_logo "$logo" --quiet
fi
wp rewrite flush --quiet
echo "seed.sh: built $(wp post list --post_type=page --format=count) pages and $(wp post list --post_type=post --format=count) posts"
