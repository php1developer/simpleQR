<?php
/**
 * Dynamic Link Page Preview with Analytics
 */

if (!function_exists('lp_get_value')) {
    function lp_get_value($fields, $key, $default = '')
    {
        if (!is_array($fields)) {
            return $default;
        }

        $variants = [$key];
        if (substr($key, -2) === '[]') {
            $variants[] = substr($key, 0, -2);
        } else {
            $variants[] = "{$key}[]";
        }

        foreach ($variants as $variant) {
            if (!array_key_exists($variant, $fields)) {
                continue;
            }

            $value = $fields[$variant];

            if (is_array($value)) {
                $value = reset($value);
            }

            if ($value === '' || $value === null) {
                continue;
            }

            return $value;
        }

        return $default;
    }
}

if (!function_exists('lp_get_list')) {
    function lp_get_list($fields, $key)
    {
        if (!is_array($fields)) {
            return [];
        }

        $variants = [$key];
        if (substr($key, -2) === '[]') {
            $variants[] = substr($key, 0, -2);
        } else {
            $variants[] = "{$key}[]";
        }

        foreach ($variants as $variant) {
            if (!array_key_exists($variant, $fields)) {
                continue;
            }
            $value = $fields[$variant];
            if (is_array($value)) {
                return array_values(array_filter($value));
            }
            if ($value !== '') {
                return [$value];
            }
        }

        return [];
    }
}

if (!function_exists('lp_render_action_button')) {
    function lp_render_action_button($title, $url, $type, $image, $class = '', $data_attrs = '')
    {
        $button_classes = ['btn-widget'];
        $before_content = '';

        if ($type === 'thumbnail') {
            $button_classes[] = 'with-thumbnail';
            if (!empty($image)) {
                $before_content = '<span class="thumbnail-img"><img src="' . esc_url($image) . '" alt=""></span>';
            }
        } elseif ($type === 'icons') {
            $button_classes[] = 'with-icon';
            if (!empty($image)) {
                $before_content = '<span class="icon-img"><img src="' . esc_url($image) . '" alt=""></span>';
            }
        }

        $class_attr = trim(implode(' ', array_filter([$class, implode(' ', $button_classes)])));

        return sprintf(
            '<a href="%s" class="%s" %s>%s<span>%s</span></a>',
            esc_url($url ?: 'javascript:void(0);'),
            esc_attr($class_attr),
            $data_attrs,
            $before_content,
            esc_html($title ?: 'Button')
        );
    }
}

