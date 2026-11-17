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
const FAM_KIT       = '0.8.9';
const FAM_KIT_FILES = array(
	'css/cmu-cms.css' => 'sha384-zV4Oiwe8ikYWEc5ApP5p49KYu9glzcYVwoCr90Jw3csuQOQt9U/RRarEyL/ZvGgd',
	'css/cmu-app.css' => 'sha384-0k+Fq/HOfWMJfjLdEWk0w9Y9wf2SN+TAxLSCc5FtIYooZOQZOpAnhfe0fHre8ZCf',
	'js/cmu-app.js'   => 'sha384-OUj0Xw1O/00sPRbiU9tY0EIto5q/A7Nn7ZxhbEwk2B/5lcnAbLldNxyL52FO+MSK',
);
const FAM_TEMPLATE  = 'https://www.cmu.edu/common/standard-v6';

function fam_kit_url( $file ) {
	return 'https://cdn.jsdelivr.net/gh/cmu-web/cmu-web-kilt@' . FAM_KIT . '/' . $file;
}

// Use CMU's icons unless the site has chosen its own in WordPress
add_action(
	'wp_head',
	function () {
		if ( has_site_icon() ) {
			return;
		}
		echo '<link rel="icon" href="https://www.cmu.edu/favicon.ico" />' . "\n";
		echo '<link rel="apple-touch-icon" href="https://www.cmu.edu/favicon-152.png" />' . "\n";
	},
	100
);

// The template first, then the kit, as the kit's Install page says. Use
// WordPress's jQuery so the template and plugins share the same instance.
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'fam-open-sans', 'https://fonts.googleapis.com/css?family=Open+Sans:400,400italic,300italic,300,700,700italic', array(), null );
		wp_enqueue_style( 'fam-template', FAM_TEMPLATE . '/css/main.6.7.min.css', array(), null, 'screen' );
		wp_enqueue_style( 'fam-template-print', FAM_TEMPLATE . '/css/print.min.css', array(), null, 'print' );
		wp_enqueue_style( 'fam-kit', fam_kit_url( 'css/cmu-cms.css' ), array( 'fam-template' ), null );
		// The kit's app layer: forms (comments, search, sign-in, plugins' forms),
		// flash messages, the pager. WordPress sites are apps often enough that
		// every page gets it, with cmu-app on the body.
		wp_enqueue_style( 'fam-kit-app', fam_kit_url( 'css/cmu-app.css' ), array( 'fam-kit' ), null );
		wp_enqueue_script( 'fam-template-list', FAM_TEMPLATE . '/js/list.min.js', array( 'jquery' ), null, true );
		// CMU's scripts expect the global $ alias that WordPress releases
		wp_add_inline_script( 'fam-template-list', 'window.$ = window.jQuery;', 'before' );
		wp_enqueue_script( 'fam-template', FAM_TEMPLATE . '/js/main.6.7.min.js', array( 'fam-template-list' ), null, true );
		wp_enqueue_script( 'fam-kit', fam_kit_url( 'js/cmu-app.js' ), array( 'fam-template' ), null, true );
	}
);

// Subresource integrity on the kit's files, so a changed file is refused
function fam_integrity( $tag, $handle, $attr ) {
	$hash = array(
		'fam-kit'     => array( 'link' => 'css/cmu-cms.css', 'script' => 'js/cmu-app.js' ),
		'fam-kit-app' => array( 'link' => 'css/cmu-app.css' ),
	);
	if ( ! isset( $hash[ $handle ][ $attr ] ) ) {
		return $tag;
	}
	$sri = FAM_KIT_FILES[ $hash[ $handle ][ $attr ] ];
	return str_replace( '<' . $attr . ' ', '<' . $attr . ' integrity="' . esc_attr( $sri ) . '" crossorigin="anonymous" ', $tag );
}
add_filter( 'style_loader_tag', fn( $tag, $handle ) => fam_integrity( $tag, $handle, 'link' ), 10, 2 );
add_filter( 'script_loader_tag', fn( $tag, $handle ) => fam_integrity( $tag, $handle, 'script' ), 10, 2 );

