<?php
/**
 * Title: Archive: List
 * Slug: mai-theme/layout-archive-list
 * Template Types: index, home, archive, search
 * Post Types: wp_template
 * Inserter: false
 *
 * The archive template with its posts one per row instead of in a grid. The
 * Design panel matches template slugs exactly, so each one is listed by name.
 */

declare( strict_types=1 );

?>
<!-- wp:mai/slot {"location":"mai/before-header","lock":{"remove":true}} /-->

<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:mai/slot {"location":"mai/after-header","lock":{"remove":true}} /-->

<!-- wp:group {"tagName":"main","className":"mai-theme-wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group mai-theme-wide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:query-title {"type":"archive"} /-->

<!-- wp:mai/slot {"location":"mai/before-loop","lock":{"remove":true}} /-->

<!-- wp:query {"queryId":1,"query":{"inherit":true},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|50"}},"layout":{"type":"default"}} -->
<!-- wp:mai/columns {"sizesLg":["300px","fill"],"sizesMd":["300px","fill"],"sizesSm":["1/1"],"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40","top":"var:preset|spacing|30"}}}} -->
<!-- wp:mai/column -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->
<!-- /wp:mai/column -->

<!-- wp:mai/column -->
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group"><!-- wp:post-title {"level":2,"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-date {"textColor":"contrast-2","fontSize":"small"} /-->

<!-- wp:post-excerpt {"excerptLength":30} /--></div>
<!-- /wp:group -->
<!-- /wp:mai/column -->
<!-- /wp:mai/columns -->
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

<!-- wp:mai/slot {"location":"mai/after-loop","lock":{"remove":true}} /--></main>
<!-- /wp:group -->

<!-- wp:mai/slot {"location":"mai/before-footer","lock":{"remove":true}} /-->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->

<!-- wp:mai/slot {"location":"mai/after-footer","lock":{"remove":true}} /-->
