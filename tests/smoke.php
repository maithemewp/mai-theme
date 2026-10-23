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
 * The `Version:` in a theme's `style.css` header, or null when it has none.
 *
 * Read from the file, not from WordPress, so the check compares what WordPress
 * printed with what the header says. A client copy sets its own version, and
 * this follows it.
 *
 * @param string $theme The theme's folder name.
 * @return string|null
 */
function header_version( string $theme ): ?string {
	$css = (string) file_get_contents( getenv( 'WP' ) . "/wp-content/themes/$theme/style.css" );

	return 1 === preg_match( '~^[ \t/*]*Version:\s*(\S+)~m', $css, $matches ) ? $matches[1] : null;
}

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

/**
 * Where an element with this id starts in the page, or null when it is missing.
 *
 * @param string $html The page.
 * @param string $id   The element's id.
 * @return int|null
 */
function id_position( string $html, string $id ): ?int {
	$pattern = '~\bid=([\'"])' . preg_quote( $id, '~' ) . '\1~';

	return 1 === preg_match( $pattern, $html, $matches, PREG_OFFSET_CAPTURE ) ? (int) $matches[0][1] : null;
}

/**
 * The opening tag of the element with this id, or an empty string.
 *
 * @param string $html The page.
 * @param string $id   The element's id.
 * @return string
 */
function tag_with_id( string $html, string $id ): string {
	$pattern = '~<[a-z]+\b[^>]*\bid=([\'"])' . preg_quote( $id, '~' ) . '\1[^>]*>~i';

	return 1 === preg_match( $pattern, $html, $matches ) ? $matches[0] : '';
}

/**
 * The text and link of the login page's heading, or nulls when it is missing.
 *
 * @param string $html The login page.
 * @return array{href: string|null, text: string|null}
 */
function login_heading( string $html ): array {
	$found = 1 === preg_match( '~<h1[^>]*class="wp-login-logo"[^>]*><a href="([^"]*)">(.*?)</a></h1>~s', $html, $matches );

	return [
		'href' => $found ? $matches[1] : null,
		'text' => $found ? $matches[2] : null,
	];
}

/**
 * PHP, run inside the site, that removes everything the checks below make.
 *
 * It finds them by a meta key and a login name rather than by ids, so it also
 * clears what a run that died halfway left behind.
 */
const CLEAN_UP = 'require_once ABSPATH . "wp-admin/includes/user.php";

foreach ( get_posts( [ "post_type" => [ "post", "attachment" ], "post_status" => "any", "meta_key" => "_mai_smoke", "numberposts" => -1, "fields" => "ids" ] ) as $id ) {
	"attachment" === get_post_type( $id ) ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
}

$user = get_user_by( "login", "mai-smoke-editor" );

if ( $user ) {
	wp_delete_user( $user->ID );
}';

/**
 * Remove everything the checks made, and put the site back as it was.
 *
 * A shutdown function, not `finally`, because a lost server ends the script
 * with `exit()`, which skips `finally` blocks. The theme folder is the real
 * repository, so the test login.css must never outlive the run.
 *
 * @return void
 */
function restore(): void {
	foreach ( [ 'mai-theme', 'acme' ] as $theme ) {
		remove_theme_login_css( $theme );
	}

	// Only put the site logo back once it was read, so a run that stopped
	// before then never clears a logo it did not touch.
	$site_logo = '';

	if ( array_key_exists( 'mai_smoke_site_logo', $GLOBALS ) ) {
		$site_logo = '$site_logo = ' . var_export( $GLOBALS['mai_smoke_site_logo'], true ) . ';
		false === $site_logo ? delete_option( "site_logo" ) : update_option( "site_logo", $site_logo );';
	}

	wp_cli_php( CLEAN_UP . $site_logo . ' switch_theme( "mai-theme" );' );
}

/**
 * The path a theme's own login.css would have.
 *
 * @param string $theme The theme's folder name.
 * @return string
 */
function theme_login_css( string $theme ): string {
	return getenv( 'WP' ) . "/wp-content/themes/$theme/assets/css/login.css";
}

/**
 * Remove the test login.css, but only one this script wrote.
 *
 * @param string $theme The theme's folder name.
 * @return void
 */
function remove_theme_login_css( string $theme ): void {
	$file = theme_login_css( $theme );

	if ( is_file( $file ) && str_contains( (string) file_get_contents( $file ), TEST_LOGIN_CSS ) ) {
		unlink( $file );
	}
}

/**
 * What the test login.css holds, so the clean-up never deletes a real one.
 */
const TEST_LOGIN_CSS = '/* Written by tests/smoke.php, and removed when it ends. */';

// The site outlives the run, so leave it on the starter for the next script.
register_shutdown_function( __NAMESPACE__ . '\restore' );

