# Rules for working in this repo

## House conventions live in a skill

How Mike builds block themes (repo shape, token names, CSS, block CSS, block styles, PHP, templates, naming) is written down in the `mai-theme-scaffold` skill. It is a draft at `docs/plans/mai-theme-scaffold/SKILL.md` until Mike reviews it and it moves to `~/.agents/skills`. Do not copy it there yourself.

**Any change to a house convention in this repo updates the skill in the same session.** The skill names this theme as the reference. When the two disagree, read the theme and tell Mike.

Reasoning lives in `docs/specs/2026-10-20-mai-theme-v3.md`. Decisions made while building are in `docs/plans/2026-10-20-mai-theme-v3-build.md` and its ledger in `.superpowers/sdd/`.

## What this is

The starter every Mai block theme client site is copied from. It ships a Slot block for every Mai Slots location, so content areas work with no template editing. Mai Content Areas and the v2 Genesis themes carry on separately. Nothing migrates onto this.

Build as though a stranger will copy it: warnings explain themselves, and a missing plugin fails in a way someone can diagnose alone.

---

Invariants only. Each rule links to where its argument lives. If you are about to break one, that is a conversation, not a judgement call.

## Templates and slots

- **Every starter Slot block is locked against removal, not movement:** `"lock":{"remove":true}`. → spec, "Slot blocks in each template"
- **Slot blocks sit in the template, outside the parts,** except the entry and sidebar slots, which sit in their own parts. → spec, "Slot blocks in each template"
- **A layout pattern holding Slot blocks is `Inserter: false`.** Otherwise WordPress offers it in posts. → spec, "Templates"
- **A layout pattern body equals its template.** `patterns/layout-post-with-sidebar.php` matches `templates/with-sidebar.html`. `tests/slots.php` fails when they differ.
- **Layout patterns list every template slug by name** in `Template Types:`. The Design panel matches exact slugs. → spec, "Templates"
- **There is no "Standard" template.** The default template is the standard width. → spec, "Templates"
- **Wide and Narrow change width through a class on `main`,** which resets `--wp--style--global--content-size` in `style.css`. They never set `contentSize` on `main`. → `.superpowers/sdd/2026-10-20-mai-theme-v3-build/progress.md`, first ruling

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
- **The package is never renamed,** in a client copy or anywhere. It keeps `Mai\Theme` and its `mai-theme-` handles.
- **Named functions, never closures,** so a site can `remove_action()` them.

## Code

- PHP 8.4. `declare( strict_types=1 );` in every PHP file.
- **Hooks:** `mai_theme_` and underscores. **Bindings and blocks:** the `mai/` namespace. **Pattern slugs:** `mai-theme/`.
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
- `php ~/LocalPackages/mai-theme-core/tests/run.php` tests the package with no install.
- **Nothing launches a visible browser.** The Site Editor check runs headless.
- **A test for a fixed bug should fail against the old behaviour.** Check that it does.

## Docs

- `docs/specs/` is the agreed design. `docs/plans/` holds build plans and the skill draft.
- `STATE.md` says where the work stands. `TODO.md` lists what is open. `CHANGES.md` is the changelog.
- `AGENTS.md` is the real file. `CLAUDE.md` is a symlink to it: `ln -s AGENTS.md CLAUDE.md`. Edit `AGENTS.md`.

## This site

Nothing yet. A client copy writes its own rules here. A site rule that overrides a house rule says so by name. A site rule that proves good everywhere moves up into the skill.
