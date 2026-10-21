# Mai Theme

A block theme starter for sites built on Mai Content Areas. Every Mai Slots location already has a Slot block in the right template, so content areas show up with no template editing.

**Status: 3.0.0, unreleased.** Paths like `~/Themes/mai-theme` assume this branch has replaced the old v1 theme there, which happens at the first push. Mai Content Areas is still private and has no updater, so the theme cannot install it on a real site yet. See [TODO.md](TODO.md).

**This is a starter, not a theme you update in place.** Each client site is a renamed copy of this folder. Fixes to the shared code reach old copies through a Composer package, covered in "Bring starter fixes into a client site".

## Requirements

- **PHP 8.4.** The Mai Slots engine requires it.
- **WordPress 7.0.** The mobile menu uses the navigation overlay part area that 7.0 added.
- **Mai Content Areas.** It carries the Mai Slots engine. Without it, Slot blocks render nothing on the front end, and the Site Editor shows a "doesn't include support" box in each slot's place.
- **Mai Columns.** It builds the header, the post navigation, and the With sidebar layout.
- **Composer,** to install the theme's plumbing package.

## The required plugins

The theme installs Mai Content Areas and Mai Columns for you, through `afragen/wp-dependency-installer`. This is how it behaves:

- **It installs and activates on every wp-admin page load** where a plugin is missing or inactive. If someone deactivates one, the next page load turns it back on.
- **It removes the Deactivate and Delete links** for both plugins.
- **It needs the right user.** Installing needs `update_plugins`. Activating needs `activate_plugins`.
- **It downloads a branch, not a release.** Whatever is committed on `main` is what installs.
- **Front-end requests skip it.** Visitors never pay for it.

To add, change, or remove a required plugin, use the `mai_theme_dependencies` filter in the site's own `inc/` folder.

## What is in the box

- **Eight standard templates:** `index`, `home`, `front-page`, `page`, `single`, `archive`, `search`, and `404`.
- **Five custom templates,** offered on posts and pages: Blank, Canvas, Wide, Narrow, and With sidebar. Blank and Canvas show the content only, with no post navigation or comments, by design.
- **Six template parts:** header, footer, mobile menu, sidebar, entry, and posts grid.
- **Two layout patterns,** for swapping a whole template in the Site Editor: "Post: With sidebar" and "Archive: List".
- **One block style:** Alt Heading, on Heading and Paragraph blocks.
- **Figtree,** self-hosted, as both the body and heading font.

Every Slot block in the templates is locked against removal. Deleting one silently stops every content area aimed at it. Moving one is fine.

The "Blog" heading on the posts page is fixed text in `templates/home.html`, so a site whose posts page has another name changes it there.

## Start a client site

Do this once per client. Rename a client copy only by editing `style.css` and naming its folder. A search and replace across the files would also rewrite `maithemewp/mai-theme-core` in `composer.json`.

**Warning:** Do not rename the `maithemewp/mai-theme-core` package, the `Mai\Theme` namespace, or the `mai-theme-` handles. The package never reads the theme's slug.

1. Copy the starter to a new folder named for the client.

   ```sh
   rsync -a --exclude='.git' --exclude='.wp-test' --exclude='.superpowers' --exclude='vendor' ~/Themes/mai-theme/ ~/Themes/acme/
   ```

2. Open `style.css` in the new folder.
3. Change `Theme Name` to the client's name.
4. Change `Text Domain` to the client's folder name.
5. Change `Version` to `1.0.0`.
6. Change `Description` to one sentence about the client's site.
7. Open `composer.json` in the new folder.
8. Change `name` to the client's package name.
9. Delete the starter's `STATE.md`, `TODO.md`, `CHANGES.md`, specs, and plans.

   ```sh
   cd ~/Themes/acme && rm STATE.md TODO.md CHANGES.md && rm -r docs/specs/* docs/plans/*
   ```

10. Write a new `STATE.md`, `TODO.md`, and `CHANGES.md` for the client.
11. Start the client's own git repository.

    ```sh
    cd ~/Themes/acme && git init
    ```

12. Install the plumbing package.

    ```sh
    cd ~/Themes/acme && composer install
    ```

