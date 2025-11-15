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
  $district_id        = absint( get_post_meta( $current_post_id, 'loc_district_id', true ) );
  $city_id            = absint( get_post_meta( $current_post_id, 'loc_city_id', true ) );
  $governorate_id     = absint( get_post_meta( $current_post_id, 'loc_governorate_id', true ) );
  $posts_per_section  = 5;

  if ( ! $main_category_id ) {
      return;
  }

  $current_post_date = get_post_time( 'Y-m-d H:i:s', false, $current_post_id );

  $base_args = array(
      'post_type'              => 'projects',
      'post_status'            => 'publish',
      'posts_per_page'         => $posts_per_section,
      'orderby'                => 'date',
      'order'                  => 'DESC',
      'fields'                 => 'ids',
      'post__not_in'           => array( $current_post_id ),
      'no_found_rows'          => true,
      'update_post_meta_cache' => false,
      'update_post_term_cache' => false,
  );

  $base_meta_query = array(
      'relation' => 'AND',
      array(
          'key'     => 'jawda_main_category_id',
          'value'   => $main_category_id,
          'compare' => '=',
      ),
  );

  $collect_related_projects = function( $meta_key, $meta_value, $remaining, $exclude_ids, $direction ) use ( $base_args, $base_meta_query, $current_post_date ) {
      if ( $remaining <= 0 ) {
          return array();
      }

      if ( $meta_key && ! $meta_value ) {
          return array();
      }

      $args                 = $base_args;
      $args['posts_per_page'] = $remaining;
      $args['post__not_in'] = array_values( array_unique( array_merge( $base_args['post__not_in'], $exclude_ids ) ) );

      $meta_query = $base_meta_query;

      if ( $meta_key ) {
          $meta_query[] = array(
              'key'     => $meta_key,
              'value'   => $meta_value,
              'compare' => '=',
          );
      }

      $args['meta_query'] = $meta_query;

      if ( 'before' === $direction ) {
          $args['date_query'] = array(
              array(
                  'column'    => 'post_date',
                  'before'    => $current_post_date,
                  'inclusive' => false,
              ),
          );
      } elseif ( 'after' === $direction ) {
          $args['date_query'] = array(
              array(
                  'column'    => 'post_date',
                  'after'     => $current_post_date,
                  'inclusive' => false,
              ),
          );
      }

      $query = new WP_Query( $args );
      $ids   = array_map( 'intval', $query->posts );

      wp_reset_postdata();

      return $ids;
  };

  $location_meta_order = array(
      'loc_district_id'   => $district_id,
      'loc_city_id'       => $city_id,
      'loc_governorate_id'=> $governorate_id,
  );

  $related_ids = array();

  foreach ( $location_meta_order as $meta_key => $meta_value ) {
      $remaining = $posts_per_section - count( $related_ids );

      if ( $remaining <= 0 ) {
          break;
      }

      $related_ids = array_merge(
          $related_ids,
          $collect_related_projects( $meta_key, $meta_value, $remaining, $related_ids, 'before' )
      );
  }

  if ( count( $related_ids ) < $posts_per_section ) {
      foreach ( $location_meta_order as $meta_key => $meta_value ) {
          $remaining = $posts_per_section - count( $related_ids );

          if ( $remaining <= 0 ) {
              break;
          }

          $related_ids = array_merge(
              $related_ids,
              $collect_related_projects( $meta_key, $meta_value, $remaining, $related_ids, 'after' )
          );
      }
  }

  if ( count( $related_ids ) < $posts_per_section ) {
      $remaining   = $posts_per_section - count( $related_ids );
      $related_ids = array_merge(
          $related_ids,
          $collect_related_projects( null, null, $remaining, $related_ids, 'before' )
      );
  }

  if ( count( $related_ids ) < $posts_per_section ) {
      $remaining   = $posts_per_section - count( $related_ids );
      $related_ids = array_merge(
          $related_ids,
          $collect_related_projects( null, null, $remaining, $related_ids, 'after' )
      );
  }

  $related_ids = array_values( array_unique( array_filter( $related_ids ) ) );
  $related_ids = array_slice( $related_ids, 0, $posts_per_section );

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
              ? sprintf( 'مشروعات %s في نفس المنطقة', $category_label )
              : sprintf( '%s projects in the same area', $category_label );
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
