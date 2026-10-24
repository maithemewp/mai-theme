# To do

## Waiting on Mike

- [ ] **Walk the `mai-theme-scaffold` skill draft** at `docs/plans/mai-theme-scaffold/SKILL.md`. After that it moves to `~/.agents/skills/mai-theme-scaffold/` and balloon-juice's `CONVENTIONS.md` points at it.
- [ ] **Approve the first push.** `v3` becomes `main` and v1 moves to a `v1` branch on `maithemewp/mai-theme`. Pushing, tagging, and releasing each need his yes.
- [ ] **Make Mai Content Areas public with an updater,** and `main` as the stable branch, the way Mai Columns ships. The installer needs it before the theme goes on a real site. Making the repo public needs his explicit go-ahead.
- [ ] **Switch the path repositories to GitHub and commit `vendor/`.** That covers `mai-theme-core` here, and the engine packages in Mai Content Areas. Regenerate `composer.lock` after, because it pins the local path package today.
- [ ] **Name the optional Mai plugins.** The spec lists optional plugins, such as Mai Performance Images and Mai Site Help, that the installer does not offer yet. It waits on Mike to name the list.
- [ ] **Revisit two settings after Mike's smoke test.** Each is a small change:
  - **The footer headings are h2 at the `medium` size.**
  - **"Posts: List" stays side by side at 80px everywhere.** Mai Columns switches on its container's width, so stacking it on phones would also stack it in every 300px sidebar.

## Parked by the spec

- [ ] **Section styles,** until the pattern library.
- [ ] **A pattern library** that inherits each site's styles. Sources, licences, and where it lives are open. See the spec, "Later: a pattern library".
- [ ] **Hub-page gap in the engine.** A Loop insert inside a Query Loop on an ordinary page renders, but the Content Areas "In loops" tab does not list it. The note is in `~/LocalPackages/mai-slots/TODO.md`. The Wide template exists for these pages.

## Later: found in other themes, left out on purpose

See the spec, "Later: not in the starter".

- [ ] **Archive cover images,** from balloon-juice's `Archive_Cover`: an archive's own image in a core Image or Cover block.
- [ ] **A Query Loop slider,** using Embla.
- [ ] **A lightbox,** the GLightbox loader.
- [ ] **Heading tag clean-up.**

## Small fixes, none urgent

**In this theme:**
- [ ] **Gate the missing-vendor notice.** It shows to every admin. Show it only to someone who can switch themes.
- [ ] **Match the text domain of the missing-vendor notice and the package's `src/admin.php`** to the client's, or leave it as `mai-theme` on purpose and say so.
- [ ] **Stop hard-coding `queryId` 1** in `parts/posts-grid.html`. The three post designs already carry none.
- [ ] **Scope `search.css` to the field wrapper.** It rounds the input inside a button-inside wrapper as well as the wrapper.
- [ ] **Keep `docs/specs` and `docs/plans` in a fresh client copy.** The "Start a client site" procedure empties them, and they have no `.gitkeep`.

**In the theme's tests:**
- [ ] **Measure a full-width group inside a sidebar template's content.** Only page, Wide, and Narrow measure one today.
- [ ] **Make two navigation checks fail without the CSS.** They pass without it. Core's path with no overlay part is untested too.
- [ ] **Tidy `tests/setup.sh`.** It resolves the package folders after the slow download, writes the port in two places, and throws away log entries made during the build.
- [ ] **Rename the test internals that still say `mai_theme_`,** such as `$GLOBALS['mai_theme_tests']`, the `_mai_theme_tests` meta key, and the `mai-theme-slots-test` slug.
- [ ] **File the editor check's post under `posts`, not `pages`,** in `tests/editor.php`.
- [ ] **Make the editor check's login wait stricter.** Its `/wp-admin/` pattern also matches a bounce back to the login page with `redirect_to` in the URL.
- [ ] **Clean up the test `login.css` on Ctrl-C.** The smoke check writes it into the active theme and removes it only on a normal exit.

**In `mai-theme-core`:**
- [ ] **Pick one hook string style.** `'\\name'` and `'\name'` are both used.
- [ ] **Guard `filemtime()` returning false,** in `setup.php`, `block-css.php`, and `login.php`. The version string would end in a dot or be empty.
- [ ] **Check the Gravity Forms no-op branch.** When the palette has none of the slugs, `gravity_form_colors()` returns its input unchanged. No check covers that.
- [ ] **Check that the WordPress logo is hidden** on the login page.
- [ ] **Give the login heading text when the Site Title is empty.** The logo link is left with no text for screen readers.
- [ ] **Keep core's other button classes on the other login screens.** The submit button rewrite drops them.
- [ ] **Soften the `esc_url_raw()` comment** in `login_logo_css()`. It claims more than the function does.
- [ ] **Decide whether empty paragraphs with attributes stay.** `strip_empty_paragraphs()` removes them, which also removes coloured bars and script-filled `<p data-wp-text>`.
- [ ] **Tag a first release** only when the plumbing settles, with Mike's yes.
