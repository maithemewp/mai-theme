<?php
/**
 * Every Mai Slots location lands where it should, in every template.
 *
 * One published content area per slot location, each matching every page and
 * saying `MARK:<location id>`. Each page shape must show exactly its own
 * markers, in order, and no others. A missing Slot block, a slot inside the
 * wrong part, or one template borrowing another all show up as a wrong list.
 *
 * Then the same pages with Mai Content Areas switched off, and with Mai Columns
 * switched off: every page must still load, with nothing new in debug.log.
 *
 * Everything this script creates carries the `_mai_theme_tests` meta, so it
 * can delete its own data, and only its own, even after a run that died.
 */

declare( strict_types=1 );

namespace Mai\Theme\Tests;

require __DIR__ . '/lib.php';

const TAG      = 'slots';
const CATEGORY = 'mai-theme-slots-test';

const CONTENT_AREAS = 'mai-content-areas/mai-content-areas.php';
const COLUMNS       = 'mai-columns/mai-columns.php';

const GLOBAL_SLOTS = [ 'before-header', 'after-header', 'before-footer', 'after-footer' ];
const ENTRY_SLOTS  = [ 'before-entry', 'before-entry-content', 'after-entry-content', 'after-entry' ];
const LOOP_SLOTS   = [ 'before-loop', 'after-loop' ];
const SIDEBAR      = [ 'before-sidebar-content', 'after-sidebar-content' ];

/**
 * PHP that deletes everything an earlier run of this script left behind.
 *
 * The index is rebuilt afterwards, through the plugin's own repository, so no
 * deleted content area lingers in it. It is derived data, so a rebuild is
 * always safe.
 */
const CLEAN_UP = <<<'PHP'
foreach ( get_posts( [ 'post_type' => [ 'post', 'page', 'mai_content_area' ], 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_mai_theme_tests', 'meta_value' => 'slots' ] ) as $id ) {
	wp_delete_post( $id, true );
}

$term = get_term_by( 'slug', 'mai-theme-slots-test', 'category' );

if ( $term ) {
	wp_delete_term( $term->term_id, 'category' );
}

if ( class_exists( \Mai\ContentAreas\Index::class ) ) {
	( new \Mai\ContentAreas\Index( \Mai\Slots\Slots::instance()->provider( 'content_areas' )->index ) )->rebuild();
}
PHP;

/**
 * The marker ids on a page, in the order they appear.
 *
 * Read as whole ids, so `before-entry` never matches inside
 * `before-entry-content`.
 *
 * @param string $html The page.
 * @return array<int, string>
 */
function markers( string $html ): array {
	preg_match_all( '~MARK:mai/([a-z-]+)~', $html, $matches );

	return $matches[1];
}

/**
 * Set the three reading options at once.
 *
 * @param array{show_on_front: string, page_on_front: int, page_for_posts: int} $options
 * @return void
 */
function set_reading( array $options ): void {
	wp_cli_php(
		'foreach ( ' . var_export( $options, true ) . ' as $name => $value ) { update_option( $name, $value ); }'
	);
}

// What this script changes, read before it changes anything, so the shutdown
// function can put it back.
$original = json_decode(
	wp_cli_php(
		'echo wp_json_encode( [
			"reading" => [
				"show_on_front"  => (string) get_option( "show_on_front" ),
				"page_on_front"  => (int) get_option( "page_on_front" ),
				"page_for_posts" => (int) get_option( "page_for_posts" ),
			],
			"plugins" => array_values( (array) get_option( "active_plugins" ) ),
		] );'
	),
	true,
	512,
	JSON_THROW_ON_ERROR
);

/**
 * Put the site back as it was, even after a failed check or a lost server.
 *
 * A shutdown function, not `finally`, because `exit()` skips `finally` blocks.
 * Plugins come back first, in their own process, so the clean-up after them
 * runs with Mai Content Areas loaded and can rebuild its index.
 *
 * @return void
 */
function restore(): void {
	global $original;

	wp_cli_php(
		'foreach ( ' . var_export( $original['plugins'], true ) . ' as $plugin ) {
			if ( ! is_plugin_active( $plugin ) ) {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					fwrite( STDERR, "reactivating $plugin failed: " . $result->get_error_message() . "\n" );
				}
			}
		}'
	);

	set_reading( $original['reading'] );
	wp_cli_php( CLEAN_UP );
}

register_shutdown_function( __NAMESPACE__ . '\restore' );

wp_cli_php( CLEAN_UP );

