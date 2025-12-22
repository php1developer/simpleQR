<?php
add_action('wp_enqueue_scripts', function () {
    // QR Code Styling
    wp_enqueue_script('qr-code-styling', 'https://cdn.jsdelivr.net/npm/qr-code-styling@1.5.0/lib/qr-code-styling.js', array(), '1.5.0', true);

    // jsPDF
    wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], null, true);
    wp_enqueue_script('html2pdf', 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js', [], null, true);
    wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array(), '2.5.1', true);

    // Bootstrap Bundle (includes Popper)
    wp_enqueue_script('bootstrap-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.2', true);
    // JSZip
    wp_enqueue_script('qr-jszip', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js', [], '3.11.0', true);
    // main QR dashboard script
    wp_enqueue_script('create-qr-dashboard', get_stylesheet_directory_uri() . '/js/create_qr_code.js', ['jquery', 'qr-jszip', 'qr-filesaver'], NULL, true);

    // FileSaver.js (needed for ZIP download)
    wp_enqueue_script(
        'qr-filesaver',
        'https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js',
        ['qr-jszip'],
        '2.0.5',
        true
    );

    // main QR dashboard script
    wp_enqueue_script(
        'qr-dashboard',
        get_stylesheet_directory_uri() . '/js/listing_qr-code.js',
        ['jquery', 'qr-jszip', 'qr-filesaver'],
        '1.0',
        true
    );
    $qr_post_id = (is_singular('qr_code')) ? get_the_ID() : 0;
    wp_localize_script('qr-dashboard', 'qr_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('qr_nonce'),
        'qrPostId' => $qr_post_id,
    ]);


    /*
    * QR Analytics scripts
    */

    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    wp_enqueue_script('chartjs-matrix', 'https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@1.3.0/dist/chartjs-chart-matrix.min.js', ['chartjs'], null, true);

    wp_enqueue_script(
        'simpleqr-analytics',
        get_stylesheet_directory_uri() . '/js/qr_analytics.js',
        ['chartjs', 'chartjs-matrix'],
        filemtime(get_stylesheet_directory() . '/js/qr_analytics.js'),
        true
    );
});
/*
* Dynamic QR code redirection Frontend not for the QR Dashboard
*/
add_action('init', function () {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?qr_key=$matches[1]', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'qr_key';
    return $vars;
});

/*
* Redirect dynamic QR codes
*/