if (!function_exists('lp_render_widget')) {
    function lp_render_widget($widget, $common)
    {
        $type = isset($widget['type']) ? strtolower($widget['type']) : '';
        $fields = isset($widget['fields']) && is_array($widget['fields']) ? $widget['fields'] : [];

        ob_start();

        switch ($type) {
            case 'button': {
                    $title = lp_get_value($fields, 'wg-w-link-title[]', '');
                    $url = lp_get_value($fields, 'wg-w-link-url[]', '');
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-link-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-link-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="button" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($url)
                    );

                    echo '<div class="widget-button linkPage">';
                    echo lp_render_action_button($title, $url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'link': {
                    $title = lp_get_value($fields, 'wg-w-link-page-title[]', '');
                    $url = lp_get_value($fields, 'wg-w-link-url[]', '');
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-link-page-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-link-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="link" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($url)
                    );

                    echo '<div class="widget-button linkPage">';
                    echo lp_render_action_button($title, $url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'landing': {
                    $title = lp_get_value($fields, 'wg-w-lp-title[]', '');
                    $url = lp_get_value($fields, 'wg-w-link-url[]', '');
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-landing-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-landing-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="landing" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($url)
                    );

                    echo '<div class="widget-button landingPage">';
                    echo lp_render_action_button($title, $url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'email': {
                    $title = lp_get_value($fields, 'wg-w-email-button-title[]', 'Email');
                    $to = lp_get_value($fields, 'wg-w-email-receiver[]', '');
                    $subject = rawurlencode(lp_get_value($fields, 'wg-w-email-subject[]', ''));
                    $body = rawurlencode(lp_get_value($fields, 'wg-w-email-body[]', ''));
                    $url = $to ? "mailto:{$to}?subject={$subject}&body={$body}" : 'javascript:void(0);';
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-email-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-link-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="email" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($to)
                    );

                    echo '<div class="widget-button email-widget">';
                    echo lp_render_action_button($title, $url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'call': {
                    $title = lp_get_value($fields, 'wg-w-call-button-title[]', 'Call');
                    $phone = preg_replace('/[^0-9+]/', '', lp_get_value($fields, 'wg-w-call-phone-number[]', ''));
                    $url = $phone ? "tel:{$phone}" : 'javascript:void(0);';
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-call-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-link-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="call" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($phone)
                    );

                    echo '<div class="widget-button call-widget">';
                    echo lp_render_action_button($title, $url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'sms': {
                    $title = lp_get_value($fields, 'wg-w-sms-button-title[]', 'Message');
                    $phone = preg_replace('/[^0-9+]/', '', lp_get_value($fields, 'wg-w-sms-phone-number[]', ''));
                    $msg = rawurlencode(lp_get_value($fields, 'wg-w-sms-msg[]', ''));
                    $url = $phone ? "sms:{$phone}?body={$msg}" : 'javascript:void(0);';
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-sms-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-link-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="sms" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($phone)
                    );

                    echo '<div class="widget-button message-widget">';
                    echo lp_render_action_button($title, $url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'uploadpdf': {
                    $title = lp_get_value($fields, 'wg-w-upload-pdf-title[]', 'Download');
                    $file_url = lp_get_value($fields, 'wg-w-upload-pdf-file_url', '');
                    $btn_type = strtolower(lp_get_value($fields, 'wg-w-upload-pdf-type[]', 'no images'));
                    $image = lp_get_value($fields, 'wg-w-link-file_url', '');

                    $data_attrs = sprintf(
                        'data-widget-type="uploadpdf" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($title),
                        esc_attr($file_url)
                    );

                    echo '<div class="widget-button uploadFile-widget">';
                    echo lp_render_action_button($title, $file_url, $btn_type === 'thumbnail' ? 'thumbnail' : ($btn_type === 'icons' ? 'icons' : ''), $image, 'trackable-link', $data_attrs);
                    echo '</div>';
                    break;
                }
            case 'seperator': {
                    $color = lp_get_value($fields, 'wg-w-separator-color', '#000000');
                    $thickness = lp_get_value($fields, 'wg-w-separator-t-number[]', 1);
                    echo '<div class="separator" style="background-color:' . esc_attr($color) . ';height:' . esc_attr($thickness) . 'px;"></div>';
                    break;
                }
            case 'text-box': {
                    $text = lp_get_value($fields, 'wg-w-text-headline[]', '');
                    $font = lp_get_value($fields, 'wg-w-text-headline-font[]', 'normal');
                    $color = lp_get_value($fields, 'wg-w-text-headline-color[]', '#000000');
                    
                    $align = lp_get_value($fields, 'wg-w-text-alignment', 'left');
                    
                    $is_bold = lp_get_value($fields, 'wg-w-text-style-bold', '0') === '1';
                    $is_italic = lp_get_value($fields, 'wg-w-text-style-italic', '0') === '1';
                    $is_underline = lp_get_value($fields, 'wg-w-text-style-underline', '0') === '1';
                    $is_strike = lp_get_value($fields, 'wg-w-text-style-strikethrough', '0') === '1';

                    $style_str = "color:" . esc_attr($color) . ";";
                    $style_str .= "text-align:" . esc_attr($align) . ";";
                    if ($is_bold) $style_str .= "font-weight:bold;";
                    if ($is_italic) $style_str .= "font-style:italic;";
                    
                    $decorations = [];
                    if ($is_underline) $decorations[] = 'underline';
                    if ($is_strike) $decorations[] = 'line-through';
                    
                    if (!empty($decorations)) {
                         $style_str .= "text-decoration:" . implode(' ', $decorations) . ";";
                    }

                    echo '<div class="text-paragraph">';
                    echo '<p style="' . $style_str . '" class="' . esc_attr($font) . '">' . wp_kses_post(nl2br($text)) . '</p>';
                    echo '</div>';
                    break;
                }
            case 'embaded-video': {
                    $video_url = lp_get_value($fields, 'wg-w-embaded-video-title[]', '');
                    $mute = lp_get_value($fields, 'wg-w-embaded-video-mute[]', '') ? '1' : '0';
                    $autoplay = lp_get_value($fields, 'wg-w-embaded-video-autoplay[]', '') ? '1' : '0';

                    if ($video_url) {
                        $glue = strpos($video_url, '?') === false ? '?' : '&';
                        $video_url .= "{$glue}mute={$mute}&autoplay={$autoplay}";
                    }

                    echo '<div class="video-widget"><div class="video-widget-inner">';
                    echo $video_url ? '<iframe src="' . esc_url($video_url) . '" allowfullscreen></iframe>' : '<p>No video URL provided.</p>';
                    echo '</div></div>';
                    break;
                }
            case 'img-gallery': {
                    $images = lp_get_list($fields, 'wg-w-link-file_urls');
                    if (empty($images)) {
                        $single = lp_get_value($fields, 'wg-w-link-file_url', '');
                        if (!empty($single)) {
                            $images = [$single];
                        }
                    }

                    if (empty($images)) {
                        echo '<div class="image-gallery"><p>No gallery images added.</p></div>';
                        break;
                    }

                    $layout = strtolower(lp_get_value($fields, 'wg-w-gallery-type[]', 'grid'));

                    echo '<div class="image-gallery"><div class="image-gallery-inner">';

                    if ($layout === 'carousel') {
                        echo '<div class="slick-slider image-gallery-slider">';
                        foreach ($images as $image) {
                            echo '<div class="image-gallery-slide"><img src="' . esc_url($image) . '" alt=""></div>';
                        }
                        echo '</div>';
                    }

                    echo '<div class="image-gallery-grid"><div class="image-gallery-grid-inner">';
                    foreach ($images as $image) {
                        echo '<div class="image-gallery-grid-box"><div class="image-gallery-grid-box-inner"><img src="' . esc_url($image) . '" alt=""></div></div>';
                    }
                    echo '</div></div>';

                    echo '</div></div>';
                    break;
                }
            case 'social': {
                    $headline = lp_get_value($fields, 'wg-w-sl-headline', 'Connect with us!');
                    $headline_color = lp_get_value($fields, 'wg-w-sl-headline-color[]', '#000000');

                    $links = [];
                    if (isset($fields['social_links']) && is_array($fields['social_links'])) {
                        $links = $fields['social_links'];
                    } elseif (!empty($fields['links']) && is_array($fields['links'])) {
                        $links = $fields['links'];
                    } elseif (!empty($fields['social_links']) && is_string($fields['social_links'])) {
                        $decoded = json_decode($fields['social_links'], true);
                        if (is_array($decoded)) {
                            $links = $decoded;
                        }
                    }

                    echo '<div class="social-link">';
                    if ($headline) {
                        echo '<div class="social-link-title"><h2 style="color:' . esc_attr($headline_color) . ';">' . esc_html($headline) . '</h2></div>';
                    }
                    if (!empty($links)) {
                        echo '<ul>';
                        foreach ($links as $link) {
                            $href = esc_url($link['url'] ?? 'javascript:void(0);');
                            $icon = esc_url($link['icon'] ?? '');
                            $alt = esc_attr($link['label'] ?? 'social');
                            $label = $link['label'] ?? 'Link';
                            
                            $data_attrs = sprintf(
                                'data-widget-type="social" data-widget-title="%s" data-widget-url="%s"',
                                esc_attr($label),
                                esc_attr($href)
                            );
                            
                            echo '<li><a href="' . $href . '" target="_blank" rel="noopener" class="trackable-link" ' . $data_attrs . '>';
                            if ($icon) {
                                echo '<img src="' . $icon . '" alt="' . $alt . '">';
                            } else {
                                echo esc_html($label);
                            }
                            echo '</a></li>';
                        }
                        echo '</ul>';
                    } else {
                        echo '<p style="text-align:center;">No social links added.</p>';
                    }
                    echo '</div>';
                    break;
                }
            case 'location': {
                    $headlines = lp_get_value($fields, 'wg-w-location-headline[]', 'Location');
                    $address = lp_get_value($fields, 'wg-w-location-address[]', '');
                    $lat = lp_get_value($fields, 'wg-w-location-lat[]', '');
                    $lng = lp_get_value($fields, 'wg-w-location-lng[]', '');
                    $show_map = lp_get_value($fields, 'wg-w-location-show-map[]', '') === '1';

                    $map_q = $address ? urlencode($address) : ($lat && $lng ? "{$lat},{$lng}" : '');
                    $map_url = $map_q ? 'https://www.google.com/maps/search/?api=1&query=' . $map_q : 'javascript:void(0);';

                    $data_attrs = sprintf(
                        'data-widget-type="location" data-widget-title="%s" data-widget-url="%s"',
                        esc_attr($headlines),
                        esc_attr($map_url)
                    );

                    if ($show_map && $map_q) {
                        // Render Card Layout with Map
                        echo '<div class="location-card">';
                        
                        // Header - clickable
                        echo '<a href="' . esc_url($map_url) . '" target="_blank" rel="noopener" class="location-header trackable-link" ' . $data_attrs . '>';
                        echo '<div class="location-icon-wrapper">';
                        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M215.7 499.2C267 435 384 279.4 384 192C384 86 298 0 192 0S0 86 0 192c0 87.4 117 243 168.3 307.2c12.3 15.3 35.1 15.3 47.4 0zM192 128a64 64 0 1 1 0 128 64 64 0 1 1 0-128z"/></svg>';
                        echo '</div>';
                        echo '<div class="location-details">';
                        echo '<span>' . esc_html($address ?: $headlines) . '</span>';
                        echo '</div>';
                        echo '</a>'; // End Header

                        // Map Body
                        echo '<div class="location-map-container">';
                        echo '<iframe src="https://maps.google.com/maps?q=' . $map_q . '&t=&z=13&ie=UTF8&iwloc=&output=embed" allowfullscreen></iframe>';
                        echo '</div>';
                        
                        echo '</div>'; // End Card

                    } else {
                        // Fallback to standard Button Layout
                        echo '<div class="location-widget">';
                        echo '<div class="widget-button">';
                        echo lp_render_action_button($headlines, $map_url, 'icons', '', 'btn-location trackable-link', $data_attrs);
                        echo '</div>';
                        if ($address) {
                            echo '<p style="text-align:center;margin-top:8px;">' . esc_html($address) . '</p>';
                        }
                        echo '</div>';
                    }
                    break;
                }
            case 'business-hours': {
                    $timezone = lp_get_value($fields, 'wg-w-bh-timezones[]', '');
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    $schedule = [];

                    if (!empty($fields['business_hours']) && is_array($fields['business_hours'])) {
                        foreach ($fields['business_hours'] as $day => $info) {
                            $schedule[$day] = [
                                'open' => !empty($info['is_open']),
                                'start' => $info['start_time'] ?? '',
                                'end' => $info['end_time'] ?? '',
                            ];
                        }
                    } else {
                        foreach ($days as $day) {
                            $open_flag = lp_get_value($fields, "wg-w-bh-{$day}-hours[]", '') ?: lp_get_value($fields, "wg-w-bh-{$day}-hours", '');
                            $start = lp_get_value($fields, "wg-w-bh-{$day}-hours-start[]", '');
                            $end = lp_get_value($fields, "wg-w-bh-{$day}-hours-end[]", '');
                            $schedule[$day] = [
                                'open' => !empty($open_flag),
                                'start' => $start,
                                'end' => $end,
                            ];
                        }
                    }

                    // Determine current status based on timezone and type
                    $timezone_str = $timezone ?: 'UTC'; // Default to UTC if empty
                    
                    try {
                        $dt = new DateTime("now", new DateTimeZone($timezone_str));
                        $current_day = strtolower($dt->format('l'));
                        $current_timestamp = $dt->getTimestamp();
                    } catch (Exception $e) {
                         // Fallback to server time
                        $dt = new DateTime("now");
                        $current_day = strtolower($dt->format('l'));
                        $current_timestamp = $dt->getTimestamp();
                    }

                    $type_mode = lp_get_value($fields, 'wg-w-bh-timezone-type', 'opening-closing');
                    $today_schedule = $schedule[$current_day] ?? ['open' => false];
                    
                    $status_main = 'CLOSED';
                    $status_sub = '';
                    $icon_check = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.47581 2 2 6.47581 2 12C2 17.5242 6.47581 22 12 22C17.5242 22 22 17.5242 22 12C22 6.47581 17.5242 2 12 2ZM15.7294 14.621L14.923 15.629C14.8701 15.6952 14.8046 15.7503 14.7304 15.7912C14.6562 15.832 14.5746 15.8579 14.4904 15.8673C14.4062 15.8766 14.321 15.8693 14.2396 15.8457C14.1582 15.8221 14.0823 15.7828 14.0161 15.7298L11.3145 13.725C11.1257 13.5739 10.9734 13.3822 10.8687 13.1642C10.764 12.9463 10.7096 12.7075 10.7097 12.4657V6.19355C10.7097 6.02244 10.7776 5.85834 10.8986 5.73735C11.0196 5.61636 11.1837 5.54839 11.3548 5.54839H12.6452C12.8163 5.54839 12.9804 5.61636 13.1014 5.73735C13.2224 5.85834 13.2903 6.02244 13.2903 6.19355V12L15.629 13.7137C15.6952 13.7667 15.7503 13.8321 15.7912 13.9064C15.8321 13.9807 15.8579 14.0623 15.8672 14.1465C15.8766 14.2308 15.8692 14.3161 15.8456 14.3975C15.8219 14.4789 15.7824 14.5548 15.7294 14.621Z" fill="var(--lp-btn-text)"></path></svg>';
                    
                    if ($type_mode === 'check-in-check-out') {
                        // Check-in/Check-out Logic (Just display times)
                         if ($today_schedule['open'] && !empty($today_schedule['start']) && !empty($today_schedule['end'])) {
                            $status_main = "CHECK IN: {$today_schedule['start']} ($timezone_str)";
                            $status_sub = "CHECK OUT: {$today_schedule['end']} ($timezone_str)";
                        } else {
                            $status_main = "UNAVAILABLE";
                        }
                    } else {
                        // Opening/Delivery Logic
                         if ($today_schedule['open'] && !empty($today_schedule['start']) && !empty($today_schedule['end'])) {
                             try {
                                $start_dt = new DateTime($today_schedule['start'], new DateTimeZone($timezone_str));
                                $end_dt = new DateTime($today_schedule['end'], new DateTimeZone($timezone_str));
                                
                                // Handling overnight hours if end < start? Assuming simplified same-day for now or simple comparison
                                // Use current date for start/end to compare with now
                                $start_dt->setDate($dt->format('Y'), $dt->format('m'), $dt->format('d'));
                                $end_dt->setDate($dt->format('Y'), $dt->format('m'), $dt->format('d'));
                                
                                if ($dt >= $start_dt && $dt <= $end_dt) {
                                    $status_main = ($type_mode === 'delivery') ? 'OPEN FOR DELIVERY' : 'OPEN NOW';
                                    $status_sub = "Closes {$today_schedule['end']} . {$today_schedule['start']} to {$today_schedule['end']} ($timezone_str)";
                                } else {
                                     $status_main = 'CLOSED';
                                     // Try to find next open slot? For simplicity, show today's hours
                                     $status_sub = "Opens {$today_schedule['start']} . {$today_schedule['start']} to {$today_schedule['end']} ($timezone_str)";
                                }

                             } catch (Exception $e) {
                                  // Invalid time format
                                  $status_sub = "{$today_schedule['start']} - {$today_schedule['end']}";
                             }
                        } else {
                             $status_main = 'CLOSED';
                             $status_sub = 'Closed Today';
                        }
                        
                         if ($type_mode === 'delivery') {
                            $icon_check = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 640 512" fill="var(--lp-btn-text)"><!--!Font Awesome Free 6.7.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M48 0C21.5 0 0 21.5 0 48L0 368c0 26.5 21.5 48 48 48l16 0c0 53 43 96 96 96s96-43 96-96l128 0c0 53 43 96 96 96s96-43 96-96l32 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l0-64 0-120c0-22.9-10.5-44.2-28.1-58.1L427.9 61c-13.3-10.4-29.3-17-46.7-18.7c-5.8-.6-11.7-.8-17.5-.5L416 42.4c0-23.4-19-42.4-42.4-42.4L48 0zM592 352a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM176 416a32 32 0 1 1 0 64 32 32 0 1 1 0-64zm320-32a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM112 304c-8.8 0-16-7.2-16-16s7.2-16 16-16l192 0c8.8 0 16 7.2 16 16s-7.2 16-16 16L112 304z"/></svg>';
                        }
                    }

                    echo '<div class="business-hours"><div class="business-hours-inner">';
                    echo '<div class="business-hours-header">';
                    echo '<div class="header-icon-left">' . $icon_check . '</div>';
                    echo '<div class="header-details">';
                    echo '<div class="status-now">' . esc_html($status_main) . '</div>';
                    if ($status_sub) {
                        echo '<div class="status-upcoming">' . esc_html($status_sub) . '</div>';
                    }
                    echo '</div>';
                    echo '<div class="header-icon-right"><svg class="arrow arrow-rotate" width="15" onclick="openDetails(this)" viewBox="0 0 12 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5.54653 7.35227L0.354396 2.16011C0.103983 1.90969 0.103983 1.50371 0.354396 1.25333L0.959974 0.647748C1.20996 0.397763 1.61511 0.397283 1.86569 0.64668L5.99993 4.76156L10.1342 0.64668C10.3847 0.397283 10.7899 0.397764 11.0399 0.647749L11.6454 1.25333C11.8959 1.50374 11.8959 1.90972 11.6454 2.16011L6.45331 7.35224C6.20292 7.60265 5.79694 7.60265 5.54653 7.35227Z" fill="var(--lp-btn-text)"></path></svg></div>';
                    echo '</div>';

                    echo '<div class="business-body">';
                    foreach ($schedule as $day => $info) {
                        $label = ucfirst($day);
                        $time = ($info['open'] && $info['start'] && $info['end']) ? "{$info['start']} - {$info['end']}" : 'Closed';
                        echo '<div class="row-container">';
                        echo '<div class="day">' . esc_html($label) . '</div>';
                        echo '<div class="day-time">' . esc_html($time) . '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div></div>';
                    break;
                }
            default: {
                    if (!empty($type)) {
                        echo '<div class="text-paragraph"><p>Unsupported widget type: ' . esc_html($type) . '</p></div>';
                    }
                }
        }

        return ob_get_clean();
    }
}

// Include Analytics Class
if (!class_exists('LinkPageAnalytics')) {
    require_once get_template_directory() . '/includes/class-linkpage-analytics.php';
}

// get_header();
while (have_posts()) : the_post();
    $linkpage_id = get_the_ID();
    
    // Track page view
    if (class_exists('LinkPageAnalytics')) {
        LinkPageAnalytics::track_view($linkpage_id);
    }
    
    $json_raw = get_post_meta($linkpage_id, 'link_page_json_data', true);
    $json_data = json_decode($json_raw, true);

    $common = is_array($json_data['common'] ?? null) ? $json_data['common'] : [];
    $widgets = is_array($json_data['widgets'] ?? null) ? $json_data['widgets'] : [];

    usort($widgets, function ($a, $b) {
        return intval($a['order'] ?? 0) <=> intval($b['order'] ?? 0);
    });

    $profile_image = lp_get_value($common, 'lp-profile-image-url', get_stylesheet_directory_uri() . '/assets/linkpages/images/default_profile.jpg');
    $title_text = lp_get_value($common, 'lp-title-text', get_the_title());
    $desc_text = lp_get_value($common, 'lp-desc-text', '');
    $title_color = lp_get_value($common, 'lp-title-text-color', '#000000');
    $desc_color = lp_get_value($common, 'lp-desc-text-color', '#000000');
    $font_family = lp_get_value($common, 'lp-font-family', 'Montserrat, sans-serif');

    $bg_type = strtolower(lp_get_value($common, 'lp-background-type[]', 'solid'));
    $bg_color = lp_get_value($common, 'lp-background-color', '#ffffff');
    $bg_image = lp_get_value($common, 'lp-background-image_url', '');
    $bg_video = lp_get_value($common, 'lp-background-video_url', '');
    $btn_bg = lp_get_value($common, 'lp-button-color', '#000000');
    $btn_text = lp_get_value($common, 'lp-button-text-color', '#ffffff');
    $btn_style = lp_get_value($common, 'btn_style', 'rounded');
    
    $profile_layout = lp_get_value($common, 'lp-profile-layout', 'profile');
    $layout_image = lp_get_value($common, 'lp_layout_image_url', '');
    $is_full_width = lp_get_value($common, 'lp-profile-cover-full-width', '0') === '1';

    // Title Styling
    $title_align = lp_get_value($common, 'lp-title-text-alignment', 'center');
    $title_bold = lp_get_value($common, 'lp-title-text-style-bold', '0') === '1';
    $title_italic = lp_get_value($common, 'lp-title-text-style-italic', '0') === '1';
    $title_underline = lp_get_value($common, 'lp-title-text-style-underline', '0') === '1';
    $title_strike = lp_get_value($common, 'lp-title-text-style-strikethrough', '0') === '1';

    // Description Styling
    $desc_align = lp_get_value($common, 'lp-desc-text-alignment', 'center');
    $desc_bold = lp_get_value($common, 'lp-desc-text-style-bold', '0') === '1';
    $desc_italic = lp_get_value($common, 'lp-desc-text-style-italic', '0') === '1';
    $desc_underline = lp_get_value($common, 'lp-desc-text-style-underline', '0') === '1';
    $desc_strike = lp_get_value($common, 'lp-desc-text-style-strikethrough', '0') === '1';

    $body_style = "font-family:{$font_family};";
    if ($bg_type === 'solid') {
        $body_style .= "background: {$bg_color};";
    } elseif ($bg_type === 'image' && $bg_image) {
        $body_style .= "background: url('{$bg_image}') center center / cover no-repeat;";
    }
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">

    <style>
        :root {
            --lp-btn-bg: <?php echo esc_attr($btn_bg); ?>;
            --lp-btn-text: <?php echo esc_attr($btn_text); ?>;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
            text-decoration: none;
        }

        .widget-layout-inner {
            max-width: 500px;
            margin: 0 auto;
            padding: 24px;
        }

        .page-header {
            display: flex;
            flex-direction: column;
            margin: 0 0 20px 0;
            position: relative;
        }

        /* Profile Layout (Default) */
        .page-header.layout-profile .page-header-thumbnail {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px auto;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* Banner Layout */
        .page-header.layout-banner .header-banner-image {
            width: 100%;
            height: 150px;
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            margin-bottom: -50px;
        }

        /* Full Width Banner Override */
        .page-header.layout-banner.full-width-banner .header-banner-image {
            width: calc(100% + 48px);
            margin-left: -24px;
            margin-right: -24px;
            border-radius: 0;
            margin-top: -24px;
            margin-bottom: -50px;
        }
        
        .page-header.layout-banner .page-header-thumbnail {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px auto;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            border: 4px solid #fff;
            position: relative;
            z-index: 1;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        /* Cover Layout */
        .page-header.layout-cover {
            border-radius: 10px;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            padding: 40px 20px;
            text-align: center;
        }
        .page-header.layout-cover .page-header-thumbnail {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #fff;
        }
        /* Minimal Layout */
        .page-header.layout-minimal {
            text-align: left;
            align-items: flex-start;
            padding: 0 10px;
        }
        .page-header.layout-minimal .page-header-title h1,
        .page-header.layout-minimal .page-header-description p {
           text-align: left; 
        }

        .page-header-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .page-header-title h1 {
            font-size: 30px;
            font-weight: <?php echo $title_bold ? 'bold' : '500'; ?>;
            text-align: <?php echo esc_attr($title_align); ?>;
            color: <?php echo esc_attr($title_color); ?>;
            font-style: <?php echo $title_italic ? 'italic' : 'normal'; ?>;
            text-decoration: <?php echo trim(($title_underline ? 'underline ' : '') . ($title_strike ? 'line-through' : '')); ?>;
            line-height: 1.2;
            margin: 0 0 20px 0;
        }

        .page-header-description p {
            font-size: 18px;
            text-align: <?php echo esc_attr($desc_align); ?>;
            line-height: 1.3;
            color: <?php echo esc_attr($desc_color); ?>;
            font-weight: <?php echo $desc_bold ? 'bold' : 'normal'; ?>;
            font-style: <?php echo $desc_italic ? 'italic' : 'normal'; ?>;
            text-decoration: <?php echo trim(($desc_underline ? 'underline ' : '') . ($desc_strike ? 'line-through' : '')); ?>;
            margin: 0;
            white-space: pre-line;
        }

        .separator {
            width: 100%;
            height: 1px;
            background-color: #000;
            margin: 20px 0;
        }

        .widget-button {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 20px;
        }

        .widget-button a.btn-widget {
            width: 100%;
            padding: 15px 20px;
            background-color: var(--lp-btn-bg);
            color: var(--lp-btn-text);
            font-size: 18px;
            line-height: 1;
            text-align: center;
            border-radius: <?php echo $btn_style === 'rounded' ? '10px' : '0'; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .widget-button a.with-icon span.icon-img {
            width: 50px;
            height: 100%;
            margin: 0 10px 0 0;
            border-radius: 0;
            overflow: hidden;
            position: absolute;
            left: 0;
        }

        .widget-button a.with-icon span.icon-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .widget-button a.with-thumbnail {
            flex-direction: column;
            padding: 0 0 20px 0;
        }

        .widget-button a.with-thumbnail span.thumbnail-img {
            width: 100%;
            height: 200px;
            margin: 0 0 18px 0;
        }

        .widget-button a.with-thumbnail span.thumbnail-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .social-link-title h2 {
            font-size: 24px;
            color: #000;
            line-height: 1.3;
            font-weight: 500;
            text-align: center;
            margin: 0 0 20px 0;
        }

        .social-link ul {
            margin: 0;
            padding: 0;
            list-style: none;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .social-link ul li {
            display: flex;
        }

        .social-link ul li a {
            display: flex;
            width: 70px;
        }

        .social-link ul li a img {
            width: 100%;
            height: 100%;
        }

        .text-paragraph p {
            font-size: 18px;
            text-align: center;
            line-height: 1.3;
            margin: 0 0 20px 0;
        }

        .text-paragraph p:last-child {
            margin: 0;
        }

        .html-widget {
            margin: 0 0 20px 0;
        }

        .html-widget p {
            font-size: 18px;
            color: #000;
            line-height: 1.3;
            font-weight: 500;
            margin: 0 0 10px 0;
        }

        .html-widget p:last-child {
            margin: 0;
        }

        .video-widget-inner iframe {
            width: 100%;
            aspect-ratio: 16/9;
            height: 100%;
        }

        .image-gallery-slider .slick-list {
            padding: 0 80px 0 0;
        }

        .image-gallery-slide.slick-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            max-height: 380px;
            object-position: center;
        }

        .image-gallery-inner {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .image-gallery-grid-inner {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .image-gallery-grid-box {
            width: calc(50% - 10px);
            display: flex;
        }

        .image-gallery-grid-box-inner img {
            width: 100%;
            height: 100%;
        }

        .image-gallery-grid-box-inner {
            border-radius: 10px;
            overflow: hidden;
        }

        a.btn-widget.btn-location span.location-icon {
            display: flex;
            position: absolute;
            left: 10px;
        }

        a.btn-widget.btn-location {
            padding-left: 40px;
            padding-right: 40px;
        }

        .widget-layout-main .business-hours-inner {
            color: var(--lp-btn-text);
            min-width: 95%;
            margin: 8px 4px;
            border-radius: <?php echo $btn_style === 'rounded' ? '10px' : '0'; ?>;
            background: var(--lp-btn-bg);
            box-shadow: none;
        }

        /* Location Card Design */
        .location-card {
            border-radius: <?php echo $btn_style === 'rounded' ? '10px' : '0'; ?>;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .location-header {
            background-color: var(--lp-btn-bg);
            color: var(--lp-btn-text);
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 80px;
            position: relative;
            text-decoration: none;
            cursor: pointer;
        }

        .location-header .location-icon-wrapper {
            margin-right: 15px;
            display: flex;
            align-items: center;
        }
        
        .location-header .location-icon-wrapper svg {
            fill: var(--lp-btn-text);
            width: 24px;
            height: 24px;
        }

        .location-header .location-details {
            text-align: center;
            font-size: 14px;
            line-height: 1.4;
            display: flex; 
            flex-direction: column;
            justify-content: center;
        }

        .location-map-container {
            height: 200px;
            width: 100%;
        }
        
        .location-map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }

        .widget-layout-main .business-hours-header {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }

        .widget-layout-main .header-icon-left {
            padding-left: 22px;
            padding-right: 4px;
        }

        .widget-layout-main .header-details {
            margin-top: 10px;
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
        }

        .widget-layout-main .status-now {
            font-weight: 700;
            margin-bottom: 3px;
            text-align: center;
        }

        .widget-layout-main .status-upcoming {
            font-size: 11px;
            margin-top: 3px;
            text-align: center;
        }

        .widget-layout-main .header-icon-right {
            padding-left: 4px;
            padding-right: 22px;
        }

        .business-body {
            border-top: 1px solid rgba(255, 255, 255, 0.30);
            display: none;
            flex-direction: column;
            row-gap: 10px;
            padding: 12px 5%;
        }

        .widget-layout-main .show .business-body {
            display: flex;
        }

        .widget-layout-main .show .header-icon-right svg {
            rotate: 180deg;
        }

        .widget-layout-main .business-body .row-container {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }

        .business-body .day {
            text-align: left;
            margin-left: 3%;
        }

        .business-body .day-time {
            min-width: 20ch;
            text-align: center;
        }

        .email-widget,
        .call-widget,
        .message-widget,
        .uploadFile-widget,
        .location-widget,
        .business-hours,
        .linkPage,
        .landingPage,
        .text-paragraph,
        .social-link,
        .html-widget,
        .video-widget,
        .image-gallery {
            margin: 0 0 20px 0;
        }
    </style>
</head>

<body style="<?php echo esc_attr($body_style); ?>">
    <?php if ($bg_type === 'video' && $bg_video) : ?>
        <video autoplay muted loop playsinline style="position: fixed; width: 100%; height: 100%; left: 0; top: 0; object-fit: cover; z-index: -1;">
            <source src="<?php echo esc_url($bg_video); ?>" type="video/mp4">
        </video>
    <?php endif; ?>

    <div class="widget-layout-main">
        <div class="widget-layout-inner" >
            <div class="page-header layout-<?php echo esc_attr($profile_layout); ?> <?php echo ($profile_layout === 'banner' && $is_full_width) ? 'full-width-banner' : ''; ?>" <?php echo ($profile_layout === 'cover' && $layout_image) ? 'style="background-image: url(' . esc_url($layout_image) . ');"' : ''; ?>>
                
                <?php if ($profile_layout === 'banner' && $layout_image) : ?>
                    <div class="header-banner-image" style="background-image: url('<?php echo esc_url($layout_image); ?>');"></div>
                <?php endif; ?>

                <?php if ($profile_layout !== 'minimal' && !empty($profile_image)) : ?>
                    <div class="page-header-thumbnail">
                        <img src="<?php echo esc_url($profile_image); ?>" alt="">
                    </div>
                <?php endif; ?>

                <div class="page-header-title">
                    <h1><?php echo esc_html($title_text); ?></h1>
                </div>

                <?php if (!empty($desc_text)) : ?>
                    <div class="page-header-description">
                        <p><?php echo wp_kses_post($desc_text); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php
            if (!empty($widgets)) {
                foreach ($widgets as $widget) {
                    echo lp_render_widget($widget, $common);
                }
            } else {
                echo '<p style="text-align:center;">No widgets added yet.</p>';
            }
            ?>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.slick/1.4.1/slick.min.js"></script>

    <script>
        // Initialize slick slider
        const sliders = document.querySelectorAll(".image-gallery-slider");
        if (sliders.length) {
            jQuery(".image-gallery-slider").slick({
                slidesToShow: 1,
                infinite: true,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                arrows: false,
            });
        }

        // Business hours toggle
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".business-hours-inner .header-icon-right").forEach(function(toggleBtn) {
                toggleBtn.addEventListener("click", function() {
                    const body = this.closest(".business-hours-inner");
                    body.classList.toggle("show");
                });
            });
            
            // Track clicks on all trackable links
            document.querySelectorAll('.trackable-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    const widgetType = this.getAttribute('data-widget-type');
                    const widgetTitle = this.getAttribute('data-widget-title');
                    const widgetUrl = this.getAttribute('data-widget-url');
                    
                    // Don't prevent default for actual navigation
                    // Send analytics in background
                    if (navigator.sendBeacon) {
                        const formData = new FormData();
                        formData.append('action', 'track_linkpage_click');
                        formData.append('linkpage_id', <?php echo $linkpage_id; ?>);
                        formData.append('widget_type', widgetType);
                        formData.append('widget_title', widgetTitle);
                        formData.append('widget_url', widgetUrl);
                        
                        navigator.sendBeacon('<?php echo admin_url('admin-ajax.php'); ?>', formData);
                    } else {
                        // Fallback to fetch
                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'track_linkpage_click',
                                linkpage_id: <?php echo $linkpage_id; ?>,
                                widget_type: widgetType,
                                widget_title: widgetTitle,
                                widget_url: widgetUrl
                            })
                        }).catch(function() {
                            // Silently fail - don't block navigation
                        });
                    }
                });
            });
        });
    </script>
</body>

</html>
<?php
endwhile;
// get_footer();