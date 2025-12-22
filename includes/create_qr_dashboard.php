<?php
add_shortcode('create_qr_codes', 'um_account_content_hook_create_qr_code');

add_filter('um_account_content_hook_create_qr_code', 'um_account_content_hook_create_qr_code');
function um_account_content_hook_create_qr_code($output)
{
    ob_start();
    
    // ==== EDIT MODE DETECTION ====
    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $output = '';
    $edit_data = [];
    $preview_id = 0;
    $existing_logo = "";
    $existing_body = "";
    $user_id = get_current_user_id();
    $user_levels = pmpro_getMembershipLevelsForUser( $user_id );

    if ( ! empty( $user_levels ) ) {
        echo "User has the following levels:\n";
        foreach ( $user_levels as $level ) {
            echo "- " . esc_html( $level->name ) . " (ID: " . $level->id . ")\n";
        }
    } else {
        echo "User has no active membership levels.\n";
    }

    

    if ($edit_id) {
        $edit_data = [
            'name'  => get_the_title($edit_id),
            'type'  => get_post_meta($edit_id, 'qr_type', true),
            'value' => get_post_meta($edit_id, 'qr_value', true),
        ];

        // Only for social-media QR
        if ($edit_data['type'] === 'social-media') {

            // Extract preview ID from stored URL
            $qr_value = $edit_data['value']; // example: /social-preview/?social_preview_id=1940
            $url_parts = wp_parse_url($qr_value);
            if (!empty($url_parts['query'])) {
                parse_str($url_parts['query'], $query_vars);
                $preview_id = isset($query_vars['social_preview_id'])
                    ? intval($query_vars['social_preview_id'])
                    : 0;
            }

            if ($preview_id) {
                $existing_logo = get_post_meta($preview_id, "logo_url", true) ?: "";
                $existing_body = get_post_meta($preview_id, "body_image_url", true) ?: "";
            }
        }
        $qr_is_static = get_post_meta($edit_id, 'qr_is_static', true);
        $qr_is_static = ($qr_is_static === '1') ? '1' : '0';
    }
    if ($edit_id): ?>
        <script>
            window.qrEditMode = {
                id: <?php echo $edit_id; ?>,
                type: "<?php echo esc_js($edit_data['type']); ?>",
                value: "<?php echo esc_js($edit_data['value']); ?>",
                name: "<?php echo esc_js($edit_data['name']); ?>",
                social_preview_id: <?php echo $preview_id; ?>,
                existing_logo: "<?php echo esc_js($existing_logo); ?>",
                existing_bodyImage: "<?php echo esc_js($existing_body); ?>",
                color: "<?php echo esc_js(get_post_meta($edit_id, 'qr_color', true)); ?>",
                shape: "<?php echo esc_js(get_post_meta($edit_id, 'qr_shape', true)); ?>",
                logo: "<?php echo esc_js(get_post_meta($edit_id, 'qr_logo', true)); ?>",
                is_static: "<?php echo esc_js($qr_is_static); ?>"

            };
            console.log("EDIT MODE DATA:", window.qrEditMode);
        </script>
    <?php endif; ?>

    <div class="um-field">
        <div class="topbar">
            <div class="user-info">
                <h4 class="dynamic_qr_title">Dynamic QR Codes</h4>
                <h4 class="static_qr_title">Static QR Codes</h4>
                <p>Modify content anytime, even after printing</p>
            </div>
        </div>
        <?php if ($edit_id): ?>
            <div class="editing-notice" style="padding:12px;background:#e8f2ff;border:1px solid #2b85ff;border-radius:6px;margin-bottom:15px;">
                <strong>You are editing QR Code #<?php echo $edit_id; ?></strong>
            </div>
            <button id="cancelEditBtn" class="btn btn-secondary" style="margin-bottom:15px;">
                Cancel Edit
            </button>
        <?php endif; ?>

        <div class="content">

            <div class="toggle-layouts create-qr">
                <ul class="nav nav-tabs" id="createQRtabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active"
                            id="step1-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#step1"
                            type="button" role="tab"
                            aria-controls="step1"
                            aria-selected="true">
                            1. Select type
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                            id="step2-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#step2"
                            type="button" role="tab"
                            aria-controls="step2"
                            aria-selected="false">
                            2. Setup
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                            id="step3-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#step3"
                            type="button" role="tab"
                            aria-controls="step3"
                            aria-selected="false">
                            3. Customize
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link"
                            id="step4-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#step4"
                            type="button" role="tab"
                            aria-controls="step4"
                            aria-selected="false">
                            4. Done
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="QRTabContent">
                    <!-- Step 1 Content -->
                    <div class="tab-pane fade show active" id="step1" role="tabpanel" aria-labelledby="step1-tab">
                        <div class="row card-row step-1 dynamic_qr_item">
                            <div class="col-md-3 card web" data-qr-type="web">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-link"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Website</h3>
                                        <p>Open any website URL</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card pdf"  data-qr-type="pdf">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>PDF</h3>
                                        <p>Show a PDF</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card mail email" data-qr-type="mail">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-envelope"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Mail</h3>
                                        <p>Send an email</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card social-media"  data-qr-type="social-media">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-regular fa-message"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Social Media</h3>
                                        <p>Share your social links</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card sms"  data-qr-type="sms">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-comment-sms"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>SMS</h3>
                                        <p>Send a text message</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card Linkpages" data-qr-type="linkpage">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Linkpage</h3>
                                        <p>Show a list of links</p>
                                    </div>
                                </a>
                            </div>

                        </div>
                        <div class="static-qrtitle-bar">
                            <h4>Static QR Codes
                                <span class="non-track">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" aria-hidden="true" role="img">
                                        <g fill="none" stroke="#2B85FF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <!-- outer circle -->
                                            <circle cx="12" cy="12" r="9.2" />
                                            <!-- diagonal slash -->
                                            <line x1="6.2" y1="6.2" x2="17.8" y2="17.8" />
                                        </g>
                                    </svg>
                                    NON - TRACKABLE
                                </span>
                                <span>
                                    <svg fill="#000000" width="800px" height="800px" viewBox="-6.5 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.813 11.406l-7.906 9.906c-0.75 0.906-1.906 0.906-2.625 0l-7.906-9.906c-0.75-0.938-0.375-1.656 0.781-1.656h16.875c1.188 0 1.531 0.719 0.781 1.656z"></path>
                                    </svg>
                                </span>
                            </h4>
                            <p>Create Permanent QR Codes that cannot be modified</p>
                        </div>
                        <div class="row card-row step-1 static_qr_item">
                            <div class="col-md-3 card web">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-link"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Website</h3>
                                        <p>Open any website URL</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card pdf" data-qr-type="pdf">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-file-pdf"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>PDF</h3>
                                        <p>Show a PDF</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card mail email"  data-qr-type="mail">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-envelope"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Mail</h3>
                                        <p>Send an email</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card social-media"  data-qr-type="social-media">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-regular fa-message"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Social Media</h3>
                                        <p>Share your social links</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card sms" data-qr-type="sms">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-comment-sms"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>SMS</h3>
                                        <p>Send a text message</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3 card Linkpages" data-qr-type="linkpage">
                                <a href="javascript:void(0)" class="d-flex flex-wrap gap-2">
                                    <div class="icon-box">
                                        <i class="fa-solid fa-up-right-from-square"></i>
                                    </div>
                                    <div class="text-box">
                                        <h3>Linkpage</h3>
                                        <p>Show a list of links</p>
                                    </div>
                                </a>
                            </div>

                        </div>
                    </div>

                    <!-- Step 2 Content -->
                    <div class="tab-pane fade" id="step2" role="tabpanel" aria-labelledby="step2-tab">
                        <div class="inner-section-setup web">
                            <div class="card">
                                <h5>Website URL</h5>
                                <p>Generate a QR Code for your website or any link you have</p>
                                <div id="webForm" class="form-wrapper">
                                    <div class="mb-3">
                                        <label class="form-label">Enter or paste URL</label>
                                        <input type="url" class="form-control web_form_url" placeholder="Enter or paste URL" required>
                                    </div>
                                    <button type="button" class="btn btn-primary">Next</button>
                                </div>
                            </div>
                        </div>

                        <div class="inner-section-setup pdf">
                            <div class="card">
                                <h5>Upload PDFs</h5>
                                <p>Generate QR Code for your PDFs in few clicks</p>
                                <div id="pdfForm" class="form-wrapper">
                                    <!-- File Upload -->
                                    <div class="upload-box mb-3">
                                        <input type="file" class="form-control mt-2" accept="application/pdf">
                                    </div>

                                    <!-- URL input -->
                                    <div class="mb-3">
                                        <label class="form-label">Or paste link below</label>
                                        <input type="url" class="form-control" placeholder="https://www.example.com">
                                    </div>

                                    <!-- Next button -->
                                    <button type="button" class="btn btn-primary">Next</button>
                                </div>
                            </div>
                        </div>
                        <div class="inner-section-setup mail">
                            <div class="card">
                                <h5>Email Information</h5>
                                <p>Specify details of the email message to link your QR code to</p>
                                <div id="emailForm" class="form-wrapper">


                                    <!-- To -->
                                    <div class="mb-3">
                                        <label class="form-label">To <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" placeholder="Enter emails separated by ','" multiple required>
                                        <small class="form-text text-muted">You can add multiple emails separated by a comma</small>
                                    </div>

                                    <!-- Subject -->
                                    <div class="mb-3">
                                        <label class="form-label">Subject</label>
                                        <input type="text" class="form-control" placeholder="Subject">
                                    </div>

                                    <!-- Body -->
                                    <div class="mb-3">
                                        <label class="form-label">Body</label>
                                        <textarea class="form-control" rows="4" placeholder="Body"></textarea>
                                    </div>

                                    <!-- Next Button -->
                                    <button type="button" class="btn btn-primary">Next</button>
                                </div>
                            </div>

                        </div>
                        <div class="inner-section-setup social-media">
                            <div class="d-flex">
                                <div class="card w-70">
                                    <h5>Social Media</h5>
                                    <p>Share social media pages that your QR code should link to</p>
                                    <div id="socialMediaForm" class="form-wrapper">
                                        <!-- Header Section -->
                                        <h5 class="mt-3">Header</h5>

                                        <!-- Logo Upload -->
                                        <div class="mb-3 header_logo_wrapper">
                                            <label class="form-label">Logo</label>
                                            <input type="file" class="form-control" accept="image/*">
                                            <small class="text-muted">
                                                Existing image is shown in preview. Upload a new image to replace it.
                                            </small>
                                            <!-- If uploaded, preview + remove option can be handled with JS -->
                                        </div>

                                        <!-- Title -->
                                        <div class="mb-3 header_title_wrapper">
                                            <label class="form-label">Title <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" placeholder="Enter title" required>
                                        </div>

                                        <!-- Title Color -->
                                        <div class="mb-3 header_title_color_wrapper">
                                            <label class="form-label">Title Color</label>
                                            <div class="input-group">
                                                <!-- <input type="text" class="form-control" placeholder="#ad63ff">
                                                <input type="color" class="form-control form-control-color" value="#ad63ff"> -->
                                                <input type="text" class="form-control" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor-title">
                                                <input type="color" class="form-control form-control-color" id="colorpicker-title" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">
                                            </div>
                                        </div>

                                        <!-- Body Section -->
                                        <h5 class="mt-3">Body</h5>

                                        <!-- Body Image -->
                                        <div class="mb-3 body_image_wrapper">
                                            <label class="form-label">Image</label>
                                            <input type="file" class="form-control" accept="image/*">
                                            <small class="text-muted">
                                                Existing image is shown in preview. Upload a new image to replace it.
                                            </small>
                                        </div>

                                        <!-- Message -->
                                        <div class="mb-3  body_message_wrapper">
                                            <label class="form-label">Message</label>
                                            <input type="text" class="form-control" placeholder="Enter message">
                                        </div>

                                        <!-- Subtext -->
                                        <div class="mb-3 body_subtext_wrapper">
                                            <label class="form-label">Subtext</label>
                                            <input type="text" class="form-control" placeholder="Enter subtext">
                                        </div>

                                        <!-- Background Color -->
                                        <div class="mb-3 body_bgcolor_wrapper">
                                            <label class="form-label">Background Color</label>
                                            <div class="input-group">
                                                <!-- <input type="text" class="form-control" placeholder="#f0efff">
                                                <input type="color" class="form-control form-control-color" value="#f0efff"> -->
                                                <input type="text" class="form-control" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor-background">
                                                <input type="color" class="form-control form-control-color" id="colorpicker-background" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">
                                            </div>
                                        </div>

                                        <!-- Links Section -->
                                        <h5 class="mt-3">Links <small class="text-muted">(At least one field is mandatory)</small></h5>
                                        <div class="qr-social_wrapper dashboard_social_wrapper">
                                            <div class="qr-social-inner dashboard_inner_wrap">
                                                <ul>
                                                    <li class="qr-active-social" data-tab="qr-socail-1">
                                                        <button class="qr-social-img" type="button">
                                                            <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png" alt="Instagram">
                                                        </button>
                                                    </li>

                                                    <li data-tab="qr-socail-2">
                                                        <button class="qr-social-img" type="button">
                                                            <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png" alt="Youtube">
                                                        </button>
                                                    </li>

                                                    <li data-tab="qr-socail-3">
                                                        <button class="qr-social-img" type="button">
                                                            <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png" alt="Twitter">
                                                        </button>
                                                    </li>

                                                    <li data-tab="qr-socail-4">
                                                        <button class="qr-social-img" type="button">
                                                            <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png" alt="Facebook">
                                                        </button>
                                                    </li>
                                                </ul>

                                                <div class="qr-social-content_wrapper dashboard_social_content_wrapper">
                                                    <div class="qr-social-inner">
                                                        <div class="qr-social-feild qr-active-social" id="qr-socail-1">
                                                            <div class="social-field-img">
                                                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png" alt="Instagram">
                                                            </div>

                                                            <div class="social-input">
                                                                <input type="url" placeholder="Instagram URL">
                                                            </div>

                                                            <div class="socail-close-icon">
                                                                <span></span>
                                                            </div>
                                                        </div>

                                                        <div class="qr-social-feild" id="qr-socail-2">
                                                            <div class="social-field-img">
                                                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png" alt="Youtube">
                                                            </div>

                                                            <div class="social-input">
                                                                <input type="url" placeholder="Youtube URL">
                                                            </div>

                                                            <div class="socail-close-icon">
                                                                <span></span>
                                                            </div>
                                                        </div>

                                                        <div class="qr-social-feild" id="qr-socail-3">
                                                            <div class="social-field-img">
                                                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png" alt="Twitter">
                                                            </div>

                                                            <div class="social-input">
                                                                <input type="url" placeholder="Twitter URL">
                                                            </div>

                                                            <div class="socail-close-icon">
                                                                <span></span>
                                                            </div>
                                                        </div>

                                                        <div class="qr-social-feild" id="qr-socail-4">
                                                            <div class="social-field-img">
                                                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png" alt="Facebook">
                                                            </div>

                                                            <div class="social-input">
                                                                <input type="url" placeholder="Facebook URL">
                                                            </div>

                                                            <div class="socail-close-icon">
                                                                <span></span>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Next Button -->
                                        <button type="button" id="social_next" class="btn btn-primary">Next</button>
                                    </div>
                                </div>
                                <div class="preview-wrapper main-preview_wrapper social-media-preview_wrapper">
                                    <div class="preview card">
                                        <div class="header">
                                            <a href="javascript:void(0)" class="header-logo-link">
                                                <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/SimplQR-Logo-blue-qr.png" alt="Logo" class="logo-img" />
                                            </a>
                                            <h5>Follow Us !!!</h5>
                                        </div>

                                        <img class="cube-image" src="<?php echo site_url(); ?>/wp-content/uploads/2025/09/9cd2fab8-1ce5-420e-8a64-aa515258f07c-scaled.jpg" alt="Blue Cube" class="banner-img" />

                                        <div class="content body-content-wrapper" style="background-color: rgb(0, 0, 0);">
                                            <h5></h5>
                                            <p></p>
                                        </div>

                                        <div class="footer">

                                        </div>
                                    </div>
                                </div>
                            </div>


                        </div>
                        <div class="inner-section-setup sms">
                            <div class="card">
                                <h5>SMS Information</h5>
                                <p>Specify details of the text (SMS) to link your QR code to</p>
                                <div id="smsForm" class="form-wrapper">
                                    <!-- Phone number -->
                                    <div class="mb-3">
                                        <label class="form-label">Phone number <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" placeholder="Enter phone number" required>
                                    </div>

                                    <!-- Message -->
                                    <div class="mb-3">
                                        <label class="form-label">Message</label>
                                        <textarea class="form-control" rows="4" placeholder="Enter Message"></textarea>
                                    </div>

                                    <!-- Next Button -->
                                    <button type="button" class="btn btn-primary">Next</button>
                                </div>
                            </div>
                        </div>
                        <div class="inner-section-setup linkpage">
                            <div class="d-flex">
                                <div class="card w-70">
                                    <h5>Select a Linkpage</h5>
                                    <p>Choose or create a linkpage to link to your QR code</p>
                                    <div id="linkpagesForm" class="form-wrapper">
                                        <div class="mb-3">
                                           <select class="form-control" id="linkpageSelect">
                                                <option value="">Select a Linkpage</option>

                                                <?php
                                                $linkpages = get_posts(array(
                                                    'post_type'      => 'link_page',
                                                    'posts_per_page' => -1,
                                                    'post_status'    => 'publish'
                                                ));

                                                foreach ($linkpages as $linkpage) {
                                                    echo '<option value="' . esc_attr($linkpage->post_name) . '">'
                                                        . esc_html($linkpage->post_title) .
                                                        '</option>';
                                                }
                                                ?>
                                            </select>
                                            <div class="btn-row">
                                                <a href="<?php echo site_url(); ?>/membership-account/create_linkpage/" 
                                                class="btn edit-linkpage">
                                                    Edit Linkpage
                                                </a>

                                                <a href="<?php echo site_url(); ?>/membership-account/create_linkpage/" 
                                                class="btn btn-primary create-new-linkpage">
                                                    <svg fill="#000000" width="20px" height="20px" viewBox="-4.5 -4.5 24 24"
                                                        xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMinYMin"
                                                        class="jam jam-plus">
                                                        <path d="M8.9 6.9v-5a1 1 0 1 0-2 0v5h-5a1 1 0 1 0 0 2h5v5a1 1 0 1 0 2 0v-5h5a1 1 0 1 0 0-2h-5z" />
                                                    </svg>
                                                    Create New Linkpage
                                                </a>
                                            </div>

                                            <!-- <div class="btn-row">
                                                <button type="button" class="btn edit-linkpage">Edit Linkpage</button>
                                                <button type="button" class="btn btn-primary create-new-linkpage">
                                                    <svg fill="#000000" width="20px" height="20px" viewBox="-4.5 -4.5 24 24" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMinYMin" class="jam jam-plus"><path d='M8.9 6.9v-5a1 1 0 1 0-2 0v5h-5a1 1 0 1 0 0 2h5v5a1 1 0 1 0 2 0v-5h5a1 1 0 1 0 0-2h-5z' /></svg>
                                                    Create New Linkpage
                                                </button>
                                                
                                            </div> -->
                                        </div>
                                        <button type="button" class="linkapge_next btn btn-primary">Next</button>
                                    </div>
                                </div>
                                <div class="preview-wrapper main-preview_wrapper linkpages-preview_wrapper">
                                    <div class="preview card">
                                        <iframe
                                            id="linkpagePreview"
                                            src=""
                                            style="width:100%; height:600px; border:none; border-radius:12px;"
                                            loading="lazy">
                                        </iframe>
                                    </div>
                                </div>

                                <!-- <div class="preview-wrapper main-preview_wrapper linkpages-preview_wrapper ">
                                    <div class="preview card">
                                        <div class="header">
                                            <a href="javascript:void(0)" class="header-logo-link">
                                                <img decoding="async" src="https://simplqr.com/wp-content/uploads/2025/08/SimplQR-Logo-blue-qr.png" alt="Logo" class="logo-img">
                                            </a>
                                            <h5>Follow Us !!!</h5>
                                        </div>

                                        <img decoding="async" class="cube-image" src="https://simplqr.com/wp-content/uploads/2025/09/9cd2fab8-1ce5-420e-8a64-aa515258f07c-scaled.jpg" alt="Blue Cube">

                                        <div class="content body-content-wrapper" style="background-color: rgb(0, 0, 0);">
                                            <h5></h5>
                                            <p></p>
                                        </div>

                                        <div class="footer">

                                        </div>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="step3" role="tabpanel" aria-labelledby="step3-tab">
                        <div class="inner-wrapper inner-section-setup d-flex">
                            <div class="card w-70">
                                <div class="qr-inner-tab_wrapper">
                                    <ul class="qr-inner-tab-nav">
                                        <li class="qr-inner-tab active-inner-tab" data-tab="qr-inner-tab1">
                                            <span class="qr-tab-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M13 15c-2 0 -5 1 -5 5"></path>
                                                    <path d="M4 11m0 2a2 2 0 0 1 2 -2h5a2 2 0 0 1 2 2v5a2 2 0 0 1 -2 2h-5a2 2 0 0 1 -2 -2z"></path>
                                                    <path d="M4 7v-2a1 1 0 0 1 1 -1h2"></path>
                                                    <path d="M11 4h2"></path>
                                                    <path d="M17 4h2a1 1 0 0 1 1 1v2"></path>
                                                    <path d="M20 11v2"></path>
                                                    <path d="M20 17v2a1 1 0 0 1 -1 1h-2"></path>
                                                </svg>
                                            </span>
                                            <span>Logos</span>
                                        </li>

                                        <li class="qr-inner-tab" data-tab="qr-inner-tab2">
                                            <span class="qr-tab-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M14 4h6v6h-6z"></path>
                                                    <path d="M4 14h6v6h-6z"></path>
                                                    <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                                    <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                                </svg>
                                            </span>
                                            <span>Shapes</span>
                                        </li>

                                        <li class="qr-inner-tab" data-tab="qr-inner-tab3">
                                            <span class="qr-tab-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 17c0 -5.523 -4.477 -10 -10 -10s-10 4.477 -10 10"></path>
                                                    <path d="M18 17a6 6 0 1 0 -12 0"></path>
                                                    <path d="M14 17a2 2 0 1 0 -4 0"></path>
                                                </svg>

                                            </span>
                                            <span>Colors</span>
                                        </li>

                                    </ul>

                                    <div class="qr-inner-tab-content">
                                        <div class="qr-inner-tab-pane active-inner-tab" id="qr-inner-tab1">
                                            <div class="qr-inner-title">
                                                <h4>Select Logo</h4>
                                            </div>
                                            <div class="qr-tab-logos">
                                                <button class="btn-logos dynamic-social-logo">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png" alt="Instagram">
                                                </button>

                                                <button class="btn-logos dynamic-social-logo">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png" alt="Youtube">
                                                </button>

                                                <button class="btn-logos dynamic-social-logo">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png" alt="Twitter">
                                                </button>

                                                <button class="btn-logos dynamic-social-logo">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png" alt="Facebook">
                                                </button>
                                            </div>

                                            <div class="qr-inner-title">
                                                <h4>OR</h4>
                                            </div>

                                            <div class="qr-upload-col">
                                                <div class="upload-button_wrapper upload_logo_wrapper">
                                                    <button class="btn-upload">Upload Logo</button>
                                                    <span class="file-name"></span>
                                                    <input type="file" name="myfile" id="dynamic-qr-logo">
                                                </div>


                                                <div class="reset-button">
                                                    <button class="btn-upload">Reset to Default</button>
                                                </div>
                                            </div>

                                        </div>

                                        <div class="qr-inner-tab-pane" id="qr-inner-tab2">
                                            <div class="qr-inner-title">
                                                <h4>Select Shapes</h4>
                                            </div>
                                            <div class="qr-tab-logos qr-tab-shapes">
                                                <button class="btn-logos dynamic-btn-shape btn-shapes">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-3.png" alt="custom-qr-3">
                                                    <span>Squre</span>
                                                </button>

                                                <button class="btn-logos dynamic-btn-shape btn-shapes">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-4.png" alt="custom-qr-4">
                                                    <span>Dots</span>
                                                </button>

                                                <button class="btn-logos dynamic-btn-shape btn-shapes">
                                                    <img decoding="async" src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-5.png" alt="custom-qr-5">
                                                    <span>Rounded</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="qr-inner-tab-pane" id="qr-inner-tab3">
                                            <div class="qr-inner-title">
                                                <h4>Select Color</h4>
                                            </div>
                                            <div class="qr-tab-logos qr-tab-color">

                                                <div class="qr-color-picker">
                                                    <input type="color" id="colorpicker" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">

                                                    <input type="text" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor">
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                            <div class="preview-wrapper">
                                <div class="preview card" style="padding: 20px;border: 5px solid #0067b4;">
                                    <div id="qrPreviewContainer" class="preview-qr-img qr-preview step3-qr">
                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/scan-me-qr-code.png" alt="Qr">
                                    </div>
                                    <div class="preview-qr-next">
                                        <button class="btn btn-primary">Next</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="step4" role="tabpanel" aria-labelledby="step4-tab">
                        <div class="inner-wrapper inner-section-setup d-flex">
                            <div class="card w-70">
                                <div class="qr-inner-tab_wrapper">
                                    <h5>Congratulations! Your QR Code is ready</h5>
                                    <p> Still want to make changes? <a href="javascript:void(0)">Modify your QR code</a></p>

                                    <div class="form-wrapper" id="post_title_wrapper">

                                        <label class="form-label">Enter a name for your QR code*</label>
                                        <input type="text" class="form-control name_qr_wrapper" placeholder="Enter name for your QR code" required>

                                    </div>
                                </div>
                            </div>
                            <div class="preview-wrapper">
                                <div class="preview-qr">
                                    <div class="preview card">
                                        <div class="preview-qr-img">
                                            <div id="qrFinalPreview"></div> <!-- ✅ QR code will appear here -->
                                            <div class="preview-qr-next">
                                                <button type="button" class="btn btn-primary finishQRBtn" id="finishQRBtn">FINISH</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="social-media-preview social-media step4-preview-part" style="display: block;">
                                    <ul class="qr-tabs-nav">
                                        <li class="qr-tab social_tab active" data-tab="tab1"><span>QR Code</span></li>
                                        <li class="qr-tab social_tab" data-tab="tab2"><span>Preview</span></li>
                                    </ul>

                                    <div class="qr-tabs-content">

                                        <!-- QR Code Tab -->
                                        <div class="qr-tab-pane active" id="tab1">
                                            <div class="preview card">
                                                <div class="preview-qr-img qr-preview step3-qr" id="step3QrInsideStep4"></div>
                                                <div class="preview-qr-next">
                                                    <button type="button" class="btn btn-primary finishQRBtn" id="finishQRBtn">FINISH</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Social Preview Tab -->
                                        <div class="qr-tab-pane" id="tab2">
                                            <div class="preview card social-preview-card step4_final_preview" id="socialPreviewContainer">
                                                <div class="header">
                                                    <a href="javascript:void(0)" class="header-logo-link">
                                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/SimplQR-Logo-blue-qr.png" alt="Logo" class="logo-img" />
                                                    </a>
                                                    <h5 class="social-title">Follow Us !!!</h5>
                                                </div>

                                                <img class="cube-image" src="<?php echo site_url(); ?>/wp-content/uploads/2025/09/9cd2fab8-1ce5-420e-8a64-aa515258f07c-scaled.jpg" alt="Blue Cube" class="banner-img" />

                                                <div class="content body-content-wrapper">
                                                    <h5 class="social-message"></h5>
                                                    <p class="social-subtext"></p>
                                                </div>

                                                <div class="footer  social-links">

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="link-pages-preview step4-preview-part" style="display: block;">
                                    <ul class="qr-tabs-nav">
                                        <li class="qr-tab social_tab active" data-tab="linkpages-preview-tab1"><span>QR Code</span></li>
                                        <li class="qr-tab social_tab" data-tab="linkpages-preview-tab2"><span>Preview</span></li>
                                    </ul>

                                    <div class="qr-tabs-content">

                                        <!-- QR Code Tab -->
                                        <div class="qr-tab-pane active" id="linkpages-preview-tab1">
                                            <div class="preview card">
                                                <div class="preview-qr-img qr-preview step3-qr" id="step3QrInsideStep4"></div>
                                                <div class="preview-qr-next">
                                                    <button type="button" class="btn btn-primary finishQRBtn" id="finishQRBtn">FINISH</button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Social Preview Tab -->
                                        <div class="qr-tab-pane" id="linkpages-preview-tab2">
                                            <div class="preview card link-pages-preview-card step4_final_preview" id="socialPreviewContainer">
                                                <div class="header">
                                                    <a href="javascript:void(0)" class="header-logo-link">
                                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/SimplQR-Logo-blue-qr.png" alt="Logo" class="logo-img" />
                                                    </a>
                                                    <h5 class="social-title">Follow Us !!!</h5>
                                                </div>

                                                <img class="cube-image" src="<?php echo site_url(); ?>/wp-content/uploads/2025/09/9cd2fab8-1ce5-420e-8a64-aa515258f07c-scaled.jpg" alt="Blue Cube" class="banner-img" />

                                                <div class="content body-content-wrapper">
                                                    <h5 class="social-message"></h5>
                                                    <p class="social-subtext"></p>
                                                </div>

                                                <div class="footer  social-links">

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
        </div>




    </div>

    <script>
   
        document.addEventListener("DOMContentLoaded", function() {
            

            const dynamicTitle = document.querySelector(".dynamic_qr_title");
            const staticTitle = document.querySelector(".static_qr_title");

            const dynamicCards = document.querySelectorAll(".dynamic_qr_item .card");
            const staticCards = document.querySelectorAll(".static_qr_item .card");

            // const linkpagesSection = document.querySelector(".inner-section-setup.Linkpages");

            /* ===== DEFAULT STATE ===== */
            dynamicTitle.style.display = "block";
            staticTitle.style.display = "none";

            // linkpagesSection.classList.add("is-dynamic");
            // linkpagesSection.classList.remove("is-static");

            /* ===== DYNAMIC CLICK ===== */
            dynamicCards.forEach(card => {
                card.addEventListener("click", () => {
                    // Title toggle
                    dynamicTitle.style.display = "block";
                    staticTitle.style.display = "none";

                    // Linkpages state
                    // linkpagesSection.classList.add("is-dynamic");
                    // linkpagesSection.classList.remove("is-static");
                });
            });

            /* ===== STATIC CLICK ===== */
            staticCards.forEach(card => {
                card.addEventListener("click", () => {
                    // Title toggle
                    staticTitle.style.display = "block";
                    dynamicTitle.style.display = "none";

                    // Linkpages state
                    // linkpagesSection.classList.add("is-static");
                    // linkpagesSection.classList.remove("is-dynamic");
                });
            });




            // 4️⃣ Pre-select QR type based on existing data
            const staticQrBar = document.querySelector(".static-qrtitle-bar");
            const staticQrItem = document.querySelector(".static_qr_item");

            staticQrBar.addEventListener("click", function() {
                // Toggle visibility
                if (staticQrItem.style.display === "none" || staticQrItem.style.display === "") {
                    staticQrItem.style.display = "flex"; // or "block" depending on your layout
                } else {
                    staticQrItem.style.display = "none";
                }
            });

            if (!window.qrEditMode) return;

            console.log("Edit Mode Activated:", window.qrEditMode);

            // 1️⃣ Highlight “Editing Mode” visually
            // const notice = document.createElement("div");
            // notice.className = "editing-notice";
            // notice.style.cssText = "padding:12px;background:#e8f2ff;border:1px solid #2b85ff;border-radius:6px;margin-bottom:15px;";
            // notice.innerHTML = `<strong>You are editing QR Code #${window.qrEditMode.id}</strong>`;
            // document.querySelector(".content").prepend(notice);

            // 2️⃣ Change FINISH button to UPDATE QR
            document.querySelectorAll('.finishQRBtn').forEach(btn => {
                btn.textContent = "UPDATE QR";
            });

            // 3️⃣ Lock Step 1 — prevent changing type
            document.querySelector('#step1-tab').classList.add("disabled");
            document.querySelectorAll('.step-1 .card').forEach(card => {
                card.style.pointerEvents = "none";
                card.style.opacity = "0.5";
            });
            if (window.qrEditMode) {
                document.querySelector("#cancelEditBtn").addEventListener("click", function() {
                    window.location.href = "/membership-account/create_qr_code"; // remove edit_id
                });
            }
        });
    </script>




<?php

    $output .= ob_get_contents();
    ob_end_clean();
    return $output;
}
add_action('wp_ajax_upload_pdf_file', 'create_qr_handle_pdf_upload');
add_action('wp_ajax_nopriv_upload_pdf_file', 'create_qr_handle_pdf_upload');

