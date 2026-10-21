#!/usr/bin/env bash
#
# Stand up a throwaway WordPress with this theme, and run every check against it.
#
# Modelled on Mai Content Areas' tests/setup.sh. SQLite rather than MySQL, so it
# needs no database server: WordPress core plus the SQLite integration drop-in,
# both from GitHub.
#
#   ./tests/setup.sh            # build the site, then run the checks
#   ./tests/setup.sh --run      # just run the checks against an existing site
#
# The theme is linked in twice, as mai-theme and as acme. A client copy is the
# starter under another folder name, and the checks switch between the two to
# prove nothing reads the slug.
#
# Everything lands in .wp-test/, which is gitignored. Delete it to start over.

set -euo pipefail

THEME_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
TEST_DIR="$THEME_DIR/.wp-test"
WP="$TEST_DIR/core"
WP_VERSION="${WP_VERSION:-7.1.3}"
PORT=8430
HOST="localhost:$PORT"

# The documented dev layout: themes in ~/Themes, plugins in ~/Plugins, packages
# in ~/LocalPackages. Override either when a checkout lives elsewhere.
PLUGINS_DIR="${PLUGINS_DIR:-$THEME_DIR/../../Plugins}"
PACKAGES_DIR="${PACKAGES_DIR:-$THEME_DIR/../../LocalPackages}"

port_taken() {
	php -r 'exit( ( @fsockopen( "127.0.0.1", (int) $argv[1] ) || @fsockopen( "[::1]", (int) $argv[1] ) ) ? 0 : 1 );' "$PORT"
}

SERVER_PID=''

stop_server() {
	[ -n "$SERVER_PID" ] || return 0

	# With workers, php -S is a parent with forked children. Stop the children
	# too, or one keeps the port and the next run fails its port check.
	pkill -TERM -P "$SERVER_PID" 2> /dev/null || true
	kill "$SERVER_PID" 2> /dev/null || true
	wait "$SERVER_PID" 2> /dev/null || true
	SERVER_PID=''
}

start_server() {
	if port_taken; then
		echo "Port $PORT is already in use. Stop whatever is running there, then run this again." >&2
		exit 1
	fi

	# Four workers, so any request the site makes back to itself while serving
	# a page never waits on itself. Cron is off in wp-config, but loopbacks are
	# not only cron.
	PHP_CLI_SERVER_WORKERS=4 php -S "$HOST" -t "$WP" > "$TEST_DIR/server.log" 2>&1 &
	SERVER_PID=$!
	trap stop_server EXIT

	for _ in $( seq 1 50 ); do
		port_taken && return 0
		kill -0 "$SERVER_PID" 2> /dev/null || break
		sleep 0.2
	done

	echo "The test server did not start on $HOST. Its log:" >&2
	cat "$TEST_DIR/server.log" >&2
	exit 1
}

