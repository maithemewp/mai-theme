---
name: mai-theme-scaffold
description: House conventions for building a BizBudding/Mai WordPress block theme, and the files every one carries. Use when starting a client block theme, deciding where a theme file belongs, choosing theme.json token names, adding block CSS or a block style, or setting up a block theme's PHP. Triggers on "new block theme", "start a client theme", "scaffold a theme", "theme.json tokens", "what should the palette be called", "where does this theme file go", "add a block style", "block CSS", "bring this theme up to standard", "Mai Theme v3".
---

# Mai theme scaffold

**Status: draft.** This file lives at `docs/plans/mai-theme-scaffold/SKILL.md` in the `mai-theme` repo until Mike reviews it. It moves to `~/.agents/skills/mai-theme-scaffold/` after that review and after the first push. Until then it is not an installed skill. The paths below assume v3 has replaced v1 at `~/Themes/mai-theme`. Before the first push that folder is the old Genesis theme, and v3 is at `~/Themes/mai-theme-v3`.

House conventions for Mai block themes. `mai-theme` (`~/Themes/mai-theme`, repo `maithemewp/mai-theme`) is the reference for **folder layout, token names, and code shape**. When this file and that theme disagree, read the theme and tell Mike. Do not pick a side quietly.

The reasons behind each rule are in the spec, `~/Themes/mai-theme/docs/specs/2026-10-20-mai-theme-v3.md`. This file holds the rules and points to the section that holds the argument. Do not copy the argument here. Two copies drift.

**Scope.** Block themes only. Mai Content Areas and the v2 Genesis themes are a separate world and keep their own conventions. Nothing migrates onto this.

**How a client theme starts.** It is a renamed copy of `mai-theme`. The README's "Start a client site" procedure is the one to follow. Plumbing fixes reach old copies through the `maithemewp/mai-theme-core` package, so a client copy never forks that code.

## Repo shape

- `style.css`: the theme header, the reset, and only what `theme.json` cannot say.
- `theme.json`: all tokens, settings, and styles.
- `functions.php`: loads Composer's autoloader and then `inc/*.php`. Nothing else.
- `inc/`: the site's own PHP. Empty in the starter.
- `templates/`, `parts/`: plain HTML files.
- `patterns/`: layout patterns that swap a whole template.
- `styles/blocks/<block>/`: block styles, as JSON.
- `assets/css/blocks/<namespace>/<block>.css`: per-block CSS.
- `assets/fonts/`: self-hosted fonts and their licences.
- `tests/`: `setup.sh` and the checks it runs.
- `docs/`: `ideas`, `specs`, `plans`, `runbooks`.
- `vendor/`: the Composer package. Never edited.

Repo files: `AGENTS.md` (with `CLAUDE.md` as a symlink to it), `STATE.md`, `TODO.md`, `CHANGES.md`, and a `.distignore` that keeps docs and tests off servers. → spec, "The repo"

Floors: PHP 8.4, WordPress 7.0. → spec, "The repo"

## Tokens

The token names are a contract: future patterns refer to them. Add freely. Rename or remove only with Mike. → spec, "Later: a pattern library"

- **Palette:** `base`, `base-2`, `base-3`, `contrast`, `contrast-2`, `primary`, `primary-2`. Add `secondary` and others per client. → spec, "Palette"
- **Font sizes:** `x-small`, `small`, `medium`, `large`, `x-large`, `xx-large`, `xxx-large`, `xxxx-large`. Written as Utopia `clamp()` strings with no `fluid` object. → spec, "Font sizes"
- **Spacing:** slugs `10` to `80`. → spec, "Spacing"
- **Fonts:** two families named by role, `body` and `heading`, labelled "Body: Font" and "Heading: Font". No template names a font. → spec, "Fonts"
- **Line height:** `settings.custom.lineHeight` with Tailwind's names and values: `none`, `tight`, `snug`, `normal`, `relaxed`, `loose`. → spec, "Line-height"
- **Shadows:** `primary` and `secondary`, by role, never by size. → spec, "Shadows"
- **Radius:** `default`, `half`, `quarter`, `full`. `half` and `quarter` are computed from `default`. → spec, "Radius sizes"
- **Aspect ratios:** core's own set under core's own slugs, so the image block's control still works. → spec, "theme.json"

**Rules for writing them:**
- Names say a role or a size, never a look. No `large` for a shadow.
- A palette slug and its label agree.
- Values use the shorthand, `var:preset|color|primary` and `var:custom|line-height|normal`. The long form is only for CSS strings.
- The `var:custom|` shorthand is a literal swap of `|` for `--`, so it is kebab case.
- Core's default presets stay off, removed at the source by the package. Switching them off in `theme.json` only hides them from pickers. → spec, "theme.json"
- Starting values: layout 740px content and 1200px wide, root padding left and right at spacing 40, block gap at spacing 30, body size `medium`.
- Custom colours stay off. Custom font sizes, letter spacing, text transform, and writing mode stay on. → spec, "Editor controls"

