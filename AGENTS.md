# Rules for working in this repo

## House conventions live in a skill

How Mike builds block themes (repo shape, token names, CSS, block CSS, block styles, PHP, templates, naming) is written down in the `mai-theme-scaffold` skill. It is a draft at `docs/plans/mai-theme-scaffold/SKILL.md` until Mike reviews it and it moves to `~/.agents/skills`. Do not copy it there yourself.

**Any change to a house convention in this repo updates the skill in the same session.** The skill names this theme as the reference. When the two disagree, read the theme and tell Mike.

Reasoning lives in `docs/specs/2026-10-20-mai-theme-v3.md`. Calls made while building are in `docs/plans/2026-10-20-mai-theme-v3-build.md`, "Decided while building", and `docs/plans/2026-10-23-mai-theme-v3-review-changes.md`, "Decided for this plan".

## What this is

The starter every Mai block theme client site is copied from. It ships a Slot block for every Mai Slots location, so content areas work with no template editing. Mai Content Areas and the v2 Genesis themes carry on separately. Nothing migrates onto this.

Build as though a stranger will copy it: warnings explain themselves, and a missing plugin fails in a way someone can diagnose alone.

---

Invariants only. Each rule links to where its argument lives. If you are about to break one, that is a conversation, not a judgement call.

## House rules

- **The starter never relies on Mai Slots rules for base content.** No block in the starter's markup carries a "Show when" rule (`maiSlotsRules`). Where posts and pages need different blocks, they get different parts or templates, as `entry-header` and `page-header` do. `tests/setup.sh` fails when `maiSlotsRules` appears. → spec, "Conventions"
- **A value is set once, in `theme.json`.** A block in a template, part, or pattern carries a style setting only when it really differs from `theme.json`. A width equal to a `theme.json` size uses its variable, `var(--wp--style--global--content-size)` or `var(--wp--style--global--wide-size)`. → spec, "Conventions" and "theme.json"

## Templates and slots

- **Every starter Slot block is locked against removal, not movement:** `"lock":{"remove":true}`. → spec, "Slot blocks in each template"
- **Slot blocks sit in the template, outside the parts,** except the entry and sidebar slots, which sit in their own parts. → spec, "Slot blocks in each template"
- **A layout pattern holding Slot blocks is `Inserter: false`.** Otherwise WordPress offers it in posts. → spec, "Templates"
- **A pattern body equals the file it copies.** `patterns/layout-post-with-sidebar.php` matches `templates/with-sidebar-post.html`, and `patterns/posts-grid.php` matches `parts/posts-grid.html`. `tests/slots.php` fails when either pair differs.
- **Post designs for the Query Loop are `Block Types: core/query` and `Inserter: yes`.** `Inserter: no` hides a pattern from "Change design". → spec, "Templates"
- **Layout patterns list every template slug by name** in `Template Types:`. The Design panel matches exact slugs. → spec, "Templates"
- **There is no "Standard" template.** The default template is the standard width. → spec, "Templates"
- **Widths are native block settings, never a class.** `main` and Post Content carry the template's Content width. In the sidebar templates, `main` is at the wide size and Post Content is not full-aligned. → spec, "Templates"
- **Custom templates are limited by post type** with `postTypes` in `theme.json`. A post is never offered a page template, and a page is never offered a post template. `tests/slots.php` checks both. → spec, "Templates"
- **The sidebar template slugs never start with `page-` or `single-`.** Those are WordPress's own template names. → spec, "Templates"

## Tokens

- **The token names are a contract.** Palette, font sizes, spacing `10` to `80`, `body` and `heading`, the two shadows, the radius sizes, and the line-height scale. Patterns will refer to them. Add freely, rename or remove only with Mike. → spec, "Later: a pattern library"
- **`theme.json` uses the shorthand,** `var:preset|color|primary` and `var:custom|line-height|normal`. The long `var(--wp--preset--…)` form is only for CSS strings.
- **Core's default presets stay off,** removed at the source by `mai-theme-core`. Never turn a default back on. → spec, "theme.json"
- **Names say a role or a size, never a look.** → spec, "Anti-goals"