add_action('template_redirect', 'qr_handle_redirect');
function qr_handle_redirect()
{
    $shortcode = get_query_var('qr_code');
    if (!$shortcode) return;

    global $wpdb;
    $redirects = $wpdb->prefix . 'qr_redirects';
    $scans = $wpdb->prefix . 'qr_scans';

    $destination = $wpdb->get_var($wpdb->prepare(
        "SELECT destination FROM $redirects WHERE shortcode = %s",
        $shortcode
    ));

    if ($destination) {
        $wpdb->insert($scans, [
            'shortcode' => $shortcode,
            'scanned_at' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        wp_redirect($destination);
        exit;
    } else {
        wp_die('Invalid or expired QR code.', 'QR Code Error', ['response' => 404]);
    }
}

// Create required database tables
function qr_create_tables()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $redirects = $wpdb->prefix . 'qr_redirects';
    $scans = $wpdb->prefix . 'qr_scans';

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta("CREATE TABLE IF NOT EXISTS $redirects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shortcode VARCHAR(50) NOT NULL UNIQUE,
        destination TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;");

    dbDelta("CREATE TABLE IF NOT EXISTS $scans (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shortcode VARCHAR(50) NOT NULL,
        scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45)
    ) $charset_collate;");
}
add_shortcode('static_qr_generator', function ($atts) {
    $atts = shortcode_atts(['type' => 'static'], $atts);
    ob_start();
    $type = $atts['type'];
    include get_stylesheet_directory() . '/templates/qr-generator.php';
    return ob_get_clean();
});

add_shortcode('dynamic_qr_generator', function ($atts) {
    $atts = shortcode_atts(['type' => 'dynamic'], $atts);
    ob_start();
    $type = $atts['type'];
    include get_stylesheet_directory() . '/templates/dynamic-qr-generator.php';
    return ob_get_clean();
});

// AJAX handler for dynamic QR
add_action('wp_ajax_generate_dynamic_qr', 'my_generate_dynamic_qr');
add_action('wp_ajax_nopriv_generate_dynamic_qr', 'my_generate_dynamic_qr');

function my_generate_dynamic_qr()
{
    global $wpdb;

    // Define temp directory inside uploads
    $upload_dir = wp_upload_dir();
    $temp_dir   = trailingslashit($upload_dir['basedir']) . 'temp-pdfs/';
    $temp_url   = trailingslashit($upload_dir['baseurl']) . 'temp-pdfs/';


    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }

    $table = $wpdb->prefix . 'qr_redirects';

    // Case 1: PDF upload 
    if (!empty($_FILES['pdf_file'])) {
        $file = $_FILES['pdf_file'];
        $filename = sanitize_file_name($file['name']);
        $unique_name = time() . '_' . $filename;
        $target_file = $temp_dir . $unique_name;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $pdf_url = $temp_url . $unique_name;

            // Generate unique key (shortcode)
            $hash = md5($pdf_url . time());
            $shortcode  = substr($hash, 0, 6);


            update_option('qr_dynamic_' . $shortcode, $pdf_url);

            $wpdb->insert($table, [
                'shortcode'    => $shortcode,
                'destination'  => $pdf_url,
                'created_at'   => current_time('mysql')
            ]);

            $short_url = home_url("/go/$shortcode");

            wp_send_json_success(['url' => $short_url]);
        } else {
            wp_send_json_error(['message' => 'PDF upload failed']);
        }
    }

    // Case 2: Normal text / URL / email / etc.
    $qr_data = isset($_POST['qr_data']) ? sanitize_text_field($_POST['qr_data']) : '';

    if (empty($qr_data)) {
        wp_send_json_error(['message' => 'No data provided']);
    }

    $hash = md5($qr_data . time());
    $shortcode  = substr($hash, 0, 6);

    update_option('qr_dynamic_' . $shortcode, $qr_data);

    // Store in custom table
    $wpdb->insert($table, [
        'shortcode'    => $shortcode,
        'destination'  => $qr_data,
        'created_at'   => current_time('mysql')
    ]);

    $short_url = home_url("/go/$shortcode");

    wp_send_json_success(['url' => $short_url]);
}



add_action('init', function () {
    if (isset($_GET['qr_redirect'])) {
        $key = sanitize_text_field($_GET['qr_redirect']);
        $stored = get_option('qr_dynamic_' . $key);

        if ($stored) {
            wp_redirect($stored);
            exit;
        }

        // If not found, go to homepage
        wp_redirect(home_url());
        exit;
    }
});
/*
* handle_pdf_upload for dynamic QR code
*/
add_action('wp_ajax_upload_pdf_and_return_url', 'handle_pdf_upload');
add_action('wp_ajax_nopriv_upload_pdf_and_return_url', 'handle_pdf_upload');

function handle_pdf_upload()
{
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    // Define temp directory inside uploads
    $upload_dir = wp_upload_dir();
    $temp_dir   = trailingslashit($upload_dir['basedir']) . 'temp-pdfs/';
    $temp_url   = trailingslashit($upload_dir['baseurl']) . 'temp-pdfs/';

    // Create temp folder if not exists
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }

    // Generate safe file name
    $file_name = sanitize_file_name($_FILES['pdf_file']['name']);
    $target    = $temp_dir . $file_name;

    // Move uploaded file
    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target)) {
        wp_send_json_success(['url' => $temp_url . $file_name]);
    } else {
        wp_send_json_error(['message' => 'Could not save file.']);
    }
}
/*
* handle_upload_pdf_qr for dynamic QR code
*/
add_action('wp_ajax_upload_pdf_qr', 'handle_upload_pdf_qr');
add_action('wp_ajax_nopriv_upload_pdf_qr', 'handle_upload_pdf_qr');

