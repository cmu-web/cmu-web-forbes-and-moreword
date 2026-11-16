<?php
/**
 * Forbes & Moreword: loads CMU's web template and the Carnegie Mellon Web Kilt,
 * and turns a few blocks' markup into the template's own, which its styles and
 * script expect (an #id on a wrapper, a class on a link, no extra wrapper).
 * The theme adds no CSS of its own.
 *
 * @package forbes-and-moreword
 */

defined( 'ABSPATH' ) || exit;

// The Web Kilt release the theme is tested with, and its files' hashes
// (subresource integrity), from the kit's package.json for that release.
const FAM_KIT       = '0.6.9';
const FAM_KIT_FILES = array(
	'css/cmu-cms.css' => 'sha384-9jhXiuuhmbvQJYP71Rg8e7/NBlfXA/7MjHSMxZeSbELc/fdpt3PAwyoS4jvEMXqy',
	'js/cmu-app.js'   => 'sha384-10393F76wEQPHaJEEoM9M1QEQsl9mTezDdEM45kArp0j26SWs3gxiGsXKrmwJHio',
);
const FAM_TEMPLATE  = 'https://www.cmu.edu/common/standard-v6';

function fam_kit_url( $file ) {
	return 'https://cdn.jsdelivr.net/gh/cmu-web/cmu-web-kilt@' . FAM_KIT . '/' . $file;
}

// The template first, then the kit, as the kit's Install page says. The
// template's scripts bring their own jQuery 1.10.1, separate from WordPress's.
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'fam-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,400italic,300italic,300,700,700italic', array(), null );
		wp_enqueue_style( 'fam-template', FAM_TEMPLATE . '/css/main.6.7.min.css', array(), null, 'screen' );
		wp_enqueue_style( 'fam-template-print', FAM_TEMPLATE . '/css/print.min.css', array(), null, 'print' );
		wp_enqueue_style( 'fam-kit', fam_kit_url( 'css/cmu-cms.css' ), array( 'fam-template' ), null );
		wp_enqueue_script( 'fam-template-list', FAM_TEMPLATE . '/js/list.min.js', array(), null, true );
		wp_enqueue_script( 'fam-template-jquery', FAM_TEMPLATE . '/js/jquery-1.10.1.min.js', array(), null, true );
		wp_enqueue_script( 'fam-template', FAM_TEMPLATE . '/js/main.6.7.min.js', array( 'fam-template-list', 'fam-template-jquery' ), null, true );
		wp_enqueue_script( 'fam-kit', fam_kit_url( 'js/cmu-app.js' ), array( 'fam-template' ), null, true );
	}
);

// Subresource integrity on the kit's files, so a changed file is refused
function fam_integrity( $tag, $handle, $attr ) {
	$hash = array( 'fam-kit' => array( 'link' => 'css/cmu-cms.css', 'script' => 'js/cmu-app.js' ) );
	if ( ! isset( $hash[ $handle ][ $attr ] ) ) {
		return $tag;
	}
	$sri = FAM_KIT_FILES[ $hash[ $handle ][ $attr ] ];
	return str_replace( '<' . $attr . ' ', '<' . $attr . ' integrity="' . esc_attr( $sri ) . '" crossorigin="anonymous" ', $tag );
}
add_filter( 'style_loader_tag', fn( $tag, $handle ) => fam_integrity( $tag, $handle, 'link' ), 10, 2 );
add_filter( 'script_loader_tag', fn( $tag, $handle ) => fam_integrity( $tag, $handle, 'script' ), 10, 2 );

// The editor shows blocks in the template's and the kit's styles too
add_action(
	'after_setup_theme',
	function () {
		add_editor_style( array( FAM_TEMPLATE . '/css/main.6.7.min.css', fam_kit_url( 'css/cmu-cms.css' ) ) );
		remove_theme_support( 'core-block-patterns' );
	}
);

// "Page - Site - Carnegie Mellon University," as on cmu.edu, built here so
// WordPress doesn't turn the hyphens into dashes
add_filter(
	'pre_get_document_title',
	function () {
		$parts = array( get_bloginfo( 'name' ), 'Carnegie Mellon University' );
		if ( ! is_front_page() ) {
			array_unshift( $parts, is_404() ? __( 'Page Not Found', 'forbes-and-moreword' ) : ( ( is_singular() || is_home() ) ? single_post_title( '', false ) : wp_strip_all_tags( get_the_archive_title() ) ) );
		}
		return esc_html( implode( ' - ', array_filter( $parts ) ) );
	}
);

// The template's script takes no-js off the body when it runs
add_filter( 'body_class', fn( $classes ) => array_merge( $classes, array( 'no-js' ) ) );

// The kit's names for core blocks' styles: a button is a btn, a ghost btn or
// a call to action (cta); a heading can have the section line (rule-top).
add_action(
	'init',
	function () {
		register_block_style( 'core/button', array( 'name' => 'btn', 'label' => __( 'Button', 'forbes-and-moreword' ), 'is_default' => true ) );
		register_block_style( 'core/button', array( 'name' => 'ghost', 'label' => __( 'Ghost Button', 'forbes-and-moreword' ) ) );
		register_block_style( 'core/button', array( 'name' => 'cta', 'label' => __( 'Call to Action', 'forbes-and-moreword' ) ) );
		register_block_style( 'core/heading', array( 'name' => 'rule-top', 'label' => __( 'With a Line', 'forbes-and-moreword' ) ) );
		register_block_pattern_category( 'cmu-kilt', array( 'label' => __( 'Carnegie Mellon Web Kilt', 'forbes-and-moreword' ) ) );
	}
);

