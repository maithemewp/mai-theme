<?php
/**
 * Title: Posts: Archive
 * Slug: mai/posts-archive
 * Categories: query
 * Block Types: core/query
 * Inserter: yes
 *
 * One post per row: a 300px image on the left, and the title, date, and
 * excerpt on the right. Stacks when its container is under 640px wide. Its
 * own query is custom, so it works when inserted on a page. "Change design"
 * keeps the query type of the Query Loop it replaces.
 */

declare( strict_types=1 );

?>
<!-- wp:query {"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:mai/columns {"sizesLg":["300px","fill"],"sizesMd":["300px","fill"],"sizesSm":["1/1"]} -->
<!-- wp:mai/column -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2"} /-->
<!-- /wp:mai/column -->

<!-- wp:mai/column {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-date {"textColor":"contrast-2","fontSize":"small"} /-->

<!-- wp:post-excerpt {"excerptLength":30} /-->
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
