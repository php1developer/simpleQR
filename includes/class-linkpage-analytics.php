<?php
/**
 * LinkPage Analytics Tracker
 * Add this to your single-link_page.php file
 */

class LinkPageAnalytics {
    
    /**
     * Track page view
     */
    public static function track_view($linkpage_id) {
        global $wpdb;
        
        $session_data = self::get_session_data();
        $session_hash = self::generate_session_hash();
        $current_time = current_time('mysql');
        $current_date = current_time('Y-m-d');
        $current_hour = date('Y-m-d H:00:00', strtotime($current_time));
        
        // Check if session exists
        $existing_session = $wpdb->get_row($wpdb->prepare(
            "SELECT id, total_views FROM {$wpdb->prefix}linkpage_sessions 
            WHERE linkpage_id = %d AND session_hash = %s",
            $linkpage_id, $session_hash
        ));
        
        $is_unique = false;
        
        if ($existing_session) {
            // Update existing session
            $wpdb->update(
                "{$wpdb->prefix}linkpage_sessions",
                array(
                    'last_visit' => $current_time,
                    'total_views' => $existing_session->total_views + 1,
                    'updated_at' => $current_time
                ),
                array('id' => $existing_session->id),
                array('%s', '%d', '%s'),
                array('%d')
            );
        } else {
            // New session - this is a unique view
            $is_unique = true;
            $geo_data = self::get_geo_data($session_data['ip']);
            
            $wpdb->insert(
                "{$wpdb->prefix}linkpage_sessions",
                array(
                    'linkpage_id' => $linkpage_id,
                    'session_hash' => $session_hash,
                    'visitor_ip' => $session_data['ip'],
                    'user_agent' => $session_data['user_agent'],
                    'device_type' => $session_data['device_type'],
                    'browser' => $session_data['browser'],
                    'os' => $session_data['os'],
                    'country' => $geo_data['country_code'],
                    'country_name' => $geo_data['country_name'],
                    'city' => $geo_data['city'],
                    'state_province' => $geo_data['state_province'],
                    'latitude' => $geo_data['latitude'],
                    'longitude' => $geo_data['longitude'],
                    'timezone' => $geo_data['timezone'],
                    'first_visit' => $current_time,
                    'last_visit' => $current_time,
                    'total_views' => 1
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s', '%d')
            );
        }
        
        // Update daily analytics
        self::update_daily_analytics($linkpage_id, $current_date, $is_unique);
        
        // Update hourly stats
        self::update_hourly_stats($linkpage_id, $current_hour, 'view');
        
        // Track referrer
        if (!empty($_SERVER['HTTP_REFERER'])) {
            self::track_referrer($linkpage_id, $_SERVER['HTTP_REFERER'], $current_date);
        }
    }
    
    /**
     * Track widget click
     */
    public static function track_click($linkpage_id, $widget_type, $widget_title, $widget_url) {
        global $wpdb;
        
        $session_data = self::get_session_data();
        $session_hash = self::generate_session_hash();
        $current_time = current_time('mysql');
        $current_date = current_time('Y-m-d');
        $current_hour = date('Y-m-d H:00:00', strtotime($current_time));
        $geo_data = self::get_geo_data($session_data['ip']);
        
        // Record click
        $wpdb->insert(
            "{$wpdb->prefix}linkpage_clicks",
            array(
                'linkpage_id' => $linkpage_id,
                'session_hash' => $session_hash,
                'widget_type' => $widget_type,
                'widget_title' => $widget_title,
                'widget_url' => $widget_url,
                'clicked_at' => $current_time,
                'device_type' => $session_data['device_type'],
                'country' => $geo_data['country_code'],
                'city' => $geo_data['city']
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Check if this is unique click for this session AND this specific widget
        $click_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}linkpage_clicks 
            WHERE linkpage_id = %d AND session_hash = %s 
            AND widget_type = %s AND widget_url = %s
            AND DATE(clicked_at) = %s",
            $linkpage_id, $session_hash, $widget_type, $widget_url, $current_date
        ));
        
        $is_unique = ($click_count == 1);
        
        // Update daily analytics
        self::update_daily_analytics($linkpage_id, $current_date, null, $is_unique);
        
        // Update hourly stats
        self::update_hourly_stats($linkpage_id, $current_hour, 'click');
    }
    