function create_qr_handle_pdf_upload()
{
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }

    if (!isset($_FILES['pdf_file'])) {
        wp_send_json(['success' => false, 'message' => 'No file uploaded.']);
    }

    $uploaded_file = $_FILES['pdf_file'];
    $upload_overrides = ['test_form' => false, 'mimes' => ['pdf' => 'application/pdf']];

    $movefile = wp_handle_upload($uploaded_file, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $filetype = wp_check_filetype($movefile['file'], null);

        $attachment = [
            'guid'           => $movefile['url'],
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name(basename($movefile['file'])),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $movefile['file']);
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        wp_send_json(['success' => true, 'url' => $movefile['url'], 'id' => $attach_id]);
    } else {
        wp_send_json(['success' => false, 'message' => $movefile['error']]);
    }
}

add_action('wp_ajax_save_styled_qr_image', 'save_styled_qr_image');
add_action('wp_ajax_nopriv_save_styled_qr_image', 'save_styled_qr_image');

function save_styled_qr_image()
{
    $post_id  = intval($_POST['post_id'] ?? 0);
    $qr_image = $_POST['qr_image'] ?? '';

    if (!$post_id || !$qr_image) {
        wp_send_json_error(['message' => 'Missing data']);
    }

    if (preg_match('/^data:image\/(\w+);base64,/', $qr_image, $type)) {
        $data = base64_decode(substr($qr_image, strpos($qr_image, ',') + 1));
        $ext  = strtolower($type[1]);

        // Save via WP API
        $filename = 'qr-' . $post_id . '-' . time() . '.' . $ext;
        $upload   = wp_upload_bits($filename, null, $data);

        if (!$upload['error']) {
            $filetype = wp_check_filetype($upload['file'], null);

            $attachment = [
                'guid'           => $upload['url'],
                'post_mime_type' => $filetype['type'],
                'post_title'     => sanitize_file_name($filename),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'post_parent'    => $post_id,
            ];

            $attach_id   = wp_insert_attachment($attachment, $upload['file'], $post_id);
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);

            update_post_meta($post_id, 'qr_image', esc_url_raw($upload['url']));
            update_post_meta($post_id, 'qr_image_id', intval($attach_id));

            wp_send_json_success(['url' => $upload['url'], 'id' => $attach_id]);
        }
    }

    wp_send_json_error(['message' => 'Invalid image data']);
}


