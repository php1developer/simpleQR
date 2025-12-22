<?php
// Register QR Code CPT
function register_qr_code_cpt()
{
    $labels = array(
        'name'               => 'QR Codes',
        'singular_name'      => 'QR Code',
        'menu_name'          => 'QR Codes',
        'name_admin_bar'     => 'QR Code',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New QR Code',
        'edit_item'          => 'Edit QR Code',
        'new_item'           => 'New QR Code',
        'view_item'          => 'View QR Code',
        'search_items'       => 'Search QR Codes',
        'not_found'          => 'No QR Codes found',
        'not_found_in_trash' => 'No QR Codes found in Trash',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'capability_type'    => 'post',
        'hierarchical'       => false,
        'supports'           => array('title', 'editor'),
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-grid-view',
        'has_archive'        => false,
        'show_in_rest'       => true, // For Gutenberg / REST API
    );

    register_post_type('qr_code', $args);

    $labels = array(
        'name'                  => 'Link Pages',
        'singular_name'         => 'Link Page',
        'menu_name'             => 'Link Pages',
        'name_admin_bar'        => 'Link Page',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Link Page',
        'edit_item'             => 'Edit Link Page',
        'new_item'              => 'New Link Page',
        'view_item'             => 'View Link Page',
        'search_items'          => 'Search Link Pages',
        'not_found'             => 'No link pages found',
        'not_found_in_trash'    => 'No link pages found in Trash',
    );

    $args = array(
        'label'                 => 'Link Pages',
        'labels'                => $labels,
        'supports'           => array('title', 'editor','author'),
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_icon'             => 'dashicons-admin-links',
        'has_archive'           => false,
        'rewrite'               => array('slug' => 'link-page',    'with_front' => false),
        'capability_type'       => 'post',
    );

    register_post_type('link_page', $args);
}
add_action('init', 'register_qr_code_cpt');

// Add custom column
function qr_code_columns($columns)
{
    $columns['qr_scan_count'] = __('Scans', 'textdomain');
    return $columns;
}
add_filter('manage_qr_code_posts_columns', 'qr_code_columns');

// Populate custom column
function qr_code_custom_column($column, $post_id)
{
    if ($column === 'qr_scan_count') {
        $count = get_post_meta($post_id, 'qr_scan_count', true);
        echo intval($count);
    }
}
add_action('manage_qr_code_posts_custom_column', 'qr_code_custom_column', 10, 2);
// Make columns sortable
add_filter('manage_edit-qr_code_sortable_columns', function ($columns) {
    $columns['qr_scan_count'] = 'qr_scan_count';
    return $columns;
});

// Adjust query to allow sorting by qr_scan_count
add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;

    if ($query->get('post_type') === 'qr_code' && $query->get('orderby') === 'qr_scan_count') {
        $query->set('meta_key', 'qr_scan_count');
        $query->set('orderby', 'meta_value_num');
    }
});

