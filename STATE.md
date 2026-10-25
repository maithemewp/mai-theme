# State
Updated: 2026-10-25 by Claude (Opus 5.5)

## Now

v3 is on GitHub as `main`, the default branch of `maithemewp/mai-theme` since 2026-10-25. v1 is on a `v1` branch. `master` and `develop` still point at v1's last commit, `f5b5173`, and the `v3` branch stays as it was. The plumbing package `~/LocalPackages/mai-theme-core` is still local on `develop`, so a fresh clone of `main` cannot run `composer install` yet. Nothing is tagged or released.

Mike walked the skill draft on 2026-10-25. It installs once `~/Themes/mai-theme` holds `main`. At the same time, `AGENTS.md` is trimmed to a pointer to the skill, the rules about this repo only, and an empty "This site" section, as the spec's "Conventions" says. The test script is `tests/run.sh`, here and in Mai Content Areas.

## Next

1. Switch `~/Themes/mai-theme` from the v1 checkout to `main`, with Mike's yes.
2. Install the skill at `~/.agents/skills/mai-theme-scaffold/`, delete its "Status: draft" paragraph, and trim `AGENTS.md`.
3. Mike runs his smoke test, then decides the two settings below.

## Blocked / waiting on

**After Mike's smoke test:**
- **The footer headings at the `medium` size.**
- **"Posts: List" at 80px, side by side everywhere.** Stacking on phones would also stack it in every 300px sidebar, because Mai Columns switches on its container's width.

**Mike:**
- **His yes to switch `~/Themes/mai-theme` to `main`,** and whether `master`, `develop`, and `v3` on GitHub are deleted.
- **Mai Content Areas going public,** with `main` as its stable branch and an updater. The installer cannot install a private plugin.
- **Switching the path repositories to GitHub and committing `vendor/`,** then regenerating `composer.lock`. A real site needs this.

## Verify

```sh
cd ~/Themes/mai-theme-v3 && ./tests/run.sh
php ~/LocalPackages/mai-theme-core/tests/run.php
```

The first must end `All checks passed.` with a clean `debug.log`. The second must end `0 failed`.

## Gotchas

- **Paths named `~/Themes/mai-theme` assume v3 has replaced v1 there.** Today that folder is the v1 Genesis checkout on `develop`, and this theme is a git worktree at `~/Themes/mai-theme-v3`. Do not touch the v1 checkout.
- **Every push needs Mike's yes,** to `main` or any other branch. A yes for one push does not cover the next.
- **After a package commit, run `composer update maithemewp/mai-theme-core` here.** `vendor/` symlinks the package, so edits are live at once. But until the package's commit changes, `vendor/composer/installed.json` may not list new files, and a new `autoload.files` entry does not load.
- **The test site uses port 8430.** Stop anything else there first. `.wp-test/` is disposable: delete it and run `./tests/run.sh` to rebuild.
- **Two patterns copy a file.** `layout-post-with-sidebar.php` equals `templates/with-sidebar-post.html` byte for byte. `posts-grid.php` equals `parts/posts-grid.html` in everything but the Query Loop's opening line. Change each pair together or `tests/slots.php` fails.
- **`tests/run.sh` fails on `maiSlotsRules` or a stray `mai-theme-` or `mai-theme/` name** in templates, parts, patterns, styles, CSS, PHP, or `theme.json`.
- **Navigation blocks carry no menu ID.** One with no menu chosen shows the most recently published menu. The README has each site choose its menu.
- **`vendor/` is gitignored for now.** Run `composer install` in a fresh checkout.
