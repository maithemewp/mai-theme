# Mai Theme v3 Review Changes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Bring the built Mai Theme v3 and `mai-theme-core` in line with the spec as revised after Mike's review on 2026-10-22 and 2026-10-23.

**Architecture:** Same two repos as the first build. `~/LocalPackages/mai-theme-core` (branch `develop`) is renamed to `Mai\Theme\Core` and gains the login page, Gravity Forms colours, the video embed shape, empty paragraph stripping, and a Patterns admin link. `~/Themes/mai-theme-v3` (worktree, branch `v3`) splits the shared entry part, sets every width natively, splits templates by post type, swaps the archive layout pattern for three Query Loop designs, rebuilds the footer, and removes every Mai Slots rule and every block setting that repeats `theme.json`. The theme's `vendor/` symlinks the package, so a package change is live in the theme at once. Every task therefore ends with `./tests/setup.sh --run` green, updating whichever tests its own change breaks.

**Tech Stack:** PHP 8.4, WordPress 7.1.3 (floor 7.0), Composer, Mai Content Areas, Mai Columns, Playwright headless.

**Spec:** `docs/specs/2026-10-20-mai-theme-v3.md` (revised 2026-10-23). Where this plan and the spec disagree, the spec wins, except the three spec amendments listed under "Decided for this plan", which this plan makes in Task 6.

## Global Constraints

- Everything in the first plan's Global Constraints still binds (`docs/plans/2026-10-20-mai-theme-v3-build.md`): PHP 8.4, `strict_types`, named hooked functions, headless only, no push, no tag, no `~/.agents` edits, soft-wrapped Markdown with no em-dashes.
- Package namespace `Mai\Theme\Core`. Theme `functions.php` namespace `Mai\Theme`.
- Names: `mai-theme` only where it names this theme or package (folder, slug, text domain, `maithemewp/mai-theme-core`, repo, skill). Everything the theme does uses `mai`: stylesheet handle `mai`, block CSS handles `mai-<namespace>-<block>`, filter `mai_dependencies`, CSS variable `--mai-site-min-height`, login handles `mai-login-global`, `mai-login`, `mai-login-theme`, login variables `--mai-login-background`, `--mai-login-card-background`, `--mai-login-logo-height`, pattern slugs `mai/…`.
- No block in the starter carries `maiSlotsRules`.
- A block carries a style setting only when it differs from `theme.json`. Widths that equal a `theme.json` size use its variable: `var(--wp--style--global--wide-size)` for 1200px and `var(--wp--style--global--content-size)` for 740px. Only Narrow's 600px is a literal.
- No custom layout classes. Widths are native block settings.
- Templates and patterns must stay valid in the Site Editor (the editor check fails any invalid block).
- Every task ends with `./tests/setup.sh --run` passing and `debug.log` clean, and `php ~/LocalPackages/mai-theme-core/tests/run.php` passing.

## Decided for this plan (agent's calls, recorded in the spec in Task 6)

- **With sidebar's `main` is 1200px** (`var(--wp--style--global--wide-size)`), as it is today. The spec's "empty everywhere else" would make it 740px, which leaves no room for the sidebar.
- **The two sidebar templates are titled "Post with sidebar" and "Page with sidebar",** slugs `with-sidebar-post` and `with-sidebar-page`, so they can be told apart in the Site Editor's template list. Neither slug starts with `page-` or `single-`, which would collide with WordPress's own template names.
- **The footer's first column holds a Site Logo block and a Site Title block,** as the header does. With no logo set, only the title shows.
- **No new login filters.** "Hooks start `mai_login_`" applies to any hook the package adds later. The login functions themselves are named functions in `Mai\Theme\Core`, which a theme can `remove_action()`.

## Review Focus

1. **Width settings that look right in the editor but render wrong.** The committed editor check measures every template on the front end at 1440px against the spec's numbers (Task 3).
2. **A page or post showing the other type's blocks.** Pages never show the meta row, post navigation, or comments; posts on Single and "Post with sidebar" do, with Mai Content Areas active and inactive (Task 3).
3. **The login page with no site logo, and with one** (Task 2).
4. **Renamed handles leaving a stale reference** anywhere in code or docs (Tasks 1, 3, and 6).
5. **A client copy under another folder name:** the `acme` pass covers the renamed handles, the login styles, and the installer's label (Tasks 1 and 2).

