# State
Updated: 2026-10-21 by Claude (Opus 5.5)

## Now

Mai Theme v3 is built, reviewed, and tested on the local branch `v3`. The final review's fixes are in: no footer menu, post navigation and comments on Wide and Narrow, the theme's PHP in `Mai\Theme\Starter`, an installer check, pattern validity checks, and a "Start a client site" procedure that leaves no starter files behind. Its plumbing is the Composer package `~/LocalPackages/mai-theme-core` on `develop`, required by path at `@dev`.

The `mai-theme-scaffold` skill is a draft at `docs/plans/mai-theme-scaffold/SKILL.md`. It is not in `~/.agents` and must not be until Mike has walked it.

Nothing is pushed, tagged, or released.

## Next

1. Settle the open decisions in `TODO.md`, "Waiting on Mike".
2. Walk the skill draft with Mike.
3. Get Mike's yes for the first push.
4. After the push, move the skill to `~/.agents/skills/mai-theme-scaffold/` with his yes. Not before: it names `~/Themes/mai-theme`, which is v1 until then.

## Blocked / waiting on

**Mike:**
- **Walking the skill draft.** It installs only after the first push.
- **The first push.** `v3` becomes `main` and v1 moves to a `v1` branch.
- **Mai Content Areas going public,** with `main` as its stable branch and an updater. The installer cannot install a private plugin.
- **Switching the path repositories to GitHub and committing `vendor/`,** then regenerating `composer.lock`.
- **Naming the optional Mai plugins** the installer should offer.
- **Three overnight calls:** "Archive: List" drops the Search and Blog headings, the With sidebar padding reset covers only that template, and "Post: With sidebar" carries comments and post navigation.

## Verify

```sh
cd ~/Themes/mai-theme-v3 && ./tests/setup.sh --run
php ~/LocalPackages/mai-theme-core/tests/run.php
```

The first must end `All checks passed.` with a clean `debug.log`. It runs the installer, slots, and smoke checks, then the headless Site Editor check. The second must end `0 failed`.

## Gotchas

- **Paths named `~/Themes/mai-theme` assume v3 has replaced v1 there.** Today that folder is the v1 Genesis checkout on `develop`, and this theme is a git worktree at `~/Themes/mai-theme-v3`, on the orphan branch `v3`. Do not touch the v1 checkout.
- **The test site uses port 8430.** Stop anything else there first. `.wp-test/` is disposable: delete it and run `./tests/setup.sh` to rebuild.
- **`vendor/` is gitignored for now.** Run `composer install` in a fresh checkout. It gets committed at the first release.
- **The layout pattern must equal its template.** `patterns/layout-post-with-sidebar.php` and `templates/with-sidebar.html` change together or `tests/slots.php` fails.
- **Wide and Narrow work through a class on `main`** that resets the content size variable. They do not set `contentSize`.
- **Navigation blocks carry no menu ID.** One with no menu chosen shows the most recently published menu. The README has each site choose its menu in the Header and Mobile menu parts.
- **Core inlines the small block stylesheets,** so a page shows a handle with no URL. The smoke check reads the URL inside the site.
- **Commit trailers name the model that wrote the commit,** so Opus 5.5 and Sonnet 5.5 both appear in the logs.
