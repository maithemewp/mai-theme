# Mai Theme v3 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Mai Theme v3 block theme starter and its plumbing package, `mai-theme-core`, so a site built from it has every Mai Slots location working with no template editing.

**Architecture:** Two repos. `~/LocalPackages/mai-theme-core` is a Composer library of plain WordPress hooks, loaded with `autoload.files`, with its pure string logic tested with nothing installed. `~/Themes/mai-theme-v3` (git worktree, orphan branch `v3` of `maithemewp/mai-theme`) is the theme: `theme.json`, `style.css`, templates, parts, patterns, block style JSON, and per-block CSS, requiring the package through a path repository. A throwaway WordPress on SQLite proves it end to end over HTTP, and headless Chromium proves the Site Editor accepts every template and part.

**Tech Stack:** PHP 8.4, WordPress 7.1.x (floor 7.0), Composer, `afragen/wp-dependency-installer` ^4, Mai Content Areas (engine Mai Slots), Mai Columns, SQLite integration, Playwright (resolves from `~/node_modules`).

**Spec:** `docs/specs/2026-10-20-mai-theme-v3.md`. Read it before any task. Where this plan and the spec disagree, the spec wins and the disagreement goes in `STATE.md`.

## Global Constraints

- PHP 8.4. `declare( strict_types=1 );` in every PHP file. Namespace `Mai\Theme`. Named functions hooked with `__NAMESPACE__ . '\name'`, never closures, so a site can `remove_action()` them.
- WordPress floor 7.0 (`Requires at least: 7.0`). Test against the newest 7.1.x tag.
- Theme slug and text domain `mai-theme`, version `3.0.0`, theme name "Mai Theme". Package `maithemewp/mai-theme-core`.
- `theme.json` values use the shorthand `var:preset|…` and `var:custom|…`, never `var(--wp--preset--…)`, except inside CSS strings.
- Hooks are `mai_theme_` plus underscores. Bindings and blocks use the `mai/` namespace. Pattern slugs use `mai-theme/`.
- No em-dashes (`—`) in any user-facing string, README, or doc. No double-hyphen used as one either.
- Comments explain why, not what. Plain language, Oxford comma.
- Never push, tag, create a GitHub repo, or edit `~/.agents`. Commit locally only.
- Nothing launches a visible browser. Playwright runs `headless: true`.
- Every Markdown file: soft-wrapped paragraphs (one line each), no em-dashes.

## Review Focus

1. **Hand-written block markup that the editor rejects.** A template or part that renders fine but shows "This block contains unexpected or invalid content" in the Site Editor. Task 7's editor check opens every template and part and fails on any invalid block.
2. **A required plugin missing.** With Mai Content Areas or Mai Columns inactive, every page still returns 200 with no PHP errors, and Slot blocks render nothing. Task 6 runs the HTTP check a second time with each plugin deactivated.
3. **"Your latest posts" on the front page.** A stranger's fresh install shows posts on the home page, not an empty `front-page.html`. Task 2 adds the hierarchy fix, Task 6 tests it.
4. **A renamed client copy.** The theme installed under another folder name, as a client copy is, still loads its styles, fonts, block CSS, and package. Task 4's smoke check runs with the theme symlinked as `acme`.
5. **Odd files in `assets/css/blocks/`.** A non-CSS file, a file at the wrong depth, or uppercase letters are ignored, never turned into a bogus block name. Task 1's unit tests pin this.

---

### Task 1: `mai-theme-core` package, pure logic and tests

**Files:**
- Create: `~/LocalPackages/mai-theme-core/composer.json`, `src/legacy-css.php` (pure function only in this task), `src/block-css.php` (pure function only in this task), `tests/run.php`, `tests/fixtures/common.css`, `tests/fixtures/common.min.css`
- Create: `README.md`, `AGENTS.md`, `CLAUDE.md` (symlink to `AGENTS.md`), `STATE.md`, `CHANGES.md`, `.gitignore` (`vendor/`)

**Interfaces:**
- Produces: `Mai\Theme\strip_legacy_block_css( string $css ): string` and `Mai\Theme\block_name_from_path( string $path, string $root ): ?string`. Task 2 calls both.

- [ ] **Step 1: `git init` the package and write `composer.json`.** Name `maithemewp/mai-theme-core`, type `library`, license `GPL-2.0-or-later`, `require`: `php >=8.4`, `afragen/wp-dependency-installer ^4`. `autoload.files` lists every file in `src/` explicitly, in this order: `legacy-css.php`, `block-css.php`, then the Task 2 files. Description in plain words.

- [ ] **Step 2: Copy core's two stylesheets as fixtures.** From `~/Herd/beof/wp-includes/css/dist/block-library/common.css` and `common.min.css` (WordPress 7.1.2) to `tests/fixtures/`, with a one-line note in `tests/run.php` saying which version they came from and to refresh them each WordPress release.

