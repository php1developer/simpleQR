<?php

function enqueue_linkpage_frontend_assets() {

    wp_enqueue_script('link-pages-js', get_stylesheet_directory_uri() . '/js/link-pages.js', array('jquery'), '1.0', true);
    wp_localize_script('link-pages-js', 'LinkPages', array(
        'default_image' => get_stylesheet_directory_uri() . '/assets/linkpages/images/empty-thumbnail.png',
    ));
    wp_enqueue_script('sortable-link-pages-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js', array(), '', true);

    wp_enqueue_style(
        'link-pages-css',
        get_stylesheet_directory_uri() . '/assets/linkpages/css/link-pages.css',
        array(),
        '1.0'
    );
}
add_action('wp_enqueue_scripts', 'enqueue_linkpage_frontend_assets');

function ajax_check_slug_exists()
{
    $slug      = sanitize_title($_POST['slug']);
    $post_type = sanitize_text_field($_POST['post_type']);

    if (empty($slug)) {
        wp_die();
    }

    // Check if slug exists
    global $wpdb;
    $exists = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND post_type = %s LIMIT 1",
            $slug,
            $post_type
        )
    );

    if ($exists) {
        echo "<span style='color:red;'>Already Taken</span>";
    }

    wp_die();
}

add_action('wp_ajax_check_slug_exists', 'ajax_check_slug_exists');

/**
 * AJAX Handler for saving link pages
 */
