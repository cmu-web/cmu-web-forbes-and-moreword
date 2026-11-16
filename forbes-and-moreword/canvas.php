<?php
/**
 * The page around a block template: WordPress's own canvas
 * (wp-includes/template-canvas.php), minus its div.wp-site-blocks wrapper.
 *
 * CMU's template script moves the footer's nav#nav to just after
 * #container, and its menu styles only match there when #container is a
 * child of <body>, as on the template's own pages. Inside .wp-site-blocks the
 * menu slides the page but never shows. Without the script, the nav stays in
 * the footer.
 *
 * @package Forbes_And_Moreword
 */

$fam_html = get_the_block_template_html();
// The wrapper opens once and closes last; WordPress may put its skip link
// before it.
$fam_open = strpos( $fam_html, '<div class="wp-site-blocks">' );
$fam_end  = strrpos( $fam_html, '</div>' );
if ( false !== $fam_open && false !== $fam_end && $fam_end > $fam_open ) {
	$fam_html = substr( $fam_html, 0, $fam_open )
		. substr( $fam_html, $fam_open + strlen( '<div class="wp-site-blocks">' ), $fam_end - $fam_open - strlen( '<div class="wp-site-blocks">' ) )
		. substr( $fam_html, $fam_end + strlen( '</div>' ) );
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php echo $fam_html; // phpcs:ignore WordPress.Security.EscapeOutput -- WordPress's rendered template, as core prints it. ?>

<?php wp_footer(); ?>
</body>
</html>