add_action('wp_ajax_create_qr_code_post', 'create_qr_code_post');
add_action('wp_ajax_nopriv_create_qr_code_post', 'create_qr_code_post');

function create_qr_code_post()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to create a QR code.']);
    }

    $user_id   = get_current_user_id();
    $qr_name   = sanitize_text_field($_POST['qr_name'] ?? '');
    $qr_type   = sanitize_text_field($_POST['qr_type'] ?? '');
    $qr_value  = trim($_POST['qr_value'] ?? '');
    $qr_data   = sanitize_text_field($_POST['qr_data'] ?? '');
    $is_static = ($_POST['is_static'] ?? '0') === '1' ? '1' : '0';

    if (empty($qr_name) || empty($qr_type) || empty($qr_value)) {
        wp_send_json_error(['message' => 'Missing required QR data.']);
    }

    /**
     * ===============================
     * PLAN LIMITS (PMPro)
     * ===============================
     */
    $limits = simplqr_get_plan_limits($user_id);

    if (!$limits) {
        wp_send_json_error(['message' => 'No active membership plan found.']);
    }

    /**
     * ===============================
     * COUNT EXISTING QRs
     * ===============================
     */
    $existing_dynamic_qrs = get_posts([
        'post_type'      => 'qr_code',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'   => 'qr_is_static',
                'value' => '0',
            ],
        ],
        'fields' => 'ids'
    ]);

    $existing_static_qrs = get_posts([
        'post_type'      => 'qr_code',
        'author'         => $user_id,
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'   => 'qr_is_static',
                'value' => '1',
            ],
        ],
        'fields' => 'ids'
    ]);

    /**
     * ===============================
     * ENFORCE PLAN LIMITS
     * ===============================
     */
    if ($is_static === '0') {
        if ($limits['dynamic_qr'] !== -1 && count($existing_dynamic_qrs) >= $limits['dynamic_qr']) {
            wp_send_json_error([
                'message' => 'You have reached your Dynamic QR limit. Please upgrade your plan.'
            ]);
        }
    } else {
        if ($limits['static_qr'] !== -1 && count($existing_static_qrs) >= $limits['static_qr']) {
            wp_send_json_error([
                'message' => 'You have reached your Static QR limit.'
            ]);
        }
    }

    /**
     * ===============================
     * SANITIZE QR VALUE (URL / DEEP LINK)
     * ===============================
     */
    $scheme = strtolower((string) parse_url($qr_value, PHP_URL_SCHEME));
    if (in_array($scheme, ['http', 'https'])) {
        $qr_value = esc_url_raw($qr_value);
    } else {
        // mailto:, sms:, tel:, etc
        $qr_value = trim($qr_value);
    }

    /**
     * ===============================
     * CREATE QR POST
     * ===============================
     */
    $post_id = wp_insert_post([
        'post_title'  => $qr_name,
        'post_type'   => 'qr_code',
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    if (!$post_id || is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Failed to create QR code.']);
    }

    /**
     * ===============================
     * SAVE META
     * ===============================
     */
    update_post_meta($post_id, 'qr_is_static', $is_static);
    update_post_meta($post_id, 'qr_type', $qr_type);
    update_post_meta($post_id, 'qr_value', $qr_value);
    update_post_meta($post_id, 'qr_data', $qr_data);
    update_post_meta($post_id, 'qr_user_id', $user_id);
    update_post_meta($post_id, 'qr_scan_count', 0);

    update_post_meta($post_id, 'qr_color', sanitize_hex_color($_POST['qr_color'] ?? '#000000'));
    update_post_meta($post_id, 'qr_shape', sanitize_text_field($_POST['qr_shape'] ?? 'square'));
    update_post_meta($post_id, 'qr_logo', sanitize_text_field($_POST['qr_logo'] ?? ''));

    /**
     * ===============================
     * TRACKING URL (DYNAMIC ONLY)
     * ===============================
     */
    if ($is_static === '0') {
        $scan_url = home_url("/qr/{$post_id}");
        update_post_meta($post_id, 'qr_scan_url', $scan_url);
    }

    /**
     * ===============================
     * SUCCESS RESPONSE
     * ===============================
     */
    wp_send_json_success([
        'post_id' => $post_id,
        'qr_url'  => $is_static === '0'
            ? get_post_meta($post_id, 'qr_scan_url', true)
            : $qr_value,
        'remaining_dynamic_qrs' =>
            $limits['dynamic_qr'] === -1
                ? 'unlimited'
                : max(0, $limits['dynamic_qr'] - count($existing_dynamic_qrs) - ($is_static === '0' ? 1 : 0))
    ]);
}

// add_action('wp_ajax_create_qr_code_post', 'create_qr_code_post');
// add_action('wp_ajax_nopriv_create_qr_code_post', 'create_qr_code_post');

// function create_qr_code_post()
// {
//     $qr_name   = sanitize_text_field($_POST['qr_name'] ?? '');
//     $user_id   = intval($_POST['user_id'] ?? 0);
//     $limits  = simplqr_get_plan_limits($user_id);
//     $user_name = sanitize_text_field($_POST['user_name'] ?? '');
//     $qr_type   = sanitize_text_field($_POST['qr_type'] ?? '');
//     $qr_value  = isset($_POST['qr_value']) ? trim($_POST['qr_value']) : '';
//     $qr_data   = sanitize_text_field($_POST['qr_data'] ?? '');

//     if (!$qr_name) {
//         wp_send_json(['success' => false, 'message' => 'Missing QR name']);
//     }

//     // keep deep links intact; only sanitize http(s)
//     $scheme = strtolower((string) parse_url($qr_value, PHP_URL_SCHEME));
//     if (in_array($scheme, ['http', 'https'])) {
//         $qr_value = esc_url_raw($qr_value);
//     } else {
//         $qr_value = trim($qr_value); // mailto:, sms:, tel:, etc.
//     }

//     $post_id = wp_insert_post([
//         'post_title'  => $qr_name,
//         'post_type'   => 'qr_code',
//         'post_status' => 'publish',
//         'post_author' => $user_id ?: 0,
//     ]);

//     if (!$post_id || is_wp_error($post_id)) {
//         wp_send_json(['success' => false, 'message' => 'Failed to create post']);
//     }
//     $is_static = ($_POST['is_static'] ?? '0') === '1' ? '1' : '0';
//     update_post_meta($post_id, 'qr_is_static', $is_static);

//     update_post_meta($post_id, 'qr_type', $qr_type);
//     update_post_meta($post_id, 'qr_value', $qr_value); // store the real target
//     update_post_meta($post_id, 'qr_user_id', $user_id);
//     update_post_meta($post_id, 'qr_user_name', $user_name);
//     update_post_meta($post_id, 'qr_data', $qr_data);
//     update_post_meta($post_id, 'qr_scan_count', 0);
//     update_post_meta($post_id, 'qr_color', sanitize_hex_color($_POST['qr_color'] ?? '#000000'));
//     update_post_meta($post_id, 'qr_shape', sanitize_text_field($_POST['qr_shape'] ?? 'square'));
//     update_post_meta($post_id, 'qr_logo', sanitize_text_field($_POST['qr_logo'] ?? ''));

//     $scan_url = home_url("/qr/{$post_id}");
//     update_post_meta($post_id, 'qr_scan_url', $scan_url);

//     wp_send_json_success([
//         'post_id' => $post_id,
//         'qr_url'  => $scan_url
//     ]);
// }
add_action('wp_ajax_update_qr_code_post', 'update_qr_code_post');
function update_qr_code_post()
{
    $post_id   = intval($_POST['post_id'] ?? 0);
    $qr_name   = sanitize_text_field($_POST['qr_name'] ?? '');
    $qr_type   = sanitize_text_field($_POST['qr_type'] ?? '');
    $qr_value  = $_POST['qr_value'] ?? '';
    $qr_data   = sanitize_text_field($_POST['qr_data'] ?? '');

    if (!$post_id || !$qr_name) {
        wp_send_json_error('Invalid data.');
    }

    // 🔵 Update title only
    wp_update_post([
        'ID'         => $post_id,
        'post_title' => $qr_name,
    ]);
    // $is_static = ($_POST['is_static'] ?? '0') === '1' ? '1' : '0';
    // update_post_meta($post_id, 'qr_is_static', $is_static);

    // 🔵 Update metadata (do NOT touch scan data)
    update_post_meta($post_id, 'qr_type', $qr_type);
    update_post_meta($post_id, 'qr_value', $qr_value);
    update_post_meta($post_id, 'qr_data', $qr_data);
    update_post_meta($post_id, 'qr_color', sanitize_hex_color($_POST['qr_color'] ?? '#000000'));
    update_post_meta($post_id, 'qr_shape', sanitize_text_field($_POST['qr_shape'] ?? 'square'));
    update_post_meta($post_id, 'qr_logo', sanitize_text_field($_POST['qr_logo'] ?? ''));

    // Existing tracking URL stays unchanged
    $scan_url = get_post_meta($post_id, 'qr_scan_url', true);

    wp_send_json_success([
        'post_id' => $post_id,
        'qr_url'  => $scan_url
    ]);
}


// Handle AJAX request
add_action("wp_ajax_create_social_preview", "create_social_preview_callback");
add_action("wp_ajax_nopriv_create_social_preview", "create_social_preview_callback");

function create_social_preview_callback()
{
    // Decode JSON data
    $raw_data = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
    if (empty($raw_data)) {
        wp_send_json_error(['message' => 'No data provided']);
    }

    $data = json_decode($raw_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $data = json_decode(stripslashes($raw_data), true);
    }
    if (empty($data)) {
        wp_send_json_error(['message' => 'Invalid JSON']);
    }

    // Upload logo
    $logo_url = "";
    if (!empty($_FILES["logo"]["name"])) {
        require_once ABSPATH . "wp-admin/includes/file.php";
        require_once ABSPATH . "wp-admin/includes/media.php";
        require_once ABSPATH . "wp-admin/includes/image.php";
        $logo_id = media_handle_upload("logo", 0);
        if (!is_wp_error($logo_id)) {
            $logo_url = wp_get_attachment_url($logo_id);
        }
    }

    // Upload body image
    $body_image_url = "";
    if (!empty($_FILES["bodyImage"]["name"])) {
        require_once ABSPATH . "wp-admin/includes/file.php";
        require_once ABSPATH . "wp-admin/includes/media.php";
        require_once ABSPATH . "wp-admin/includes/image.php";
        $body_id = media_handle_upload("bodyImage", 0);
        if (!is_wp_error($body_id)) {
            $body_image_url = wp_get_attachment_url($body_id);
        }
    }

    // ⭐ IMPORTANT — Check for existing preview ID
    $preview_id = isset($_POST["preview_id"]) ? intval($_POST["preview_id"]) : 0;

    if ($preview_id > 0) {

        // UPDATE EXISTING POST
        wp_update_post([
            "ID"         => $preview_id,
            "post_title" => sanitize_text_field($data["title"] ?: "Social Preview")
        ]);

        update_post_meta($preview_id, "social_data", maybe_serialize($data));

        // Use existing images if no new file uploaded
        $existing_logo = sanitize_text_field($_POST["existing_logo"] ?? "");
        $existing_body = sanitize_text_field($_POST["existing_bodyImage"] ?? "");

        if ($logo_url) {
            update_post_meta($preview_id, "logo_url", $logo_url);
        } else if ($existing_logo) {
            update_post_meta($preview_id, "logo_url", $existing_logo);
        }

        if ($body_image_url) {
            update_post_meta($preview_id, "body_image_url", $body_image_url);
        } else if ($existing_body) {
            update_post_meta($preview_id, "body_image_url", $existing_body);
        }
        $post_id = $preview_id;
    } else {

        // CREATE NEW POST
        $post_id = wp_insert_post([
            "post_title"  => sanitize_text_field($data["title"] ?: "Social Preview"),
            "post_type"   => "social_preview",
            "post_status" => "publish",
        ]);

        update_post_meta($post_id, "social_data", maybe_serialize($data));

        if ($logo_url) {
            update_post_meta($post_id, "logo_url", $logo_url);
        }
        if ($body_image_url) {
            update_post_meta($post_id, "body_image_url", $body_image_url);
        }
    }

    // Return URL
    $url = add_query_arg("social_preview_id", $post_id, site_url("/social-preview/"));

    wp_send_json_success([
        "url"          => $url,
        "logo_url"     => $logo_url,
        "bodyImageUrl" => $body_image_url
    ]);
}



add_action('wp_ajax_get_qr_scan_count',        'qr_ajax_get_scan_count');
add_action('wp_ajax_nopriv_get_qr_scan_count', 'qr_ajax_get_scan_count');


function qr_ajax_get_scan_count()
{
    $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    wp_cache_delete($post_id, 'post_meta');
    $is_static = get_post_meta($post_id, 'qr_is_static', true);
    if ($is_static !== '1') {
        $count     = (int) get_post_meta($post_id, 'qr_scan_count', true);
        $device    = get_post_meta($post_id, 'last_device', true);
        $time      = get_post_meta($post_id, 'last_scan_time', true);
        $ip        = get_post_meta($post_id, 'last_scan_ip', true);
        $location  = get_post_meta($post_id, 'last_scan_location', true);
        $log       = get_post_meta($post_id, 'qr_scan_log', true);

        wp_send_json_success([
            'qr_scan_count'  => $count,
            'last_device'    => $device,
            'last_scan_time' => $time,
            'last_ip'        => $ip,
            'last_location'  => $location,
            'scan_log'       => $log,
        ]);
    }
}



/**
 * === QR Tracking Redirect (/qr/{post_id}) ===
 * Logs scan count + device + last scan time and redirects to saved destination (qr_value).
 */
// /qr/{id} route
add_action('init', function () {
    add_rewrite_rule('^qr/([0-9]+)/?', 'index.php?qr_redirect_id=$matches[1]', 'top');
});
add_filter('query_vars', function ($vars) {
    $vars[] = 'qr_redirect_id';
    return $vars;
});
add_action('template_redirect', function () {

    $qr_id = get_query_var('qr_redirect_id');
    if (!$qr_id && isset($_GET['qr_id'])) {
        $qr_id = (int) $_GET['qr_id'];
    }
    if (!$qr_id) return;

    $destination = get_post_meta($qr_id, 'qr_value', true);
    if (empty($destination)) wp_die('QR target not found for this ID.');

    /* ---------- BOT FILTER ---------- */
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (preg_match('/bot|spider|crawl|preview/i', $ua)) {
        status_header(200);
        header('X-Robots-Tag: noindex, nofollow', true);
        echo '<!doctype html><meta name="robots" content="noindex,nofollow"><title>OK</title>';
        exit;
    }

    /* ---------- THROTTLE ---------- */
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'qr_hit_' . $qr_id . '_' . md5($ip);

    $throttled = (bool) get_transient($key);
    if (!$throttled) {
        set_transient($key, 1, 6);
    }

    $is_static = get_post_meta($qr_id, 'qr_is_static', true);

    /* ---------- STORE ANALYTICS (DYNAMIC ONLY) ---------- */
    /* ---------- STORE ANALYTICS (DYNAMIC ONLY) ---------- */
    if (!$throttled && $is_static !== '1') {

        wp_cache_delete($qr_id, 'post_meta');

        // Increment scan count
        $count = (int) get_post_meta($qr_id, 'qr_scan_count', true);
        update_post_meta($qr_id, 'qr_scan_count', $count + 1);

        $device = qr_detect_device();
        $time   = current_time('mysql');

        $city = $state = $location = '';
        $latitude = $longitude = null;
        $location_source = 'ipinfo';

        if (filter_var($ip, FILTER_VALIDATE_IP) && $ip !== '127.0.0.1') {

            $ipinfo_token = '7a9785ad0ecc25';
            $res = wp_remote_get(
                "https://ipinfo.io/{$ip}/json?token={$ipinfo_token}",
                ['timeout' => 5]
            );

            if (!is_wp_error($res)) {
                $data = json_decode(wp_remote_retrieve_body($res), true);

                if (is_array($data)) {

                    $city  = $data['city']   ?? '';
                    $state = $data['region'] ?? '';

                    if (!empty($data['loc']) && str_contains($data['loc'], ',')) {
                        [$latitude, $longitude] = array_map('trim', explode(',', $data['loc']));
                    }

                    $location = trim(implode(', ', array_filter([
                        $city,
                        $state,
                        $data['country'] ?? ''
                    ])));
                }
            }
        }

        // Save last scan meta
        update_post_meta($qr_id, 'last_device', $device);
        update_post_meta($qr_id, 'last_scan_time', $time);
        update_post_meta($qr_id, 'last_scan_ip', $ip);
        update_post_meta($qr_id, 'last_scan_city', $city);
        update_post_meta($qr_id, 'last_scan_state', $state);
        update_post_meta($qr_id, 'last_scan_latitude', $latitude);
        update_post_meta($qr_id, 'last_scan_longitude', $longitude);
        update_post_meta($qr_id, 'last_scan_location', $location);
        update_post_meta($qr_id, 'location_source', $location_source);

        // Append scan log
        $log = get_post_meta($qr_id, 'qr_scan_log', true);
        if (!is_array($log)) {
            $log = [];
        }

        $log[] = [
            'device'   => $device,
            'time'     => $time,
            'ip'       => $ip,
            'city'     => $city,
            'state'    => $state,
            'lat'      => $latitude,
            'lon'      => $longitude,
            'location' => $location,
            'source'   => $location_source,
        ];

        update_post_meta($qr_id, 'qr_scan_log', $log);
    }


    /* ---------- REDIRECT ---------- */
    nocache_headers();

    $scheme = strtolower((string) parse_url($destination, PHP_URL_SCHEME));
    if (in_array($scheme, ['http', 'https'])) {
        wp_redirect($destination, 302);
        exit;
    }

    // deep links
    header('Content-Type: text/html; charset=utf-8');
?>
    <!doctype html>
    <meta charset="utf-8">
    <title>Opening…</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                location.href = <?php echo json_encode($destination); ?>;
            }, 50);
        });
    </script>
    <p>Opening your app…</p>
