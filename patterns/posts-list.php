<?php
/**
 * Title: Posts: List
 * Slug: mai/posts-list
 * Categories: query
 * Block Types: core/query
 * Inserter: yes
 *
 * A small square thumbnail and the title, for sidebars and "Favorite Posts"
 * sections. The thumbnail column is 80px, set on the Mai Columns block. It
 * stays beside the title at every width: Mai Columns measures its container,
 * and a sidebar is narrower than a phone, so stacking on small containers
 * would stack it in every sidebar. Its own query is the five latest posts.
 */

declare( strict_types=1 );

?>
<!-- wp:query {"query":{"perPage":5,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"layout":{"type":"default"}} -->
<div class="wp-block-query"><!-- wp:post-template -->
<!-- wp:mai/columns {"alignItems":"center","sizesLg":["80px","fill"],"sizesMd":["80px","fill"],"sizesSm":["80px","fill"]} -->
<!-- wp:mai/column -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"1"} /-->
<!-- /wp:mai/column -->

<!-- wp:mai/column -->
<!-- wp:post-title {"level":3,"isLink":true,"fontSize":"medium"} /-->
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
<!-- /wp:query-no-results --></div>
<!-- /wp:query -->
