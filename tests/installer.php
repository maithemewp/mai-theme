<?php
/**
 * The dependency installer turns a required plugin back on, for a real admin.
 *
 * Mai Columns is deactivated, then the admin logs in over HTTP and loads the
 * dashboard, which runs `admin_init` with that user, as on a client site. The
 * installer must activate Mai Columns again, say so, and add nothing to
 * debug.log. Mai Columns is still installed, so turning it back on needs no
 * download, and this runs offline. Installing a missing plugin downloads it
 * from GitHub, so that path is not tested here.
 *
 * The same run repeats under the renamed copy `acme`, to prove the notice names
 * the theme and not its folder. Mai Columns and the starter theme are put back
 * afterwards, whatever happens.
 */

declare( strict_types=1 );

namespace Mai\Theme\Tests;

require __DIR__ . '/lib.php';

/**
 * PHP, run inside the site, that prints whether Mai Columns is active.
 *
 * `is_plugin_active()` lives in a wp-admin file that loading WordPress does not
 * include, so the code includes it itself.
 */
const IS_ACTIVE = 'require_once ABSPATH . "wp-admin/includes/plugin.php"; echo is_plugin_active( "mai-columns/mai-columns.php" ) ? "active" : "inactive";';

/**
 * Activate Mai Columns again if the check did not, so the next script starts from a whole site.
 *
 * A shutdown function, not `finally`, because `exit()` skips `finally` blocks.
 *
 * @return void
 */
function restore(): void {
	wp_cli_php(
		'require_once ABSPATH . "wp-admin/includes/plugin.php";

		if ( ! is_plugin_active( "mai-columns/mai-columns.php" ) ) {
			$result = activate_plugin( "mai-columns/mai-columns.php" );

			if ( is_wp_error( $result ) ) {
				fwrite( STDERR, "reactivating Mai Columns failed: " . $result->get_error_message() . "\n" );
			}
		}'
	);
}

register_shutdown_function( __NAMESPACE__ . '\restore' );

/**
 * Deactivate Mai Columns, log in as the admin over HTTP, and load the dashboard.
 *
 * The installer turns the plugin back on during that request and prints a
 * notice about it, so the page it returns is the notice as a client sees it.
 *
 * @return array{status: int, body: string, log: string}
 */
function dashboard_after_deactivating(): array {
	check( 'Mai Columns starts active', trim( wp_cli_php( IS_ACTIVE ) ), 'active' );

	wp_cli_php( 'require_once ABSPATH . "wp-admin/includes/plugin.php"; deactivate_plugins( "mai-columns/mai-columns.php" );' );
	check( 'Mai Columns is inactive', trim( wp_cli_php( IS_ACTIVE ) ), 'inactive' );

	$offset = log_size();

	$login = log_in( 'admin', 'password' );
	check( 'logging in as admin redirects', $login['status'], 302 );
	check( 'and sets the logged-in cookie', $login['logged_in'] );

	$dashboard = request( BASE_URL . '/wp-admin/', $login['cookies'] );

	return [
		'status' => $dashboard['status'],
		'body'   => $dashboard['body'],
		'log'    => new_log_lines( $offset ),
	];
}

echo "\nMai Columns deactivated, then an admin loads the dashboard\n";

$dashboard = dashboard_after_deactivating();
check( 'the dashboard loads for the admin', $dashboard['status'], 200 );
check( 'the installer says it activated Mai Columns', str_contains( $dashboard['body'], 'Mai Columns has been activated.' ) );
check( 'Mai Columns is active again', trim( wp_cli_php( IS_ACTIVE ) ), 'active' );
check( 'debug.log has nothing new', $dashboard['log'], '' );

// The installer builds its label from the folder name, so a client copy in
// `acme` would read "Acme". The package swaps in the theme's "Theme Name".
// Both folders hold the same style.css, so the label is "Mai Theme" for each,
// and only the acme pass can tell the package's label from the installer's.
echo "\nThe notice is labelled with the theme's name, in a renamed copy too\n";

register_shutdown_function( static fn(): string => wp_cli_php( 'switch_theme( "mai-theme" );' ) );

check( 'switched to acme', trim( wp_cli_php( 'switch_theme( "acme" ); echo get_stylesheet();' ) ), 'acme' );

$dashboard = dashboard_after_deactivating();
check( 'the dashboard loads in acme', $dashboard['status'], 200 );
check( 'the installer says it activated Mai Columns in acme', str_contains( $dashboard['body'], 'Mai Columns has been activated.' ) );
check( 'the notice is labelled with the theme name', str_contains( $dashboard['body'], '<strong>[Mai Theme]</strong>' ) );
check( 'and not with the folder name', str_contains( $dashboard['body'], '<strong>[Acme]</strong>' ), false );
check( 'Mai Columns is active again in acme', trim( wp_cli_php( IS_ACTIVE ) ), 'active' );
check( 'debug.log has nothing new in acme', $dashboard['log'], '' );

done();