- [ ] **Step 3: Write the failing tests in `tests/run.php`.** Same style as `~/LocalPackages/mai-slots/tests/run.php`: a `check( string $what, $actual, $expected = true )` helper, a pass and fail count, and a non-zero exit on any failure. No WordPress loaded. Assertions:
  - For each fixture: `strip_legacy_block_css()` removes exactly 15 rules (count by comparing occurrences of the 15 class names: 0 left), keeps `.has-border-color`, `.screen-reader-text`, and `.wp-element-button`, and leaves `{` and `}` counts equal.
  - A nested rule is left untouched: `':root .has-midnight-gradient-background{background:red;&:hover{opacity:1}}.keep{color:blue}'` returns unchanged.
  - A plugin gradient survives: `':root .has-acme-gradient-background{background:red}'` returns unchanged.
  - An empty string returns `''`.
  - `block_name_from_path( "$root/core/navigation.css", $root )` is `'core/navigation'`. `"$root/acme/grid-gallery.css"` is `'acme/grid-gallery'`. `"$root/mai-auth/account.css"` is `'mai-auth/account'`.
  - Returns `null` for: `"$root/core/navigation.txt"`, `"$root/navigation.css"` (no namespace folder), `"$root/core/sub/navigation.css"` (too deep), `"$root/Core/Navigation.css"` (uppercase), `"/elsewhere/core/navigation.css"` (outside root).

- [ ] **Step 4: Run `php tests/run.php`.** Expected: fatal or FAIL, functions not defined.

- [ ] **Step 5: Implement `strip_legacy_block_css()` in `src/legacy-css.php`.** The 15 class names exactly as listed in the spec's "theme.json" section: gradients `vivid-green-cyan-to-vivid-cyan-blue`, `purple-crush`, `hazy-dawn`, `subdued-olive`, `atomic-cream`, `nightshade`, `midnight` (each `:root .has-<name>-gradient-background`), greys `.has-very-light-gray-color`, `.has-very-dark-gray-color`, `.has-very-light-gray-background-color`, `.has-very-dark-gray-background-color` (each under `:root`), font sizes `.has-regular-font-size`, `.has-larger-font-size`, `.has-normal-font-size`, `.has-huge-font-size`. Return the original string when `preg_replace` fails or the result's brace counts differ. The docblock carries the why from the spec, briefly.

- [ ] **Step 6: Implement `block_name_from_path()` in `src/block-css.php`.** Lowercase `a-z0-9-` for both namespace and name, exactly one folder below `$root`, `.css` only.

- [ ] **Step 7: Run `php tests/run.php`.** Expected: every check `ok`, exit 0.

- [ ] **Step 8: Write `README.md`, `AGENTS.md` (+ `CLAUDE.md` symlink), `STATE.md`, `CHANGES.md`.** README: what it is in two sentences, what each file does, how to test (`php tests/run.php`), and that only one copy runs because only one theme is active. AGENTS.md: invariants only, the Global Constraints that apply, "never edited in a client copy", "every function named and hooked". STATE.md in the house shape (Now, Next, Blocked / waiting on, Verify, Gotchas).

- [ ] **Step 9: Commit.**

```bash
git add -A && git commit -m "Start mai-theme-core with the legacy CSS and block CSS path logic, tested with nothing installed"
```

---

### Task 2: `mai-theme-core` WordPress hooks

**Files:**
- Modify: `src/legacy-css.php`, `src/block-css.php` (add the hooks)
- Create: `src/setup.php`, `src/theme-json.php`, `src/bindings.php`, `src/template-parts.php`, `src/performance.php`, `src/dependencies.php`, `src/template-hierarchy.php`
- Modify: `composer.json` (`autoload.files`), `README.md`

**Interfaces:**
- Consumes: Task 1's two functions.
- Produces, all in `Mai\Theme`, all hooked by name:
  - `enqueue_styles(): void` on `wp_enqueue_scripts`: handle `mai-theme`, `get_stylesheet_uri()`, version `wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_stylesheet_directory() . '/style.css' )`.
  - `setup(): void` on `after_setup_theme`: `add_editor_style( 'style.css' )`.
  - `remove_default_theme_json_presets( \WP_Theme_JSON_Data $theme_json ): \WP_Theme_JSON_Data` on `wp_theme_json_data_default`, ported from `~/Herd/beof/wp-content/themes/beof/inc/theme-json.php`, typed.
  - `enqueue_block_css(): void` on `init`: globs `get_theme_file_path( 'assets/css/blocks' ) . '/*/*.css'`, maps with `block_name_from_path()`, skips `null`, calls `wp_enqueue_block_style( $block, [ 'handle' => 'mai-theme-' . str_replace( '/', '-', $block ), 'src' => …, 'path' => …, 'ver' => (string) filemtime( $path ) ] )`.
  - `remove_legacy_block_css(): void` on `wp_head` at priority 2: maps `strip_legacy_block_css()` over the `after` data of `wp-block-library`, skipping non-strings.
  - `register_bindings(): void` on `init`: source `mai/current-year`, label "Current year", `get_value_callback` returning `wp_date( 'Y' )`.
  - `register_template_part_areas( array $areas ): array` on `default_wp_template_part_areas`: adds `sidebar`, label "Sidebar", `area_tag` `aside`, icon `sidebar`, description in plain words.
  - `disable_emojis(): void` on `init`, ported from beof's `inc/performance.php`.
  - `register_dependencies(): void` on `after_setup_theme`: when `WP_Dependency_Installer` exists and `is_admin()`, registers the list from `dependencies(): array`, which returns the filtered (`mai_theme_dependencies`) list: Mai Content Areas (`mai-content-areas/mai-content-areas.php`, `maithemewp/mai-content-areas`, branch `main`, host `github`, `optional` false) and Mai Columns (`mai-columns/mai-columns.php`, `maithemewp/mai-columns`, branch `main`, host `github`, `optional` false). `WP_Dependency_Installer::instance( __DIR__ )->register( $list )->run()`.
  - `skip_front_page_for_posts( array $templates ): array` on `frontpage_template_hierarchy`: when `get_option( 'show_on_front' )` is `'posts'`, removes `'front-page'` so `home.html` shows the posts. Agent's call, recorded in the spec's "Left to the builder" spirit: a stranger's default install must not land on an empty front page.

