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
    $stored = get_post_meta( $project_id, JAWDA_PROJECT_LINKS_META_KEY, true );

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
    global $wpdb;

    $scope = jawda_get_project_internal_links_scope( $project_id );
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
