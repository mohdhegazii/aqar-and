<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) { die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects - MODIFIED
----------------------------------------------------------------------------- */

function get_my_related_projects() {

  ob_start();

  $current_post_id = get_the_ID();

  $related_project_ids = jawda_get_project_internal_links( $current_post_id );
  $heading             = jawda_get_project_internal_links_heading( $current_post_id );

  if ( empty( $related_project_ids ) ) {
      $fallback = jawda_get_project_internal_links_fallback_data( $current_post_id );

      if ( isset( $fallback['ids'] ) && is_array( $fallback['ids'] ) ) {
          $related_project_ids = $fallback['ids'];
      }

      if ( '' === $heading && ! empty( $fallback['heading'] ) ) {
          $heading = $fallback['heading'];
      }
  }

  if ( empty( $related_project_ids ) ) {
      return;
  }

  $filtered_ids = array();
  $seen         = array();

  foreach ( $related_project_ids as $project_id ) {
      $project_id = absint( $project_id );

      if ( $project_id === $current_post_id ) {
          continue;
      }

      if ( isset( $seen[ $project_id ] ) ) {
          continue;
      }

      if ( 'publish' !== get_post_status( $project_id ) ) {
          continue;
      }

      $seen[ $project_id ] = true;
      $filtered_ids[]      = $project_id;
  }

  if ( empty( $filtered_ids ) ) {
      return;
  }

  if ( '' === $heading ) {
      $is_arabic = function_exists( 'aqarand_is_arabic_locale' ) ? aqarand_is_arabic_locale() : is_rtl();
      $heading   = $is_arabic ? 'مشروعات مشابهة' : 'Related projects';
  }

  ?>

  <div>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <div class="headline">
            <h2><?php echo esc_html( $heading ); ?></h2>
            <div class="separator"></div>
          </div>
        </div>
      </div>
      <div class="row">
        <?php foreach ( $filtered_ids as $project_id ) : ?>
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
