<?php
add_shortcode('qr_listing_data', 'um_account_content_hook_qr_code');

add_filter('um_account_content_hook_qr_code', 'um_account_content_hook_qr_code');
function um_account_content_hook_qr_code($output)
{
    if (!is_user_logged_in()) return $output;
    $output = '';
    $user_id   = get_current_user_id();
     $current_user = wp_get_current_user();
    $user_name = $current_user->display_name;
    $paged     = isset($_GET['qr_page']) ? intval($_GET['qr_page']) : 1;
    $per_page  = 5; // QR codes per page

    $args = [
        'post_type'      => 'qr_code',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'author'         => $user_id,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $qr_query = new WP_Query($args);

    ob_start(); ?>

    <div class="um-field">
        <div class="topbar">
            <div class="user-info">
                <h4>Hi <?php echo esc_html($user_name); ?>,</h4>
                <p>View and manage your QR codes.</p>
            </div>
        </div>

        <div class="content">
            <div class="performance card">



                <?php if (!$qr_query->have_posts()) : ?>
                    <!-- No QR Codes -->
                    <div class="no-or-code mt-20 mb-20 card text-center">
                        <h2>No QR Code Found</h2>
                        <p>Looks like you haven't created any QR code yet!</p>
                        <a href="/membership-account/create_qr_code" class="btn card-btn-base">Create QR Codes</a>
                    </div>
                <?php else : ?>
                    <!-- Toolbar -->
                    <div class="filers-top-bar tool-bar">
                        <div class="row justify-between">
                            <div class="inner-wrapper col-md-6">
                                <div class="col-md-12 d-flex justify-content-start gap-2 align-items-center">
                                    <p class="m-0 selected-items"><span class="count selected_count_wrap">0</span> selected</p>
                                    <a href="javascript:void(0);" class="btn card-btn-base deselect_all_wrap ">De-Select All</a>
                                    <a href="javascript:void(0);" class="btn card-btn-base select_all_wrap">Select All</a>
                                </div>
                            </div>
                            <div class="inner-wrapper col-md-6">
                                <div class="col-md-12 d-flex justify-content-end gap-2">
                                    <a href="javascript:void(0);" class="btn card-btn-base delete_all_wrap">Delete</a>
                                    <a href="javascript:void(0);" class="btn card-btn-base export_all_btn">Export</a>
                                    <a href="javascript:void(0);" class="btn card-btn-base all_qr_download_btn">Download</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Tabs -->
                    <div class="toggle-layouts cust_layouts_wrapper">
                        <ul class="nav nav-tabs qr_layout_wrapper" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="grid-tab" data-bs-toggle="tab" data-bs-target="#grid"
                                    type="button" role="tab" aria-controls="grid" aria-selected="true">Grid</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list"
                                    type="button" role="tab" aria-controls="list" aria-selected="false">List</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="myTabContent">
                            <!-- Grid View -->
                            <div class="tab-pane fade show active" id="grid" role="tabpanel" aria-labelledby="grid-tab">
                                <div class="gird-layout">
                                    <?php while ($qr_query->have_posts()) : $qr_query->the_post();
                                        $post_id      = get_the_ID();
                                        $qr_data      = get_post_meta($post_id, 'qr_data', true);
                                        $scan_count   = get_post_meta($post_id, 'qr_scan_count', true);
                                        $qr_user_id   = get_post_meta($post_id, 'qr_user_id', true);
                                        $qr_user_name = get_post_meta($post_id, 'qr_user_name', true);
                                        $qr_type      = get_post_meta($post_id, 'qr_type', true);
                                        $qr_value     = get_post_meta($post_id, 'qr_value', true);
                                        $qr_image     = get_post_meta($post_id, 'qr_image', true);
                                        $qr_color  = get_post_meta($post_id, 'qr_color', true);
                                        $qr_shape  = get_post_meta($post_id, 'qr_shape', true);
                                        $qr_logo   = get_post_meta($post_id, 'qr_logo', true);
                                        $qr_value  = get_post_meta($post_id, 'qr_value', true);
                                        $is_static = get_post_meta($post_id, 'qr_is_static', true);


                                    ?>
                                        <div class="inner-card-row grid_layout_wrapper" data-id="<?php echo $post_id; ?>"
                                            data-qr="<?php echo esc_attr($qr_value); ?>"
                                            data-color="<?php echo esc_attr($qr_color); ?>"
                                            data-shape="<?php echo esc_attr($qr_shape); ?>"
                                            data-logo="<?php echo esc_attr($qr_logo); ?>"
                                            data-url="<?php echo esc_url(get_post_meta($post_id, 'qr_scan_url', true)); ?>">

                                            <div class="check-img-box">
                                                <input type="checkbox">
                                                <img class="list_qr_image_wrapper" src="<?php echo $qr_image; ?>" alt="QR Code">
                                            </div>
                                            <div class="details">
                                                <strong><?php the_title(); ?> <?php echo get_the_date('m/d/Y'); ?></strong>
                                                <p><span class="label_wrap">Link: </span><a href="<?php echo $qr_data; ?>"><span><?php echo esc_html($qr_value); ?></span></a></p>
                                                <p><span class="label_wrap">Org: </span><?php echo esc_html($qr_user_name); ?></p>
                                                <p><span class="label_wrap">Created: </span><?php echo get_the_date('M d Y'); ?></p>
                                                <p><span class="label_wrap">Labels: </span><?php echo esc_html($qr_type); ?></p>
                                                <p class="scan_wrap"><span class="label_wrap">Scan<?php echo intval($scan_count) > 1 ? 's' : ''; ?>: </span><?php echo intval($scan_count); ?></p>
                                            </div>
                                            <div class="record-view">
                                                <?php if ($is_static !== '1') : ?>
                                                    <p><strong><?php echo intval($scan_count); ?></strong> Scan<?php echo intval($scan_count) > 1 ? 's' : ''; ?></p>
                                                <?php else : ?>
                                                    <p class="muted">Non Trackable</p>
                                                <?php endif; ?>
                                                <!-- <a href="javascript:void(0)">View Analytics</a> -->
                                            </div>
                                            <div class="actions">
                                                <a href="<?php echo site_url('/membership-account/create_qr_code?edit_id=' . $post_id); ?>"
                                                    class="btn card-btn-base edit_qr_wrap">
                                                    Edit
                                                </a>

                                                <a href="javascript:void(0);" class="btn card-btn-base delete">Delete</a>
                                                <div class="download-wrapper">
                                                    <a href="javascript:void(0);" class="btn card-btn-base download_qr_btn">Download</a>

                                                    <!-- <select class="qr-format-select">
                                                        <option value="png">PNG</option>
                                                        <option value="jpeg">JPEG</option>
                                                        <option value="svg">SVG</option>
                                                        <option value="pdf">PDF</option>
                                                        <option value="eps">EPS</option>
                                                    </select>

                                                    <button class="btn card-btn-base qr-format-download download_btn">Download</button>
                                                    <button class="btn card-btn-base qr-format-cancel cancel_wrap cancel_btn">Cancel</button> -->
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>

                                    <!-- Pagination -->
                                    <div class="pagination mt-3 ajax-pagination">
                                        <?php
                                        echo paginate_links([
                                            'total'   => $qr_query->max_num_pages,
                                            'current' => $paged,
                                            'format'  => '?qr_page=%#%',
                                            'prev_text' => '« Prev',
                                            'next_text' => 'Next »',
                                        ]);
                                        ?>
                                    </div>
                                </div>
                            </div>

                            <!-- List View -->
                            <div class="tab-pane fade list_layout_wrapper" id="list" role="tabpanel" aria-labelledby="list-tab">
                                <div class="list-layout">
                                    <div class="table-responsive">
                                        <table class="table table-bordered align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th><input type="checkbox"></th>
                                                    <th></th>
                                                    <th>Name</th>
                                                    <th>Created</th>
                                                    <th>Labels</th>
                                                    <th>Scans</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Loop again for list view
                                                $qr_query->rewind_posts();
                                                while ($qr_query->have_posts()) : $qr_query->the_post();
                                                    $post_id      = get_the_ID();
                                                    $scan_count   = get_post_meta($post_id, 'qr_scan_count', true);
                                                    $qr_user_name = get_post_meta($post_id, 'qr_user_name', true);
                                                    $qr_type      = get_post_meta($post_id, 'qr_type', true);
                                                    $qr_value     = get_post_meta($post_id, 'qr_value', true);
                                                    $qr_image     = get_post_meta($post_id, 'qr_image', true);
                                                    $qr_color  = get_post_meta($post_id, 'qr_color', true);
                                                    $qr_shape  = get_post_meta($post_id, 'qr_shape', true);
                                                    $qr_logo   = get_post_meta($post_id, 'qr_logo', true);
                                                    $qr_value  = get_post_meta($post_id, 'qr_value', true);
                                                    $is_static = get_post_meta($post_id, 'qr_is_static', true); ?>
                                                    <tr data-id="<?php echo $post_id; ?>" data-qr="<?php echo esc_attr($qr_value); ?>"
                                                        data-color="<?php echo esc_attr($qr_color); ?>"
                                                        data-shape="<?php echo esc_attr($qr_shape); ?>"
                                                        data-logo="<?php echo esc_attr($qr_logo); ?>"
                                                        data-url="<?php echo esc_url(get_post_meta($post_id, 'qr_scan_url', true)); ?>">

                                                        <td><input type="checkbox"></td>
                                                        <td> <img src="<?php echo $qr_image; ?>" alt="QR Code" class="qr-img me-2 list_qr_image_wrapper" alt="QR Code" style="width: 15%;"></td>
                                                        <td><a href="<?php echo esc_url($qr_value); ?>"><?php the_title(); ?></a></td>
                                                        <td><?php echo get_the_date('M d, Y'); ?></td>
                                                        <td><?php echo esc_html($qr_type); ?></td>
                                                        <?php if ($is_static !== '1') : ?>
                                                            <td><?php echo intval($scan_count); ?></td>
                                                        <?php else : ?>
                                                            <td class="muted">Non Trackable</td>
                                                        <?php endif; ?>
                                                        <td class="table-actions text-center">

                                                            <div class="list_download-wrapper">
                                                                <a href="<?php echo site_url('/membership-account/create_qr_code?edit_id=' . $post_id); ?>"
                                                                    class="table-action-icon edit_qr_wrap">
                                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                                </a>
                                                                <div class="download-wrapper">
                                                                    <a href="javascript:void(0)" class="table-action-icon download_qr_btn"><i class="fa-solid fa-download"></i></a>
                                                                    <!-- <select class="qr-format-select">
                                                                        <option value="png">PNG</option>
                                                                        <option value="jpeg">JPEG</option>
                                                                        <option value="svg">SVG</option>
                                                                        <option value="pdf">PDF</option>
                                                                        <option value="eps">EPS</option>
                                                                    </select>

                                                                    <button class="btn card-btn-base qr-format-download download_btn">Download</button>
                                                                    <button class="btn card-btn-base qr-format-cancel cancel_wrap cancel_btn">Cancel</button> -->
                                                                </div>
                                                                <a href="javascript:void(0)" class="table-action-icon delete"><i class="fa-solid fa-trash"></i></a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                        <!-- Pagination for list view -->
                                        <div class="pagination mt-3 ajax-pagination">
                                            <?php
                                            echo paginate_links([
                                                'total'   => $qr_query->max_num_pages,
                                                'current' => $paged,
                                                'format'  => '?qr_page=%#%',
                                                'prev_text' => '« Prev',
                                                'next_text' => 'Next »',
                                            ]);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
            <div id="qrExportModal" style="display:none;">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <h4>Bulk download</h4>
                    <p>Note: This action will be applied to <span id="qr-count"></span> QR codes</p>

                    <label>File type</label>
                    <select id="qr-file-type">
                        <option value="svg">SVG</option>
                        <option value="png">PNG</option>
                        <option value="jpeg">JPEG</option>
                        <option value="pdf">PDF</option>
                    </select>
                    <div class="cust_btn_wrap">
                        <button id="qr-export-confirm" class="btn card-btn-base">Download</button>
                        <button id="qr-export-cancel" class="btn card-btn-base cancel_wrap">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php
    wp_reset_postdata();
    $output .= ob_get_clean();
    return $output;
}





// Delete selected QR codes
add_action('wp_ajax_delete_qr_codes', 'delete_qr_codes');
function delete_qr_codes()
{
    if (!is_user_logged_in() || empty($_POST['qr_ids'])) wp_send_json_error('Unauthorized');

    $qr_ids = array_map('intval', $_POST['qr_ids']);
    foreach ($qr_ids as $id) {
        wp_delete_post($id, true);
    }

    wp_send_json_success('QR Codes deleted');
}

// Export selected QR codes as CSV via AJAX
add_action('wp_ajax_export_qr_codes', 'export_qr_codes_ajax');
function export_qr_codes_ajax()
{
    if (!is_user_logged_in() || empty($_POST['qr_ids'])) wp_send_json_error('Unauthorized');

    $qr_ids = array_map('intval', $_POST['qr_ids']);
    if (empty($qr_ids)) wp_send_json_error('No QR codes selected');

    $csv_data = "Title,Image URL,QR Value,User,Type,Scan Count\n";

    foreach ($qr_ids as $id) {
        $title     = get_the_title($id);
        $img_url   = get_post_meta($id, 'qr_image', true); // image URL
        $qr_value  = get_post_meta($id, 'qr_value', true); // QR code value
        $user      = get_post_meta($id, 'qr_user_name', true);
        $type      = get_post_meta($id, 'qr_type', true);
        $count     = get_post_meta($id, 'scan_count', true);

        $csv_data .= "\"$title\",\"$img_url\",\"$qr_value\",\"$user\",\"$type\",\"$count\"\n";
    }

    wp_send_json_success(['csv' => $csv_data]);
}

add_action('wp_ajax_download_qr_codes', 'download_qr_codes_ajax');
function download_qr_codes_ajax()
{

    check_ajax_referer('qr_nonce', '_wpnonce');

    if (empty($_POST['images'])) {
        wp_send_json_error('No QR codes selected');
    }

    $images = $_POST['images'];
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'png';

    if (!class_exists('ZipArchive')) {
        wp_send_json_error('ZipArchive is not available on this server.');
    }

    // Create ZIP
    $zip_path = tempnam(sys_get_temp_dir(), 'qr_zip_') . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        wp_send_json_error('Could not create ZIP file.');
    }

    // Load FPDF for PDF exporting
    if ($format === 'pdf') {
        if (!class_exists('FPDF')) {
            require_once get_stylesheet_directory() . '/fpdf/fpdf.php';
        }
    }


    foreach ($images as $img) {

        $id      = intval($img['id']);

        // Get HD image if exists
        $hd = get_post_meta($id, 'qr_image_hd', true);
        $img_url = $hd ?: esc_url_raw($img['url']);

        $content = @file_get_contents($img_url);
        if (!$content) continue;

        $output_ext = $format;
        $output_content = $content;

        if ($format === 'svg') {

            $output_content =
                '<svg xmlns="http://www.w3.org/2000/svg" width="1500" height="1500">'
                . '<image href="data:image/png;base64,' . base64_encode($content) . '" width="1500" height="1500"/></svg>';
        } elseif (in_array($format, ['jpeg', 'png'])) {

            $image = imagecreatefromstring($content);
            ob_start();

            // KEEP HD SIZE (no resizing)
            if ($format === 'jpeg') {
                imagejpeg($image, null, 100);
            } else {
                imagepng($image, null, 9);
            }

            $output_content = ob_get_clean();
            imagedestroy($image);
        } elseif ($format === 'pdf') {

            // FPDF must be loaded locally
            require_once get_stylesheet_directory() . '/fpdf/fpdf.php';

            $pdf_path = tempnam(sys_get_temp_dir(), 'qr_') . '.pdf';
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->Image($img_url, 10, 10, 180, 180);
            $pdf->Output('F', $pdf_path);

            $zip->addFile($pdf_path, "QR_{$id}.pdf");
            unlink($pdf_path);
            continue;
        }

        $zip->addFromString("QR_{$id}.{$output_ext}", $output_content);
    }


    $zip->close();

    // Send file
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="qr-codes.zip"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    unlink($zip_path);

    exit;
}