// A button is the template's link: <a class="btn">, <a class="btn ghost"> or
// <a class="cta">, without WordPress's wrapper or its button classes.
add_filter(
	'render_block_core/button',
	function ( $html, $block ) {
		$class = $block['attrs']['className'] ?? '';
		$kind  = str_contains( $class, 'is-style-cta' ) ? 'cta' : ( str_contains( $class, 'is-style-ghost' ) ? 'btn ghost' : 'btn' );
		if ( ! preg_match( '/<a\b([^>]*)>(.*?)<\/a>/s', $html, $m ) ) {
			return $html;
		}
		$href = preg_match( '/\shref="([^"]*)"/', $m[1], $h ) ? $h[1] : '#';
		return '<a class="' . $kind . '" href="' . $href . '">' . trim( $m[2] ) . '</a>';
	},
	10,
	2
);

// The heading style's class is the template's own: rule-top
add_filter( 'render_block_core/heading', fn( $html ) => str_replace( 'is-style-rule-top', 'rule-top', $html ) );

// The template lays out #content's and .content's direct children, so the
// post's blocks go in without the post-content wrapper.
add_filter(
	'render_block_core/post-content',
	fn( $html ) => preg_replace( '/^\s*<div\b[^>]*>(.*)<\/div>\s*$/s', '$1', $html )
);

// The site's name and the line under it, as the template's #sitename has them
add_filter(
	'render_block_core/site-title',
	fn() => '<span class="sitetitle"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a></span>'
);
add_filter(
	'render_block_core/site-tagline',
	function () {
		$line = get_bloginfo( 'description' );
		return $line ? '<h2>' . esc_html( $line ) . '</h2>' : '';
	}
);

// The navigation: the site's name already links home, so the home page isn't
// a tab; the template marks the current page's item active.
add_filter(
	'render_block_core/page-list',
	function ( $html ) {
		$home = preg_quote( esc_url( home_url( '/' ) ), '/' );
		$html = preg_replace( '/<li\b[^>]*>\s*<a\b[^>]*href="' . $home . '"[^>]*>.*?<\/a>\s*<\/li>/s', '', $html );
		return str_replace( 'current-menu-item', 'current-menu-item active', $html );
	}
);

// The footer's copyright line keeps the current year
add_filter(
	'render_block_core/list',
	function ( $html, $block ) {
		if ( ! str_contains( $block['attrs']['className'] ?? '', 'fam-copyright' ) ) {
			return $html;
		}
		return '<ul><li>&copy; ' . esc_html( wp_date( 'Y' ) ) . ' Carnegie Mellon University</li></ul>';
	},
	10,
	2
);

// Block templates render in our canvas.php, which drops WordPress's
// div.wp-site-blocks so #container is a child of <body> and the template's
// menu works (see canvas.php)
add_filter(
	'template_include',
	function ( $template ) {
		return wp_normalize_path( $template ) === wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' )
			? __DIR__ . '/canvas.php'
			: $template;
	},
	20
);

// A post's top is the kit's Article (cmu-article): the first category as a
// label, the date over the headline, the excerpt (only one written by hand)
// as the summary, then "By" and the author. The blocks in the template show
// in the editor; on the site, PHP writes the kit's markup from the post.
// The cmu-article and cmu-byline styles arrive in Web Kilt 0.7.0; with 0.6.9
// the parts show in the template's plain styles.
function fam_ap_date( $time ) {
	// AP: abbreviate Jan., Feb., Aug., Sept., Oct., Nov. and Dec. with a date
	$month = array( 1 => 'Jan.', 'Feb.', 'March', 'April', 'May', 'June', 'July', 'Aug.', 'Sept.', 'Oct.', 'Nov.', 'Dec.' );
	return $month[ (int) wp_date( 'n', $time ) ] . ' ' . wp_date( 'j, Y', $time );
}
add_filter(
	'render_block_core/group',
	function ( $html, $block ) {
		if ( ! str_contains( $block['attrs']['className'] ?? '', 'cmu-article' ) || ! is_singular() ) {
			return $html;
		}
		$post  = get_post();
		$out   = '<div class="cmu-article">';
		$terms = array_filter( get_the_category( $post ), fn( $t ) => 'uncategorized' !== $t->slug );
		if ( $terms ) {
			$out .= '<p class="cmu-label">' . esc_html( reset( $terms )->name ) . '</p>';
		}
		$time = get_post_timestamp( $post );
		$out .= '<p class="date"><time datetime="' . esc_attr( wp_date( 'Y-m-d', $time ) ) . '">' . esc_html( fam_ap_date( $time ) ) . '</time></p>';
		$out .= '<h1>' . esc_html( get_the_title( $post ) ) . '</h1>';
		if ( has_excerpt( $post ) ) {
			$out .= '<p class="cmu-lead">' . esc_html( get_the_excerpt( $post ) ) . '</p>';
		}
		$out .= '<p class="cmu-byline">By ' . esc_html( get_the_author_meta( 'display_name', $post->post_author ) ) . '</p>';
		return $out . '</div>';
	},
	10,
	2
);

// The lead photo is the template's captioned photo: figure.image with the
// image's caption, if it has one
add_filter(
	'render_block_core/post-featured-image',
	function ( $html, $block ) {
		if ( ! str_contains( $block['attrs']['className'] ?? '', 'fam-lead-photo' ) || ! has_post_thumbnail() ) {
			return $html;
		}
		$id      = get_post_thumbnail_id();
		$caption = wp_get_attachment_caption( $id );
		return '<figure class="image">' . wp_get_attachment_image( $id, 'large' )
			. ( $caption ? '<figcaption>' . esc_html( $caption ) . '</figcaption>' : '' ) . '</figure>';
	},
	10,
	2
);
