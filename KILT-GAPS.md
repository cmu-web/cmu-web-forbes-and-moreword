# Web Kilt Gaps Found While Building Forbes & Moreword

Things the theme would use if the Web Kilt had them. The theme adds no CSS
of its own, so these wait for the kit.

- ~~**A list of posts.**~~ The kit's News Grid, the template's own, covers it:
  the theme renders the Latest Posts block and the News page's Query Loop as
  the News Grid.
- ~~**Breadcrumbs for a CMS without them built in.**~~ The theme writes the
  template's trail itself, from a marker group in each template.
- ~~**A byline.**~~ Web Kilt 0.7.0 adds the Article component (category,
  date, headline, summary and byline); the theme uses it.
- ~~**Buttons in the block editor.**~~ The editor loads the template's and
  the kit's styles, and the theme's `editor.js` gives the editor's buttons,
  calls to action and headings with a line the template's look, read from the
  template's own styles.
- ~~**The hero's note.**~~ A paragraph with `cmu-cta-note` right after a
  Buttons block with `cmu-cta-row` moves into the row as the page renders.