## Block styles

- **`altheading` is a deliberate exception to hyphenated slugs.** Three v2 sites already use `is-style-altheading`. Every block style file needs an explicit `slug` and at least one style property. → spec, "Block styles"

## PHP

- **`inc/` is the site's own PHP.** It is empty in the starter. `functions.php` loads the autoloader and then `inc/*.php`, nothing else.
- **Never edit `vendor/`.** The plumbing is the `maithemewp/mai-theme-core` package. Fix it in `~/LocalPackages/mai-theme-core`, then `composer update`. → spec, "PHP"
- **The package is never renamed,** in a client copy or anywhere. It keeps `Mai\Theme\Core` and its `mai` handles.
- **Named functions, never closures,** so a site can `remove_action()` them.
- **`functions.php` uses `Mai\Theme`.** The package uses `Mai\Theme\Core`, so the two cannot clash. → spec, "PHP"
- **Code in `inc/` uses the client's own namespace,** never `Mai\Theme\Core` or `Mai\Theme`. A function with the same name as one in either, now or in a later release, is a fatal error.

## Code

- PHP 8.4. `declare( strict_types=1 );` in every PHP file.
- **`mai-theme` names this theme and its package. `mai` names what the theme does.** Hooks start `mai_`, as in `mai_dependencies`. Stylesheet handles are `mai` and `mai-<namespace>-<block>`. CSS variables start `--mai-`. Pattern slugs, bindings, and blocks use `mai/`. `tests/setup.sh` fails on any other `mai-theme-` name in the theme's code. → spec, "The repo"
- **Per-block CSS** goes in `assets/css/blocks/<namespace>/<block>.css`. **Block styles** go in `styles/blocks/<block>/`. → spec, "CSS"
- **`style.css` holds only what `theme.json` cannot say.**
- Comments explain why, not what.

## Writing

- No em-dashes in any Markdown file or user-facing string, and no double hyphen used as one.
- Soft-wrap prose: one line per paragraph.
- README procedures follow ASD-STE100: numbered, one imperative instruction per step, under 20 words, commands in code blocks, warnings before the step they guard.

## Git

- **Never push, tag, or cut a release without Mike's yes.** The first push turns `v3` into `main` and moves v1 to a `v1` branch.
- **Pushing needs a switch first:** the path repositories move to GitHub (this theme's `mai-theme-core`, and the engine packages in Mai Content Areas), and `vendor/` is committed. → `TODO.md`

## Testing

- `./tests/setup.sh` builds a throwaway site in `.wp-test/` and runs every check. `./tests/setup.sh --run` reruns against it. The last line must be `All checks passed.` with a clean `debug.log`.
- **After a package commit, run `composer update maithemewp/mai-theme-core` here.** `vendor/` symlinks the package, so code edits are live at once. A file added to the package's `autoload.files` only loads once Composer rebuilds its file list, and `composer.lock` only moves with this.
- `php ~/LocalPackages/mai-theme-core/tests/run.php` tests the package with no install.
- **Nothing launches a visible browser.** The Site Editor check runs headless.
- **A test for a fixed bug should fail against the old behaviour.** Check that it does.

## Docs

- `docs/specs/` is the agreed design. `docs/plans/` holds build plans and the skill draft.
- `STATE.md` says where the work stands. `TODO.md` lists what is open. `CHANGES.md` is the changelog.
- `AGENTS.md` is the real file. `CLAUDE.md` is a symlink to it: `ln -s AGENTS.md CLAUDE.md`. Edit `AGENTS.md`.

## This site

Nothing yet. A client copy writes its own rules here. A site rule that overrides a house rule says so by name. A site rule that proves good everywhere moves up into the skill.