function ajax_save_link_page()
{
    // Check user permissions
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to create a link page']);
    }

    try {
        // Get the JSON data
        $linkpage_data_json = isset($_POST['linkpage_data']) ? wp_unslash($_POST['linkpage_data']) : '';

        if (empty($linkpage_data_json)) {
            wp_send_json_error(['message' => 'No data provided']);
        }

        $linkpage_data = json_decode($linkpage_data_json, true);

        if (!$linkpage_data || !isset($linkpage_data['common']) || !isset($linkpage_data['widgets'])) {
            wp_send_json_error(['message' => 'Invalid data format']);
        }

        $common = $linkpage_data['common'];
        $widgets = $linkpage_data['widgets'];

        // Check if this is an update (edit mode)
        $post_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
        $is_update = $post_id > 0;

        // ========================================
        // STEP 1: Handle File Uploads
        // ========================================

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $uploaded_files = [];

        // Handle common files (profile, layout, background)
        $common_file_keys = ['lp_profile_image', 'lp_layout_image', 'lp_background_image', 'lp_background_video'];

        foreach ($common_file_keys as $file_key) {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
                $upload_overrides = ['test_form' => false];
                $uploaded_file = wp_handle_upload($_FILES[$file_key], $upload_overrides);

                if (isset($uploaded_file['url']) && !isset($uploaded_file['error'])) {
                    $uploaded_files[$file_key] = esc_url_raw($uploaded_file['url']);
                    $common[$file_key . '_url'] = $uploaded_files[$file_key];
                } else {
                    error_log("File upload failed for {$file_key}: " . ($uploaded_file['error'] ?? 'Unknown error'));
                }
            }
        }

        // Handle widget files
        if (isset($_FILES['widget_files']) && is_array($_FILES['widget_files'])) {
            $widget_files = $_FILES['widget_files'];

            // Process each widget's files
            if (isset($widget_files['name']) && is_array($widget_files['name'])) {
                foreach ($widget_files['name'] as $widget_id => $file_keys) {
                    if (!is_array($file_keys)) continue;

                    // Find the widget in our widgets array
                    $widget_index = null;
                    foreach ($widgets as $index => $widget) {
                        if (isset($widget['id']) && $widget['id'] === $widget_id) {
                            $widget_index = $index;
                            break;
                        }
                    }

                    if ($widget_index === null) continue;

                    // Process each file key for this widget
                    foreach ($file_keys as $file_key => $file_data) {
                        // Handle single file
                        if (!is_array($file_data)) {
                            $upload_file = [
                                'name'     => $widget_files['name'][$widget_id][$file_key],
                                'type'     => $widget_files['type'][$widget_id][$file_key],
                                'tmp_name' => $widget_files['tmp_name'][$widget_id][$file_key],
                                'error'    => $widget_files['error'][$widget_id][$file_key],
                                'size'     => $widget_files['size'][$widget_id][$file_key],
                            ];

                            if ($upload_file['error'] === UPLOAD_ERR_OK) {
                                $upload_overrides = ['test_form' => false];
                                $uploaded_file = wp_handle_upload($upload_file, $upload_overrides);

                                if (isset($uploaded_file['url']) && !isset($uploaded_file['error'])) {
                                    $widgets[$widget_index]['fields'][$file_key . '_url'] = esc_url_raw($uploaded_file['url']);
                                }
                            }
                        } else {
                            // Handle multiple files (array)
                            $uploaded_urls = [];

                            foreach ($file_data as $file_index => $file_name) {
                                if (empty($file_name)) continue;

                                $upload_file = [
                                    'name'     => $widget_files['name'][$widget_id][$file_key][$file_index],
                                    'type'     => $widget_files['type'][$widget_id][$file_key][$file_index],
                                    'tmp_name' => $widget_files['tmp_name'][$widget_id][$file_key][$file_index],
                                    'error'    => $widget_files['error'][$widget_id][$file_key][$file_index],
                                    'size'     => $widget_files['size'][$widget_id][$file_key][$file_index],
                                ];

                                if ($upload_file['error'] === UPLOAD_ERR_OK) {
                                    $upload_overrides = ['test_form' => false];
                                    $uploaded_file = wp_handle_upload($upload_file, $upload_overrides);

                                    if (isset($uploaded_file['url']) && !isset($uploaded_file['error'])) {
                                        $uploaded_urls[] = esc_url_raw($uploaded_file['url']);
                                    }
                                }
                            }

                            if (!empty($uploaded_urls)) {
                                $widgets[$widget_index]['fields'][$file_key . '_urls'] = $uploaded_urls;
                            }
                        }
                    }
                }
            }
        }

        // ========================================
        // STEP 2: Sanitize Data
        // ========================================

        // Sanitize common fields
        $sanitized_common = [];
        foreach ($common as $key => $value) {
            $safe_key = sanitize_text_field($key);

            if (is_array($value)) {
                $sanitized_common[$safe_key] = array_map('sanitize_text_field', $value);
            } else {
                if (strlen($value) > 200) {
                    $sanitized_common[$safe_key] = sanitize_textarea_field($value);
                } else {
                    $sanitized_common[$safe_key] = sanitize_text_field($value);
                }
            }
        }

        // Sanitize widgets
        $sanitize_field = function ($value) use (&$sanitize_field) {
            if (is_array($value)) {
                $clean = [];
                foreach ($value as $k => $v) {
                    $clean[sanitize_text_field($k)] = $sanitize_field($v);
                }
                return $clean;
            }

            if (is_string($value)) {
                // Preserve JSON-like strings by decoding first
                $maybe_json = json_decode($value, true);
                if (is_array($maybe_json)) {
                    return $sanitize_field($maybe_json);
                }

                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return esc_url_raw($value);
                }

                if (strlen($value) > 200) {
                    return sanitize_textarea_field($value);
                }

                return sanitize_text_field($value);
            }

            return $value;
        };

        $sanitized_widgets = [];
        foreach ($widgets as $widget) {
            $sanitized_widget = [
                'id'    => isset($widget['id']) ? sanitize_text_field($widget['id']) : '',
                'type'  => isset($widget['type']) ? sanitize_text_field($widget['type']) : '',
                'order' => isset($widget['order']) ? intval($widget['order']) : 0,
                'fields' => []
            ];

            if (isset($widget['fields']) && is_array($widget['fields'])) {
                foreach ($widget['fields'] as $field_key => $field_value) {
                    $safe_field_key = sanitize_text_field($field_key);
                    $sanitized_widget['fields'][$safe_field_key] = $sanitize_field($field_value);
                }
            }

            $sanitized_widgets[] = $sanitized_widget;
        }

        // ========================================
        // STEP 3: Build Final JSON
        // ========================================

        $final_data = [
            'common'  => $sanitized_common,
            'widgets' => $sanitized_widgets
        ];

        $json_data = wp_json_encode($final_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // ========================================
        // STEP 4: Handle Custom Slug
        // ========================================

        $auto_generate = isset($sanitized_common['lp-auto-generate']) && $sanitized_common['lp-auto-generate'] === '1';
        $custom_slug = '';

        if (!$auto_generate && !empty($sanitized_common['linkpage-custom-slug-url'])) {
            $custom_slug = sanitize_title($sanitized_common['linkpage-custom-slug-url']);
        }

        // ========================================
        // STEP 5: Create or Update Post
        // ========================================

        $post_title = !empty($sanitized_common['lp-linkpage-name'])
            ? sanitize_text_field($sanitized_common['lp-linkpage-name'])
            : 'Link Page ' . date('Y-m-d H:i:s');

        $post_args = [
            'post_type'   => 'link_page',
            'post_title'  => $post_title,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ];

        // Add custom slug if provided
        if (!empty($custom_slug)) {
            $post_args['post_name'] = $custom_slug;
        }

        // If updating, add post ID
        if ($is_update) {
            $post_args['ID'] = $post_id;
            $result = wp_update_post($post_args);
        } else {
            $result = wp_insert_post($post_args);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to ' . ($is_update ? 'update' : 'create') . ' link page: ' . $result->get_error_message()]);
        }

        $post_id = $is_update ? $post_id : $result;

        // ========================================
        // STEP 6: Save Meta Data
        // ========================================

        update_post_meta($post_id, 'link_page_json_data', $json_data);

        // Save individual uploaded file URLs as meta for easy access
        foreach ($uploaded_files as $file_key => $file_url) {
            update_post_meta($post_id, $file_key, $file_url);
        }

        // Log success
        error_log("Link page " . ($is_update ? 'updated' : 'created') . " successfully. Post ID: {$post_id}");

        // ========================================
        // STEP 7: Send Success Response
        // ========================================

        $redirect_url = add_query_arg('linkpage_created', $post_id, get_permalink(get_the_ID()));

        wp_send_json_success([
            'message' => 'Link page ' . ($is_update ? 'updated' : 'created') . ' successfully',
            'post_id' => $post_id,
            'post_title' => $post_title,
            'redirect_url' => $redirect_url,
            'permalink' => get_permalink($post_id),
            'uploaded_files_count' => count($uploaded_files),
            'widgets_count' => count($sanitized_widgets),
            'is_update' => $is_update
        ]);
    } catch (Exception $e) {
        error_log("Link page creation error: " . $e->getMessage());
        wp_send_json_error(['message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

add_action('wp_ajax_save_link_page', 'ajax_save_link_page');

function ajax_load_link_page_data()
{

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Unauthorized']);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if ($post_id <= 0) {
        wp_send_json_error(['message' => 'Invalid Post ID']);
    }

    // Check if post exists and belongs to logged-in user
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'link_page') {
        wp_send_json_error(['message' => 'Link Page not found']);
    }

    if ((int)$post->post_author !== get_current_user_id()) {
        wp_send_json_error(['message' => 'Not allowed']);
    }

    // Fetch JSON saved in meta
    $json_data = get_post_meta($post_id, 'link_page_json_data', true);

    if (empty($json_data)) {
        wp_send_json_error(['message' => 'No saved data found']);
    }

    $decoded_data = json_decode($json_data, true);

    if (!$decoded_data) {
        wp_send_json_error(['message' => 'Failed to decode JSON']);
    }

    // Send success response
    wp_send_json_success([
        'post_id' => $post_id,
        'data'    => $decoded_data
    ]);
}
add_action('wp_ajax_load_link_page_data', 'ajax_load_link_page_data');

add_shortcode('linkpage_listing_data','linkpage_listing_data_func');
add_shortcode('create_linkpage_data','create_linkpage_data_func');

function linkpage_listing_data_func() {

    ob_start();
    $output = '';
    if (!is_user_logged_in()) {
        return $output;
    }
    $user_id = get_current_user_id();
    $args = [
        'post_type' => 'link_page',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'author' => $user_id,
        'orderby' => 'date',
        'order' => 'DESC',
    ];
    $linkpages_query = new WP_Query($args);

    if (!empty($linkpages_query)) {
        $linkpage_count = $linkpages_query->found_posts;
    } else {
        $linkpage_count = 0;
    }

    ?>

    <div class='link-pages-listing-container'>
        <div class='um-field' id='field1' >
            <div class='um-top-header'>
                <div class='um_col8'>
                    <h3 class='header-title'>Link Pages ( <?php echo esc_html($linkpage_count); ?> )</h3>
                    <p class='sub-description'>Create a mobile-friendly, link-in-bio page with multiple links.</p>
                </div>
                <div class='um_col4'>
                    <div class='um-btn-wraper'>
                        <a href="<?php echo home_url('membership-account/create_linkpage/') ?>"> <i class='fa-solid fa-plus'></i>Create Link Page </a>
                    </div>
                </div>
            </div>
            <div class='um_card-body'>
                <?php if (!$linkpages_query->have_posts()):
                ?>
                    <div class='um-contentbox' style='text-align: center; display: flex; flex-direction: column;'>
                        <div class='um_imgbox'>
                            <img src='<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/linkpage-empty-design.png'; ?>' alt='No Link Page Created'>
                        </div>
                        <div class='um-text-box'>
                            <span class='um_innertitle'>No Link Page Created</span>
                            <span class='um_description'>Link Page is a mobile-friendly link-in-bio page with multiple
                                links.</span>
                        </div>
                    </div>
                <?php else:
                    $curernt_page_url = get_permalink() . 'create_linkpage';
                ?>
                    <table id="linkpage-table" class="um-linkpage-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Created</th>
                                <th>Views</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            global $wpdb;
                            while ($linkpages_query->have_posts()):
                                $json_data = get_post_meta(get_the_ID(), 'link_page_json_data', true);
                                $link_page_json_data = json_decode($json_data, true);

                                $linkpages_query->the_post();
                                $post_id = get_the_ID();
                                $analytics_table = $wpdb->prefix . 'linkpage_analytics';
                                $views = $wpdb->get_var($wpdb->prepare(
                                    "SELECT SUM(views) FROM $analytics_table WHERE linkpage_id = %d",
                                    $post_id
                                ));
                                
                                // If no analytics data found, default to 0
                                $views = $views ? intval($views) : 0;

                                ?>
                                <tr>
                                    <td><a href="<?php the_permalink(); ?>" class="link-name"><?php the_title(); ?></a></td>
                                    <td><?php echo get_the_date('M d, Y'); ?></td>
                                    <td><?php echo esc_html($views); ?></td>
                                    <td class="actions">
                                        <div class="action-icons-main">
                                            <button class="btn-icon" title="Info"><i class="fa-solid fa-circle-info"></i></button>
                                            <button class="btn-icon" title="Analytics"><i
                                                    class="fa-solid fa-chart-line"></i></button>
                                            <a href="<?php the_permalink(); ?>" class="btn-icon" title="View"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <button type="button" class="btn-icon more-actions" title="More"><i
                                                    class="fa-solid fa-ellipsis-vertical"></i></button>
                                            <div class="actions-menu">
                                                <a href="<?php echo add_query_arg('eid', get_the_ID(), $curernt_page_url); ?>">
                                                    Edit
                                                </a>
                                                <button class="action-item duplicate-link"><i class="fa-solid fa-copy"></i>
                                                    Duplicate</button>
                                                <button class="action-item delete-link"><i class="fa-solid fa-trash"></i>
                                                    Delete</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php wp_reset_postdata(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php       
    $output .= ob_get_contents();
    ob_end_clean();
    return $output;
}

function create_linkpage_data_func() {

    ob_start();
    $output = '';
    $eid = !empty($_REQUEST['eid']) ? intval($_REQUEST['eid']) : 0;

    // Load existing data if in edit mode
    $existing_data = null;

    if ($eid > 0) {
        $json_data = get_post_meta($eid, 'link_page_json_data', true);
        if (!empty($json_data)) {
            $existing_data = json_decode($json_data, true);
        }
    }

    //echo "<pre>";
    //print_r($existing_data);
    //echo "</pre>";
    //exit();
    // Continue with the rest of your shortcode output
    

    ?>

    <!-- Add this script to pass data to JavaScript -->
    <script type="text/javascript">
        var LinkPageEditData = <?php echo json_encode($existing_data); ?>;
        var LinkPageEditId = <?php echo $eid; ?>;
        
        // Trigger preview updates when in edit mode
        <?php if ($eid > 0 && !empty($existing_data)): ?>
        jQuery(document).ready(function($) {
            // Wait for DOM to be fully loaded, then trigger preview updates
            setTimeout(function() {
                // Update title and description text in preview
                if ($('[name="lp-title-text"]').val()) {
                    $('#inputtextboxTitle').text($('[name="lp-title-text"]').val());
                }
                if ($('[name="lp-desc-text"]').val()) {
                    $('#inputtextbox2Title').text($('[name="lp-desc-text"]').val());
                }
                
                // Update background color if solid background is selected
                if ($('[name="lp-background-type[]"]:checked').val() === 'solid') {
                    var bgColor = $('[name="lp-background-color"]').val();
                    if (bgColor) {
                        $('#linkpage-id').css('background-color', bgColor);
                    }
                }
                
                // Update font family for the entire preview
                var fontFamily = $('[name="lp-font-family"]').val();
                if (fontFamily) {
                    $('#linkpage-id').css('font-family', fontFamily);
                }
                
                // Update title text font size
                var titleFont = $('[name="lp-title-text-font"]').val();
                var titleFontSize = '24px';
                if (titleFont === 'h1') titleFontSize = '32px';
                else if (titleFont === 'h2') titleFontSize = '24px';
                else if (titleFont === 'h3') titleFontSize = '18px';
                else titleFontSize = '16px';
                
                // Update title text styles
                var titleStyles = {
                    color: $('[name="lp-title-text-color"]').val(),
                    textAlign: $('[name="lp-title-text-alignment"]:checked').val(),
                    fontWeight: $('[name="lp-title-text-style-bold"]:checked').length ? 'bold' : 'normal',
                    textDecoration: '',
                    fontStyle: $('[name="lp-title-text-style-italic"]:checked').length ? 'italic' : 'normal',
                    fontSize: titleFontSize
                };
                
                var titleDecorations = [];
                if ($('[name="lp-title-text-style-underline"]:checked').length) titleDecorations.push('underline');
                if ($('[name="lp-title-text-style-strikethrough"]:checked').length) titleDecorations.push('line-through');
                titleStyles.textDecoration = titleDecorations.join(' ');
                
                $('#inputtextboxTitle').css(titleStyles);
                
                // Update description text font size
                var descFont = $('[name="lp-desc-text-font"]').val();
                var descFontSize = '13px';
                if (descFont === 'h1') descFontSize = '32px';
                else if (descFont === 'h2') descFontSize = '24px';
                else if (descFont === 'h3') descFontSize = '18px';
                else descFontSize = '13px';
                
                // Update description text styles
                var descStyles = {
                    color: $('[name="lp-desc-text-color"]').val(),
                    textAlign: $('[name="lp-desc-text-alignment"]:checked').val(),
                    fontWeight: $('[name="lp-desc-text-style-bold"]:checked').length ? 'bold' : 'normal',
                    textDecoration: '',
                    fontStyle: $('[name="lp-desc-text-style-italic"]:checked').length ? 'italic' : 'normal',
                    fontSize: descFontSize
                };
                
                var descDecorations = [];
                if ($('[name="lp-desc-text-style-underline"]:checked').length) descDecorations.push('underline');
                if ($('[name="lp-desc-text-style-strikethrough"]:checked').length) descDecorations.push('line-through');
                descStyles.textDecoration = descDecorations.join(' ');
                
                $('#inputtextbox2Title').css(descStyles);
                
                // Update button styles
                var buttonColor = $('[name="lp-button-color"]').val();
                var buttonTextColor = $('[name="lp-button-text-color"]').val();
                var buttonStyle = $('[name="btn_style"]:checked').val();
                
                if (buttonColor) {
                    $('.linkpage-body .btn.rounded-filled').css('background-color', buttonColor);
                }
                if (buttonTextColor) {
                    $('.linkpage-body .btn.rounded-filled').css('color', buttonTextColor);
                }
                
                // Apply button border radius based on style
                if (buttonStyle) {
                    if (buttonStyle === 'rounded' || buttonStyle === 'outline-rounded') {
                        $('.linkpage-body .btn.rounded-filled').css('border-radius', '40px');
                    } else {
                        $('.linkpage-body .btn.rounded-filled').css('border-radius', '8px');
                    }
                    
                    // Apply outline style
                    if (buttonStyle.startsWith('outline-')) {
                        $('.linkpage-body .btn.rounded-filled').css({
                            'background': 'transparent',
                            'border': '2px solid ' + buttonColor,
                            'color': buttonColor
                        });
                    } else {
                        $('.linkpage-body .btn.rounded-filled').css({
                            'background-color': buttonColor,
                            'border': 'none',
                            'color': buttonTextColor
                        });
                    }
                }
                
                // Trigger layout update for the selected profile layout
                var selectedLayout = $('[name="layout"]:checked');
                if (selectedLayout.length) {
                    updateProfileLayout(selectedLayout[0]);
                }
                
                // Update layout background width if checkbox is checked
                var fullWidthCheckbox = $('[name="lp-profile-cover-full-width"]');
                if (fullWidthCheckbox.length && fullWidthCheckbox.is(':checked')) {
                    updateLayoutBackgroundWidth(fullWidthCheckbox[0]);
                }
                
                // Update profile image in preview if it exists
                var profileImageUrl = $('[name="lp-profile-image-url"]').val();
                if (profileImageUrl) {
                    // Update in form thumbnail preview
                    $('.lp-thumbnail-preview').attr('src', profileImageUrl);
                    // Update in mobile preview panel
                    $('.linkpage-thumbnail .img-box').attr('src', profileImageUrl);
                }
                
                // Update layout image in preview if it exists
                var layoutImageUrl = $('[name="lp-layout-image-url"]').val();
                if (layoutImageUrl) {
                    // Update banner/cover background in mobile preview
                    $('.linkpage-header::before').css('background-image', 'url(' + layoutImageUrl + ')');
                    $('.linkpage-header-full::before').css('background-image', 'url(' + layoutImageUrl + ')');
                    // Add class to ensure proper display
                    if ($('.linkpage-header').length) {
                        $('.linkpage-header').addClass('has-background-image');
                    }
                    if ($('.linkpage-header-full').length) {
                        $('.linkpage-header-full').addClass('has-background-image');
                    }
                }
                
                // Trigger background type update
                var bgType = $('[name="lp-background-type[]"]:checked');
                if (bgType.length) {
                    updateBackgroundType(bgType[0]);
                }
                
                // Trigger background color update
                var bgColorInput = $('[name="lp-background-color"]');
                if (bgColorInput.length && bgColorInput.val()) {
                    updateBackgroundColor(bgColorInput[0]);
                }
                
                // Trigger font family update
                var fontFamilySelect = $('[name="lp-font-family"]');
                if (fontFamilySelect.length) {
                    updateFontFamily(fontFamilySelect[0]);
                }
                
                // Trigger button style update
                var btnStyleRadio = $('[name="btn_style"]:checked');
                if (btnStyleRadio.length) {
                    updateButtonStyle(btnStyleRadio.val());
                }
                
                // Trigger button color update
                var btnColorInput = $('[name="lp-button-color"]');
                if (btnColorInput.length && btnColorInput.val()) {
                    updateButtonColor(btnColorInput[0]);
                }
                
                // Trigger button text color update
                var btnTextColorInput = $('[name="lp-button-text-color"]');
                if (btnTextColorInput.length && btnTextColorInput.val()) {
                    updateButtonTextColor(btnTextColorInput[0]);
                }
                
                // Trigger title text alignment update
                var titleAlign = $('[name="lp-title-text-alignment"]:checked');
                if (titleAlign.length) {
                    updateTitleTextSectionAlign(titleAlign[0]);
                }
                
                // Trigger description text alignment update
                var descAlign = $('[name="lp-desc-text-alignment"]:checked');
                if (descAlign.length) {
                    updateDescTextSectionAlign(descAlign[0]);
                }
                
                // Trigger title text style updates
                if ($('[name="lp-title-text-style-bold"]:checked').length) {
                    updateTitleTextSectionStyle($('[name="lp-title-text-style-bold"]')[0], 'bold');
                }
                if ($('[name="lp-title-text-style-underline"]:checked').length) {
                    updateTitleTextSectionStyle($('[name="lp-title-text-style-underline"]')[0], 'underline');
                }
                if ($('[name="lp-title-text-style-italic"]:checked').length) {
                    updateTitleTextSectionStyle($('[name="lp-title-text-style-italic"]')[0], 'italic');
                }
                if ($('[name="lp-title-text-style-strikethrough"]:checked').length) {
                    updateTitleTextSectionStyle($('[name="lp-title-text-style-strikethrough"]')[0], 'strikethrough');
                }
                
                // Trigger description text style updates
                if ($('[name="lp-desc-text-style-bold"]:checked').length) {
                    updateDescTextSectionStyle($('[name="lp-desc-text-style-bold"]')[0], 'bold');
                }
                if ($('[name="lp-desc-text-style-underline"]:checked').length) {
                    updateDescTextSectionStyle($('[name="lp-desc-text-style-underline"]')[0], 'underline');
                }
                if ($('[name="lp-desc-text-style-italic"]:checked').length) {
                    updateDescTextSectionStyle($('[name="lp-desc-text-style-italic"]')[0], 'italic');
                }
                if ($('[name="lp-desc-text-style-strikethrough"]:checked').length) {
                    updateDescTextSectionStyle($('[name="lp-desc-text-style-strikethrough"]')[0], 'strikethrough');
                }
                
                console.log('Preview updated with saved data on page load');
            }, 500);
        });
        <?php endif; ?>
    </script>
    <style>
        .style-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 1px solid #E4E5E7;
            border-radius: 8px;
            cursor: pointer;
            margin-right: 8px;
            background: #fff;
            position: relative;
            transition: all 0.2s ease;
        }

        .style-wrapper:hover {
            border-color: #0d6efd;
            background: #f8f9fa;
        }

        .style-wrapper input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .style-wrapper.active {
            background-color: #e7f1ff;
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .style-wrapper i {
            font-size: 16px;
        }
    </style>

    <!-- ----------------popup----------- -->
    <!-- <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAHuJ3q7yB3xvpd5cgXNSDN0ulR-3KFGIY&libraries=places"></script> -->
    <div class='cust_profile_layout popup' id='popup'>
        <div class='modal01 cust-popup-content'>
            <h3>Select header layout</h3>
            <p>Choose a layout that suits your use case.</p>
            <div class='options'>
                <div class='option' id='option1'>
                    <input type='radio' id='profile' name='layout' value="profile" onchange="updateProfileLayout(this)" <?php echo (empty($existing_data['common']['lp-profile-layout']) || $existing_data['common']['lp-profile-layout'] === 'profile') ? 'checked' : ''; ?>>
                    <label for='profile'>
                        <svg xmlns="http://www.w3.org/2000/svg" width="136" height="108" viewBox="0 0 136 108" fill="none">
                            <path
                                d="M0.5 16C0.5 7.43958 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V107.5H0.5V16Z"
                                fill="#FAFAFA" />
                            <rect x="52" y="24" width="32" height="32" rx="16" fill="#DBDFE3" />
                            <rect x="53" y="80" width="30" height="4" fill="#DBDFE3" />
                            <rect x="12" y="88" width="112" height="4" fill="#DBDFE3" />
                            <rect x="12" y="96" width="112" height="4" fill="#DBDFE3" />
                            <path d="M0.5 16C0.5 7.43958 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V107.5H0.5V16Z" stroke="#E4E5E7" />
                        </svg>
                        <div>Profile</div>
                    </label>
                </div>
                <div class='option' id='option2'>
                    <input type='radio' id='banner' value="banner" onchange="updateProfileLayout(this)" name='layout' <?php echo (!empty($existing_data['common']['lp-profile-layout']) && $existing_data['common']['lp-profile-layout'] === 'banner') ? 'checked' : ''; ?>>
                    <label for='banner'>
                        <svg xmlns="http://www.w3.org/2000/svg" width="136" height="108" viewBox="0 0 136 108" fill="none">
                            <path d="M0.5 16C0.5 7.43958 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V107.5H0.5V16Z"
                                fill="#FAFAFA" />
                            <g clip-path="url(#clip0_158_57413)">
                                <rect width="120" height="40" transform="translate(8.33203 8)" fill="#DBDFE3" />
                                <rect x="50" y="30" width="36" height="36" rx="18" fill="#DBDFE3" />
                                <rect x="50" y="30" width="36" height="36" rx="18" stroke="#FAFAFA" stroke-width="4" />
                            </g>
                            <rect x="53" y="80" width="30" height="4" fill="#DBDFE3" />
                            <rect x="12" y="88" width="112" height="4" fill="#DBDFE3" />
                            <rect x="12" y="96" width="112" height="4" fill="#DBDFE3" />
                            <path d="M0.5 16C0.5 7.43958 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V107.5H0.5V16Z" stroke="#DBDFE3" />
                            <defs>
                                <clipPath id="clip0_158_57413">
                                    <path
                                        d="M8 16C8 11.5817 11.5817 8 16 8H120C124.418 8 128 11.5817 128 16V64C128 68.4183 124.418 72 120 72H16C11.5817 72 8 68.4183 8 64V16Z"
                                        fill="white" />
                                </clipPath>
                            </defs>
                        </svg>
                        <div>Banner</div>
                    </label>
                </div>
                <div class='option' id='option3'>
                    <input type='radio' id='cover' value="cover" onchange="updateProfileLayout(this)" name='layout' <?php echo (!empty($existing_data['common']['lp-profile-layout']) && $existing_data['common']['lp-profile-layout'] === 'cover') ? 'checked' : ''; ?>>
                    <label for='cover'>
                        <svg xmlns="http://www.w3.org/2000/svg" width="136" height="108" viewBox="0 0 136 108" fill="none">
                            <path
                                d="M0.5 16C0.5 7.43958 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V107.5H0.5V16Z"
                                fill="#FAFAFA" />
                            <path d="M8 16C8 11.5817 11.5817 8 16 8H120C124.418 8 128 11.5817 128 16V72H8V16Z"
                                fill="#DBDFE3" />
                            <rect x="53" y="80" width="30" height="4" fill="#DBDFE3" />
                            <rect x="12" y="88" width="112" height="4" fill="#DBDFE3" />
                            <rect x="12" y="96" width="112" height="4" fill="#DBDFE3" />
                            <path
                                d="M0.5 16C0.5 7.43958 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V107.5H0.5V16Z"
                                stroke="#E4E5E7" />
                        </svg>
                        <div>Cover</div>
                    </label>
                </div>
                <div class='option' id='option4'>
                    <input type='radio' id='minimal' value="minimal" onchange="updateProfileLayout(this)" name='layout' <?php echo (!empty($existing_data['common']['lp-profile-layout']) && $existing_data['common']['lp-profile-layout'] === 'minimal') ? 'checked' : ''; ?>>
                    <label for='minimal'>
                        <svg xmlns="http://www.w3.org/2000/svg" width="136" height="107" viewBox="0 0 136 107" fill="none">
                            <path d="M0.5 16C0.5 7.43959 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V106.5H0.5V16Z"
                                fill="#FAFAFA" />
                            <rect x="53" y="32" width="30" height="4" fill="#DBDFE3" />
                            <rect x="12" y="40" width="112" height="4" fill="#DBDFE3" />
                            <rect x="12" y="48" width="112" height="4" fill="#DBDFE3" />
                            <path d="M0.5 16C0.5 7.43959 7.43959 0.5 16 0.5H120C128.56 0.5 135.5 7.43959 135.5 16V106.5H0.5V16Z"
                                stroke="#DBDFE3" />
                        </svg>
                        <div>Minimal</div>
                    </label>
                </div>
            </div>
            <div class='popup-footer'>
                <a href='#' class="close-popup-btn" onclick="">Close</a>
                <a href='javascript:void(0)' id='optionsubmit' class='btn select'>Select</a>
            </div>
        </div>
    </div>

    <div class='custom-timepicker-popup'>
        <div class='timepicker'>
            <div class='top'>
                <span class='h'>12</span> : <span class='m'>00</span> <span class='ampm'>AM</span>
            </div>
            <div class='circle'></div>
            <div class='actions'>
                <div class='action cancel'>Cancel</div>
                <div class='action ok'>OK</div>
            </div>
        </div>
    </div>

    
    <div class='link-pages-main-container'>
        <?php /*
        <div class='um-field' id='field1' style="<?php echo !empty($eid) ? 'display:none;' : ''; ?>">
            <div class='um-top-header'>
                <div class='um_col8'>
                    <h3 class='header-title'>Link Pages ( <?php echo esc_html($linkpage_count); ?> )</h3>
                    <p class='sub-description'>Create a mobile-friendly, link-in-bio page with multiple links.</p>
                </div>
                <div class='um_col4'>
                    <div class='um-btn-wraper'>
                        <button type='button' id='create_linkpage'>
                            <i class='fa-solid fa-plus'></i> Create Link Page
                        </button>
                    </div>
                </div>
            </div>
            <div class='um_card-body'>
                <?php if (!$linkpages_query->have_posts()):
                ?>
                    <div class='um-contentbox' style='text-align: center; display: flex; flex-direction: column;'>
                        <div class='um_imgbox'>
                            <img src='<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/linkpage-empty-design.png'; ?>' alt='No Link Page Created'>
                        </div>
                        <div class='um-text-box'>
                            <span class='um_innertitle'>No Link Page Created</span>
                            <span class='um_description'>Link Page is a mobile-friendly link-in-bio page with multiple
                                links.</span>
                        </div>
                    </div>
                <?php else:
                    
                ?>
                    <table id="linkpage-table" class="um-linkpage-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Created</th>
                                <th>Views</th>
                                <th class="actions-column">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($linkpages_query->have_posts()):
                                $json_data = get_post_meta(get_the_ID(), 'link_page_json_data', true);
                                $link_page_json_data = json_decode($json_data, true);

                                $linkpages_query->the_post();
                                $views = get_post_meta(get_the_ID(), 'linkpage_views', true) ?: 0; ?>
                                <tr>
                                    <td><a href="<?php the_permalink(); ?>" class="link-name"><?php the_title(); ?></a></td>
                                    <td><?php echo get_the_date('M d, Y'); ?></td>
                                    <td><?php echo esc_html($views); ?></td>
                                    <td class="actions">
                                        <div class="action-icons-main">
                                            <button class="btn-icon" title="Info"><i class="fa-solid fa-circle-info"></i></button>
                                            <button class="btn-icon" title="Analytics"><i
                                                    class="fa-solid fa-chart-line"></i></button>
                                            <a href="<?php the_permalink(); ?>" class="btn-icon" title="View"><i
                                                    class="fa-solid fa-eye"></i></a>
                                            <button type="button" class="btn-icon more-actions" title="More"><i
                                                    class="fa-solid fa-ellipsis-vertical"></i></button>
                                            <div class="actions-menu">
                                                <a href="<?php echo add_query_arg('edit_id', get_the_ID(), $curernt_page_url); ?>">
                                                    Edit
                                                </a>
                                                <button class="action-item duplicate-link"><i class="fa-solid fa-copy"></i>
                                                    Duplicate</button>
                                                <button class="action-item delete-link"><i class="fa-solid fa-trash"></i>
                                                    Delete</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php wp_reset_postdata(); ?>
                <?php endif; ?>
            </div>
        </div>
        */ ?>
        <!-------------------------- Linkpages --------------------------->
        <div class='um-field' id='field2' style="<?php echo !empty($eid) ? 'display:none;' : ''; ?>">

            <div class='um-top-header'>
                <div class='um_col8'>
                    <!-- <h3 class='header-title'>Link Pages (<?php echo esc_html($linkpage_count); ?> )</h3 class='header-title'> -->
                    <p class='sub-description'>Create a mobile-friendly, link-in-bio page with multiple links.</p>
                </div>
                <div class='um_col4'>
                    <!-- <div class='um-btn-wraper'>
                        <a href='javascript:void(0)' onclick="show('popup')"><i class='fa-solid fa-plus'></i>
                            Create Link Page</a>
                    </div> -->
                </div>
            </div>

            <div class='um_card-body'>
                <div class='um_btn-wraper-row'>
                    <div class='um-btn-wraper'><a href='#'>Featured templates</a></div>
                    <div class='um-btn-wraper'><a href='#'>Saved templates ( 0 )</a></div>
                </div>
                <div class='um-grid-templates'>
                    <div class='um_templates-col'>
                        <a class='button' href='javascript:void(0)' onclick="show('popup')">
                            <div class='um-wraper'>
                                <div class='um-thumbnail-img'><img
                                        src='https://simplqr.com/wp-content/uploads/2025/09/011.png'>
                                </div>
                                <div class='um_templates-contnet'>
                                    <h4 class='um_template-title'>Blank template</h4>
                                    <p class='um_temp-description'>Design your own linkpage template from scratch</p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'>
                                <img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/01.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Freelancer</h4>
                                <p class='um_temp-description'>Grab the attention of those who love to see your work before
                                    becoming your customer!</p>
                            </div>
                        </div>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'>
                                <img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/02.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Footwear Retail</h4>
                                <p class='um_temp-description'>Highlight the best product features - all at once.</p>
                            </div>
                        </div>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'>
                                <img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/03.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Product Description I</h4>
                                <p class='um_temp-description'>Help consumers learn about your product and share feedback
                                    easily.</p>
                            </div>
                        </div>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'><img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/04.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Product Description II</h4>
                                <p class='um_temp-description'>Share product details and an exclusive discount with
                                    consumers.</p>
                            </div>
                        </div>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'>
                                <img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/05.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Product Assembly Guide</h4>
                                <p class='um_temp-description'>Help consumers assemble your product easily with a
                                    step-by-step guide.</p>
                            </div>
                        </div>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'><img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/06.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Blank template</h4>
                                <p class='um_temp-description'>Design your own linkpage template from scratch</p>
                            </div>
                        </div>
                    </div>
                    <div class='um_templates-col'>
                        <div class='um-wraper'>
                            <div class='um-thumbnail-img'><img
                                    src='https://simplqr.com/wp-content/uploads/2025/09/07.png'>
                            </div>
                            <div class='um_templates-contnet'>
                                <h4 class='um_template-title'>Musician</h4>
                                <p class='um_temp-description'>Showcase your work beautifully while linking to your event
                                    details, playlists, and social media.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class='um-field' id='field3' style="<?php echo empty($eid) ? 'display:none;' : ''; ?>">
            <div class='um-content__inner'>
                <div class='tab'>
                    <div class='firstbtnrow'>
                        <button type='button' class='tablinks' data-step="Step1" id='defaultOpen'>
                            <span class='um_step-text'>Step 1</span>
                            <span class='um-step-title'>Build your link page</span>
                        </button>
                        <button type='button' class='tablinks' data-step="Step2">
                            <span class='um_step-text'>Step 2</span>
                            <span class='um-step-title'>Design your link page</span>
                        </button>
                        <button type='button' class='tablinks' data-step="Step3">
                            <span class='um_step-text'>Step 3</span>
                            <span class='um-step-title'>Setup your link page</span>
                        </button>
                    </div>
                </div>
                <div class='main-tabcontent'>
                    <div class="link-pages-form">
                        <div class='um-contener-step'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col8'>
                                    <div id='Step1' class='tabcontent'>
                                        <div class='cust-wraper'>
                                            <div class='um-inner-heading-row'>
                                                <h4 class='page-heading'>Page header</h4>
                                                <div class='heading-btn-wraper'>
                                                    <a href='javascript:void(0)' onclick="show('popup')"><i
                                                            class='fa-solid fa-user'></i>Switch layout</a>
                                                </div>
                                            </div>
                                            <div class='cust-fild-step1'>
                                                <div class='cust-profilefild'>
                                                    <div id='profileimgdiv' class='profileimgdiv' style='display: none'>
                                                        <label class='cust_fild_title'>Profile</label>
                                                        <?php
                                                        $profile_image_url = !empty($existing_data['common']['lp_profile_image_url']) 
                                                            ? esc_url($existing_data['common']['lp_profile_image_url']) 
                                                            : get_stylesheet_directory_uri() . '/assets/linkpages/images/Photoupload.svg';
                                                        $default_profile_url = get_stylesheet_directory_uri() . '/assets/linkpages/images/Photoupload.svg';
                                                        ?>
                                                        <img class="lp-thumbnail-preview"
                                                            data-default-url='<?php echo $default_profile_url; ?>'
                                                            src='<?php echo $profile_image_url; ?>'>
                                                        <!-- Hidden input to store image URL -->
                                                        <input type='hidden' class='lp-profile-image'
                                                            name='lp-profile-image-url' value='<?php echo !empty($existing_data['common']['lp_profile_image_url']) ? esc_attr($existing_data['common']['lp_profile_image_url']) : ''; ?>'>
                                                        <input type='hidden' name='lp-profile-image-uploaded' value='<?php echo !empty($existing_data['common']['lp-profile-image-uploaded']) ? '1' : ''; ?>'>
                                                        <div id='thumbnailPopup' class='popup-modal profilepopup'>
                                                            <div class='popup-content'>
                                                                <div class="popup-header d-flex position-relative">
                                                                    <h3>Upload Profile Image</h3>
                                                                    <a href="javascript:void(0)" id='profilepopupclose'
                                                                        class='close-btn position-absolute end-0'> X</a>
                                                                </div>
                                                                <div class='thumbnail-img'>
                                                                    <div class='cust-upload-box active' id='drop-area-profile'>
                                                                        <div class="upload-placeholder" style="<?php echo !empty($existing_data['common']['lp_profile_image_url']) ? 'display:none;' : ''; ?>">
                                                                            <div class='icon'>
                                                                                <i class='fa-solid fa-cloud-arrow-up'
                                                                                    style='color: #000'></i>
                                                                            </div>
                                                                            <p>
                                                                                Drag file here or
                                                                                <label for='lp-w-profile-file'>Upload file</label>
                                                                            </p>
                                                                            <input type='file' class="upload-input" id='lp-w-profile-file'
                                                                                name="lp-w-link-file[]" accept='image/*'
                                                                                style="display:none;" data-file-key="icon">
                                                                            <small>500x500 pixels. 5MB max file size.</small>
                                                                        </div>
                                                                        <!-- Preview Section -->
                                                                        <div class="cover-preview" style="<?php echo !empty($existing_data['common']['lp_profile_image_url']) ? 'display:block;' : 'display:none;'; ?>">
                                                                            <img id="coverPreviewImg45" class="preview-img" src="<?php echo $profile_image_url; ?>" alt="Preview" />
                                                                            <button type="button" class="remove-btn">Remove</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id='uploaddivforoption2' class='uploaddivforoption2'
                                                        style='display: none;'>
                                                        <?php
                                                        $layout_image_url = !empty($existing_data['common']['lp_layout_image_url']) 
                                                            ? esc_url($existing_data['common']['lp_layout_image_url']) 
                                                            : '';
                                                        ?>
                                                        <div class='cust-upload-box active' id='drop-area-layout'>
                                                            <div class="upload-placeholder" style="<?php echo !empty($existing_data['common']['lp_layout_image_url']) ? 'display:none;' : ''; ?>">
                                                                <div class='icon'>
                                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                                        style='color: #000'></i>
                                                                </div>
                                                                <p>
                                                                    Drag file here or
                                                                    <label for='lp-w-layout-file'>Upload file</label>
                                                                </p>
                                                                <input type='file' class="upload-input" id='lp-w-layout-file'
                                                                    name="lp-w-link-file[]" accept='image/*'
                                                                    style="display:none;" data-file-key="icon">
                                                                <small>500x500 pixels. 5MB max file size.</small>
                                                            </div>
                                                            <!-- Preview Section -->
                                                            <div class="cover-preview" style="<?php echo !empty($existing_data['common']['lp_layout_image_url']) ? 'display:block;' : 'display:none;'; ?>">
                                                                <img id="coverPreviewImg45" class="preview-img" src="<?php echo $layout_image_url; ?>" alt="Preview" />
                                                                <button type="button" class="remove-btn">Remove</button>
                                                            </div>
                                                        </div>
                                                        <input type='hidden' name='lp-layout-image-url' value='<?php echo !empty($existing_data['common']['lp_layout_image_url']) ? esc_attr($existing_data['common']['lp_layout_image_url']) : ''; ?>'>
                                                        <input type='hidden' name='lp-layout-image-uploaded' value='<?php echo !empty($existing_data['common']['lp-layout-image-uploaded']) ? '1' : ''; ?>'>'
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox' name="lp-profile-cover-full-width" onchange="updateLayoutBackgroundWidth(this)" <?php echo (!empty($existing_data['common']['lp-profile-cover-full-width']) && $existing_data['common']['lp-profile-cover-full-width'] === '1') ? 'checked' : ''; ?>>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Full Width</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div data-id='inputtextbox'>
                                                    <div class='cust-titlefild'>
                                                        <label class='cust_fild_title'>Title</label>
                                                        <input name="lp-title-text" class='lp-title-text' id='input_1'
                                                            type='text' placeholder='Enter title' value='<?php echo !empty($existing_data["common"]["lp-title-text"]) ? esc_attr($existing_data["common"]["lp-title-text"]) : "Stark Industries"; ?>' required>
                                                    </div>
                                                    <div class='cust-editerfild-row'>
                                                        <div class='custcol4'>
                                                            <div class='cust-innerwraper'>
                                                                <select name='lp-title-text-font'>
                                                                    <option value='normal' <?php echo (empty($existing_data["common"]["lp-title-text-font"]) || $existing_data["common"]["lp-title-text-font"] === "normal") ? "selected" : ""; ?>>Normal text</option>
                                                                    <option value='h1' <?php echo (!empty($existing_data["common"]["lp-title-text-font"]) && $existing_data["common"]["lp-title-text-font"] === "h1") ? "selected" : ""; ?>>Heading 1 ( 32px )</option>
                                                                    <option value='h2' <?php echo (!empty($existing_data["common"]["lp-title-text-font"]) && $existing_data["common"]["lp-title-text-font"] === "h2") ? "selected" : ""; ?>>Heading 2 ( 24px )</option>
                                                                    <option value='h3' <?php echo (!empty($existing_data["common"]["lp-title-text-font"]) && $existing_data["common"]["lp-title-text-font"] === "h3") ? "selected" : ""; ?>>Heading 3 ( 18px )</option>
                                                                </select>
                                                                <div class='color-pair' id='formainheading'>
                                                                    <input type='text' name="lp-title-text-hexcolor"
                                                                        class='hexcolor lp-title-text-hexcolor'
                                                                        value='<?php echo !empty($existing_data["common"]["lp-title-text-hexcolor"]) ? esc_attr($existing_data["common"]["lp-title-text-hexcolor"]) : "#000000"; ?>'>
                                                                    <input type='color' name="lp-title-text-color"
                                                                        class='colorpicker lp-title-text-color'
                                                                        value='<?php echo !empty($existing_data["common"]["lp-title-text-color"]) ? esc_attr($existing_data["common"]["lp-title-text-color"]) : "#000000"; ?>'>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class='custcol4'>
                                                            <div class='cust-innerwraper'>
                                                                <div class='text-styling'>
                                                                    <div class='text-alignment'>
                                                                        <label class='style-wrapper'>
                                                                            <input type="radio" name="lp-title-text-alignment" value="left" onchange="updateTitleTextSectionAlign(this)" <?php echo (empty($existing_data["common"]["lp-title-text-alignment"]) || $existing_data["common"]["lp-title-text-alignment"] === "left") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-align-left'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="radio" name="lp-title-text-alignment" value="center" onchange="updateTitleTextSectionAlign(this)" <?php echo (!empty($existing_data["common"]["lp-title-text-alignment"]) && $existing_data["common"]["lp-title-text-alignment"] === "center") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-align-center'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="radio" name="lp-title-text-alignment" value="right" onchange="updateTitleTextSectionAlign(this)" <?php echo (!empty($existing_data["common"]["lp-title-text-alignment"]) && $existing_data["common"]["lp-title-text-alignment"] === "right") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-align-right'></i></span>
                                                                        </label>
                                                                    </div>
                                                                    <div class='font-styling' id='font-styling'>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-title-text-style-bold" value="1" onchange="updateTitleTextSectionStyle(this, 'bold')" <?php echo (!empty($existing_data["common"]["lp-title-text-style-bold"]) && $existing_data["common"]["lp-title-text-style-bold"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-bold'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-title-text-style-underline" value="1" onchange="updateTitleTextSectionStyle(this, 'underline')" <?php echo (!empty($existing_data["common"]["lp-title-text-style-underline"]) && $existing_data["common"]["lp-title-text-style-underline"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-underline'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-title-text-style-italic" value="1" onchange="updateTitleTextSectionStyle(this, 'italic')" <?php echo (!empty($existing_data["common"]["lp-title-text-style-italic"]) && $existing_data["common"]["lp-title-text-style-italic"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-italic'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-title-text-style-strikethrough" value="1" onchange="updateTitleTextSectionStyle(this, 'strikethrough')" <?php echo (!empty($existing_data["common"]["lp-title-text-style-strikethrough"]) && $existing_data["common"]["lp-title-text-style-strikethrough"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-strikethrough'></i></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class='custcol4'>
                                                            <div class='cust-innerwraper'></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div data-id='inputtextbox2'>
                                                    <div class='cust-descriptionfild'>
                                                        <label class='cust_fild_title'>Description</label>
                                                        <input class='lp-desc-text' name='lp-desc-text' id='lp-desc-text' type='text'
                                                            placeholder='Enter description' pattern='' value='<?php echo !empty($existing_data["common"]["lp-desc-text"]) ? esc_attr($existing_data["common"]["lp-desc-text"]) : ""; ?>'>
                                                    </div>
                                                    <div class='cust-editerfild-row'>
                                                        <div class='custcol4'>
                                                            <div class='cust-innerwraper'>
                                                                <select name='lp-desc-text-font' id='fonts2'>
                                                                    <option value='normal' <?php echo (empty($existing_data["common"]["lp-desc-text-font"]) || $existing_data["common"]["lp-desc-text-font"] === "normal") ? "selected" : ""; ?>>Normal text</option>
                                                                    <option value='h1' <?php echo (!empty($existing_data["common"]["lp-desc-text-font"]) && $existing_data["common"]["lp-desc-text-font"] === "h1") ? "selected" : ""; ?>>Heading 1 ( 32px )</option>
                                                                    <option value='h2' <?php echo (!empty($existing_data["common"]["lp-desc-text-font"]) && $existing_data["common"]["lp-desc-text-font"] === "h2") ? "selected" : ""; ?>>Heading 2 ( 24px )</option>
                                                                    <option value='h3' <?php echo (!empty($existing_data["common"]["lp-desc-text-font"]) && $existing_data["common"]["lp-desc-text-font"] === "h3") ? "selected" : ""; ?>>Heading 3 ( 18px )</option>
                                                                </select>
                                                                <div class='color-pair' id='forsubheading'>
                                                                    <input type='text' name="lp-desc-text-hexcolor"
                                                                        class='hexcolor lp-desc-text-hexcolor'
                                                                        value='<?php echo !empty($existing_data["common"]["lp-desc-text-hexcolor"]) ? esc_attr($existing_data["common"]["lp-desc-text-hexcolor"]) : "#000000"; ?>'>
                                                                    <input type='color' name="lp-desc-text-color"
                                                                        class='colorpicker lp-desc-text-color'
                                                                        value='<?php echo !empty($existing_data["common"]["lp-desc-text-color"]) ? esc_attr($existing_data["common"]["lp-desc-text-color"]) : "#000000"; ?>'>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class='custcol4'>
                                                            <div class='cust-innerwraper'>
                                                                <div class='text-styling'>
                                                                    <div class='text-alignment' id='text-alignment2'>
                                                                        <label class='style-wrapper'>
                                                                            <input type="radio" name="lp-desc-text-alignment" value="left" onchange="updateDescTextSectionAlign(this)" <?php echo (empty($existing_data["common"]["lp-desc-text-alignment"]) || $existing_data["common"]["lp-desc-text-alignment"] === "left") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-align-left'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="radio" name="lp-desc-text-alignment" value="center" onchange="updateDescTextSectionAlign(this)" <?php echo (!empty($existing_data["common"]["lp-desc-text-alignment"]) && $existing_data["common"]["lp-desc-text-alignment"] === "center") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-align-center'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="radio" name="lp-desc-text-alignment" value="right" onchange="updateDescTextSectionAlign(this)" <?php echo (!empty($existing_data["common"]["lp-desc-text-alignment"]) && $existing_data["common"]["lp-desc-text-alignment"] === "right") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-align-right'></i></span>
                                                                        </label>
                                                                    </div>
                                                                    <div class='font-styling' id='font-styling2'>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-desc-text-style-bold" value="1" onchange="updateDescTextSectionStyle(this, 'bold')" <?php echo (!empty($existing_data["common"]["lp-desc-text-style-bold"]) && $existing_data["common"]["lp-desc-text-style-bold"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-bold'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-desc-text-style-underline" value="1" onchange="updateDescTextSectionStyle(this, 'underline')" <?php echo (!empty($existing_data["common"]["lp-desc-text-style-underline"]) && $existing_data["common"]["lp-desc-text-style-underline"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-underline'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-desc-text-style-italic" value="1" onchange="updateDescTextSectionStyle(this, 'italic')" <?php echo (!empty($existing_data["common"]["lp-desc-text-style-italic"]) && $existing_data["common"]["lp-desc-text-style-italic"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-italic'></i></span>
                                                                        </label>
                                                                        <label class='style-wrapper'>
                                                                            <input type="checkbox" name="lp-desc-text-style-strikethrough" value="1" onchange="updateDescTextSectionStyle(this, 'strikethrough')" <?php echo (!empty($existing_data["common"]["lp-desc-text-style-strikethrough"]) && $existing_data["common"]["lp-desc-text-style-strikethrough"] === "1") ? "checked" : ""; ?>>
                                                                            <span class="style-icon"><i class='fa-solid fa-strikethrough'></i></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class='custcol4'>
                                                            <div class='cust-innerwraper'></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="appendwidgetboxcontainer">






                                            <div class='cust-wraper cust-mt widgetbox button-text buttontextfields'
                                                data-widget-counter="16" data-widget-type="button" id='buttontextfields_1'>
                                                <div class='um_contenter-inner-row'>
                                                    <div class='cust-col2'>
                                                        <div class='linkicons movewidget'>
                                                            <i class='fa-solid fa-up-down-left-right'></i>
                                                        </div>
                                                        <div class='linkicons'>
                                                            <i class='fa-solid fa-link'></i>
                                                        </div>
                                                    </div>
                                                    <div class='cust-col10'>
                                                        <p class='subtxt'>Enter Title</p>
                                                        <div class='togglewhitebox'>
                                                            <div class='fortitle'>
                                                                <input type='text' name="wg-w-link-title[]"
                                                                    placeholder='Enter title' oninput="updateAddButtonWidgetText(this)" required>
                                                            </div>
                                                            <div class='forurl'>
                                                                <input type='text' name="wg-w-link-url[]"
                                                                    placeholder='Enter URL' oninput="updateAddButtonWidgetURL(this)">
                                                            </div>
                                                            <div class='radio-wrapper-main'>
                                                                <div class='radio-group'>
                                                                    <label class='radio-wrapper'>
                                                                        <input type='radio' id="lp-w-link-type-no-img_1"
                                                                            name='wg-w-link-type[]' value='No Images'
                                                                            checked onchange="updateButtonWidgetPreviewType(this)">
                                                                        <span class='checkmark'
                                                                            for="lp-w-link-type-no-img_1"></span>
                                                                        <span for="lp-w-link-type-no-img_1">No Images</span>
                                                                    </label>
                                                                    <!-- <label class='radio-wrapper'>
                                                                        <input type='radio' name='wg-w-link-type[]'
                                                                            value='Icons' id='lp-w-link-type-icon_1' onchange="updateButtonWidgetPreviewType(this)">
                                                                        <span class='checkmark'
                                                                            for="lp-w-link-type-icon_1"></span>
                                                                        <span for="lp-w-link-type-icon_1">Icons</span>
                                                                    </label> -->
                                                                    <label class='radio-wrapper'>
                                                                        <input type='radio' name='wg-w-link-type[]'
                                                                            value='Thumbnail' id='lp-w-link-type-thumnail_1' onchange="updateButtonWidgetPreviewType(this)">
                                                                        <span class='checkmark'
                                                                            for="lp-w-link-type-thumnail_1"></span>
                                                                        <span for='lp-w-link-type-thumnail_1'>Thumbnail</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class='cust-upload-box drop-area' id='drop-area'>
                                                                <div class="upload-placeholder">
                                                                    <div class='icon'>
                                                                        <i class='fa-solid fa-cloud-arrow-up'
                                                                            style='color: #000'></i>
                                                                    </div>
                                                                    <p>
                                                                        Drag file here or
                                                                        <label for='lp-w-link-file'>Upload file</label>
                                                                    </p>
                                                                    <input type='file' class="upload-input" id='lp-w-link-file'
                                                                        name="wg-w-link-file[]" accept='image/*'
                                                                        style="display:none;" data-file-key="icon">
                                                                    <small>500x500 pixels. 5MB max file size.</small>
                                                                </div>
                                                                <!-- Preview Section -->
                                                                <div class="cover-preview" style="display:none;">
                                                                    <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                                    <button type="button" class="remove-btn">Remove</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='cust-col2 second'>
                                                        <div class='linkicons deletebox'>
                                                            <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                                        </div>
                                                        <div class='linkicons togglearrow'>
                                                            <i class='fa-solid fa-angle-up'></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class='addbtn'>
                                            <button type='button' class='addlink' id='addlink'><i class='fa-solid fa-plus'
                                                    style='color: #ffffff;'></i> Add Link</button>
                                            <button type='button' class='widgets' id='widgetsBtn'>
                                                <i class='fa-solid fa-plus' style='color: #1f7fd9;'></i> Add Widgets
                                                <ul class='input-type' id='input-type' name='input-type'>
                                                    <li class='selectitems separator' data-id='Separatorfield'>Separator
                                                    </li>
                                                    <li class='selectitems linkpage' data-id='linkpagefield'>Link Page</li>
                                                    <!-- <li class='selectitems landingpage' data-id='landingpagefield'>Landing
                                                        Page </li>
                                                    <li class='selectitems form' data-id='formfield'>Form</li> -->
                                                    <li class='selectitems socialmedia' data-id='sociallinkfield'>Social
                                                        Links</li>
                                                    <li class='selectitems text' data-id='textboxfield'>Text</li>
                                                    <li class='selectitems embadedvideo' data-id='embadedfield'>Embedded
                                                        Video </li>
                                                    <li class='selectitems imagegallery' data-id='imggalleryfield'>Image
                                                        Gallery </li>
                                                    <li class='selectitems email' data-id='emailfield'>Email</li>
                                                    <li class='selectitems call' data-id='callfield'>Call</li>
                                                    <li class='selectitems sms' data-id='smsfield'>SMS</li>
                                                    <li class='selectitems uploadpdf' data-id='uploadpdffield'>Upload PDF
                                                    </li>
                                                    <li class='selectitems location' data-id='locationfield'>Location</li>
                                                    <li class='selectitems bussinesshours' data-id='businesshoursfield'>
                                                        Business Hours</li>
                                                </ul>
                                            </button>
                                        </div>
                                        <div class='tab cust-mt'>
                                            <div class='secondbtnrow'>
                                                <button type='button' disabled
                                                    class='um-btn-wraper um-back-btn'>BACK</button>
                                                <button type='button' data-step="Step2"
                                                    class='um-btn-wraper um-next-btn'>NEXT STEP</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id='Step2' class='tabcontent'>
                                        <div class='cust-wraper'>
                                            <div class='um-inner-heading-row'>
                                                <div class='maincontentbox'>
                                                    <h4 class='boxtitle'>Background <span><i
                                                                class='fa-solid fa-angle-down'></i></span></h4>
                                                    <div class='togglebox active'>
                                                        <div class='radio-group'>
                                                            <label class='radio-wrapper'>
                                                                <input type='radio' name='lp-background-type[]'
                                                                    value='solid' <?php echo (empty($existing_data["common"]["lp-background-type"]) || (is_array($existing_data["common"]["lp-background-type"]) && $existing_data["common"]["lp-background-type"][0] === "solid") || $existing_data["common"]["lp-background-type"] === "solid") ? "checked" : ""; ?> onchange="updateBackgroundType(this)">
                                                                <span class='checkmark'></span>
                                                                <span>Solid color</span>
                                                            </label>
                                                            <label class='radio-wrapper'>
                                                                <input type='radio' name='lp-background-type[]'
                                                                    value='image' <?php echo (!empty($existing_data["common"]["lp-background-type"]) && ((is_array($existing_data["common"]["lp-background-type"]) && $existing_data["common"]["lp-background-type"][0] === "image") || $existing_data["common"]["lp-background-type"] === "image")) ? "checked" : ""; ?> onchange="updateBackgroundType(this)">
                                                                <span class='checkmark'></span>
                                                                <span>Image</span>
                                                            </label>
                                                            <label class='radio-wrapper'>
                                                                <input type='radio' name='lp-background-type[]'
                                                                    value='video' <?php echo (!empty($existing_data["common"]["lp-background-type"]) && ((is_array($existing_data["common"]["lp-background-type"]) && $existing_data["common"]["lp-background-type"][0] === "video") || $existing_data["common"]["lp-background-type"] === "video")) ? "checked" : ""; ?> onchange="updateBackgroundType(this)">
                                                                <span class='checkmark'></span>
                                                                <span>Video</span>
                                                            </label>
                                                        </div>
                                                        <div class='colorpicker background-colorpicker' style="display: block;">
                                                            <p class='backgroundtxt'>Background color</p>
                                                            <div class='color-pair d-flex'>
                                                                <input type='text' name="lp-background-hexcolor"
                                                                    class='hexcolor lp-background-hexcolor' value='<?php echo !empty($existing_data["common"]["lp-background-hexcolor"]) ? esc_attr($existing_data["common"]["lp-background-hexcolor"]) : "#FFFFFF"; ?>'>
                                                                <input type='color' name="lp-background-color"
                                                                    class='colorpicker lp-background-color' value='<?php echo !empty($existing_data["common"]["lp-background-color"]) ? esc_attr($existing_data["common"]["lp-background-color"]) : "#FFFFFF"; ?>' onchange="updateBackgroundColor(this)">
                                                            </div>
                                                        </div>
                                                        <div class='imagepicker' style="display: none;">
                                                            <div class='cust-upload-box drop-area-background-image active' id='drop-area-background-image'>
                                                                <div class="upload-placeholder">
                                                                    <div class='icon'>
                                                                        <i class='fa-solid fa-cloud-arrow-up'
                                                                            style='color: #000'></i>
                                                                    </div>
                                                                    <p>
                                                                        Drag file here or
                                                                        <label for='lp-w-link-file'>Upload file</label>
                                                                    </p>
                                                                    <input type='file' class="upload-input" id='lp-w-link-file'
                                                                        name="lp-w-link-file[]" accept='image/*'
                                                                        style="display:none;">
                                                                    <small>500x500 pixels. 5MB max file size.</small>
                                                                </div>
                                                                <!-- Preview Section -->
                                                                <div class="cover-preview" style="display:none;">
                                                                    <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                                    <button type="button" class="remove-btn">Remove</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class='videopicker' style="display: none;">
                                                            <div class='cust-upload-box drop-area-background-video active' id='drop-area-background-video'>
                                                                <div class="upload-placeholder">
                                                                    <div class='icon'>
                                                                        <i class='fa-solid fa-cloud-arrow-up'
                                                                            style='color: #000'></i>
                                                                    </div>
                                                                    <p>
                                                                        Drag file here or
                                                                        <label for='lp-w-link-file'>Upload file</label>
                                                                    </p>
                                                                    <input type='file' class="upload-input" id='lp-w-link-file'
                                                                        name="lp-w-link-file[]" accept='image/*'
                                                                        style="display:none;">
                                                                    <small>500x500 pixels. 5MB max file size.</small>
                                                                </div>
                                                                <!-- Preview Section -->
                                                                <div class="cover-preview" style="display:none;">
                                                                    <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                                    <button type="button" class="remove-btn">Remove</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <hr class='custhr'>
                                                </div>
                                                <div class='maincontentbox'>
                                                    <h4 class='boxtitle'>Typography <span><i
                                                                class='fa-solid fa-angle-down'></i></span></h4>
                                                    <div class='togglebox active'>
                                                        <p class='backgroundtxt'>Font</p>
                                                        <select class='font-type' id='font-type' name='lp-font-family' onchange="updateFontFamily(this)">
                                                            <option value='karla' <?php echo (!empty($existing_data["common"]["lp-font-family"]) && $existing_data["common"]["lp-font-family"] === "karla") ? "selected" : ""; ?>> Karla </option>
                                                            <option value='Montserrat' <?php echo (empty($existing_data["common"]["lp-font-family"]) || $existing_data["common"]["lp-font-family"] === "Montserrat") ? "selected" : ""; ?>> Montserrat </option>
                                                            <option value='lato' <?php echo (!empty($existing_data["common"]["lp-font-family"]) && $existing_data["common"]["lp-font-family"] === "lato") ? "selected" : ""; ?>>Lato</option>
                                                            <option value='sniglet' <?php echo (!empty($existing_data["common"]["lp-font-family"]) && $existing_data["common"]["lp-font-family"] === "sniglet") ? "selected" : ""; ?>>Sniglet </option>
                                                            <option value='dancing-script' <?php echo (!empty($existing_data["common"]["lp-font-family"]) && $existing_data["common"]["lp-font-family"] === "dancing-script") ? "selected" : ""; ?>>Dancing Script</option>
                                                            <option value='georgia' <?php echo (!empty($existing_data["common"]["lp-font-family"]) && $existing_data["common"]["lp-font-family"] === "georgia") ? "selected" : ""; ?>>Georgia </option>
                                                            <option value='acme' <?php echo (!empty($existing_data["common"]["lp-font-family"]) && $existing_data["common"]["lp-font-family"] === "acme") ? "selected" : ""; ?>>Acme</option>
                                                        </select>
                                                    </div>
                                                    <hr class='custhr'>
                                                </div>
                                                <div class='maincontentbox'>
                                                    <h4 class='boxtitle'>Button style <span>
                                                            <i class='fa-solid fa-angle-down'></i></span>
                                                    </h4>
                                                    <div class='togglebox active'>

                                                        <!-- <div class='btnstyle'>
                                                            <button class='btn1'>Button</button>
                                                            <button class='btn1 btn2'>Button</button>
                                                        </div>
                                                        <div class='btnstyle'>
                                                            <button class='btnwithoutbg'>Button</button>
                                                            <button class='btnwithoutbg btn2'>Button</button>
                                                        </div> -->

                                                        <style>
                                                            
                                                            .btnstyle-option input {
                                                                display: none;
                                                            }

                                                            /* Layout */
                                                            .btnstyle-group {
                                                                display: flex;
                                                                gap: 20px;
                                                                margin-bottom: 20px;
                                                            }

                                                            /* DEFAULT button styles (use your existing classes) */
                                                            .btn1 {
                                                                background: #c2c7cc;
                                                                padding: 10px 30px;
                                                                border: none;
                                                                color: #fff;
                                                                font-weight: 600;
                                                                text-transform: uppercase;
                                                                cursor: pointer;
                                                                display: inline-block;
                                                            }

                                                            .btn1.btn2 {
                                                                border-radius: 40px;
                                                            }

                                                            .btnwithoutbg {
                                                                background: transparent;
                                                                border: 2px solid #000;
                                                                padding: 10px 30px;
                                                                font-weight: 600;
                                                                text-transform: uppercase;
                                                                cursor: pointer;
                                                                display: inline-block;
                                                            }

                                                            .btnwithoutbg.btn2 {
                                                                border-radius: 40px;
                                                            }

                                                            /* SELECTED state (when radio is checked) */
                                                            .btnstyle-option input:checked+span {
                                                                border: 2px solid;
                                                                border-color: #3a7afe !important;
                                                            }
                                                        </style>

                                                        <div class="btnstyle-group">
                                                            <label class="btnstyle-option">
                                                                <input type="radio" name="btn_style" value="square" onchange="updateButtonStyle('square')" <?php echo (!empty($existing_data["common"]["btn_style"]) && $existing_data["common"]["btn_style"] === "square") ? "checked" : ""; ?>>
                                                                <span class="btn1">Button</span>
                                                            </label>

                                                            <label class="btnstyle-option">
                                                                <input type="radio" name="btn_style" value="rounded" <?php echo (empty($existing_data["common"]["btn_style"]) || $existing_data["common"]["btn_style"] === "rounded") ? "checked" : ""; ?> onchange="updateButtonStyle('rounded')">
                                                                <span class="btn1 btn2">Button</span>
                                                            </label>
                                                        </div>

                                                        <div class="btnstyle-group">
                                                            <label class="btnstyle-option">
                                                                <input type="radio" name="btn_style" value="outline-square" onchange="updateButtonStyle('outline-square')" <?php echo (!empty($existing_data["common"]["btn_style"]) && $existing_data["common"]["btn_style"] === "outline-square") ? "checked" : ""; ?>>
                                                                <span class="btnwithoutbg">Button</span>
                                                            </label>

                                                            <label class="btnstyle-option">
                                                                <input type="radio" name="btn_style" value="outline-rounded" onchange="updateButtonStyle('outline-rounded')" <?php echo (!empty($existing_data["common"]["btn_style"]) && $existing_data["common"]["btn_style"] === "outline-rounded") ? "checked" : ""; ?>>
                                                                <span class="btnwithoutbg btn2">Button</span>
                                                            </label>
                                                        </div>

                                                        <h4 class='boxtitle'>Button Color</h4>
                                                        <p class='backgroundtxt'>Button color</p>
                                                        <div class='colorpicker'>
                                                            <div class='color-pair'>
                                                                <input type='text' name="lp-button-hexcolor"
                                                                    class='hexcolor lp-button-hexcolor' value='<?php echo !empty($existing_data["common"]["lp-button-hexcolor"]) ? esc_attr($existing_data["common"]["lp-button-hexcolor"]) : "#2f2f3f"; ?>'>
                                                                <input type='color' name="lp-button-color"
                                                                    class='colorpicker lp-button-color' value='<?php echo !empty($existing_data["common"]["lp-button-color"]) ? esc_attr($existing_data["common"]["lp-button-color"]) : "#2f2f3f"; ?>' onchange="updateButtonColor(this)">
                                                            </div>
                                                        </div>
                                                        <p class='backgroundtxt'>Button Text color</p>
                                                        <div class='colorpicker'>
                                                            <div class='color-pair'>
                                                                <input type='text' name="lp-button-text-hexcolor"
                                                                    class='hexcolor lp-button-text-hexcolor'
                                                                    value='<?php echo !empty($existing_data["common"]["lp-button-text-hexcolor"]) ? esc_attr($existing_data["common"]["lp-button-text-hexcolor"]) : "#ffffff"; ?>'>
                                                                <input type='color' name="lp-button-text-color"
                                                                    class='colorpicker lp-button-text-color'
                                                                    value='<?php echo !empty($existing_data["common"]["lp-button-text-color"]) ? esc_attr($existing_data["common"]["lp-button-text-color"]) : "#ffffff"; ?>' oninput="updateButtonTextColor(this)">
                                                            </div>
                                                        </div>
                                                        <div class='font-styling mt-2' id='font-styling'>
                                                            <label class='style-wrapper'>
                                                                <!-- <input type="checkbox" name="lp-button-text-style-bold" value="1" onchange="updateButtonTextSectionStyle(this, 'bold')" <?php echo (!empty($existing_data["common"]["lp-button-text-style-bold"]) && $existing_data["common"]["lp-button-text-style-bold"] === "1") ? "checked" : ""; ?>> -->
                                                                <span class="style-icon"><i class='fa-solid fa-bold'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <!-- <input type="checkbox" name="lp-button-text-style-underline" value="1" onchange="updateButtonTextSectionStyle(this, 'underline')" <?php echo (!empty($existing_data["common"]["lp-button-text-style-underline"]) && $existing_data["common"]["lp-button-text-style-underline"] === "1") ? "checked" : ""; ?>> -->
                                                                <span class="style-icon"><i class='fa-solid fa-underline'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <!-- <input type="checkbox" name="lp-button-text-style-italic" value="1" onchange="updateButtonTextSectionStyle(this, 'italic')" <?php echo (!empty($existing_data["common"]["lp-button-text-style-italic"]) && $existing_data["common"]["lp-button-text-style-italic"] === "1") ? "checked" : ""; ?>> -->
                                                                <span class="style-icon"><i class='fa-solid fa-italic'></i></span>
                                                            </label>
                                                           
                                                        </div>
                                                    </div>
                                                    <hr class='custhr'>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='tab cust-mt'>
                                            <div class='secondbtnrow'>
                                                <button type='button' data-step="Step1"
                                                    class='um-btn-wraper um-back-btn'>BACK</button>
                                                <button type='button' data-step="Step3"
                                                    class='um-btn-wraper um-next-btn'>NEXT STEP</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id='Step3' class='tabcontent'>
                                        <div class='cust-wraper'>
                                            <div class='um-inner-heading-row'>
                                                <div class='maincontentbox'>
                                                    <h4 class='boxtitle'>LINK PAGE URL <span>
                                                            <i class='fa-solid fa-angle-down'></i></span>
                                                    </h4>
                                                    <div class='togglebox active'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox' class="auto-generate-switch" name="lp-auto-generate" <?php echo (empty($existing_data["common"]["lp-auto-generate"]) || $existing_data["common"]["lp-auto-generate"] === "1") ? "checked" : ""; ?>>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Auto-generated</span>
                                                        </div>
                                                        <div class='translatemain'>
                                                            <input type='text' class='firsttxt' disabled placeholder='<?php echo home_url() . '/link-page'; ?>'>
                                                            <span class='divider'>/</span>
                                                            <div class="linkpage-custom-slug-container">
                                                                <input type='text' id="linkpage-custom-slug-url" name="linkpage-custom-slug-url" class='secondtxt' <?php echo (empty($existing_data["common"]["lp-auto-generate"]) || $existing_data["common"]["lp-auto-generate"] === "1") ? "disabled" : ""; ?> required placeholder='<?php echo !empty($existing_data["common"]["linkpage-custom-slug-url"]) ? esc_attr($existing_data["common"]["linkpage-custom-slug-url"]) : "(Auto generated)"; ?>' value='<?php echo !empty($existing_data["common"]["linkpage-custom-slug-url"]) ? esc_attr($existing_data["common"]["linkpage-custom-slug-url"]) : ""; ?>'>
                                                                <span id="link-page-slug-result"> </span>
                                                            </div>
                                                        </div>
                                                        <p class='subtitletxt'>LINK PAGE NAME</p>
                                                        <input type='text' name="lp-linkpage-name" class='secondinput'
                                                            placeholder='Name' value="<?php echo !empty($existing_data['common']['lp-linkpage-name']) ? esc_attr($existing_data['common']['lp-linkpage-name']) : 'LINK PAGE ' . date('m/d/Y'); ?>" required>
                                                    </div>
                                                    <hr class='custhr'>
                                                </div>
                                                <div class='maincontentbox'>
                                                    <h4 class='boxtitle'>Advanced settings <span>
                                                            <i class='fa-solid fa-angle-down'></i></span>
                                                    </h4>
                                                    <div class='togglebox'>
                                                        <h4 class='boxtitle'>Advanced location analytics</h4>
                                                        <div class='switchbox'>
                                                            <p class='switchsubtxt'>Enable GPS location analytics</p>
                                                            <label class='switch'>
                                                                <input type='checkbox' name="lp-enable-gps-analytics"
                                                                    <?php echo (empty($existing_data["common"]["lp-enable-gps-analytics"]) || $existing_data["common"]["lp-enable-gps-analytics"] === "1" || $existing_data["common"]["lp-enable-gps-analytics"] === "on") ? "checked" : ""; ?>>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Enable</span>
                                                        </div>
                                                        <h4 class='boxtitle'>Schedule</h4>
                                                        <div class='switchbox'>
                                                            <p class='switchsubtxt'>Set start and end dates for this
                                                                campaign</p>
                                                            <label class='switch'>
                                                                <input type='checkbox' name="lp-set-start-end-for-campaign"
                                                                    <?php echo (empty($existing_data["common"]["lp-set-start-end-for-campaign"]) || $existing_data["common"]["lp-set-start-end-for-campaign"] === "1" || $existing_data["common"]["lp-set-start-end-for-campaign"] === "on") ? "checked" : ""; ?>>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Enable</span>
                                                        </div>
                                                        <h4 class='boxtitle'>Password protection</h4>
                                                        <div class='switchbox'>
                                                            <p class='switchsubtxt'>Require a password to access content</p>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name="lp-require-password-to-access-content" <?php echo (empty($existing_data["common"]["lp-require-password-to-access-content"]) || $existing_data["common"]["lp-require-password-to-access-content"] === "1" || $existing_data["common"]["lp-require-password-to-access-content"] === "on") ? "checked" : ""; ?>>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Enable</span>
                                                        </div>
                                                        <h4 class='boxtitle'>Pass Parameters</h4>
                                                        <div class='switchbox'>
                                                            <p class='switchsubtxt'>Pass incoming parameters dynamically to
                                                                button URLs</p>
                                                            <label class='switch'>
                                                                <input type='checkbox' <?php echo (empty($existing_data["common"]["lp-pass-incomming-parameters"]) || $existing_data["common"]["lp-pass-incomming-parameters"] === "1" || $existing_data["common"]["lp-pass-incomming-parameters"] === "on") ? "checked" : ""; ?>
                                                                    name="lp-pass-incomming-parameters">
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Enable</span>
                                                        </div>
                                                    </div>
                                                    <hr class='custhr'>
                                                </div>
                                                <div class='maincontentbox'>
                                                    <h4 class='boxtitle'><span>Retargeting <span class='fa-solid fa-crown'
                                                                style='color: #ffd43b;'></span></span>
                                                        <span><i class='fa-solid fa-angle-down'></i></span>
                                                    </h4>
                                                    <div class='togglebox'>
                                                        <h4 class='boxtitle'>Facebook pixel</h4>
                                                        <p class='boxsubtitle'>The Facebook pixel is a piece of code for
                                                            your website that enables you to measure, optimize and build
                                                            audiences for your ad campaigns.</p>
                                                        <a href='' class='boxsmltxt'>Connect Facebook Pixel</a>
                                                        <h4 class='boxtitle'>Google Adwords</h4>
                                                        <p class='boxsubtitle'>With Google Remarketing, you can
                                                            strategically position your ads in front of people who viewed
                                                            this campaign as they browse Google or its partner websites,
                                                            thus helping you increase your brand awareness or remind those
                                                            audiences to make a purchase.
                                                        </p>
                                                        <a href='' class='boxsmltxt'>Connect Google AdWords</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='tab cust-mt'>
                                            <div class='secondbtnrow'>
                                                <button type='button' data-step="Step2"
                                                    class='um-btn-wraper um-back-btn'>BACK</button>
                                                <button type='submit' name="link-pages-submit" value="submit-page-link-form"
                                                    class='um-btn-wraper um-next-btn'>FINISH</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col4 mobilelayoutmain'>
                                    <div class='cust-wraper'>
                                        <div class='mobiledevice'>
                                            <style>
                                                .linkpage-header {
                                                    position: relative;
                                                    margin-top: -24px;
                                                    /* Offset parent padding */
                                                    margin-left: -24px;
                                                    margin-right: -24px;
                                                    padding: 24px 24px 16px 24px;
                                                }

                                                .linkpage-header::before {
                                                    content: '';
                                                    position: absolute;
                                                    top: 0;
                                                    left: 0;
                                                    right: 0;
                                                    height: 110px;
                                                    margin-left: 20px;
                                                    margin-right: 20px;
                                                    margin-top: 20px;
                                                    border-radius: 10px;
                                                    background: #E8E8E8;
                                                    background-repeat: no-repeat;
                                                    background-position: center center;
                                                    background-size: cover;
                                                    z-index: 0;
                                                }

                                                .linkpage-header-full {
                                                    position: relative;
                                                    margin-top: -24px;
                                                    /* Offset parent padding */
                                                    margin-left: -24px;
                                                    margin-right: -24px;
                                                    padding: 24px 24px 16px 24px;
                                                }

                                                .linkpage-header-full::before {
                                                    content: '';
                                                    position: absolute;
                                                    top: 0;
                                                    left: 0;
                                                    right: 0;
                                                    height: 110px;
                                                    padding-left: 20px;
                                                    padding-right: 20px;
                                                    padding-top: 20px;
                                                    border-radius: 24px 24px 0px 0px;
                                                    background: #E8E8E8;
                                                    background-repeat: no-repeat;
                                                    background-position: center center;
                                                    background-size: cover;
                                                    z-index: 0;
                                                }

                                                .linkpage-thumbnail,
                                                #linkpage-header-id p,
                                                #linkpage-header-id .btn {
                                                    position: relative;
                                                    z-index: 1;
                                                    margin-top: 70px;
                                                }
                                            </style>
                                            <div id='linkpage-id' data-linkpageid='' data-linkpagename='' class='content'
                                                style='overflow:auto;max-width: 300px;min-width: 180px;
                                            height: 55vh;
                                            max-height: 530px;
                                            min-height: 340px;-ms-overflow-style:none; scrollbar-width:none;'>
                                                <div class='linkpage-container' style='padding-left: 24px;padding-right: 24px;'>
                                                    <div class='' id="linkpage-header-id" style='margin-bottom: 16px'>

                                                        <div class='linkpage-thumbnail text-center'>
                                                            <div class="cover-page-section" style="width: 75px;height:70px; display:none;"></div>
                                                            <img class='img-box'
                                                                src='<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/default_profile.jpg' ?>'
                                                                width='75px' height='75px' data-default-url="<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/default_profile.jpg' ?>" style='border-radius: 50%' alt=''>
                                                        </div>

                                                        <!-- <p id = 'maintitle' style = 'color: #000000; font-size:24px; line-height:normal ; margin-top: 16px; margin-bottom: 16px' class = 'subtitle2 pre-line'>Stark Industries </p> -->
                                                        <!-- <p id = 'subtitle'    style = 'color: #000000; font-size:13px; line-height:normal ; margin-top: 16px; margin-bottom: 24px'  class = 'subtitle2 pre-line text-center'>  Changing the world for a better future</p> -->

                                                        <p id='inputtextboxTitle'
                                                            style='color: #000000; font-size:24px; line-height:normal ; margin-top: 16px; margin-bottom: 16px'
                                                            class='subtitle2 pre-line'> Stark Industries </p>

                                                        <p id='inputtextbox2Title'
                                                            style='color: #000000; font-size:13px; line-height:normal ; margin-top: 16px; margin-bottom: 24px'
                                                            class='subtitle2 pre-line'> Changing the world for a better future
                                                        </p>



                                                    </div>
                                                    <div class="linkpage-body">
                                                        <div class='btn rounded-filled' style="min-width: 100%;margin: 8px 4px;"
                                                            data-preview-for="buttontextfields_1">
                                                            <div class="icon-preview">
                                                                <img src="<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/empty-thumbnail.png' ?>" data-default-src="<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/empty-thumbnail.png' ?>" alt="">
                                                            </div>
                                                            <div class="thumbnail-preview">
                                                                <img src="<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/empty-thumbnail.png' ?>" data-default-src="<?php echo get_stylesheet_directory_uri() . '/assets/linkpages/images/empty-thumbnail.png' ?>" alt="">
                                                            </div>
                                                            <button
                                                                style='  position: relative; background: transparent;  width: 100%;; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border: none; color: #ffffff; font-size: 14px; padding: 0;'>Button
                                                                title</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class='widgetboxcontainer'>
                        <div class='cust-wraper cust-mt widgetbox button-text buttontextfields'
                            data-widget-counter="1" data-widget-type="button" id='buttontextfields'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-link'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <p class='subtxt'>Enter Title</p>
                                    <div class='togglewhitebox'>
                                        <div class='fortitle'>
                                            <input type='text' name="wg-w-link-title[]"
                                                placeholder='Enter title' oninput="updateAddButtonWidgetText(this)">
                                        </div>
                                        <div class='forurl'>
                                            <input type='text' name="wg-w-link-url[]"
                                                placeholder='Enter URL' oninput="updateAddButtonWidgetURL(this)">
                                        </div>
                                        <div class='radio-wrapper-main'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-link-type-no-img"
                                                        name='wg-w-link-type[]' value='No Images'
                                                        checked onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-link-type-no-img"></span>
                                                    <span for="lp-w-link-type-no-img">No Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-link-type[]'
                                                        value='Icons' id='lp-w-link-type-icon' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-link-type-icon"></span>
                                                    <span for="lp-w-link-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-link-type[]'
                                                        value='Thumbnail' id='lp-w-link-type-thumnail' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-link-type-thumnail"></span>
                                                    <span for='lp-w-link-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='cust-upload-box drop-area' id='drop-area'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up' style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-link-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-link-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;">
                                                <small>500x500 pixels. 5MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                <button type="button" class="remove-btn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox separator-field Separatorfield'
                            data-widget-counter="2" data-widget-type="seperator" id='Separatorfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-minus'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <p class='subtxt'>Separator</p>
                                    <div class='togglewhitebox'>
                                        <div class='twoparts'>
                                            <div>
                                                <p class='subtxt'>Color</p>
                                                <div class='color-pair' id='forseparator'>
                                                    <input type='text' name="wg-w-separator-hexcolor"
                                                        class='hexcolor lp-w-separator-hexcolor'
                                                        value='#000000'>
                                                    <input type='color' name="wg-w-separator-color"
                                                        class='colorpicker lp-w-separator-color'
                                                        value='#000000'>
                                                </div>
                                            </div>
                                            <div class='numbertext' id=''>
                                                <p class='subtxt'>Thickness</p>
                                                <input type='number' name="wg-w-separator-t-number[]">
                                            </div>
                                        </div>
                                        <div class='range'>
                                            <p class='subtxt'>Width</p>
                                            <input type='range' id="lp-w-separator-t-range"
                                                name="wg-w-separator-t-range[]">
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox link-page-field linkpagefield'
                            data-widget-counter="3" data-widget-type="link" id='linkpagefield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-earth-asia'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='fortitle'>
                                            <input type='text' name="wg-w-link-page-title[]"
                                                placeholder='Enter title' oninput="updateLinkpageButtonTitle(this)">
                                        </div>
                                        <div class='no-found'>
                                            <p class=''><i class='fa-solid fa-circle-info'
                                                    style='color: #ff445e; margin-right: 5px;'></i>No
                                                linkpages found</p>
                                        </div>
                                        <div class='radio-wrapper-main'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-link-page-type-no-img"
                                                        name='wg-w-link-page-type[]' value='No Images'
                                                        checked onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-link-page-type-no-img"></span>
                                                    <span for="lp-w-link-page-type-no-img">No
                                                        Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-link-page-type[]'
                                                        value='Icons' id='lp-w-link-page-type-icon' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-link-page-type-icon"></span>
                                                    <span for="lp-w-link-page-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-link-page-type[]'
                                                        value='Thumbnail'
                                                        id='lp-w-link-page-type-thumnail' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-link-page-type-thumnail"></span>
                                                    <span
                                                        for='lp-w-link-page-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='cust-upload-box drop-area' id='drop-area-linkpage-page'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                        style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-linkpage-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-linkpage-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;">
                                                <small>500x500 pixels. 5MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                <button type="button" class="remove-btn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox landing-page-field landingpagefield'
                            data-widget-counter="4" data-widget-type="landing" id='landingpagefield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-earth-asia'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='fortitle'>
                                            <input type='text' name="wg-w-lp-title[]"
                                                placeholder='Enter title'>
                                        </div>
                                        <div class='no-found'>
                                            <p class=''><i class='fa-solid fa-circle-info'
                                                    style='color: #ff445e; margin-right: 5px;'></i>No
                                                landing pages found</p>
                                        </div>
                                        <div class='radio-wrapper-main'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-landing-type-no-img"
                                                        name='wg-w-landing-type[]' value='No Images'
                                                        checked>
                                                    <span class='checkmark'
                                                        for="lp-w-landing-type-no-img"></span>
                                                    <span for="lp-w-landing-type-no-img">No
                                                        Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-landing-type[]'
                                                        value='Icons' id='lp-w-landing-type-icon'>
                                                    <span class='checkmark'
                                                        for="lp-w-landing-type-icon"></span>
                                                    <span for="lp-w-landing-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-landing-type[]'
                                                        value='Thumbnail'
                                                        id='lp-w-landing-type-thumnail'>
                                                    <span class='checkmark'
                                                        for="lp-w-landing-type-thumnail"></span>
                                                    <span
                                                        for='lp-w-landing-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='cust-upload-box' id='drop-area-landing-page'>
                                            <div class='icon'><i class='fa-solid fa-cloud-arrow-up'
                                                    style='color: #000'></i></div>
                                            <p> Drag file here or <label for='lp-w-landing-file'>Upload
                                                    file</label>
                                            </p>
                                            <input type='file' id='lp-w-landing-file'
                                                name="wg-w-landing-file[]" accept='image/*'>
                                            <small>500x500 pixels. 5MB max file size.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox form-field formfield'
                            data-widget-counter="5" data-widget-type="form" id='formfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-thumbs-up'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='fortitle'>
                                            <input type='text' placeholder='Enter title'
                                                name="wg-w-form-title[]">
                                        </div>
                                        <div class='no-found'>
                                            <p class=''><i class='fa-solid fa-circle-info'
                                                    style='color: #ff445e; margin-right: 5px;'></i>
                                                No Forms page found</p>
                                        </div>
                                        <div class='radio-wrapper-main'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-form-type-no-img"
                                                        name='wg-w-form-type[]' value='No Images'
                                                        checked>
                                                    <span class='checkmark'
                                                        for="lp-w-form-type-no-img"></span>
                                                    <span for="lp-w-form-type-no-img">No Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-form-type[]'
                                                        value='Icons' id='lp-w-form-type-icon'>
                                                    <span class='checkmark'
                                                        for="lp-w-form-type-icon"></span>
                                                    <span for="lp-w-form-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-form-type[]'
                                                        value='Thumbnail' id='lp-w-form-type-thumnail'>
                                                    <span class='checkmark'
                                                        for="lp-w-form-type-thumnail"></span>
                                                    <span for='lp-w-form-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='cust-upload-box' id='drop-area-form'>
                                            <div class='icon'>
                                                <i class='fa-solid fa-cloud-arrow-up'
                                                    style='color: #000'></i>
                                            </div>
                                            <p>
                                                Drag file here or
                                                <label for='lp-w-form-file'>Upload file</label>
                                            </p>
                                            <input type='file' id='lp-w-form-file' accept='image/*'
                                                name="wg-w-form-file[]">
                                            <small>500x500 pixels. 5MB max file size.</small>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox social-links sociallinkfield'
                            data-widget-counter="6" data-widget-type="social" id='sociallinkfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-hashtag'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <p class='subtxt'>Social Links</p>
                                    <div class='togglewhitebox'>
                                        <div class='twoparts'>
                                            <div class='numbertext' id=''>
                                                <p class='subtxt'>Headline (optional)</p>
                                                <input type='text' oninput="updateSocialLinkHeadline(this)" class="lp-w-sl-headline[]"
                                                    value="Connect with us!">
                                            </div>
                                            <div>
                                                <p class='subtxt'>Text Color</p>
                                                <div class='color-pair' id='forseparator'>
                                                    <input type='text'
                                                        name="wg-w-sl-headline-hexcolor[]"
                                                        class='hexcolor lp-w-sl-headline-hexcolor'
                                                        value='#000000'>
                                                    <input type='color' oninput="updateSocialLinkHeadlineColor(this)" name="wg-w-sl-headline-color[]"
                                                        class='colorpicker lp-w-sl-headline-color'
                                                        value='#000000'>
                                                </div>
                                            </div>
                                        </div>
                                        <div class='forbtn'>
                                            <p class='subtxt'>Select View</p>
                                            <div class="radio-group" id="social-view-radio">
                                                <label class="radio-wrapper">
                                                    <input type="radio" name="social-view-type"
                                                        value="icons" checked>
                                                    <span class="checkmark"></span>
                                                    <span>Icons</span>
                                                </label>
                                                <label class="radio-wrapper">
                                                    <input type="radio" name="social-view-type"
                                                        value="buttons">
                                                    <span class="checkmark"></span>
                                                    <span>Buttons</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='forsocialbtndiv cust-mt'>
                                            <p class='subtxt'>Add Social Links </p>
                                            <div class='forsocialbtns'>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/facebook-icon.png'
                                                        alt='Facebook' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/instagram-icon.png'
                                                        alt='instagram' class='socialimg'>
                                                </a>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/twitter-icon.png'
                                                        alt='twitter' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/linkedin-icon.png'
                                                        alt='linkedin' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/tiktok-icon.png'
                                                        alt='tik-tok' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/snapchat-icon.png'
                                                        alt='snapchat' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/whatsapp-icon.png'
                                                        alt='whatsapp' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/telegram-icon.png'
                                                        alt='telegram' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/vimeo-icon.png'
                                                        alt='vimeo' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/youtube-icon.png'
                                                        alt='youtube' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/wistia-icon.png'
                                                        alt='wistia' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/twitch-icon.png'
                                                        alt='twitch' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/discord-icon.png'
                                                        alt='discord' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/pinterest-icon.png'
                                                        alt='pinterest' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/yelp-icon.png'
                                                        alt='yelp' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/paypal-icon.png'
                                                        alt='paypal' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/venmo-icon.png'
                                                        alt='venmo' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/cashapp-icon.png'
                                                        alt='cashapp' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/calendly-icon.png'
                                                        alt='calendly' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/shopify-icon.png'
                                                        alt='shopify' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/github-icon.png'
                                                        alt='github' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/dribbble-icon.png'
                                                        alt='dribbble' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/behance-icon.png'
                                                        alt='behance' class='socialimg'>
                                                </a>
                                                <a class="socialimg" href="javascript:void(0);">
                                                    <img src='/wp-content/uploads/2025/10/plus-sign-icon.png'
                                                        alt='plus' class='socialimg'>
                                                </a>
                                            </div>
                                            <div class='socialicons-subcontainer'>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox text-box-field textboxfield'
                            data-widget-counter="7" data-widget-type="text-box" id='textboxfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-font'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <p class='subtxt'>Headline (optional)</p>
                                            <textarea class='textareafieldtxt'
                                                name="wg-w-text-headline[]" rows='4' cols='50'
                                                placeholder='Entertext' oninput="updateTextSectionInput(this)">Text content here</textarea>
                                        </div>
                                        <div class='cust-editerfild-row'>
                                            <div class='custcol4'>
                                                <div class='cust-innerwraper'>
                                                    <select name="wg-w-text-headline-font[]" onchange="updateTextSectionFont(this)">
                                                        <option value='normal'>Normal text</option>
                                                        <option value='h1'>Heading 1 ( 32px )</option>
                                                        <option value='h2'>Heading 2 ( 24px )</option>
                                                        <option value='h3'>Heading 3 ( 18px )</option>
                                                    </select>
                                                    <div class='color-pair' id='formainheading'>
                                                        <input type='text'
                                                            name="wg-w-text-headline-hexcolor[]"
                                                            class='hexcolor lp-title-text-hexcolor'
                                                            value='#000000'>
                                                        <input type='color'
                                                            name="wg-w-text-headline-color[]"
                                                            class='colorpicker lp-w-text-headline-color'
                                                            value='#000000' oninput="updateTextSectionColor(this)">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class='custcol4'>
                                                <div class='cust-innerwraper'>
                                                    <div class='text-styling'>
                                                        <div class='text-alignment cust-mt' id='text-alignment'>
                                                            <label class='style-wrapper'>
                                                                <input type="radio" name="wg-w-text-alignment[]" value="left" onchange="updateTextSectionAlign(this)" checked>
                                                                <span class="style-icon"><i class='fa-solid fa-align-left'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <input type="radio" name="wg-w-text-alignment[]" value="center" onchange="updateTextSectionAlign(this)">
                                                                <span class="style-icon"><i class='fa-solid fa-align-center'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <input type="radio" name="wg-w-text-alignment[]" value="right" onchange="updateTextSectionAlign(this)">
                                                                <span class="style-icon"><i class='fa-solid fa-align-right'></i></span>
                                                            </label>
                                                        </div>
                                                        <div class='font-styling cust-mt' id='font-styling'>
                                                            <label class='style-wrapper'>
                                                                <input type="checkbox" name="wg-w-text-style-bold[]" value="1" onchange="updateTextSectionStyle(this, 'bold')">
                                                                <span class="style-icon"><i class='fa-solid fa-bold'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <input type="checkbox" name="wg-w-text-style-underline[]" value="1" onchange="updateTextSectionStyle(this, 'underline')">
                                                                <span class="style-icon"><i class='fa-solid fa-underline'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <input type="checkbox" name="wg-w-text-style-italic[]" value="1" onchange="updateTextSectionStyle(this, 'italic')">
                                                                <span class="style-icon"><i class='fa-solid fa-italic'></i></span>
                                                            </label>
                                                            <label class='style-wrapper'>
                                                                <input type="checkbox" name="wg-w-text-style-strikethrough[]" value="1" onchange="updateTextSectionStyle(this, 'strikethrough')">
                                                                <span class="style-icon"><i class='fa-solid fa-strikethrough'></i></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class='custcol4'>
                                                <div class='cust-innerwraper'></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox embaded-video embadedfield'
                            data-widget-counter="8" data-widget-type="embaded" id='embadedfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-video'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <input type='text' placeholder='Enter the URL of your video'
                                                name="wg-w-embaded-video-title[]" oninput="updateEmbadedVideoURL(this)">
                                            <div class='switchbox'>
                                                <label class='switch'>
                                                    <input type='checkbox'
                                                        name="wg-w-embaded-video-mute[]" onchange="updateVideoMute(this)">
                                                    <span class='slider'></span>
                                                </label>
                                                <span class='label-text'>Mute Video</span>
                                            </div>
                                            <div class='switchbox'>
                                                <label class='switch'>
                                                    <input type='checkbox'
                                                        name="wg-w-embaded-video-autoplay[]" onchange="updateVideoAutoPlay(this)">
                                                    <span class='slider'></span>
                                                </label>
                                                <span class='label-text'>Autoplay</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox img-gallery imggalleryfield'
                            data-widget-counter="9" data-widget-type="img-gallery" id='imggalleryfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-images'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='cust-upload-box active drop-area-gallary' id='drop-area-gallary'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                        style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-gallary-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-gallary-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;">
                                                <small>500x500 pixels. 5MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <!-- <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                                    <button type="button" class="remove-btn">Remove</button> -->
                                            </div>
                                        </div>
                                        <div class='radio-wrapper-main'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-gallery-type-carousel"
                                                        name='wg-w-gallery-type[]' value='Carousel'
                                                        onchange="updateGallaryLayout(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-gallery-type-carousel"></span>
                                                    <span
                                                        for="lp-w-gallery-type-carousel">Carousel</span>
                                                </label>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-gallery-type-grid"
                                                        name='wg-w-gallery-type[]' value='grid' checked onchange="updateGallaryLayout(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-gallery-type-grid"></span>
                                                    <span for="lp-w-gallery-type-grid">Grid</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox email-field emailfield'
                            data-widget-counter="10" data-widget-type="email" id='emailfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-envelope'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <input type='text' name="wg-w-email-button-title[]"
                                                placeholder='Button Title' oninput="updateEmailButtonTitle(this)">
                                            <input name="wg-w-email-receiver[]" type='text'
                                                class='cust-mt' placeholder='Receiver Email'>
                                            <input class='cust-mt' type='text'
                                                placeholder='Subject' name="wg-w-email-subject[]">
                                            <input class='cust-mt' type='text' placeholder='Body'
                                                name="wg-w-email-body[]">
                                        </div>
                                        <div class='radio-wrapper-main cust-mt'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-email-type-no-img"
                                                        name='wg-w-email-type[]' value='No Images'
                                                        checked onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-email-type-no-img"></span>
                                                    <span for="lp-w-email-type-no-img">No Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-email-type[]'
                                                        value='Icons' id='lp-w-email-type-icon' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-email-type-icon"></span>
                                                    <span for="lp-w-email-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-email-type[]'
                                                        value='Thumbnail' id='lp-w-email-type-thumnail' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-email-type-thumnail"></span>
                                                    <span
                                                        for='lp-w-email-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='cust-upload-box drop-area' id='drop-area-email'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                        style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-email-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-email-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;" data-file-key="thumbnail">
                                                <small>500x500 pixels. 5MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                <button type="button" class="remove-btn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox call-field callfield'
                            data-widget-counter="11" data-widget-type="call" id='callfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-phone'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <input type='text' name="wg-w-call-button-title[]"
                                                placeholder='Button Title' oninput="updateCallButtonTitle(this)">
                                            <input type='text' name="wg-w-call-phone-number[]"
                                                placeholder='Enter phone number'>
                                        </div>
                                        <div class='radio-wrapper-main cust-mt'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-call-type-no-img"
                                                        name='wg-w-call-type[]' value='No Images'
                                                        checked onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-call-type-no-img"></span>
                                                    <span for="lp-w-call-type-no-img">No Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-call-type[]'
                                                        value='Icons' id='lp-w-call-type-icon' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-call-type-icon"></span>
                                                    <span for="lp-w-call-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-call-type[]'
                                                        value='Thumbnail' id='lp-w-call-type-thumnail' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-call-type-thumnail"></span>
                                                    <span for='lp-w-call-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class='cust-upload-box drop-area' id='drop-area-call'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                        style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-call-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-call-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;" data-file-key="thumbnail">
                                                <small>500x500 pixels. 5MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                <button type="button" class="remove-btn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox sms-field smsfield'
                            data-widget-counter="12" data-widget-type="sms" id='smsfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-comment-sms'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <input type='text' name="wg-w-sms-button-title[]"
                                                placeholder='Enter button title' oninput="updateSendMessageButtonTitle(this)">
                                            <input type='text' name="wg-w-sms-phone-number[]"
                                                placeholder='Enter phone number'>
                                            <input type='text' name="wg-w-sms-msg[]"
                                                placeholder='Enter message'>
                                        </div>
                                        <div class='radio-wrapper-main cust-mt'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' id="lp-w-sms-type-no-img"
                                                        name='wg-w-sms-type[]' value='No Images'
                                                        checked onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-sms-type-no-img"></span>
                                                    <span for="lp-w-sms-type-no-img">No Images</span>
                                                </label>
                                                <!-- <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-sms-type[]'
                                                        value='Icons' id='lp-w-sms-type-icon' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-sms-type-icon"></span>
                                                    <span for="lp-w-sms-type-icon">Icons</span>
                                                </label> -->
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-sms-type[]'
                                                        value='Thumbnail' id='lp-w-sms-type-thumnail' onchange="updateButtonWidgetPreviewType(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-sms-type-thumnail"></span>
                                                    <span for='lp-w-sms-type-thumnail'>Thumbnail</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class='cust-upload-box drop-area' id='drop-area-message'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                        style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-sms-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-sms-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;" data-file-key="thumbnail">
                                                <small>500x500 pixels. 5MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                <button type="button" class="remove-btn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class='cust-wraper cust-mt widgetbox pdf-field uploadpdffield'
                            data-widget-counter="13" data-widget-type="upload-pdf" id='uploadpdffield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-file-pdf'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <input type='text' name="wg-w-upload-pdf-title[]"
                                                placeholder='Enter title' value="PDF Document" oninput="updatePDFWidgetButtonText(this)">
                                        </div>
                                        <!-- <div class='cust-upload-box' id='drop-area-pdf'>
                                                                <div class='icon'><i class='fa-solid fa-cloud-arrow-up'
                                                                        style='color: #000'></i></div>
                                                                <p>
                                                                    Drag file here or
                                                                    <label for='lp-w-upload-pdf-file'>Upload file</label>
                                                                </p>
                                                                <input type='file' id='lp-w-upload-pdf-file'
                                                                    name="wg-w-upload-pdf-file[]" accept='image/*'>
                                                                <small>500x500 pixels. 5MB max file size.</small>
                                                            </div> -->
                                        <div class='cust-upload-box active drop-area-pdf'>
                                            <div class="upload-placeholder">
                                                <div class='icon'>
                                                    <i class='fa-solid fa-cloud-arrow-up'
                                                        style='color: #000'></i>
                                                </div>
                                                <p>
                                                    Drag file here or
                                                    <label for='lp-w-pdf-file'>Upload file</label>
                                                </p>
                                                <input type='file' class="upload-input" id='lp-w-pdf-file'
                                                    name="wg-w-link-file[]" accept='image/*'
                                                    style="display:none;">
                                                <small>50MB max file size.</small>
                                            </div>
                                            <!-- Preview Section -->
                                            <div class="cover-preview" style="display:none;">
                                                <img id="coverPreviewImg45" class="preview-img" src="" alt="Preview" />
                                                <button type="button" class="remove-btn">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox location-field locationfield'
                            data-widget-counter="14" data-widget-type="location" id='locationfield'
                            style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-font'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <!-- <p class = 'subtxt'>Social Links</p> -->
                                    <div class='togglewhitebox'>
                                        <div class='numbertext' id=''>
                                            <p class='subtxt'>Headline (optional)</p>
                                            <input type='text' name="wg-w-location-headline[]"
                                                placeholder='Search Google Maps' class="location-search-input">
                                            <div class="map-container" style="width: 100%; height: 450px;"></div>
                                            <input type="hidden" class="location-lat" name="wg-w-location-lat[]">
                                            <input type="hidden" class="location-lng" name="wg-w-location-lng[]">
                                            <p class='subtxt'>Address</p>
                                            <input type='text' class="location-address" name="wg-w-location-address[]"
                                                placeholder='Enter Address or Search on Google Maps above' onchange="updateLocationAddress(this)">
                                            <div class='switchbox'>
                                                <label class='switch'>
                                                    <input type='checkbox'
                                                        name="wg-w-location-show-map[]" onchange="enableMapPreview(this)">
                                                    <span class='slider'></span>
                                                </label>
                                                <span class='label-text'>Show Map</span>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class='cust-wraper cust-mt widgetbox business-hours businesshoursfield'
                            data-widget-counter="15" data-widget-type="business-hours"
                            id='businesshoursfield' style='display: none;'>
                            <div class='um_contenter-inner-row'>
                                <div class='cust-col2'>
                                    <div class='linkicons movewidget'>
                                        <i class='fa-solid fa-up-down-left-right'></i>
                                    </div>
                                    <div class='linkicons'>
                                        <i class='fa-solid fa-clock'></i>
                                    </div>
                                </div>
                                <div class='cust-col10'>
                                    <p class='subtxt'>Business Hours </p>
                                    <div class='togglewhitebox'>
                                        <p class='subtxt'>Set hours for</p>
                                        <!-- <div class='forbtn cust-mt'>
                                                            <button type='button' class='active'>Opening / Closing</button>
                                                            <button type='button'>Online delivery</button>
                                                            <button type='button'>Check-in / Check-out</button>
                                                        </div> -->
                                        <div class='radio-wrapper-main'>
                                            <div class='radio-group'>
                                                <label class='radio-wrapper'>
                                                    <input type='radio'
                                                        id="lp-w-bh-timezone-type-opening-closing"
                                                        name='wg-w-bh-timezone-type[]'
                                                        value='opening-closing' checked onchange="updateBusinessHoursTitle(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-bh-timezone-type-opening-closing"></span>
                                                    <span
                                                        for="lp-w-bh-timezone-type-opening-closing">Opening
                                                        / Closing</span>
                                                </label>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-bh-timezone-type[]'
                                                        value='online-delivery'
                                                        id='lp-w-bh-timezone-type--online-delivery' onchange="updateBusinessHoursTitle(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-bh-timezone-type-online-delivery"></span>
                                                    <span
                                                        for="lp-w-bh-timezone-type--online-delivery">Online
                                                        delivery</span>
                                                </label>
                                                <label class='radio-wrapper'>
                                                    <input type='radio' name='wg-w-bh-timezone-type[]'
                                                        value='check-in-checkout'
                                                        id='lp-w-bh-timezone-type-checkin-checkout' onchange="updateBusinessHoursTitle(this)">
                                                    <span class='checkmark'
                                                        for="lp-w-bh-timezone-type-checkin-checkout"></span>
                                                    <span
                                                        for='lp-w-bh-timezone-type-checkin-checkout'>Check-in
                                                        / Check-out</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class='cust-mt'>
                                            <p class='subtxt'>Timezone </p>
                                        </div>
                                        <div style='width: 100%'>
                                            <select class='timezone-selection' id='timezone-selection'
                                                name='wg-w-bh-timezones[]'>
                                                <optgroup label="US Timezones">
                                                    <option value="America/New_York">Eastern Time (US & Canada)</option>
                                                    <option value="America/Chicago">Central Time (US & Canada)</option>
                                                    <option value="America/Denver">Mountain Time (US & Canada)</option>
                                                    <option value="America/Phoenix">Arizona</option>
                                                    <option value="America/Los_Angeles">Pacific Time (US & Canada)</option>
                                                    <option value="America/Anchorage">Alaska</option>
                                                    <option value="Pacific/Honolulu">Hawaii</option>
                                                </optgroup>
                                                <option disabled>────────────────────────</option>
                                                <optgroup label="Africa">
                                                    <option value="Africa/Abidjan">Abidjan</option>
                                                    <option value="Africa/Accra">Accra</option>
                                                    <option value="Africa/Addis_Ababa">Addis Ababa</option>
                                                    <option value="Africa/Algiers">Algiers</option>
                                                    <option value="Africa/Cairo">Cairo</option>
                                                    <option value="Africa/Casablanca">Casablanca</option>
                                                    <option value="Africa/Johannesburg">Johannesburg</option>
                                                    <option value="Africa/Lagos">Lagos</option>
                                                    <option value="Africa/Nairobi">Nairobi</option>
                                                </optgroup>
                                                <optgroup label="America">
                                                    <option value="America/Argentina/Buenos_Aires">Buenos Aires</option>
                                                    <option value="America/Bogota">Bogota</option>
                                                    <option value="America/Caracas">Caracas</option>
                                                    <option value="America/Halifax">Halifax</option>
                                                    <option value="America/Lima">Lima</option>
                                                    <option value="America/Mexico_City">Mexico City</option>
                                                    <option value="America/Santiago">Santiago</option>
                                                    <option value="America/Sao_Paulo">Sao Paulo</option>
                                                    <option value="America/St_Johns">St. John's</option>
                                                    <option value="America/Toronto">Toronto</option>
                                                    <option value="America/Vancouver">Vancouver</option>
                                                </optgroup>
                                                <optgroup label="Asia">
                                                    <option value="Asia/Baghdad">Baghdad</option>
                                                    <option value="Asia/Bangkok">Bangkok</option>
                                                    <option value="Asia/Dubai">Dubai</option>
                                                    <option value="Asia/Hong_Kong">Hong Kong</option>
                                                    <option value="Asia/Jakarta">Jakarta</option>
                                                    <option value="Asia/Jerusalem">Jerusalem</option>
                                                    <option value="Asia/Karachi">Karachi</option>
                                                    <option value="Asia/Calcutta">Kolkata</option>
                                                    <option value="Asia/Kuwait">Kuwait</option>
                                                    <option value="Asia/Manila">Manila</option>
                                                    <option value="Asia/Riyadh">Riyadh</option>
                                                    <option value="Asia/Seoul">Seoul</option>
                                                    <option value="Asia/Shanghai">Shanghai</option>
                                                    <option value="Asia/Singapore">Singapore</option>
                                                    <option value="Asia/Tehran">Tehran</option>
                                                    <option value="Asia/Tokyo">Tokyo</option>
                                                </optgroup>
                                                <optgroup label="Australia">
                                                    <option value="Australia/Adelaide">Adelaide</option>
                                                    <option value="Australia/Brisbane">Brisbane</option>
                                                    <option value="Australia/Darwin">Darwin</option>
                                                    <option value="Australia/Melbourne">Melbourne</option>
                                                    <option value="Australia/Perth">Perth</option>
                                                    <option value="Australia/Sydney">Sydney</option>
                                                </optgroup>
                                                <optgroup label="Europe">
                                                    <option value="Europe/Amsterdam">Amsterdam</option>
                                                    <option value="Europe/Athens">Athens</option>
                                                    <option value="Europe/Belgrade">Belgrade</option>
                                                    <option value="Europe/Berlin">Berlin</option>
                                                    <option value="Europe/Brussels">Brussels</option>
                                                    <option value="Europe/Budapest">Budapest</option>
                                                    <option value="Europe/Copenhagen">Copenhagen</option>
                                                    <option value="Europe/Dublin">Dublin</option>
                                                    <option value="Europe/Helsinki">Helsinki</option>
                                                    <option value="Europe/Istanbul">Istanbul</option>
                                                    <option value="Europe/Lisbon">Lisbon</option>
                                                    <option value="Europe/London">London</option>
                                                    <option value="Europe/Madrid">Madrid</option>
                                                    <option value="Europe/Moscow">Moscow</option>
                                                    <option value="Europe/Paris">Paris</option>
                                                    <option value="Europe/Prague">Prague</option>
                                                    <option value="Europe/Rome">Rome</option>
                                                    <option value="Europe/Stockholm">Stockholm</option>
                                                    <option value="Europe/Vienna">Vienna</option>
                                                    <option value="Europe/Warsaw">Warsaw</option>
                                                    <option value="Europe/Zurich">Zurich</option>
                                                </optgroup>
                                                <optgroup label="Pacific">
                                                    <option value="Pacific/Auckland">Auckland</option>
                                                    <option value="Pacific/Fiji">Fiji</option>
                                                    <option value="Pacific/Guam">Guam</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <div class='timezoecheckboxmain'>
                                            <div class='labeltime'>
                                                <input type='checkbox' id='sametimezone1'
                                                    name='wg-w-bh-same-for-all-days[]'
                                                    value='sametimezone' onchange="updateSameForAllDays(this)">
                                                <label for='sametimezone1'>Same for all days</label><br>
                                            </div>
                                            <div class="timezoneweekdays">
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Monday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-monday-hours[]' value="monday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            name='wg-w-bh-monday-hours-start[]'
                                                            placeholder='9:00 AM' onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            name='wg-w-bh-monday-hours-end[]'
                                                            placeholder='6:00 PM' onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Tuesday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-tuesday-hours[]' value="tuesday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            name="wg-w-bh-tuesday-hours-start[]"
                                                            placeholder='9:00 AM' onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            name="wg-w-bh-tuesday-hours-end[]"
                                                            placeholder='6:00 PM' onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Wednesday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-wednesday-hours[]' value="wednesday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            placeholder='9:00 AM'
                                                            name="wg-w-bh-wednesday-hours-start[]" onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            placeholder='6:00 PM'
                                                            name="wg-w-bh-wednesday-hours-end[]" onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Thursday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-thursday-hours[]' value="thursday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            name="wg-w-bh-thursday-hours-start[]"
                                                            placeholder='9:00 AM' onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            name="wg-w-bh-thursday-hours-end[]"
                                                            placeholder='6:00 PM' onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Friday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-friday-hours' value="friday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            name="wg-w-bh-friday-hours-start[]"
                                                            placeholder='9:00 AM' onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            placeholder='6:00 PM'
                                                            name="wg-w-bh-friday-hours-end[]" onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Saturday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-saturday-hours' value="saturday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            name="wg-w-bh-saturday-hours-start[]"
                                                            placeholder='9:00 AM' onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            name="wg-w-bh-saturday-hours-end[]"
                                                            placeholder='6:00 PM' onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>Sunday</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-sunday-hours' value="sunday" onchange="updateBusinessTimeToggle(this)" checked>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            placeholder='9:00 AM'
                                                            name='wg-w-bh-sunday-hours-start[]' onchange="updateBusinessTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            placeholder='6:00 PM'
                                                            name='wg-w-bh-sunday-hours-end[]' onchange="updateBusinessTimeEnd(this)">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="timezoneallweeks" style="display: none;">
                                                <div class='timezoecheckbox'>
                                                    <div class='time-day'>
                                                        <p class='datetxt'>All Week</p>
                                                    </div>
                                                    <div class='openclose'>
                                                        <div class='switchbox'>
                                                            <label class='switch'>
                                                                <input type='checkbox'
                                                                    name='wg-w-bh-week-hours[]' value="week" checked disabled>
                                                                <span class='slider'></span>
                                                            </label>
                                                            <span class='label-text'>Open</span>
                                                        </div>
                                                    </div>
                                                    <div class='timestartinputbox'>
                                                        <input class='timestartinput' type='text'
                                                            name='wg-w-bh-week-hours-start[]'
                                                            placeholder='9:00 AM' onchange="updateAllWeekTimeStart(this)">
                                                    </div>
                                                    <div class='hyphentxt'>-</div>
                                                    <div class='timeendinputbox'>
                                                        <input class='timeendinput' type='text'
                                                            name='wg-w-bh-week-hours-end[]'
                                                            placeholder='6:00 PM' onchange="updateAllWeekTimeEnd(this)">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class='cust-col2 second'>
                                    <div class='linkicons deletebox'>
                                        <i class='fa-solid fa-trash' style='color: #fa0000;'></i>
                                    </div>
                                    <div class='linkicons togglearrow'>
                                        <i class='fa-solid fa-angle-up'></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            $('#Step3 .auto-generate-switch').on('change', function() {
                if (!$(this).is(':checked')) {
                    $('#linkpage-custom-slug-url').removeAttr('disabled');
                } else {
                    $('#linkpage-custom-slug-url').attr('disabled', 'disabled');
                    $('#linkpage-custom-slug-url').attr('placeholder', '(Auto generated)');
                }
            });
            $("#Step3 #linkpage-custom-slug-url").on("blur", function() {
                let slug = $(this).val();

                $.ajax({
                    url: qrAjax.ajax_url,
                    type: "POST",
                    data: {
                        action: "check_slug_exists",
                        slug: slug,
                        post_type: "link_page"
                    },
                    success: function(response) {
                        $("#link-page-slug-result").html(response);
                    }
                });

            });

        });
    </script>

    <?php
    // At the end of your form submission button, add edit_id if in edit mode
    if ($eid > 0) {
        echo '<input type="hidden" name="eid" value="' . esc_attr($eid) . '">';
    }

    $output .= ob_get_contents();
    ob_end_clean();
    return $output;
}

