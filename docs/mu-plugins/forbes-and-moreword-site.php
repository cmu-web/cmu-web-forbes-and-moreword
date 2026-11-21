<?php
/**
 * Plugin Name: Forbes & Moreword Docs Site
 * Description: The docs site is built from the repository by docs/seed.sh, and nobody signs in. This refuses every sign-in and turns off the ways in that don't use one. For the hosted docs site only; it isn't part of the theme.
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

// Install's AI agent prompt is one of the kit's App components
add_action( 'wp_enqueue_scripts', fn() => wp_enqueue_style( 'fam-kit-app' ), 20 );

// Link previews: the theme writes the tags (see its functions.php). This
// site's picture is a file served as it is, /images/preview.jpg, not one in
// the media library, and its home page has its own sentence.
add_filter(
	'fam_link_preview',
	function ( $fields ) {
		if ( empty( $fields['image'] ) ) {
			$fields['image'] = array(
				'url'    => home_url( '/images/preview.jpg' ),
				'width'  => 1200,
				'height' => 630,
				'alt'    => 'Forbes & Moreword\'s home page: WordPress Sites in CMU Style, over the Tartans logo on a basketball court.',
			);
		}
		if ( is_front_page() ) {
			$fields['description'] = 'A WordPress block theme built from Carnegie Mellon\'s web template and the Carnegie Mellon Web Kilt.';
		}
		return $fields;
	}
);

/*
 * This docs site's own Google tag. It's here, not in the theme, so it counts
 * visits to this docs site and never to a site built on the theme. The footer's Legal Info link covers it.
 */
add_action(
	'wp_head',
	function () {
		?>
<script async src="https://www.googletagmanager.com/gtm.js?id=GTM-KXCHXXF5"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag() {
		dataLayer.push(arguments);
	}
	gtag("js", new Date());
</script>
		<?php
	},
	1
);

/*
 * About's More From CMU Web: the cards come from CMU Web's project list,
 * through CMU Web's script. It's here because WordPress strips scripts from a
 * page's content; the page keeps the section's heading and sentence.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! is_page( 'about' ) ) {
			return;
		}
		?>
<script src="https://cmu-web.github.io/js/more-from-cmu-web.js" defer></script>
		<?php
	}
);
