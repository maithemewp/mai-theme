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
 * Mai Columns is put back afterwards, whatever happens.
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
 * Send one request to the test server, with cookies, without following redirects.
 *
 * `fetch_url()` cannot log in: it sends no cookies and cannot POST. This keeps
 * the cookies a response sets, so the next request can send them back.
 *
 * @param string                     $url     The full URL.
 * @param array<string, string>      $cookies Cookies to send, name to value, as the server set them.
 * @param array<string, string>|null $form    Fields to POST, or null for a GET.
 * @return array{status: int, body: string, cookies: array<string, string>}
 */
function request( string $url, array $cookies = [], ?array $form = null ): array {
	$headers = [];

	if ( [] !== $cookies ) {
		$headers[] = 'Cookie: ' . implode( '; ', array_map( static fn( string $name, string $value ): string => "$name=$value", array_keys( $cookies ), $cookies ) );
	}

	if ( null !== $form ) {
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	}

	$context = stream_context_create(
		[
			'http' => [
				'method'          => null === $form ? 'GET' : 'POST',
				'header'          => implode( "\r\n", $headers ),
				'content'         => null === $form ? '' : http_build_query( $form ),
				'ignore_errors'   => true,
				'follow_location' => 0,
				'timeout'         => 60,
			],
		]
	);

	$body     = @file_get_contents( $url, false, $context );
	$response = http_get_last_response_headers();

	if ( false === $body || ! is_array( $response ) || 1 !== preg_match( '~^HTTP/\S+\s+(\d{3})~', $response[0] ?? '', $matches ) ) {
		fwrite( STDERR, "Cannot reach $url. Is the test server running? Run ./tests/setup.sh --run.\n" );
		exit( 1 );
	}

	$set = [];

	foreach ( $response as $line ) {
		// A cookie set to a space or `deleted` is WordPress clearing it.
		if ( 1 === preg_match( '~^Set-Cookie:\s*([^=;\s]+)=([^;]*)~i', $line, $cookie ) && ! in_array( $cookie[2], [ '', '+', '%20', 'deleted' ], true ) ) {
			$set[ $cookie[1] ] = $cookie[2];
		}
	}

	return [
		'status'  => (int) $matches[1],
		'body'    => $body,
		'cookies' => $set,
	];
}

echo "\nMai Columns deactivated, then an admin loads the dashboard\n";

check( 'Mai Columns starts active', trim( wp_cli_php( IS_ACTIVE ) ), 'active' );

wp_cli_php( 'require_once ABSPATH . "wp-admin/includes/plugin.php"; deactivate_plugins( "mai-columns/mai-columns.php" );' );
check( 'Mai Columns is inactive', trim( wp_cli_php( IS_ACTIVE ) ), 'inactive' );

$offset = log_size();

// wp-login.php refuses a login without its test cookie, which a browser gets
// from loading the form first.
$test_cookie = [ 'wordpress_test_cookie' => 'WP%20Cookie%20check' ];
$login       = request(
	BASE_URL . '/wp-login.php',
	$test_cookie,
	[
		'log'         => 'admin',
		'pwd'         => 'password',
		'wp-submit'   => 'Log In',
		'redirect_to' => BASE_URL . '/wp-admin/',
		'testcookie'  => '1',
	]
);

$logged_in = array_filter( $login['cookies'], static fn( string $name ): bool => str_starts_with( $name, 'wordpress_logged_in_' ), ARRAY_FILTER_USE_KEY );
check( 'logging in as admin redirects', $login['status'], 302 );
check( 'and sets the logged-in cookie', [] !== $logged_in );

$dashboard = request( BASE_URL . '/wp-admin/', $test_cookie + $login['cookies'] );
check( 'the dashboard loads for the admin', $dashboard['status'], 200 );
check( 'the installer says it activated Mai Columns', str_contains( $dashboard['body'], 'Mai Columns has been activated.' ) );
check( 'Mai Columns is active again', trim( wp_cli_php( IS_ACTIVE ) ), 'active' );
check( 'debug.log has nothing new', new_log_lines( $offset ), '' );

done();
