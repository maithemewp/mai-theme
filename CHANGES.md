# Changes

## 3.0.0 (unreleased)

* Added: The starter block theme for Mai Slots. Eight standard templates, six custom templates, and eight template parts, with a locked Slot block for every Mai Slots location.
* Added: Custom templates by post type. Posts get "Post with sidebar". Pages get Wide, Narrow, "Page with sidebar", Blank, and Canvas.
* Added: The entry split around Post Content into three parts: Entry header for posts, Page header for pages, and Entry footer for both. Pages never show the meta row, post navigation, or comments.
* Added: The "Post: With sidebar" layout pattern, for the Site Editor's Design panel on Single Posts.
* Added: Three post designs for the Query Loop's "Change design" button: "Posts: Grid", "Posts: Archive", and "Posts: List". The blog, archive, and search lists share the Posts grid part, so one change restyles them all. Each design carries its own query, so it also works inserted on any page.
* Added: A four-column footer with the site logo and Site Title, the tagline, social icons, three lists of links, and a copyright line whose year is a block binding.
* Added: `theme.json` tokens for colour, font size, spacing, fonts, line height, shadows, radius, and aspect ratios, with core's default presets removed.
* Added: The Alt Heading block style, on Heading and Paragraph.
* Added: Starter block CSS for the navigation overlay, Post Template cards, and the Search block.
* Added: Figtree, self-hosted.
* Added: Plumbing through the `maithemewp/mai-theme-core` package: a dependency installer for Mai Content Areas and Mai Columns, a login page in the theme's look, Gravity Forms colours, video embed shapes, empty paragraph removal, and an Appearance, Patterns link.
* Added: `./tests/run.sh`, a throwaway test site with a smoke check, a slots check, an installer check, and a headless Site Editor check.
* Changed: Widths are native block settings. The Wide, Narrow, and With sidebar classes and their CSS are gone.
* Changed: Names drop `-theme` for what the theme does: the `mai` stylesheet handle, `mai-<namespace>-<block>` block CSS handles, the `mai_dependencies` filter, the `--mai-site-min-height` variable, and `mai/` pattern slugs. `functions.php` uses `Mai\Theme`, and the package uses `Mai\Theme\Core`.
* Removed: The "Archive: List" layout pattern, replaced by the post designs.
* Removed: Every Mai Slots "Show when" rule from the starter's markup, and every block setting that repeated `theme.json`.
