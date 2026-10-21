# State
Updated: 2026-10-21 by Claude (Sonnet 5.5)

## Now

Mai Theme v3 is built and tested on the local branch `v3`. It has `theme.json` with all the tokens, `style.css`, six parts, 13 templates, two layout patterns, the starter block CSS, the Alt Heading block style, and the README with its three procedures. Its plumbing is the Composer package `~/LocalPackages/mai-theme-core` on `develop`, required by path at `@dev`.

The `mai-theme-scaffold` skill is written as a draft at `docs/plans/mai-theme-scaffold/SKILL.md`. It is not in `~/.agents` and must not be until Mike has walked it.

Nothing is pushed, tagged, or released.

## Next

1. Walk the skill draft with Mike, then move it to `~/.agents/skills/mai-theme-scaffold/` with his yes.
2. Settle the three overnight decisions listed below.
3. Get Mike's yes for the first push. See below.

## Blocked / waiting on

**Mike, in this order:**
- **Walking the skill draft.** Until then the theme points at a skill that is not installed.
- **The first push.** It makes `v3` become `main` and moves v1 to a `v1` branch. Nothing is pushed until he says so.
- **Mai Content Areas going public,** with `main` as its stable branch and an updater, as Mai Columns has. The installer cannot install a private plugin, and there is no way to update it today.
- **Switching the path repositories to GitHub and committing `vendor/`.** That covers `mai-theme-core` here and the engine packages in Mai Content Areas. Then regenerate `composer.lock`.

**Three calls made overnight that he may want different:**
- **The "Archive: List" pattern drops the Search and Blog headings.** One pattern serves four templates, so it uses the archive title. Applying it to Search or the Blog home loses their heading and search form.
- **The With sidebar padding reset covers only that template.** A full-width background group in its content loses its side padding. The entry part inside other Mai Columns layouts would show a gap.
- **"Post: With sidebar" now carries comments** and post navigation, so switching a post to the sidebar layout does not drop them. It costs one block group if he wants it bare.

## Verify

```sh
cd ~/Themes/mai-theme-v3 && ./tests/setup.sh --run
php ~/LocalPackages/mai-theme-core/tests/run.php
```

The first must end `All checks passed.` with a clean `debug.log`. It runs 260 checks: smoke, slots, and a headless Site Editor check. The second expects 31 passed, 0 failed.

## Gotchas

- **The theme is a git worktree** at `~/Themes/mai-theme-v3`, on the orphan branch `v3`. `~/Themes/mai-theme` is still the v1 checkout on `develop`. Do not touch it.
- **The test site uses port 8430.** Stop anything else there first.
- **`.wp-test/` is disposable.** Delete it and run `./tests/setup.sh` to start over.
- **`vendor/` is gitignored for now.** Composer symlinks the path repository into it, so run `composer install` in a fresh checkout. It gets committed at the first release.
- **The layout pattern must equal its template.** `patterns/layout-post-with-sidebar.php` and `templates/with-sidebar.html` change together or `tests/slots.php` fails.
- **Wide and Narrow work through a class on `main`** that resets the content size variable. They do not set `contentSize`.
- **Commit trailers name the model that wrote the commit,** so both Opus 5.5 and Sonnet 5.5 appear in the logs.