    /**
     * Update daily analytics
     */
    private static function update_daily_analytics($linkpage_id, $date, $is_unique_view = null, $is_unique_click = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'linkpage_analytics';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table WHERE linkpage_id = %d AND metric_date = %s",
            $linkpage_id, $date
        ));
        
        if ($existing) {
            $set_clauses = [];
            
            if ($is_unique_view !== null) {
                $set_clauses[] = "views = views + 1";
                if ($is_unique_view) {
                    $set_clauses[] = "unique_views = unique_views + 1";
                }
            }
            
            if ($is_unique_click !== null) {
                $set_clauses[] = "clicks = clicks + 1";
                if ($is_unique_click) {
                    $set_clauses[] = "unique_clicks = unique_clicks + 1";
                }
            }
            
            if (!empty($set_clauses)) {
                $set_clauses[] = "updated_at = '" . current_time('mysql') . "'";
                $sql = "UPDATE $table SET " . implode(', ', $set_clauses) . " WHERE id = %d";
                $wpdb->query($wpdb->prepare($sql, $existing->id));
            }
        } else {
            $wpdb->insert(
                $table,
                array(
                    'linkpage_id' => $linkpage_id,
                    'metric_date' => $date,
                    'views' => $is_unique_view !== null ? 1 : 0,
                    'unique_views' => $is_unique_view ? 1 : 0,
                    'clicks' => $is_unique_click !== null ? 1 : 0,
                    'unique_clicks' => $is_unique_click ? 1 : 0
                ),
                array('%d', '%s', '%d', '%d', '%d', '%d')
            );
        }
    }
    
    /**
     * Update hourly statistics
     */
    private static function update_hourly_stats($linkpage_id, $hour, $type) {
        global $wpdb;
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}linkpage_hourly_stats 
            WHERE linkpage_id = %d AND stat_hour = %s",
            $linkpage_id, $hour
        ));
        
        if ($existing) {
            $field = ($type === 'view') ? 'views' : 'clicks';
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}linkpage_hourly_stats 
                SET {$field} = {$field} + 1, updated_at = %s 
                WHERE id = %d",
                current_time('mysql'), $existing->id
            ));
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}linkpage_hourly_stats",
                array(
                    'linkpage_id' => $linkpage_id,
                    'stat_hour' => $hour,
                    'views' => ($type === 'view') ? 1 : 0,
                    'clicks' => ($type === 'click') ? 1 : 0
                ),
                array('%d', '%s', '%d', '%d')
            );
        }
    }
    
    /**
     * Track referrer
     */
    private static function track_referrer($linkpage_id, $referrer_url, $date) {
        global $wpdb;
        
        $parsed = parse_url($referrer_url);
        $domain = isset($parsed['host']) ? $parsed['host'] : '';
        
        // Skip if same domain
        if ($domain === $_SERVER['HTTP_HOST']) {
            return;
        }
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}linkpage_referrers 
            WHERE linkpage_id = %d AND referrer_domain = %s AND visit_date = %s",
            $linkpage_id, $domain, $date
        ));
        
        if ($existing) {
            $wpdb->update(
                "{$wpdb->prefix}linkpage_referrers",
                array(
                    'visit_count' => $existing->visit_count + 1,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%d', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                "{$wpdb->prefix}linkpage_referrers",
                array(
                    'linkpage_id' => $linkpage_id,
                    'referrer_url' => $referrer_url,
                    'referrer_domain' => $domain,
                    'visit_date' => $date,
                    'visit_count' => 1
                ),
                array('%d', '%s', '%s', '%s', '%d')
            );
        }
    }
    
    /**
     * Generate session hash
     */
    private static function generate_session_hash() {
        $ip = self::get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash('sha256', $ip . $user_agent . date('Y-m-d'));
    }
    
    /**
     * Get session data
     */
    private static function get_session_data() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return array(
            'ip' => self::get_client_ip(),
            'user_agent' => $user_agent,
            'device_type' => self::detect_device($user_agent),
            'browser' => self::detect_browser($user_agent),
            'os' => self::detect_os($user_agent)
        );
    }
    
    /**
     * Get client IP
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                        'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Detect device type
     */
    private static function detect_device($user_agent) {
        if (preg_match('/tablet|ipad|playbook|silk/i', $user_agent)) {
            return 'tablet';
        }
        if (preg_match('/mobile|android|iphone|ipod|opera mini|blackberry|palm|windows phone/i', $user_agent)) {
            return 'mobile';
        }
        if (preg_match('/windows|mac|linux|ubuntu/i', $user_agent)) {
            return 'desktop';
        }
        return 'unknown';
    }
    
    /**
     * Detect browser
     */
    private static function detect_browser($user_agent) {
        if (strpos($user_agent, 'Edge') !== false) return 'Edge';
        if (strpos($user_agent, 'Chrome') !== false) return 'Chrome';
        if (strpos($user_agent, 'Safari') !== false) return 'Safari';
        if (strpos($user_agent, 'Firefox') !== false) return 'Firefox';
        if (strpos($user_agent, 'Opera') !== false) return 'Opera';
        return 'Unknown';
    }
    
    /**
     * Detect operating system
     */
    private static function detect_os($user_agent) {
        if (preg_match('/windows/i', $user_agent)) return 'Windows';
        if (preg_match('/macintosh|mac os x/i', $user_agent)) return 'Mac OS';
        if (preg_match('/linux/i', $user_agent)) return 'Linux';
        if (preg_match('/android/i', $user_agent)) return 'Android';
        if (preg_match('/iphone|ipad|ipod/i', $user_agent)) return 'iOS';
        return 'Unknown';
    }
    
    /**
     * Get geolocation data
     * You can integrate with services like:
     * - ipapi.co (free tier available)
     * - ip-api.com (free for non-commercial)
     * - MaxMind GeoIP2
     */
    private static function get_geo_data($ip) {
        // Skip for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
            return array(
                'country_code' => '',
                'country_name' => '',
                'city' => '',
                'state_province' => '',
                'latitude' => 0,
                'longitude' => 0,
                'timezone' => ''
            );
        }
        
        // Cache geolocation data to avoid repeated API calls
        $cache_key = 'geo_' . md5($ip);
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Example using ip-api.com (free)
        $response = wp_remote_get("http://ip-api.com/json/{$ip}");
        
        if (is_wp_error($response)) {
            return self::empty_geo_data();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || $data['status'] !== 'success') {
            return self::empty_geo_data();
        }
        
        $geo_data = array(
            'country_code' => $data['countryCode'] ?? '',
            'country_name' => $data['country'] ?? '',
            'city' => $data['city'] ?? '',
            'state_province' => $data['regionName'] ?? '',
            'latitude' => $data['lat'] ?? 0,
            'longitude' => $data['lon'] ?? 0,
            'timezone' => $data['timezone'] ?? ''
        );
        
        // Cache for 24 hours
        set_transient($cache_key, $geo_data, DAY_IN_SECONDS);
        
        return $geo_data;
    }
    
    /**
     * Empty geo data
     */
    private static function empty_geo_data() {
        return array(
            'country_code' => '',
            'country_name' => '',
            'city' => '',
            'state_province' => '',
            'latitude' => 0,
            'longitude' => 0,
            'timezone' => ''
        );
    }
}

// Add this at the beginning of your single-link_page.php (after the while loop starts)
// LinkPageAnalytics::track_view(get_the_ID());

// For tracking clicks, you'll need to implement AJAX endpoints
// Example JavaScript to track clicks:
/*
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-widget').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            var widgetType = this.closest('[class*="widget-"]').className.match(/(\w+)-widget/)[1];
            var widgetTitle = this.querySelector('span') ? this.querySelector('span').textContent : '';
            var widgetUrl = this.getAttribute('href');
            
            // Send analytics
            fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'track_linkpage_click',
                    linkpage_id: <?php echo get_the_ID(); ?>,
                    widget_type: widgetType,
                    widget_title: widgetTitle,
                    widget_url: widgetUrl
                })
            });
        });
    });
});
*/