// The editor shows blocks in the template's font and styles and the kit's,
// the same files the site loads
add_action(
	'after_setup_theme',
	function () {
		add_editor_style(
			array(
				'https://fonts.googleapis.com/css?family=Open+Sans:400,400italic,300italic,300,700,700italic',
				FAM_TEMPLATE . '/css/main.6.7.min.css',
				fam_kit_url( 'css/cmu-cms.css' ),
				fam_kit_url( 'css/cmu-app.css' ),
			)
		);
		remove_theme_support( 'core-block-patterns' );
		add_theme_support( 'custom-logo' );
	}
);

// In the editor, buttons, calls to action and headings with a line take the
// template's look (editor.js says how); block assets load inside the editor
add_action(
	'enqueue_block_assets',
	function () {
		if ( is_admin() ) {
			wp_enqueue_script( 'fam-editor', get_theme_file_uri( 'editor.js' ), array(), wp_get_theme()->get( 'Version' ), true );
		}
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
add_filter( 'body_class', fn( $classes ) => array_merge( $classes, array( 'no-js', 'cmu-app' ) ) );

// Silent video heroes use the template's player and keep their photo fallback
add_filter(
	'render_block_core/html',
	function ( $html ) {
		$tag = new WP_HTML_Tag_Processor( $html );
		while ( $tag->next_tag( 'DIV' ) ) {
			if ( $tag->has_class( 'hero' ) && $tag->has_class( 'tubular' ) && $tag->get_attribute( 'data-vid' ) ) {
				wp_enqueue_script( 'fam-youtube', 'https://www.youtube.com/iframe_api', array( 'fam-template' ), null, true );
				wp_enqueue_script( 'fam-template-video', FAM_TEMPLATE . '/js/tubular-reload.min.js', array( 'fam-youtube' ), null, true );
				break;
			}
		}
		return $html;
	}
);

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
// <a class="cta">, without WordPress's wrapper or its button classes. Keep
// link settings, accessible names, custom attributes and formatted content.
add_filter(
	'render_block_core/button',
	function ( $html, $block ) {
		$classes = preg_split( '/\s+/', trim( $block['attrs']['className'] ?? '' ) );
		$kind    = in_array( 'is-style-cta', $classes, true ) ? array( 'cta' ) : ( in_array( 'is-style-ghost', $classes, true ) ? array( 'btn', 'ghost' ) : array( 'btn' ) );
		if ( ! preg_match( '/<(a|button)\b[^>]*>.*?<\/\1>/is', $html, $m ) ) {
			return $html;
		}
		$tag = new WP_HTML_Tag_Processor( $m[0] );
		if ( ! $tag->next_tag() ) {
			return $html;
		}
		$tag->remove_class( 'wp-block-button__link' );
		$tag->remove_class( 'wp-element-button' );
		foreach ( $kind as $class ) {
			$tag->add_class( $class );
		}
		// WordPress puts the block's anchor on the wrapper we removed
		if ( null === $tag->get_attribute( 'id' ) && ! empty( $block['attrs']['anchor'] ) ) {
			$tag->set_attribute( 'id', $block['attrs']['anchor'] );
		}
		// A link to a file to download is a download: the browser saves it, and
		// the kit's script (cmu-app.js, from 0.8.7) says "Downloaded" for a moment
		$href = (string) $tag->get_attribute( 'href' );
		$path = (string) wp_parse_url( $href, PHP_URL_PATH );
		if ( 'A' === $tag->get_tag() && $path && preg_match( '#(\.(zip|pdf|dmg|exe|msi|pkg|tar\.gz|whl|docx|xlsx|pptx|csv|webp|png|jpg|svg)$|/releases/latest/download/)#i', $path ) ) {
			if ( null === $tag->get_attribute( 'download' ) ) {
				$tag->set_attribute( 'download', true );
			}
			if ( null === $tag->get_attribute( 'data-cmu-download' ) ) {
				$tag->set_attribute( 'data-cmu-download', wp_basename( $path ) );
			}
		}
		return $tag->get_updated_html();
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

// Dates in lists of posts follow AP style, as the Article's date does:
// Nov. 15, 2026, not November 15, 2026
add_filter(
	'render_block_core/post-date',
	function ( $html, $block, $instance ) {
		$id = $instance->context['postId'] ?? get_the_ID();
		if ( ! $id ) {
			return $html;
		}
		return preg_replace( '/(<time\b[^>]*>).*?(<\/time>)/s', '$1' . esc_html( fam_ap_date( get_post_timestamp( $id ) ) ) . '$2', $html, 1 );
	},
	10,
	3
);

// Breadcrumbs, the template's trail from the site's home to the page, first
// in #content on every page but the home page. A group with the breadcrumbs
// class marks the spot in a template; on the site, PHP writes the trail:
// the site's name, the page's parent pages (or News, for a post), then the
// page itself as plain text. The steps are joined with &nbsp;&rsaquo;&nbsp;
// so a step never starts a line with the arrow.
function fam_breadcrumbs() {
	if ( is_front_page() ) {
		return '';
	}
	// WordPress gives titles with entities (&#038;); decode before escaping
	$plain = fn( $text ) => html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' );
	$link  = fn( $url, $text ) => '<a href="' . esc_url( $url ) . '">' . esc_html( $plain( $text ) ) . '</a>';
	$steps = array( $link( home_url( '/' ), get_bloginfo( 'name' ) ) );
	$news  = (int) get_option( 'page_for_posts' );
	if ( is_singular( 'post' ) && $news ) {
		$steps[] = $link( get_permalink( $news ), get_the_title( $news ) );
	}
	if ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object_id() ) ) as $parent ) {
			$steps[] = $link( get_permalink( $parent ), get_the_title( $parent ) );
		}
	}
	if ( is_404() ) {
		$here = __( 'Page Not Found', 'forbes-and-moreword' );
	} elseif ( is_home() ) {
		$here = $news ? get_the_title( $news ) : __( 'News', 'forbes-and-moreword' );
	} elseif ( is_singular() ) {
		$here = single_post_title( '', false );
	} else {
		$here = wp_strip_all_tags( get_the_archive_title() );
	}
	$steps[] = esc_html( $plain( $here ) );
	return '<div class="breadcrumbs">' . implode( '&nbsp;&rsaquo;&nbsp;', $steps ) . '</div>';
}
add_filter(
	'render_block_core/group',
	function ( $html, $block ) {
		$classes = preg_split( '/\s+/', trim( $block['attrs']['className'] ?? '' ) );
		return in_array( 'breadcrumbs', $classes, true ) ? fam_breadcrumbs() : $html;
	},
	10,
	2
);

