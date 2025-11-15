<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects - MODIFIED
----------------------------------------------------------------------------- */

function get_my_related_projects() {

  ob_start();

  $current_post_id = get_the_ID();

  // Always work with the full ordered list of published projects so the
  // circular algorithm stays consistent across every page render.
  $all_project_ids = get_posts(
      array(
          'post_type'      => 'projects',
          'post_status'    => 'publish',
          'posts_per_page' => -1,
          'orderby'        => 'date',
          'order'          => 'DESC',
          'fields'         => 'ids',
      )
  );

  $total_projects = count( $all_project_ids );

  // Without at least two projects there is nothing sensible to link to.
  if ( $total_projects <= 1 ) {
      return;
  }

  // Locate the current project within the ordered list to determine its index.
  $current_index = array_search( $current_post_id, $all_project_ids, true );

  // If the project is missing (unpublished or excluded) abort gracefully.
  if ( false === $current_index ) {
      return;
  }

  $links_per_project    = 5;
  $related_project_ids = array();

  if ( $total_projects <= $links_per_project ) {
      // Special case: when the catalogue is small, link to every other project.
      foreach ( $all_project_ids as $project_id ) {
          if ( $project_id === $current_post_id ) {
              continue;
          }

          $related_project_ids[] = $project_id;
      }
  } else {
      // Standard case: take the next K projects in publish-date order, wrapping
      // back to the newest projects once we hit the end of the list.
      for ( $offset = 1; $offset <= $links_per_project; $offset++ ) {
          $wrapped_index = ( $current_index + $offset ) % $total_projects;
          $related_project_ids[] = $all_project_ids[ $wrapped_index ];
      }
  }

  if ( empty( $related_project_ids ) ) {
      return;
  }

  ?>

  <div>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="headline">
            <h2><?php txt( 'Related projects' ); ?></h2>
            <div class="separator"></div>
          </div>
        </div>
      </div>
      <div class="row">
        <?php foreach ( $related_project_ids as $project_id ) : ?>
          <div class="col-md-4">
            <?php get_my_project_box( $project_id ); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php

  $content = ob_get_clean();
  echo minify_html( $content );

}
