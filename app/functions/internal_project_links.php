<?php

/**
 * Internal project linking system.
 */

if ( ! defined( 'JAWDA_INTERNAL_LINKS_META_KEY' ) ) {
    define( 'JAWDA_INTERNAL_LINKS_META_KEY', '_internal_related_projects_ids' );
}

/**
 * Regenerate internal links for all published projects.
 *
 * This method groups projects by main category and location hierarchy,
 * sorts them by publish date (DESC), applies a circular ring logic and stores
 * up to five related project IDs in post meta.
 */
function jawda_regenerate_internal_project_links() {
    global $wpdb;

    $posts_table = $wpdb->posts;
    $meta_table  = $wpdb->postmeta;

    $rows = $wpdb->get_results(
        "SELECT p.ID, p.post_date,
                mc.meta_value  AS main_category_id,
                gov.meta_value AS governorate_id,
                city.meta_value AS city_id,
                dist.meta_value AS district_id
           FROM {$posts_table} p
      LEFT JOIN {$meta_table} mc
             ON (mc.post_id = p.ID AND mc.meta_key = 'jawda_main_category_id')
      LEFT JOIN {$meta_table} gov
             ON (gov.post_id = p.ID AND gov.meta_key = 'loc_governorate_id')
      LEFT JOIN {$meta_table} city
             ON (city.post_id = p.ID AND city.meta_key = 'loc_city_id')
      LEFT JOIN {$meta_table} dist
             ON (dist.post_id = p.ID AND dist.meta_key = 'loc_district_id')
          WHERE p.post_type = 'projects'
            AND p.post_status = 'publish'
          ORDER BY p.ID ASC",
        ARRAY_A
    );

    if ( empty( $rows ) ) {
        return;
    }

    $projects = [];
    $category_groups = [];

    foreach ( $rows as $row ) {
        $project_id      = (int) $row['ID'];
        $main_category   = (int) ( $row['main_category_id'] ?? 0 );
        $governorate_id  = (int) ( $row['governorate_id'] ?? 0 );
        $city_id         = (int) ( $row['city_id'] ?? 0 );
        $district_id     = (int) ( $row['district_id'] ?? 0 );
        $post_date       = $row['post_date'];
        $timestamp       = strtotime( $post_date );

        if ( false === $timestamp ) {
            $timestamp = 0;
        }

        $projects[ $project_id ] = [
            'id'             => $project_id,
            'post_date'      => $post_date,
            'timestamp'      => $timestamp,
            'main_category'  => $main_category,
            'governorate_id' => $governorate_id,
            'city_id'        => $city_id,
            'district_id'    => $district_id,
        ];

        if ( ! isset( $category_groups[ $main_category ] ) ) {
            $category_groups[ $main_category ] = [
                'all'         => [],
                'governorate' => [],
                'city'        => [],
                'district'    => [],
            ];
        }

        $category_groups[ $main_category ]['all'][] = $project_id;

        if ( $governorate_id > 0 ) {
            $category_groups[ $main_category ]['governorate'][ $governorate_id ][] = $project_id;
        }

        if ( $city_id > 0 ) {
            $category_groups[ $main_category ]['city'][ $city_id ][] = $project_id;
        }

        if ( $district_id > 0 ) {
            $category_groups[ $main_category ]['district'][ $district_id ][] = $project_id;
        }
    }

    $final_clusters = [];

    foreach ( $projects as $project ) {
        $main_category = $project['main_category'];
        $cluster_level = 'category';
        $cluster_id    = 0;

        if ( $project['district_id'] > 0 ) {
            $district_projects = $category_groups[ $main_category ]['district'][ $project['district_id'] ] ?? [];
            if ( count( $district_projects ) >= 6 ) {
                $cluster_level = 'district';
                $cluster_id    = $project['district_id'];
            }
        }

        if ( 'category' === $cluster_level && $project['city_id'] > 0 ) {
            $city_projects = $category_groups[ $main_category ]['city'][ $project['city_id'] ] ?? [];
            if ( count( $city_projects ) >= 6 ) {
                $cluster_level = 'city';
                $cluster_id    = $project['city_id'];
            }
        }

        if ( 'category' === $cluster_level && $project['governorate_id'] > 0 ) {
            $governorate_projects = $category_groups[ $main_category ]['governorate'][ $project['governorate_id'] ] ?? [];
            if ( ! empty( $governorate_projects ) ) {
                $cluster_level = 'governorate';
                $cluster_id    = $project['governorate_id'];
            }
        }

        if ( 'category' === $cluster_level ) {
            $cluster_id = 0;
        }

        $cluster_key = sprintf( '%s:%s:%s', $main_category, $cluster_level, $cluster_id );

        if ( ! isset( $final_clusters[ $cluster_key ] ) ) {
            $final_clusters[ $cluster_key ] = [];
        }

        $final_clusters[ $cluster_key ][] = $project['id'];
    }

    if ( empty( $final_clusters ) ) {
        return;
    }

    $links_map = [];

    foreach ( $final_clusters as $cluster_key => $cluster_project_ids ) {
        if ( empty( $cluster_project_ids ) ) {
            continue;
        }

        usort(
            $cluster_project_ids,
            static function ( $a, $b ) use ( $projects ) {
                $time_a = $projects[ $a ]['timestamp'];
                $time_b = $projects[ $b ]['timestamp'];

                if ( $time_a === $time_b ) {
                    return $projects[ $b ]['id'] <=> $projects[ $a ]['id'];
                }

                return $time_b <=> $time_a;
            }
        );

        $total_projects = count( $cluster_project_ids );

        foreach ( $cluster_project_ids as $index => $project_id ) {
            $limit = min( 5, $total_projects - 1 );
            $links = [];

            for ( $offset = 1; $offset <= $limit; $offset++ ) {
                $target_index = ( $index + $offset ) % $total_projects;
                $target_id    = (int) $cluster_project_ids[ $target_index ];

                if ( $target_id === $project_id ) {
                    continue;
                }

                $links[] = $target_id;
            }

            $links_map[ $project_id ] = $links;
        }
    }

    foreach ( $projects as $project_id => $project ) {
        $linked_ids = $links_map[ $project_id ] ?? [];
        update_post_meta( $project_id, JAWDA_INTERNAL_LINKS_META_KEY, $linked_ids );
    }
}