---

### Task 1: Package rename, names, and the installer label

**Files:** package `src/*.php`, `tests/run.php`, `README.md`, `AGENTS.md`, `STATE.md`, `CHANGES.md`; theme `tests/smoke.php`, `tests/editor.mjs`, `tests/installer.php`, `style.css` (the min-height variable), `composer.lock`.

**Interfaces:**
- Produces: namespace `Mai\Theme\Core`; stylesheet handle `mai`; block CSS handles `mai-<namespace>-<block>`; filter `mai_dependencies`; `dependency_label( string $label, string $source ): string` on `wp_dependency_dismiss_label` (2 arguments, installer line 911), returning `wp_get_theme()->get( 'Name' )` only when `$source === get_stylesheet()`, else `$label` unchanged. It runs in wp-admin only, as the installer does.

- [ ] **Step 1:** Update `tests/run.php` to the new namespace. Update the theme's `smoke.php` and `editor.mjs` handle expectations (`mai`, `mai-core-navigation`, and so on). Add to `installer.php` a check, run with `acme` active (switch inside the script, restore on shutdown), that the notice label reads the active theme's "Theme Name" and not "Acme". Run both suites. Expected: failures on the new names.
- [ ] **Step 2:** Rename the namespace in every `src/` file and hook string; rename the handles, the filter, and `--mai-theme-site-min-height` to `--mai-site-min-height` in the package and the theme's `style.css`. Add `dependency_label()` with a one-sentence why.
- [ ] **Step 3:** Run `composer update maithemewp/mai-theme-core` in the theme, then both suites. Expected: all pass, log clean.
- [ ] **Step 4:** Update the package docs. Commit in each repo: "Rename the package namespace to Mai\Theme\Core, drop -theme from what it names, and label installer notices with the theme name".

### Task 2: Package additions

**Files:** package: create `src/login.php`, `src/login.css`, `src/gravity-forms.php`, `src/embeds.php`, `src/content.php`, `src/admin.php`; modify `composer.json` `autoload.files`, `tests/run.php`, `README.md`. Theme: `tests/smoke.php`.

