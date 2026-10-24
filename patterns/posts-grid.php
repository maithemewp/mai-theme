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
 * The cards stay identical to the part's: tests/slots.php fails when anything
 * but the query differs. Its own query is custom, the latest 9 posts, so it
 * works when inserted on a page. "Change design" keeps the query type of the
 * Query Loop it replaces, so the part still follows the page. Inserter stays
 * yes, because core leaves a pattern hidden from the inserter out of "Change
 * design" too.
 */

declare( strict_types=1 );

?>
<!-- wp:query {"query":{"perPage":9,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"layout":{"type":"default"}} -->
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
