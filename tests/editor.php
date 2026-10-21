<?php
/**
 * Sets up the throwaway site for tests/editor.mjs.
 *
 *   php tests/editor.php            make the pages the widths are measured on
 *   php tests/editor.php clean 3,7  delete them, and any menus the editor made
 *                                   that are not in the list
 *
 * Prints the site's address, the pages, and the navigation menus that already
 * exist, as JSON. The Navigation block creates a menu the first time the
 * editor shows one with none to fall back on, and the clean step deletes it so
 * the next script sees the site as it was.
 *
 * Everything this makes carries the `_mai_theme_tests` meta set to `editor`,
 * so it deletes its own pages, and only its own, even after a run that died.
 */

declare( strict_types=1 );

namespace Mai\Theme\Tests;

require getenv( 'WP' ) . '/wp-load.php';

foreach ( get_posts( [ 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_mai_theme_tests', 'meta_value' => 'editor' ] ) as $stale ) {
	wp_delete_post( $stale, true );
}

$menus = get_posts( [ 'post_type' => 'wp_navigation', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] );

if ( 'clean' === ( $argv[1] ?? '' ) ) {
	$keep = array_map( 'intval', array_filter( explode( ',', $argv[2] ?? '' ) ) );

	foreach ( array_diff( $menus, $keep ) as $made ) {
		wp_delete_post( $made, true );
	}

	echo wp_json_encode( [ 'url' => site_url() ] );

	return;
}

// A paragraph, a wide group, and a full group, each with a class to find it by.
$content = '<!-- wp:paragraph {"className":"mai-test-paragraph"} -->
<p class="mai-test-paragraph">A paragraph in the content.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","className":"mai-test-wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide mai-test-wide"><!-- wp:paragraph -->
<p>A wide group.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"mai-test-full","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull mai-test-full"><!-- wp:paragraph -->
<p>A full group.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->';

$make = static function ( string $title, string $template ) use ( $content ): int {
	$id = wp_insert_post(
		[
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $content,
			// The meta, not `page_template`, which refuses a template whose
			// file is missing and would stop the run before a check could say
			// which one.
			'meta_input'   => [ '_mai_theme_tests' => 'editor' ] + ( '' === $template ? [] : [ '_wp_page_template' => $template ] ),
		],
		true
	);

	if ( is_wp_error( $id ) ) {
		fwrite( STDERR, $id->get_error_message() );
		exit( 1 );
	}

	return $id;
};

echo wp_json_encode(
	[
		'url'   => site_url(),
		'menus' => $menus,
		'pages' => [
			'page'    => $make( 'Editor page', '' ),
			'wide'    => $make( 'Editor wide page', 'wide' ),
			'narrow'  => $make( 'Editor narrow page', 'narrow' ),
			'sidebar' => $make( 'Editor page with sidebar', 'with-sidebar' ),
		],
	]
);