function handle_upload_pdf_qr()
{
    check_ajax_referer('qr_pdf_nonce', 'security');

    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    // Define temp directory inside uploads
    $upload_dir = wp_upload_dir();
    $temp_dir   = trailingslashit($upload_dir['basedir']) . 'temp-pdfs/';
    $temp_url   = trailingslashit($upload_dir['baseurl']) . 'temp-pdfs/';

    // Create temp folder if not exists
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }

    // Generate safe file name
    $file_name = sanitize_file_name($_FILES['pdf_file']['name']);
    $target    = $temp_dir . $file_name;

    // Move uploaded file
    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target)) {
        wp_send_json_success(['url' => $temp_url . $file_name]);
    } else {
        wp_send_json_error(['message' => 'Could not save file.']);
    }
}
/*
* Delete PDf for dynamic QR code
*/
function cleanup_temp_pdfs_auto()
{
    $upload_dir = wp_upload_dir();
    $temp_dir   = trailingslashit($upload_dir['basedir']) . 'temp-pdfs/';
    $log_file   = trailingslashit($upload_dir['basedir']) . 'temp-pdfs/cleanup-log.txt';

    if (!file_exists($temp_dir)) return;

    // Get last run time 
    $last_run = get_option('cleanup_temp_pdfs_last_run', 0);
    $now = time();

    // Only run if 5 minutes have passed 
    if (($now - $last_run) < 5 * 60) return;

    $files = glob($temp_dir . '*.pdf');
    $expire_time = $now - (24 * 60 * 60); // Delete files older than 24 hours

    if ($files) {
        foreach ($files as $file) {
            if (filemtime($file) < $expire_time) {
                if (@unlink($file)) {
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ✅ Deleted: $file\n", FILE_APPEND);
                } else {
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ❌ Failed to delete: $file\n", FILE_APPEND);
                }
            }
        }
    } else {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] ℹ️ No PDF files found.\n", FILE_APPEND);
    }

    // Update last run timestamp
    update_option('cleanup_temp_pdfs_last_run', $now);
}

add_action('init', 'cleanup_temp_pdfs_auto');

// Optional: manual run via URL
add_action('init', function () {
    if (isset($_GET['run_cleanup']) && current_user_can('manage_options')) {
        cleanup_temp_pdfs_auto();
        wp_die('Manual cleanup executed. Check cleanup-log.txt for details.');
    }
});

/* add new tab called "overview" */

// Reorder Ultimate Member account tabs
add_filter('um_account_page_default_tabs_hook', 'my_um_reorder_tabs', 100);
function my_um_reorder_tabs($tabs)
{
    // --- Custom tabs first ---
    $tabs[80]['overview'] = array(
        'icon'   => 'um-icon-document',
        'title'  => 'Overview',
        'custom' => true,
    );

    $tabs[82]['analytics'] = array(
        'icon'   => 'um-icon-ios-analytics',
        'title'  => 'Analytics',
        'custom' => true,
    );

    $tabs[83]['qr_code'] = array(
        'icon'   => 'um-faicon-qrcode',
        'title'  => 'QR Codes',
        'custom' => true,
    );

    $tabs[84]['create_qr_code'] = array(
        'icon'   => 'um-icon-qr-scanner',
        'title'  => 'Create QR Codes',
        'custom' => true,
    );

    $tabs[85]['linkpage'] = array(
        'icon'   => 'um-faicon-external-link',
        'title'  => 'Link Pages',
        'custom' => true,
    );
    $tabs[86]['linkpage_analytics'] = array(
        'icon'   => 'um-faicon-external-link',
        'title'  => 'Link Pages Analytics',
        'custom' => true,
    );

    // --- Push UM profile/account tabs to bottom ---
    $tabs[900]['general']       = $tabs[100]['general'];       // Account
    $tabs[910]['password']      = $tabs[200]['password'];      // Change Password
    $tabs[920]['privacy']       = $tabs[300]['privacy'];       // Privacy
    $tabs[930]['notifications'] = $tabs[400]['notifications']; // Notifications
    $tabs[940]['delete']        = $tabs[99999]['delete'];      // Delete Account

    ksort($tabs);
    return $tabs;
}

add_filter('um_account_page_default_tabs_hook', 'my_custom_tab_in_um', 100);
function my_custom_tab_in_um($tabs)
{
    $tabs[800]['overview']['icon'] = 'um-icon-document';
    $tabs[800]['overview']['title'] = 'Overview';
    $tabs[800]['overview']['custom'] = true;
    $tabs[800]['analytics']['icon'] = 'um-icon-ios-analytics';
    $tabs[800]['analytics']['title'] = 'Analytics';
    $tabs[800]['analytics']['custom'] = true;
    $tabs[800]['qr_code']['icon'] = 'um-faicon-qrcode';
    $tabs[800]['qr_code']['title'] = 'QR Codes';
    $tabs[800]['qr_code']['custom'] = true;
    $tabs[800]['create_qr_code']['icon'] = 'um-icon-qr-scanner';
    $tabs[800]['create_qr_code']['title'] = 'Create QR Codes';
    $tabs[800]['create_qr_code']['custom'] = true;
    $tabs[800]['linkpage']['icon'] = 'um-faicon-external-link';
    $tabs[800]['linkpage']['title'] = 'Link Pages';
    $tabs[800]['linkpage']['custom'] = true;
    $tabs[800]['linkpage_analytics']['icon'] = 'um-icon-ios-analytics';
    $tabs[800]['linkpage_analytics']['title'] = 'Link Pages Analytics';
    $tabs[800]['linkpage_analytics']['custom'] = true;
    return $tabs;
}

