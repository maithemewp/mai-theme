# State
Updated: 2026-10-23 by Claude (Opus 5.5)

## Now

Mike's review changes are built, tested, and documented on the local branch `v3`, with the plumbing package `~/LocalPackages/mai-theme-core` on `develop`. Posts and pages have their own entry header parts and sidebar templates, widths are native block settings, the post lists have three designs under "Change design", the footer is rebuilt, and the package adds the login page. No block carries a Mai Slots rule or repeats `theme.json`. The spec records every call the build made as "agent's call". Nothing is pushed, tagged, or released.

## Next

1. Walk the skill draft, `docs/plans/mai-theme-scaffold/SKILL.md`, with Mike.
2. Get Mike's answers on the open calls below.
3. Get Mike's yes for the first push.
4. After the push, move the skill to `~/.agents/skills/mai-theme-scaffold/` with his yes. It names `~/Themes/mai-theme`, which is v1 until then.

## Blocked / waiting on

**Mike:**
- **Walking the skill draft.** It installs only after the first push.
- **The first push.** `v3` becomes `main` and v1 moves to a `v1` branch.
- **Mai Content Areas going public,** with `main` as its stable branch and an updater. The installer cannot install a private plugin.
- **Switching the path repositories to GitHub and committing `vendor/`,** then regenerating `composer.lock`.
- **The sidebar template titles,** "Post with sidebar" and "Page with sidebar". This is his taste.
- **"Posts: List" not stacking on phones.** Mai Columns switches on its container's width, so stacking would also stack it in every 300px sidebar.
- **"Posts: Grid" inserted on a page follows the page's own query,** and core warns it cannot inherit. It has to equal the `posts-grid` part.
- **The footer headings at the `medium` size.**

## Verify

```sh
cd ~/Themes/mai-theme-v3 && ./tests/setup.sh --run
php ~/LocalPackages/mai-theme-core/tests/run.php
```

The first must end `All checks passed.` with a clean `debug.log`. The second must end `0 failed`.

## Gotchas

- **Paths named `~/Themes/mai-theme` assume v3 has replaced v1 there.** Today that folder is the v1 Genesis checkout on `develop`, and this theme is a git worktree at `~/Themes/mai-theme-v3`. Do not touch the v1 checkout.
- **After a package commit, run `composer update maithemewp/mai-theme-core` here.** `vendor/` symlinks the package, so edits are live at once. But until the package's commit changes, `vendor/composer/installed.json` may not list new files, and a new `autoload.files` entry does not load.
- **The test site uses port 8430.** Stop anything else there first. `.wp-test/` is disposable: delete it and run `./tests/setup.sh` to rebuild.
- **Two patterns copy a file byte for byte.** `layout-post-with-sidebar.php` equals `templates/with-sidebar-post.html`, and `posts-grid.php` equals `parts/posts-grid.html`. Change each pair together or `tests/slots.php` fails.
- **`tests/setup.sh` fails on `maiSlotsRules` or a stray `mai-theme-` or `mai-theme/` name** in templates, parts, patterns, styles, CSS, PHP, or `theme.json`.
- **Navigation blocks carry no menu ID.** One with no menu chosen shows the most recently published menu. The README has each site choose its menu.
- **`vendor/` is gitignored for now.** Run `composer install` in a fresh checkout.