## CSS

- **`style.css` holds only what `theme.json` cannot say.** It loads on the front end with a `filemtime` version, and in the editor through `add_editor_style( 'style.css' )`. The package does both.
- **Reset:** Josh Comeau's, every rule inside `:where()`, at the top.
- **Word wrapping:** `overflow-wrap: anywhere` on content areas only, never globally.
- **One global `:focus-visible` outline.**
- **Line height pairs with size in `style.css`,** because `theme.json` cannot bind leading to a size preset.
- **Footer stays down:** `.wp-site-blocks` is a flex column with a full-viewport minimum height, and `main` grows.
- **Width variations are classes on `main`** that reset `--wp--style--global--content-size`.
- **Modern CSS only when Baseline widely available.** See Mike's global rule.

→ spec, "CSS"

## Block CSS and block styles

**Block CSS:** one file per block, in a namespace folder. `assets/css/blocks/core/navigation.css` is for `core/navigation`. `assets/css/blocks/acme/grid-gallery.css` would be for `acme/grid-gallery`. The package hands each file to `wp_enqueue_block_style()`, so it prints only where its block renders. Never use flat names. → spec, "CSS"

**Block styles:** JSON files in `styles/blocks/<block>/`, with no PHP. → spec, "Block styles"
- Give every file an explicit `slug`.
- Give every file at least one style property.
- A style that needs real CSS keeps its JSON file and puts the extra rules in its block's CSS file.

**The starter ships one block style,** `altheading`, labelled "Alt Heading", on Heading and Paragraph. Its slug breaks the hyphen rule on purpose. → spec, "Block styles"

## PHP and the package

- **The plumbing is `maithemewp/mai-theme-core`,** a Composer package loaded through `autoload.files`, namespace `Mai\Theme`. A client copy never renames it and never edits `vendor/`. → spec, "PHP"
- **No helpers and no config API.** Plain hooks calling core.
- **Named functions, hooked with `__NAMESPACE__ . '\name'`,** never closures, so a site can `remove_action()`.
- **`declare( strict_types=1 );`** in every PHP file, PHP 8.4, WordPress spacing inside parentheses.
- **`inc/` is the site's own PHP.** Client features still belong in the client's own plugin.
- **Required plugins** are Mai Content Areas and Mai Columns, installed by `afragen/wp-dependency-installer` from the package's config. The theme never bundles the engine. → spec, "Dependencies"
- **Releases:** no tags and no pushes without Mike's yes. The theme commits `vendor/` once the package ships from GitHub. → spec, "PHP"

## Templates and slots

- **Standard templates:** `index`, `home`, `front-page`, `page`, `single`, `archive`, `search`, `404`. Each is header part, `<main>` as a constrained group, then footer part.
- **Custom templates:** Blank, Canvas, Wide, Narrow, With sidebar. There is no "Standard". → spec, "Templates"
- **Parts:** header, footer, mobile menu, sidebar, and one shared entry part used by single, With sidebar, Wide, and Narrow.
- **Every Mai Slots location gets a Slot block wherever it applies,** locked with `"lock":{"remove":true}`. Canvas has none. The list per template is in the spec. → spec, "Slot blocks in each template"
- **Layout patterns** for swapping a whole template are hidden, list each template slug in `Template Types:`, and are `Inserter: false` when they hold Slot blocks. → spec, "Templates"
- **Templates are plain HTML,** not one-line pattern references.
- **Saving from the Site Editor** with Create Block Theme moves any template with an image into `patterns/<slug>.php`. The README says so. → spec, "Templates"

## Naming

- **Theme slug and text domain:** the same, and the folder name. The starter is `mai-theme`, not `mai`, because a client copy is renamed by search and replace.
- **Hooks:** `mai_theme_` and underscores.
- **Block bindings and blocks:** the `mai/` namespace, as in `mai/current-year`.
- **Pattern slugs:** `mai-theme/` plus a hyphenated name.
- **Block style slugs:** hyphenated. `altheading` is the one exception.
- **Template and part slugs:** hyphenated, as in `with-sidebar` and `mobile-menu`.

## Docs and tests

- `docs/` has the four folders: `ideas`, `specs`, `plans`, `runbooks`. Files are `YYYY-MM-DD-<slug>.md`.
- The README holds numbered procedures in ASD-STE100: numbered, one imperative instruction per step, under 20 words, commands in code blocks, warnings before the step they guard.
- `./tests/setup.sh` builds a throwaway SQLite site in `.wp-test/` and runs every check. `--run` reruns them. The run must end `All checks passed.` with a clean `debug.log`.
- Browser checks run headless.

## Keeping this file honest

Each theme's `AGENTS.md` keeps only that site's rules and points here at the top. A site rule that overrides a house rule says so by name. A site rule that proves good everywhere moves up into this file.

Any change to a house convention in `mai-theme` updates this file in the same session.