/* make our new tab hookable */

add_action('um_account_tab__overview', 'um_account_tab__overview');
function um_account_tab__overview($info)
{
    global $ultimatemember;
    extract($info);

    $output = $ultimatemember->account->get_tab_output('overview');
    if ($output) {
        echo $output;
    }
    $output = $ultimatemember->account->get_tab_output('analytics');
    if ($output) {
        echo $output;
    }
    $output = $ultimatemember->account->get_tab_output('qr_code');
    if ($output) {
        echo $output;
    }
    $output = $ultimatemember->account->get_tab_output('create_qr_code');
    if ($output) {
        echo $output;
    }
    $output = $ultimatemember->account->get_tab_output('linkpage');
    if ($output) {
        echo $output;
    }
    $output = $ultimatemember->account->get_tab_output('linkpage_analytics');
    if ($output) {
        echo $output;
    }
}
add_action("init", function () {
    register_post_type("social_preview", [
        "label" => "Social Previews",
        "public" => true,
        "show_ui" => true,
        "supports" => ["title", "custom-fields"]
    ]);
});

add_filter("template_include", function ($template) {
    if (is_page("social-preview") && isset($_GET["social_preview_id"])) {
        return get_stylesheet_directory() . "/page-social-preview.php";
    }
    return $template;
});


function enqueue_linkpage_datatables()
{
    // DataTables CSS
    wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', [], null);
    wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], null);
    wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', [], null);
    // DataTables JS
    wp_enqueue_script('datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);
    wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], null, true);
    wp_enqueue_script('google-maps-api','https://maps.googleapis.com/maps/api/js?key=AIzaSyAHuJ3q7yB3xvpd5cgXNSDN0ulR-3KFGIY&libraries=places',array('jquery'),null,true);
}
add_action('wp_enqueue_scripts', 'enqueue_linkpage_datatables');


/**
 * Dashboard Sidebar Tabs with Icons
 */

/**
 * PMPro Dashboard Tabs Shortcode
 */
