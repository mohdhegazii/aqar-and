<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const JAWDA_PROJECT_LINKS_META_KEY       = '_internal_related_projects_ids';
const JAWDA_PROJECT_LINKS_SCOPE_META_KEY = '_internal_related_projects_scope';

/**
 * Retrieve the cached related project IDs for a given project.
 */
function jawda_get_project_internal_links( $project_id ) {
    static $attempted_regeneration = false;

    $stored = get_post_meta( $project_id, JAWDA_PROJECT_LINKS_META_KEY, true );

    if ( is_string( $stored ) && $stored !== '' ) {
        $maybe_unserialized = maybe_unserialize( $stored );
        if ( is_array( $maybe_unserialized ) ) {
            $stored = $maybe_unserialized;
        } else {
            $stored = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $stored ) ) ) );
        }
    }

    if ( ! is_array( $stored ) && ! $attempted_regeneration ) {
        $attempted_regeneration = true;
        jawda_regenerate_project_internal_links();
        $stored = get_post_meta( $project_id, JAWDA_PROJECT_LINKS_META_KEY, true );

        if ( is_string( $stored ) && $stored !== '' ) {
            $maybe_unserialized = maybe_unserialize( $stored );
            if ( is_array( $maybe_unserialized ) ) {
                $stored = $maybe_unserialized;
            } else {
                $stored = array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $stored ) ) ) );
            }
        }
    }

    if ( ! is_array( $stored ) ) {
        return array();
    }

    $ids = array();
    foreach ( $stored as $id ) {
        $id = absint( $id );
        if ( $id > 0 ) {
            $ids[] = $id;
        }
    }

    return $ids;
}

/**
 * Retrieve the stored scope definition (location level) for a project.
 */
function jawda_get_project_internal_links_scope( $project_id ) {
    $stored = get_post_meta( $project_id, JAWDA_PROJECT_LINKS_SCOPE_META_KEY, true );

    if ( ! is_array( $stored ) ) {
        return array();
    }

    return array(
        'level'       => isset( $stored['level'] ) ? (string) $stored['level'] : '',
        'location_id' => isset( $stored['location_id'] ) ? absint( $stored['location_id'] ) : 0,
        'category_id' => isset( $stored['category_id'] ) ? absint( $stored['category_id'] ) : 0,
    );
}

/**
 * Build the localized heading for a project's related links section.
 */
function jawda_get_project_internal_links_heading( $project_id ) {
    $scope = jawda_get_project_internal_links_scope( $project_id );

    return jawda_format_project_internal_links_heading( $scope );
}

/**
 * Build the localized heading for a project's related links section using a scope definition.
 */
function jawda_format_project_internal_links_heading( array $scope ) {
    global $wpdb;

    if ( empty( $scope['category_id'] ) ) {
        return '';
    }

    $is_arabic = function_exists( 'aqarand_is_arabic_locale' ) ? aqarand_is_arabic_locale() : is_rtl();
    $name_col  = $is_arabic ? 'name_ar' : 'name_en';

    $category_name = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT {$name_col} FROM {$wpdb->prefix}property_categories WHERE id = %d",
            $scope['category_id']
        )
    );

    if ( ! $category_name ) {
        return '';
    }

    $location_label = '';
    if ( ! empty( $scope['location_id'] ) ) {
        switch ( $scope['level'] ) {
            case 'district':
                $table = $wpdb->prefix . 'locations_districts';
                break;
            case 'city':
                $table = $wpdb->prefix . 'locations_cities';
                break;
            case 'governorate':
                $table = $wpdb->prefix . 'locations_governorates';
                break;
            default:
                $table = '';
        }

        if ( $table ) {
            $location_label = $wpdb->get_var(
                $wpdb->prepare( "SELECT {$name_col} FROM {$table} WHERE id = %d", $scope['location_id'] )
            );
        }
    }

    if ( $location_label ) {
        return $is_arabic
            ? sprintf( 'مشروعات %1$s في %2$s', $category_name, $location_label )
            : sprintf( 'Other %1$s in %2$s', $category_name, $location_label );
    }

    return $is_arabic
        ? sprintf( 'مشروعات %s مشابهة', $category_name )
        : sprintf( 'Related %s projects', $category_name );
}

/**
 * Fallback query that mirrors the previous dynamic behaviour when no stored links exist.
 */
