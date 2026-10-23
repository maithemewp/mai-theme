# Mai Theme

A block theme starter for sites built on Mai Content Areas. Every Mai Slots location already has a Slot block in the right template, so content areas show up with no template editing.

**Status: 3.0.0, unreleased.** Paths like `~/Themes/mai-theme` assume this branch has replaced the old v1 theme there, which happens at the first push. Mai Content Areas is still private and has no updater, so the theme cannot install it on a real site yet. See [TODO.md](TODO.md).

**This is a starter, not a theme you update in place.** Each client site is a renamed copy of this folder. Fixes to the shared code reach old copies through a Composer package, covered in "Bring starter fixes into a client site".

## Requirements

- **PHP 8.4.** The Mai Slots engine requires it.
- **WordPress 7.0.** The mobile menu uses the navigation overlay part area that 7.0 added.
- **Mai Content Areas.** It carries the Mai Slots engine. Without it, Slot blocks render nothing on the front end, and the Site Editor shows a "doesn't include support" box in each slot's place.
- **Mai Columns.** It builds the header, the footer's columns, the post navigation, both sidebar templates, and the Archive and List post designs.
- **Composer,** to install the theme's plumbing package.

## The required plugins

The theme installs Mai Content Areas and Mai Columns for you, through `afragen/wp-dependency-installer`. This is how it behaves:

- **It installs and activates on every wp-admin page load** where a plugin is missing or inactive. If someone deactivates one, the next page load turns it back on.
- **It removes the Deactivate and Delete links** for both plugins.
- **It needs the right user.** Installing needs `update_plugins`. Activating needs `activate_plugins`.
- **It downloads a branch, not a release.** Whatever is committed on `main` is what installs.
- **Front-end requests skip it.** Visitors never pay for it.

To add, change, or remove a required plugin, use the `mai_dependencies` filter in the site's own `inc/` folder.

## What is in the box

- **Eight standard templates:** `index`, `home`, `front-page`, `page`, `single`, `archive`, `search`, and `404`. `single` is the default for posts and custom post types, and `page` is the default for pages.
- **Custom templates, by post type:**
  - **Posts:** "Post with sidebar" (`with-sidebar-post`).
  - **Pages:** Wide, Narrow, "Page with sidebar" (`with-sidebar-page`), Blank, and Canvas.
- **Eight template parts:** header, footer, mobile menu, sidebar, entry header, page header, entry footer, and posts grid.
- **One layout pattern,** "Post: With sidebar", for swapping the Single Posts template in the Site Editor's Design panel.
- **Three post designs** for the Query Loop: "Posts: Grid", "Posts: Archive", and "Posts: List".
- **One block style:** Alt Heading, on Heading and Paragraph blocks.
- **Figtree,** self-hosted, as both the body and heading font.
- **A login page in the theme's look,** from the plumbing package.

**Posts and pages get different blocks.** Posts show a meta row (date, author, and category), post navigation, and comments. Pages show none of those. The entry header part is for posts and the page header part is for pages, and both share the entry footer part. Wide, Narrow, Blank, and Canvas are offered on pages only, so they carry no post navigation or comments.

**Each template sets its own width** with the native Content width setting on `main` and on Post Content. Pages and posts are 740px, Narrow is 600px, and Wide and the four post lists are 1200px. Both sidebar templates are 1200px, with the content column beside a 300px sidebar.

Every Slot block in the templates is locked against removal. Deleting one silently stops every content area aimed at it. Moving one is fine.

The "Blog" heading on the posts page is fixed text in `templates/home.html`, so a site whose posts page has another name changes it there.

## Start a client site

Do this once per client. A client copy is renamed in three places: its folder, the `style.css` header, and `name` in `composer.json`. Never rename it by search and replace. That would also rewrite `maithemewp/mai-theme-core` in `composer.json` and the `mai/` names in every template.

**Warning:** Do not rename the `maithemewp/mai-theme-core` package, the `Mai\Theme\Core` and `Mai\Theme` namespaces, the `mai` handles, or the `mai/` names. The package never reads the theme's slug.

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

One post can get the sidebar from the post's own **Template** setting: choose "Post with sidebar". To give it to every post, use one of the two ways below. The result is the same layout: the entry, post navigation, and comments next to the sidebar part.

### In code

**Warning:** This overwrites `templates/single.html`. Commit your work first.

1. Open a terminal in the theme folder.
2. Copy the "Post with sidebar" template over the single template.

   ```sh
   cp templates/with-sidebar-post.html templates/single.html
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

## Change the design of every post list

The blog, archive, and search templates all show their posts through the **Posts grid** template part. Its Query Loop holds the "Posts: Grid" design. Change the design there once, and every post list on the site follows. No template heading changes.

- **Posts: Grid** shows three columns of cards, each with an image, title, date, and short excerpt.
- **Posts: Archive** shows one post per row, with a 300px image beside the title, date, and excerpt.
- **Posts: List** shows a small square thumbnail beside each title. It suits sidebars and "Favorite posts" sections.

This saves to the database, not to a file. Save it back to the theme with Create Block Theme if the change must travel with the code.

**Warning:** The next steps change every post list on the site, not one page.

1. Go to **Appearance > Editor**.
2. Click **Patterns**.
3. Click **All template parts**.
4. Click **Posts grid**.
5. Open the **List View**.
6. Select the **Query Loop** block.
7. Click **Change design** in the block toolbar.
8. Click **Posts: Archive** or **Posts: List**.
9. Click **Save**.

The new design keeps two settings from the old one: the post type, and whether the list follows the page's own query. So each list still shows the right posts. Every other Query Loop setting comes from the new design, so set any other change again afterwards.

**On a page, "Posts: Grid" needs one more setting.** It matches the Posts grid part, so it follows the page's own query. Core then warns that a page cannot inherit the template's query. Set the Query Loop's **Query type** to **Custom**. "Posts: Archive" and "Posts: List" already carry their own query.

## Customise the login page

The plumbing package puts the login page in the theme's look: the site logo, or the Site Title when there is no logo, above a card that holds the form. Change it in three layers, from smallest to largest.

- **`theme.json` tokens.** The page uses the palette, the fonts, the `default` radius, the `primary` shadow, and the button style. A client's colours and fonts reach the login page with no other change.
- **The theme's own `assets/css/login.css`.** Create this file in the theme, and the login page loads it after the package's styles. Three variables cover the common changes: `--mai-login-background`, `--mai-login-card-background`, and `--mai-login-logo-height`. Any other rule there wins too.
- **The package's hooks.** Each one is a named function in `Mai\Theme\Core`, such as `enqueue_login_styles` on `login_enqueue_scripts`, so a site can remove it from its own `inc/` folder with `remove_action()` or `remove_filter()`.

An example `assets/css/login.css`:

```css
:root {
	--mai-login-background: var(--wp--preset--color--base-3);
	--mai-login-logo-height: 64px;
}
```

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
- **`docs/`** holds the spec, the build plans, and the draft of the `mai-theme-scaffold` skill.

## License

GPL-2.0-or-later. Figtree is under the SIL Open Font License, in `assets/fonts/OFL-Figtree.txt`.