function dashboard_tabs_shortcode() {

    if (!is_user_logged_in() || !pmpro_hasMembershipLevel()) {
        return '<p>You must be logged in to view this page.</p>';
    }

    $allowed_tabs = ['overview','analytics','qr_code','create_qr_code','create_linkpage','linkpage_analytics','linkpage','Account'];

    $active_tab = get_query_var('account_tab');
    $active_tab = in_array($active_tab, $allowed_tabs) ? $active_tab : 'overview';

    ob_start();
    ?>
    <div class="dashboard-wrapper">

        <!-- SIDEBAR -->
        <aside class="dashboard-sidebar">

            <div class="profile-box">
                <?php echo do_shortcode('[pmpro_member field="avatar"]'); ?>
                <?php echo do_shortcode('[pmpro_member field="display_name"]'); ?>
            </div>

            <ul class="sidebar-nav">
                <?php
                $tabs = [
                    'overview' => ['Overview', 'fa-regular fa-file-lines', '/membership-account/overview'],
                    'analytics' => ['QR Code Analytics', 'fa-solid fa-chart-pie', '/membership-account/analytics/'],
                    'qr_code' => ['QR Codes', 'fa-solid fa-qrcode', '/membership-account/qr_code/'],
                    'create_qr_code' => ['Create QR Codes', 'fa-solid fa-expand', '/membership-account/create_qr_code/'],
                    'linkpage' => ['Linkpages', 'fa-solid fa-chart-line', '/membership-account/linkpage/'],
                    'create_linkpage' => ['Create Linkpages', 'fa-solid fa-expand', '/membership-account/create_linkpage/'],
                    'linkpage_analytics' => ['Linkpage Analytics', 'fa-solid fa-chart-pie', '/membership-account/linkpage_analytics/'],
                    'account' => ['Account', 'fa-solid fa-user', '/membership-account'],
                ];
                foreach ($tabs as $key => $tab) : ?>
                    <li class="<?php echo $active_tab === $key ? 'active' : ''; ?>"
                        data-tab="<?php echo esc_attr($key); ?>"
                        data-url="<?php echo esc_url(site_url($tab[2])); ?>">
                        <a href="<?php echo esc_url(site_url($tab[2])); ?>">
                            <i class="<?php echo esc_attr($tab[1]); ?>"></i>
                            <?php echo esc_html($tab[0]); ?>
                            <span class="sidebar-nav-arrow">
                                <i class="fa-solid fa-angle-right"></i>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>

            </ul>
        </aside>

        <!-- CONTENT -->
        <main class="dashboard-content">

            <div class="dashboard-pane <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" id="overview">
                <?php echo do_shortcode('[qr_overview_data]'); ?>
            </div>

            <div class="dashboard-pane <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>" id="analytics">
                <?php echo do_shortcode('[qr_analytics_data]'); ?>
            </div>

            <div class="dashboard-pane <?php echo $active_tab === 'qr_code' ? 'active' : ''; ?>" id="qr_code">
                <?php echo do_shortcode('[qr_listing_data]'); ?>
            </div>

            <div class="dashboard-pane <?php echo $active_tab === 'create_qr_code' ? 'active' : ''; ?>" id="create_qr_code">
                <?php echo do_shortcode('[create_qr_codes]'); ?>
            </div>
            <div class="dashboard-pane <?php echo $active_tab === 'linkpage' ? 'active' : ''; ?>" id="linkpage">
                <?php echo do_shortcode('[linkpage_listing_data]'); ?>
            </div>

            <div class="dashboard-pane <?php echo $active_tab === 'create_linkpage' ? 'active' : ''; ?>" id="create_linkpage">
                <?php echo do_shortcode('[create_linkpage_data]'); ?>
            </div>

            <div class="dashboard-pane <?php echo $active_tab === 'linkpage_analytics' ? 'active' : ''; ?>" id="linkpage_analytics">
                <?php echo do_shortcode('[linkpage_analytics_data]'); ?>
            </div>
            <div class="dashboard-pane <?php echo $active_tab === 'account' ? 'active' : ''; ?>" id="account">
                <?php echo do_shortcode('[pmpro_account]'); ?>
            </div>
        </main>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('dashboard_tabs', 'dashboard_tabs_shortcode');


/**
 * CSS & JS
 */

/**
 * Dashboard Assets
 */
function dashboard_tabs_assets() {

    wp_enqueue_style('dashicons');

    wp_register_style('dashboard-tabs-css', false);
    wp_enqueue_style('dashboard-tabs-css');

    wp_add_inline_style('dashboard-tabs-css', '
        .dashboard-wrapper { display:flex; background:#eaf2fd; min-height:600px; border-radius:12px; overflow:hidden; }
        .dashboard-sidebar { width:260px; background:#fff; padding:20px; border-right:1px solid #e5e7eb; }
        .profile-box { text-align:center; margin-bottom:30px; }
        .sidebar-nav { list-style:none; padding:0; margin:0; }
        .sidebar-nav li { padding:12px 14px; margin-bottom:8px; border-radius:8px; cursor:pointer; }
        .sidebar-nav li.active { background:#eaf2fd; border:1px solid #2563eb; color:#2563eb; }
        .dashboard-content { flex:1; padding:30px; }
        .dashboard-pane { display:none; }
        .dashboard-pane.active { display:block; }
    ');

    wp_register_script('dashboard-tabs-js', false, [], null, true);
    wp_enqueue_script('dashboard-tabs-js');

    wp_add_inline_script('dashboard-tabs-js', '
        document.addEventListener("DOMContentLoaded", function () {

            const dashboard = document.querySelector(".dashboard-wrapper");
            if (!dashboard) return;

            const tabs = dashboard.querySelectorAll(".sidebar-nav li");
            const panes = dashboard.querySelectorAll(".dashboard-pane");

            tabs.forEach(tab => {
                tab.addEventListener("click", function (e) {
                    e.preventDefault();

                    const target = this.dataset.tab;
                    const url = this.dataset.url;

                    if (!target || !url) return;

                    tabs.forEach(t => t.classList.remove("active"));
                    panes.forEach(p => p.classList.remove("active"));

                    this.classList.add("active");
                    const pane = dashboard.querySelector("#" + target);
                    if (pane) pane.classList.add("active");

                    history.pushState({ tab: target }, "", url);
                });
            });

            window.addEventListener("popstate", function (e) {
                if (!e.state || !e.state.tab) return;

                const target = e.state.tab;

                tabs.forEach(t => t.classList.remove("active"));
                panes.forEach(p => p.classList.remove("active"));

                const activeTab = dashboard.querySelector(`[data-tab="${target}"]`);
                const activePane = dashboard.querySelector("#" + target);

                if (activeTab) activeTab.classList.add("active");
                if (activePane) activePane.classList.add("active");
            });

        });
    ');
}
add_action('wp_enqueue_scripts', 'dashboard_tabs_assets');
/**
 * PMPro Account Tab Rewrite Rules
 */
function pmpro_account_tabs_rewrite() {

    add_rewrite_rule(
        '^membership-account/?$',
        'index.php?pagename=membership-account',
        'top'
    );

    add_rewrite_rule(
        '^membership-account/([^/]+)/?$',
        'index.php?pagename=membership-account&account_tab=$matches[1]',
        'top'
    );
}
add_action('init', 'pmpro_account_tabs_rewrite');

/**
 * Register account_tab query var
 */
function pmpro_register_account_tab_query_var($vars) {
    $vars[] = 'account_tab';
    return $vars;
}
add_filter('query_vars', 'pmpro_register_account_tab_query_var');

function simplqr_get_plan_limits($user_id) {

    // Get PMPro levels
    $levels = pmpro_getMembershipLevelsForUser($user_id);
    if (empty($levels)) {
        return false;
    }

    // Users can only have one level in your setup
    $level_id = (int) $levels[0]->id;

    /**
     * ============================
     * BASE PLAN DEFINITIONS
     * ============================
     * -1 = unlimited
     */
    $plans = [

        // 🔹 14 Day Trial (Free)
        3 => [
            'dynamic_qr'      => 1,
            'static_qr'       => 1,
            'link_pages'      => 1,
            'scan_limit'      => 100,
            'analytics'       => false,
            'analytics_months'=> 0,
            'landing_pages'   => 0,
            'addons_allowed'  => false,
            'expires'         => true,
        ],

        // 🔹 Simpl Starter ($7/month)
        1 => [
            'dynamic_qr'      => 1,
            'static_qr'       => -1,
            'link_pages'      => 1,
            'scan_limit'      => -1,
            'analytics'       => true,
            'analytics_months'=> 12,
            'landing_pages'   => 1,
            'addons_allowed'  => false,
            'expires'         => false,
        ],

        // 🔹 Simpl Growth ($20/month)
        2 => [
            'dynamic_qr'      => 20,
            'static_qr'       => -1,
            'link_pages'      => 20,
            'scan_limit'      => -1,
            'analytics'       => true,
            'analytics_months'=> 12,
            'landing_pages'   => 20,
            'addons_allowed'  => true,
            'expires'         => false,
        ],
    ];

    // Invalid level safety check
    if (!isset($plans[$level_id])) {
        return false;
    }

    $limits = $plans[$level_id];

    /**
     * ============================
     * ADD-ONS (ONLY IF ALLOWED)
     * ============================
     * Add-ons are ONLY valid on Growth plan
     */
    // if ($limits['addons_allowed']) {

    //     // Custom Domain add-on
    //     if (pmpro_hasMembershipLevel('Custom Domain')) {
    //         $limits['custom_domain'] = true;
    //     } else {
    //         $limits['custom_domain'] = false;
    //     }

    //     // Extra Dynamic QR Packs
    //     if (pmpro_hasMembershipLevel('10 Dynamic QR Pack')) {
    //         $limits['dynamic_qr'] += 10;
    //         $limits['link_pages'] += 10;
    //     }

    //     if (pmpro_hasMembershipLevel('25 Dynamic QR Pack')) {
    //         $limits['dynamic_qr'] += 25;
    //         $limits['link_pages'] += 25;
    //     }

    // } else {
    //     // Hard-disable add-ons for non-growth plans
    //     $limits['custom_domain'] = false;
    // }

    return $limits;
}
