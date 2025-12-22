<?php
/**
 * Add these to your functions.php or plugin file
 */

// AJAX handler for click tracking
add_action('wp_ajax_track_linkpage_click', 'handle_linkpage_click_tracking');
add_action('wp_ajax_nopriv_track_linkpage_click', 'handle_linkpage_click_tracking');

function handle_linkpage_click_tracking() {
    $linkpage_id = intval($_POST['linkpage_id'] ?? 0);
    $widget_type = sanitize_text_field($_POST['widget_type'] ?? '');
    $widget_title = sanitize_text_field($_POST['widget_title'] ?? '');
    $widget_url = esc_url_raw($_POST['widget_url'] ?? '');
    
    if ($linkpage_id && $widget_type) {
        LinkPageAnalytics::track_click($linkpage_id, $widget_type, $widget_title, $widget_url);
    }
    
    wp_send_json_success();
}

// Create database tables on plugin/theme activation
function create_linkpage_analytics_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Analytics table
    $sql_analytics = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linkpage_analytics (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        linkpage_id BIGINT(20) UNSIGNED NOT NULL,
        metric_date DATE NOT NULL,
        views INT DEFAULT 0,
        unique_views INT DEFAULT 0,
        clicks INT DEFAULT 0,
        unique_clicks INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_linkpage_date (linkpage_id, metric_date),
        KEY idx_date (metric_date)
    ) $charset_collate;";
    
    // Sessions table
    $sql_sessions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linkpage_sessions (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        linkpage_id BIGINT(20) UNSIGNED NOT NULL,
        session_hash VARCHAR(64) NOT NULL,
        visitor_ip VARCHAR(45),
        user_agent TEXT,
        device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
        browser VARCHAR(50),
        os VARCHAR(50),
        country VARCHAR(2),
        country_name VARCHAR(100),
        city VARCHAR(100),
        state_province VARCHAR(100),
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        timezone VARCHAR(50),
        first_visit DATETIME NOT NULL,
        last_visit DATETIME NOT NULL,
        total_views INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_linkpage (linkpage_id),
        KEY idx_session (session_hash),
        KEY idx_first_visit (first_visit),
        UNIQUE KEY unique_session (linkpage_id, session_hash)
    ) $charset_collate;";
    
    // Clicks table
    $sql_clicks = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linkpage_clicks (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        linkpage_id BIGINT(20) UNSIGNED NOT NULL,
        session_hash VARCHAR(64) NOT NULL,
        widget_type VARCHAR(50) NOT NULL,
        widget_title VARCHAR(255),
        widget_url TEXT,
        clicked_at DATETIME NOT NULL,
        device_type ENUM('desktop', 'mobile', 'tablet', 'unknown') DEFAULT 'unknown',
        country VARCHAR(2),
        city VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_linkpage (linkpage_id),
        KEY idx_clicked_at (clicked_at),
        KEY idx_widget (widget_type)
    ) $charset_collate;";
    
    // Hourly stats table
    $sql_hourly = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linkpage_hourly_stats (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        linkpage_id BIGINT(20) UNSIGNED NOT NULL,
        stat_hour DATETIME NOT NULL,
        views INT DEFAULT 0,
        clicks INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_hour (linkpage_id, stat_hour),
        KEY idx_stat_hour (stat_hour)
    ) $charset_collate;";
    
    // Referrers table
    $sql_referrers = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}linkpage_referrers (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        linkpage_id BIGINT(20) UNSIGNED NOT NULL,
        referrer_url TEXT,
        referrer_domain VARCHAR(255),
        visit_date DATE NOT NULL,
        visit_count INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_linkpage_date (linkpage_id, visit_date)
    ) $charset_collate;";
    
    dbDelta($sql_analytics);
    dbDelta($sql_sessions);
    dbDelta($sql_clicks);
    dbDelta($sql_hourly);
    dbDelta($sql_referrers);
}

// Hook for activation
register_activation_hook(__FILE__, 'create_linkpage_analytics_tables');

// Or run on theme/plugin init if tables don't exist
add_action('after_setup_theme', function() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'linkpage_analytics';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        create_linkpage_analytics_tables();
    }
});


