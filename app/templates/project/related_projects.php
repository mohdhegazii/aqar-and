<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects - MODIFIED
----------------------------------------------------------------------------- */

function get_my_related_projects() {

  ob_start();

  $current_post_id = get_the_ID();
  $linked_ids      = jawda_get_internal_project_links( $current_post_id );

  if ( empty( $linked_ids ) ) {
      $content = ob_get_clean();
      echo minify_html( $content );
      return;
  }

  $related_projects = get_posts( array(
      'post_type'      => 'projects',
      'post__in'       => $linked_ids,
      'posts_per_page' => count( $linked_ids ),
      'orderby'        => 'post__in',
  ) );

  if ( empty( $related_projects ) ) {
      $content = ob_get_clean();
      echo minify_html( $content );
      return;
  }

  ?>

  <div>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="headline">
            <h2><?php txt( 'Similar projects' ); ?></h2>
            <div class="separator"></div>
          </div>
        </div>
      </div>
      <div class="row">
        <?php foreach ( $related_projects as $project ) : ?>
          <div class="col-md-4">
            <?php get_my_project_box( $project->ID ); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php

  $content = ob_get_clean();
  echo minify_html( $content );

}