<?php
    exit;
});


// tiny UA helper
function qr_detect_device()
{
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false) return 'iOS';
    if (strpos($ua, 'android') !== false) return 'Android';
    if (strpos($ua, 'windows') !== false) return 'Windows';
    if (strpos($ua, 'macintosh') !== false || strpos($ua, 'mac os') !== false) return 'macOS';
    return 'Unknown';
}


/**
 * === Utilities: Save image bytes to Media Library and return attachment info ===
 */
function qr_save_png_to_media_library($post_id, $png_bytes, $filename = '')
{
    if (!$filename) $filename = "qr-{$post_id}.png";
    $upload = wp_upload_bits($filename, null, $png_bytes);
    if (!empty($upload['error'])) {
        return new WP_Error('upload_failed', $upload['error']);
    }

    $filetype = wp_check_filetype($upload['file'], null);
    $attachment_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'] ?: 'image/png',
        'post_title'     => "QR {$post_id}",
        'post_content'   => '',
        'post_status'    => 'inherit',
    ], $upload['file'], $post_id);

    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return [
        'id'  => $attachment_id,
        'url' => $upload['url'],
        'path' => $upload['file'],
    ];
}

/**
 * === Generate QR PNG bytes for a URL ===
 * Tries local PHP QR Code library if available; otherwise falls back to remote API.
 */
