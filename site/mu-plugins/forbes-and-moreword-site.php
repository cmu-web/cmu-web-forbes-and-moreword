<?php
/**
 * Plugin Name: Forbes & Moreword Docs Site
 * Description: The docs site is built from the repository by site/seed.sh, and nobody signs in. This refuses every sign-in and turns off the ways in that don't use one. For the hosted docs site only; it isn't part of the theme.
 *
 * @package forbes-and-moreword
 */

defined( 'ABSPATH' ) || exit;

/*
 * The one way in, for screenshots of the block editor on a copy running on
 * your own computer: dev/run.sh sets this constant in wp-config.php, and
 * it works only when the page is opened at 127.0.0.1 or localhost. The
 * hosted site never sets it, and its server refuses the sign-in page anyway.
 */
function fam_site_local_login() {
	if ( ! defined( 'FAM_UNSAFE_LOCAL_LOGIN_FOR_SCREENSHOTS' ) || true !== FAM_UNSAFE_LOCAL_LOGIN_FOR_SCREENSHOTS ) {
		return false;
	}
	$host = strtolower( (string) wp_parse_url( 'http://' . ( $_SERVER['HTTP_HOST'] ?? '' ), PHP_URL_HOST ) );
	return in_array( $host, array( '127.0.0.1', 'localhost' ), true );
}

// No sign-in, with any password, for anyone
add_filter(
	'authenticate',
	function ( $user ) {
		if ( fam_site_local_login() ) {
			return $user;
		}
		return new WP_Error( 'fam_no_login', __( 'This site is built from its repository. Nobody signs in.', 'forbes-and-moreword' ) );
	},
	100
);

// Nor any other way in: application passwords, XML-RPC, registration
add_filter( 'wp_is_application_passwords_available', '__return_false' );
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array' );
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit;
}
add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
add_filter( 'pre_option_users_can_register', fn() => '0' );

// No comments or pings
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );

// Nobody's account names are listed: no users in the REST API, no author pages
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( str_starts_with( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	}
);
add_action(
	'template_redirect',
	function () {
		if ( is_author() ) {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
		}
	}
);

// The home page's AI Agent Prompt is one of the kit's App components
add_action( 'wp_enqueue_scripts', fn() => wp_enqueue_style( 'fam-kit-app' ), 20 );

// Link previews, as the Web Kilt's Link Previews standard has them: every
// page shares the home page's picture, /images/preview.jpg (1200 x 630)
add_action(
	'wp_head',
	function () {
		$site = get_bloginfo( 'name' );
		$text = 'A WordPress block theme built from Carnegie Mellon\'s web template and the Carnegie Mellon Web Kilt.';
		$home = is_front_page();
		$post = get_queried_object();
		if ( ! $home && $post instanceof WP_Post && has_excerpt( $post ) ) {
			$text = get_the_excerpt( $post );
		}
		$url   = $home ? home_url( '/' ) : ( $post instanceof WP_Post ? get_permalink( $post ) : home_url( add_query_arg( array() ) ) );
		$title = $home || ! ( $post instanceof WP_Post ) ? $site : get_the_title( $post );
		// A post is an article: its date and author, which some apps show
		$is_post = ! $home && $post instanceof WP_Post && 'post' === $post->post_type;
		$tags  = array(
			array( 'name', 'description', $text ),
			array( 'property', 'og:type', $is_post ? 'article' : 'website' ),
			array( 'property', 'og:site_name', $site ),
			array( 'property', 'og:title', $title ),
			array( 'property', 'og:description', $text ),
			array( 'property', 'og:url', $url ),
			array( 'property', 'og:image', home_url( '/images/preview.jpg' ) ),
			array( 'property', 'og:image:width', '1200' ),
			array( 'property', 'og:image:height', '630' ),
			array( 'property', 'og:image:alt', 'Forbes & Moreword\'s home page: WordPress Sites in CMU Style, next to two street signs reading Forbes Ave and Moreword Ave.' ),
			array( 'name', 'twitter:card', 'summary_large_image' ),
		);
		if ( $is_post ) {
			$tags[] = array( 'property', 'article:published_time', get_the_date( 'c', $post ) );
			$tags[] = array( 'name', 'author', get_the_author_meta( 'display_name', $post->post_author ) );
		}
		echo "<!-- Link previews -->\n";
		foreach ( $tags as $t ) {
			printf( "<meta %s=\"%s\" content=\"%s\" />\n", $t[0], esc_attr( $t[1] ), esc_attr( $t[2] ) );
		}
		echo "<!-- End of link previews -->\n";
	},
	1
);