// A post's top is the kit's Article (cmu-article): the first category as a
// label, the date over the headline, the excerpt (only one written by hand)
// as the summary, then "By" and the author. The blocks in the template show
// in the editor; on the site, PHP writes the kit's markup from the post.
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

// The site's name, the line under it and, if the site has one, its logo, in
// the template's #sitename markup. With a logo, the name and line go in a div
// and the linked logo follows it with no space between, as the Web Kilt's
// Site Name says: a space pushes the logo onto its own line. The blocks in the
// header show in the editor; on the site, PHP writes this from the settings.
add_filter(
	'render_block_core/group',
	function ( $html, $block ) {
		if ( 'sitename' !== ( $block['attrs']['anchor'] ?? '' ) ) {
			return $html;
		}
		$home = esc_url( home_url( '/' ) );
		$name = '<span class="sitetitle"><a href="' . $home . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a></span>';
		$line = get_bloginfo( 'description' );
		$name .= $line ? '<h2>' . esc_html( $line ) . '</h2>' : '';
		$logo  = (int) get_theme_mod( 'custom_logo', get_option( 'site_logo' ) );
		if ( ! $logo || ! wp_attachment_is_image( $logo ) ) {
			return '<div id="sitename">' . $name . '</div>';
		}
		$alt = get_post_meta( $logo, '_wp_attachment_image_alt', true ) ?: get_bloginfo( 'name' ) . ' logo';
		$img = wp_get_attachment_image( $logo, 'full', false, array( 'alt' => $alt, 'loading' => 'eager', 'class' => '' ) );
		return '<div id="sitename"><div>' . $name . '</div><a href="' . $home . '">' . $img . '</a></div>';
	},
	10,
	2
);