function qr_generate_png_bytes($data_url)
{
    // 1) Try phpqrcode if installed (no fatal if not found)
    //    Place the library at: <your-plugin-dir>/lib/phpqrcode/qrlib.php
    $maybe_lib = __DIR__ . '/lib/phpqrcode/qrlib.php';
    if (file_exists($maybe_lib)) {
        require_once $maybe_lib;
        if (class_exists('QRcode')) {
            // Output to memory buffer
            ob_start();
            // QR_ECLEVEL_H is defined by phpqrcode; if not, use 'H'
            $ec = defined('QR_ECLEVEL_H') ? QR_ECLEVEL_H : 'H';
            QRcode::png($data_url, null, $ec, 10);
            $png = ob_get_clean();
            if ($png) return $png;
        }
    }

    // 2) Fallback: remote PNG generation (no deps)
    // Note: if host blocks remote fopen, enable cURL or allow_url_fopen.
    $remote = 'https://api.qrserver.com/v1/create-qr-code/?size=540x540&data=' . rawurlencode($data_url);
    // Prefer wp_remote_get to avoid allow_url_fopen requirement
    $resp = wp_remote_get($remote, ['timeout' => 20]);
    if (is_wp_error($resp)) return $resp;

    $code = wp_remote_retrieve_response_code($resp);
    $body = wp_remote_retrieve_body($resp);
    if ($code !== 200 || empty($body)) {
        return new WP_Error('remote_qr_failed', 'Failed to generate QR using remote service.');
    }
    return $body;
}