function jawda_get_project_internal_links_fallback_data( $project_id ) {
    $project_id = absint( $project_id );

    if ( ! $project_id ) {
        return array(
            'ids'     => array(),
            'heading' => '',
        );
    }

    $main_category_id  = absint( get_post_meta( $project_id, 'jawda_main_category_id', true ) );
    $city_id           = absint( get_post_meta( $project_id, 'loc_city_id', true ) );
    $district_id       = absint( get_post_meta( $project_id, 'loc_district_id', true ) );
    $governorate_id    = absint( get_post_meta( $project_id, 'loc_governorate_id', true ) );
    $posts_per_section = 5;

    if ( ! $main_category_id ) {
        return array(
            'ids'     => array(),
            'heading' => '',
        );
    }

    $base_args = array(
        'post_type'      => 'projects',
        'post_status'    => 'publish',
        'post__not_in'   => array( $project_id ),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'date_query'     => array(
            array(
                'before'    => get_post_field( 'post_date', $project_id ),
                'inclusive' => false,
            ),
        ),
        'fields'         => 'ids',
        'posts_per_page' => $posts_per_section,
        'no_found_rows'  => true,
    );

    $base_meta_query = array(
        array(
            'key'     => 'jawda_main_category_id',
            'value'   => $main_category_id,
            'compare' => '=',
        ),
    );

    $collect = function( $meta_key, $meta_value, $remaining, $exclude_ids = array() ) use ( $base_args, $base_meta_query ) {
        if ( empty( $meta_value ) || $remaining <= 0 ) {
            return array();
        }

        $args                   = $base_args;
        $args['posts_per_page'] = $remaining;
        $args['post__not_in']   = array_values( array_unique( array_merge( $base_args['post__not_in'], array_map( 'absint', (array) $exclude_ids ) ) ) );

        $meta_query   = $base_meta_query;
        $meta_query[] = array(
            'key'     => $meta_key,
            'value'   => $meta_value,
            'compare' => '=',
        );

        if ( count( $meta_query ) > 1 ) {
            $meta_query = array_merge( array( 'relation' => 'AND' ), $meta_query );
        }

        $args['meta_query'] = $meta_query;

        $query    = new WP_Query( $args );
        $post_ids = array_map( 'absint', $query->posts );
        wp_reset_postdata();

        return $post_ids;
    };

    $related_ids    = array();
    $scope_level    = '';
    $scope_location = 0;

    if ( $district_id ) {
        $district_posts = $collect( 'loc_district_id', $district_id, $posts_per_section, $related_ids );
        if ( ! empty( $district_posts ) ) {
            $related_ids    = array_merge( $related_ids, $district_posts );
            $scope_level    = 'district';
            $scope_location = $district_id;
        }
    }

    if ( count( $related_ids ) < $posts_per_section && $city_id ) {
        $remaining  = $posts_per_section - count( $related_ids );
        $city_posts = $collect( 'loc_city_id', $city_id, $remaining, $related_ids );

        if ( ! empty( $city_posts ) ) {
            $related_ids    = array_merge( $related_ids, $city_posts );
            $scope_level    = 'city';
            $scope_location = $city_id;
        }
    }

    if ( count( $related_ids ) < $posts_per_section && $governorate_id ) {
        $remaining       = $posts_per_section - count( $related_ids );
        $gov_posts       = $collect( 'loc_governorate_id', $governorate_id, $remaining, $related_ids );
        if ( ! empty( $gov_posts ) ) {
            $related_ids    = array_merge( $related_ids, $gov_posts );
            $scope_level    = 'governorate';
            $scope_location = $governorate_id;
        }
    }

    if ( count( $related_ids ) < $posts_per_section ) {
        $remaining = $posts_per_section - count( $related_ids );

        $args                   = $base_args;
        $args['posts_per_page'] = $remaining;
        $args['post__not_in']   = array_values( array_unique( array_merge( $base_args['post__not_in'], $related_ids ) ) );

        $meta_query = $base_meta_query;
        if ( count( $meta_query ) > 1 ) {
            $meta_query = array_merge( array( 'relation' => 'AND' ), $meta_query );
        }

        $args['meta_query'] = $meta_query;

        $query      = new WP_Query( $args );
        $more_posts = array_map( 'absint', $query->posts );
        wp_reset_postdata();

        if ( ! empty( $more_posts ) ) {
            $related_ids = array_merge( $related_ids, $more_posts );
            if ( '' === $scope_level ) {
                $scope_level    = 'category';
                $scope_location = 0;
            }
        }
    }

    $related_ids = array_slice( array_values( array_unique( array_map( 'absint', $related_ids ) ) ), 0, $posts_per_section );

    if ( empty( $related_ids ) ) {
        return array(
            'ids'     => array(),
            'heading' => '',
        );
    }

    if ( '' === $scope_level ) {
        $scope_level    = 'category';
        $scope_location = 0;
    }

    $heading = jawda_format_project_internal_links_heading(
        array(
            'level'       => $scope_level,
            'location_id' => $scope_location,
            'category_id' => $main_category_id,
        )
    );

    return array(
        'ids'     => $related_ids,
        'heading' => $heading,
    );
}

