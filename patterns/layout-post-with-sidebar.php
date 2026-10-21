<?php
/**
 * Title: Post: With sidebar
 * Slug: mai-theme/layout-post-with-sidebar
 * Template Types: single
 * Post Types: wp_template
 * Inserter: false
 *
 * The With sidebar template, offered as a layout for Single Posts in the Site
 * Editor's Design panel. Keep it identical to templates/with-sidebar.html:
 * tests/slots.php fails when the two differ.
 */

declare( strict_types=1 );

?>
<!-- wp:mai/slot {"location":"mai/before-header","lock":{"remove":true}} /-->

<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:mai/slot {"location":"mai/after-header","lock":{"remove":true}} /-->

<!-- wp:group {"tagName":"main","className":"mai-theme-wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<main class="wp-block-group mai-theme-wide" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:mai/columns {"className":"mai-theme-with-sidebar","sizesLg":["fill","300px"],"sizesMd":["1/1"],"sizesSm":["1/1"],"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60","top":"var:preset|spacing|60"}}}} -->
<!-- wp:mai/column -->
<!-- wp:template-part {"slug":"entry"} /-->

<!-- wp:group {"tagName":"nav","metadata":{"maiSlotsRules":{"version":1,"sets":[[{"type":"post-type","config":{"operator":"is","values":["post"],"labels":{"post":"Posts"}}}]]},"name":"Post navigation. Shows on posts only"},"ariaLabel":"Posts","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"padding":{"top":"var:preset|spacing|40"}},"border":{"top":{"color":"var:preset|color|base-3","width":"1px"}}},"layout":{"type":"default"}} -->
<nav class="wp-block-group" aria-label="Posts" style="border-top-color:var(--wp--preset--color--base-3);border-top-width:1px;margin-top:var(--wp--preset--spacing--60);padding-top:var(--wp--preset--spacing--40)"><!-- wp:mai/columns {"sizesLg":["1/2"],"sizesMd":["1/2"],"sizesSm":["1/1"],"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40","top":"var:preset|spacing|20"}}}} -->
<!-- wp:mai/column -->
<!-- wp:post-navigation-link {"type":"previous","showTitle":true,"linkLabel":true,"arrow":"arrow"} /-->
<!-- /wp:mai/column -->

<!-- wp:mai/column -->
<!-- wp:post-navigation-link {"showTitle":true,"linkLabel":true,"arrow":"arrow","style":{"typography":{"textAlign":"right"}}} /-->
<!-- /wp:mai/column -->
<!-- /wp:mai/columns --></nav>
<!-- /wp:group -->

<!-- wp:comments {"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
<div class="wp-block-comments" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:comments-title {"level":2,"fontSize":"large"} /-->

<!-- wp:comment-template -->
<!-- wp:group {"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
<div class="wp-block-group" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:avatar {"size":48} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:comment-author-name /-->

<!-- wp:comment-date /-->

<!-- wp:comment-content /-->

<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:comment-edit-link /-->

<!-- wp:comment-reply-link /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:comment-template -->

<!-- wp:comments-pagination {"layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:comments-pagination-previous /-->

<!-- wp:comments-pagination-next /-->
<!-- /wp:comments-pagination -->

<!-- wp:post-comments-form /--></div>
<!-- /wp:comments -->
<!-- /wp:mai/column -->

<!-- wp:mai/column -->
<!-- wp:template-part {"slug":"sidebar","area":"sidebar"} /-->
<!-- /wp:mai/column -->
<!-- /wp:mai/columns --></main>
<!-- /wp:group -->

<!-- wp:mai/slot {"location":"mai/before-footer","lock":{"remove":true}} /-->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->

<!-- wp:mai/slot {"location":"mai/after-footer","lock":{"remove":true}} /-->
