<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects - MODIFIED
----------------------------------------------------------------------------- */

function get_my_related_projects() {

  ob_start();

  $current_post_id    = get_the_ID();
  $main_category_id   = absint( get_post_meta( $current_post_id, 'jawda_main_category_id', true ) );
  $posts_per_section  = 5;

  if ( ! $main_category_id ) {
      return;
  }

  $base_meta_query = array(
      array(
          'key'     => 'jawda_main_category_id',
          'value'   => $main_category_id,
          'compare' => '=',
      ),
  );

  $all_projects_query = new WP_Query(
      array(
          'post_type'              => 'projects',
          'post_status'            => 'publish',
          'posts_per_page'         => -1,
          'orderby'                => 'date',
          'order'                  => 'DESC',
          'meta_query'             => $base_meta_query,
          'fields'                 => 'ids',
          'no_found_rows'          => true,
          'update_post_meta_cache' => false,
          'update_post_term_cache' => false,
      )
  );

  $related_ids = array();

  if ( $all_projects_query->have_posts() ) {
      $all_project_ids = $all_projects_query->posts;
      $current_index   = array_search( $current_post_id, $all_project_ids, true );

      if ( false !== $current_index ) {
          $total_projects = count( $all_project_ids );
          $max_related    = min( $posts_per_section, max( 0, $total_projects - 1 ) );

          if ( $max_related > 0 ) {
              $pointer = ( $current_index + 1 ) % $total_projects;

              while ( count( $related_ids ) < $max_related && $pointer !== $current_index ) {
                  $related_ids[] = $all_project_ids[ $pointer ];
                  $pointer       = ( $pointer + 1 ) % $total_projects;
              }
          }
      }
  }

  wp_reset_postdata();

  if ( ! empty( $related_ids ) ) {
      global $wpdb;
      $is_ar   = function_exists( 'aqarand_is_arabic_locale' ) ? aqarand_is_arabic_locale() : is_rtl();
      $name_col = $is_ar ? 'name_ar' : 'name_en';

      $category_label = '';
      if ( $main_category_id ) {
          $category_label = $wpdb->get_var(
              $wpdb->prepare(
                  "SELECT {$name_col} FROM {$wpdb->prefix}property_categories WHERE id = %d",
                  $main_category_id
              )
          );
      }

      $heading_text = $is_ar ? 'مشروعات مشابهة' : 'Similar projects';

      if ( $category_label ) {
          $heading_text = $is_ar
              ? sprintf( 'مشروعات %s السابقة', $category_label )
              : sprintf( 'Previous %s projects', $category_label );
      }

      ?>

      <div>
        <div class="container">
          <div class="row">
            <div class="col-md-12">
              <div class="headline">
                <h2><?php echo esc_html( $heading_text ); ?></h2>
                <div class="separator"></div>
              </div>
            </div>
          </div>
          <div class="row">
            <?php foreach ( $related_ids as $project_id ) : ?>
              <div class="col-md-4">
                <?php get_my_project_box( $project_id ); ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php
  }

  $content = ob_get_clean();
  echo minify_html( $content );

}
