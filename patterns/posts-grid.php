<?php
/**
 * Title: Posts: Grid
 * Slug: mai/posts-grid
 * Categories: query
 * Block Types: core/query
 * Inserter: yes
 *
 * Three columns of cards: image, title, date, and a short excerpt. The default
 * design in parts/posts-grid.html, so "Change design" can switch back to it.
 * Keep the two identical: tests/slots.php fails when they differ. Inserter
 * stays yes, because core leaves a pattern hidden from the inserter out of
 * "Change design" too.
 */

declare( strict_types=1 );

?>
<!-- wp:query {"queryId":1,"query":{"inherit":true},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group"><!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->

<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-date {"textColor":"contrast-2","fontSize":"small"} /-->

<!-- wp:post-excerpt {"excerptLength":20} /--></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-pagination {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>Nothing found.</p>
<!-- /wp:paragraph -->

<!-- wp:search {"label":"Search","buttonPosition":"button-inside"} /-->
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->
