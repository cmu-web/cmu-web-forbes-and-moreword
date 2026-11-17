/**
 * Forbes & Moreword in the block editor: buttons, ghost buttons, calls to
 * action and headings with a line look as they do on the site.
 *
 * On the site, PHP writes the template's own markup (a.btn, a.cta, h2.rule-top)
 * and the template styles it. The editor has its own markup (a button is an
 * editable div), so the template's styles don't reach it. Rather than copy the
 * template's values into the theme, this reads them from hidden samples of the
 * template's markup, in the editor with the template's stylesheet, and gives
 * the editor's markup the same look. The theme still adds no CSS of its own.
 */
(function () {
	'use strict';
	var PROPS = [
		'display', 'background-color', 'color', 'border-top', 'border-right', 'border-bottom',
		'border-left', 'border-radius', 'padding-top', 'padding-right', 'padding-bottom',
		'padding-left', 'margin-top', 'font-family', 'font-size', 'font-weight', 'line-height',
		'letter-spacing', 'text-transform', 'text-decoration', 'box-shadow',
	];
	var AFTER = ['content', 'display', 'margin-left', 'font-size', 'line-height', 'vertical-align'];
	// What to copy from each sample to the editor's markup
	var KINDS = [
		{
			sample: '<p><a class="btn" href="#">Button</a></p>',
			target: '.wp-block-button:not([class*="is-style-"]) > .wp-block-button__link, .wp-block-button.is-style-btn > .wp-block-button__link',
		},
		{ sample: '<p><a class="btn ghost" href="#">Button</a></p>', target: '.wp-block-button.is-style-ghost > .wp-block-button__link' },
		{ sample: '<p><a class="cta" href="#">Call</a></p>', target: '.wp-block-button.is-style-cta > .wp-block-button__link', after: true },
	];
	var HEADINGS = ['h1', 'h2', 'h3', 'h4'];

	function rules(el, pseudo, list) {
		var c = getComputedStyle(el, pseudo || null);
		return list
			.map(function (p) {
				var v = c.getPropertyValue(p);
				return v ? p + ':' + v + ' !important' : '';
			})
			.filter(Boolean)
			.join(';');
	}

	function build() {
		// Samples sit in a hidden #content, where the template's styles apply
		var box = document.createElement('div');
		box.id = 'content';
		box.setAttribute('aria-hidden', 'true');
		box.style.cssText = 'position:absolute;left:-9999px;top:0;width:800px;visibility:hidden';
		document.body.appendChild(box);
		var css = '';
		KINDS.forEach(function (k) {
			box.innerHTML = k.sample;
			var a = box.querySelector('a');
			css += k.target + '{' + rules(a, null, PROPS) + '}';
			if (k.after) {
				css += k.target.replace(/, /g, '::after, ') + '::after{' + rules(a, '::after', AFTER) + '}';
			}
		});
		HEADINGS.forEach(function (h) {
			box.innerHTML = '<div class="content"><div><p>Text</p><' + h + ' class="rule-top">Heading</' + h + '></div></div>';
			var el = box.querySelector(h);
			css += h + '.is-style-rule-top{' + rules(el, null, ['border-top', 'padding-top', 'margin-top']) + '}';
		});
		box.remove();
		var style = document.getElementById('fam-editor-look') || document.createElement('style');
		style.id = 'fam-editor-look';
		style.textContent = css;
		document.head.appendChild(style);
	}

	// After the stylesheets have loaded, so the samples have the template's look
	if (document.readyState === 'complete') build();
	else window.addEventListener('load', build);
})();