foreach ( [ 'mai-theme', 'acme' ] as $theme ) {
	if ( is_file( theme_login_css( $theme ) ) ) {
		fwrite( STDERR, theme_login_css( $theme ) . " already exists. The login checks write and remove that file, so move it aside first.\n" );
		exit( 1 );
	}
}

// The logo checks clear the site logo, so remember it for the clean-up first.
$GLOBALS['mai_smoke_site_logo'] = json_decode( wp_cli_php( 'echo wp_json_encode( get_option( "site_logo" ) );' ), true, 512, JSON_THROW_ON_ERROR );

// One post with empty paragraphs, an editor to log in as, and a logo, made
// once and shared by both passes. The site logo option is set only while a
// check needs it.
$made = wp_cli_php(
	CLEAN_UP . '
	require_once ABSPATH . "wp-admin/includes/image.php";

	$post = wp_insert_post(
		[
			"post_title"   => "Empty paragraphs",
			"post_status"  => "publish",
			"post_content" => "<!-- wp:paragraph -->\n<p>Before the gap</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p></p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>&nbsp;</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p> </p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>After the gap</p>\n<!-- /wp:paragraph -->",
			"meta_input"   => [ "_mai_smoke" => 1 ],
		],
		true
	);

	$editor = wp_insert_user(
		[
			"user_login" => "mai-smoke-editor",
			"user_pass"  => "password",
			"user_email" => "mai-smoke-editor@example.com",
			"role"       => "editor",
		]
	);

	$uploads = wp_upload_dir();
	$file    = $uploads["path"] . "/mai-smoke-logo.png";
	imagepng( imagecreatetruecolor( 400, 120 ), $file );

	$logo = wp_insert_attachment( [ "post_mime_type" => "image/png", "post_title" => "Smoke logo", "meta_input" => [ "_mai_smoke" => 1 ] ], $file, 0, true );

	foreach ( [ "post" => $post, "editor" => $editor, "logo" => $logo ] as $what => $result ) {
		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, "making the $what failed: " . $result->get_error_message() . "\n" );
		}
	}

	wp_update_attachment_metadata( $logo, wp_generate_attachment_metadata( $logo, $file ) );
	delete_option( "site_logo" );

	echo wp_json_encode( [ "post" => $post, "logo" => $logo, "logo_url" => wp_get_attachment_image_url( $logo, "full" ) ] );'
);

$made = json_decode( $made, true, 512, JSON_THROW_ON_ERROR );
$site = json_decode( wp_cli_php( 'echo wp_json_encode( [ "name" => get_bloginfo( "name" ), "home" => home_url( "/" ), "year" => wp_date( "Y" ) ] );' ), true, 512, JSON_THROW_ON_ERROR );
$year = (string) $site['year'];