- [ ] **Step 1: Write each file.** Signatures as above. Each docblock gives the why from the spec in one or two sentences.

- [ ] **Step 2: Lint.** Run: `for f in src/*.php; do php -l "$f"; done`. Expected: "No syntax errors" for each.

- [ ] **Step 3: Run `php tests/run.php`.** Expected: still passes. Loading `src/legacy-css.php` and `src/block-css.php` with no WordPress must not fatal, so hook registration in those two files is guarded with `function_exists( 'add_action' )`.

- [ ] **Step 4: Commit.**

```bash
git add -A && git commit -m "Add the theme hooks: styles, preset filter, block CSS, legacy CSS, binding, sidebar area, dependencies"
```

WordPress behaviour of these hooks is proven by Tasks 4 and 6.

---

### Task 3: Theme scaffold, `theme.json`, `style.css`, fonts, block style

**Files (all in `~/Themes/mai-theme-v3`):**
- Create: `style.css`, `theme.json`, `functions.php`, `composer.json`, `.gitignore`, `.distignore`, `assets/fonts/figtree-latin-wght-normal.woff2`, `assets/fonts/figtree-latin-wght-italic.woff2`, `assets/fonts/OFL-Figtree.txt`, `styles/blocks/heading/altheading.json`, `inc/.gitkeep`

**Interfaces:**
- Consumes: the package from Tasks 1 and 2 through `vendor/autoload.php`.
- Produces: every preset slug later tasks use: colours `base`, `base-2`, `base-3`, `contrast`, `contrast-2`, `primary`, `primary-2`. Spacing `10` to `80`. Font sizes `x-small` to `xxxx-large`. Font families `body`, `heading`. Shadows `primary`, `secondary`. Radius `default`, `half`, `quarter`, `full`. Custom `lineHeight.none|tight|snug|normal|relaxed|loose`. Template part areas `header`, `footer`, `navigation-overlay`, `sidebar`.

- [ ] **Step 1: `composer.json`.** Path repository `../../LocalPackages/mai-theme-core`, require `maithemewp/mai-theme-core: @dev`, `php >=8.4`. Run `composer install`. Expected: `vendor/autoload.php` exists and `vendor/maithemewp/mai-theme-core` is a symlink.

- [ ] **Step 2: `functions.php`.** `declare( strict_types=1 );`, namespace `Mai\Theme`, `defined( 'ABSPATH' ) || exit;`, require `vendor/autoload.php` when it exists, otherwise show an admin notice in plain words ("Mai Theme is missing its vendor folder. Run composer install in the theme folder.") and stop. Then require `inc/*.php`.

- [ ] **Step 3: `style.css` header.** Theme Name "Mai Theme", Theme URI `https://bizbudding.com/`, Author "BizBudding, Inc.", Author URI `https://bizbudding.com`, Description "A block theme starter for Mai Slots and Mai Content Areas.", Version `3.0.0`, Requires at least `7.0`, Tested up to `7.1`, Requires PHP `8.4`, Text Domain `mai-theme`, License GPL-2.0-or-later, plus the Utopia font scale URL from beof's header.

- [ ] **Step 4: `style.css` body,** in this order, every rule from the spec's "CSS" section:
  - Josh Comeau reset in `:where()`, from beof `style.css` lines 22 to 48, without beof's global `overflow-wrap` rule.
  - `:root` `text-underline-offset: 2px` and `accent-color: var(--wp--preset--color--primary)`.
  - Footer at the bottom, from beof lines 57 to 75, with `--mai-theme-site-min-height` replacing beof's variable name.
  - `text-wrap: pretty` on `:where(h1, h2, h3, h4, h5, h6, p)`.
  - Line-height pairing: `.has-xx-large-font-size, .has-xxx-large-font-size, .has-xxxx-large-font-size { line-height: var(--wp--custom--line-height--none); }`.
  - `overflow-wrap: anywhere` on `.wp-block-post-content` and `.wp-block-comment-author-name` only.
  - `html { scroll-padding-top: 40px; scrollbar-gutter: stable; }`, smooth scrolling inside `@media (prefers-reduced-motion: no-preference)`, `:target { scroll-margin-top: 16px; }`, `:where(ul, ol) { padding-inline-start: var(--wp--preset--spacing--40); }`.
  - Cross-page view transitions: `@view-transition { navigation: auto; }` inside `@media (prefers-reduced-motion: no-preference)`.
  - One global focus rule: `:where(a, button, input, select, textarea, summary, [tabindex]):focus-visible { outline: 2px solid var(--wp--preset--color--primary); outline-offset: 2px; }`.
  - No `.screen-reader-text` rule. Core's common block CSS already ships the full rule in 7.1, which balloon-juice's comment predates. Say so in a one-line comment.

