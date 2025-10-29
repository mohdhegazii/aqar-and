<?php
/*
Template Name: catalogs Page
Template Post Type: page
*/

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# Front Page
----------------------------------------------------------------------------- */

// Jawda header
get_my_header();

// Post Loop
while ( have_posts() ) : the_post();

// Page Header
get_my_page_header();

// End Loop
endwhile;

// Reset My Data
wp_reset_postdata();

?>
    <div class="projectspage">
      <div class="container">
    		<div class="row">
            <?php
         if ( get_query_var('paged') ) {
             $paged = get_query_var('paged');
         } elseif ( get_query_var('page') ) { // 'page' is used instead of 'paged' on Static Front Page
             $paged = get_query_var('page');
         } else {
             $paged = 1;
         }
         $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
         $loop = new WP_Query(
             array(
                 'post_type' => 'catalogs',
                 'posts_per_page' => get_option('posts_per_page'),
                 'paged' => $paged,
                 'post_status' => 'publish',
                 'orderby' => 'date', // modified | title | name | ID | rand
                 'order' => 'DESC'
             )
         );
       ?>
       <?php if ($loop->have_posts()): while ($loop->have_posts()) : $loop->the_post(); ?>
         <div class="col-md-4 projectbxspace">
            <?php get_my_article_box(); ?>
         </div>
            <?php endwhile; ?>
            <?php if ($loop->max_num_pages > 1) : // custom pagination  ?>
         <div class="col-md-12 center">
           <div class="blognavigation">
             <?php
             global $wp_query;
               $orig_query = $wp_query; // fix for pagination to work
               $wp_query = $loop;
               $big = 999999999;
               echo paginate_links(array(
                   'base' => str_replace($big, '%#%', get_pagenum_link($big)),
                   'format' => '?paged=%#%',
                   'current' => max(1, get_query_var('paged')),
                   'total' => $wp_query->max_num_pages
               ));
               $wp_query = $orig_query; // fix for pagination to work
             ?>
           </div>
        </div>
      <?php endif; endif; wp_reset_postdata(); ?>
    		</div>
    	</div>
    </div>

    <?php if ( !empty(get_the_content()) || get_the_content() !== "" ): ?>
    <div class="project-main">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
            <div class="content-box maincontent">
            <?php wpautop(the_content()); ?>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
<?php

// Jawda header
get_my_footer();