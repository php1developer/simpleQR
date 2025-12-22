<?php
add_shortcode('qr_analytics_data', 'um_account_content_hook_analytics');

add_filter('um_account_content_hook_analytics', 'um_account_content_hook_analytics');
function um_account_content_hook_analytics($output)
{
    ob_start();
    $output = '';
    $uid          = get_current_user_id();
    $today        = strtotime('today');
    $current_user = wp_get_current_user();
    $user_name    = $current_user ? $current_user->display_name : '';

    // Fetch all QR codes for this user (IDs only)
    $args = [
        'post_type'      => 'qr_code',
        'author'         => $uid,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];
    $user_qr_codes = get_posts($args);
    $qr_count      = is_array($user_qr_codes) ? count($user_qr_codes) : 0;
    $total_scans   = 0;



    $daily_scans = [];
    $daily_unique_scanners = [];
    $labels = [];
    $values = [];
    $unique_values = [];
    $type_data   = [];
    $device_data = [];
    $hourly_data = [];
    $city_data = [];
    // ✅ FIX: use $user_qr_codes (not $qr_codes)
    if (!empty($user_qr_codes)) {
        $start_date = strtotime('-6 days', $today); // only include last 7 days

        foreach ($user_qr_codes as $qr_id) {
            $total_scans += (int) get_post_meta($qr_id, 'qr_scan_count', true);

            $type = get_post_meta($qr_id, 'qr_type', true) ?: 'Unknown';
            $logs = get_post_meta($qr_id, 'qr_scan_log', true);

            if (!is_array($logs)) continue;

            foreach ($logs as $log) {
                if (empty($log['time'])) continue;

                $timestamp = strtotime($log['time']);
                if ($timestamp < $start_date) continue; // ⛔ skip scans older than 7 days

                // Basic log data
                $device = !empty($log['device']) ? $log['device'] : 'Unknown';
                $ip     = !empty($log['ip']) ? $log['ip'] : 'unknown';
                $date   = date('Y-m-d', $timestamp);

                // --- Daily scans and unique scanners ---
                $daily_scans[$date] = ($daily_scans[$date] ?? 0) + 1;
                $daily_unique_scanners[$date][$ip] = true;

                // --- Type counts ---
                $type_data[$type] = ($type_data[$type] ?? 0) + 1;

                // --- Device counts ---
                $device_data[$device] = ($device_data[$device] ?? 0) + 1;

                // --- City/State/Country breakdown ---
                $location = !empty($log['location']) ? $log['location'] : 'Unknown';
                $parts = array_map('trim', explode(',', $location));
                $city = $parts[0] ?? 'Unknown';
                $state = $parts[1] ?? '';
                $country = $parts[2] ?? '';
                $key = "{$city}|{$state}|{$country}";

                if (!isset($city_data[$key])) {
                    $city_data[$key] = [
                        'city' => $city,
                        'state' => $state,
                        'country' => $country,
                        'scans' => 0,
                    ];
                }
                $city_data[$key]['scans']++;

                // --- Hourly heatmap ---
                $day  = date('D', $timestamp);
                $hour = date('ga', $timestamp);
                if (!isset($hourly_data[$day])) $hourly_data[$day] = [];
                $hourly_data[$day][$hour] = ($hourly_data[$day][$hour] ?? 0) + 1;
            }
        }
    }


    // Flatten unique scanner counts
    foreach ($daily_unique_scanners as $date => $ips) {
        $daily_unique_scanners[$date] = count($ips);
    }


    for ($i = 6; $i >= 0; $i--) { // 7 days: today + 6 previous
        $d        = date('Y-m-d', strtotime("-{$i} days", $today));
        $labels[] = $d;
        $values[] = $daily_scans[$d] ?? 0;
        $unique_values[] = $daily_unique_scanners[$d] ?? 0;
    }

    $total_city_scans = array_sum(array_column($city_data, 'scans'));
    foreach ($city_data as &$entry) {
        $entry['percent'] = $total_city_scans > 0
            ? round(($entry['scans'] / $total_city_scans) * 100, 1)
            : 0;
    }
    unset($entry);
    usort($city_data, fn($a, $b) => $b['scans'] <=> $a['scans']);

    // Prepare for JS
    $chart_data = [
        'type'    => $type_data,
        'device'  => $device_data,
        'heatmap' => $hourly_data,
    ];

    // Unique chart ID (important if multiple charts exist)
    $chart_id = uniqid('qrchart_');
    // ✅ Calculate 7-day totals
    $last_7_days_total_scans = array_sum($values);
    $last_7_days_unique_ips  = 0;
    foreach ($daily_unique_scanners as $ips) {
        $last_7_days_unique_ips += $ips;
    }
?>
    <div class="um-field">
        <div class="topbar">
            <div class="user-info">
                <?php if ($qr_count == 0): ?>
                    <h4>You have no QR Codes yet!</h4>
                    <p>Create your first QR code, scan it, and see your analytics below.</p>
                <?php else: ?>
                    <h4>Hi <?php echo esc_html($user_name); ?>,</h4>
                    <p>You’ve created <?php echo esc_html($qr_count); ?> QR codes with a total of <?php echo esc_html($total_scans); ?> scans.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="content analytics_dashboard_content">
            <div class="filers-top-bar mb-30">
                <div class="row justify-between">
                    <div class="col-md-6 d-flex justify-content-start gap-2">

                        <select id="qrRangeSelect_<?php echo esc_attr($chart_id);
                                                    ?>">
                            <option value="7" selected>Last 7 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="60">Last 60 days</option>
                            <option value="90">Last 90 days</option>
                            <!-- <option value="180">Last 180 days</option>
                            <option value="365">Last 365 days</option> -->
                        </select>
                        <span id="dateRangeText_<?php echo esc_attr($chart_id);
                                                ?>" class="range-label">
                            <?php echo esc_html(date('M d, Y', strtotime('-6 days')) . ' to ' . date('M d, Y'));
                            ?>
                        </span>
                    </div>
                    <div class="col-md-6 d-flex justify-content-end gap-2">
                        <a href="javascript:void(0);" class="btn card-btn-base analyics_qr_export_wrap">Export</a>
                    </div>
                </div>
            </div>

            <!-- Analytics Summary Cards -->
            <!-- 🔹 Analytics Summary Cards (Last 7 Days) -->
            <div class="cards-row">
                <!-- 🔹 Total Scans -->
                <div class="card total_scan_card_wrap">
                    <div class="card-inner-info w-100">
                        <div class="info-col">
                            <div class="title-wrapper">
                                <h5><i class="fa-solid fa-mobile-screen-button"></i> Total Scans</h5>
                            </div>
                            <div class="summary-wrapper">
                                <div class="inner-col">
                                    <div class="d-flex">
                                        <span id="curTotal" class="count total_analytics_count">
                                            <?php echo esc_html($last_7_days_total_scans); ?>
                                        </span>
                                        <!-- <span id="growthText" style="color:#16a34a;font-weight:500;">+0.00%</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🔹 Unique Scanners -->
                <div class="card">
                    <div class="card-inner-info w-100">
                        <div class="info-col">
                            <div class="title-wrapper">
                                <h5><i class="fa-regular fa-user"></i> Unique Scanners</h5>
                            </div>
                            <div class="summary-wrapper">
                                <div class="inner-col">
                                    <div class="d-flex">
                                        <span class="count">
                                            <?php echo esc_html($last_7_days_unique_ips); ?>
                                        </span>
                                        <!-- <span style="color:#16a34a;font-weight:500;">+0.00%</span> -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 🔹 Total QR Codes -->
                <div class="card">
                    <div class="card-inner-info w-100">
                        <div class="info-col">
                            <div class="title-wrapper">
                                <h5>Scanned Codes</h5>
                            </div>
                            <div class="summary-wrapper">
                                <div class="inner-col">
                                    <div class="d-flex">
                                        <span class="count"><?php echo esc_html($qr_count); ?></span>
                                        <a href="<?php echo site_url('/membership-account/qr_code'); ?>" class="view-qr">View QR codes</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="qr-card">
                <div class="meta">
                    <h3>Scans</h3>
                 
                </div>

                <div class="qr-chart-box">
                    <div class="qr-chart-canvas">
                        <canvas id="qrPerfChart_<?php echo esc_attr($chart_id); ?>"></canvas>
                    </div>

                    <div class="qr-stats">
                        <div>
                            <div style="display:flex;align-items:center;">
                                <span class="qr-dot" style="background:#2563eb"></span>
                                <strong>Scans</strong>
                            </div>
                            <div id="curTotal_<?php echo esc_attr($chart_id); ?>" style="font-size:28px;margin:6px 0;">0</div>
                            <!-- <div id="growthText_<?php //echo esc_attr($chart_id); 
                                                        ?>" style="font-size:13px;color:#16a34a;">+0%</div> -->
                        </div>

                        <!-- <div style="margin-top:20px;">
                            <div style="display:flex;align-items:center;">
                                <span class="qr-dot" style="background:#60a5fa;opacity:.6"></span>
                                <strong style="color:#374151;">Previous scans</strong>
                            </div>
                            <div id="prevTotal_<?php echo esc_attr($chart_id); ?>" style="font-size:18px;color:#374151;">0</div>
                            <div id="prevRangeText_<?php echo esc_attr($chart_id); ?>" style="font-size:12px;color:#6b7280;">–</div>
                        </div> -->
                    </div>
                    <!-- <div class="no-data-box" style="width:100%;text-align:center;padding:40px 0;">
                            <p style="color:#6b7280;margin-top:10px;">No data found for your QR code scans yet.</p>
                        </div> -->

                </div>
            </div>
            <div class="qr-card">
                <div class="meta">
                    <h3>Unique Scanners</h3>
                </div>

                <div class="qr-chart-box">
                    <?php // if (array_sum($unique_values) > 0): 
                    ?>
                    <div class="qr-chart-canvas">
                        <canvas id="qrUniqueChart_<?php echo esc_attr($chart_id); ?>"></canvas>
                    </div>

                    <div class="qr-stats">
                        <div>
                            <div style="display:flex;align-items:center;">
                                <span class="qr-dot" style="background:#16a34a"></span>
                                <strong>Unique Scanners</strong>
                            </div>
                            <div id="curUniqueTotal_<?php echo esc_attr($chart_id); ?>" style="font-size:28px;margin:6px 0;">0</div>
                            <!-- <div id="growthUniqueText_<?php // echo esc_attr($chart_id); 
                                                            ?>" style="font-size:13px;color:#16a34a;">+0%</div> -->
                        </div>

                        <!-- <div style="margin-top:20px;">
                            <div style="display:flex;align-items:center;">
                                <span class="qr-dot" style="background:#86efac;opacity:.6"></span>
                                <strong style="color:#374151;">Previous Period</strong>
                            </div>
                            <div id="prevUniqueTotal_<?php echo esc_attr($chart_id); ?>" style="font-size:18px;color:#374151;">0</div>
                            <div id="prevUniqueRangeText_<?php echo esc_attr($chart_id); ?>" style="font-size:12px;color:#6b7280;">–</div>
                        </div> -->
                    </div>
                    <?php //else: 
                    ?>
                    <!-- <div class="no-data-box" style="width:100%;text-align:center;padding:40px 0;">
                            <p style="color:#6b7280;margin-top:10px;">No unique scanner data yet.</p>
                        </div> -->
                    <?php //endif; 
                    ?>
                </div>
            </div>
            <!-- 🔹 Top Performing QR Codes -->
            <div class="qr-analytics-dashboard">
                <div class="qr-analytics-card">
                    <div class="card-header">
                        <h3>Top Performing QR Codes</h3>
                    </div>

                    <div class="card-body">
                        <table class="analytics-table">
                            <thead>
                                <tr>
                                    <th>RANK</th>
                                    <th>NAME</th>
                                    <th>SCANS</th>
                                    <th>% OF TOTAL SCANS</th>
                                </tr>
                            </thead>
                            <tbody id="topQrTableBody">
                                <?php
                                if (!empty($user_qr_codes)) {
                                    // Build an array of [id, name, scans]
                                    $qr_data = [];

                                    foreach ($user_qr_codes as $qr_id) {
                                        $name  = get_the_title($qr_id);
                                        $logs  = get_post_meta($qr_id, 'qr_scan_log', true);
                                        $scans = 0;

                                        if (!empty($logs) && is_array($logs)) {
                                            foreach ($logs as $log) {
                                                if (empty($log['time'])) continue;
                                                $time = strtotime($log['time']);


                                                if (!empty($log['time'])) {
                                                    $time = strtotime($log['time']);

                                                    // last 7 days including today
                                                    if ($time >= strtotime('-6 days', $today)) {
                                                        $scans++;
                                                    }
                                                }
                                            }
                                        }

                                        $qr_data[] = [
                                            'id'    => $qr_id,
                                            'name'  => $name,
                                            'scans' => $scans,
                                        ];
                                    }

                                    // Remove QR codes with zero scans (optional)
                                    $qr_data = array_filter($qr_data, fn($q) => $q['scans'] > 0);

                                    // Sort by scans descending
                                    usort($qr_data, fn($a, $b) => $b['scans'] <=> $a['scans']);

                                    // Calculate total scans (only last 7 days)
                                    $total_scans_all = max(array_sum(array_column($qr_data, 'scans')), 1);

                                    // Calculate percentages
                                    foreach ($qr_data as &$qr) {
                                        $qr['percent'] = round(($qr['scans'] / $total_scans_all) * 100, 1);
                                    }
                                    unset($qr);

                                    // Limit to top 10
                                    $rank = 1;
                                    foreach (array_slice($qr_data, 0, 10) as $qr) { ?>
                                        <tr>
                                            <td><?php echo $rank++; ?></td>
                                            <td><?php echo esc_html($qr['name']); ?></td>
                                            <td><?php echo esc_html($qr['scans']); ?></td>
                                            <td>
                                                <div style="background:#e5e7eb;border-radius:4px;overflow:hidden;width:100%;height:10px;position:relative;">
                                                    <div style="background:#2563eb;width:<?php echo $qr['percent']; ?>%;height:10px;"></div>
                                                </div>
                                                <span style="font-size:12px;color:#111827;"><?php echo $qr['percent']; ?>%</span>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="5" style="text-align:center;color:#6b7280;">No QR code data available yet.</td></tr>';
                                }
                                ?>
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
            <div class="qr-analytics-dashboard">

                <!-- 🔹 Scans by QR Code Type -->
                <div class="qr-analytics-card qr_code_scan_wrap">
                    <div class="card-header">
                        <h3>Scans by QR Code Type</h3>
                    </div>
                    <div class="card-body two-col">
                        <?php // if (!empty($type_data) && array_sum($type_data) > 0): 
                        ?>
                        <div class="chart-col">
                            <canvas id="qrTypeChart"></canvas>
                        </div>
                        <div class="table-col">
                            <table class="analytics-table">
                                <thead>
                                    <tr>
                                        <th>TYPE</th>
                                        <th>QR CODES SCANNED</th>
                                        <th>SCANS</th>
                                        <th>% OF TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody id="typeTableBody">
                                    <?php
                                    $total_type_scans = array_sum($type_data);

                                    foreach ($type_data as $type => $scan_count) {
                                        // Count only QR codes that actually have scans
                                        $qr_codes_of_type = array_filter($user_qr_codes, function ($qr_id) use ($type) {
                                            $qr_type = get_post_meta($qr_id, 'qr_type', true);
                                            $count = (int) get_post_meta($qr_id, 'qr_scan_count', true);
                                            return $qr_type === $type && $count > 0;
                                        });

                                        $scanned_qr_count = count($qr_codes_of_type);
                                        $percent = $total_type_scans > 0 ? round(($scan_count / $total_type_scans) * 100, 1) : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html($type); ?></td>
                                            <td><?php echo esc_html($scanned_qr_count); ?></td>
                                            <td><?php echo esc_html($scan_count); ?></td>
                                            <td><?php echo esc_html($percent); ?>%</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php //else: 
                        ?>
                        <!-- <div style="width:100%;text-align:center;padding:40px 0;color:#6b7280;">
                                <i class="fa-solid fa-circle-info"></i>
                                No QR code scan data found yet.
                            </div> -->
                        <?php //endif; 
                        ?>
                    </div>
                </div>

                <!-- 🔹 Device Used -->
                <div class="two_col_wrapper">
                    <div class="qr-analytics-card device_scans_card">
                        <div class="card-header">
                            <h3>Scans by Device Used</h3>
                        </div>
                        <div class="card-body two-col">
                            <?php // if (!empty($device_data) && array_sum($device_data) > 0): 
                            ?>
                            <div class="chart-col">
                                <canvas id="qrDeviceChart"></canvas>
                            </div>
                            <div class="table-col">
                                <table class="analytics-table">
                                    <thead>
                                        <tr>
                                            <th>DEVICE USED</th>
                                            <th>SCANS</th>
                                            <th>% OF SCANS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="deviceTableBody">
                                        <?php
                                        $total_device_scans = array_sum($device_data);
                                        foreach ($device_data as $device => $scan_count) {
                                            $percent = $total_device_scans > 0
                                                ? round(($scan_count / $total_device_scans) * 100, 1)
                                                : 0;
                                        ?>
                                            <tr>
                                                <td><?php echo esc_html($device); ?></td>
                                                <td><?php echo esc_html($scan_count); ?></td>
                                                <td><?php echo esc_html($percent); ?>%</td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php // else: 
                            ?>
                            <!-- <div style="width:100%;text-align:center;padding:40px 0;color:#6b7280;">
                                    <i class="fa-solid fa-mobile-screen"></i>
                                    No device data available yet.
                                </div> -->
                            <?php //endif; 
                            ?>
                        </div>
                    </div>

                    <!-- 🔹 Time of Day -->
                    <div class="qr-analytics-card">
                        <div class="card-header">
                            <h3>Scans by Time of Day</h3>
                        </div>
                        <div class="card-body" style="position: relative;">
                            <?php // if (!empty($hourly_data) && array_sum(array_map('array_sum', $hourly_data)) > 0): 
                            ?>
                            <canvas id="qrHeatmap"></canvas>
                            <div class="heatmap-legend"></div>
                            <div class="heatmap-legend-labels">
                                <div id="legend-max">6</div>
                                <div style="margin-top:190px;" id="legend-min">0</div>
                            </div>
                            <?php //else: 
                            ?>
                            <!-- <div style="width:100%;text-align:center;padding:40px 0;color:#6b7280;">
                                    <i class="fa-regular fa-clock" style="margin-right:6px;color:#9ca3af;"></i>
                                    No time-of-day scan data found yet.
                                </div> -->
                            <?php // endif; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- 🔹 Scans by City/State/Country -->
            <div class="qr-analytics-card" id="scanLocationCard">
                <div class="card-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <h3>Scans by</h3>
                        <select id="scanFilter" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:6px;">
                            <option value="city" selected>City</option>
                            <option value="state">State</option>
                            <option value="country">Country</option>
                        </select>
                    </div>
                </div>

                <div class="card-body">
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th class="col-city">City</th>
                                <th class="col-state">State/Province</th>
                                <th class="col-country">Country</th>
                                <th class="col-scans">Scans</th>
                                <th class="col-percent">% of Scans</th>
                            </tr>
                        </thead>
                        <tbody id="scanTableBody">
                            <?php if (!empty($city_data) && array_sum(array_column($city_data, 'scans')) > 0): ?>
                                <?php
                                $total_city_scans = array_sum(array_column($city_data, 'scans'));
                                foreach (array_slice($city_data, 0, 10) as $entry):
                                    $percent = round(($entry['scans'] / $total_city_scans) * 100, 1);
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($entry['city']); ?></td>
                                        <td><?php echo esc_html($entry['state']); ?></td>
                                        <td><?php echo esc_html($entry['country']); ?></td>
                                        <td><?php echo esc_html($entry['scans']); ?></td>
                                        <td><?php echo esc_html($percent); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:#6b7280;">No scan data in the last 7 days.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
<?php
    wp_localize_script('simpleqr-analytics', 'qrAnalyticsData', [
        'chart_data'    => $chart_data,
        'labels'        => $labels,
        'values'        => $values,
        'unique_values' => $unique_values,
        'chart_id'      => $chart_id,
        // ✅ Add this line
        'ajax_url'      => admin_url('admin-ajax.php'),
    ]);

    $output .= ob_get_clean();
    return $output;
}
// 🔹 AJAX: Scans by City/State/Country
add_action('wp_ajax_get_scan_location_data', 'get_scan_location_data');
function get_scan_location_data()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not authorized');
    }

    $uid    = get_current_user_id();
    $filter = sanitize_text_field($_POST['filter'] ?? 'city');
    $page   = intval($_POST['page'] ?? 1);
    $range  = intval($_POST['range'] ?? 7); // ✅ new line
    $per_page = 5;

    $today = strtotime('today');
    $from = strtotime('-' . ($range - 1) . ' days', $today);

    $args = [
        'post_type'      => 'qr_code',
        'author'         => $uid,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $user_qr_codes = get_posts($args);
    $data = [];

    foreach ($user_qr_codes as $qr_id) {
        $logs = get_post_meta($qr_id, 'qr_scan_log', true);
        if (empty($logs) || !is_array($logs)) continue;

        foreach ($logs as $log) {
            $timestamp = strtotime($log['time'] ?? '');
            if (!$timestamp || $timestamp < $from) continue; // ✅ skip older scans

            $location = !empty($log['location']) ? $log['location'] : 'Unknown';
            $parts = array_map('trim', explode(',', $location));

            $city = $parts[0] ?? 'Unknown';
            $state = $parts[1] ?? '';
            $country = $parts[2] ?? '';

            // Grouping key depends on filter
            if ($filter === 'state') $key = $state ?: 'Unknown';
            elseif ($filter === 'country') $key = $country ?: 'Unknown';
            else $key = $city ?: 'Unknown';

            if (!isset($data[$key])) {
                $data[$key] = [
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'scans' => 0
                ];
            }

            $data[$key]['scans']++;
        }
    }

    // Sort, paginate, and add percent
    usort($data, fn($a, $b) => $b['scans'] <=> $a['scans']);
    $total = count($data);
    $paged = array_slice($data, ($page - 1) * $per_page, $per_page);

    $total_scans = array_sum(array_column($data, 'scans'));
    foreach ($paged as &$r) {
        $r['percent'] = $total_scans > 0 ? round(($r['scans'] / $total_scans) * 100, 1) : 0;
    }

    wp_send_json_success([
        'rows' => $paged,
        'total' => $total,
        'per_page' => $per_page,
        'page' => $page,
        'filter' => $filter,
    ]);
}


// 🔹 AJAX: Date-range and filter-wise analytics refresh
// 🔹 AJAX: Date-range and filter-wise analytics refresh
add_action('wp_ajax_get_filtered_analytics', 'get_filtered_analytics');
function get_filtered_analytics()
{
    if (!is_user_logged_in()) wp_send_json_error('Not authorized');

    $uid     = get_current_user_id();
    $range   = intval($_POST['range'] ?? 7);
    $today   = strtotime('today');
    $from = strtotime('-' . ($range - 1) . ' days', $today);


    $args = [
        'post_type'      => 'qr_code',
        'author'         => $uid,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    $user_qr_codes = get_posts($args);
    $qr_count = count($user_qr_codes);

    // --- containers ---
    $data = [
        'total_scans'        => 0,
        'unique_scanners'    => 0,
        'qr_count'           => $qr_count,
        'type'               => [],
        'type_qr_count'      => [], // ✅ NEW for "QR CODES SCANNED"
        'device'             => [],
        'heatmap'            => [],
        'city_state_country' => [],
        'daily_labels'       => [],
        'daily_values'       => [],
        'unique_daily'       => [],
        'top_qr'             => [],
    ];

    $daily_scans = [];
    $unique_ips  = [];
    $filter_data = [];
    $top_qr_data = [];
    $type_qr_map = []; // ✅ Tracks unique QR codes per type

    foreach ($user_qr_codes as $qr_id) {
        $logs = get_post_meta($qr_id, 'qr_scan_log', true);
        if (empty($logs) || !is_array($logs)) continue;

        $type = get_post_meta($qr_id, 'qr_type', true) ?: 'Unknown';

        $qr_scans = 0; // for top QR codes
        foreach ($logs as $log) {
            $t = strtotime($log['time'] ?? '');
            if (!$t || $t < $from) continue;

            $data['total_scans']++;
            $qr_scans++;

            // Daily totals
            $date = date('Y-m-d', $t);
            $daily_scans[$date] = ($daily_scans[$date] ?? 0) + 1;

            // Unique IPs
            $ip = $log['ip'] ?? 'unknown';
            $unique_ips[$date][$ip] = true;

            // Count scans per type
            $data['type'][$type] = ($data['type'][$type] ?? 0) + 1;

            // ✅ Mark this QR code as active under this type
            $type_qr_map[$type][$qr_id] = true;

            // Device
            $device = $log['device'] ?? 'Unknown';
            $data['device'][$device] = ($data['device'][$device] ?? 0) + 1;

            // Heatmap
            $day  = date('D', $t);
            $hour = date('ga', $t);
            $data['heatmap'][$day][$hour] = ($data['heatmap'][$day][$hour] ?? 0) + 1;

            // Location
            $loc = $log['location'] ?? 'Unknown';
            $parts = array_map('trim', explode(',', $loc));
            $city = $parts[0] ?? 'Unknown';
            $state = $parts[1] ?? '';
            $country = $parts[2] ?? '';
            $key = "{$city}|{$state}|{$country}";
            if (!isset($filter_data[$key])) {
                $filter_data[$key] = [
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'scans' => 0
                ];
            }
            $filter_data[$key]['scans']++;
        }

        if ($qr_scans > 0) {
            $top_qr_data[] = [
                'id'    => $qr_id,
                'name'  => get_the_title($qr_id),
                'scans' => $qr_scans,
            ];
        }
    }

    // ✅ Count distinct QR codes per type
    foreach ($type_qr_map as $type => $ids) {
        $data['type_qr_count'][$type] = count($ids);
    }

    // --- Unique scanners ---
    foreach ($unique_ips as $d => $ips) {
        $data['unique_daily'][$d] = count($ips);
    }
    $all_ips = [];
    foreach ($unique_ips as $arr) $all_ips += $arr;
    $data['unique_scanners'] = count($all_ips);

    // --- Daily labels ---
    for ($i = $range - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days", $today));
        $data['daily_labels'][] = $d;
        $data['daily_values'][] = $daily_scans[$d] ?? 0;
        $data['unique_daily'][$d] = $data['unique_daily'][$d] ?? 0;
    }

    // --- City/State/Country ---
    $totalLoc = array_sum(array_column($filter_data, 'scans'));
    foreach ($filter_data as &$r)
        $r['percent'] = $totalLoc ? round($r['scans'] / $totalLoc * 100, 1) : 0;
    usort($filter_data, fn($a, $b) => $b['scans'] <=> $a['scans']);
    $data['city_state_country'] = $filter_data;

    // --- Top QR Codes ---
    usort($top_qr_data, fn($a, $b) => $b['scans'] <=> $a['scans']);
    $totalTop = max(array_sum(array_column($top_qr_data, 'scans')), 1);
    foreach ($top_qr_data as &$r)
        $r['percent'] = round(($r['scans'] / $totalTop) * 100, 1);
    $data['top_qr'] = array_slice($top_qr_data, 0, 10);

    wp_send_json_success($data);
}
