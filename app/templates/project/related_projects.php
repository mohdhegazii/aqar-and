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

  $query = new WP_Query(
      array(
          'post_type'              => 'projects',
          'post_status'            => 'publish',
          'posts_per_page'         => -1,
          'orderby'                => 'date',
          'order'                  => 'ASC',
          'fields'                 => 'ids',
          'no_found_rows'          => true,
          'update_post_meta_cache' => false,
          'update_post_term_cache' => false,
          'meta_query'             => array(
              array(
                  'key'     => 'jawda_main_category_id',
                  'value'   => $main_category_id,
                  'compare' => '=',
              ),
          ),
      )
  );

  $ordered_ids = array_map( 'intval', $query->posts );

  wp_reset_postdata();

  if ( empty( $ordered_ids ) ) {
      return;
  }

  $current_index = array_search( $current_post_id, $ordered_ids, true );

  if ( false === $current_index ) {
      return;
  }

  $cache_meta_value = function( $post_id, $meta_key, &$cache ) {
      if ( isset( $cache[ $post_id ][ $meta_key ] ) ) {
          return $cache[ $post_id ][ $meta_key ];
      }

      $value = get_post_meta( $post_id, $meta_key, true );
      $cache[ $post_id ][ $meta_key ] = $value;

      return $value;
  };

  $ring_collect = function( $meta_key, $meta_value, $selected_ids, $limit ) use ( $ordered_ids, $current_index, &$cache_meta_value ) {
      if ( $limit <= 0 ) {
          return array();
      }

      if ( $meta_key && ! $meta_value ) {
          return array();
      }

      $collected   = array();
      $meta_cache  = array();
      $total_posts = count( $ordered_ids );

      $match_post = function( $post_id ) use ( $meta_key, $meta_value, &$cache_meta_value, &$meta_cache ) {
          if ( ! $meta_key ) {
              return true;
          }

          $value = $cache_meta_value( $post_id, $meta_key, $meta_cache );

          return absint( $value ) === absint( $meta_value );
      };

      for ( $i = $current_index - 1; $i >= 0 && count( $collected ) < $limit; $i-- ) {
          $post_id = $ordered_ids[ $i ];

          if ( $post_id === $ordered_ids[ $current_index ] ) {
              continue;
          }

          if ( in_array( $post_id, $selected_ids, true ) || in_array( $post_id, $collected, true ) ) {
              continue;
          }

          if ( $match_post( $post_id ) ) {
              $collected[] = $post_id;
          }
      }

      if ( count( $collected ) < $limit ) {
          for ( $i = $total_posts - 1; $i > $current_index && count( $collected ) < $limit; $i-- ) {
              $post_id = $ordered_ids[ $i ];

              if ( $post_id === $ordered_ids[ $current_index ] ) {
                  continue;
              }

              if ( in_array( $post_id, $selected_ids, true ) || in_array( $post_id, $collected, true ) ) {
                  continue;
              }

              if ( $match_post( $post_id ) ) {
                  $collected[] = $post_id;
              }
          }
      }

      return $collected;
  };

  $location_meta_order = array(
      'loc_district_id'    => $district_id,
      'loc_city_id'        => $city_id,
      'loc_governorate_id' => $governorate_id,
  );

  $related_ids = array();

  foreach ( $location_meta_order as $meta_key => $meta_value ) {
      $remaining = $posts_per_section - count( $related_ids );

      if ( $remaining <= 0 ) {
          break;
      }

      $related_ids = array_merge(
          $related_ids,
          $ring_collect( $meta_key, $meta_value, $related_ids, $remaining )
      );
  }

  if ( count( $related_ids ) < $posts_per_section ) {
      $remaining   = $posts_per_section - count( $related_ids );
      $related_ids = array_merge(
          $related_ids,
          $ring_collect( null, null, $related_ids, $remaining )
      );
  }

  $related_ids = array_slice( array_unique( array_filter( $related_ids ) ), 0, $posts_per_section );

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
