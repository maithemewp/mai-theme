<?php
/**
 * Helpers shared by every check script.
 *
 * Check scripts do not load WordPress themselves. They read pages over HTTP from
 * the server `tests/setup.sh --run` starts, because that is the only way to see
 * what a visitor gets: the real request, the real template, the real `<head>`.
 * When a script needs to change the site first, it runs PHP inside it with
 * `wp_cli_php()`, in a separate process, so the site it changes is the one the
 * server reads.
 *
 * Run a script through `tests/setup.sh --run`, which sets `WP` and starts the
 * server. Each script ends with `done()`, which prints its totals and sets its
 * exit code. A script that stops before `done()`, or forgets it, still exits 1,
 * so it can never pass by printing nothing.
 */

declare( strict_types=1 );

namespace Mai\Theme\Tests;

const BASE_URL = 'http://localhost:8430';

$GLOBALS['mai_theme_tests'] = [ 'pass' => 0, 'fail' => 0, 'done' => false ];

register_shutdown_function( __NAMESPACE__ . '\\queue_verdict' );

/**
 * Put the verdict at the very end of the shutdown queue.
 *
 * An `exit()` inside a shutdown function skips every one after it, and scripts
 * register their own to put the site back. Registering from inside a shutdown
 * function appends to the queue as it runs, so the verdict goes last and never
 * skips a script's clean-up.
 *
 * @return void
 */
function queue_verdict(): void {
	register_shutdown_function( __NAMESPACE__ . '\\verdict' );
}

/**
 * Fail a script that never reached `done()`, or that failed a check after it.
 *
 * `done()` sets the exit code for a script that finishes. This catches the
 * rest: a script that forgot `done()`, one that a setup step stopped early, and
 * a clean-up check that failed after the totals printed.
 *
 * @return void
 */
function verdict(): void {
	[ 'fail' => $fail, 'done' => $done ] = $GLOBALS['mai_theme_tests'];

	if ( ! $done ) {
		fwrite( STDERR, "\nThis script stopped before done(), so its checks do not count. Failing it.\n\n" );
		exit( 1 );
	}

	if ( $fail > 0 ) {
		exit( 1 );
	}
}

/**
 * Print one result, and count it.
 *
 * @param string $what     What is being checked, in plain words.
 * @param mixed  $actual   What happened.
 * @param mixed  $expected What should have happened. Compared with `===`.
 * @return void
 */
function check( string $what, mixed $actual, mixed $expected = true ): void {
	$ok = $expected === $actual;
	$GLOBALS['mai_theme_tests'][ $ok ? 'pass' : 'fail' ]++;

	printf(
		"  %s %s%s\n",
		$ok ? 'ok  ' : 'FAIL',
		$what,
		$ok ? '' : sprintf( "\n       expected %s, got %s", var_export( $expected, true ), var_export( $actual, true ) )
	);
}

/**
 * Print the script's totals and exit, non-zero if anything failed.
 *
 * @return never
 */
function done(): never {
	[ 'pass' => $pass, 'fail' => $fail ] = $GLOBALS['mai_theme_tests'];

	$GLOBALS['mai_theme_tests']['done'] = true;

	printf( "\n%d passed, %d failed\n\n", $pass, $fail );
	exit( $fail > 0 ? 1 : 0 );
}

/**
 * GET a front-end page from the running test server.
 *
 * @param string $query The query string, without the `?`. Empty for the home page.
 * @return array{status: int, body: string}
 */
function fetch( string $query ): array {
	return fetch_url( BASE_URL . '/?' . $query );
}

/**
 * GET any URL from the running test server, such as a font or a stylesheet.
 *
 * Redirects are not followed, so a check sees the status WordPress sent rather
 * than wherever it led. A server that cannot be reached stops the script with
 * the URL, because an empty body would make every "does not contain" check
 * pass.
 *
 * @param string $url The full URL.
 * @return array{status: int, body: string}
 */
function fetch_url( string $url ): array {
	$context = stream_context_create(
		[
			'http' => [
				'ignore_errors'   => true,
				'follow_location' => 0,
				'timeout'         => 30,
			],
		]
	);

	$body    = @file_get_contents( $url, false, $context );
	$headers = http_get_last_response_headers();

	if ( false === $body || ! is_array( $headers ) || 1 !== preg_match( '~^HTTP/\S+\s+(\d{3})~', $headers[0] ?? '', $matches ) ) {
		fwrite( STDERR, "Cannot reach $url. Is the test server running? Run ./tests/setup.sh --run.\n" );
		exit( 1 );
	}

	return [
		'status' => (int) $matches[1],
		'body'   => $body,
	];
}

/**
 * Run PHP inside the test site, with WordPress loaded, and return what it prints.
 *
 * It runs in its own `php` process, so nothing it loads leaks into the calling
 * script. Anything it writes to stderr, or a non-zero exit, stops the calling
 * script with that output, because a setup step that quietly failed makes
 * every check after it meaningless.
 *
 * @param string $code PHP statements, without an opening `<?php` tag.
 * @return string What the code printed.
 */
function wp_cli_php( string $code ): string {
	$wp = getenv( 'WP' );

	if ( ! is_string( $wp ) || ! is_file( $wp . '/wp-load.php' ) ) {
		fwrite( STDERR, "Set WP to the test site's core folder, or run the checks through ./tests/setup.sh --run.\n" );
		exit( 1 );
	}

	// Stderr goes to a file, not a second pipe, so a chatty process can never
	// fill one pipe while this side waits on the other.
	$errors  = (string) tempnam( sys_get_temp_dir(), 'mai-theme-tests' );
	$process = proc_open(
		[ PHP_BINARY, '-r', 'require "wp-load.php";' . "\n" . $code ],
		[
			1 => [ 'pipe', 'w' ],
			2 => [ 'file', $errors, 'w' ],
		],
		$pipes,
		$wp
	);

	if ( ! is_resource( $process ) ) {
		fwrite( STDERR, "Could not start php to run code inside the test site.\n" );
		exit( 1 );
	}

	$stdout = (string) stream_get_contents( $pipes[1] );
	fclose( $pipes[1] );
	$status = proc_close( $process );
	$stderr = (string) file_get_contents( $errors );
	unlink( $errors );

	if ( 0 !== $status || '' !== $stderr ) {
		fwrite( STDERR, "Code run inside the test site failed (exit $status):\n$code\n\n$stderr$stdout\n" );
		exit( 1 );
	}

	return $stdout;
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