// News Grid, the template's grid of linked stories (the Web Kilt's News Grid),
// for the Latest Posts block and a Query Loop's posts: each story a picture
// (the featured image), the date, the title and a sentence, three to a row.
// The block's own settings choose what shows. When no story has a picture,
// the stories use the template's layout without one: the date, title and
// sentence alone. When some do, a story without one gets the template's gray
// picture box, so the rows stay even.
function fam_news_date( $time ) {
	// The template's style with AP months: Monday, Oct. 19, 2026
	return wp_date( 'l', $time ) . ', ' . fam_ap_date( $time );
}
function fam_news_items( $posts, $show ) {
	$out      = '';
	$pictures = $show['image'] && array_filter( $posts, fn( $p ) => (bool) get_post_thumbnail_id( $p ) );
	foreach ( $posts as $post ) {
		$item  = '';
		$image = $pictures ? get_post_thumbnail_id( $post ) : 0;
		$url   = $image ? wp_get_attachment_image_url( $image, $show['size'] ?: 'medium_large' ) : '';
		if ( $url ) {
			$alt   = (string) get_post_meta( $image, '_wp_attachment_image_alt', true );
			$item .= '<div class="img" role="img" aria-label="' . esc_attr( $alt ) . '" style="background-image:url(' . esc_url( $url ) . ')"></div>';
		} elseif ( $pictures ) {
			$item .= '<div class="img" aria-hidden="true"></div>';
		}
		if ( $show['date'] ) {
			$time  = get_post_timestamp( $post );
			$item .= '<small><time datetime="' . esc_attr( wp_date( 'Y-m-d', $time ) ) . '">' . esc_html( fam_news_date( $time ) ) . '</time></small>';
		}
		$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );
		$item .= '<h2 class="name">' . esc_html( $title ) . '</h2>';
		if ( $show['excerpt'] ) {
			// A hand-written excerpt as it is; otherwise the post's opening
			// words, as many as the block asks for
			$text = has_excerpt( $post )
				? get_the_excerpt( $post )
				: wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), $show['words'] ?: 55 );
			$item .= '<span>' . esc_html( html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) . '</span>';
		}
		$out .= '<div><a href="' . esc_url( get_permalink( $post ) ) . '">' . $item . '</a></div>';
	}
	return $out;
}
// The grid, marked so a News Grid group around it (a heading and a link to
// more, in one section) can take the stories in place of it
function fam_news_grid( $items, $columns = 3 ) {
	$columns = max( 2, min( 4, (int) $columns ) );
	return '<div class="grid column' . $columns . ' news" data-fam-news>' . $items . '</div><!-- /fam-news -->';
}

// The Latest Posts block, with its settings: how many, which categories and
// author, the order, and whether to show the picture, date and sentence
add_filter(
	'render_block_core/latest-posts',
	function ( $html, $block ) {
		$a    = $block['attrs'] ?? array();
		$args = array(
			'posts_per_page'      => (int) ( $a['postsToShow'] ?? 5 ),
			'post_status'         => 'publish',
			'order'               => $a['order'] ?? 'desc',
			'orderby'             => $a['orderBy'] ?? 'date',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'suppress_filters'    => false,
		);
		if ( ! empty( $a['categories'] ) ) {
			$args['category__in'] = array_map( 'intval', array_column( $a['categories'], 'id' ) );
		}
		if ( ! empty( $a['selectedAuthor'] ) ) {
			$args['author'] = (int) $a['selectedAuthor'];
		}
		$posts = get_posts( $args );
		if ( ! $posts ) {
			return '';
		}
		$show = array(
			'image'   => ! empty( $a['displayFeaturedImage'] ),
			'size'    => $a['featuredImageSizeSlug'] ?? '',
			'date'    => ! empty( $a['displayPostDate'] ),
			'excerpt' => ! empty( $a['displayPostContent'] ),
			'words'   => (int) ( $a['excerptLength'] ?? 55 ),
		);
		$columns = 'grid' === ( $a['postLayout'] ?? '' ) ? ( $a['columns'] ?? 3 ) : 3;
		return fam_news_grid( fam_news_items( $posts, $show ), $columns );
	},
	10,
	2
);

