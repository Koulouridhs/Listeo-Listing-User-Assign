<?php
/*
Plugin Name: Listeo Listing User Assign
Description: A plugin for bulk user creation from custom post type "listing" (with admin listing filter option & pagination).
Version: 1.0
Author: George Koulouridhs
Text Domain: my-listing-user-assign
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Security: Prevent direct access
}

/**
 * On plugin activation, create the "owner" role if it does not exist.
 */
register_activation_hook( __FILE__, 'mlua_add_owner_role' );
function mlua_add_owner_role() {
    add_role(
        'owner',
        __( 'Owner', 'my-listing-user-assign' ),
        array(
            'read' => true
            // Add any additional capabilities if needed (e.g., 'edit_posts' => true).
        )
    );
}

/**
 * Add the Admin Menu
 */
add_action( 'admin_menu', 'mlua_register_admin_menu' );
function mlua_register_admin_menu() {
    add_menu_page(
        __( 'Listing User Assign', 'my-listing-user-assign' ), // Page Title
        __( 'Listing User Assign', 'my-listing-user-assign' ), // Menu Title
        'manage_options',                                      // Required capability
        'mlua-listing-user-assign',                            // Page slug
        'mlua_admin_page_callback',                            // Callback content
        'dashicons-admin-users',                               // Icon
        25                                                     // Position in the menu
    );
}

/**
 * Admin Page Callback (displays the page content)
 */