/**
 * Trigger regeneration when a new project is published.
 *
 * @param string  $new_status New status.
 * @param string  $old_status Previous status.
 * @param WP_Post $post       Post object.
 */
function jawda_maybe_regenerate_project_links_on_publish( $new_status, $old_status, $post ) {
    if ( 'projects' !== ( $post->post_type ?? '' ) ) {
        return;
    }

    if ( 'publish' !== $new_status || 'publish' === $old_status ) {
        return;
    }

    jawda_regenerate_internal_project_links();
}
add_action( 'transition_post_status', 'jawda_maybe_regenerate_project_links_on_publish', 10, 3 );

/**
 * Retrieve the stored internal links for a project.
 *
 * @param int $project_id Project ID.
 *
 * @return int[] Array of linked project IDs.
 */
function jawda_get_internal_project_links( $project_id ) {
    $project_id = (int) $project_id;

    if ( $project_id <= 0 ) {
        return [];
    }

    $linked_ids = get_post_meta( $project_id, JAWDA_INTERNAL_LINKS_META_KEY, true );

    if ( ! is_array( $linked_ids ) ) {
        return [];
    }

    $linked_ids = array_values( array_unique( array_map( 'absint', $linked_ids ) ) );
    $linked_ids = array_filter( $linked_ids, static fn( $id ) => $id > 0 );

    return array_slice( $linked_ids, 0, 5 );
}
