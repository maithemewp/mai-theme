<?php
/**
 * The theme's `<head>` on a real page, as the starter and as a renamed copy.
 *
 * Every check runs twice: once with `mai-theme` active and once with `acme`,
 * the same folder under another name. A client copy is exactly that, so
 * anything that reads the theme's slug instead of the active theme fails here.
 */

declare( strict_types=1 );

namespace Mai\Theme\Tests;

require __DIR__ . '/lib.php';

/**
 * The contents of one `<style id="…">` block, or null when the page has none.
 *
 * @param string $html The page.
 * @param string $id   The style element's id.
 * @return string|null
 */
function style_block( string $html, string $id ): ?string {
	$pattern = '~<style[^>]*\bid=([\'"])' . preg_quote( $id, '~' ) . '\1[^>]*>(.*?)</style>~s';

	return 1 === preg_match( $pattern, $html, $matches ) ? $matches[2] : null;
}

// The site outlives the run, so leave it on the starter for the next script.
// A shutdown function, not `finally`, because a lost server ends the script
// with `exit()`, which skips `finally` blocks.
register_shutdown_function( static fn(): string => wp_cli_php( 'switch_theme( "mai-theme" );' ) );

foreach ( [ 'mai-theme', 'acme' ] as $theme ) {
	echo "\nWith $theme active\n";

	check( "switched to $theme", trim( wp_cli_php( 'switch_theme( ' . var_export( $theme, true ) . ' ); echo get_stylesheet();' ) ), $theme );

	$page = fetch( '' );
	$html = $page['body'];
	check( 'home page returns 200', $page['status'], 200 );

	$folder = BASE_URL . '/wp-content/themes/' . $theme . '/';

	preg_match( '~<link[^>]*\bid=([\'"])mai-theme-css\1[^>]*\bhref=([\'"])([^\'"]+)\2~', $html, $link );
	check( 'mai-theme-css loads style.css from this folder, versioned 3.0.0 plus its file time', 1 === preg_match( '~^' . preg_quote( $folder, '~' ) . 'style\.css\?ver=3\.0\.0\.\d+$~', $link[3] ?? '' ) );

	$global = style_block( $html, 'global-styles-inline-css' ) ?? '';
	check( 'global styles print', '' !== $global );

	foreach ( [
		'--wp--preset--color--primary-2',
		'--wp--preset--spacing--80',
		'--wp--preset--font-size--xxxx-large',
		'--wp--preset--shadow--secondary',
		'--wp--custom--line-height--snug',
		'--wp--preset--aspect-ratio--16-9',
		'--wp--preset--font-family--heading',
	] as $variable ) {
		check( "global styles define $variable", str_contains( $global, $variable . ':' ) );
	}

	check( 'the half radius prints as calc() of the default, untouched', str_contains( $global, '--wp--preset--border-radius--half: calc(var(--wp--preset--border-radius--default) / 2)' ) );

	foreach ( [
		'--wp--preset--color--vivid-',
		'--wp--preset--gradient--',
		'--wp--preset--shadow--natural',
		'--wp--preset--font-size--huge',
	] as $default ) {
		check( "global styles leave out core's $default", str_contains( $global, $default ), false );
	}

	check( 'no duotone filters', str_contains( $html, 'wp-duotone' ), false );

	$library = style_block( $html, 'wp-block-library-inline-css' );
	check( "core's block library CSS is inlined", null !== $library );

	foreach ( [ 'vivid-green-cyan', 'has-very-light-gray', 'has-huge-font-size' ] as $legacy ) {
		check( "the inlined block library CSS has no $legacy", str_contains( (string) $library, $legacy ), false );
	}

	check( 'and still has .screen-reader-text', str_contains( (string) $library, '.screen-reader-text' ) );

	$font = $folder . 'assets/fonts/figtree-latin-wght-normal.woff2';
	preg_match( '~@font-face\s*\{[^}]*font-family:\s*Figtree;[^}]*url\([\'"]?([^\'")]+figtree-latin-wght-normal\.woff2)~', $html, $face );
	check( "Figtree's @font-face points at this folder's font", $face[1] ?? null, $font );
	check( 'and the font returns 200', fetch_url( $font )['status'], 200 );

	// Not just `wp-emoji-release`: that file only loads with SCRIPT_DEBUG off,
	// and this site has it on. Every part core prints, the settings, the
	// loader, and the styles, carries `wp-emoji` in either mode.
	check( 'no emoji script or styles', str_contains( $html, 'wp-emoji' ), false );
}

done();