$locations = [ ...GLOBAL_SLOTS, ...ENTRY_SLOTS, ...LOOP_SLOTS, ...SIDEBAR ];

$ids = json_decode(
	wp_cli_php(
		'$tag       = [ "_mai_theme_tests" => "slots" ];
		$locations = ' . var_export( $locations, true ) . ';

		foreach ( $locations as $location ) {
			$id = wp_insert_post( [
				"post_type"    => "mai_content_area",
				"post_status"  => "publish",
				"post_title"   => "mai/$location",
				"post_content" => "<!-- wp:paragraph --><p>MARK:mai/$location</p><!-- /wp:paragraph -->",
				"meta_input"   => $tag,
			], true );

			if ( is_wp_error( $id ) ) {
				fwrite( STDERR, $id->get_error_message() );
				exit( 1 );
			}

			// No rule sets, so it matches every page.
			update_post_meta( $id, "mai_placements", [ "version" => 1, "placements" => [
				[ "location" => "mai/$location", "priority" => 10, "position" => [], "rules" => [ "version" => 1, "sets" => [] ] ],
			] ] );
		}

		( new \Mai\ContentAreas\Index( \Mai\Slots\Slots::instance()->provider( "content_areas" )->index ) )->rebuild();

		$category = wp_insert_term( "Slots test", "category", [ "slug" => "mai-theme-slots-test" ] );

		$make = static function ( string $type, string $title, string $template = "" ) use ( $tag, $category ): int {
			$id = wp_insert_post( [
				"post_type"     => $type,
				"post_status"   => "publish",
				"post_title"    => $title,
				"post_content"  => "<!-- wp:paragraph --><p>Body of $title.</p><!-- /wp:paragraph -->",
				"post_date"     => "2019-05-01 10:00:00",
				"post_category" => "post" === $type ? [ $category["term_id"] ] : [],
				// The meta, not `page_template`, which refuses a template
				// whose file is missing and so would stop the run before
				// any check could say which one.
				"meta_input"    => $tag + ( "" === $template ? [] : [ "_wp_page_template" => $template ] ),
			], true );

			if ( is_wp_error( $id ) ) {
				fwrite( STDERR, $id->get_error_message() );
				exit( 1 );
			}

			return $id;
		};

		echo wp_json_encode( [
			"category"     => $category["term_id"],
			"post"         => $make( "post", "Hello slots" ),
			"post_sidebar" => $make( "post", "Slots post with sidebar", "with-sidebar" ),
			"page"         => $make( "page", "Slots page" ),
			"blank"        => $make( "page", "Slots blank page", "blank" ),
			"canvas"       => $make( "page", "Slots canvas page", "canvas" ),
			"wide"         => $make( "page", "Slots wide page", "wide" ),
			"narrow"       => $make( "page", "Slots narrow page", "narrow" ),
			"page_sidebar" => $make( "page", "Slots page with sidebar", "with-sidebar" ),
			"front"        => $make( "page", "Slots front page" ),
		] );'
	),
	true,
	512,
	JSON_THROW_ON_ERROR
);

$posts_home  = [ 'show_on_front' => 'posts', 'page_on_front' => 0, 'page_for_posts' => 0 ];
$static_home = [ 'show_on_front' => 'page', 'page_on_front' => $ids['front'], 'page_for_posts' => 0 ];

$entry   = [ 'before-header', 'after-header', ...ENTRY_SLOTS, 'before-footer', 'after-footer' ];
$sidebar = [ 'before-header', 'after-header', ...ENTRY_SLOTS, ...SIDEBAR, 'before-footer', 'after-footer' ];
$loop    = [ 'before-header', 'after-header', ...LOOP_SLOTS, 'before-footer', 'after-footer' ];

// Each page: its label, its query, the reading options it needs, its status,
// and the markers it shows, in order.
$pages = [
	[ 'single post', 'p=' . $ids['post'], $posts_home, 200, $entry ],
	[ 'page', 'page_id=' . $ids['page'], $posts_home, 200, $entry ],
	[ 'Wide page', 'page_id=' . $ids['wide'], $posts_home, 200, $entry ],
	[ 'Narrow page', 'page_id=' . $ids['narrow'], $posts_home, 200, $entry ],
	[ 'With sidebar page', 'page_id=' . $ids['page_sidebar'], $posts_home, 200, $sidebar ],
	[ 'With sidebar post', 'p=' . $ids['post_sidebar'], $posts_home, 200, $sidebar ],
	[ 'Blank page', 'page_id=' . $ids['blank'], $posts_home, 200, GLOBAL_SLOTS ],
	[ '404', 'p=999999', $posts_home, 404, GLOBAL_SLOTS ],
	[ 'Canvas page', 'page_id=' . $ids['canvas'], $posts_home, 200, [] ],
	[ 'category archive', 'cat=' . $ids['category'], $posts_home, 200, $loop ],
	[ 'search', 's=hello', $posts_home, 200, $loop ],
	[ 'blog home, showing the latest posts', '', $posts_home, 200, $loop ],
	[ 'static front page', '', $static_home, 200, GLOBAL_SLOTS ],
];

