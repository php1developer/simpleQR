<?php
add_shortcode('qr_overview_data', 'um_account_qr_perf_final');

add_filter('um_account_content_hook_overview', 'um_account_qr_perf_final');
function um_account_qr_perf_final()
{
    ob_start();
    $output = '';
    $uid = get_current_user_id();
    $today = strtotime('today');
    $current_user = wp_get_current_user();
    $user_name = $current_user->display_name; // or ->first_name
    // ===== Get QR Codes created by this user =====
    $current_user_id = get_current_user_id();
    $args = array(
        'post_type'      => 'qr_code',
        'author'         => $current_user_id,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );
    $user_qr_codes = get_posts($args);

    $qr_count = count($user_qr_codes);
    $total_scans = 0;

    if ($qr_count > 0) {
        foreach ($user_qr_codes as $qr_id) {
            $total_scans += (int) get_post_meta($qr_id, 'qr_scan_count', true);
        }
    }

    $top_qr_codes = [];
    foreach ($user_qr_codes as $qr_id) {
        $scan_count = (int) get_post_meta($qr_id, 'qr_scan_count', true);
        if ($scan_count > 0) {
            $top_qr_codes[] = [
                'title' => get_the_title($qr_id),
                'scans' => $scan_count,
            ];
        }
    }
    // Collect scan logs by date
    $qr_posts = get_posts([
        'post_type'      => 'qr_code',
        'author'         => $uid,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $daily_scans = []; // date => count
    foreach ($qr_posts as $pid) {
        $logs = get_post_meta($pid, 'qr_scan_log', true);
        if (empty($logs) || !is_array($logs)) continue;
        foreach ($logs as $log) {
            if (empty($log['time'])) continue;
            $date = date('Y-m-d', strtotime($log['time']));
            $daily_scans[$date] = ($daily_scans[$date] ?? 0) + 1;
        }
    }

    // Build continuous last 365-day dataset
    $labels = [];
    $values = [];
    for ($i = 364; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days", $today));
        $labels[] = $d;
        $values[] = $daily_scans[$d] ?? 0;
    }
    ?>

    <style>
        .qr-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px 28px;
            margin-top: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .qr-card h3 {
            font-weight: 600;
            color: #111827;
            margin: 0;
            font-size: 1.25rem;
        }

        .qr-card .meta {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .qr-card select {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
        }

        .qr-chart-box {
            margin-top: 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
        }

        .qr-chart-canvas {
            flex: 1;
            min-width: 300px;
            height: 180px;
        }

        .qr-stats {
            width: 240px;
            min-width: 220px;
        }

        .qr-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
    </style>

    <div class="um-field">

        <div class="topbar">
            <div class="user-info">
                <h4>Hi <?php echo esc_html($user_name); ?>,</h4>
                <p>Here's a summary of your QR code campaigns.</p>
            </div>
            <a href="/membership-account/qr_code" class="outline-base-btn">+ Create QR Code</a>
        </div>
        <div class="content">
            <!-- Cards -->

            <div class="cards-row">
                <div class="card">
                    <?php if ($qr_count > 0): ?>
                        <div class="card-inner-info have-data">
                            <a href="/membership-account/qr_code">
                                <h5>QR Codes</h5>
                                <p><strong><?php echo esc_html($qr_count); ?></strong> Dynamic QR Codes</p>
                                <p><strong><?php echo esc_html($total_scans); ?></strong> Total Scans</p>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="card-inner-info">
                            <div class="info-col">
                                <h5>QR Codes</h5>
                                <p>Create custom QR Codes in a few clicks</p>
                                <a href="/membership-account/qr_code" class="btn card-btn-base">Create QR Codes</a>
                            </div>
                            <div class="img-box-col">
                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/09/qrcode.svg" alt="icon">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <div class="card-inner-info">
                        <div class="info-col">
                            <h5>Link Pages</h5>
                            <p>Create your own link-in-bio page</p>
                            <a href="/account/linkpage/" class="btn card-btn-base">Design Link Pages</a>
                        </div>
                        <div class="img-box-col">
                            <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/09/qrcode.svg" alt="icon">
                        </div>
                    </div>
                </div>
                <div class="card">
                    <?php if (!empty($top_qr_codes)) : ?>
                        <!-- Show Top Performing QR Codes -->
                        <div class="card-inner-info have-data">
                            <a href="/membership-account/qr_code">
                                <h5>Top Performing QR Codes</h5>
                                 <p><strong>QR CODES</strong><span>TOTAL SCANS</span></p>
                                <?php
                                $max = min(3, count($top_qr_codes)); // Show top 3
                                for ($i = 0; $i < $max; $i++) :
                                ?>
                                    <p><strong><?php echo esc_html($top_qr_codes[$i]['title']); ?></strong><span><?php echo esc_html($top_qr_codes[$i]['scans']); ?> scans</span></p>
                                <?php endfor; ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <!-- No QR codes with scans -->
                        <div class="card-inner-info">
                            <div class="info-col">
                                <h5>Top Performing QR Codes</h5>
                                <p>No scans yet. Start creating QR Codes to see performance here.</p>
                            </div>
                            <div class="img-box-col">
                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/09/qrcode.svg" alt="icon">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="qr-card">
            <div class="meta">
                <h3>QR Codes Performance</h3>
                <span id="dateRangeText" style="font-size:14px;color:#4b5563;background:#f9fafb;padding:6px 10px;border:1px solid #e5e7eb;border-radius:6px;">
                    <?php echo date('M d, Y', strtotime('-6 days')) . ' to ' . date('M d, Y'); ?>
                </span>
                <select id="qrRangeSelect">
                    <option value="7" selected>Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="60">Last 60 days</option>
                    <option value="90">Last 90 days</option>
                    <option value="180">Last 180 days</option>
                    <option value="365">Last 365 days</option>
                </select>
            </div>

            <div class="qr-chart-box">
                <?php if (array_sum($values) > 0): ?>
                    <div class="qr-chart-canvas">
                        <canvas id="qrPerfChart"></canvas>
                    </div>

                    <div class="qr-stats">
                        <div>
                            <div style="display:flex;align-items:center;"><span class="qr-dot" style="background:#2563eb"></span><strong>Scans</strong></div>
                            <div id="curTotal" style="font-size:28px;margin:6px 0;">0</div>
                            <div id="growthText" style="font-size:13px;color:#16a34a;">+0%</div>
                        </div>

                        <div style="margin-top:20px;">
                            <div style="display:flex;align-items:center;"><span class="qr-dot" style="background:#60a5fa;opacity:.6"></span><strong style="color:#374151;">Previous scans</strong></div>
                            <div id="prevTotal" style="font-size:18px;color:#374151;">0</div>
                            <div id="prevRangeText" style="font-size:14px;color:#6b7280;">–</div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-data-box" style="width:100%;text-align:center;padding:40px 0;">
                        <p style="color:#6b7280;margin-top:10px;">No data found for your QR code scans yet.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
        <div class="performance card">
            <div class="card-inner-info">
                <div class="info-col">
                    <div class="title-wrapper">
                        <h5>Link Pages Performance</h5>
                        <div class="selection-box">
                            <span class="selected-date start">
                                Sep 04, 2025 to Sep 10, 2025
                            </span>
                            vs
                            <span class="selected-date end">
                                Aug 28, 2025 to Sep 03, 2025
                            </span>
                            <select id="date-range" name="date-range">
                                <option value="today">Today</option>
                                <option value="last7">Last 7 days</option>
                                <option value="last30">Last 30 days</option>
                                <option value="last60">Last 60 days</option>
                                <option value="last90">Last 90 days</option>
                                <option value="last180">Last 180 days</option>
                                <option value="last365">Last 365 days</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>
                    <div class="summary-wrapper">
                        <div class="inner-col">
                            <p>Scans</p>
                            <div class="d-flex">
                                <span class="sucess">0</span>
                                <span class="danger">0</span>
                                <span>0</span>
                            </div>
                        </div>
                        <div class="inner-col">
                            <div class="chart">

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="performance card">
            <div class="card-inner-info">
                <div class="info-col">
                    <div class="title-wrapper">
                        <h5>Link Pages Performance</h5>
                        <div class="selection-box">
                            <span class="selected-date start">
                                Sep 04, 2025 to Sep 10, 2025
                            </span>
                            vs
                            <span class="selected-date end">
                                Aug 28, 2025 to Sep 03, 2025
                            </span>
                            <select id="date-range" name="date-range">
                                <option value="today">Today</option>
                                <option value="last7">Last 7 days</option>
                                <option value="last30">Last 30 days</option>
                                <option value="last60">Last 60 days</option>
                                <option value="last90">Last 90 days</option>
                                <option value="last180">Last 180 days</option>
                                <option value="last365">Last 365 days</option>
                                <option value="custom">Custom</option>
                            </select>
                        </div>
                    </div>
                    <div class="summary-wrapper no-data-found">
                        <div class="inner-col full">
                            <p>No data yet</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function() {
            const labelsAll = <?php echo json_encode($labels); ?>;
            const dataAll = <?php echo json_encode($values); ?>;

            const ctx = document.getElementById('qrPerfChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Scans',
                        data: [],
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,0.15)',
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` ${ctx.parsed.y} scans`
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#6b7280',
                                maxTicksLimit: 8
                            },
                            grid: {
                                color: 'rgba(229,231,235,0.4)'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#6b7280'
                            },
                            grid: {
                                color: 'rgba(229,231,235,0.4)'
                            }
                        }
                    }
                }
            });

            const sel = document.getElementById('qrRangeSelect');
            const dateTxt = document.getElementById('dateRangeText');
            const curEl = document.getElementById('curTotal');
            const prevEl = document.getElementById('prevTotal');
            const prevTxt = document.getElementById('prevRangeText');
            const growthEl = document.getElementById('growthText');

            function sum(a) {
                return a.reduce((x, y) => x + y, 0);
            }

            function fmt(d) {
                return new Date(d + 'T00:00:00').toLocaleDateString(undefined, {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric'
                });
            }

            function updateFor(days) {
                const end = labelsAll.length - 1;
                const start = Math.max(0, end - (days - 1));
                const curLabels = labelsAll.slice(start, end + 1);
                const curVals = dataAll.slice(start, end + 1);
                const prevLabels = labelsAll.slice(Math.max(0, start - days), start);
                const prevVals = dataAll.slice(Math.max(0, start - days), start);

                chart.data.labels = curLabels;
                chart.data.datasets[0].data = curVals;
                chart.update();

                const curSum = sum(curVals);
                const prevSum = sum(prevVals);
                const growth = prevSum > 0 ? ((curSum - prevSum) / prevSum * 100) :
                    (curSum > 0 ? 999 : 0);

                curEl.textContent = curSum;
                prevEl.textContent = prevSum;

                const color = growth > 0 ? '#16a34a' : (growth < 0 ? '#dc2626' : '#6b7280');
                const arrow = growth > 0 ? '↑ ' : (growth < 0 ? '↓ ' : '');
                growthEl.style.color = color;
                growthEl.textContent = arrow + Math.round(Math.abs(growth)) + '%';

                dateTxt.textContent = `${fmt(curLabels[0])} to ${fmt(curLabels[curLabels.length-1])}`;
                if (prevLabels.length) {
                    prevTxt.textContent = `${fmt(prevLabels[0])} to ${fmt(prevLabels[prevLabels.length-1])}`;
                } else prevTxt.textContent = 'No previous period';
            }

            updateFor(parseInt(sel.value, 10));
            sel.addEventListener('change', () => updateFor(parseInt(sel.value, 10)));
        })();
    </script>

    <?php
    $output .= ob_get_clean();
    return $output;
}


add_action('wp_footer', 'um_custom_account_tab_script', 100);
function um_custom_account_tab_script()
{
    if (is_page('account')) : ?>
        <script>
            jQuery(document).ready(function($) {

                // Handle clicks on /account/ links
                $(document).on('click', 'a[href^="/membership-account/"]', function(e) {
                    e.preventDefault();
                    var href = $(this).attr('href');

                    var tab = href.split('/').filter(Boolean).pop(); // last part of URL

                    if (tab) {
                        var $tabLink = $('.um-account-side li a[data-tab="' + tab + '"]');

                        if ($tabLink.length) {
                            $tabLink.trigger('click');
                            history.pushState(null, '', href);
                        } else {}
                    }
                });

                // Auto-open tab on page load
                var pathParts = window.location.pathname.split('/').filter(Boolean);

                var lastPart = pathParts.pop();

                if (lastPart) {
                    var $tabLink = $('.um-account-side li a[data-tab="' + lastPart + '"]');

                    if ($tabLink.length) {
                        $tabLink.trigger('click');
                    } else {}
                }
            });
        </script>
<?php endif;
}
