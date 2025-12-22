<?php

/**
 * Recommended way to include parent theme styles.
 * (Please see http://codex.wordpress.org/Child_Themes#How_to_Create_a_Child_Theme)
 *
 */

add_action('wp_enqueue_scripts', 'hello_elementor_child_style');
function hello_elementor_child_style()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('font-awesome-style',  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css');
    wp_enqueue_style('bootstrap-style',  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));
}

function enqueue_frontend_assets()
{
    wp_enqueue_script('my-qr-js', get_stylesheet_directory_uri() . '/js/script.js', array('jquery'), '1.0', true);
    // wp_enqueue_script('font-awsome-js', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array('jquery'), '1.0', true);
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js', array('jquery'), '1.0', true);
    wp_localize_script('my-qr-js', 'qrAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('qr_pdf_nonce'),
        'user_id'  => get_current_user_id(),
        'user_name' => wp_get_current_user()->display_name,

    ));

    wp_enqueue_media();

 
}
add_action('wp_enqueue_scripts', 'enqueue_frontend_assets');
require_once get_stylesheet_directory() . '/includes/custom-functions.php';
require_once get_stylesheet_directory() . '/includes/qr-dashboard.php';
require_once get_stylesheet_directory() . '/includes/custom-posttypes.php';
require_once get_stylesheet_directory() . '/includes/create_qr_dashboard.php';
require_once get_stylesheet_directory() . '/includes/listing_qr_code.php';
require_once get_stylesheet_directory() . '/includes/qr_analytics.php';

require_once get_stylesheet_directory() . '/includes/create_link_pages.php';
require_once get_stylesheet_directory() . '/includes/link_analytics.php';
require_once get_stylesheet_directory() . '/includes/class-linkpage-analytics.php';


/*
// QR Analytics Admin Menu
add_action('admin_menu', function () {
    add_menu_page('QR Analytics', 'QR Analytics', 'manage_options', 'qr-analytics', 'qr_admin_page');
});

function qr_admin_page()
{
    global $wpdb;
    $redirects = $wpdb->prefix . 'qr_redirects';
    $scans = $wpdb->prefix . 'qr_scans';

    $rows = $wpdb->get_results("
        SELECT r.shortcode, r.destination, COUNT(s.id) as scans
        FROM $redirects r
        LEFT JOIN $scans s ON r.shortcode = s.shortcode
        GROUP BY r.id
        ORDER BY r.created_at DESC
    ");
    
    <div class="wrap">
        <h1>QR Code Analytics</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Destination URL</th>
                    <th>Scans</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
<tr>
    <td><code><?php echo esc_html($row->shortcode); ?></code></td>
    <td><a href="<?php echo esc_url($row->destination); ?>"
            target="_blank"><?php echo esc_html($row->destination); ?></a></td>
    <td><?php echo intval($row->scans); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php
} */


/* Finally we add some content in the tab */


/**
 * Add the Member Menu navigation to the Member Links section on the Account Page.
 *
 * title: Add Member Menu to Account Page Member Links
 * layout: snippet
 * collection: frontend-pages
 * category: account-page
 * link: https://www.paidmembershipspro.com/making-powerful-member-dashboard-membership-account-page/
 *
 * You can add this recipe to your site by creating a custom plugin
 * or using the Code Snippets plugin available for free in the WordPress repository.
 * Read this companion article for step-by-step directions on either method.
 * https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
 */

// function my_pmpro_member_links_top() {
// 	echo '<li><h3>Account Menu</h3>';
// 	$pmpro_login_widget_menu_defaults = array(
// 		'theme_location'  => 'pmpro-login-widget',
// 		'container'       => 'nav',
// 		'container_id'    => 'pmpro-member-navigation',
// 		'container_class' => 'pmpro-member-navigation',
// 		'fallback_cb'	  => false,
// 		'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
// 	);
// 	wp_nav_menu( $pmpro_login_widget_menu_defaults );
// 	echo '</li>';

// 	// Add link to onboarding page.
// 	if ( pmpro_hasMembershipLevel( '1' ) ) {
// 		echo '<li><hr /><a href="/membership-checkout/?pmpro_level=5">Upgrade to Full Membership</a></li>';
// 	}
// }
// add_action( 'pmpro_member_links_top', 'my_pmpro_member_links_top' );
