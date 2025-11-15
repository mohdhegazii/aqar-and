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
  $fallback_data       = null;

  if ( empty( $related_project_ids ) ) {
      $fallback_data = jawda_get_project_internal_links_fallback_data( $current_post_id );

      if ( isset( $fallback_data['ids'] ) && is_array( $fallback_data['ids'] ) ) {
          $related_project_ids = $fallback_data['ids'];
      }

      if ( '' === $heading && ! empty( $fallback_data['heading'] ) ) {
          $heading = $fallback_data['heading'];
      }
  }

  $filter_ids = function( $ids ) use ( $current_post_id ) {
      $filtered = array();
      $seen     = array();

      foreach ( (array) $ids as $project_id ) {
          $project_id = absint( $project_id );

          if ( ! $project_id || $project_id === $current_post_id ) {
              continue;
          }

          if ( isset( $seen[ $project_id ] ) ) {
              continue;
          }

          if ( 'publish' !== get_post_status( $project_id ) ) {
              continue;
          }

          $seen[ $project_id ] = true;
          $filtered[]          = $project_id;
      }

      return $filtered;
  };

  $filtered_ids = $filter_ids( $related_project_ids );

  if ( empty( $filtered_ids ) ) {
      if ( ! is_array( $fallback_data ) ) {
          $fallback_data = jawda_get_project_internal_links_fallback_data( $current_post_id );
      }

      if ( ! empty( $fallback_data['ids'] ) ) {
          $filtered_ids = $filter_ids( $fallback_data['ids'] );
      }

      if ( '' === $heading && ! empty( $fallback_data['heading'] ) ) {
          $heading = $fallback_data['heading'];
      }
  }

  if ( ! empty( $filtered_ids ) ) {
      $filtered_ids = array_slice( $filtered_ids, 0, 5 );
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
        <div class="col-12">
          <div class="related-projects-slider" data-related-slides="5">
            <?php foreach ( $filtered_ids as $project_id ) : ?>
              <div class="related-projects-slider__item projectbxspace">
                <?php get_my_project_box( $project_id ); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php

  $content = ob_get_clean();
  echo minify_html( $content );

}
