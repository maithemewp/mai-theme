<?php

// Child theme (Do not remove!).
define( 'CHILD_THEME_NAME', 'Mai Theme' );
define( 'CHILD_THEME_URL', 'https://maitheme.com/' );
define( 'CHILD_THEME_VERSION', '1.4.0' );

// Support the Mai Theme Engine (Do not remove!).
add_theme_support( 'mai-theme-engine' );

/**
 * Mai Theme dependencies (Do not remove!).
 * This auto-installs Mai Theme Engine plugin,
 * which is required for the theme to function properly.
 *
 * composer require afragen/wp-dependency-installer
 */
include_once( __DIR__ . '/vendor/autoload.php' );
add_filter( 'pand_theme_loader', '__return_true' );
WP_Dependency_Installer::instance()->run( __DIR__ );

// Don't do anything else if the Mai Theme Engine plugin is not active.
if ( ! class_exists( 'Mai_Theme_Engine' ) ) {
	return;
}

// Include all php files in the /includes/ directory.
foreach ( glob( dirname( __FILE__ ) . '/includes/*.php' ) as $file ) { include $file; }


/**********************************
 * Add your customizations below! *
 **********************************/


// Enqueue CSS files.
add_action( 'wp_enqueue_scripts', 'maitheme_enqueue_fonts' );
function maitheme_enqueue_fonts() {
	wp_enqueue_style( 'maitheme-google-fonts', '//fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,700,700i|Source+Sans+Pro:400,400i,600,600i,700,700i', array(), CHILD_THEME_VERSION );
}
