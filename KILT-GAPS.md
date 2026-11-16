# Web Kilt Gaps Found While Building Forbes & Moreword

Things the theme would use if the Web Kilt had them. The theme adds no CSS
of its own, so these wait for the kit.

- **A list of posts.** A news or blog list (title as a link, date, a
  sentence) with no list indent. The template indents list items, so the
  query loop's list sits about 20 pixels in.
- **Breadcrumbs for a CMS without them built in.** The template has the
  look; the theme would need a block that writes the trail.
- ~~**A byline.**~~ Web Kilt 0.7.0 adds the Article component (category,
  date, headline, summary and byline); the theme uses it.
- **Buttons in the block editor.** The editor shows WordPress's own button
  styles; a small editor stylesheet from the kit (for `.wp-block-button`
  with the btn, ghost and cta styles) would make the editor match the page.
- **The hero's note.** The split hero's `cmu-cta-note` sits inside
  `cmu-cta-row`; WordPress's Buttons block holds only buttons, so the theme's
  pattern leaves the note out. A version of the note that follows the row
  would fit.
