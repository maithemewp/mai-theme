# State
Updated: 2026-10-25 by Claude (Opus 5.5)

## Now

v3 is `main` on GitHub, the default branch of `maithemewp/mai-theme` since 2026-10-25, and v1 is on a `v1` branch. `~/Themes/mai-theme` is now a checkout of `main`. The `mai-theme-v3` worktree is gone, and v1's only untracked folder, an `includes/` holding a `.DS_Store`, is in `~/tmp/mai-theme-v1-includes/`. The plumbing package `~/LocalPackages/mai-theme-core` is still local on `develop`, so a fresh clone of `main` cannot run `composer install` yet. Nothing is tagged or released.

The `mai-theme-scaffold` skill is installed at `~/.agents/skills/mai-theme-scaffold/`, with a symlink from `~/.claude/skills`. `AGENTS.md` is trimmed to a pointer to it, the two house rules, this repo's own rules, and an empty "This site" section. The test script is `tests/run.sh`, here and in Mai Content Areas.

## Next

1. Mike runs his smoke test, then decides the two settings below.
2. Point balloon-juice's `CONVENTIONS.md` at the skill, with Mike's yes.

## Blocked / waiting on

**After Mike's smoke test:**
- **The footer headings at the `medium` size.**
- **"Posts: List" at 80px, side by side everywhere.** Stacking on phones would also stack it in every 300px sidebar, because Mai Columns switches on its container's width.

**Mike:**
- **Whether `master`, `develop`, and `v3` are deleted on GitHub.**
- **Mai Content Areas going public,** with `main` as its stable branch and an updater. The installer cannot install a private plugin.
- **Switching the path repositories to GitHub and committing `vendor/`,** then regenerating `composer.lock`. A real site needs this.

## Verify

```sh
cd ~/Themes/mai-theme && ./tests/run.sh
php ~/LocalPackages/mai-theme-core/tests/run.php
```

The first must end `All checks passed.` with a clean `debug.log`. The second must end `0 failed`.

## Gotchas

- **The local `develop` and `master` branches are v1.** Never check them out here for theme work. v1's history lives on `v1`.
- **Every push needs Mike's yes,** to `main` or any other branch. A yes for one push does not cover the next.
- **After a package commit, run `composer update maithemewp/mai-theme-core` here.** `vendor/` symlinks the package, so edits are live at once. But until the package's commit changes, `vendor/composer/installed.json` may not list new files, and a new `autoload.files` entry does not load.
- **The test site uses port 8430.** Stop anything else there first. `.wp-test/` is disposable: delete it and run `./tests/run.sh` to rebuild.
- **Two patterns copy a file.** `layout-post-with-sidebar.php` equals `templates/with-sidebar-post.html` byte for byte. `posts-grid.php` equals `parts/posts-grid.html` in everything but the Query Loop's opening line. Change each pair together or `tests/slots.php` fails.
- **`tests/run.sh` fails on `maiSlotsRules` or a stray `mai-theme-` or `mai-theme/` name** in templates, parts, patterns, styles, CSS, PHP, or `theme.json`.
- **Navigation blocks carry no menu ID.** One with no menu chosen shows the most recently published menu. The README has each site choose its menu.
- **`vendor/` is gitignored for now.** Run `composer install` in a fresh checkout.
