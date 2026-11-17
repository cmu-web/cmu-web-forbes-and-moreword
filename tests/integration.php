<?php
/**
 * Run against the local docs site: dev/run.sh check
 * Uses WordPress's real block renderer and the seeded pages and posts
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI || ! in_array( wp_parse_url( home_url(), PHP_URL_HOST ), array( '127.0.0.1', 'localhost' ), true ) ) {
	throw new RuntimeException( 'Run these checks against the local docs site.' );
}

$GLOBALS['fam_checks'] = 0;
function fam_check( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$GLOBALS['fam_checks'];
}
function fam_check_tag( $html, $name ) {
	$tag = new WP_HTML_Tag_Processor( $html );
	fam_check( $tag->next_tag( $name ), 'Expected a ' . $name . ' element: ' . $html );
	return $tag;
}

// Link settings and formatted labels survive the CMU button conversion
$html = do_blocks( '<!-- wp:button {"className":"is-style-cta","anchor":"read-guide","linkTarget":"_blank","rel":"noopener noreferrer"} --><div id="read-guide" class="wp-block-button is-style-cta"><a class="wp-block-button__link wp-element-button custom-link" href="https://example.edu/guide?a=1&amp;b=2" target="_blank" rel="noopener noreferrer" title="Read &gt; More" aria-label="Read the guide" data-project="example">Read <strong>the guide</strong></a></div><!-- /wp:button -->' );
$tag  = fam_check_tag( $html, 'A' );
fam_check( $tag->has_class( 'cta' ) && $tag->has_class( 'custom-link' ), 'Keep the CTA and custom classes.' );
fam_check( ! $tag->has_class( 'wp-element-button' ) && ! str_contains( $html, '<div' ), 'Remove the WordPress button classes and wrapper.' );
foreach ( array( 'href' => 'https://example.edu/guide?a=1&b=2', 'target' => '_blank', 'rel' => 'noopener noreferrer', 'id' => 'read-guide', 'title' => 'Read > More', 'aria-label' => 'Read the guide', 'data-project' => 'example' ) as $name => $value ) {
	fam_check( $tag->get_attribute( $name ) === $value, 'Lost link attribute: ' . $name );
}
fam_check( str_contains( $html, '<strong>the guide</strong>' ), 'Keep formatting inside the link.' );

$html = do_blocks( '<!-- wp:button {"className":"is-style-ghost","anchor":"wrapper-id"} --><div class="wp-block-button is-style-ghost" id="wrapper-id"><a id="link-id" class="wp-block-button__link wp-element-button" href="/guide.pdf" download="guide.pdf" aria-describedby="guide-note">Download the Guide</a></div><!-- /wp:button -->' );
$tag  = fam_check_tag( $html, 'A' );
fam_check( $tag->has_class( 'btn' ) && $tag->has_class( 'ghost' ), 'Render the ghost button.' );
fam_check( 'link-id' === $tag->get_attribute( 'id' ), 'Keep an existing link ID.' );
fam_check( 'guide.pdf' === $tag->get_attribute( 'download' ) && 'guide-note' === $tag->get_attribute( 'aria-describedby' ), 'Keep downloads and descriptions.' );
fam_check( 'guide.pdf' === $tag->get_attribute( 'data-cmu-download' ), 'Mark a file link for the kit\'s Downloaded message.' );
$html = do_blocks( '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="https://github.com/cmu-web/cmu-web-forbes-and-moreword/releases/latest/download/forbes-and-moreword.zip">Download the Theme</a></div><!-- /wp:button -->' );
$tag  = fam_check_tag( $html, 'A' );
fam_check( true === $tag->get_attribute( 'download' ) && 'forbes-and-moreword.zip' === $tag->get_attribute( 'data-cmu-download' ), 'Make a release download link a download.' );
$html = do_blocks( '<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/install/">Install</a></div><!-- /wp:button -->' );
fam_check( ! str_contains( $html, 'download' ), 'Leave a link to a page a link.' );

$html = apply_filters( 'render_block_core/button', '<div class="wp-block-button"><button type="submit" name="action" value="save" disabled>Save</button></div>', array( 'attrs' => array() ) );
$tag  = fam_check_tag( $html, 'BUTTON' );
fam_check( $tag->has_class( 'btn' ) && 'submit' === $tag->get_attribute( 'type' ) && true === $tag->get_attribute( 'disabled' ), 'Keep native button behavior.' );
fam_check( 'action' === $tag->get_attribute( 'name' ) && 'save' === $tag->get_attribute( 'value' ), 'Keep form button values.' );

$html = apply_filters( 'render_block_core/button', '<a href="/">Continue</a>', array( 'attrs' => array( 'className' => 'is-style-cta-example' ) ) );
fam_check( fam_check_tag( $html, 'A' )->has_class( 'btn' ), 'Match exact style names.' );
fam_check( '<p>No button</p>' === apply_filters( 'render_block_core/button', '<p>No button</p>', array( 'attrs' => array() ) ), 'Leave unmatched content alone.' );

// The template and plugins share WordPress's jQuery
// Resolve the dependencies as WordPress does when printing the scripts
// so the list script cannot run before jQuery is available
do_action( 'wp_enqueue_scripts' );
$scripts = wp_scripts();
$scripts->all_deps( $scripts->queue );
fam_check( in_array( 'jquery', $scripts->to_do, true ), 'Load WordPress jQuery.' );
$jquery_index = array_search( 'jquery-core', $scripts->to_do, true );
$list_index   = array_search( 'fam-template-list', $scripts->to_do, true );
fam_check( false !== $jquery_index && false !== $list_index && $jquery_index < $list_index, 'Load jQuery before CMU scripts.' );
fam_check( ! wp_script_is( 'fam-template-jquery', 'registered' ), 'Do not register a second jQuery.' );
fam_check( in_array( 'window.$ = window.jQuery;', (array) $scripts->get_data( 'fam-template-list', 'before' ), true ), 'Provide the alias required by CMU scripts.' );
fam_check( str_contains( fam_integrity( '<script src="example.js"></script>', 'fam-kit', 'script' ), 'integrity="' . FAM_KIT_FILES['js/cmu-app.js'] . '"' ), 'Attach script integrity.' );

// Render real content with the seeded site's query context
$original_query = $GLOBALS['wp_query'];
$original_post  = $GLOBALS['post'] ?? null;
try {
	$pages = get_pages( array( 'exclude' => (int) get_option( 'page_on_front' ), 'number' => 1 ) );
	fam_check( (bool) $pages, 'Seed a page for the navigation check.' );
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $pages[0]->ID ) );
	$GLOBALS['post']     = $pages[0];
	$html                = do_blocks( '<!-- wp:page-list /-->' );
	$links               = new WP_HTML_Tag_Processor( $html );
	$current             = false;
	while ( $links->next_tag( 'A' ) ) {
		fam_check( untrailingslashit( $links->get_attribute( 'href' ) ) !== untrailingslashit( home_url( '/' ) ), 'The site name already links home.' );
	}
	$items = new WP_HTML_Tag_Processor( $html );
	while ( $items->next_tag( 'LI' ) ) {
		if ( $items->has_class( 'current-menu-item' ) ) {
			$current = $items->has_class( 'active' );
		}
	}
	fam_check( $current, 'Mark the current page active.' );

	$posts = get_posts( array( 'name' => 'forbes-and-moreword-0-1-0', 'numberposts' => 1 ) );
	fam_check( (bool) $posts, 'Seed the release post for the Article check.' );
	$post                = $posts[0];
	$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $post->ID ) );
	$GLOBALS['post']     = $post;
	$html                = do_blocks( '<!-- wp:group {"className":"cmu-article"} --><div class="wp-block-group cmu-article"></div><!-- /wp:group -->' );
	fam_check( str_contains( $html, '<div class="cmu-article">' ) && str_contains( wp_specialchars_decode( $html, ENT_QUOTES ), $post->post_title ), 'Render the kit Article.' );
	fam_check( str_contains( $html, 'Releases' ) && str_contains( $html, '<time datetime=' ), 'Render the category and date.' );
	fam_check( str_contains( $html, esc_html( $post->post_excerpt ) ) && str_contains( $html, 'Margaret Morrison' ), 'Render the written summary and byline.' );
	fam_check( 'Sept. 8, 2026' === fam_ap_date( strtotime( '2026-09-08 16:00:00 UTC' ) ), 'Use AP date abbreviations.' );

	// The trail on a post: the site's name, News, then the post as plain text
	$html = do_blocks( '<!-- wp:group {"className":"breadcrumbs"} --><div class="wp-block-group breadcrumbs"><!-- wp:paragraph --><p>Breadcrumbs</p><!-- /wp:paragraph --></div><!-- /wp:group -->' );
	fam_check( str_starts_with( $html, '<div class="breadcrumbs"><a href="' . esc_url( home_url( '/' ) ) . '">' ), 'Start the breadcrumbs at the site\'s home.' );
	fam_check( str_contains( $html, '>News</a>&nbsp;&rsaquo;&nbsp;' . esc_html( $post->post_title ) . '</div>' ), 'Put News, then the post, in a post\'s breadcrumbs.' );
	// A list's date: AP, as the Article's
	$html = do_blocks( '<!-- wp:post-date /-->' );
	fam_check( str_contains( $html, '>' . fam_ap_date( get_post_timestamp( $post ) ) . '</time>' ), 'Write list dates in AP style.' );

	$html = do_blocks( '<!-- wp:group {"anchor":"sitename"} --><div id="sitename" class="wp-block-group"></div><!-- /wp:group -->' );
	fam_check( str_contains( $html, '<div id="sitename">' ) && str_contains( $html, 'class="sitetitle"' ), 'Render the CMU site name.' );
	$heading = fam_check_tag( $html, 'A' );
	fam_check( $heading->get_attribute( 'href' ) === home_url( '/' ), 'The site name links home.' );
} finally {
	$GLOBALS['wp_query'] = $original_query;
	$GLOBALS['post']     = $original_post;
}

// No breadcrumbs on the home page
$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => (int) get_option( 'page_on_front' ) ) );
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
fam_check( '' === fam_breadcrumbs(), 'Leave the breadcrumbs off the home page.' );
$GLOBALS['wp_query'] = $original_query;
$GLOBALS['wp_the_query'] = $original_query;

$html = do_blocks( '<!-- wp:list {"className":"fam-copyright"} --><ul class="wp-block-list fam-copyright"><!-- wp:list-item --><li>Copyright</li><!-- /wp:list-item --></ul><!-- /wp:list -->' );
fam_check( str_contains( $html, wp_date( 'Y' ) . ' Carnegie Mellon University' ), 'Keep the copyright year current.' );
fam_check( apply_filters( 'template_include', ABSPATH . WPINC . '/template-canvas.php' ) === get_template_directory() . '/canvas.php', 'Use the CMU canvas for block templates.' );

// Latest Posts is the template's News Grid, with the block's settings
$newest = get_posts( array( 'numberposts' => 2, 'post_status' => 'publish' ) );
$html   = do_blocks( '<!-- wp:latest-posts {"postsToShow":2,"displayPostDate":true,"displayPostContent":true} /-->' );
fam_check( str_contains( $html, '<div class="grid column3 news"' ) && ! str_contains( $html, 'wp-block-latest-posts' ), 'Render Latest Posts as the News Grid.' );
fam_check( 2 === substr_count( $html, '<h2 class="name">' ), 'Show as many stories as the block asks for.' );
fam_check( str_contains( $html, esc_html( fam_news_date( get_post_timestamp( $newest[0] ) ) ) ), 'Date stories in the template\'s style, with AP months.' );
fam_check( str_contains( $html, '<span>' ) && ! str_contains( $html, 'class="img"' ), 'Show the sentence, and no picture when the block shows none.' );
$html = do_blocks( '<!-- wp:latest-posts {"postsToShow":2} /-->' );
fam_check( ! str_contains( $html, '<small>' ) && ! str_contains( $html, '<span>' ), 'Leave off the date and sentence when the block does.' );
$html = do_blocks( '<!-- wp:latest-posts {"postsToShow":1,"order":"asc"} /-->' );
$oldest = get_posts( array( 'numberposts' => 1, 'order' => 'ASC', 'post_status' => 'publish' ) );
fam_check( str_contains( $html, esc_html( html_entity_decode( get_the_title( $oldest[0] ), ENT_QUOTES, 'UTF-8' ) ) ), 'Keep the block\'s order.' );
// In a News Grid group, the stories join the group's heading in one grid
$html = do_blocks( '<!-- wp:group {"className":"grid column3 news"} --><div class="wp-block-group grid column3 news"><!-- wp:heading {"level":1} --><h1 class="wp-block-heading">Latest News</h1><!-- /wp:heading --><!-- wp:latest-posts {"postsToShow":2} /--></div><!-- /wp:group -->' );
fam_check( 1 === preg_match_all( '/class="[^"]*\\bnews\\b/', $html ) && ! str_contains( $html, 'data-fam-news' ) && 2 === substr_count( $html, '<h2 class="name">' ), 'Put the stories straight into the News Grid group.' );

// Link previews: the tags, unless an SEO plugin writes its own
$GLOBALS['wp_query']     = new WP_Query( array( 'p' => $newest[0]->ID ) );
$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
$fields = fam_link_preview_fields();
fam_check( 'article' === $fields['type'] && str_ends_with( $fields['title'], ' - ' . html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES, 'UTF-8' ) ), 'Name a post and the site in its preview.' );
fam_check( ! empty( $fields['image']['url'] ) && '' !== $fields['description'], 'Give a preview a picture and a sentence.' );
ob_start();
do_action( 'wp_head' );
$head = ob_get_clean();
fam_check( str_contains( $head, 'property="og:title"' ) && str_contains( $head, 'name="twitter:card"' ), 'Write the link preview tags.' );
$GLOBALS['wp_query']     = $original_query;
$GLOBALS['wp_the_query'] = $original_query;

WP_CLI::success( $GLOBALS['fam_checks'] . ' integration checks passed.' );