/**
 * === Core: Regenerate QR for a post and update its image ===
 */
function qr_regenerate_for_post($post_id)
{
    $post_id = (int) $post_id;
    if (!$post_id) return new WP_Error('bad_id', 'Invalid post ID.');

    // Build permanent tracking URL
    $tracking = home_url("/qr/{$post_id}");

    // Generate PNG bytes
    $png = qr_generate_png_bytes($tracking);
    if (is_wp_error($png)) return $png;

    // Save to media library and link it
    $saved = qr_save_png_to_media_library($post_id, $png);
    if (is_wp_error($saved)) return $saved;

    // Update post meta your UI expects (cover both common keys)
    update_post_meta($post_id, 'qr_tracking_url', $tracking);
    update_post_meta($post_id, 'qr_image',       $saved['url']);    // if your UI reads a URL
    update_post_meta($post_id, 'qr_image_id',    $saved['id']);     // if your UI reads attachment ID

    // Optional: set as featured image if you use it
    // set_post_thumbnail($post_id, $saved['id']);

    return $saved;
}

add_action('before_delete_post', function ($post_id) {
    $post_type = get_post_type($post_id);

    if ($post_type === 'qr_code' || $post_type === 'social_preview') {
        // Delete linked QR image
        $qr_image_id = get_post_meta($post_id, 'qr_image_id', true);
        if ($qr_image_id) {
            wp_delete_attachment($qr_image_id, true); // also deletes file
        }

        // Delete all child attachments
        $attachments = get_children([
            'post_parent' => $post_id,
            'post_type'   => 'attachment',
            'numberposts' => -1,
        ]);
        foreach ($attachments as $att) {
            wp_delete_attachment($att->ID, true);
        }
    }
});
add_action("wp_ajax_load_social_preview_data", "load_social_preview_data_callback");
add_action("wp_ajax_nopriv_load_social_preview_data", "load_social_preview_data_callback");

function load_social_preview_data_callback()
{

    $id = intval($_POST['id']);

    $data = maybe_unserialize(get_post_meta($id, "social_data", true));
    $logo  = get_post_meta($id, "logo_url", true);
    $body  = get_post_meta($id, "body_image_url", true);

    wp_send_json_success([
        "title"      => $data['title'] ?? "",
        "message"    => $data['message'] ?? "",
        "subtext"    => $data['subtext'] ?? "",
        "titleColor" => $data['titleColor'] ?? "#000000",
        "bgColor"    => $data['bgColor'] ?? "#ffffff",
        "logo"       => $logo ?: "",
        "bodyImage"  => $body ?: "",
        "links"      => $data['links'] ?? []
    ]);
}
