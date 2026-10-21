# To do

## Waiting on Mike

- [ ] **Walk the `mai-theme-scaffold` skill draft** at `docs/plans/mai-theme-scaffold/SKILL.md`. After that it moves to `~/.agents/skills/mai-theme-scaffold/` and balloon-juice's `CONVENTIONS.md` points at it.
- [ ] **Approve the first push.** `v3` becomes `main` and v1 moves to a `v1` branch on `maithemewp/mai-theme`. Pushing, tagging, and releasing each need his yes.
- [ ] **Make Mai Content Areas public with an updater,** and `main` as the stable branch, the way Mai Columns ships. The installer needs it before the theme goes on a real site. Making the repo public needs his explicit go-ahead.
- [ ] **Switch the path repositories to GitHub and commit `vendor/`.** That covers `mai-theme-core` here, and the engine packages in Mai Content Areas. Regenerate `composer.lock` after, because it pins the local path package today.
- [ ] **Name the optional Mai plugins.** The spec lists optional plugins, such as Mai Performance Images and Mai Site Help, that the installer does not offer yet. It waits on Mike to name the list.
- [ ] **Decide the three calls made overnight:**
  - **"Archive: List" drops the Search and Blog headings,** because it uses an archive title and one pattern serves four templates. Split it per template, or accept it.
  - **The With sidebar padding reset covers only that template.** A full-width background group in its content loses its side padding, and the entry part in other Mai Columns layouts would show a gap. Widen the reset, or keep it narrow.
  - **"Post: With sidebar" carries comments and post navigation.** Keep it, or make it bare.

- [ ] **Decide whether to amend the spec in two places.** It still says a client copy is renamed by search and replace (section "The repo"), and that Mai Columns builds the header and footer (section "Dependencies"). The build now renames through `style.css` and the folder only, and the footer has no columns. The skill draft's "not `mai`" line points at the first of these.

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

- [ ] **Make the footer year check able to fail.** The footer's saved fallback text is `2026`, so the check passes this year even if the `mai/current-year` binding breaks. Store a fallback that is never the current year, or assert the binding inside the site. The package's `STATE.md` cites this check as the binding's proof.
- [ ] **Drop "only" from the rename sentence** in the README and the skill draft. A client copy also changes `name` in `composer.json`, so "never by search and replace" is the accurate rule.
- [ ] **Keep `docs/specs` and `docs/plans` in a fresh client copy.** The "Start a client site" procedure empties them, and they have no `.gitkeep`.
- [ ] **Match the missing-vendor notice's text domain** to the client's, or leave it as `mai-theme` on purpose and say so.

**In `mai-theme-core`:**
- [ ] **Pick one hook string style.** `'\\name'` and `'\name'` are both used.
- [ ] **Guard `filemtime()` returning false,** in `setup.php` and `block-css.php`. The version string would end in a dot or be empty.
- [ ] **Fix the `dependencies()` docblock.** Its example name depends on the theme's folder name, and it says Mai Columns builds the footer, which it no longer does.
- [ ] **Tag a first release** only when the plumbing settles, with Mike's yes.