// A Query Loop's posts: the blocks in its post template choose what shows (a
// featured image, a date, an excerpt), and the title always does
add_filter(
	'render_block_core/post-template',
	function ( $html, $block, $instance ) {
		$names = array();
		$walk  = function ( $blocks ) use ( &$walk, &$names ) {
			foreach ( $blocks as $b ) {
				$names[ $b['blockName'] ] = $b['attrs'] ?? array();
				$walk( $b['innerBlocks'] ?? array() );
			}
		};
		$walk( $block['innerBlocks'] ?? array() );
		// The posts the loop shows: the page's own query when it inherits it,
		// as on the News page, or the block's
		if ( ! empty( $instance->context['query']['inherit'] ) ) {
			global $wp_query;
			$posts = $wp_query->posts;
		} else {
			$page  = (int) ( $_GET[ 'query-' . ( $instance->context['queryId'] ?? 0 ) . '-page' ] ?? 1 ); // phpcs:ignore WordPress.Security.NonceVerification
			$query = new WP_Query( build_query_vars_from_query_block( $instance, max( 1, $page ) ) );
			$posts = $query->posts;
		}
		if ( ! $posts ) {
			return $html;
		}
		$show = array(
			'image'   => isset( $names['core/post-featured-image'] ),
			'size'    => $names['core/post-featured-image']['sizeSlug'] ?? '',
			'date'    => isset( $names['core/post-date'] ),
			'excerpt' => isset( $names['core/post-excerpt'] ),
			'words'   => (int) ( $names['core/post-excerpt']['excerptLength'] ?? 55 ),
		);
		return fam_news_grid( fam_news_items( $posts, $show ), $block['attrs']['layout']['columnCount'] ?? 3 );
	},
	10,
	3
);

// A group with the news class is one News Grid section: its heading, then
// the stories from the Latest Posts or Query Loop inside it, then anything
// after (such as a link to all the news), with no grid inside the grid
add_filter(
	'render_block_core/group',
	function ( $html, $block ) {
		$classes = preg_split( '/\s+/', trim( $block['attrs']['className'] ?? '' ) );
		if ( ! in_array( 'news', $classes, true ) ) {
			return $html;
		}
		return preg_replace( '/<div class="grid column\d news" data-fam-news>(.*?)<\/div><!-- \/fam-news -->/s', '$1', $html );
	},
	20,
	2
);

// A Query Loop with stories in it gives up its wrapper, so the News Grid is a
// section of #content as the template lays them out; its page links follow
// in a section of their own
add_filter(
	'render_block_core/query',
	function ( $html ) {
		if ( ! str_contains( $html, 'data-fam-news' ) && ! preg_match( '/class="[^"]*\bgrid column\d[^"]*\bnews\b/', $html ) ) {
			return $html;
		}
		$html = preg_replace( '/^\s*<div\b[^>]*>(.*)<\/div>\s*$/s', '$1', $html );
		return preg_replace( '/(<nav\b[^>]*wp-block-query-pagination.*?<\/nav>)/s', '<div class="content wide"><div>$1</div></div>', $html );
	}
);

