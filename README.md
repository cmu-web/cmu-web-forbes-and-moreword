# Forbes & Moreword

A WordPress block theme for Carnegie Mellon websites, built from Carnegie
Mellon's web template and the
[Carnegie Mellon Web Kilt](https://webkilt.andrew.cmu.edu/). Write in the
block editor, and your pages come out looking like cmu.edu. It's named for
the corner of Forbes and Morewood avenues, with more words.

Docs: https://forbesandmoreword.andrew.cmu.edu/

![A home page made with the theme: the CMU roof, the site's name and tabs, then a split hero.](images/home.jpg)

## Install

1. Download `forbes-and-moreword.zip` from the
   [latest release](https://github.com/cmu-web/cmu-web-forbes-and-moreword/releases/latest).
2. In WordPress, select **Appearance › Themes › Add New Theme › Upload
   Theme**, choose the file and select **Install Now**.
3. Select **Activate**.

It works on WordPress 6.6 or later, with PHP 8.1 or later. Visitors'
browsers load CMU's template from www.cmu.edu and the kit from jsDelivr.

## What You Get

| In WordPress | On the Page |
| --- | --- |
| Every page | CMU's roof, the site's name, tabs for its pages and the footer |
| Site title and tagline | The name at the top and the line under it |
| Site Logo | Your unit's official logo, to the right of the name |
| Posts | The kit's Article at the top: category, date, headline, summary and byline |
| The Full-Width Sections page template | A page made of full-width sections, like the kit's home pages |
| Patterns, under Carnegie Mellon Web Kilt | Split Hero, Text Section, Cards on Gray, Leadership Grid, Callout and Numbered Steps |
| Button styles | Button, Ghost Button and Call to Action |
| Heading style | With a Line, for section headings |
| Header template parts | A red, gray or white roof |

![The block editor with the theme's patterns open beside a page using the Leadership Grid.](images/editor.jpg)

## How It Works

The theme adds no CSS of its own. Its look comes from CMU's template and
the kit, loaded in `functions.php`; the kit is pinned to one release with
subresource integrity, so a changed file is refused. To make the template's
styles and script work, `functions.php` also turns a few blocks into the
template's markup:

- Buttons become the template's `btn`, `btn ghost` and `cta` links.
- The post content goes into the page without WordPress's wrapper, since the
  template lays out the page's sections by their place in the page.
- The site's name and tagline become the template's `#sitename`, with the
  logo, if there is one, linked right after them.
- A post's top becomes the kit's Article, written from the post's category,
  date, hand-written excerpt and author. Its styles come with Web Kilt
  0.7.0; until the theme moves to it, the parts show in the template's
  plain styles.
- The navigation lists the pages, without the home page, and marks the
  current one.
- The footer's copyright line keeps the current year.
- Pages render without WordPress's `wp-site-blocks` wrapper (`canvas.php`),
  so the template's menu opens.
- The kit's `cmu-app.css` is registered as `fam-kit-app` but not loaded;
  add `wp_enqueue_style( 'fam-kit-app' )` to use the kit's App components.

## Known Gaps

- No breadcrumbs yet.
- The navigation lists pages only, in their order; it doesn't use the
  Navigation block's menus.
- In the block editor, buttons look like WordPress's buttons, not the
  template's; the page shows the template's.
- A list of posts is indented the way the template indents lists.
- No dark mode, search or comments.

## The Docs Site

forbesandmoreword.andrew.cmu.edu runs this theme. Everything on it comes
from `site/`: `seed.sh` builds it with WP-CLI on an empty database, from
the pages in `site/pages/`, the News posts in `site/posts/`, the images in
`site/images/` and the site's own footer and 404 in `site/parts/` and
`site/templates/`. Files in `site/static/` (the home page's street signs and
the link preview images) are served as they are, at `/images/`. To change the site, change those files. Nobody
signs in: `site/mu-plugins/` refuses every sign-in and turns off
application passwords, XML-RPC and the REST API's user list.

To run a copy on your computer, with made-up posts by Andrew Carnegie,
Margaret Morrison and Andrew Mellon and a placeholder logo, you need Docker:

    dev/run.sh up       # http://127.0.0.1:3800/
    dev/run.sh down

For screenshots of the block editor, `dev/run.sh editor` gives the local
copy's admin account a demo password. It works only at 127.0.0.1 or
localhost, with a setting only `dev/compose.yml` turns on.

## Release

Push a version tag, such as `0.1.0`, that matches `Version:` in
`forbes-and-moreword/style.css`. The
[release workflow](.github/workflows/release.yml) zips the theme and
attaches `forbes-and-moreword.zip` to the tag's GitHub release.

## License

Copyright Carnegie Mellon University. The code and documentation use the
[BSD 3-Clause License](LICENSE). CMU's names, marks and Scotty artwork are
not covered by that license, and CMU's web template is separate from this
repository.