// Add meta boxes for QR Code details
function qr_code_meta_boxes()
{
    add_meta_box(
        'qr_code_meta',
        'QR Code Details',
        'qr_code_meta_box_callback',
        'qr_code',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'qr_code_meta_boxes');


// --- Add Generate QR button + handler and helper to save downloaded image to Media Library ---

/**
 * Add generate QR button to the existing qr_code meta box
 * (This function is the meta box callback — replace or merge with your existing callback)
 */
function qr_code_meta_box_callback($post)
{
    wp_nonce_field('save_qr_code_meta', 'qr_code_meta_nonce');

    $destination_url  = get_post_meta($post->ID, 'destination_url', true);
    $qr_scan_count    = get_post_meta($post->ID, 'qr_scan_count', true);
    $qr_scan_log      = get_post_meta($post->ID, 'qr_scan_log', true);
    $qr_image         = get_post_meta($post->ID, 'qr_image', true);

    $qr_data          = get_post_meta($post->ID, 'qr_data', true);
    $qr_user_id       = get_post_meta($post->ID, 'qr_user_id', true);
    $qr_user_name     = get_post_meta($post->ID, 'qr_user_name', true);
    $qr_type          = get_post_meta($post->ID, 'qr_type', true);
    $qr_value         = get_post_meta($post->ID, 'qr_value', true);

    if (!is_array($qr_scan_log)) $qr_scan_log = [];

    // Device breakdown
    $device_count = ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0];
    foreach ($qr_scan_log as $log) {
        if (!empty($log['device']) && isset($device_count[$log['device']])) {
            $device_count[$log['device']]++;
        }
    }
?>

    <div class="qr-meta-box-wrapper" style="max-width:800px; font-family:Arial,sans-serif;">


        <!-- User Info -->
        <h4 style="border-bottom:1px solid #ddd; padding-bottom:4px; margin-top:15px;">User Info</h4>
        <table style="width:100%; border-spacing:8px 4px;">
            <tr>
                <td style="width:30%;"><label><strong>User ID:</strong></label></td>
                <td><input type="number" value="<?php echo esc_attr($qr_user_id); ?>" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:4px;" readonly></td>
            </tr>
            <tr>
                <td><label><strong>User Name:</strong></label></td>
                <td><input type="text" value="<?php echo esc_attr($qr_user_name); ?>" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:4px;" readonly></td>
            </tr>
        </table>

        <!-- QR Details -->
        <h4 style="border-bottom:1px solid #ddd; padding-bottom:4px; margin-top:15px;">QR Details</h4>
        <table style="width:100%; border-spacing:8px 4px;">
            <tr>
                <td style="width:30%;"><label><strong>QR Type:</strong></label></td>
                <td><input type="text" value="<?php echo esc_attr($qr_type); ?>" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:4px;" readonly></td>
            </tr>
            <tr>
                <td><label for="qr_data"><strong>QR Value:</strong></label><br></td>
                <td><textarea id="qr_data" readonly name="qr_data" rows="3" style="width:100%; padding:6px; border:1px solid #ddd; border-radius:4px;"><?php echo esc_textarea($qr_data); ?></textarea>
                </td>
            </tr>
        </table>

        <!-- QR Image -->
        <h4 style="border-bottom:1px solid #ddd; padding-bottom:4px; margin-top:15px;">QR Code Image</h4>
        <input type="hidden" name="qr_image" id="qr_image" value="<?php echo esc_attr($qr_image); ?>">
        <div id="qr_image_preview" style="margin-top:10px;">
            <?php if ($qr_image): ?>
                <img src="<?php echo esc_url($qr_image); ?>" style="max-width:200px; border:1px solid #ddd; padding:4px; border-radius:4px;">
            <?php endif; ?>
        </div>

        <!-- Scan Stats -->
        <h4 style="border-bottom:1px solid #ddd; padding-bottom:4px; margin-top:20px;">Scan Stats</h4>
        <p><strong>Total Scans:</strong> <span id="scanCountDisplay"><?php echo (int)$qr_scan_count; ?></span></p>

        <!-- Scan Logs Table -->
        <h4 style="border-bottom:1px solid #ddd; padding-bottom:4px; margin-top:15px;">Scan Logs</h4>
        <?php if (!empty($qr_scan_log)): ?>
            <table style="width:100%; border-collapse:collapse; border:1px solid #ddd; margin-top:6px;">
                <thead style="background:#f9f9f9;">
                    <tr>
                        <th style="padding:8px; border:1px solid #ddd;">Time</th>
                        <th style="padding:8px; border:1px solid #ddd;">IP Address</th>
                        <th style="padding:8px; border:1px solid #ddd;">Device</th>
                        <th style="padding:8px; border:1px solid #ddd;">Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qr_scan_log as $log): ?>
                        <tr>
                            <td style="padding:6px; border:1px solid #ddd;"><?php echo esc_html($log['time'] ?? ''); ?></td>
                            <td style="padding:6px; border:1px solid #ddd;"><?php echo esc_html($log['ip'] ?? ''); ?></td>
                            <td style="padding:6px; border:1px solid #ddd;"><?php echo esc_html($log['device'] ?? ''); ?></td>
                            <td style="padding:6px; border:1px solid #ddd;"><?php echo esc_html($log['location'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No scans yet.</p>
        <?php endif; ?>

    </div>

<?php }


// Add submenu under QR Code post type
function qr_code_reports_menu()
{
    add_submenu_page(
        'edit.php?post_type=qr_code', // parent: QR Code CPT
        'QR Code Reports',
        'Reports',
        'manage_options',
        'qr-code-reports',
        'qr_code_reports_page_callback'
    );
}
add_action('admin_menu', 'qr_code_reports_menu');

function qr_code_reports_page_callback()
{
    // Get all QR Codes
    $args = [
        'post_type'      => 'qr_code',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ];
    $qr_posts = get_posts($args);

?>
    <div class="wrap">
        <h1>QR Code Reports</h1>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>QR Code</th>
                    <th>User</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Scan Count</th>
                    <th>Device Breakdown</th>
                    <th>Last 5 Scans</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($qr_posts as $post):
                    $qr_scan_count  = get_post_meta($post->ID, 'qr_scan_count', true) ?: 0;
                    $qr_scan_log   = get_post_meta($post->ID, 'qr_scan_log', true);
                    $qr_image    = get_post_meta($post->ID, 'qr_image', true);
                    $qr_user     = get_post_meta($post->ID, 'qr_user_name', true);
                    $qr_type     = get_post_meta($post->ID, 'qr_type', true);
                    $qr_value    = get_post_meta($post->ID, 'qr_value', true);

                    if (!is_array($qr_scan_log)) $qr_scan_log = [];

                    // Device breakdown
                    $device_count = ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0];
                    foreach ($qr_scan_log as $log) {
                        if (!empty($log['device']) && isset($device_count[$log['device']])) {
                            $device_count[$log['device']]++;
                        }
                    }
                ?>
                    <tr>
                        <td>
                            <?php if ($qr_image): ?>
                                <img src="<?php echo esc_url($qr_image); ?>" style="max-width:80px;"><br>
                            <?php endif; ?>
                            <a href="<?php echo get_edit_post_link($post->ID); ?>">
                                <?php echo esc_html(get_the_title($post)); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($qr_user ?: '-'); ?></td>
                        <td><?php echo esc_html($qr_type ?: '-'); ?></td>
                        <td><?php echo esc_html($qr_value ?: '-'); ?></td>
                        <td><strong><?php echo intval($qr_scan_count); ?></strong></td>
                        <td>
                            D: <?php echo intval($device_count['Desktop']); ?> |
                            M: <?php echo intval($device_count['Mobile']); ?> |
                            T: <?php echo intval($device_count['Tablet']); ?>
                        </td>
                        <td>
                            <?php
                            $recent_logs = array_slice(array_reverse($qr_scan_log), 0, 5);
                            if ($recent_logs) {
                                echo '<ul style="margin:0;">';
                                foreach ($recent_logs as $log) {
                                    echo '<li>' . esc_html($log['time'] ?? '') . ' - ' .
                                        esc_html($log['ip'] ?? '') . ' (' .
                                        esc_html($log['device'] ?? '') . ')</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo 'No scans';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php
}

// /**
//  * AJAX handler: generate QR image, save to Media Library, and return URL
//  */
// add_action('wp_ajax_generate_qr_image', 'ajax_generate_qr_image');
// function ajax_generate_qr_image()
// {
//     // security
//     if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'generate_qr_image_nonce')) {
//         wp_send_json_error('Invalid nonce', 403);
//     }
//     if (empty($_POST['post_id'])) {
//         wp_send_json_error('Missing post_id', 400);
//     }

//     $post_id = intval($_POST['post_id']);
//     if (!$post_id || !current_user_can('edit_post', $post_id)) {
//         wp_send_json_error('Permission denied', 403);
//     }

//     // Build the scan URL that the QR should point to
//     $scan_url = esc_url_raw(add_query_arg('qr_id', $post_id, home_url('/')));
//     // Use API to generate QR (QRServer)
//     $size = '400x400'; // you can change size
//     $api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . rawurlencode($size) . '&data=' . rawurlencode($scan_url);

//     // Fetch image from API
//     $resp = wp_remote_get($api_url, array('timeout' => 20));
//     if (is_wp_error($resp)) {
//         wp_send_json_error('Failed to fetch QR image: ' . $resp->get_error_message());
//     }
//     $code = wp_remote_retrieve_response_code($resp);
//     if ($code !== 200) {
//         wp_send_json_error('QR API returned HTTP ' . $code);
//     }
//     $body = wp_remote_retrieve_body($resp);
//     if (empty($body)) {
//         wp_send_json_error('Empty image body');
//     }

//     // Prepare filename and upload to WP uploads
//     $uploads = wp_get_upload_dir();
//     $filename = 'qr-' . $post_id . '-' . time() . '.png';
//     $file_path = trailingslashit($uploads['path']) . $filename;
//     $file_url  = trailingslashit($uploads['url']) . $filename;

//     $saved = file_put_contents($file_path, $body);
//     if (!$saved) {
//         wp_send_json_error('Failed to save image to server');
//     }

//     // Let WP create attachment and metadata
//     $filetype = wp_check_filetype($filename, null);
//     $attachment = array(
//         'guid'           => $file_url,
//         'post_mime_type' => $filetype['type'],
//         'post_title'     => sanitize_file_name($filename),
//         'post_content'   => '',
//         'post_status'    => 'inherit'
//     );

//     $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
//     if (is_wp_error($attach_id) || !$attach_id) {
//         // clean up file
//         @unlink($file_path);
//         wp_send_json_error('Failed to create attachment');
//     }

//     require_once(ABSPATH . 'wp-admin/includes/image.php');
//     $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
//     wp_update_attachment_metadata($attach_id, $attach_data);

//     // Save URL (or attachment ID) to post meta
//     $attachment_url = wp_get_attachment_url($attach_id);
//     update_post_meta($post_id, 'qr_image', esc_url_raw($attachment_url));
//     // Also optionally store attachment ID
//     update_post_meta($post_id, 'qr_image_id', intval($attach_id));

//     // Return URL
//     wp_send_json_success(array('url' => $attachment_url));
// }

// /**
//  * Optional: auto-generate QR on post save when qr_image is empty
//  * (If you prefer auto generation without pressing button, enable this.)
//  */
// function qr_auto_generate_on_save($post_id)
// {
//     if (wp_is_post_revision($post_id)) return;
//     if (get_post_type($post_id) !== 'qr_code') return;
//     if (get_post_meta($post_id, 'qr_image', true)) return; // already has image

//     // We will call the generator function programmatically (avoid AJAX)
//     $scan_url = esc_url_raw(add_query_arg('qr_id', $post_id, home_url('/')));
//     $size = '400x400';
//     $api_url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . rawurlencode($size) . '&data=' . rawurlencode($scan_url);
//     $resp = wp_remote_get($api_url, array('timeout' => 20));
//     if (is_wp_error($resp)) return;
//     if (wp_remote_retrieve_response_code($resp) !== 200) return;
//     $body = wp_remote_retrieve_body($resp);
//     if (empty($body)) return;

//     $uploads = wp_get_upload_dir();
//     $filename = 'qr-' . $post_id . '-' . time() . '.png';
//     $file_path = trailingslashit($uploads['path']) . $filename;
//     $file_url  = trailingslashit($uploads['url']) . $filename;

//     if (! file_put_contents($file_path, $body)) return;

//     $filetype = wp_check_filetype($filename, null);
//     $attachment = array(
//         'guid'           => $file_url,
//         'post_mime_type' => $filetype['type'],
//         'post_title'     => sanitize_file_name($filename),
//         'post_content'   => '',
//         'post_status'    => 'inherit'
//     );

//     $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
//     if (is_wp_error($attach_id)) return;

//     require_once(ABSPATH . 'wp-admin/includes/image.php');
//     $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
//     wp_update_attachment_metadata($attach_id, $attach_data);

//     update_post_meta($post_id, 'qr_image', esc_url_raw(wp_get_attachment_url($attach_id)));
//     update_post_meta($post_id, 'qr_image_id', intval($attach_id));
// }
// Uncomment if you want auto-generation on save
// Handle QR code redirect and logging
// function handle_qr_redirect()
// {
//     if (empty($_GET['qr_id'])) return;

//     $qr_id = intval($_GET['qr_id']);
//     if (!$qr_id) return;

//     // Destination URL
//     $url = get_post_meta($qr_id, 'destination_url', true);
//     if (!$url) return;

//     // Increment scan count
//     $qr_scan_count = (int) get_post_meta($qr_id, 'qr_scan_count', true);
//     $qr_scan_count++;
//     update_post_meta($qr_id, 'qr_scan_count', $qr_scan_count);

//     // Detect device (Desktop, Mobile, Tablet)
//     $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
//     if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
//         $device_type = 'Tablet';
//     } elseif (wp_is_mobile()) {
//         $device_type = 'Mobile';
//     } else {
//         $device_type = 'Desktop';
//     }

//     // Save log
//     $logs = get_post_meta($qr_id, 'qr_scan_log', true);
//     if (!is_array($logs)) $logs = [];
//     $logs[] = [
//         'time'   => current_time('mysql'),
//         'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
//         'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? '',
//         'device' => $device_type,
//     ];
//     update_post_meta($qr_id, 'qr_scan_log', $logs);

//     // Prevent caching
//     header("Cache-Control: no-cache, must-revalidate");
//     header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

//     wp_redirect($url);
//     exit;
// }
// add_action('template_redirect', 'handle_qr_redirect');