/**
 * Fetch every page, with the reading options each one needs.
 *
 * @param array<int, array{0: string, 1: string, 2: array<string, mixed>, 3: int, 4: array<int, string>}> $pages
 * @return array<string, array{status: int, body: string}> Keyed by label.
 */
function fetch_all( array $pages ): array {
	$current = null;
	$results = [];

	foreach ( $pages as [ $label, $query, $reading ] ) {
		if ( $reading !== $current ) {
			set_reading( $reading );
			$current = $reading;
		}

		$results[ $label ] = fetch( $query );
	}

	return $results;
}

/**
 * What debug.log gained since `$offset`, without core's own update checks.
 *
 * @param int $offset The log's size before.
 * @return string
 */
function new_log_lines( int $offset ): string {
	$log = getenv( 'WP' ) . '/wp-content/debug.log';

	clearstatcache( true, $log );

	if ( ! is_file( $log ) || filesize( $log ) <= $offset ) {
		return '';
	}

	$lines = explode( "\n", (string) file_get_contents( $log, false, null, $offset ) );
	$lines = array_filter( $lines, static fn( string $line ): bool => '' !== trim( $line ) && 1 !== preg_match( '~wp_version_check|wp_update_plugins|wp_update_themes~', $line ) );

	return implode( "\n", $lines );
}

/**
 * The size of debug.log now, so a pass can read only what it added.
 *
 * @return int
 */
function log_size(): int {
	$log = getenv( 'WP' ) . '/wp-content/debug.log';

	clearstatcache( true, $log );

	return is_file( $log ) ? (int) filesize( $log ) : 0;
}

echo "\nWith every plugin active\n";

$results = fetch_all( $pages );

foreach ( $pages as [ $label, , , $status, $expected ] ) {
	check( "$label returns $status", $results[ $label ]['status'], $status );
	check( "$label shows exactly its slots, in order", markers( $results[ $label ]['body'] ), $expected );
}

$single = $results['single post']['body'];

foreach ( [
	'Before header lands before the <header> element'  => [ 'MARK:mai/before-header', '<header', -1 ],
	'After header lands after the </header> tag'       => [ 'MARK:mai/after-header', '</header>', 1 ],
	'Before footer lands before the <footer> element'  => [ 'MARK:mai/before-footer', '<footer', -1 ],
	'After footer lands after the </footer> tag'       => [ 'MARK:mai/after-footer', '</footer>', 1 ],
] as $what => [ $marker, $tag, $side ] ) {
	$at    = strpos( $single, $marker );
	$other = strpos( $single, $tag );
	check( $what, false !== $at && false !== $other && $side === ( $at <=> $other ) );
}

check( 'the header menu is labelled Main', str_contains( $single, 'aria-label="Main"' ) );
check( 'the footer menu is labelled Footer', str_contains( $single, 'aria-label="Footer"' ) );
check( 'the sidebar renders in an <aside> element', str_contains( $results['With sidebar page']['body'], '<aside class="wp-block-template-part"' ) );

check( 'the Canvas page has no <header> element', str_contains( $results['Canvas page']['body'], '<header' ), false );
check( 'the Canvas page still shows its content', str_contains( $results['Canvas page']['body'], 'Body of Slots canvas page.' ) );

// The theme ships front-page.html, which core would use for a posts home page
// too. Mai Theme Core drops it from the hierarchy when the home page shows
// posts, so the loop's slots above and the post's title here prove home.html.
check( 'the latest-posts home page lists the post', str_contains( $results['blog home, showing the latest posts']['body'], 'Hello slots' ) );
check( 'the static front page shows its own content', str_contains( $results['static front page']['body'], 'Body of Slots front page.' ) );

/**
 * The text of the group holding the Post Date block, or null when the page has none.
 *
 * @param string $html The page.
 * @return string|null
 */
function entry_meta( string $html ): ?string {
	$date = \Dom\HTMLDocument::createFromString( $html, LIBXML_NOERROR )->querySelector( 'main .wp-block-group > .wp-block-post-date' );

	return $date?->parentElement?->textContent;
}