foreach ( [ 'mai-theme', 'acme' ] as $theme ) {
	echo "\nWith $theme active\n";

	check( "switched to $theme", trim( wp_cli_php( 'switch_theme( ' . var_export( $theme, true ) . ' ); echo get_stylesheet();' ) ), $theme );

	$page = fetch( '' );
	$html = $page['body'];
	check( 'home page returns 200', $page['status'], 200 );

	// The year's saved text is "Year", never a year, so only the binding can
	// put this year there. The footer has no Navigation block on purpose: one
	// with no menu chosen shows the most recently published menu, whichever
	// that is.
	$footer = \Dom\HTMLDocument::createFromString( $html, LIBXML_NOERROR )->querySelector( 'footer' );
	$text   = (string) $footer?->textContent;
	check( 'the page has a <footer>', null !== $footer );
	check( "the footer's copyright shows this year, through the binding", str_contains( $text, $year ) );
	check( 'and not the saved text, Year', str_contains( $text, 'Year' ), false );
	check( 'the footer holds no Navigation block', $footer?->querySelector( '.wp-block-navigation' ), null );
	check(
		'the footer shows the Site Title in its first column and its copyright',
		array_map( static fn( \Dom\Element $title ): string => trim( (string) $title->textContent ), $footer ? iterator_to_array( $footer->querySelectorAll( '.wp-block-site-title' ) ) : [] ),
		[ $site['name'], $site['name'] ]
	);

	$folder = BASE_URL . '/wp-content/themes/' . $theme . '/';

	$version = header_version( $theme );
	check( "this folder's style.css header has a version", null !== $version );

	preg_match( '~<link[^>]*\bid=([\'"])mai-css\1[^>]*\bhref=([\'"])([^\'"]+)\2~', $html, $link );
	check( "mai-css loads style.css from this folder, versioned $version plus its file time", 1 === preg_match( '~^' . preg_quote( $folder . 'style.css?ver=' . $version . '.', '~' ) . '\d+$~', $link[3] ?? '' ) );

	// Core inlines a block stylesheet this small, so the page shows the handle
	// but no URL. The URL and the file core read are checked inside the site,
	// by handing core's render_block filter a Navigation block, which registers
	// the stylesheet as a real page does, without rendering a menu.
	check( 'the page prints the Navigation block CSS, as mai-core-navigation', 1 === preg_match( '~<(?:link|style)[^>]*\bid=([\'"])mai-core-navigation(?:-inline)?-css\1~', $html ) );

	$navigation = json_decode(
		wp_cli_php(
			'$block = [ "blockName" => "core/navigation", "attrs" => [], "innerBlocks" => [], "innerHTML" => "", "innerContent" => [] ];
			apply_filters( "render_block", "", $block, new WP_Block( $block ) );
			$style = wp_styles()->query( "mai-core-navigation" );
			echo wp_json_encode( [ "src" => $style ? $style->src : null, "path" => $style ? wp_styles()->get_data( "mai-core-navigation", "path" ) : null ] );'
		),
		true,
		512,
		JSON_THROW_ON_ERROR
	);
	check( 'its URL is under this folder', str_starts_with( (string) $navigation['src'], $folder . 'assets/css/blocks/core/navigation.css' ) );
	check( 'and core reads it from this folder', $navigation['path'], getenv( 'WP' ) . "/wp-content/themes/$theme/assets/css/blocks/core/navigation.css" );

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

	echo "\nThe login page, with $theme active\n";

	$login = fetch_url( BASE_URL . '/wp-login.php' );
	$html  = $login['body'];
	check( 'wp-login.php returns 200', $login['status'], 200 );

	$global_at = id_position( $html, 'mai-login-global-inline-css' );
	$login_at  = id_position( $html, 'mai-login-inline-css' );
	check( 'it prints the global stylesheet as mai-login-global', null !== $global_at );
	check( 'it prints the login styles as mai-login', null !== $login_at );
	check( 'mai-login-global comes before mai-login', null !== $global_at && null !== $login_at && $global_at < $login_at );
	check( "and after core's own login styles", ( id_position( $html, 'login-css' ) ?? PHP_INT_MAX ) < ( $global_at ?? -1 ) );
	check( 'mai-login-global holds the theme.json variables', str_contains( style_block( $html, 'mai-login-global-inline-css' ) ?? '', '--wp--preset--color--primary:' ) );
	check( 'mai-login sets the three login variables', preg_match_all( '~--mai-login-(?:background|card-background|logo-height):~', style_block( $html, 'mai-login-inline-css' ) ?? '' ), 3 );
	check( 'no mai-login-theme without a theme login.css', id_position( $html, 'mai-login-theme-css' ), null );

	preg_match( '~@font-face\s*\{[^}]*font-family:\s*Figtree;[^}]*url\([\'"]?([^\'")]+figtree-latin-wght-normal\.woff2)~', $html, $face );
	check( "Figtree's @font-face prints, pointing at this folder's font", $face[1] ?? null, $font );

	check( 'the Log In button has the block button classes', 1 === preg_match( '~\bclass="wp-block-button__link wp-element-button"~', tag_with_id( $html, 'wp-submit' ) ) );
	check( 'and its paragraph is a block button wrapper', 1 === preg_match( '~<p class="submit wp-block-button">~', $html ) );

	$heading = login_heading( $html );
	check( 'with no logo, the heading shows the Site Title', $heading['text'], htmlspecialchars( $site['name'], ENT_QUOTES ) );
	check( 'and links to the home page', $heading['href'], $site['home'] );
	check( 'and draws no logo', str_contains( $html, $made['logo_url'] ), false );

	file_put_contents( theme_login_css( $theme ), TEST_LOGIN_CSS . "\nbody.login { --mai-login-background: red; }\n" );
	wp_cli_php( 'update_option( "site_logo", ' . (int) $made['logo'] . ' );' );

	$html = fetch_url( BASE_URL . '/wp-login.php' )['body'];

	remove_theme_login_css( $theme );
	wp_cli_php( 'delete_option( "site_logo" );' );

	check( 'with a logo, mai-login draws it as the .login h1 a background', 1 === preg_match( '~\.login h1 a\s*\{[^}]*url\(\s*[\'"]?' . preg_quote( $made['logo_url'], '~' ) . '~', style_block( $html, 'mai-login-inline-css' ) ?? '' ) );
	check( 'and the Site Title stays as the link text', login_heading( $html )['text'], htmlspecialchars( $site['name'], ENT_QUOTES ) );

	$theme_css = tag_with_id( $html, 'mai-login-theme-css' );
	check( "a theme login.css loads as mai-login-theme, from this folder", 1 === preg_match( '~\bhref=([\'"])' . preg_quote( $folder . 'assets/css/login.css?ver=', '~' ) . '[^\'"]+\1~', $theme_css ) );
	check( 'after mai-login', ( id_position( $html, 'mai-login-inline-css' ) ?? PHP_INT_MAX ) < ( id_position( $html, 'mai-login-theme-css' ) ?? -1 ) );

	echo "\nContent, embeds, and forms, with $theme active\n";

	$post = fetch( 'p=' . (int) $made['post'] );
	check( 'the post with empty paragraphs returns 200', $post['status'], 200 );

	$gap = 1 === preg_match( '~Before the gap</p>(.*?)<p\b[^>]*>After the gap~s', $post['body'], $between ) ? $between[1] : null;
	check( 'both paragraphs with text render', null !== $gap );
	check( 'and the empty paragraphs between them are gone', trim( (string) $gap ), '' );
	// Core's own the_content filters turn a null into a string long before
	// priority 99, so the null comes from a filter in between, as a plugin's
	// can. A TypeError is caught, so the check fails instead of the script.
	check(
		'a null reaching the empty paragraph filter comes back as null, not a fatal error',
		trim( wp_cli_php( 'add_filter( "the_content", "__return_null", 50 ); try { var_export( apply_filters( "the_content", "<p></p>" ) ); } catch ( TypeError $e ) { echo "TypeError: " . $e->getMessage(); }' ) ),
		'NULL'
	);

	$embeds = json_decode(
		wp_cli_php(
			'echo wp_json_encode( [
				"landscape" => apply_filters( "embed_oembed_html", "<iframe width=\"500\" height=\"281\" src=\"https://www.youtube.com/embed/a\"></iframe>", "", [], 0 ),
				"portrait"  => apply_filters( "embed_oembed_html", "<iframe width=\"422\" height=\"750\" src=\"https://www.youtube.com/embed/b\"></iframe>", "", [], 0 ),
				"no-size"   => apply_filters( "embed_oembed_html", "<iframe src=\"https://example.com/c\"></iframe>", "", [], 0 ),
				"percent"   => apply_filters( "embed_oembed_html", "<iframe width=\"100%\" height=\"120\" src=\"https://www.mixcloud.com/widget/iframe/?feed=d\"></iframe>", "", [], 0 ),
				"styled"    => apply_filters( "embed_oembed_html", "<iframe style=\"border-radius: 12px\" width=\"500\" height=\"152\" src=\"https://open.spotify.com/embed/e\"></iframe>", "", [], 0 ),
			] );'
		),
		true,
		512,
		JSON_THROW_ON_ERROR
	);
	check( 'a landscape video embed takes its own ratio and fills the column', str_contains( $embeds['landscape'], 'style="aspect-ratio: 500 / 281; width: 100%; height: auto; box-sizing: border-box;"' ) );
	check( 'a portrait one keeps its own width', str_contains( $embeds['portrait'], 'style="aspect-ratio: 422 / 750; max-width: 100%; height: auto; box-sizing: border-box;"' ) );
	check( 'an embed with no size is left alone', $embeds['no-size'], '<iframe src="https://example.com/c"></iframe>' );
	check( 'an embed sized in percent is left alone', $embeds['percent'], '<iframe width="100%" height="120" src="https://www.mixcloud.com/widget/iframe/?feed=d"></iframe>' );
	check( 'an embed keeps its own style, joined with a semicolon', str_contains( $embeds['styled'], 'style="border-radius: 12px; aspect-ratio: 500 / 152;' ) );

	$forms = json_decode( wp_cli_php( 'echo wp_json_encode( apply_filters( "gform_default_styles", false ) );' ), true, 512, JSON_THROW_ON_ERROR );
	check( 'Gravity Forms buttons take the primary colour', $forms['buttonPrimaryBackgroundColor'] ?? null, '#2563eb' );
	check( 'and its inputs the base-3 border', $forms['inputBorderColor'] ?? null, '#e7e5e4' );

	echo "\nThe admin menu, with $theme active\n";

	$editor = log_in( 'mai-smoke-editor', 'password' );
	check( 'the editor logs in', $editor['logged_in'] );

	$dashboard = request( BASE_URL . '/wp-admin/', $editor['cookies'] );
	check( 'the dashboard loads for the editor', $dashboard['status'], 200 );

	$appearance = 1 === preg_match( '~<li[^>]*\bid="menu-appearance"[^>]*>(.*?)</ul>~s', $dashboard['body'], $menu ) ? $menu[1] : '';
	check( 'the editor sees Appearance, Patterns, linked to the synced patterns', 1 === preg_match( '~<a href=([\'"])edit\.php\?post_type=wp_block\1[^>]*>Patterns</a>~', $appearance ) );
}

done();