// add_filter('um_account_content_hook_linkpage_analytics', 'um_account_content_hook_linkpage_analytics');

add_shortcode('linkpage_analytics_data','um_account_content_hook_linkpage_analytics');


function um_account_content_hook_linkpage_analytics($output)
{
    ob_start();

    // 1. Setup & Auth
    if (!is_user_logged_in()) {
        echo '<div class="lp-analytics-error">Please log in.</div>';
        $output .= ob_get_clean();
        return $output;
    }

    global $wpdb;
    $user_id = get_current_user_id();

    // Fetch Link Pages
    $link_pages = get_posts([
        'post_type'      => 'link_page',
        'posts_per_page' => -1,
        'author'         => $user_id,
        'post_status'    => ['publish', 'private']
    ]);

    if (empty($link_pages)) {
        echo '<div class="lp-empty">No Link Pages Found</div>';
        $output .= ob_get_clean();
        return $output;
    }

// 2. Filter Logic
    $selected_lp_id = 'all'; // Always show all pages
    $range_days = isset($_GET['report_range']) ? intval($_GET['report_range']) : 7;
    
    // Dates
    $end_date = current_time('Y-m-d');
    $start_date = date('Y-m-d', strtotime("-$range_days days", strtotime($end_date)));
    
    // Previous Period Dates
    $prev_end_date = date('Y-m-d', strtotime("-1 day", strtotime($start_date)));
    $prev_start_date = date('Y-m-d', strtotime("-$range_days days", strtotime($prev_end_date)));

    // IDs
    $link_page_ids = wp_list_pluck($link_pages, 'ID');
    $lp_titles = wp_list_pluck($link_pages, 'post_title', 'ID');
    
    // Logic simplified for 'all'
    $allowed_ids = $link_page_ids;
    if (empty($allowed_ids)) $allowed_ids = [-1];
    $ids_placeholder = implode(',', array_map('intval', $allowed_ids));

    // --- HELPER QUERIES ---

    // Function to get totals for a date range
    $get_totals = function($s, $e) use ($wpdb, $ids_placeholder) {
        return $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(views) as views, SUM(unique_views) as unique_views, SUM(clicks) as clicks 
             FROM {$wpdb->prefix}linkpage_analytics 
             WHERE linkpage_id IN ($ids_placeholder) AND metric_date BETWEEN %s AND %s",
            $s, $e
        ));
    };

    // Current Period Totals
    $curr = $get_totals($start_date, $end_date);
    $curr_views = intval($curr->views ?? 0);
    $curr_clicks = intval($curr->clicks ?? 0);
    $curr_users = intval($curr->unique_views ?? 0);

    // Previous Period Totals
    $prev = $get_totals($prev_start_date, $prev_end_date);
    $prev_views = intval($prev->views ?? 0);
    $prev_clicks = intval($prev->clicks ?? 0);
    $prev_users = intval($prev->unique_views ?? 0);

    // Calculate % Change
    $calc_change = function($now, $before) {
        if ($before == 0) return $now > 0 ? 100 : 0;
        return round((($now - $before) / $before) * 100, 1);
    };
    
    $view_change = $calc_change($curr_views, $prev_views);
    $click_change = $calc_change($curr_clicks, $prev_clicks);
    $user_change = $calc_change($curr_users, $prev_users);

    // Helper to format change label
    $fmt_change = function($val) {
        $cls = $val >= 0 ? 'text-green' : 'text-red';
        $icon = $val >= 0 ? '↑' : '↓';
        return "<span class='$cls'>$icon " . abs($val) . "%</span> than previous period";
    };

    // --- CHART DATA ---

    // Time Series Data (Daily)
    $daily_stats = $wpdb->get_results($wpdb->prepare(
        "SELECT metric_date, SUM(views) as views, SUM(clicks) as clicks, SUM(unique_views) as users
         FROM {$wpdb->prefix}linkpage_analytics 
         WHERE linkpage_id IN ($ids_placeholder) AND metric_date BETWEEN %s AND %s 
         GROUP BY metric_date ORDER BY metric_date ASC",
        $start_date, $end_date
    ));

    $labels = []; $d_clicks = []; $d_views = []; $d_users = [];
    $period = new DatePeriod(new DateTime($start_date), new DateInterval('P1D'), (new DateTime($end_date))->modify('+1 day'));
    $stats_map = [];
    foreach ($daily_stats as $s) $stats_map[$s->metric_date] = $s;

    foreach ($period as $dt) {
        $d = $dt->format('Y-m-d');
        $labels[] = $dt->format('M d, Y');
        $d_clicks[] = intval($stats_map[$d]->clicks ?? 0);
        $d_views[] = intval($stats_map[$d]->views ?? 0);
        $d_users[] = intval($stats_map[$d]->users ?? 0);
    }

    // --- LINK LEVEL ANALYTICS ---
    $top_links = $wpdb->get_results($wpdb->prepare(
        "SELECT linkpage_id, widget_title, widget_url, COUNT(*) as clicks, COUNT(DISTINCT session_hash) as unique_clicks 
         FROM {$wpdb->prefix}linkpage_clicks 
         WHERE linkpage_id IN ($ids_placeholder) AND clicked_at BETWEEN %s AND %s
         GROUP BY linkpage_id, widget_title, widget_url 
         ORDER BY clicks DESC LIMIT 10",
        $start_date . ' 00:00:00', $end_date . ' 23:59:59'
    ));

    // --- DEVICES ---
    $devices = $wpdb->get_results($wpdb->prepare(
        "SELECT device_type, COUNT(*) as count 
         FROM {$wpdb->prefix}linkpage_sessions 
         WHERE linkpage_id IN ($ids_placeholder) AND last_visit BETWEEN %s AND %s 
         GROUP BY device_type ORDER BY count DESC",
        $start_date . ' 00:00:00', $end_date . ' 23:59:59'
    ));

    // --- CITIES (Locations) ---
    $locations = $wpdb->get_results($wpdb->prepare(
        "SELECT city, state_province, country_name, COUNT(*) as views, COUNT(DISTINCT session_hash) as visitors 
         FROM {$wpdb->prefix}linkpage_sessions 
         WHERE linkpage_id IN ($ids_placeholder) AND last_visit BETWEEN %s AND %s AND city != ''
         GROUP BY city, state_province, country_name 
         ORDER BY views DESC LIMIT 10",
        $start_date . ' 00:00:00', $end_date . ' 23:59:59'
    ));

    // --- VIEWS BY TIME ---
    // Simple hourly aggregation for heatmap effect
    $hourly_raw = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE_FORMAT(stat_hour, '%w') as day_num, DATE_FORMAT(stat_hour, '%k') as hour_num, SUM(views) as total 
         FROM {$wpdb->prefix}linkpage_hourly_stats 
         WHERE linkpage_id IN ($ids_placeholder) AND stat_hour BETWEEN %s AND %s
         GROUP BY day_num, hour_num",
        $start_date . ' 00:00:00', $end_date . ' 23:59:59'
    ));
    
    // Map to grid [Day][Hour] = count
    $time_grid = [];
    $max_time_view = 0;
    foreach($hourly_raw as $r) {
        $time_grid[$r->day_num][$r->hour_num] = intval($r->total);
        if($r->total > $max_time_view) $max_time_view = $r->total;
    }
    
    ?>
    <!-- Dependencies -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="la-dashboard">
        <style>
            .la-dashboard { font-family: 'Inter', sans-serif; background: #f4f6f8; padding: 20px; color: #333; }
            .la-header { display: flex; justify-content: space-between; margin-bottom: 20px; align-items: center; }
            .la-title { font-size: 20px; font-weight: 600; }
            .la-controls { display: flex; gap: 10px; }
            .la-select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; background: #fff; }
            .la-btn { background: #3182ce; color: #fff; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
            
            /* Cards */
            .la-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
            .la-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .la-card-head { color: #666; font-size: 14px; margin-bottom: 10px; font-weight: 500; }
            .la-card-val { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
            .la-card-sub { font-size: 12px; color: #888; }
            .text-green { color: #48bb78; background: #e6fffa; padding: 2px 5px; border-radius: 4px; font-weight: 600; }
            .text-red { color: #e53e3e; background: #fff5f5; padding: 2px 5px; border-radius: 4px; font-weight: 600; }
            
            /* Section */
            .la-section { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .la-section-title { font-size: 16px; font-weight: 600; margin-bottom: 15px; display: flex; justify-content: space-between; }
            
            /* Table */
            .la-table { width: 100%; border-collapse: collapse; font-size: 13px; }
            .la-table th { text-align: left; color: #888; font-weight: 600; padding: 10px; border-bottom: 1px solid #eee; text-transform: uppercase; font-size: 11px; }
            .la-table td { padding: 12px 10px; border-bottom: 1px solid #f9f9f9; color: #444; }
            .la-link { color: #3182ce; text-decoration: none; }
            
            /* Charts */
            .la-chart-box { height: 300px; position: relative; }
            .la-chart-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px; }
            
            /* Time Heatmap */
            .la-time-grid { display: grid; grid-template-columns: auto repeat(24, 1fr); gap: 2px; }
            .la-time-cell { aspect-ratio: 1; background: #ebf8ff; border-radius: 2px; }
            .la-time-label { font-size: 10px; color: #888; display: flex; align-items: center; justify-content: flex-end; padding-right: 5px; }
            
            /* Map */
            #la-map { height: 350px; width: 100%; border-radius: 8px; z-index: 1; }
            
            /* Utils */
            .d-flex { display: flex; }
            .gap-4 { gap: 16px; }
        </style>

        <!-- Header -->
        <div class="la-header">
            <div class="la-title">Linkpage Analytics</div>
            <div class="la-controls">
                <select id="la-report-range" class="la-select" onchange="updateReportRange(this.value)">
                    <option value="7" <?php selected($range_days, 7); ?>>Last 7 days</option>
                    <option value="30" <?php selected($range_days, 30); ?>>Last 30 days</option>
                    <option value="90" <?php selected($range_days, 90); ?>>Last 90 days</option>
                </select>
                <a href="javascript:void(0)" class="la-btn analyics_link_export_wrap">Export</a>
            </div>
            
            <script>
            function updateReportRange(val) {
                var url = new URL(window.location.href);
                url.searchParams.set('report_range', val);
                window.location.href = url.toString();
            }
            </script>
        </div>

        <!-- 1. Summary Cards -->
        <div class="la-cards">
            <div class="la-card">
                <div class="la-card-head">Views</div>
                <div class="la-card-val"><?php echo number_format($curr_views); ?></div>
                <div class="la-card-sub"><?php echo $fmt_change($view_change); ?></div>
            </div>
            <div class="la-card">
                <div class="la-card-head">Clicks</div>
                <div class="la-card-val"><?php echo number_format($curr_clicks); ?></div>
                <div class="la-card-sub"><?php echo $fmt_change($click_change); ?></div>
            </div>
            <div class="la-card">
                <div class="la-card-head">Users</div>
                <div class="la-card-val"><?php echo number_format($curr_users); ?></div>
                <div class="la-card-sub"><?php echo $fmt_change($user_change); ?></div>
            </div>
        </div>

        <!-- 2. Clicks Chart -->
        <div class="la-section">
            <div class="la-section-title">
                <span>Clicks</span>
                <span style="font-size:12px;color:#888;">Expected Clicks: <?php echo number_format($curr_clicks); ?></span>
            </div>
            <div class="la-chart-box">
                <canvas id="chartClicks"></canvas>
            </div>
        </div>

        <!-- 3. Link-level Analytics -->
        <div class="la-section">
            <div class="la-section-title">Link-level Analytics</div>
            <div style="overflow-x:auto;">
                <table class="la-table">
                    <thead>
                        <tr>
                            <th>Linkpage Name</th>
                            <th>Link Title</th>
                            <th>Link URL</th>
                            <th>Total Clicks</th>
                            <th>Unique Clicks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($top_links): foreach($top_links as $l): ?>
                        <tr>
                            <td><?php echo esc_html($lp_titles[$l->linkpage_id] ?? 'Unknown'); ?></td>
                            <td><?php echo esc_html($l->widget_title); ?></td>
                            <td><a href="<?php echo esc_url($l->widget_url); ?>" target="_blank" class="la-link"><?php echo substr($l->widget_url, 0, 30).'...'; ?></a></td>
                            <td><?php echo number_format($l->clicks); ?></td>
                            <td><?php echo number_format($l->unique_clicks); ?></td>
                        </tr>
                        <?php endforeach; else: echo "<tr><td colspan='5'>No data</td></tr>"; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 4. Views Chart -->
        <div class="la-section">
            <div class="la-section-title">Views</div>
            <div class="la-chart-box">
                <canvas id="chartViews"></canvas>
            </div>
        </div>

        <!-- 5. Users Chart -->
        <div class="la-section">
            <div class="la-section-title">Users</div>
            <div class="la-chart-box">
                <canvas id="chartUsers"></canvas>
            </div>
        </div>

        <!-- 6 & 7. Device & Time -->
        <div class="la-chart-row">
            <!-- Devices -->
            <div class="la-section" style="margin:0;">
                <div class="la-section-title">Views by Device</div>
                <div style="height:250px; position:relative;">
                    <canvas id="chartDevice"></canvas>
                </div>
            </div>
            
            <!-- Time (Heatmap approximation) -->
            <div class="la-section" style="margin:0;">
                <div class="la-section-title">Views by Time</div>
                <div style="height:250px; overflow:hidden; font-size:10px;">
                    <div style="display:flex; height:100%;">
                       <div style="width:30px; display:flex; flex-direction:column; justify-content:space-around;">
                           <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                       </div>
                       <div style="flex:1; display:flex; flex-direction:column; gap:2px;">
                           <?php 
                           for($d=0; $d<7; $d++) {
                               echo "<div style='display:flex; gap:2px; flex:1;'>";
                               for($h=0; $h<24; $h++) {
                                   $val = $time_grid[$d][$h] ?? 0;
                                   $op = $max_time_view > 0 ? min(1, ($val / $max_time_view) + 0.1) : 0.1;
                                   if ($val == 0) $op = 0.05;
                                   $color = "rgba(49, 130, 206, $op)";
                                   echo "<div style='flex:1; background:$color; border-radius:1px;' title='Day $d Hour $h: $val Views'></div>";
                               }
                               echo "</div>";
                           }
                           ?>
                           <div style="display:flex; justify-content:space-between; margin-top:5px; color:#aaa;">
                               <span>12am</span><span>6am</span><span>12pm</span><span>6pm</span>
                           </div>
                       </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 8. Views by City -->
        <div class="la-section">
            <div class="la-section-title">Views by City</div>
            <table class="la-table">
                <thead>
                    <tr>
                        <th>City</th>
                        <th>State/Province</th>
                        <th>Country</th>
                        <th>Views</th>
                        <th>Visitors</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($locations): foreach($locations as $loc): ?>
                    <tr>
                        <td><?php echo esc_html($loc->city ?: 'Unknown'); ?></td>
                        <td><?php echo esc_html($loc->state_province ?: '-'); ?></td>
                        <td><?php echo esc_html($loc->country_name ?: '-'); ?></td>
                        <td><?php echo number_format($loc->views); ?></td>
                        <td><?php echo number_format($loc->visitors); ?></td>
                    </tr>
                    <?php endforeach; else: echo "<tr><td colspan='5'>No data</td></tr>"; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 9. Map -->
        <!-- <div class="la-section">
            <div class="la-section-title">Views by Location</div>
            <div id="la-map"></div>
        </div> -->

    </div>

    <!-- Scripts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Shared Options
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { borderDash: [2, 4] } } },
            plugins: { legend: { display: false } }
        };

        // 1. Clicks (Bar)
        new Chart(document.getElementById('chartClicks').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{ label: 'Clicks', data: <?php echo json_encode($d_clicks); ?>, borderColor: '#3182ce', backgroundColor: 'rgba(49, 130, 206, 0.1)', borderWidth: 2, tension: 0.4, fill: true  }]
            },
            options: commonOptions
        });

        // 2. Views (Line)
        new Chart(document.getElementById('chartViews').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{ 
                    label: 'Views', data: <?php echo json_encode($d_views); ?>, 
                    borderColor: '#3182ce', backgroundColor: 'rgba(49, 130, 206, 0.1)', 
                    borderWidth: 2, tension: 0.4, fill: true 
                }]
            },
            options: commonOptions
        });

        // 3. Users (Line)
        new Chart(document.getElementById('chartUsers').getContext('2d'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{ 
                    label: 'Users', data: <?php echo json_encode($d_users); ?>, 
                    borderColor: '#3182ce', backgroundColor: 'rgba(49, 130, 206, 0.1)', 
                    borderWidth: 2, tension: 0.4, fill: true 
                }]
            },
            options: commonOptions
        });

        // 4. Device (Doughnut)
        new Chart(document.getElementById('chartDevice').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php 
                    $dl = []; $dv = [];
                    foreach($devices as $d) { $dl[] = ucfirst($d->device_type); $dv[] = $d->count; }
                    echo json_encode($dl); 
                ?>,
                datasets: [{ 
                    data: <?php echo json_encode($dv); ?>,
                    backgroundColor: ['#3182ce', '#38b2ac', '#ecc94b', '#e53e3e'],
                    borderWidth: 0,
                    cutout: '70%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // 5. Map (Leaflet)
        // Init map
        var map = L.map('la-map').setView([20, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        // Add markers (Simplified: Randomize slightly if exact same loc to avoid overlap issue or just place them)
        // Since we don't have lat/lng in the $locations table displayed, we'll try to use the raw sessions data if available.
        // Actually we queried 'city' grouped. Lat/Lng is in sessions table.
        // We'll pass a separate tiny JSON with top lat/lngs.
        <?php
        // Get Lat/Lngs for map
        $map_points = $wpdb->get_results($wpdb->prepare(
            "SELECT latitude, longitude, city, COUNT(*) as count 
             FROM {$wpdb->prefix}linkpage_sessions 
             WHERE linkpage_id IN ($ids_placeholder) AND last_visit BETWEEN %s AND %s AND latitude != 0
             GROUP BY latitude, longitude LIMIT 50",
            $start_date . ' 00:00:00', $end_date . ' 23:59:59'
        ));
        echo "var points = " . json_encode($map_points) . ";";
        ?>
        
        points.forEach(function(p) {
            L.circleMarker([p.latitude, p.longitude], {
                radius: 5 + (Math.min(p.count, 50) / 5),
                fillColor: "#3182ce",
                color: "#fff",
                weight: 1,
                opacity: 1,
                fillOpacity: 0.6
            }).bindPopup(p.city + ": " + p.count + " views").addTo(map);
        });

    });


     jQuery(document).on("click", ".analyics_link_export_wrap", async function (e) {
        e.preventDefault();
        debugger;
        const element = document.querySelector(".la-dashboard");
        if (!element) {
            alert("Analytics content not found.");
            return;
        }

        const $btn = jQuery(this);
        $btn.text("Exporting...");
        $btn.prop("disabled", true);

        try {
            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                logging: false,
                scrollY: 0,
                windowWidth: document.body.scrollWidth
            });

            const imgData   = canvas.toDataURL("image/png");
            const pdf       = new jspdf.jsPDF("p", "pt", "a4");
            const pdfWidth  = pdf.internal.pageSize.getWidth();
            const pdfHeight = pdf.internal.pageSize.getHeight();
            const imgWidth  = pdfWidth;
            const imgHeight = (canvas.height * pdfWidth) / canvas.width;

            let heightLeft = imgHeight;
            let position   = 0;

            pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
            heightLeft -= pdfHeight;

            while (heightLeft > 10) {
                position -= pdfHeight;
                pdf.addPage();
                pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);
                heightLeft -= pdfHeight;
            }

            const fileName = "Linkpage_Analytics_Report_" + new Date().toISOString().slice(0, 10) + ".pdf";
            pdf.save(fileName);
        } catch (err) {
            console.error("Export failed:", err);
            alert("Failed to generate PDF. See console for details.");
        }

        $btn.text("Export");
        $btn.prop("disabled", false);
    });
    </script>
    <?php
    $output .= ob_get_clean();
    return $output;
}