/**
 * Regenerate and persist the internal links for every published project.
 */
function jawda_regenerate_project_internal_links() {
    $projects = get_posts(
        array(
            'post_type'      => 'projects',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        )
    );

    if ( empty( $projects ) ) {
        return;
    }

    $records             = array();
    $links_map           = array();
    $scope_map           = array();
    $without_main_cat    = array();
    $records_by_category = array();

    foreach ( $projects as $project_id ) {
        $record = array(
            'ID'             => (int) $project_id,
            'main_category'  => (int) get_post_meta( $project_id, 'jawda_main_category_id', true ),
            'district_id'    => (int) get_post_meta( $project_id, 'loc_district_id', true ),
            'city_id'        => (int) get_post_meta( $project_id, 'loc_city_id', true ),
            'governorate_id' => (int) get_post_meta( $project_id, 'loc_governorate_id', true ),
        );

        $records[]               = $record;
        $links_map[ $project_id ] = array();

        if ( $record['main_category'] ) {
            $records_by_category[ $record['main_category'] ][] = $record;
        } else {
            $without_main_cat[] = $project_id;
        }
    }

    foreach ( $records_by_category as $category_id => $category_records ) {
        $unassigned = array();
        foreach ( $category_records as $record ) {
            $unassigned[ $record['ID'] ] = $record;
        }

        // District level clusters (require at least 6 projects).
        $district_groups = array();
        foreach ( $category_records as $record ) {
            if ( $record['district_id'] && isset( $unassigned[ $record['ID'] ] ) ) {
                $district_groups[ $record['district_id'] ][] = $record;
            }
        }

        foreach ( $district_groups as $district_id => $group_records ) {
            if ( count( $group_records ) < 6 ) {
                continue;
            }

            jawda_assign_project_link_cluster(
                array(
                    'level'       => 'district',
                    'location_id' => (int) $district_id,
                    'category_id' => (int) $category_id,
                    'projects'    => array_values( $group_records ),
                ),
                $links_map,
                $scope_map
            );

            foreach ( $group_records as $assigned ) {
                unset( $unassigned[ $assigned['ID'] ] );
            }
        }

        if ( empty( $unassigned ) ) {
            continue;
        }

        // City level clusters for remaining projects (require at least 6).
        $city_groups = array();
        foreach ( $unassigned as $record ) {
            if ( $record['city_id'] ) {
                $city_groups[ $record['city_id'] ][] = $record;
            }
        }

        foreach ( $city_groups as $city_id => $group_records ) {
            if ( count( $group_records ) < 6 ) {
                continue;
            }

            jawda_assign_project_link_cluster(
                array(
                    'level'       => 'city',
                    'location_id' => (int) $city_id,
                    'category_id' => (int) $category_id,
                    'projects'    => array_values( $group_records ),
                ),
                $links_map,
                $scope_map
            );

            foreach ( $group_records as $assigned ) {
                unset( $unassigned[ $assigned['ID'] ] );
            }
        }

        if ( ! empty( $unassigned ) ) {
            // Governorate level clusters (no minimum size).
            $governorate_groups = array();
            foreach ( $unassigned as $record ) {
                if ( $record['governorate_id'] ) {
                    $governorate_groups[ $record['governorate_id'] ][] = $record;
                }
            }

            foreach ( $governorate_groups as $governorate_id => $group_records ) {
                jawda_assign_project_link_cluster(
                    array(
                        'level'       => 'governorate',
                        'location_id' => (int) $governorate_id,
                        'category_id' => (int) $category_id,
                        'projects'    => array_values( $group_records ),
                    ),
                    $links_map,
                    $scope_map
                );

                foreach ( $group_records as $assigned ) {
                    unset( $unassigned[ $assigned['ID'] ] );
                }
            }
        }

        if ( ! empty( $unassigned ) ) {
            jawda_assign_project_link_cluster(
                array(
                    'level'       => 'category',
                    'location_id' => 0,
                    'category_id' => (int) $category_id,
                    'projects'    => array_values( $unassigned ),
                ),
                $links_map,
                $scope_map
            );
        }
    }

    // Persist results.
    foreach ( $records as $record ) {
        $project_id = $record['ID'];
        $links      = isset( $links_map[ $project_id ] ) ? $links_map[ $project_id ] : array();
        $links      = array_values( array_map( 'absint', $links ) );
        update_post_meta( $project_id, JAWDA_PROJECT_LINKS_META_KEY, $links );

        if ( isset( $scope_map[ $project_id ] ) ) {
            update_post_meta( $project_id, JAWDA_PROJECT_LINKS_SCOPE_META_KEY, $scope_map[ $project_id ] );
        } else {
            delete_post_meta( $project_id, JAWDA_PROJECT_LINKS_SCOPE_META_KEY );
        }
    }

    if ( ! empty( $without_main_cat ) ) {
        foreach ( $without_main_cat as $project_id ) {
            delete_post_meta( $project_id, JAWDA_PROJECT_LINKS_SCOPE_META_KEY );
        }
    }
}

