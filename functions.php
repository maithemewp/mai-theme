<?php

declare( strict_types=1 );

// Not Mai\Theme, which belongs to the mai-theme-core package. Sharing it means
// a function here, or one the package adds later, fatals on the same name.
namespace Mai\Theme\Starter;

defined( 'ABSPATH' ) || exit;

// The plumbing lives in the mai-theme-core package, which Composer installs into vendor/.
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', __NAMESPACE__ . '\\show_missing_vendor_notice' );

	return;
}

require_once __DIR__ . '/vendor/autoload.php';

foreach ( glob( __DIR__ . '/inc/*.php' ) ?: [] as $file ) {
	require_once $file;
}

/**
 * Says what to do when the vendor folder is missing, instead of failing without a word.
 */
function show_missing_vendor_notice(): void {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Mai Theme is missing its vendor folder. Run composer install in the theme folder.', 'mai-theme' )
	);
}