13. Open `theme.json`.
14. Set the client's colours under `settings.color.palette`.
15. Set the client's fonts under `settings.typography.fontFamilies`.
16. Set the client's shadows under `settings.shadow.presets`.
17. Set the site's main radius, `default`, under `settings.border.radiusSizes`.
18. Activate the theme in wp-admin.
19. Load any wp-admin page.
20. Wait for the installer to add the required plugins.
21. Go to **Appearance > Editor**.

**Warning:** A Navigation block with no menu chosen shows the most recently published menu. If you skip the next steps, a menu made later, such as a footer menu, takes over the header.

22. Open the **Header** template part.
23. Select the Navigation block.
24. Choose the client's main menu in the block settings.
25. Click **Save**.
26. Open the **Mobile menu** template part.
27. Select the Navigation block.
28. Choose the same menu in the block settings.
29. Click **Save**.

Keep the existing preset slugs. Patterns and templates refer to them by name.

The menu choice saves to the database, not to the theme's files. Leave it there: a menu's ID is different on every site.

## Give every single post the sidebar

Two ways to do it. The result is the same layout: the entry, post navigation, and comments next to the sidebar part.

### In code

**Warning:** This overwrites `templates/single.html`. Commit your work first.

1. Open a terminal in the theme folder.
2. Copy the With sidebar template over the single template.

   ```sh
   cp templates/with-sidebar.html templates/single.html
   ```

3. Review the change.

   ```sh
   git diff templates/single.html
   ```

4. Commit the change.

### In the Site Editor

This saves to the database, not to a file. Save it back to the theme with Create Block Theme if the change must travel with the code.

1. Go to **Appearance > Editor**.
2. Click **Templates**.
3. Click **Single Posts**.
4. Click the pencil icon.
5. Open the settings sidebar.
6. Find the **Design** panel.
7. Click **Post: With sidebar**.
8. Click **Save**.

## Bring starter fixes into a client site

Fixes to the plumbing ship in the `maithemewp/mai-theme-core` package. Until the first release the package is a local path repository, so this procedure starts after the switch to GitHub. Running the checks needs the test site from "Testing".

1. Open a terminal in the client's theme folder.
2. Update the package.

   ```sh
   composer update maithemewp/mai-theme-core
   ```

3. Check the version that installed.

   ```sh
   composer show maithemewp/mai-theme-core
   ```

4. Run the checks.

   ```sh
   ./tests/setup.sh --run
   ```

5. Commit `composer.lock` and `vendor/`.
6. Deploy the theme.

Template, style, and `theme.json` fixes are not in the package. Those are files in the starter, and a client copy merges them by hand.

## Saving from the Site Editor

Create Block Theme writes an edited template to `templates/<slug>.html`. Edited parts go to `parts/`.

If the template then contains PHP, Create Block Theme moves it to `patterns/<slug>.php` and leaves a one-line reference in the template. Its "Localize images" option is on by default and adds PHP for every image. So a template with an image comes back as a one-line reference plus a pattern named after the template. Finding `patterns/single.php` after a save is normal.

## Testing

The checks run against a throwaway WordPress site in `.wp-test/`. It uses SQLite, so it needs no database server. The theme is linked in twice, as `mai-theme` and as a renamed copy called `acme`, to prove nothing reads the slug.

It needs Mai Content Areas and Mai Columns checked out in `~/Plugins`, each with its build, and the plumbing package in `~/LocalPackages`.

1. Build the site and run every check.

   ```sh
   ./tests/setup.sh
   ```

2. Run the checks again against the same site.

   ```sh
   ./tests/setup.sh --run
   ```

Building the site downloads WordPress, so it takes a minute. Running the checks again reuses the site.

A passing run ends with `All checks passed.` and a clean `debug.log`. The headless Site Editor check uses port 8430. Stop anything else on that port first.

The plumbing package has its own tests, which need no install.

```sh
php ~/LocalPackages/mai-theme-core/tests/run.php
```

## Repo files

- **`AGENTS.md`** holds the rules for working in this repo. `CLAUDE.md` is a symlink to it.
- **`STATE.md`** says where the work stands now.
- **`TODO.md`** lists what is open.
- **`CHANGES.md`** is the changelog.
- **`docs/`** holds the spec, the build plan, and the draft of the `mai-theme-scaffold` skill.

## License

GPL-2.0-or-later. Figtree is under the SIL Open Font License, in `assets/fonts/OFL-Figtree.txt`.
