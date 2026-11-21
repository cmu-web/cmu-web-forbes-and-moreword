# Development

Set up your computer once with
[Working on CMU Web Projects](https://github.com/cmu-web/.github/blob/main/DEVELOPMENT.md);
this page covers this project's own commands.

Forbes & Moreword is a WordPress block theme, in `forbes-and-moreword/`.
`docs/` is the source of its docs site, and `dev/` runs a local copy of that
site in Docker.

## Run It

To run a copy of the docs site on your computer, with made-up posts by
Andrew Carnegie, Margaret Morrison and Andrew Mellon and a placeholder logo,
you need Docker:

```sh
dev/run.sh up       # http://127.0.0.1:3800/
dev/run.sh down
```

For screenshots of the block editor, `dev/run.sh editor` gives the local
copy's admin account a demo password. It works only at 127.0.0.1 or
localhost, with a setting only `dev/compose.yml` turns on.

## How the Theme Works

The theme adds no CSS of its own. Its look comes from CMU's template and
the kit, loaded in `functions.php`; the kit is pinned to one release with
subresource integrity, so a changed file is refused. To make the template's
styles and script work, `functions.php` also turns a few blocks into the
template's markup:

- Buttons become the template's `btn`, `btn ghost` and `cta` links. A button
  that links to a file, such as a `.zip` or `.pdf` or a GitHub release
  download, becomes a download link (`download` and `data-cmu-download`), so
  the kit's script can say "Downloaded" after a click.
- The post content goes into the page without WordPress's wrapper, since the
  template lays out the page's sections by their place in the page.
- The site's name and tagline become the template's `#sitename`, with the
  logo, if there is one, linked right after them.
- A post's top becomes the kit's Article, written from the post's category,
  date, hand-written excerpt and author.
- Every page but the home page starts with the template's breadcrumbs: the
  site's name, the page's parent pages (or News, for a post), then the page.
- The Latest Posts block and the News page's Query Loop become the
  template's News Grid, three stories to a row. The block's settings still
  work: how many stories, the categories, author and order, and whether to
  show each story's featured image, date ("Monday, Nov. 16, 2026") and
  sentence. Put the block in a group with the `grid column3 news` class, after
  a level 1 heading, to give the grid a heading; a paragraph with
  `cmu-grid-full` after it, such as a link to all the news, runs across the
  grid. When no story has a picture, the stories show without one; when some
  do, the rest get the template's gray picture box, so the rows line up.
- Dates in other lists of posts follow AP style, as the Article's does:
  Nov. 15, 2026.
- The navigation is the site's menu (**Appearance › Editor › Navigation**),
  so a tab's label needn't match its page's title and a tab can link
  anywhere. It's written as the template's own list, with the current tab
  marked and the home page left out. With no menu yet, it lists the pages.
- The footer's copyright line keeps the current year.
- Pages render without WordPress's `wp-site-blocks` wrapper (`canvas.php`),
  so the template's menu opens.
- The kit's `cmu-app.css` loads on every page, with `cmu-app` on the body,
  so forms (comments, search, plugins' forms), flash messages and the pager
  get the kit's styles.
- Every page gets link preview tags, following the kit's Link Previews
  standard: "Page - Site" as the title, the page's excerpt (or its opening
  words) as the description, and a picture. A post with a featured image uses
  it; everything else uses the Preview Image you choose under **Appearance ›
  Customize › Link Previews**, a JPEG, 1200 × 630 pixels and under 300 KB.
  When Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework is
  active, the theme writes none, since the plugin writes its own. The
  `fam_link_preview` filter changes any field.
- In the block editor, the template's font and styles and the kit's load
  too, and `editor.js` gives buttons, calls to action and headings with a line
  the template's look, read from the template's own styles.

The template uses WordPress's jQuery, including its global `$` alias, instead
of loading another version. Button conversions preserve link settings,
anchors and accessibility attributes.

## The Docs Site

The docs site, themes.andrew.cmu.edu/forbes-and-moreword/0/, runs this
theme. Everything on it comes from `docs/`: `seed.sh` builds it with WP-CLI on an empty database, from
the pages in `docs/pages/`, the News posts in `docs/posts/`, the images in
`docs/images/` and the site's own footer and 404 in `docs/parts/` and
`docs/templates/`. Files in `docs/static/images/` (the street sign artwork and
the link preview images) are served as they are, at `/images/`. To change the
site, change those files. Nobody signs in: `docs/mu-plugins/` refuses every
sign-in and turns off application passwords, XML-RPC and the REST API's user
list.

The site demonstrates the theme through its own pages: a silent video hero,
full-width sections, cards and a live news feed. Installation instructions
and the agent setup prompt live on Install. A video hero loads YouTube's
player and the template's video script only on pages that use one; its
photo remains visible on phones and when the video cannot play.

## Check a Change

The everyday check is `dev/run.sh check`: it needs only Docker, with the
local site running (`dev/run.sh up`, which downloads WordPress and MariaDB the
first time). It checks the actual WordPress block rendering, link settings, script
dependencies, navigation and Article markup.

### Browser Tests

The browser tests are separate. They check desktop and mobile pages in
Chromium with Playwright: the mobile menu, the News Grid, a plugin attached
to WordPress's jQuery and the agent prompt's copy button. With the local site
running and Node installed:

```sh
npm ci
npm run test:browser
```

The first time, install Playwright's browser as the setup page describes.

Or, with nothing to install but Docker, `dev/run.sh browser` runs the same
tests in Playwright's image, as your user, so its screenshots stay yours.
They save screenshots in `test-results/` (or the folder in
`FAM_TEST_RESULTS`) and use gray fixtures for PlaceMellon images.

The Check Code workflow runs both on WordPress 6.6 and 7.0.4, on every push
and pull request. It also checks the PHP, `theme.json`, `editor.js` and the
seed script's syntax, and compares the kit's integrity hashes with the
pinned release's manifest.

## Release

The tag matches `Version:` in `forbes-and-moreword/style.css`. Set it,
commit, tag the commit (e.g., `0.1.4`), push the tag and create the tag's
GitHub release with its notes. The tag starts two workflows:

- **Attach Release Files** zips the theme and attaches
  `forbes-and-moreword.zip` to the release once it exists.
- **Publish Image** builds the docs site's image, with the site seeded into
  it, and publishes it to `ghcr.io/cmu-web/cmu-web-forbes-and-moreword`.