run_tests() {
	local failed=0

	if [ ! -f "$WP/wp-load.php" ]; then
		echo "No test site in $WP. Run ./tests/setup.sh first to build it." >&2
		return 1
	fi

	# Emptied first. The log is read below as the record of this run, and a
	# fatal from an earlier one lingering in it would fail a clean run.
	: > "$WP/wp-content/debug.log"

	start_server

	# Every script starts from the starter, whatever the last run left active.
	( cd "$WP" && php -r 'require "wp-load.php"; switch_theme( "mai-theme" );' )

	shopt -s nullglob

	for test in "$THEME_DIR"/tests/*.php; do
		# The shared helpers, not a check.
		[ "$( basename "$test" )" = "lib.php" ] && continue
		WP="$WP" php "$test" || failed=1
	done

	for test in "$THEME_DIR"/tests/*.mjs; do
		WP="$WP" node "$test" || failed=1
	done

	stop_server

	# A clean log is part of the result. Deprecations and notices are how a
	# theme fails on a client site: visibly to them, silently to us.
	#
	# WordPress's own update checks are filtered out. They fail whenever the
	# machine cannot reach wordpress.org, which says nothing about this theme.
	local noise='wp_version_check\|wp_update_plugins\|wp_update_themes'
	local log="$WP/wp-content/debug.log"

	if [ -s "$log" ] && grep -qv "$noise" "$log"; then
		echo "debug.log has entries:"
		grep -v "$noise" "$log"
		failed=1
	else
		echo "debug.log clean: no warnings, notices, or deprecations."
	fi

	# The last line is the whole run's verdict, so a fatal in the first script
	# cannot hide behind a later script's own summary.
	if [ "$failed" -eq 0 ]; then
		echo
		echo "All checks passed."
	else
		echo
		echo "FAILED: a check failed, a script died, or the log is not clean."
	fi

	return $failed
}

if [ "${1:-}" = "--run" ]; then
	run_tests
	exit $?
fi

# Checked before anything is downloaded, so a missing piece fails in a second
# with a plain message instead of after a minute of cloning.
if [ ! -f "$THEME_DIR/vendor/autoload.php" ]; then
	echo "The theme has no vendor folder. Run composer install in $THEME_DIR first." >&2
	exit 1
fi

find_dir() {
	if [ -d "$1" ]; then
		( cd "$1" && pwd )
		return 0
	fi

	echo "Cannot find $1. Set PLUGINS_DIR or PACKAGES_DIR to where it lives." >&2
	return 1
}

MCA_DIR="$( find_dir "$PLUGINS_DIR/mai-content-areas" )"
COLUMNS_DIR="$( find_dir "$PLUGINS_DIR/mai-columns" )"

if [ ! -f "$MCA_DIR/build/index.js" ]; then
	echo "Mai Content Areas has no build. Run npm install and npm run build in $MCA_DIR first." >&2
	exit 1
fi

if [ ! -f "$COLUMNS_DIR/vendor/autoload.php" ] || [ ! -d "$COLUMNS_DIR/build" ]; then
	echo "Mai Columns needs its vendor and build folders. Run composer install and npm run build in $COLUMNS_DIR first." >&2
	exit 1
fi

if port_taken; then
	echo "Port $PORT is already in use. Stop whatever is running there, then run this again." >&2
	exit 1
fi

echo "==> WordPress $WP_VERSION"
rm -rf "$TEST_DIR"
mkdir -p "$TEST_DIR"
git clone --depth 1 --quiet https://github.com/WordPress/WordPress.git "$WP"
git -C "$WP" fetch --depth 1 --quiet origin tag "$WP_VERSION"
git -C "$WP" checkout --quiet "$WP_VERSION"

echo "==> SQLite integration"
git clone --depth 1 --quiet https://github.com/WordPress/sqlite-database-integration.git "$TEST_DIR/sqlite"
bash "$TEST_DIR/sqlite/bin/build-sqlite-plugin-zip.sh" > /dev/null
cp -R "$TEST_DIR/sqlite/build/plugin-sqlite-database-integration" "$WP/wp-content/plugins/"
mkdir -p "$WP/wp-content/database"

# The drop-in wants the plugin root; it appends wp-includes/sqlite/db.php itself.
sed "s|{SQLITE_IMPLEMENTATION_FOLDER_PATH}|$WP/wp-content/plugins/plugin-sqlite-database-integration|; s|{SQLITE_PLUGIN}|plugin-sqlite-database-integration/load.php|" \
	"$WP/wp-content/plugins/plugin-sqlite-database-integration/db.copy" > "$WP/wp-content/db.php"

echo "==> wp-config"
cat > "$WP/wp-config.php" <<CONFIG
<?php
define( 'DB_NAME', 'wordpress' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

foreach ( [ 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ] as \$salt ) {
	define( \$salt, 'mai-theme-throwaway-test-site' );
}

\$table_prefix = 'wp_';

define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
define( 'WP_HOME', 'http://$HOST' );
define( 'WP_SITEURL', 'http://$HOST' );

// Background updates and cron write to debug.log on their own schedule, and the
// clean log is part of every run's verdict.
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'DISABLE_WP_CRON', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once ABSPATH . 'wp-settings.php';
CONFIG

echo "==> Installing"
( cd "$WP" && php -r '
define( "WP_INSTALLING", true );
require "wp-load.php";
require ABSPATH . "wp-admin/includes/upgrade.php";
wp_install( "Mai Theme test", "admin", "admin@example.com", true, "", "password" );
' )

echo "==> Mai Content Areas"

# Its composer.json points at the documented dev layout, the plugin in Plugins
# and its packages in LocalPackages, and Composer refuses a path repository
# whose directory does not exist. So the test builds that layout, as Mai
# Content Areas' own setup does: a stand-in plugin directory two levels below
# a LocalPackages, with the working tree linked into it. What runs is the
# working tree; only the shape around it is arranged.
STAGE="$TEST_DIR/Plugins/mai-content-areas"
mkdir -p "$STAGE" "$TEST_DIR/LocalPackages"

for package in mai-slots mai-dom mai-package-loader; do
	ln -sfn "$( find_dir "$PACKAGES_DIR/$package" )" "$TEST_DIR/LocalPackages/$package"
done

cp "$MCA_DIR/composer.json" "$STAGE/composer.json"
cp "$MCA_DIR/mai-content-areas.php" "$STAGE/mai-content-areas.php"

for linked in src tests build; do
	ln -sfn "$MCA_DIR/$linked" "$STAGE/$linked"
done

( cd "$STAGE" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-progress 2>&1 | tail -4 )

ln -sfn "$STAGE" "$WP/wp-content/plugins/mai-content-areas"

echo "==> Mai Columns"
ln -sfn "$COLUMNS_DIR" "$WP/wp-content/plugins/mai-columns"

echo "==> Theme, as mai-theme and as acme"
ln -sfn "$THEME_DIR" "$WP/wp-content/themes/mai-theme"
ln -sfn "$THEME_DIR" "$WP/wp-content/themes/acme"

( cd "$WP" && php -r '
require "wp-load.php";

foreach ( [ "mai-content-areas/mai-content-areas.php", "mai-columns/mai-columns.php" ] as $plugin ) {
	$result = activate_plugin( $plugin );

	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, "activating $plugin failed: " . $result->get_error_message() . "\n" );
		exit( 1 );
	}

	echo "activated $plugin\n";
}

$theme = wp_get_theme( "mai-theme" );

if ( ! $theme->exists() || $theme->errors() ) {
	fwrite( STDERR, "mai-theme cannot be activated: " . ( $theme->errors() ? $theme->errors()->get_error_message() : "not found" ) . "\n" );
	exit( 1 );
}

switch_theme( "mai-theme" );
echo "activated mai-theme\n";
' )

echo
run_tests