- [ ] **Step 5: `theme.json`.** `$schema` `https://schemas.wp.org/wp/6.9/theme.json`, version 3. Settings and styles exactly as the spec's "theme.json" section, with these values pinned:
  - Palette (agent's call, warm neutrals and one blue): `base` #ffffff "Base", `base-2` #f5f5f4 "Base 2", `base-3` #e7e5e4 "Base 3", `contrast` #1c1917 "Contrast", `contrast-2` #57534e "Contrast 2", `primary` #2563eb "Primary", `primary-2` #1d4ed8 "Primary 2".
  - Font sizes: beof's eight `size` values exactly (`jq '.settings.typography.fontSizes' ~/Herd/beof/wp-content/themes/beof/theme.json`), with no `fluid` objects, and `typography.fluid` left out.
  - Spacing: beof's `10` to `80` exactly, same names.
  - Font families: `body` named "Body: Figtree" with beof's Figtree `fontFamily` stack and both `fontFace` entries (paths `file:./assets/fonts/…`, `fontDisplay` `swap`). `heading` named "Heading: Figtree" with the same stack and no `fontFace`.
  - Shadows: `primary` "Primary" `0 4px 16px rgba(0, 0, 0, 0.12)`, `secondary` "Secondary" `0 1px 3px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.06)`.
  - Radius: `default` "Default" `6px`, `half` "Half" `calc(var(--wp--preset--border-radius--default) / 2)`, `quarter` "Quarter" `calc(var(--wp--preset--border-radius--default) / 4)`, `full` "Full" `9999px`. Task 4 checks the `calc()` survives.
  - Aspect ratios: core's set under core's slugs and names: `square` 1, `4-3` 4/3, `3-4` 3/4, `3-2` 3/2, `2-3` 2/3, `16-9` 16/9, `9-16` 9/16. `defaultAspectRatios` false.
  - Custom line heights: `none` 1, `tight` 1.25, `snug` 1.375, `normal` 1.5, `relaxed` 1.625, `loose` 2.
  - Defaults off: `defaultPalette`, `defaultGradients`, `defaultDuotone`, `defaultFontSizes`, `defaultSpacingSizes`, `shadow.defaultPresets`, `dropCap`, `color.custom`, `color.customGradient`. Editor controls on: `customFontSize`, `letterSpacing`, `textTransform`, `writingMode`.
  - Styles: `typography.fontFamily` `var:preset|font-family|body`, `fontSize` `var:preset|font-size|medium`, `lineHeight` `var:custom|line-height|normal`. `elements.heading.typography` `fontFamily` `var:preset|font-family|heading`, `lineHeight` `var:custom|line-height|tight`. `elements.link` colour `primary`, `:hover` `primary-2`. `elements.button` background `primary`, text `base`, `:hover` background `primary-2`, radius `var:preset|border-radius|default`. Root spacing and layout as the spec says. Text colour `contrast`, background `base`.
  - `templateParts`: `header` (area `header`), `footer` (area `footer`), `mobile-menu` "Mobile menu" (area `navigation-overlay`), `sidebar` (area `sidebar`), `entry` "Entry" (area `uncategorized`), `posts-grid` "Posts grid" (area `uncategorized`).
  - `customTemplates`: `blank` "Blank", `canvas` "Canvas", `wide` "Wide", `narrow` "Narrow", `with-sidebar` "With sidebar", each with `postTypes` `["post", "page"]`.

- [ ] **Step 6: `styles/blocks/heading/altheading.json`.** `version` 3, `title` "Alt Heading", `slug` "altheading", `blockTypes` `["core/heading", "core/paragraph"]`, `styles.typography.fontFamily` `var:preset|font-family|heading`.

- [ ] **Step 7: Fonts.** Copy beof's two Figtree `woff2` files and `OFL-Figtree.txt`.

- [ ] **Step 8: `.gitignore` and `.distignore`.** `.gitignore`: `vendor/` (switches to committed at first release, as Mai Content Areas will), `.wp-test/`, `node_modules/`, `.DS_Store`. `.distignore`: `docs/`, `tests/`, `.wp-test/`, `AGENTS.md`, `CLAUDE.md`, `STATE.md`, `TODO.md`, `composer.json`, `composer.lock`, following balloon-juice's `.distignore`.

- [ ] **Step 9: Validate.** Run: `jq empty theme.json styles/blocks/heading/altheading.json && php -l functions.php`. Expected: no output from jq, "No syntax errors".

- [ ] **Step 10: Commit.**

```bash
git add -A && git commit -m "Scaffold Mai Theme v3: theme.json tokens, base CSS, Figtree, and the Alt Heading style"
```

---

### Task 4: Throwaway WordPress and the smoke check

**Files:**
- Create: `tests/setup.sh`, `tests/lib.php`, `tests/smoke.php`

**Interfaces:**
- Consumes: Task 3's theme, the package, `~/Plugins/mai-content-areas`, `~/Plugins/mai-columns`.
- Produces: `tests/setup.sh` (build) and `tests/setup.sh --run` (run every `tests/*.php` and `tests/*.mjs` check, then the clean `debug.log` check, then "All checks passed." or "FAILED"). `tests/lib.php` exports `check()`, `fetch( string $query ): array{status: int, body: string}` (GETs `http://localhost:8430/?$query` from the running server), and `wp_cli_php( string $code ): string` (runs PHP inside the site with `wp-load.php`). Tasks 6 and 7 use all three.

- [ ] **Step 1: `tests/setup.sh`,** modelled on `~/Plugins/mai-content-areas/tests/setup.sh`: WordPress at the newest 7.1.x tag from GitHub into `.wp-test/core`, SQLite integration, the same `wp-config.php` with `WP_HOME` and `WP_SITEURL` `http://localhost:8430`, install with admin / password. Stage Mai Content Areas the same way its own script does (path repositories need the `Plugins` and `LocalPackages` shape). Symlink `~/Plugins/mai-columns` (its `vendor/` and `build/` are present). Symlink the theme twice: as `wp-content/themes/mai-theme` and as `wp-content/themes/acme`. Activate both plugins and `mai-theme`. `--run` starts `PHP_CLI_SERVER_WORKERS=4 php -S localhost:8430 -t .wp-test/core` for the run and stops it after, failing early if the port is taken. The `debug.log` check filters only WordPress's own update-check noise, as Mai Content Areas does.

- [ ] **Step 2: Write `tests/smoke.php` checks** (run once with `mai-theme` active and once with `acme` active, switching with `switch_theme()` inside `wp_cli_php()`):
  - Home page returns 200.
  - The page contains `mai-theme-css` with a `style.css?ver=3.0.0.` URL from the active theme's folder.
  - Global styles contain `--wp--preset--color--primary-2`, `--wp--preset--spacing--80`, `--wp--preset--font-size--xxxx-large`, `--wp--preset--shadow--secondary`, `--wp--custom--line-height--snug`, `--wp--preset--aspect-ratio--16-9`, and `--wp--preset--font-family--heading`.
  - Global styles contain `--wp--preset--border-radius--half: calc(var(--wp--preset--border-radius--default) / 2)` exactly. If this fails, replace the two `calc()` values in `theme.json` with `3px` and `1.5px`, note it in `STATE.md`, and make the check assert those instead.
  - None of core's defaults print: no `--wp--preset--color--vivid-`, no `--wp--preset--gradient--`, no `--wp--preset--shadow--natural`, no `--wp--preset--font-size--huge` in global styles, no `wp-duotone`.
  - The legacy classes are gone from `wp-block-library-inline-css`: no `vivid-green-cyan`, no `has-very-light-gray`, no `has-huge-font-size`, and `.screen-reader-text` is still present.
  - The `@font-face` for Figtree points at the active theme's `assets/fonts/figtree-latin-wght-normal.woff2`, and that URL returns 200.
  - No emoji script (`wp-emoji-release`).

- [ ] **Step 3: Add a stub `templates/index.html`** holding only `<!-- wp:post-content /-->`, because a block theme with no templates counts as broken. Task 6 replaces it.

- [ ] **Step 4: Run `./tests/setup.sh`.** Expected: all smoke checks pass, "debug.log clean", "All checks passed."

- [ ] **Step 5: Commit.**

```bash
git add -A && git commit -m "Add the throwaway test site and the smoke check, including a renamed copy"
```

---

### Task 5: Template parts

**Files:**
- Create: `parts/header.html`, `parts/footer.html`, `parts/mobile-menu.html`, `parts/sidebar.html`, `parts/entry.html`, `parts/posts-grid.html`

**Interfaces:**
- Produces: part slugs used by Task 6's templates and patterns. Entry part contains the four entry slots. Sidebar part contains the two sidebar slots.

Slot block markup, everywhere in this plan: `<!-- wp:mai/slot {"location":"<id>","lock":{"remove":true}} /-->`.

- [ ] **Step 1: `parts/header.html`.** Read beof's `parts/header.html` and `parts/mobile-menu.html` first. A constrained group at wide size with Mai Columns: site logo plus site title on the left, the Navigation block on the right with `overlayMenu` `mobile` using the `mobile-menu` overlay part as beof does. The part's own tag stays the default `<header>`, so no inner `<header>` group. No utility bar.

- [ ] **Step 2: `parts/mobile-menu.html`.** beof's shape: `navigation-overlay-close`, then a vertical `nav` group with a Navigation block. No beof colours or fonts, only this theme's presets.

- [ ] **Step 3: `parts/footer.html`.** A `base-2` background group with a paragraph "© {year} {site title}" where the year is a paragraph bound to `mai/current-year` (`"metadata":{"bindings":{"content":{"source":"mai/current-year"}}}`) and the title is a Site Title block, inside Mai Columns that stack on small screens.

- [ ] **Step 4: `parts/entry.html`.** In order: slot `mai/before-entry`, Post Title (level 1), a meta group (Post Date, Post Author Name, Post Terms category) carrying `"metadata":{"maiSlotsRules":{"version":1,"sets":[[{"type":"post-type","config":{"operator":"is","values":["post"],"labels":{"post":"Posts"}}}]]}}` so it shows on posts only (agent's call: one entry part for posts and pages, and pages hide the meta through Mai Slots' own Show when rules; without the engine the meta shows on pages too, which is harmless), Post Featured Image, slot `mai/before-entry-content`, Post Content (`"layout":{"type":"constrained"}`), slot `mai/after-entry-content`, slot `mai/after-entry`.

- [ ] **Step 5: `parts/sidebar.html`.** Slot `mai/before-sidebar-content`, Search block (label "Search", button inside), Heading "Latest posts" (level 2, font size `large`), Latest Posts block (5 posts), slot `mai/after-sidebar-content`.

- [ ] **Step 6: `parts/posts-grid.html`.** Read beof's `parts/posts-grid.html`. A Query Loop (`inherit` true) with a grid Post Template (3 columns), each card: featured image (aspect ratio `3-2`, linked), Post Title (level 2, `large`, linked), Post Date (`small`, `contrast-2`), Post Excerpt (20 words). Then Query Pagination and Query No Results ("Nothing found." plus a Search block). No slots inside the part. The archive slots sit in the templates, around the part.

- [ ] **Step 7: Run `./tests/setup.sh --run`.** Expected: smoke still passes, log clean.

- [ ] **Step 8: Commit.**

```bash
git add -A && git commit -m "Add the header, footer, mobile menu, sidebar, entry, and posts grid parts"
```

---

### Task 6: Templates, layout patterns, and the slot map check

**Files:**
- Create: `templates/index.html` (replaces Task 4's stub), `home.html`, `front-page.html`, `page.html`, `single.html`, `archive.html`, `search.html`, `404.html`, `blank.html`, `canvas.html`, `wide.html`, `narrow.html`, `with-sidebar.html`
- Create: `patterns/layout-post-with-sidebar.php`, `patterns/layout-archive-list.php`
- Create: `tests/slots.php`

**Interfaces:**
- Consumes: Task 5's part slugs, Task 4's `fetch()`, `wp_cli_php()`, `check()`.

Shared shape for every template except Canvas: slot `mai/before-header`, header part, slot `mai/after-header`, a `main` group, slot `mai/before-footer`, footer part, slot `mai/after-footer`. `main` is a constrained group with padding top `50` and bottom `80` (beof) unless noted.

- [ ] **Step 1: Write the failing test `tests/slots.php`.** Set-up through `wp_cli_php()`: delete any old content areas, create one published content area per slot location, body `<p>MARK:<location id></p>`, placement with empty rules (`{"version":1,"sets":[]}`), using the same meta shape and `Mai\ContentAreas\Index` rebuild as `~/Plugins/mai-content-areas/tests/blockrules.php`'s `contentArea()`. Create a post with a category, a page, one page per custom template (meta `_wp_page_template` = `blank`, `canvas`, `wide`, `narrow`, `with-sidebar`), and a post with `with-sidebar`. For each URL, assert the exact ordered list of markers in the body (order checked by string position), and that no other marker appears:
  - **Single post** (`p=ID`) and **page** (`page_id=ID`) and **Wide** and **Narrow** pages: `before-header`, `after-header`, `before-entry`, `before-entry-content`, `after-entry-content`, `after-entry`, `before-footer`, `after-footer`.
  - **With sidebar** page and post: the eight above plus `before-sidebar-content` and `after-sidebar-content`, after `after-entry` and before `before-footer`.
  - **Blank** page and **front page** and **404** (`p=999999`): `before-header`, `after-header`, `before-footer`, `after-footer`.
  - **Canvas** page: no markers at all, and no `<header` element.
  - **Category archive** (`cat=ID`), **search** (`s=hello`), **blog home** (no query, `show_on_front` `posts`): `before-header`, `after-header`, `before-loop`, `after-loop`, `before-footer`, `after-footer`.
  - **Before header lands outside the header element:** the `MARK:mai/before-header` position is before the first `<header`.
  - **"Your latest posts" home** shows the post's title (proves `skip_front_page_for_posts()`). With `show_on_front` `page` and a front page set, the front page shows its content and the four global markers.
  - **Entry meta:** the post shows its date's year inside the meta group, the page does not.
  - Every response is 200 except the 404, which is 404.

- [ ] **Step 2: Run `./tests/setup.sh --run`.** Expected: `slots.php` FAILs on missing templates.

- [ ] **Step 3: Write the templates.**
  - `index.html`, `home.html`, `archive.html`, `search.html`: Query Title on archive and search (search adds a Search block), "Blog" heading on home only. Then slot `mai/before-loop`, `posts-grid` part, slot `mai/after-loop`, all inside `main` at wide size.
  - `single.html`, `page.html`: `entry` part inside `main`. `single.html` adds Post Navigation Link previous and next in Mai Columns, then the Comments block, after the entry part.
  - `front-page.html`: `main` with no padding holding Post Content (`"align":"full","layout":{"type":"constrained"}`), no title.
  - `404.html`: heading "Page not found", a paragraph "Try a search instead.", a Search block.
  - `blank.html`: `main` with zero padding holding Post Content full width.
  - `canvas.html`: Post Content only.
  - `wide.html`: as `page.html` with `main`'s `contentSize` set to the wide size (`1200px`).
  - `narrow.html`: as `page.html` with `main`'s `contentSize` `600px` (agent's call).
  - `with-sidebar.html`: `main` at wide size with Mai Columns `sizesLg` `["fill","300px"]`, `sizesMd` and `sizesSm` `["1/1"]`: entry part in the first column, sidebar part in the second.

- [ ] **Step 4: Write the two layout patterns.** PHP pattern headers:
  - `layout-post-with-sidebar.php`: Title "Post: With sidebar", Slug `mai-theme/layout-post-with-sidebar`, Template Types `single`, Inserter `false`, Post Types `wp_template`. Content identical to `with-sidebar.html`.
  - `layout-archive-list.php`: Title "Archive: List", Slug `mai-theme/layout-archive-list`, Template Types `index, home, archive, search`, Inserter `false`, Post Types `wp_template`. Same as `archive.html` but the Query Loop inlined (not the `posts-grid` part) with a list Post Template: one post per row, image left at 300px, title, date, and excerpt right.

- [ ] **Step 5: Run `./tests/setup.sh --run`.** Expected: `slots.php` all `ok`, smoke still passes, log clean.

- [ ] **Step 6: Add the missing-plugin pass to `tests/slots.php`.** After the main checks: deactivate Mai Content Areas, fetch every URL above, assert 200 (404 for the 404), no `MARK:` anywhere, and `debug.log` has nothing new. Reactivate. Then the same with Mai Columns deactivated. Run again. Expected: pass.

- [ ] **Step 7: Commit.**

```bash
git add -A && git commit -m "Add every template and the two layout patterns, with a check that each Mai Slots location lands where it should"
```

---

### Task 7: Block CSS, layout widths, and the Site Editor check

**Files:**
- Create: `assets/css/blocks/core/navigation.css`, `assets/css/blocks/core/post-template.css`, `assets/css/blocks/core/search.css`
- Create: `tests/editor.mjs`, `tests/editor.php` (set-up helper, prints JSON)

**Interfaces:**
- Consumes: Task 4's server and login (admin / password), Task 6's pages.

- [ ] **Step 1: Write the failing test `tests/editor.mjs`.** Playwright `chromium.launch( { headless: true } )`. Log in. Then:
  - **Every template and part opens with no invalid blocks.** For each of the 13 templates and 6 parts, open `site-editor.php?p=/wp_template/mai-theme//<slug>&canvas=edit` (parts: `/wp_template_part/mai-theme//<slug>`), wait for the editor, then in the page collect every block recursively from `wp.data.select( 'core/block-editor' ).getBlocks()` and assert none has `isValid === false`, and that the Mai Slots placeholder count matches the slot count expected for that file from Task 6's map. Also assert no `console.error` other than known core noise, listed explicitly in the test with a reason.
  - **The two layout patterns appear in the Design panel** for Single Posts and for Blog Home: `wp.data.select( 'core' ).getBlockPatterns()` filtered by `templateTypes` includes `mai-theme/layout-post-with-sidebar` for `single` and `mai-theme/layout-archive-list` for each of `index`, `home`, `archive`, `search`.
  - **Widths on the front end at 1440px wide:** `.wp-block-post-content` measures 740px on the page, 1200px on the Wide page, 600px on the Narrow page. An `alignfull` group added to the page's content spans the viewport.
  - **Block CSS loads only where its block is:** the page with the sidebar has `mai-theme-core-search-css`, a page without a Search block does not.
  - **Alt Heading** is in the heading's style list: `wp.data.select( 'core/blocks' ).getBlockStyles( 'core/heading' )` includes `altheading`.

- [ ] **Step 2: Run `./tests/setup.sh --run`.** Expected: FAIL on the block CSS handle and whichever widths or blocks are wrong.

- [ ] **Step 3: Write the three block CSS files.** Navigation: the overlay open state uses `base` background and `contrast` text, spacing from presets. Post Template: card gap `spacing|40`, image radius `var(--wp--preset--border-radius--default)`. Search: input border `base-3`, radius `default`, button uses the button element style. Fix whatever template markup Step 1 found invalid by re-saving the block in the editor's shape (copy what the editor serialises).

- [ ] **Step 4: Run `./tests/setup.sh --run`.** Expected: every check passes, log clean.

- [ ] **Step 5: Commit.**

```bash
git add -A && git commit -m "Add the starter block CSS and a headless check that the Site Editor accepts every template and part"
```

---

### Task 8: Docs, conventions skill draft, repo files

**Files:**
- Create: `README.md`, `AGENTS.md`, `CLAUDE.md` (symlink), `STATE.md`, `TODO.md`, `CHANGES.md`, `docs/plans/mai-theme-scaffold/SKILL.md` (draft)

- [ ] **Step 1: `README.md`.** What it is, requirements (PHP 8.4, WordPress 7.0, Mai Content Areas, Mai Columns), how the dependency installer behaves (installs and re-activates on wp-admin loads, removes Deactivate and Delete), and three numbered procedures in STE style (numbered, one imperative instruction per step, under 20 words, commands in code blocks, warnings before the step): "Start a client site", "Give every single post the sidebar" (the file copy, and the Site Editor layout choice), "Bring starter fixes into a client site" (`composer update maithemewp/mai-theme-core`). A short "Saving from the Site Editor" section on what Create Block Theme writes back (`patterns/<slug>.php` for templates with images). A "Testing" section: `./tests/setup.sh`, `./tests/setup.sh --run`.

- [ ] **Step 2: `AGENTS.md` + `CLAUDE.md` symlink.** At the top: house conventions live in the `mai-theme-scaffold` skill, which is a draft at `docs/plans/mai-theme-scaffold/SKILL.md` until Mike reviews it and it moves to `~/.agents/skills`. Any change to a house convention updates the skill in the same session. Then invariants only: slots locked against removal, `altheading` is a deliberate exception to hyphenated slugs, `inc/` is the site's own PHP, never edit `vendor/`, the token names are a contract. An empty "This site" section.

- [ ] **Step 3: Draft `docs/plans/mai-theme-scaffold/SKILL.md`.** Shape it on `~/.agents/skills/mai-plugin-scaffold/SKILL.md`. Frontmatter `name: mai-theme-scaffold` and a description with triggers ("new block theme", "start a client theme", "theme.json tokens", "where does this theme file go"). Names `mai-theme` as the reference theme and says: when this skill and the theme disagree, read the theme and tell Mike. Content distilled from the spec: repo shape, token names and why, CSS rules, block CSS and block style locations, PHP and the package, templates and slots, naming. No reasoning that the spec already holds; link to the spec instead.

- [ ] **Step 4: `STATE.md`, `TODO.md`, `CHANGES.md`.** STATE in the house shape. TODO lists what waits on Mike: the skill review, the first push (v3 becoming `main`, v1 to a `v1` branch), Mai Content Areas going public with an updater, switching path repositories to GitHub and committing `vendor/`. CHANGES: "3.0.0, unreleased".

- [ ] **Step 5: Note the hub-page gap in the engine.** Add one line to `~/LocalPackages/mai-slots/TODO.md`, uncommitted: a Loop insert inside a Query Loop on an ordinary page renders, but the Content Areas "In loops" tab does not list it, because the insert store only scans templates, parts, and synced patterns. Mai Theme v3's Wide template is for exactly those hub pages.

- [ ] **Step 6: Final run.** `./tests/setup.sh --run` in the theme and `php tests/run.php` in the package. Expected: both pass, log clean.

- [ ] **Step 7: Commit.**

```bash
git add -A && git commit -m "Add the README procedures, repo rules, and a draft of the mai-theme-scaffold skill"
```

## Decided while building

Calls made during the overnight build that the plan or spec did not settle. Each says what it costs if Mike wants it the other way. The Wide and Narrow entry is the one `AGENTS.md` leans on.

- **Wide and Narrow change width through a class on `main`.** The classes `mai-theme-wide` and `mai-theme-narrow` reset `--wp--style--global--content-size` in `style.css` (the wide size, and 600px). They do not set `contentSize` on `main`. The shared entry part holds a constrained Post Content, which would otherwise cap its children at the global content size, so changing `main`'s `contentSize` could never widen or narrow the content. Constrained layouts read that variable when no `contentSize` is set, so `main` and the entry part's Post Content both follow it, and `alignfull` still works. The width checks measure a paragraph inside Post Content, not the Post Content element. **If wrong:** Wide and Narrow need their own entry markup. That is a template-only change.
- **The first package task lists only its own two files in `autoload.files`.** The second task adds its files as it creates them. Listing files that do not exist yet breaks Composer's autoloader. **If wrong:** it costs nothing.
- **Commit trailers name the model that wrote the commit.** The package's commits keep `Co-Authored-By: Claude Sonnet 5.5`. **If wrong:** a reword of those commits, if Mike wants one trailer everywhere.
- **The package's branch is `develop`,** matching mai-slots, because Mike's repos use `develop` for work. **If wrong:** a branch rename.
- **The dependency installer gets the theme folder, not the package's folder.** `register_dependencies()` passes `get_stylesheet_directory()`. The package's own folder is `src`, so notices would read "[Src]" and "Required by: Src". The theme folder gives "Mai Theme", or a renamed copy's name. **If wrong:** the installer looks for a harmless `wp-dependencies.json` in the theme folder.
- **The emoji check asserts there is no `wp-emoji` at all.** The first idea was to look for `wp-emoji-release`, but that only prints with `SCRIPT_DEBUG` off, so the check could never fail on the test site. **If wrong:** it costs nothing.
- **Full-width and wide blocks in page and post content were being capped at the content width.** The entry part is a plain div inside constrained `main`, and a review caught it. The fix was made in the width task, which already owned the width measurements. **If wrong:** the width task measures again and picks another shape.
- **With sidebar carries post navigation and the Comments block,** in both the template and the "Post: With sidebar" pattern. Navigation shows on posts only, through a Mai Slots rule. Switching a post to the sidebar layout must not drop its comments. **If wrong:** removing one block group makes it bare.
- **The Post Template card gap lives in `theme.json`,** not in `post-template.css`. In CSS it would lose to core's global gap, or override a gap set on the block. This matches "`style.css` holds only what `theme.json` cannot say". **If wrong:** list-layout Post Templates someone adds get a gap of 40 instead of 30.
- **The `mai-theme-with-sidebar` class zeroes root padding inside the With sidebar columns.** The full-width entry wrapper otherwise gains 32px on each side. **If wrong:** a full-width background group placed in that template's content loses its side padding. The reset covers only that template, so the entry part inside other Mai Columns layouts would show the gap. This is for Mike to decide.
- **Parked: "Archive: List" drops the Search and Blog headings.** It uses an archive Query Title, so applying it to Search or the Blog home loses their heading and search form. One pattern serving four templates cannot carry a heading per template. It is left as the spec agreed rather than split into more patterns. **If wrong:** splitting it into one pattern per template.
