// Check the rendered docs site, including the template and plugin compatibility
const assert = require('node:assert/strict');
const fs = require('node:fs/promises');
const path = require('node:path');
const { chromium } = require('playwright');

const base = process.env.FAM_TEST_URL || 'http://127.0.0.1:4200';
assert.ok(['127.0.0.1', 'localhost'].includes(new URL(base).hostname), 'Use the local test site');
// Screenshots go to test-results/, or FAM_TEST_RESULTS when it's set
const directory = process.env.FAM_TEST_RESULTS || path.join(__dirname, '../test-results');

(async () => {
  await fs.mkdir(directory, { recursive: true });
  const browser = await chromium.launch();
  try {
    const context = await browser.newContext({ permissions: ['clipboard-read', 'clipboard-write'] });
    // Model a plugin that attaches itself to the jQuery supplied by WordPress
    await context.route(/\/wp-includes\/js\/jquery\/jquery(?:\.min)?\.js(?:\?|$)/, async (route) => {
      const response = await route.fetch();
      await route.fulfill({
        response,
        body: `${await response.text()}\nwindow.jQuery.fn.famPluginProbe = function () { return this; };`,
      });
    });
    // Keep image selection deterministic without calling the photo service
    await context.route('https://placemellon.andrew.cmu.edu/**', (route) => {
      const parts = new URL(route.request().url()).pathname.split('/').filter(Boolean);
      const width = parseInt(parts[0], 10) || 600;
      const height = parseInt(parts[1], 10) || width;
      return route.fulfill({
        contentType: 'image/svg+xml',
        body: `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}"><rect width="100%" height="100%" fill="#e0e0e0"/></svg>`,
      });
    });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    async function visit(url) {
      const response = await page.goto(new URL(url, base).href, { waitUntil: 'load' });
      assert.equal(response.status(), 200, `Failed page: ${url}`);
      await page.waitForFunction(() => !document.body.classList.contains('no-js'));
      assert.equal(await page.locator('body > #container').count(), 1, 'The CMU canvas must be a body child');
      assert.equal(await page.evaluate(() => typeof window.jQuery.fn.famPluginProbe), 'function', 'Keep plugins attached to WordPress jQuery');
      assert.equal(await page.evaluate(() => window.$ === window.jQuery), true, 'Use the same jQuery instance');
      assert.equal(await page.locator('script[src*="jquery-1.10.1"]').count(), 0, 'Do not load legacy jQuery');
    }
    await page.setViewportSize({ width: 1300, height: 1000 });
    await visit('/news/');
    // The news index is the template's News Grid: stories as linked cards
    assert.equal(await page.locator('#content > .grid.news').count(), 1, 'Show the news as the News Grid');
    const article = await page.locator('#content .grid.news > div > a').first().getAttribute('href');
    assert.ok(article, 'The news index needs an article link');
    const pages = [['home', '/'], ['install', '/install/'], ['patterns', '/patterns/'], ['news', '/news/'], ['about', '/about/'], ['article', article]];
    for (const [name, url] of pages) {
      for (const width of [1300, 390]) {
        await page.setViewportSize({ width, height: 1000 });
        await visit(url);
        await page.addStyleTag({ content: '*, *::before, *::after { transition: none !important; animation: none !important; }' });
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), `${name} overflows at ${width}px`);
        if (name === 'home') {
          assert.equal(await page.locator('#content > .hero.tubular[data-vid="hAdHQCNtGUk"]').count(), 1, 'Render the live video hero');
          assert.equal(await page.locator('script#fam-template-video-js').count(), 1, 'Load the template video player');
        }
        if (name === 'article') {
          assert.equal(await page.locator('.cmu-article h1').count(), 1, 'Render the Article heading');
          assert.ok(await page.locator('.cmu-article .cmu-byline').textContent(), 'Render the byline');
        }
        if (width === 390) {
          await page.locator('#container header button.menu-btn').click();
          assert.equal(await page.locator('body').evaluate((body) => body.classList.contains('menu-on')), true, 'Open the mobile menu');
          await page.locator('#nav button.menu-btn').click();
          assert.equal(await page.locator('body').evaluate((body) => body.classList.contains('menu-on')), false, 'Close the mobile menu');
        }
        await page.screenshot({ path: path.join(directory, `${name}-${width}.png`), fullPage: true });
      }
    }
    await visit('/');
    const copy = page.locator('button[data-cmu-copy="#fam-agent-prompt"]');
    await copy.click();
    await page.waitForFunction(() => document.querySelector('button[data-cmu-copy="#fam-agent-prompt"]').getAttribute('data-cmu-copy-state') === 'copied');
    assert.ok((await page.evaluate(() => navigator.clipboard.readText())).includes('Forbes & Moreword'), 'Copy the agent prompt');
    assert.deepEqual(errors, [], 'Pages must not produce JavaScript errors');
    console.log('ok: desktop and mobile pages, navigation, jQuery plugin, Article and copy controls');
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
