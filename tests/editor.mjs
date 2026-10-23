/**
 * The Site Editor accepts every template and part, and the front end lays them out at the right widths.
 *
 *   WP=$PWD/.wp-test/core node tests/editor.mjs
 *
 * Run through `tests/setup.sh --run`, which starts the server this reads.
 * Drives headless Chromium and never opens a window.
 *
 * - The theme ships exactly the templates and parts it should, no more.
 * - Every template and part opens with no invalid blocks, and with exactly
 *   the Slot blocks its files hold. The count is read from the files, so a
 *   new slot needs no edit here.
 * - The Post: With sidebar layout pattern is offered for Single Posts, and
 *   parses to valid blocks only.
 * - Alt Heading is in the Heading block's style list.
 * - At 1440px wide, content is 740px on a page, 1200px on Wide, and 600px on
 *   Narrow, with Narrow's wide blocks at 740px. A full group spans the
 *   window. The lists of posts are 1200px. In both sidebar templates, Post
 *   Content sits in its column with no stray padding and nothing poking out.
 * - Each starter block stylesheet loads where its block is, and only there,
 *   and does what it is for: the search field's border, the open menu's
 *   colours, and the Post Template gap from theme.json.
 */

import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { readFileSync, readdirSync, existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const WP = process.env.WP;

if ( ! WP ) {
	console.error( 'Set WP to the throwaway site, such as WP=$PWD/.wp-test/core, or run ./tests/setup.sh --run.' );
	process.exit( 1 );
}

const THEME = fileURLToPath( new URL( '..', import.meta.url ) ).replace( /\/$/, '' );
const setUp = ( ...args ) => JSON.parse( execFileSync( 'php', [ fileURLToPath( new URL( './editor.php', import.meta.url ) ), ...args ], { env: { ...process.env, WP } } ).toString() );

let pass = 0;
let fail = 0;
const check = ( what, actual, expected = true ) => {
	const ok = JSON.stringify( actual ) === JSON.stringify( expected );
	ok ? pass++ : fail++;
	console.log( `  ${ ok ? 'ok  ' : 'FAIL' } ${ what }${ ok ? '' : `\n       expected ${ JSON.stringify( expected ) }, got ${ JSON.stringify( actual ) }` }` );
};

/**
 * Console errors that say nothing about this theme, each with why it is allowed.
 *
 * Matched against the start of the message, as `[ start, why ]`. Anything
 * else fails the run. Empty because WordPress 7.1.3 logged none on
 * 2026-10-20. Add an entry only with the reason it is not this theme's.
 */
const KNOWN_NOISE = [];

const noise = ( message ) => KNOWN_NOISE.find( ( [ start ] ) => message.startsWith( start ) );

// How many of a kind of block a file holds, counting the parts it pulls in,
// since the editor shows a part's blocks inside the template.
const source = ( kind, slug ) => readFileSync( `${ THEME }/${ kind }/${ slug }.html`, 'utf8' );
const countIn = ( html, pattern ) => {
	let count = ( html.match( pattern ) ?? [] ).length;

	for ( const [ , part ] of html.matchAll( /<!-- wp:template-part \{"slug":"([a-z0-9-]+)"/g ) ) {
		if ( existsSync( `${ THEME }/parts/${ part }.html` ) ) {
			count += countIn( source( 'parts', part ), pattern );
		}
	}

	return count;
};
const slotsIn = ( html ) => countIn( html, /<!-- wp:mai\/slot[\s{/]/g );
// Every block opener, nested ones included. Closers start `<!-- /wp:`, so
// they never match. The editor can show more blocks than this, such as a
// menu's links, but never fewer.
const blocksIn = ( html ) => countIn( html, /<!-- wp:[a-z]/g );

// What the theme ships. A file added or removed without updating this list is
// a change nobody checked in the editor.
const EXPECTED = {
	templates: [ '404', 'archive', 'blank', 'canvas', 'front-page', 'home', 'index', 'narrow', 'page', 'search', 'single', 'wide', 'with-sidebar-page', 'with-sidebar-post' ],
	parts: [ 'entry-footer', 'entry-header', 'footer', 'header', 'mobile-menu', 'page-header', 'posts-grid', 'sidebar' ],
};

const files = [
	...readdirSync( `${ THEME }/templates` ).filter( ( f ) => f.endsWith( '.html' ) ).map( ( f ) => [ 'templates', 'wp_template', f.slice( 0, -5 ) ] ),
	...readdirSync( `${ THEME }/parts` ).filter( ( f ) => f.endsWith( '.html' ) ).map( ( f ) => [ 'parts', 'wp_template_part', f.slice( 0, -5 ) ] ),
];

const site = setUp();
const BASE = site.url.replace( /\/$/, '' );
const browser = await chromium.launch( { headless: true } );

try {
	const errors = [];
	const allowed = new Set();
	const listen = ( page, where ) => {
		page.on( 'pageerror', ( e ) => errors.push( `${ where }: pageerror: ${ e.message }` ) );
		page.on( 'console', ( message ) => {
			if ( 'error' !== message.type() ) {
				return;
			}

			const known = noise( message.text() );
			known ? allowed.add( known[ 0 ] ) : errors.push( `${ where }: ${ message.text() }` );
		} );
	};

	console.log( '\nThe theme\'s files' );

	for ( const kind of [ 'templates', 'parts' ] ) {
		const have = files.filter( ( [ folder ] ) => folder === kind ).map( ( [ , , slug ] ) => slug ).sort();
		check( `${ kind }/ holds exactly ${ EXPECTED[ kind ].join( ', ' ) }`, have, EXPECTED[ kind ] );
	}

	console.log( '\nThe front end at 1440px wide, logged out' );

	const visitor = await browser.newPage( { viewport: { width: 1440, height: 900 } } );
	listen( visitor, 'front end' );

	// A page id, or a query string such as `p=12` or `s=Editor`.
	const measure = async ( where ) => {
		await visitor.goto( `${ BASE }/?${ 'number' === typeof where ? `page_id=${ where }` : where }` );

		return visitor.evaluate( () => {
			const box = ( selector ) => {
				const el = document.querySelector( selector );
				const rect = el?.getBoundingClientRect();

				return rect ? { left: Math.round( rect.left ), right: Math.round( rect.right ), width: Math.round( rect.width ) } : null;
			};
			const main = document.querySelector( 'main' );

			return {
				// Not the root's clientWidth, which counts the scrollbar gutter
				// style.css reserves. The site's own box is the window's usable width.
				viewport: document.querySelector( '.wp-site-blocks' ).clientWidth,
				title: box( 'main .wp-block-post-title' ),
				paragraph: box( 'main .mai-test-paragraph' ),
				wide: box( 'main .mai-test-wide' ),
				full: box( 'main .mai-test-full' ),
				grid: box( 'main .wp-block-post-template' ),
				styles: [ ...document.querySelectorAll( 'link[id^="mai-"], style[id^="mai-"]' ) ].map( ( el ) => el.id ),
				classes: main ? [ ...main.classList ] : null,
			};
		} );
	};

	const spans = ( m ) => m.full?.left === 0 && m.full?.width === m.viewport;

	const page = await measure( site.pages.page );
	check( 'page: the title is 740px', page.title?.width, 740 );
	check( 'page: a paragraph in content is 740px', page.paragraph?.width, 740 );
	check( 'page: a wide group in content is 1200px', page.wide?.width, 1200 );
	check( 'page: a full group in content spans the window', spans( page ), true );

	const wide = await measure( site.pages.wide );
	check( 'Wide: the title is 1200px', wide.title?.width, 1200 );
	check( 'Wide: a paragraph in content is 1200px', wide.paragraph?.width, 1200 );
	check( 'Wide: a full group in content spans the window', spans( wide ), true );

	// Without its own Wide width, Narrow's wide blocks would shrink to 600px.
	const narrow = await measure( site.pages.narrow );
	check( 'Narrow: the title is 600px', narrow.title?.width, 600 );
	check( 'Narrow: a paragraph in content is 600px', narrow.paragraph?.width, 600 );
	check( 'Narrow: a wide group in content is 740px', narrow.wide?.width, 740 );
	check( 'Narrow: a full group in content spans the window', spans( narrow ), true );

	// home.html for the blog home, archive.html, and search.html. index.html
	// has no page of its own, since every list it would serve has its own
	// template.
	for ( const [ label, where ] of [ [ 'the blog home', '' ], [ 'a category archive', `cat=${ site.category }` ], [ 'a search', 's=Editor' ] ] ) {
		check( `${ label }: the posts grid is 1200px`, ( await measure( where ) ).grid?.width, 1200 );
	}

	// Post Content sits in the first column. It starts and ends where the
	// column does, so does its paragraph, and nothing in the column reaches
	// past either edge.
	for ( const [ label, where ] of [ [ 'Page with sidebar', site.pages.sidebar ], [ 'Post with sidebar', `p=${ site.pages.post }` ] ] ) {
		await measure( where );
		const column = await visitor.evaluate( () => {
			const content = document.querySelector( 'main .wp-block-post-content' );
			const holder = content?.parentElement;

			if ( ! holder || holder.matches( 'main' ) ) {
				return null;
			}

			const edge = holder.getBoundingClientRect();
			const inset = ( el ) => {
				const rect = el?.getBoundingClientRect();

				return rect ? [ Math.round( rect.left - edge.left ), Math.round( edge.right - rect.right ) ] : null;
			};
			const outside = [ ...holder.querySelectorAll( '*' ) ].filter( ( el ) => {
				const rect = el.getBoundingClientRect();

				return rect.width > 0 && ( rect.left < edge.left - 0.5 || rect.right > edge.right + 0.5 );
			} ).map( ( el ) => el.className || el.tagName );

			return {
				content: inset( content ),
				paragraph: inset( content.querySelector( '.mai-test-paragraph' ) ),
				outside: [ ...new Set( outside ) ],
			};
		} );
		check( `${ label }: Post Content fills its column, edge to edge`, column?.content, [ 0, 0 ] );
		check( 'and so does its paragraph', column?.paragraph, [ 0, 0 ] );
		check( 'and nothing in the column reaches outside it', column?.outside, [] );
	}

	// A Slot block prints its content bare, a template part has no layout of
	// its own, and the reset zeroes every margin. So blocks a content area puts
	// in After entry content are spaced only if entry-footer wraps its slots in
	// a group with a layout. The gap is theme.json's blockGap, spacing 30.
	for ( const [ label, where ] of [ [ 'page', site.pages.page ], [ 'Page with sidebar', site.pages.sidebar ], [ 'Post with sidebar', `p=${ site.pages.post }` ] ] ) {
		await measure( where );
		const spacing = await visitor.evaluate( () => {
			const first = document.querySelector( 'main .mai-test-slot-first' );
			const second = document.querySelector( 'main .mai-test-slot-second' );
			const probe = document.body.appendChild( document.createElement( 'div' ) );
			probe.style.marginTop = 'var(--wp--preset--spacing--30)';
			const want = Math.round( parseFloat( getComputedStyle( probe ).marginTop ) );
			probe.remove();

			return {
				gap: first && second ? Math.round( second.getBoundingClientRect().top - first.getBoundingClientRect().bottom ) : null,
				want,
			};
		} );
		check( `${ label }: two blocks in After entry content are ${ spacing.want }px apart, the block gap`, spacing.gap, spacing.want );
	}

	console.log( '\nBlock stylesheets load where their block is' );

	const sidebarStyles = ( await measure( site.pages.sidebar ) ).styles;
	const loaded = ( styles, block ) => styles.some( ( id ) => new RegExp( `^mai-core-${ block }(-inline)?-css$` ).test( id ) );
	check( 'the page with the sidebar loads the Search block CSS', loaded( sidebarStyles, 'search' ) );
	check( 'a page with no Search block does not', loaded( page.styles, 'search' ), false );
	check( 'a page loads the Navigation block CSS, for the header menu', loaded( page.styles, 'navigation' ) );
	check( 'a page with no Query Loop leaves out the Post Template CSS', loaded( page.styles, 'post-template' ), false );

	await visitor.goto( `${ BASE }/` );
	check( 'the blog home loads the Post Template CSS', loaded( await visitor.evaluate( () => [ ...document.querySelectorAll( '[id^="mai-"]' ) ].map( ( el ) => el.id ) ), 'post-template' ) );

	console.log( '\nBlock stylesheets take effect' );

	// What a preset resolves to in this page, read the same way as the value
	// it is compared with, so `#fff` and `rgb(255, 255, 255)` cannot differ.
	const resolve = ( property, value ) => visitor.evaluate( ( [ p, v ] ) => {
		const probe = document.body.appendChild( document.createElement( 'div' ) );
		probe.style.setProperty( p, v );
		const out = getComputedStyle( probe ).getPropertyValue( p );
		probe.remove();

		return out;
	}, [ property, value ] );
	const computed = ( selector, property ) => visitor.evaluate( ( [ s, p ] ) => {
		const el = document.querySelector( s );

		return el ? getComputedStyle( el ).getPropertyValue( p ) : null;
	}, [ selector, property ] );

	const gap = await visitor.evaluate( () => document.getElementById( 'global-styles-inline-css' )?.textContent ?? '' );
	check( 'a new Post Template grid gets the spacing 40 gap from theme.json', gap.includes( ':root :where(.wp-block-post-template-is-layout-grid){gap: var(--wp--preset--spacing--40);}' ) );

	await visitor.goto( `${ BASE }/?page_id=${ site.pages.sidebar }` );
	const wrapper = '.wp-block-search__button-inside .wp-block-search__inside-wrapper';
	check( 'the search field\'s border is base-3', await computed( wrapper, 'border-top-color' ), await resolve( 'border-top-color', 'var(--wp--preset--color--base-3)' ) );
	check( 'and its radius is the default radius', await computed( wrapper, 'border-top-left-radius' ), await resolve( 'border-top-left-radius', 'var(--wp--preset--border-radius--default)' ) );
	check( 'the search button takes the button element\'s colour', await computed( '.wp-block-search__button', 'background-color' ), await resolve( 'background-color', 'var(--wp--preset--color--primary)' ) );

	// The header menu's overlay is the Mobile menu part, so its container is
	// see-through unless the Navigation CSS paints it.
	await visitor.setViewportSize( { width: 390, height: 844 } );
	await visitor.goto( `${ BASE }/?page_id=${ site.pages.page }` );
	await visitor.click( 'header .wp-block-navigation__responsive-container-open' );
	await visitor.waitForSelector( 'header .wp-block-navigation__responsive-container.is-menu-open' );
	const open = 'header .wp-block-navigation__responsive-container.is-menu-open';
	check( 'the open menu\'s background is base', await computed( open, 'background-color' ), await resolve( 'background-color', 'var(--wp--preset--color--base)' ) );
	check( 'and its text is contrast', await computed( open, 'color' ), await resolve( 'color', 'var(--wp--preset--color--contrast)' ) );

	await visitor.close();

	console.log( '\nThe Site Editor' );

	const editor = await browser.newPage( { viewport: { width: 1440, height: 1000 } } );
	listen( editor, 'editor' );

	await editor.goto( `${ BASE }/wp-login.php` );
	await editor.fill( '#user_login', 'admin' );
	await editor.fill( '#user_pass', 'password' );
	await editor.click( '#wp-submit' );
	await editor.waitForURL( /wp-admin/ );

	// Every block in the editor, depth first, with its parents' names, so a
	// failure says where in the file the block sits. Walked by block order,
	// not `getBlocks()`: a template part's blocks belong to the part's own
	// record, and `getBlocks()` leaves them out of the part's `innerBlocks`
	// (measured in the page template on 7.1.3: 4 of its 8 slots).
	const collect = () => editor.evaluate( () => {
		const store = wp.data.select( 'core/block-editor' );
		const all = [];
		const tree = ( clientId ) => ( { ...store.getBlock( clientId ), innerBlocks: store.getBlockOrder( clientId ).map( tree ) } );
		const walk = ( blocks, path ) => {
			for ( const block of blocks ) {
				const here = [ ...path, block.name ];
				const entry = { name: block.name, path: here.join( ' > ' ), isValid: block.isValid };

				if ( false === block.isValid ) {
					entry.original = block.originalContent;
					entry.saved = wp.blocks.getSaveContent( block.name, block.attributes, block.innerBlocks );
				}

				all.push( entry );
				walk( block.innerBlocks, here );
			}
		};
		walk( store.getBlockOrder().map( tree ), [] );

		return all;
	} );

	// Settled once the block count holds still for three reads in a row, so
	// the parts, which load after the template, are in. A file that never
	// opens, or never holds still, says so instead of passing on an empty or
	// half-loaded list.
	const settle = async ( id ) => {
		try {
			await editor.waitForFunction( ( want ) => window.wp?.data?.select( 'core/editor' )?.getCurrentPostId() === want, id, { timeout: 60000 } );
		} catch {
			return { settled: false, blocks: [] };
		}

		let last = -1;
		let steady = 0;

		for ( let tries = 0; tries < 60 && steady < 3; tries++ ) {
			await editor.waitForTimeout( 500 );
			const count = ( await collect() ).length;
			steady = count > 0 && count === last ? steady + 1 : 0;
			last = count;
		}

		return { settled: steady >= 3, blocks: await collect() };
	};

	for ( const [ folder, type, slug ] of files ) {
		const id = `mai-theme//${ slug }`;
		const file = `${ folder }/${ slug }.html`;

		await editor.goto( `${ BASE }/wp-admin/site-editor.php?p=${ encodeURIComponent( `/${ type }/${ id }` ) }&canvas=edit` );
		const { settled, blocks } = await settle( id );
		const html = source( folder, slug );

		check( `${ file } opens in the editor and its blocks settle`, settled );
		check( `${ file } loads at least its ${ blocksIn( html ) } blocks`, blocks.length >= blocksIn( html ) ? blocksIn( html ) : blocks.length, blocksIn( html ) );

		const invalid = blocks.filter( ( b ) => false === b.isValid );

		check( `${ file } opens with no invalid blocks`, invalid.map( ( b ) => b.path ), [] );

		for ( const b of invalid ) {
			console.log( `       ${ b.path }\n       file:   ${ b.original }\n       editor: ${ b.saved }` );
		}

		check( `${ file } shows its ${ slotsIn( html ) } Slot blocks`, blocks.filter( ( b ) => 'mai/slot' === b.name ).length, slotsIn( html ) );
	}

	const patterns = await editor.evaluate( async () => ( await wp.data.resolveSelect( 'core' ).getBlockPatterns() ).map( ( p ) => [ p.name, p.templateTypes ?? [] ] ) );
	const offered = ( name, template ) => patterns.some( ( [ n, types ] ) => n === name && types.includes( template ) );

	check( 'Post: With sidebar is offered for Single Posts', offered( 'mai/layout-post-with-sidebar', 'single' ) );

	// A pattern never opens as a template, so the loop above never sees its
	// markup. This parses it as the Design panel does when it applies one, and
	// walks every block, nested ones included.
	const layouts = [ [ 'mai/layout-post-with-sidebar', 'Post: With sidebar' ] ];
	const parsed = await editor.evaluate( async ( names ) => {
		const all = await wp.data.resolveSelect( 'core' ).getBlockPatterns();
		const count = ( blocks ) => blocks.reduce( ( n, b ) => n + 1 + count( b.innerBlocks ), 0 );
		const invalid = ( blocks, path ) => blocks.flatMap( ( b ) => {
			const here = [ ...path, b.name ];

			return [ ...( false === b.isValid ? [ here.join( ' > ' ) ] : [] ), ...invalid( b.innerBlocks, here ) ];
		} );

		return Object.fromEntries( names.map( ( name ) => {
			const blocks = wp.blocks.parse( all.find( ( p ) => p.name === name )?.content ?? '' );

			return [ name, { blocks: count( blocks ), invalid: invalid( blocks, [] ) } ];
		} ) );
	}, layouts.map( ( [ name ] ) => name ) );

	for ( const [ name, label ] of layouts ) {
		// Openers in the file itself. A template part's blocks are not in the
		// pattern's markup, so the parts it names are not counted.
		const want = ( readFileSync( `${ THEME }/patterns/${ name.split( '/' )[ 1 ] }.php`, 'utf8' ).match( /<!-- wp:[a-z]/g ) ?? [] ).length;

		check( `${ label } parses to its ${ want } blocks`, parsed[ name ].blocks, want );
		check( `${ label } holds no invalid blocks`, parsed[ name ].invalid, [] );
	}

	check( 'Alt Heading is in the Heading block\'s styles', await editor.evaluate( () => wp.data.select( 'core/blocks' ).getBlockStyles( 'core/heading' ).map( ( s ) => s.name ).includes( 'altheading' ) ) );

	await editor.close();

	check( 'no console errors or page errors', errors, [] );

	for ( const [ start, why ] of KNOWN_NOISE.filter( ( [ start ] ) => allowed.has( start ) ) ) {
		console.log( `       allowed: "${ start }", ${ why }` );
	}
} finally {
	await browser.close();
	setUp( 'clean', site.menus.join( ',' ) );
}

console.log( `\n${ pass } passed, ${ fail } failed\n` );
process.exit( fail ? 1 : 0 );
