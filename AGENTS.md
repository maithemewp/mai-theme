# Rules for working in this repo

## House conventions live in a skill

How Mike builds block themes (repo shape, token names, CSS, block CSS, block styles, PHP, templates, naming) is in the `mai-theme-scaffold` skill, at `~/.agents/skills/mai-theme-scaffold/SKILL.md`. Read it before changing anything here. This file holds only the rules for this repo.

**Any change to a house convention in this repo updates the skill in the same session.** The skill names this theme as the reference. When the two disagree, read the theme and tell Mike.

Reasoning lives in `docs/specs/2026-10-20-mai-theme-v3.md`. Calls made while building are in `docs/plans/2026-10-20-mai-theme-v3-build.md`, "Decided while building", and `docs/plans/2026-10-23-mai-theme-v3-review-changes.md`, "Decided for this plan".

## What this is

The starter every Mai block theme client site is copied from. It ships a Slot block for every Mai Slots location, so content areas work with no template editing. Mai Content Areas and the v2 Genesis themes carry on separately. Nothing migrates onto this.

Build as though a stranger will copy it: warnings explain themselves, and a missing plugin fails in a way someone can diagnose alone.

---

Invariants only. Each rule links to where its argument lives. If you are about to break one, that is a conversation, not a judgement call.

## House rules

These two are also in the skill, on purpose. → spec, "Conventions"

- **The starter never relies on Mai Slots rules for base content.** No block in the starter's markup carries a "Show when" rule (`maiSlotsRules`). Where posts and pages need different blocks, they get different parts or templates, as `entry-header` and `page-header` do.
- **A value is set once, in `theme.json`.** A block in a template, part, or pattern carries a style setting only when it really differs from `theme.json`.

## This repo

- **A pattern body equals the file it copies.** `patterns/layout-post-with-sidebar.php` matches `templates/with-sidebar-post.html`. `patterns/posts-grid.php` matches `parts/posts-grid.html` in everything but its query: the part follows the page, and the pattern lists the latest 9 posts, so it works on any page. `tests/slots.php` fails when either pair differs. → spec, "Templates"
- **`tests/run.sh` fails on `maiSlotsRules` or a stray `mai-theme-` name** in templates, parts, patterns, styles, CSS, PHP, or `theme.json`. Only `mai-theme-core` and `mai-theme-scaffold` are allowed. → spec, "The repo"
- **Fix the plumbing in `~/LocalPackages/mai-theme-core`,** never in `vendor/`, then run `composer update` here. → spec, "PHP"

## Git

- **Never push, tag, or cut a release without Mike's yes.** `main` is v3, the default branch since 2026-10-25. v1 lives on the `v1` branch.
- **A real site needs a switch first:** the path repositories move to GitHub (this theme's `mai-theme-core`, and the engine packages in Mai Content Areas), and `vendor/` is committed. Until then a fresh clone of `main` cannot run `composer install`. → `TODO.md`

## Testing

- `./tests/run.sh` runs every check against a throwaway site in `.wp-test/`, and builds the site first when that folder is missing. Delete `.wp-test/` to rebuild. The last line must be `All checks passed.` with a clean `debug.log`.
- **After a package commit, run `composer update maithemewp/mai-theme-core` here.** `vendor/` symlinks the package, so code edits are live at once. A file added to the package's `autoload.files` only loads once Composer rebuilds its file list, and `composer.lock` only moves with this.
- `php ~/LocalPackages/mai-theme-core/tests/run.php` tests the package with no install.
- **A test for a fixed bug should fail against the old behaviour.** Check that it does.

## Docs

- `docs/specs/` is the agreed design. `docs/plans/` holds the build plans.
- `STATE.md` says where the work stands. `TODO.md` lists what is open. `CHANGES.md` is the changelog.
- `AGENTS.md` is the real file. `CLAUDE.md` is a symlink to it: `ln -s AGENTS.md CLAUDE.md`. Edit `AGENTS.md`.

## This site

Nothing yet. A client copy writes its own rules here. A site rule that overrides a house rule says so by name. A site rule that proves good everywhere moves up into the skill.