**Interfaces:**
- **Login,** ported from `~/Herd/balloon-juice/wp-content/themes/balloon-juice/inc/login.php` (`CustomLogin`, lines 57 to 286) into named functions. Keep its hooks: `login_enqueue_scripts`, the `login_header` and `login_footer` output buffer at 999 that rewrites the submit button with `WP_HTML_Tag_Processor` (lines 90 to 135), and `login_headerurl`. Add `login_headertext` returning the escaped Site Title for the no-logo case. Style order, all printed through `wp_enqueue_style` dependencies on `login_enqueue_scripts`: `mai-login-global` (registered with no file, `wp_get_global_stylesheet()` added inline), then `mai-login` (no file, `src/login.css` read and added inline, so nothing needs a public URL from the package), then `mai-login-theme` (the active theme's `assets/css/login.css` by `get_theme_file_uri()`, only when it exists). Fonts with `wp_print_font_faces()` on `login_head`. Logo: the site logo (`custom_logo`) as `CustomLogin` sets it, a CSS background on `.login h1 a` at `--mai-login-logo-height`; no logo means the Site Title as text in the heading font. Hide the WordPress logo either way. `src/login.css` sets the spec's look with the three `--mai-login-*` variables defaulting to the spec's tokens, and uses only this theme's palette slugs.
- **`gravity_form_colors( mixed $styles ): mixed`** on `gform_default_styles`, ported from `~/Herd/beof/wp-content/themes/beof/inc/gravity-forms.php:17`, keeping its `mixed` type because Gravity Forms passes `false`. Map beof's keys to this theme's palette (`secondary` becomes `primary-2`). A no-op without Gravity Forms.
- **Embed shape,** ported from `balloon-juice/inc/embed-shape.php`.
- **`strip_empty_paragraphs( string $content ): string`** on `the_content`, ported from `balloon-juice/inc/cleanup.php:45`, returning `$content` unchanged when `preg_replace` returns null (invalid UTF-8).
- **`add_patterns_link(): void`** on `admin_menu`, ported from `balloon-juice/inc/admin.php`.

- [ ] **Step 1:** Tests first. `tests/run.php`: `strip_empty_paragraphs()` removes `<p></p>`, `<p> </p>`, `<p>&nbsp;</p>`; keeps `<p>0</p>`, `<p class="x">Text</p>`, `<p><img src="a.jpg"></p>`; returns invalid UTF-8 input unchanged. Theme `smoke.php`, in both the `mai-theme` and `acme` passes: `wp-login.php` returns 200 and carries `mai-login-global` before `mai-login`, Figtree's `@font-face`, the block-style button classes on the submit button, and the Site Title with no logo; with a site logo set (an attachment created by the test, removed on shutdown), the logo's URL in the `.login h1 a` background; a temporary `assets/css/login.css` written into the active theme by the test (removed on shutdown) loads as `mai-login-theme` after `mai-login`; a rendered post's empty paragraphs are gone; an editor user sees the Appearance → Patterns link. Run; expected: failures.
- [ ] **Step 2:** Implement. Read each source first and keep only generic behaviour.
- [ ] **Step 3:** Both suites pass, log clean. Commit in each repo: "Add the login page, Gravity Forms colours, embed shape, empty paragraph stripping, and a Patterns link".

### Task 3: Theme structure, widths, and templates by type

**Files:** theme: delete `parts/entry.html`, `templates/with-sidebar.html`, `patterns/layout-archive-list.php`; create `parts/entry-header.html`, `parts/page-header.html`, `parts/entry-footer.html`, `templates/with-sidebar-post.html`, `templates/with-sidebar-page.html`; modify `templates/page.html`, `single.html`, `wide.html`, `narrow.html`, `index.html`, `home.html`, `archive.html`, `search.html`, `patterns/layout-post-with-sidebar.php`, `theme.json`, `style.css`, `functions.php`, `assets/css/` (remove anything serving removed classes), `tests/slots.php`, `tests/editor.mjs`, `tests/editor.php`, `tests/setup.sh`.

- [ ] **Step 1, tests:** rewrite `slots.php`'s map to the spec's "Slot blocks in each template" for page, post, Wide, Narrow, both sidebar templates, Blank, Canvas, front page, 404, archive, search, and blog home. Pages never show the meta row, post navigation, or comments; posts on Single and "Post with sidebar" do. Run the map again with Mai Content Areas inactive. Assert Wide, Narrow, Blank, Canvas, and "Page with sidebar" are not offered for posts, and "Post with sidebar" is not offered for pages (`wp_get_theme()->get_page_templates( null, $type )`). In `editor.mjs`: the new file list; "Post: With sidebar" parses with no invalid blocks; front-end widths at 1440px: page text 740 and a full group spanning the window; Narrow text 600 and a wide group 740; Wide text 1200; listing templates' grid 1200; each sidebar template's Post Content inset 0 in its column. In `setup.sh --run`: a grep that fails if `maiSlotsRules`, or `mai-theme-` other than in `mai-theme-core` and `mai-theme-scaffold`, appears in templates, parts, patterns, CSS, or PHP. Run; expected: failures.
- [ ] **Step 2, parts:** `entry-header`, `page-header`, and `entry-footer` exactly as the spec's "Templates" describes, each header inside one wrapper group. No `maiSlotsRules` anywhere in the theme.
- [ ] **Step 3, templates:** Post Content directly in each template, full-aligned and constrained with the template's Content width, and `main` with the same width, per the Global Constraints' variables (Narrow `600px` with a Wide width of `var(--wp--style--global--content-size)`). Sidebar templates as the spec and "Decided for this plan" describe, `main` at the wide size, Post Content not full-aligned with `contentSize` and `wideSize` `100%`. Delete the three layout classes and their CSS.
- [ ] **Step 4, theme.json and patterns:** `templateParts` lists the new parts and drops `entry`; `customTemplates` gives each template its `postTypes` per the spec and its title per "Decided for this plan". `layout-post-with-sidebar.php` matches `with-sidebar-post.html` byte for byte after `?>`, slug `mai/layout-post-with-sidebar`. `functions.php` to `Mai\Theme`.
- [ ] **Step 5:** Both suites pass, log clean. Commit: "Split the entry part around Post Content, set widths natively, and give posts and pages their own sidebar templates".

### Task 4: Query Loop designs, footer, and theme.json repeats

**Files:** theme: create `patterns/posts-grid.php`, `patterns/posts-archive.php`, `patterns/posts-list.php`; modify `parts/posts-grid.html`, `parts/footer.html`, `theme.json` as needed, every template, part, and pattern with a repeat; `tests/smoke.php`, `tests/editor.mjs`, `tests/slots.php`.

- [ ] **Step 1, tests:** `editor.mjs`: the three designs parse with no invalid blocks, and `getPatternsByBlockTypes( 'core/query' )` for a Query Loop includes all three. `slots.php`: `parts/posts-grid.html` equals the body of `patterns/posts-grid.php` after `?>` byte for byte. `smoke.php`: the footer shows the current year (its saved fallback text is not a year, so this proves the binding), holds no Navigation block, and shows the Site Title. Run; expected: failures.
- [ ] **Step 2:** The three designs as the spec describes, each with header `Block Types: core/query` and `Inserter: yes`, since `Inserter: no` hides a pattern from "Change design" (`block-editor.js:15537`, `15563`). Slugs `mai/posts-grid`, `mai/posts-archive`, `mai/posts-list`. Pagination and "Nothing found" in each, tokens only.
- [ ] **Step 3:** The footer per the spec's "Template parts" and "Decided for this plan", the copyright bound to `mai/current-year` with fallback text `Year`.
- [ ] **Step 4:** Sweep every template, part, and pattern for block style settings that repeat `theme.json` (`styles`, `styles.elements`, `styles.blocks.<block>`), the way the balloon-juice and Horizon West sweep did (`~/tmp/theme-json-repeats/summary.md`): remove repeats, keep real overrides, and measure every changed property before and after on the test site; revert any that changes. List removals and kept overrides in the commit body. Remove the Post Template `blockGap` from `parts/posts-grid.html`.
- [ ] **Step 5:** Both suites pass, log clean. Commit: "Add the three post designs, rebuild the footer, and remove settings that repeat theme.json".

### Task 5: Prove every new check can fail

**Files:** none committed.

- [ ] **Step 1:** For each check added in Tasks 1 to 4, break the code it guards (one at a time, in a scratch change), run the check, see it fail, and revert. Record each in the report. `git status --short` must be clean in both repos at the end.

### Task 6: Docs, conventions, and spec amendments

**Files:** theme `README.md`, `AGENTS.md`, `STATE.md`, `TODO.md`, `CHANGES.md`, `docs/plans/mai-theme-scaffold/SKILL.md`, `docs/specs/2026-10-20-mai-theme-v3.md`; package `README.md`, `STATE.md`, `CHANGES.md`.

- [ ] **Step 1:** Sweep `AGENTS.md`, `SKILL.md`, and both READMEs for every fact this build changed: package namespace, handles, hooks, pattern slugs, the shared entry part, the sidebar template slugs, `functions.php`'s namespace, and the footer. Then add the two house rules (no Mai Slots rules in starter markup; set a value once in `theme.json`) to `AGENTS.md` and the skill, each linking to the spec's "Conventions".
- [ ] **Step 2:** README: the templates by post type; the "Give every single post the sidebar" procedure rewritten for `with-sidebar-post.html` and the "Post: With sidebar" layout; switching post designs with "Change design" on the `posts-grid` part's Query Loop; customising the login page in three layers; renaming a client copy changes the folder, `style.css`, and `composer.json`'s `name`, never by search and replace. Procedures in strict STE.
- [ ] **Step 3:** `TODO.md`: add the spec's "Later" items; remove items this build finished or made obsolete (the footer year check, the "only" wording, the With sidebar padding reset, the spec amendments). `STATE.md` rewritten, about 40 lines, no check counts. `CHANGES.md` under 3.0.0 unreleased in both repos.
- [ ] **Step 4:** Amend the spec with the four calls under "Decided for this plan", each marked "agent's call".
- [ ] **Step 5:** `rg -n '—'` on every changed Markdown file prints nothing; paragraphs are single lines. Both suites pass. Commit in each repo: "Document the review changes and the two house rules".
