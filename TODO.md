# To do

## Waiting on Mike

- [ ] **Walk the `mai-theme-scaffold` skill draft** at `docs/plans/mai-theme-scaffold/SKILL.md`. After that it moves to `~/.agents/skills/mai-theme-scaffold/` and balloon-juice's `CONVENTIONS.md` points at it.
- [ ] **Approve the first push.** `v3` becomes `main` and v1 moves to a `v1` branch on `maithemewp/mai-theme`. Pushing, tagging, and releasing each need his yes.
- [ ] **Make Mai Content Areas public with an updater,** and `main` as the stable branch, the way Mai Columns ships. The installer needs it before the theme goes on a real site. Making the repo public needs his explicit go-ahead.
- [ ] **Switch the path repositories to GitHub and commit `vendor/`.** That covers `mai-theme-core` here, and the engine packages in Mai Content Areas. Regenerate `composer.lock` after, because it pins the local path package today.
- [ ] **Decide the three calls made overnight:**
  - **"Archive: List" drops the Search and Blog headings,** because it uses an archive title and one pattern serves four templates. Split it per template, or accept it.
  - **The With sidebar padding reset covers only that template.** A full-width background group in its content loses its side padding, and the entry part in other Mai Columns layouts would show a gap. Widen the reset, or keep it narrow.
  - **"Post: With sidebar" carries comments and post navigation.** Keep it, or make it bare.

## Parked by the spec

- [ ] **Section styles,** until the pattern library.
- [ ] **A pattern library** that inherits each site's styles. Sources, licences, and where it lives are open. See the spec, "Later: a pattern library".
- [ ] **Hub-page gap in the engine.** A Loop insert inside a Query Loop on an ordinary page renders, but the Content Areas "In loops" tab does not list it. The note is in `~/LocalPackages/mai-slots/TODO.md`. The Wide template exists for these pages.

## Small fixes, none urgent

**In this theme:**
- [ ] **Gate the missing-vendor notice.** It shows to every admin. Show it only to someone who can switch themes.
- [ ] **Stop hard-coding `queryId` 1** in `parts/posts-grid.html`.
- [ ] **Scope `search.css` to the field wrapper.** It rounds the input inside a button-inside wrapper as well as the wrapper.
- [ ] **Measure a full-width group inside a post's With sidebar.** Only the page is measured today.
- [ ] **Make two navigation checks fail without the CSS.** They pass without it. Core's path with no overlay part is untested too.
- [ ] **Tidy `tests/setup.sh`.** It resolves the package folders after the slow download, writes the port in two places, and throws away log entries made during the build.

**In `mai-theme-core`:**
- [ ] **Pick one hook string style.** `'\\name'` and `'\name'` are both used.
- [ ] **Guard `filemtime()` returning false,** in `setup.php` and `block-css.php`. The version string would end in a dot or be empty.
- [ ] **Add a test where a nested legacy rule is not the first.** Correct the `legacy-css.php` docblock that overclaims, and say the brace guard is belt and braces.
- [ ] **Fix the `dependencies()` docblock.** Its example name depends on the theme's folder name.
- [ ] **Tag a first release** only when the plumbing settles, with Mike's yes.