add_action('wp_ajax_load_qr_pagination', 'load_qr_pagination');
function load_qr_pagination()
{
    if (!is_user_logged_in()) wp_send_json_error('Unauthorized');

    $user_id   = get_current_user_id();
    $paged     = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page  = 5;
    $layout    = isset($_POST['layout']) ? sanitize_text_field($_POST['layout']) : 'grid';

    $args = [
        'post_type'      => 'qr_code',
        'posts_per_page' => $per_page,
        'paged'          => $paged,
        'author'         => $user_id,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ];

    $qr_query = new WP_Query($args);
    ob_start();
    // ---------- GRID VIEW ----------
    if ($layout === 'grid') : ?>
        <div class="gird-layout">
            <?php if ($qr_query->have_posts()) : ?>
                <?php while ($qr_query->have_posts()) : $qr_query->the_post();
                    $post_id      = get_the_ID();
                    $qr_data      = get_post_meta($post_id, 'qr_data', true);
                    $scan_count   = get_post_meta($post_id, 'qr_scan_count', true);
                    $qr_user_name = get_post_meta($post_id, 'qr_user_name', true);
                    $qr_type      = get_post_meta($post_id, 'qr_type', true);
                    $qr_value     = get_post_meta($post_id, 'qr_value', true);
                    $qr_image     = get_post_meta($post_id, 'qr_image', true);
                    $qr_color  = get_post_meta($post_id, 'qr_color', true);
                    $qr_shape  = get_post_meta($post_id, 'qr_shape', true);
                    $qr_logo   = get_post_meta($post_id, 'qr_logo', true);
                    $qr_value  = get_post_meta($post_id, 'qr_value', true);
                    $is_static = get_post_meta($post_id, 'qr_is_static', true); ?>
                    <div class="inner-card-row grid_layout_wrapper" data-id="<?php echo $post_id; ?>"
                        data-qr="<?php echo esc_attr($qr_value); ?>"
                        data-color="<?php echo esc_attr($qr_color); ?>"
                        data-shape="<?php echo esc_attr($qr_shape); ?>"
                        data-logo="<?php echo esc_attr($qr_logo); ?>"
                        data-url="<?php echo esc_url(get_post_meta($post_id, 'qr_scan_url', true)); ?>">
                        <div class="check-img-box">
                            <input type="checkbox">
                            <img class="list_qr_image_wrapper" src="<?php echo esc_url($qr_image); ?>" alt="QR Code">
                        </div>
                        <div class="details">
                            <strong><?php the_title(); ?> <?php echo get_the_date('m/d/Y'); ?></strong>
                            <p>Link: <a href="<?php echo esc_url($qr_data); ?>"><span><?php echo esc_html($qr_value); ?></span></a></p>
                            <p>Org: <?php echo esc_html($qr_user_name); ?></p>
                            <p>Created: <?php echo get_the_date('M d Y'); ?></p>
                            <p>Labels: <?php echo esc_html($qr_type); ?></p>
                        </div>
                        <div class="record-view">
                            <?php if ($is_static !== '1') : ?>
                                <p><strong><?php echo intval($scan_count); ?></strong> Scan<?php echo intval($scan_count) > 1 ? 's' : ''; ?></p>
                            <?php else : ?>
                                <p class="muted">Non Trackable</p>
                            <?php endif; ?>
                        </div>
                        <div class="actions">
                            <a href="<?php echo site_url('/membership-account/create_qr_code?edit_id=' . $post_id); ?>"
                                class="btn card-btn-base edit_qr_wrap">
                                Edit
                            </a>

                            <a href="javascript:void(0);" class="btn card-btn-base delete">Delete</a>
                            <div class="download-wrapper">
                                <a href="javascript:void(0);" class="btn card-btn-base download_qr_btn">Download</a>

                                <!-- <select class="qr-format-select">
                                    <option value="png">PNG</option>
                                    <option value="jpeg">JPEG</option>
                                    <option value="svg">SVG</option>
                                    <option value="pdf">PDF</option>
                                    <option value="eps">EPS</option>
                                </select>

                                <button class="btn card-btn-base qr-format-download download_btn">Download</button>
                                <button class="btn card-btn-base qr-format-cancel cancel_wrap cancel_btn">Cancel</button> -->
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>

                <div class="pagination mt-3 ajax-pagination">
                    <?php
                    echo paginate_links([
                        'total'   => $qr_query->max_num_pages,
                        'current' => $paged,
                        'format'  => '?qr_page=%#%',
                        'prev_text' => '« Prev',
                        'next_text' => 'Next »',
                    ]);
                    ?>
                </div>
            <?php else : ?>
                <div class="no-or-code card text-center">
                    <h2>No QR Code Found</h2>
                </div>
            <?php endif; ?>
        </div>

    <?php
    // ---------- LIST VIEW ----------
    else : ?>
        <div class="list-layout">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox"></th>
                            <th></th>
                            <th>Name</th>
                            <th>Created</th>
                            <th>Labels</th>
                            <th>Scans</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($qr_query->have_posts()) :
                            while ($qr_query->have_posts()) : $qr_query->the_post();
                                $post_id      = get_the_ID();
                                $scan_count   = get_post_meta($post_id, 'scan_count', true);
                                $qr_user_name = get_post_meta($post_id, 'qr_user_name', true);
                                $qr_type      = get_post_meta($post_id, 'qr_type', true);
                                $qr_value     = get_post_meta($post_id, 'qr_value', true);
                                $qr_image     = get_post_meta($post_id, 'qr_image', true);
                                $qr_color  = get_post_meta($post_id, 'qr_color', true);
                                $qr_shape  = get_post_meta($post_id, 'qr_shape', true);
                                $qr_logo   = get_post_meta($post_id, 'qr_logo', true);
                                $qr_value  = get_post_meta($post_id, 'qr_value', true);
                                $is_static = get_post_meta($post_id, 'qr_is_static', true);

                        ?>
                                <tr data-id="<?php echo $post_id; ?>" data-qr="<?php echo esc_attr($qr_value); ?>"
                                    data-color="<?php echo esc_attr($qr_color); ?>"
                                    data-shape="<?php echo esc_attr($qr_shape); ?>"
                                    data-logo="<?php echo esc_attr($qr_logo); ?>"
                                    data-url="<?php echo esc_url(get_post_meta($post_id, 'qr_scan_url', true)); ?>">
                                    <td><input type="checkbox"></td>
                                    <td><img src="<?php echo esc_url($qr_image); ?>" class="qr-img" style="width: 50px;"></td>
                                    <td><a href="<?php echo esc_url($qr_value); ?>"><?php the_title(); ?></a></td>
                                    <td><?php echo get_the_date('M d, Y'); ?></td>
                                    <td><?php echo esc_html($qr_type); ?></td>
                                    <?php if ($is_static !== '1') : ?>
                                        <td><?php echo intval($scan_count); ?></td>
                                    <?php else : ?>
                                        <td class="muted">Non Trackable</td>
                                    <?php endif; ?>
                                    <td class="table-actions text-center">
                                        <div class="list_download-wrapper">
                                            <a href="<?php echo site_url('/membership-account/create_qr_code?edit_id=' . $post_id); ?>"
                                                class="table-action-icon edit_qr_wrap">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <div class="download-wrapper">
                                                <a href="javascript:void(0)" class="table-action-icon download_qr_btn"><i class="fa-solid fa-download"></i></a>
                                                <!-- <select class="qr-format-select">
                                                    <option value="png">PNG</option>
                                                    <option value="jpeg">JPEG</option>
                                                    <option value="svg">SVG</option>
                                                    <option value="pdf">PDF</option>
                                                    <option value="eps">EPS</option>
                                                </select>

                                                <button class="btn card-btn-base qr-format-download download_btn">Download</button>
                                                <button class="btn card-btn-base qr-format-cancel cancel_wrap cancel_btn">Cancel</button> -->
                                            </div>
                                            <a href="javascript:void(0)" class="table-action-icon delete"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else : ?>
                            <tr>
                                <td colspan="7" class="text-center">No QR Codes Found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="pagination mt-3 ajax-pagination">
                    <?php
                    echo paginate_links([
                        'total'   => $qr_query->max_num_pages,
                        'current' => $paged,
                        'format'  => '?qr_page=%#%',
                        'prev_text' => '« Prev',
                        'next_text' => 'Next »',
                    ]);
                    ?>
                </div>
            </div>
        </div>
<?php
    endif;

    wp_reset_postdata();
    wp_send_json_success(['html' => ob_get_clean()]);
}