check( "the post's meta group shows its year", str_contains( (string) entry_meta( $single ), '2019' ) );
check( 'the page has no meta group', entry_meta( $results['page']['body'] ), null );
// Inside main only. Core's inlined CSS carries a 2019 URL in a comment.
$main = \Dom\HTMLDocument::createFromString( $results['page']['body'], LIBXML_NOERROR )->querySelector( 'main' );
check( "and no sign of the page's year in main", null !== $main && ! str_contains( (string) $main->textContent, '2019' ) );

/**
 * The first element matching a selector inside `main`, or null.
 *
 * @param string $html     The page.
 * @param string $selector A CSS selector, scoped to `main`.
 * @return \Dom\Element|null
 */
function in_main( string $html, string $selector ): ?\Dom\Element {
	return \Dom\HTMLDocument::createFromString( $html, LIBXML_NOERROR )->querySelector( "main $selector" );
}

$main_classes = static fn( string $label ): array => explode( ' ', (string) \Dom\HTMLDocument::createFromString( $results[ $label ]['body'], LIBXML_NOERROR )->querySelector( 'main' )?->getAttribute( 'class' ) );

check( 'the Wide page puts mai-theme-wide on main', in_array( 'mai-theme-wide', $main_classes( 'Wide page' ), true ) );
check( 'the Narrow page puts mai-theme-narrow on main', in_array( 'mai-theme-narrow', $main_classes( 'Narrow page' ), true ) );
check( 'the latest-posts home page is headed Blog', trim( (string) in_main( $results['blog home, showing the latest posts']['body'], 'h1' )?->textContent ), 'Blog' );
check( 'the search page has a search form', null !== in_main( $results['search']['body'], 'form.wp-block-search' ) );

// Switching a post to the sidebar layout keeps what Single Posts shows after
// the entry. The previous and next links are for posts only.
$sidebar_post = $results['With sidebar post']['body'];
check( 'the With sidebar post links to the previous and next posts', null !== in_main( $sidebar_post, 'nav[aria-label="Posts"] .wp-block-post-navigation-link' ) );
check( 'and has the comments block', null !== in_main( $sidebar_post, '.wp-block-comments' ) );
check( 'the With sidebar page has no post links', in_main( $results['With sidebar page']['body'], 'nav[aria-label="Posts"]' ), null );

// The pattern is the template, offered in the Design panel. PHP drops the one
// newline after the closing tag, so what follows it is exactly what the
// pattern prints. (The tag itself cannot appear in this comment: it would end
// PHP mode here.)
$pattern = (string) file_get_contents( dirname( __DIR__ ) . '/patterns/layout-post-with-sidebar.php' );
check(
	'the Post: With sidebar pattern prints templates/with-sidebar.html exactly',
	substr( $pattern, (int) strpos( $pattern, "?>\n" ) + 3 ),
	(string) file_get_contents( dirname( __DIR__ ) . '/templates/with-sidebar.html' )
);

foreach ( [ CONTENT_AREAS => 'Mai Content Areas', COLUMNS => 'Mai Columns' ] as $plugin => $name ) {
	echo "\nWith $name deactivated\n";

	$offset = log_size();

	wp_cli_php( 'deactivate_plugins( ' . var_export( $plugin, true ) . ' );' );
	check( "$name is inactive", trim( wp_cli_php( 'echo is_plugin_active( ' . var_export( $plugin, true ) . ' ) ? "active" : "inactive";' ) ), 'inactive' );

	$results = fetch_all( $pages );

	foreach ( $pages as [ $label, , , $status, $expected ] ) {
		check( "$label returns $status", $results[ $label ]['status'], $status );

		// Without the engine, a Slot block is an unregistered block with no
		// saved content, so core renders nothing in its place. Without Mai
		// Columns, its blocks render their inner blocks, slots included.
		check(
			CONTENT_AREAS === $plugin ? "$label shows no markers" : "$label still shows its slots, in order",
			markers( $results[ $label ]['body'] ),
			CONTENT_AREAS === $plugin ? [] : $expected
		);
	}

	check( 'debug.log has nothing new', new_log_lines( $offset ), '' );

	wp_cli_php(
		'$result = activate_plugin( ' . var_export( $plugin, true ) . ' );

		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, $result->get_error_message() );
		}'
	);
	check( "$name is active again", trim( wp_cli_php( 'echo is_plugin_active( ' . var_export( $plugin, true ) . ' ) ? "active" : "inactive";' ) ), 'active' );
}

done();