/**
 * Assign a circular link cluster to the provided map references.
 *
 * @param array $cluster   Cluster definition (level, location_id, category_id, projects).
 * @param array $links_map Reference to the global links map.
 * @param array $scope_map Reference to the global scope map.
 */
function jawda_assign_project_link_cluster( array $cluster, array &$links_map, array &$scope_map ) {
    $project_ids = array();
    foreach ( $cluster['projects'] as $record ) {
        $project_ids[] = (int) $record['ID'];
    }

    $count = count( $project_ids );
    if ( 0 === $count ) {
        return;
    }

    $links_per_project = ( $count > 1 ) ? min( 5, $count - 1 ) : 0;

    foreach ( $project_ids as $index => $project_id ) {
        $links = array();

        if ( $links_per_project > 0 ) {
            for ( $offset = 1; $offset <= $links_per_project; $offset++ ) {
                $target_index = ( $index + $offset ) % $count;
                $links[]      = $project_ids[ $target_index ];
            }
        }

        $links_map[ $project_id ] = $links;
        $scope_map[ $project_id ] = array(
            'level'       => $cluster['level'],
            'location_id' => (int) $cluster['location_id'],
            'category_id' => (int) $cluster['category_id'],
        );
    }
}

/**
 * Trigger regeneration whenever a published project is saved.
 */
function jawda_regenerate_project_internal_links_on_save( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( 'projects' !== $post->post_type ) {
        return;
    }

    if ( 'publish' !== $post->post_status ) {
        return;
    }

    if ( ! $update ) {
        return;
    }

    jawda_regenerate_project_internal_links();
}
add_action( 'save_post_projects', 'jawda_regenerate_project_internal_links_on_save', 20, 3 );

/**
 * Recalculate links when a project enters or leaves the published state.
 */
function jawda_regenerate_project_internal_links_on_status_change( $new_status, $old_status, $post ) {
    if ( $new_status === $old_status ) {
        return;
    }

    if ( 'projects' !== $post->post_type ) {
        return;
    }

    if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
        return;
    }

    if ( 'publish' === $old_status && 'publish' !== $new_status ) {
        delete_post_meta( $post->ID, JAWDA_PROJECT_LINKS_META_KEY );
        delete_post_meta( $post->ID, JAWDA_PROJECT_LINKS_SCOPE_META_KEY );
    }

    jawda_regenerate_project_internal_links();
}
add_action( 'transition_post_status', 'jawda_regenerate_project_internal_links_on_status_change', 10, 3 );

/**
 * Ensure links stay in sync when a project is deleted or trashed.
 */
function jawda_regenerate_project_internal_links_on_delete( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || 'projects' !== $post->post_type ) {
        return;
    }

    delete_post_meta( $post_id, JAWDA_PROJECT_LINKS_META_KEY );
    delete_post_meta( $post_id, JAWDA_PROJECT_LINKS_SCOPE_META_KEY );

    jawda_regenerate_project_internal_links();
}
add_action( 'trashed_post', 'jawda_regenerate_project_internal_links_on_delete' );
add_action( 'deleted_post', 'jawda_regenerate_project_internal_links_on_delete' );
add_action( 'before_delete_post', 'jawda_regenerate_project_internal_links_on_delete' );

if ( defined( 'WP_CLI' ) && WP_CLI && class_exists( 'WP_CLI' ) ) {
    WP_CLI::add_command(
        'jawda regenerate-project-links',
        function() {
            jawda_regenerate_project_internal_links();
            WP_CLI::success( 'Project internal links regenerated.' );
        }
    );
}
