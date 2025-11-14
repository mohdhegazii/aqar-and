<?php

// files are not executed directly
if ( ! defined( 'ABSPATH' ) ) {	die( 'Invalid request.' ); }

/* -----------------------------------------------------------------------------
# related_projects - MODIFIED
----------------------------------------------------------------------------- */

function get_my_related_projects() {

  ob_start();

  $current_post_id   = get_the_ID();
  $related_category  = get_the_terms( $current_post_id, 'project_category' );
  $city_id           = absint( get_post_meta( $current_post_id, 'loc_city_id', true ) );
  $district_id       = absint( get_post_meta( $current_post_id, 'loc_district_id', true ) );
  $governorate_id    = absint( get_post_meta( $current_post_id, 'loc_governorate_id', true ) );
  $posts_per_section = 5;

  $base_args = array(
      'post_type'    => 'projects',
      'post__not_in' => array( $current_post_id ),
      'orderby'      => 'date',
      'order'        => 'DESC',
      'date_query'   => array(
          'before' => get_the_date( 'Y-m-d H:i:s', $current_post_id ),
      ),
  );

  if ( ! empty( $related_category ) && is_array( $related_category ) ) {
      $base_args['tax_query'] = array(
          'relation' => 'AND',
          array(
              'taxonomy' => 'project_category',
              'field'    => 'term_id',
              'terms'    => wp_list_pluck( $related_category, 'term_id' ),
          ),
      );
  }

  $collect_related_projects = function( $meta_key, $meta_value, $remaining, $exclude_ids = array() ) use ( $base_args ) {
      if ( empty( $meta_value ) || $remaining <= 0 ) {
          return array();
      }

      $args                     = $base_args;
      $args['posts_per_page']   = $remaining;
      $args['post__not_in']     = array_values( array_unique( array_merge( $base_args['post__not_in'], $exclude_ids ) ) );
      $args['meta_query']       = array(
          array(
              'key'     => $meta_key,
              'value'   => $meta_value,
              'compare' => '=',
          ),
      );

      $query    = new WP_Query( $args );
      $post_ids = wp_list_pluck( $query->posts, 'ID' );
      wp_reset_postdata();

      return $post_ids;
  };

  $similar_project_ids = array();

  if ( $district_id ) {
      $similar_project_ids = array_merge(
          $similar_project_ids,
          $collect_related_projects( 'loc_district_id', $district_id, $posts_per_section, $similar_project_ids )
      );
  }

  if ( count( $similar_project_ids ) < $posts_per_section && $city_id ) {
      $similar_project_ids = array_merge(
          $similar_project_ids,
          $collect_related_projects(
              'loc_city_id',
              $city_id,
              $posts_per_section - count( $similar_project_ids ),
              $similar_project_ids
          )
      );
  }

  if ( count( $similar_project_ids ) < $posts_per_section && $governorate_id ) {
      $similar_project_ids = array_merge(
          $similar_project_ids,
          $collect_related_projects(
              'loc_governorate_id',
              $governorate_id,
              $posts_per_section - count( $similar_project_ids ),
              $similar_project_ids
          )
      );
  }

  $other_city_project_ids = array();
  if ( $city_id ) {
      $other_city_project_ids = $collect_related_projects(
          'loc_city_id',
          $city_id,
          $posts_per_section,
          array_merge( $similar_project_ids, array( $current_post_id ) )
      );
  }

  $has_related_content = ! empty( $similar_project_ids ) || ! empty( $other_city_project_ids );

  if ( $has_related_content ) {
      global $wpdb;
      $is_ar   = function_exists( 'aqarand_is_arabic_locale' ) ? aqarand_is_arabic_locale() : is_rtl();
      $name_col = $is_ar ? 'name_ar' : 'name_en';

      $category_label = '';
      if ( ! empty( $related_category ) && isset( $related_category[0] ) ) {
          $category_label = $related_category[0]->name;
      }

      $city_label = '';
      if ( $city_id ) {
          $city_label = $wpdb->get_var(
              $wpdb->prepare( "SELECT {$name_col} FROM {$wpdb->prefix}locations_cities WHERE id = %d", $city_id )
          );
      }

      $other_heading = '';
      if ( $category_label && $city_label ) {
          $other_heading = $is_ar
              ? sprintf( 'مشروعات %1$s أخرى في %2$s', $category_label, $city_label )
              : sprintf( 'Other %1$s in %2$s', $category_label, $city_label );
      }

      ?>

      <div>
        <div class="container">
          <?php if ( ! empty( $similar_project_ids ) ) : ?>
            <div class="row">
              <div class="col-md-12">
                <div class="headline">
                  <h2><?php txt( 'Similar projects' ); ?></h2>
                  <div class="separator"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <?php foreach ( $similar_project_ids as $project_id ) : ?>
                <div class="col-md-4">
                  <?php get_my_project_box( $project_id ); ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ( ! empty( $other_city_project_ids ) && $other_heading ) : ?>
            <div class="row">
              <div class="col-md-12">
                <div class="headline">
                  <h2><?php echo esc_html( $other_heading ); ?></h2>
                  <div class="separator"></div>
                </div>
              </div>
            </div>
            <div class="row">
              <?php foreach ( $other_city_project_ids as $project_id ) : ?>
                <div class="col-md-4">
                  <?php get_my_project_box( $project_id ); ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <?php
  }

  $content = ob_get_clean();
  echo minify_html( $content );

}