// Link previews, as the Web Kilt's Link Previews standard has them: the page's
// title and the site's ("Page - Site"), one short sentence, the address and a
// picture. A post uses its featured image; every other page uses the site's
// Preview Image (Appearance > Customize > Link Previews), a 1200 × 630 JPEG.
// A page's sentence is its excerpt, or its opening words. Nothing is written
// when an SEO plugin is active, since it writes these tags itself and two
// sets conflict. A site can change any of it with the fam_link_preview filter.
function fam_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' );
}
add_action(
	'customize_register',
	function ( $customize ) {
		$customize->add_section(
			'fam_link_previews',
			array(
				'title'       => __( 'Link Previews', 'forbes-and-moreword' ),
				'description' => __( 'What Slack, Teams and social sites show when someone shares a link to this site.', 'forbes-and-moreword' ),
				'priority'    => 160,
			)
		);
		$customize->add_setting( 'fam_preview_image', array( 'sanitize_callback' => 'absint' ) );
		$customize->add_control(
			new WP_Customize_Media_Control(
				$customize,
				'fam_preview_image',
				array(
					'section'     => 'fam_link_previews',
					'mime_type'   => 'image',
					'label'       => __( 'Preview Image', 'forbes-and-moreword' ),
					'description' => __( 'A JPEG, 1200 × 630 pixels and under 300 KB, usually the top of the home page. Posts with a featured image use theirs.', 'forbes-and-moreword' ),
				)
			)
		);
	}
);
function fam_preview_image( $id ) {
	$src = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
	if ( ! $src ) {
		return null;
	}
	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
		'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}
function fam_link_preview_fields() {
	$plain = fn( $text ) => trim( html_entity_decode( wp_strip_all_tags( (string) $text ), ENT_QUOTES, 'UTF-8' ) );
	$site  = $plain( get_bloginfo( 'name' ) );
	$post  = get_queried_object();
	$news  = (int) get_option( 'page_for_posts' );
	$text  = $plain( get_bloginfo( 'description' ) );
	$title = $site;
	$url   = home_url( '/' );
	$type  = 'website';
	$image = fam_preview_image( (int) get_theme_mod( 'fam_preview_image' ) );
	if ( is_home() && $news ) {
		$post = get_post( $news );
	}
	if ( $post instanceof WP_Post && ( is_singular() || is_home() ) ) {
		if ( ! is_front_page() ) {
			$title = $plain( get_the_title( $post ) ) . ' - ' . $site;
		}
		$url = get_permalink( $post );
		if ( has_excerpt( $post ) ) {
			$text = $plain( get_the_excerpt( $post ) );
		} elseif ( ! is_front_page() && trim( $post->post_content ) ) {
			$text = wp_trim_words( $plain( strip_shortcodes( $post->post_content ) ), 25 );
		}
		if ( is_singular( 'post' ) ) {
			$type  = 'article';
			$image = fam_preview_image( (int) get_post_thumbnail_id( $post ) ) ?: $image;
		}
	} elseif ( is_archive() ) {
		$title = $plain( get_the_archive_title() ) . ' - ' . $site;
		$url   = home_url( add_query_arg( array() ) );
		$text  = $plain( get_the_archive_description() ) ?: $text;
	}
	$fields = array(
		'site'        => $site,
		'title'       => $title,
		'description' => $text,
		'url'         => $url,
		'type'        => $type,
		'image'       => $image,
	);
	if ( 'article' === $type ) {
		$fields['published'] = get_the_date( 'c', $post );
		$fields['author']    = get_the_author_meta( 'display_name', $post->post_author );
	}
	return apply_filters( 'fam_link_preview', $fields );
}
add_action(
	'wp_head',
	function () {
		if ( fam_seo_plugin_active() ) {
			return;
		}
		$f    = fam_link_preview_fields();
		$tags = array(
			array( 'name', 'description', $f['description'] ),
			array( 'property', 'og:site_name', $f['site'] ),
			array( 'property', 'og:title', $f['title'] ),
			array( 'property', 'og:description', $f['description'] ),
			array( 'property', 'og:url', $f['url'] ),
			array( 'property', 'og:type', $f['type'] ),
		);
		if ( ! empty( $f['image'] ) ) {
			$tags[] = array( 'property', 'og:image', $f['image']['url'] );
			$tags[] = array( 'property', 'og:image:width', (string) $f['image']['width'] );
			$tags[] = array( 'property', 'og:image:height', (string) $f['image']['height'] );
			if ( '' !== ( $f['image']['alt'] ?? '' ) ) {
				$tags[] = array( 'property', 'og:image:alt', $f['image']['alt'] );
			}
		}
		$tags[] = array( 'name', 'twitter:card', empty( $f['image'] ) ? 'summary' : 'summary_large_image' );
		if ( ! empty( $f['published'] ) ) {
			$tags[] = array( 'property', 'article:published_time', $f['published'] );
			$tags[] = array( 'name', 'author', $f['author'] );
		}
		echo "<!-- Link previews -->\n";
		foreach ( $tags as $t ) {
			if ( '' !== (string) $t[2] ) {
				printf( "<meta %s=\"%s\" content=\"%s\" />\n", $t[0], esc_attr( $t[1] ), esc_attr( $t[2] ) );
			}
		}
		echo "<!-- End of link previews -->\n";
	},
	1
);

// A list of posts' pages (Query Pagination, on the News page and archives) as
// the kit's pager: first, previous, the numbers with an ellipsis for a gap,
// next and last. Only for the page's own query; anything else keeps
// WordPress's links.
add_filter(
	'render_block_core/query-pagination',
	function ( $html, $block, $instance ) {
		global $wp_query;
		// A Query Loop with its own query paginates itself; leave it
		if ( empty( $instance->context['query']['inherit'] ) ) {
			return $html;
		}
		$total = (int) $wp_query->max_num_pages;
		if ( $total < 2 ) {
			return '';
		}
		$current = max( 1, (int) get_query_var( 'paged' ) );
		$links   = paginate_links(
			array(
				'type'      => 'array',
				'total'     => $total,
				'current'   => $current,
				'mid_size'  => 2,
				'end_size'  => 1,
				'prev_next' => false,
			)
		);
		if ( ! is_array( $links ) ) {
			return $html;
		}
		$arrow = function ( $page, $symbol, $label, $rel = '' ) {
			return '<li' . ( '' === $rel ? ' class="cmu-pager-end"' : '' ) . '><a class="cmu-pager-arrow" href="' . esc_url( get_pagenum_link( $page ) ) . '"' . ( $rel ? ' rel="' . $rel . '"' : '' ) . ' aria-label="' . esc_attr( $label ) . '">' . $symbol . '</a></li>';
		};
		$items = array();
		if ( $current > 1 ) {
			$items[] = $arrow( 1, '&laquo;', __( 'First page', 'forbes-and-moreword' ) );
			$items[] = $arrow( $current - 1, '&lsaquo;', __( 'Previous page', 'forbes-and-moreword' ), 'prev' );
		}
		foreach ( $links as $link ) {
			if ( str_contains( $link, 'dots' ) ) {
				$items[] = '<li><span>&hellip;</span></li>';
			} elseif ( str_contains( $link, 'current' ) ) {
				$items[] = '<li><a href="' . esc_url( get_pagenum_link( $current ) ) . '" aria-current="page">' . (int) $current . '</a></li>';
			} elseif ( preg_match( '/href="([^"]+)"[^>]*>([^<]+)</', $link, $m ) ) {
				$items[] = '<li><a href="' . esc_url( html_entity_decode( $m[1] ) ) . '">' . esc_html( trim( wp_strip_all_tags( $m[2] ) ) ) . '</a></li>';
			}
		}
		if ( $current < $total ) {
			$items[] = $arrow( $current + 1, '&rsaquo;', __( 'Next page', 'forbes-and-moreword' ), 'next' );
			$items[] = $arrow( $total, '&raquo;', __( 'Last page', 'forbes-and-moreword' ) );
		}
		// In a section of its own, so it gets the page's margins and room above
		// the footer
		return '<div class="content wide"><div><div class="cmu-pager" role="navigation" aria-label="' . esc_attr__( 'Pages', 'forbes-and-moreword' ) . '"><ol>' . implode( '', $items ) . '</ol></div></div></div>';
	},
	10,
	3
);

// A note after a button row (the kit's cmu-cta-note): WordPress's Buttons
// block holds only buttons, so a paragraph with the class cmu-cta-note right
// after a Buttons block with cmu-cta-row moves into the row, beside the
// buttons, where the kit puts it.
add_filter(
	'the_content',
	function ( $html ) {
		if ( ! str_contains( $html, 'cmu-cta-note' ) ) {
			return $html;
		}
		return preg_replace(
			'#(<div class="wp-block-buttons[^"]*\bcmu-cta-row\b[^"]*"[^>]*>.*?)</div>\s*(<p class="[^"]*\bcmu-cta-note\b[^"]*"[^>]*>.*?</p>)#s',
			'$1$2</div>',
			$html
		);
	},
	20
);

// The navigation from a Navigation block: the site's menu (Appearance › Editor
// › Navigation), so a tab's label needn't be its page's title, and a tab can
// link anywhere. It's written as the template's own list (ul > li > a, with
// active on the current page's item), not WordPress's navigation markup. With
// no menu, WordPress's fallback lists the pages, as the page list did.
function fam_nav_is_current( $url, $id ) {
	if ( $id && is_singular() && (int) $id === get_queried_object_id() ) {
		return true;
	}
	if ( $id && is_home() && (int) $id === (int) get_option( 'page_for_posts' ) ) {
		return true;
	}
	$here = untrailingslashit( (string) wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH ) );
	$path = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	return '' !== $path && $path === $here;
}
function fam_nav_items( $blocks ) {
	$out = '';
	foreach ( $blocks as $block ) {
		$name = $block['blockName'] ?? '';
		$a    = $block['attrs'] ?? array();
		if ( 'core/page-list' === $name ) {
			// The page list's own items, without its list around them
			$list = render_block( $block );
			$out .= preg_replace( '#^\s*<ul\b[^>]*>|</ul>\s*$#', '', $list );
			continue;
		}
		if ( ! in_array( $name, array( 'core/navigation-link', 'core/navigation-submenu' ), true ) ) {
			continue;
		}
		$url   = $a['url'] ?? '';
		$id    = $a['id'] ?? 0;
		$label = wp_kses( $a['label'] ?? '', array() );
		if ( '' === $url || '' === $label || untrailingslashit( $url ) === untrailingslashit( home_url() ) || '/' === $url ) {
			continue; // The site's name already links home
		}
		$classes = array_filter( explode( ' ', $a['className'] ?? '' ) );
		if ( fam_nav_is_current( $url, $id ) ) {
			$classes[] = 'active';
		}
		$sub = 'core/navigation-submenu' === $name ? fam_nav_items( $block['innerBlocks'] ?? array() ) : '';
		$out .= '<li' . ( $classes ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '' ) . '>'
			. '<a href="' . esc_url( $url ) . '"' . ( in_array( 'active', $classes, true ) ? ' aria-current="page"' : '' ) . '>' . $label . '</a>'
			. ( '' !== $sub ? '<ul>' . $sub . '</ul>' : '' ) . '</li>';
	}
	return $out;
}
add_filter(
	'render_block_core/navigation',
	function ( $html, $block ) {
		$ref  = $block['attrs']['ref'] ?? 0;
		$menu = $ref ? get_post( $ref ) : null;
		if ( ! $menu && class_exists( 'WP_Navigation_Fallback' ) ) {
			$menu = WP_Navigation_Fallback::get_fallback();
		}
		$blocks = ( $menu && 'wp_navigation' === $menu->post_type )
			? parse_blocks( $menu->post_content )
			: array( array( 'blockName' => 'core/page-list', 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() ) );
		return '<ul>' . fam_nav_items( $blocks ) . '</ul>';
	},
	10,
	2
);