function mlua_admin_page_callback() {
    // Capability check
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Bulk create users if form is submitted
    if ( isset( $_POST['mlua_bulk_create_users'] ) && isset( $_POST['mlua_listing_ids'] ) ) {
        mlua_handle_bulk_create_users( $_POST['mlua_listing_ids'] );
    }

    // Check for single user creation request
    if ( isset( $_GET['mlua_single_create_user'] ) && isset( $_GET['listing_id'] ) ) {
        if ( wp_verify_nonce( $_GET['_wpnonce'], 'mlua_create_single_user' ) ) {
            mlua_handle_bulk_create_users( array( intval( $_GET['listing_id'] ) ) );
        }
    }

    // Get filter parameter for showing admin-owned listings or not
    //  - mlua_filter_admin = '1' => Show all listings (including admin-owned)
    //  - mlua_filter_admin = '0' => Hide admin-owned listings
    $filter_admin = isset( $_GET['mlua_filter_admin'] ) ? sanitize_text_field( $_GET['mlua_filter_admin'] ) : '0';

    // Get admin user IDs (we will exclude them if $filter_admin == '0')
    $admin_user_ids = get_users( array(
        'role'   => 'administrator',
        'fields' => 'ID'
    ) );

    // Pagination: current page
    $paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;

    // Build WP_Query args for "listing"
    $args = array(
        'post_type'      => 'listing',
        'posts_per_page' => 30,
        'paged'          => $paged,
    );

    // If $filter_admin == '0', exclude admin authors
    if ( $filter_admin === '0' ) {
        $args['author__not_in'] = $admin_user_ids;
    }

    // Run the query
    $listings_query = new WP_Query( $args );
    ?>

    <div class="wrap">
        <h1><?php esc_html_e( 'Listing User Assign', 'my-listing-user-assign' ); ?></h1>

        <!-- ADMIN LISTINGS VISIBILITY FILTER FORM -->
        <form method="get" style="margin-bottom: 1em;">
            <!-- Important: remain on the correct page (admin.php?page=...) -->
            <input type="hidden" name="page" value="mlua-listing-user-assign" />

            <label style="margin-right:20px;">
                <input type="radio" name="mlua_filter_admin" value="0" <?php checked( $filter_admin, '0' ); ?> />
                <?php esc_html_e( 'Hide admin-owned listings', 'my-listing-user-assign' ); ?>
            </label>
            <label style="margin-right:20px;">
                <input type="radio" name="mlua_filter_admin" value="1" <?php checked( $filter_admin, '1' ); ?> />
                <?php esc_html_e( 'Show all listings', 'my-listing-user-assign' ); ?>
            </label>

            <input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'my-listing-user-assign' ); ?>" />
        </form>

        <?php if ( $listings_query->have_posts() ) : ?>
            <!-- BULK CREATE FORM -->
            <form method="post">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td style="width: 5%;">
                                <input type="checkbox" id="mlua-check-all" />
                            </td>
                            <th style="width: 20%;">
                                <?php esc_html_e( 'Current Owner (Post Author)', 'my-listing-user-assign' ); ?>
                            </th>
                            <th style="width: 25%;">
                                <?php esc_html_e( 'Listing Title', 'my-listing-user-assign' ); ?>
                            </th>
                            <th style="width: 25%;">
                                <?php esc_html_e( 'Email ( _email )', 'my-listing-user-assign' ); ?>
                            </th>
                            <th style="width: 25%;">
                                <?php esc_html_e( 'Action', 'my-listing-user-assign' ); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ( $listings_query->have_posts() ) :
                            $listings_query->the_post();

                            $listing_id    = get_the_ID();
                            $listing_title = get_the_title();
                            $owner_id      = get_post_field( 'post_author', $listing_id );
                            $owner_user    = get_userdata( $owner_id );
                            $owner_name    = $owner_user ? $owner_user->user_login : __( 'No user', 'my-listing-user-assign' );
                            $listing_email = get_post_meta( $listing_id, '_email', true );

                            // Edit post link
                            $edit_link = get_edit_post_link( $listing_id );
                            // Front-end view link
                            $view_link = get_permalink( $listing_id );
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="mlua_listing_ids[]" value="<?php echo esc_attr( $listing_id ); ?>" />
                                </td>
                                <td>
                                    <?php echo esc_html( $owner_name ); ?>
                                </td>
                                <td>
                                    <!-- Title: link to edit the post in admin -->
                                    <strong>
                                        <a href="<?php echo esc_url( $edit_link ); ?>">
                                            <?php echo esc_html( $listing_title ); ?>
                                        </a>
                                    </strong>
                                    <div>
                                        <!-- Link that opens the listing in a new tab -->
                                        <a href="<?php echo esc_url( $view_link ); ?>" target="_blank">
                                            <?php esc_html_e( 'View listing', 'my-listing-user-assign' ); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <?php echo esc_html( $listing_email ); ?>
                                </td>
                                <td>
                                    <!-- Single create link -->
                                    <a href="<?php
                                        echo esc_url( add_query_arg( array(
                                            'mlua_single_create_user' => 1,
                                            'listing_id'              => $listing_id,
                                            '_wpnonce'                => wp_create_nonce( 'mlua_create_single_user' ),
                                            // Keep the current filter to return properly
                                            'mlua_filter_admin'       => $filter_admin,
                                        ), admin_url( 'admin.php?page=mlua-listing-user-assign' ) ) );
                                    ?>" class="button">
                                       <?php esc_html_e( 'Create User & Assign', 'my-listing-user-assign' ); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        endwhile;
                        ?>
                    </tbody>
                </table>

                <p style="margin-top:20px;">
                    <input type="submit" name="mlua_bulk_create_users" class="button button-primary" value="<?php esc_attr_e( 'Bulk Create Users', 'my-listing-user-assign' ); ?>">
                </p>
            </form>

            <?php
            // Pagination
            $total_pages = $listings_query->max_num_pages;
            if ( $total_pages > 1 ) {
                $current_page = max( 1, $paged );

                // Preserve mlua_filter_admin so filter won't be lost
                echo paginate_links( array(
                    'base'      => add_query_arg( array( 'paged' => '%#%', 'mlua_filter_admin' => $filter_admin ) ),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => __( '« Previous', 'my-listing-user-assign' ),
                    'next_text' => __( 'Next »', 'my-listing-user-assign' ),
                ) );
            }

            wp_reset_postdata();
            ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No Listings found.', 'my-listing-user-assign' ); ?></p>
        <?php endif; ?>
    </div>

    <script>
    // "Select All" checkbox logic
    document.addEventListener('DOMContentLoaded', function() {
        const checkAll = document.getElementById('mlua-check-all');
        if (checkAll) {
            checkAll.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('input[name="mlua_listing_ids[]"]');
                for (const cb of checkboxes) {
                    cb.checked = checkAll.checked;
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Function that creates users or finds them, then assigns them to listings
 */
function mlua_handle_bulk_create_users( $listing_ids ) {
    // We'll keep the admin filter to properly redirect afterwards
    $filter_admin = isset( $_GET['mlua_filter_admin'] ) ? sanitize_text_field( $_GET['mlua_filter_admin'] ) : '0';

    foreach ( $listing_ids as $listing_id ) {
        $listing_id = intval( $listing_id );
        $post = get_post( $listing_id );

        if ( ! $post || $post->post_type !== 'listing' ) {
            continue; // Invalid ID or non-listing
        }

        $listing_title = $post->post_title;
        $listing_email = get_post_meta( $listing_id, '_email', true );

        // If no email, skip user creation
        if ( empty( $listing_email ) ) {
            continue;
        }

        // Check if user already exists with this email
        $existing_user = get_user_by( 'email', $listing_email );
        if ( ! $existing_user ) {
            // Create a new user (role = owner)
            // Do not send notification email
            $random_password = wp_generate_password( 12 );
            $user_id = wp_insert_user( array(
                'user_login'            => sanitize_user( $listing_title, true ),
                'user_email'            => sanitize_email( $listing_email ),
                'user_pass'             => $random_password,
                'display_name'          => $listing_title,
                'role'                  => 'owner',
                'send_user_notification' => false
            ) );

            if ( is_wp_error( $user_id ) ) {
                // If there's an error, proceed to the next
                continue;
            }

            // Assign listing to the new user
            wp_update_post( array(
                'ID'          => $listing_id,
                'post_author' => $user_id
            ) );

        } else {
            // If a user with this email already exists, just assign the post
            wp_update_post( array(
                'ID'          => $listing_id,
                'post_author' => $existing_user->ID
            ) );
        }
    }

    // Prevent form re-submission (redirect back to the list, preserving the filter)
    wp_safe_redirect( admin_url( 'admin.php?page=mlua-listing-user-assign&mlua_filter_admin=' . $filter_admin . '&mlua_done=1' ) );
    exit;
}
