# State
Updated: 2026-10-23 by Claude (Opus 5.5)

## Now

Mike's review changes are built, tested, and documented on the branch `v3`, with the plumbing package `~/LocalPackages/mai-theme-core` on `develop`. "Posts: Grid" now carries its own query, the latest 9 posts, so it works inserted on any page. The `posts-grid` part keeps following the page. Mike confirmed the sidebar template names, "Post with sidebar" and "Page with sidebar". `v3` is pushed to GitHub as a backup branch only. `main`, the v1 branches, and the package are untouched, and nothing is tagged or released.

## Next

1. Mike runs his smoke test, then decides the two settings below.
2. Walk the skill draft, `docs/plans/mai-theme-scaffold/SKILL.md`, with Mike.
3. Get Mike's yes for the first real push, where `v3` becomes `main`.
4. After that, move the skill to `~/.agents/skills/mai-theme-scaffold/` with his yes. It names `~/Themes/mai-theme`, which is v1 until then.

## Blocked / waiting on

**After Mike's smoke test:**
- **The footer headings at the `medium` size.**
- **"Posts: List" at 80px, side by side everywhere.** Stacking on phones would also stack it in every 300px sidebar, because Mai Columns switches on its container's width.

**Mike, parked:**
- **Walking the skill draft.** It installs only after the first push.
- **`v3` becoming `main`,** with v1 moved to a `v1` branch.
- **Mai Content Areas going public,** with `main` as its stable branch and an updater. The installer cannot install a private plugin.
- **Switching the path repositories to GitHub and committing `vendor/`,** then regenerating `composer.lock`.

## Verify

```sh
cd ~/Themes/mai-theme-v3 && ./tests/setup.sh --run
php ~/LocalPackages/mai-theme-core/tests/run.php
```

The first must end `All checks passed.` with a clean `debug.log`. The second must end `0 failed`.

## Gotchas

- **Paths named `~/Themes/mai-theme` assume v3 has replaced v1 there.** Today that folder is the v1 Genesis checkout on `develop`, and this theme is a git worktree at `~/Themes/mai-theme-v3`. Do not touch the v1 checkout.
- **The `v3` branch on GitHub is a backup, not a release.** Pushing it again needs Mike's yes each time. Never push `main` or any other branch without it.
- **After a package commit, run `composer update maithemewp/mai-theme-core` here.** `vendor/` symlinks the package, so edits are live at once. But until the package's commit changes, `vendor/composer/installed.json` may not list new files, and a new `autoload.files` entry does not load.
- **The test site uses port 8430.** Stop anything else there first. `.wp-test/` is disposable: delete it and run `./tests/setup.sh` to rebuild.
- **Two patterns copy a file.** `layout-post-with-sidebar.php` equals `templates/with-sidebar-post.html` byte for byte. `posts-grid.php` equals `parts/posts-grid.html` in everything but the Query Loop's opening line. Change each pair together or `tests/slots.php` fails.
- **`tests/setup.sh` fails on `maiSlotsRules` or a stray `mai-theme-` or `mai-theme/` name** in templates, parts, patterns, styles, CSS, PHP, or `theme.json`.
- **Navigation blocks carry no menu ID.** One with no menu chosen shows the most recently published menu. The README has each site choose its menu.
- **`vendor/` is gitignored for now.** Run `composer install` in a fresh checkout.
