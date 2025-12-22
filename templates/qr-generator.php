<section class="qr-code-generator" id="qr-generator" data-type="<?php echo esc_attr($type); ?>">
    <div class="qr-container">
        <div class="qr-tabs-section">
            <div class="qr-tabs-container">
                <ul class="qr-tabs-nav">
                    <li class="qr-tab active" data-tab="tab1">
                        <span class="qr-tab-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 15l6 -6" />
                                <path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464" />
                                <path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463" />
                            </svg>
                        </span>
                        <span>URL</span>
                    </li>
                    <li class="qr-tab" data-tab="tab2">
                        <span class="qr-tab-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 3v4a1 1 0 0 0 1 1h4" />
                                <path d="M5 12v-7a2 2 0 0 1 2 -2h7l5 5v4" />
                                <path d="M5 18h1.5a1.5 1.5 0 0 0 0 -3h-1.5v6" />
                                <path d="M17 18h2" />
                                <path d="M20 15h-3v6" />
                                <path d="M11 15v6h1a2 2 0 0 0 2 -2v-2a2 2 0 0 0 -2 -2h-1z" />
                            </svg>
                        </span>
                        <span>PDF</span>
                    </li>
                    <li class="qr-tab" data-tab="tab3">
                        <span class="qr-tab-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                <path d="M3 7l9 6l9 -6" />
                            </svg>
                        </span>
                        <span>Email</span>
                    </li>

                    <li class="qr-tab" data-tab="tab4">
                        <span class="qr-tab-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 9h8" />
                                <path d="M8 13h6" />
                                <path d="M18 4a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-5l-5 3v-3h-2a3 3 0 0 1 -3 -3v-8a3 3 0 0 1 3 -3h12z" />
                            </svg>

                        </span>
                        <span>SMS</span>
                    </li>

                    <li class="qr-tab" data-tab="tab5">
                        <span class="qr-tab-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 9l14 0" />
                                <path d="M5 15l14 0" />
                                <path d="M11 4l-4 16" />
                                <path d="M17 4l-4 16" />
                            </svg>
                        </span>
                        <span>Social Media</span>
                    </li>
                </ul>

                <div class="qr-tabs-content">
                    <div class="qr-tab-pane active" id="tab1">
                        <div class="qr-tab-pane-inner">
                            <div class="qr-tab-content-col">
                                <div class="qr-link-part qr-white-bg">
                                    <div class="qr-link-part-inner">
                                        <input type="url" placeholder="Add URL" id="qr_url_wrap" class="link-input">
                                        <button type="button" class="static-qr-wrap btn-qr-primary">Generate QR</button>
                                    </div>
                                </div>

                                <div class="qr-logo-part qr-white-bg">
                                    <div class="qr-inner-tab_wrapper">
                                        <ul class="qr-inner-tab-nav">
                                            <li class="qr-inner-tab active-inner-tab" data-tab="qr-inner-tab1">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M13 15c-2 0 -5 1 -5 5" />
                                                        <path d="M4 11m0 2a2 2 0 0 1 2 -2h5a2 2 0 0 1 2 2v5a2 2 0 0 1 -2 2h-5a2 2 0 0 1 -2 -2z" />
                                                        <path d="M4 7v-2a1 1 0 0 1 1 -1h2" />
                                                        <path d="M11 4h2" />
                                                        <path d="M17 4h2a1 1 0 0 1 1 1v2" />
                                                        <path d="M20 11v2" />
                                                        <path d="M20 17v2a1 1 0 0 1 -1 1h-2" />
                                                    </svg>
                                                </span>
                                                <span>Logos</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab2">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 4h6v6h-6z" />
                                                        <path d="M4 14h6v6h-6z" />
                                                        <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    </svg>
                                                </span>
                                                <span>Shapes</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab3">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 17c0 -5.523 -4.477 -10 -10 -10s-10 4.477 -10 10" />
                                                        <path d="M18 17a6 6 0 1 0 -12 0" />
                                                        <path d="M14 17a2 2 0 1 0 -4 0" />
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
                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png"
                                                            alt="Instagram">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png"
                                                            alt="Youtube">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png"
                                                            alt="Twitter">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png"
                                                            alt="Facebook">
                                                    </button>
                                                </div>

                                                <div class="qr-inner-title">
                                                    <h4>OR</h4>
                                                </div>

                                                <div class="qr-upload-col">
                                                    <div class="upload-button_wrapper upload_logo_wrapper">
                                                        <button class="btn-upload">Upload Logo</button>
                                                        <span class="file-name"></span>
                                                        <input type="file" name="myfile" id="qr-logo" />
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
                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-3.png"
                                                            alt="custom-qr-3">
                                                        <span>Squre</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-4.png"
                                                            alt="custom-qr-4">
                                                        <span>Dots</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-5.png"
                                                            alt="custom-qr-5">
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

                                                        <input type="text" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor"></input>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <div class="qr-tab-download-col">
                                <div class="qr-code-image_wrapper qr-white-bg">
                                    <div class="qr-code-image-inner" id="qr-preview">
                                        <img
                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/scan-me-qr-code.png"
                                            alt="scan-me-qr-code">
                                    </div>

                                </div>

                                <div class="qr-formate_wrapper">
                                    <div class="qr-formate-inner">
                                        <div class="qr-inner-title">
                                            <h4>Download Format</h4>
                                        </div>

                                        <div class="qr-formate-select_wrapper">
                                            <div class="qr-formate-select-inner">
                                                <ul>
                                                    <li>
                                                        <a href="javascript:;">.PNG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.SVG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.PDF</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.EPS</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="qr-download_wrapper">
                                            <button type="button" class="btn-qr-primary">Download Now</button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="qr-tab-pane" id="tab2">
                        <div class="qr-tab-pane-inner">
                            <div class="qr-tab-content-col">
                                <div class="qr-link-part qr-white-bg">
                                    <div class="qr-link-part-inner">
                                        <input type="url" placeholder="Add URL" id="qr_url_wrap" class="link-input">
                                        <button type="button" class="static-qr-wrap btn-qr-primary">Generate QR</button>
                                    </div>

                                    <div class="qr-inner-title qr-m-16">
                                        <h4>OR</h4>
                                    </div>

                                    <div class="qr-upload-pdf_wrapper">
                                        <div class="qr-upload-pdf-inner">
                                            <div class="qr-upload-col">
                                                <div class="upload-button_wrapper upload_pdf_wrapper">
                                                    <button class="btn-upload">Upload PDF</button>
                                                    <span class="file-name"></span> <!-- 👈 add this -->

                                                    <input type="file" name="myfile" accept="application/pdf" hidden>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="qr-logo-part qr-white-bg">
                                    <div class="qr-inner-tab_wrapper">
                                        <ul class="qr-inner-tab-nav">
                                            <li class="qr-inner-tab active-inner-tab" data-tab="qr-inner-tab4">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M13 15c-2 0 -5 1 -5 5" />
                                                        <path d="M4 11m0 2a2 2 0 0 1 2 -2h5a2 2 0 0 1 2 2v5a2 2 0 0 1 -2 2h-5a2 2 0 0 1 -2 -2z" />
                                                        <path d="M4 7v-2a1 1 0 0 1 1 -1h2" />
                                                        <path d="M11 4h2" />
                                                        <path d="M17 4h2a1 1 0 0 1 1 1v2" />
                                                        <path d="M20 11v2" />
                                                        <path d="M20 17v2a1 1 0 0 1 -1 1h-2" />
                                                    </svg>
                                                </span>
                                                <span>Logos</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab5">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 4h6v6h-6z" />
                                                        <path d="M4 14h6v6h-6z" />
                                                        <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    </svg>
                                                </span>
                                                <span>Shapes</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab6">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 17c0 -5.523 -4.477 -10 -10 -10s-10 4.477 -10 10" />
                                                        <path d="M18 17a6 6 0 1 0 -12 0" />
                                                        <path d="M14 17a2 2 0 1 0 -4 0" />
                                                    </svg>

                                                </span>
                                                <span>Colors</span>
                                            </li>

                                        </ul>

                                        <div class="qr-inner-tab-content">
                                            <div class="qr-inner-tab-pane active-inner-tab" id="qr-inner-tab4">
                                                <div class="qr-inner-title">
                                                    <h4>Select Logo</h4>
                                                </div>
                                                <div class="qr-tab-logos">
                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png"
                                                            alt="Instagram">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png"
                                                            alt="Youtube">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png"
                                                            alt="Twitter">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png"
                                                            alt="Facebook">
                                                    </button>
                                                </div>

                                                <div class="qr-inner-title">
                                                    <h4>OR</h4>
                                                </div>

                                                <div class="qr-upload-col">
                                                    <div class="upload-button_wrapper upload_logo_wrapper">
                                                        <button class="btn-upload">Upload Logo</button>
                                                        <span class="file-name"></span>
                                                        <input type="file" name="myfile" id="qr-logo" />
                                                    </div>
                                                    <div class="reset-button">
                                                        <button class="btn-upload">Reset to Default</button>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab5">
                                                <div class="qr-inner-title">
                                                    <h4>Select Shapes</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-shapes">
                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-3.png"
                                                            alt="custom-qr-3">
                                                        <span>Squre</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-4.png"
                                                            alt="custom-qr-4">
                                                        <span>Dots</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-5.png"
                                                            alt="custom-qr-5">
                                                        <span>Rounded</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab6">
                                                <div class="qr-inner-title">
                                                    <h4>Select Color</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-color">

                                                    <div class="qr-color-picker">
                                                        <input type="color" id="colorpicker" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">

                                                        <input type="text" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor"></input>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <div class="qr-tab-download-col">
                                <div class="qr-code-image_wrapper qr-white-bg">
                                    <div class="qr-code-image-inner" id="qr-preview">
                                        <img
                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/scan-me-qr-code.png"
                                            alt="scan-me-qr-code">
                                    </div>

                                </div>

                                <div class="qr-formate_wrapper">
                                    <div class="qr-formate-inner">
                                        <div class="qr-inner-title">
                                            <h4>Download Format</h4>
                                        </div>

                                        <div class="qr-formate-select_wrapper">
                                            <div class="qr-formate-select-inner">
                                                <ul>
                                                    <li>
                                                        <a href="javascript:;">.PNG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.SVG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.PDF</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.EPS</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="qr-download_wrapper">
                                            <button type="button" class="btn-qr-primary">Download Now</button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="qr-tab-pane" id="tab3">
                        <div class="qr-tab-pane-inner">
                            <div class="qr-tab-content-col">
                                <div class="qr-link-part qr-white-bg">
                                    <div class="qr-form_wrapper">
                                        <div class="qr-form-inner">
                                            <form>
                                                <div class="qr-form-inpt">
                                                    <label>Reicipent's Email</label>
                                                    <input type="email" placeholder="Enter email">
                                                </div>

                                                <div class="qr-form-inpt">
                                                    <label>Subject</label>
                                                    <input type="text" placeholder="Enter subtext">
                                                </div>

                                                <div class="qr-form-inpt">
                                                    <label>Body</label>
                                                    <input type="text" placeholder="Enter text">
                                                </div>
                                            </form>

                                            <div class="qr-submit-button">
                                                <button type="button" class="static-qr-wrap btn-qr-primary">Generate QR</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="qr-logo-part qr-white-bg">
                                    <div class="qr-inner-tab_wrapper">
                                        <ul class="qr-inner-tab-nav">
                                            <li class="qr-inner-tab active-inner-tab" data-tab="qr-inner-tab7">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M13 15c-2 0 -5 1 -5 5" />
                                                        <path d="M4 11m0 2a2 2 0 0 1 2 -2h5a2 2 0 0 1 2 2v5a2 2 0 0 1 -2 2h-5a2 2 0 0 1 -2 -2z" />
                                                        <path d="M4 7v-2a1 1 0 0 1 1 -1h2" />
                                                        <path d="M11 4h2" />
                                                        <path d="M17 4h2a1 1 0 0 1 1 1v2" />
                                                        <path d="M20 11v2" />
                                                        <path d="M20 17v2a1 1 0 0 1 -1 1h-2" />
                                                    </svg>
                                                </span>
                                                <span>Logos</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab8">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 4h6v6h-6z" />
                                                        <path d="M4 14h6v6h-6z" />
                                                        <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    </svg>
                                                </span>
                                                <span>Shapes</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab9">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 17c0 -5.523 -4.477 -10 -10 -10s-10 4.477 -10 10" />
                                                        <path d="M18 17a6 6 0 1 0 -12 0" />
                                                        <path d="M14 17a2 2 0 1 0 -4 0" />
                                                    </svg>

                                                </span>
                                                <span>Colors</span>
                                            </li>

                                        </ul>

                                        <div class="qr-inner-tab-content">
                                            <div class="qr-inner-tab-pane active-inner-tab" id="qr-inner-tab7">
                                                <div class="qr-inner-title">
                                                    <h4>Select Logo</h4>
                                                </div>
                                                <div class="qr-tab-logos">
                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png"
                                                            alt="Instagram">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png"
                                                            alt="Youtube">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png"
                                                            alt="Twitter">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png"
                                                            alt="Facebook">
                                                    </button>
                                                </div>

                                                <div class="qr-inner-title">
                                                    <h4>OR</h4>
                                                </div>

                                                <div class="qr-upload-col">
                                                    <div class="upload-button_wrapper upload_logo_wrapper">
                                                        <button class="btn-upload">Upload Logo</button>
                                                        <span class="file-name"></span>
                                                        <input type="file" name="myfile" id="qr-logo" />
                                                    </div>

                                                    <div class="reset-button">
                                                        <button class="btn-upload">Reset to Default</button>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab8">
                                                <div class="qr-inner-title">
                                                    <h4>Select Shapes</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-shapes">
                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-3.png"
                                                            alt="custom-qr-3">
                                                        <span>Squre</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-4.png"
                                                            alt="custom-qr-4">
                                                        <span>Dots</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-5.png"
                                                            alt="custom-qr-5">
                                                        <span>Rounded</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab9">
                                                <div class="qr-inner-title">
                                                    <h4>Select Color</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-color">

                                                    <div class="qr-color-picker">
                                                        <input type="color" id="colorpicker" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">

                                                        <input type="text" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor"></input>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <div class="qr-tab-download-col">
                                <div class="qr-code-image_wrapper qr-white-bg">
                                    <div class="qr-code-image-inner" id="qr-preview">
                                        <img
                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/scan-me-qr-code.png"
                                            alt="scan-me-qr-code">
                                    </div>

                                </div>

                                <div class="qr-formate_wrapper">
                                    <div class="qr-formate-inner">
                                        <div class="qr-inner-title">
                                            <h4>Download Format</h4>
                                        </div>

                                        <div class="qr-formate-select_wrapper">
                                            <div class="qr-formate-select-inner">
                                                <ul>
                                                    <li>
                                                        <a href="javascript:;">.PNG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.SVG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.PDF</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.EPS</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="qr-download_wrapper">
                                            <button type="button" class="btn-qr-primary">Download Now</button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="qr-tab-pane" id="tab4">
                        <div class="qr-tab-pane-inner">
                            <div class="qr-tab-content-col">
                                <div class="qr-link-part qr-white-bg">
                                    <div class="qr-form_wrapper">
                                        <div class="qr-form-inner">
                                            <form>
                                                <div class="qr-form-inpt">
                                                    <label>Recipient's Phone Number</label>
                                                    <input type="email" placeholder="Enter recipient's Phone Number">
                                                </div>

                                                <div class="qr-form-inpt">
                                                    <label>Message</label>
                                                    <input type="text" placeholder="Enter message">
                                                </div>
                                            </form>

                                            <div class="qr-submit-button">
                                                <button type="button" class="static-qr-wrap btn-qr-primary">Generate QR</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="qr-logo-part qr-white-bg">
                                    <div class="qr-inner-tab_wrapper">
                                        <ul class="qr-inner-tab-nav">
                                            <li class="qr-inner-tab active-inner-tab" data-tab="qr-inner-tab10">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M13 15c-2 0 -5 1 -5 5" />
                                                        <path d="M4 11m0 2a2 2 0 0 1 2 -2h5a2 2 0 0 1 2 2v5a2 2 0 0 1 -2 2h-5a2 2 0 0 1 -2 -2z" />
                                                        <path d="M4 7v-2a1 1 0 0 1 1 -1h2" />
                                                        <path d="M11 4h2" />
                                                        <path d="M17 4h2a1 1 0 0 1 1 1v2" />
                                                        <path d="M20 11v2" />
                                                        <path d="M20 17v2a1 1 0 0 1 -1 1h-2" />
                                                    </svg>
                                                </span>
                                                <span>Logos</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab11">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 4h6v6h-6z" />
                                                        <path d="M4 14h6v6h-6z" />
                                                        <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    </svg>
                                                </span>
                                                <span>Shapes</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab12">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 17c0 -5.523 -4.477 -10 -10 -10s-10 4.477 -10 10" />
                                                        <path d="M18 17a6 6 0 1 0 -12 0" />
                                                        <path d="M14 17a2 2 0 1 0 -4 0" />
                                                    </svg>

                                                </span>
                                                <span>Colors</span>
                                            </li>

                                        </ul>

                                        <div class="qr-inner-tab-content">
                                            <div class="qr-inner-tab-pane active-inner-tab" id="qr-inner-tab10">
                                                <div class="qr-inner-title">
                                                    <h4>Select Logo</h4>
                                                </div>
                                                <div class="qr-tab-logos">
                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png"
                                                            alt="Instagram">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png"
                                                            alt="Youtube">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png"
                                                            alt="Twitter">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png"
                                                            alt="Facebook">
                                                    </button>
                                                </div>

                                                <div class="qr-inner-title">
                                                    <h4>OR</h4>
                                                </div>

                                                <div class="qr-upload-col">
                                                    <div class="upload-button_wrapper upload_logo_wrapper">
                                                        <button class="btn-upload">Upload Logo</button>
                                                        <span class="file-name"></span>
                                                        <input type="file" name="myfile" id="qr-logo" />
                                                    </div>


                                                    <div class="reset-button">
                                                        <button class="btn-upload">Reset to Default</button>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab11">
                                                <div class="qr-inner-title">
                                                    <h4>Select Shapes</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-shapes">
                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-3.png"
                                                            alt="custom-qr-3">
                                                        <span>Squre</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-4.png"
                                                            alt="custom-qr-4">
                                                        <span>Dots</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-5.png"
                                                            alt="custom-qr-5">
                                                        <span>Rounded</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab12">
                                                <div class="qr-inner-title">
                                                    <h4>Select Color</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-color">

                                                    <div class="qr-color-picker">
                                                        <input type="color" id="colorpicker" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">

                                                        <input type="text" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor"></input>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <div class="qr-tab-download-col">
                                <div class="qr-code-image_wrapper qr-white-bg">
                                    <div class="qr-code-image-inner" id="qr-preview">
                                        <img
                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/scan-me-qr-code.png"
                                            alt="scan-me-qr-code">
                                    </div>

                                </div>

                                <div class="qr-formate_wrapper">
                                    <div class="qr-formate-inner">
                                        <div class="qr-inner-title">
                                            <h4>Download Format</h4>
                                        </div>

                                        <div class="qr-formate-select_wrapper">
                                            <div class="qr-formate-select-inner">
                                                <ul>
                                                    <li>
                                                        <a href="javascript:;">.PNG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.SVG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.PDF</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.EPS</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="qr-download_wrapper">
                                            <button type="button" class="btn-qr-primary">Download Now</button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                    <div class="qr-tab-pane" id="tab5">
                        <div class="qr-tab-pane-inner">
                            <div class="qr-tab-content-col">
                                <div class="qr-link-part qr-white-bg">
                                    <div class="qr-social_wrapper">
                                        <div class="qr-social-inner cust_social_wrap">
                                            <ul>
                                                <li class="qr-active-social" data-tab="qr-socail-1">
                                                    <button class="qr-social-img">
                                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png" alt="Instagram">
                                                    </button>
                                                </li>

                                                <li data-tab="qr-socail-2">
                                                    <button class="qr-social-img">
                                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png" alt="Youtube">
                                                    </button>
                                                </li>

                                                <li data-tab="qr-socail-3">
                                                    <button class="qr-social-img">
                                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png" alt="Twitter">
                                                    </button>
                                                </li>

                                                <li data-tab="qr-socail-4">
                                                    <button class="qr-social-img">
                                                        <img src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png" alt="Facebook">
                                                    </button>
                                                </li>
                                            </ul>

                                            <div class="qr-social-content_wrapper">
                                                <div class="qr-social-inner cust_social_wrap">
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
                                    <div class="qr-submit-button">
                                        <button type="button" class="static-qr-wrap btn-qr-primary">Generate QR</button>
                                    </div>
                                </div>

                                <div class="qr-logo-part qr-white-bg">
                                    <div class="qr-inner-tab_wrapper">
                                        <ul class="qr-inner-tab-nav">
                                            <li class="qr-inner-tab active-inner-tab" data-tab="qr-inner-tab13">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M13 15c-2 0 -5 1 -5 5" />
                                                        <path d="M4 11m0 2a2 2 0 0 1 2 -2h5a2 2 0 0 1 2 2v5a2 2 0 0 1 -2 2h-5a2 2 0 0 1 -2 -2z" />
                                                        <path d="M4 7v-2a1 1 0 0 1 1 -1h2" />
                                                        <path d="M11 4h2" />
                                                        <path d="M17 4h2a1 1 0 0 1 1 1v2" />
                                                        <path d="M20 11v2" />
                                                        <path d="M20 17v2a1 1 0 0 1 -1 1h-2" />
                                                    </svg>
                                                </span>
                                                <span>Logos</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab14">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M14 4h6v6h-6z" />
                                                        <path d="M4 14h6v6h-6z" />
                                                        <path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                        <path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" />
                                                    </svg>
                                                </span>
                                                <span>Shapes</span>
                                            </li>

                                            <li class="qr-inner-tab" data-tab="qr-inner-tab15">
                                                <span class="qr-tab-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
                                                        stroke="#000000" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M22 17c0 -5.523 -4.477 -10 -10 -10s-10 4.477 -10 10" />
                                                        <path d="M18 17a6 6 0 1 0 -12 0" />
                                                        <path d="M14 17a2 2 0 1 0 -4 0" />
                                                    </svg>

                                                </span>
                                                <span>Colors</span>
                                            </li>

                                        </ul>

                                        <div class="qr-inner-tab-content">
                                            <div class="qr-inner-tab-pane active-inner-tab" id="qr-inner-tab13">
                                                <div class="qr-inner-title">
                                                    <h4>Select Logo</h4>
                                                </div>
                                                <div class="qr-tab-logos">
                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Instagram.png"
                                                            alt="Instagram">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Youtube.png"
                                                            alt="Youtube">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Twitter.png"
                                                            alt="Twitter">
                                                    </button>

                                                    <button class="btn-logos social-logo">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/Facebook.png"
                                                            alt="Facebook">
                                                    </button>
                                                </div>

                                                <div class="qr-inner-title">
                                                    <h4>OR</h4>
                                                </div>

                                                <div class="qr-upload-col">
                                                    <div class="upload-button_wrapper upload_logo_wrapper">
                                                        <button class="btn-upload">Upload Logo</button>
                                                        <span class="file-name"></span>
                                                        <input type="file" name="myfile" id="qr-logo" />
                                                    </div>

                                                    <div class="reset-button">
                                                        <button class="btn-upload">Reset to Default</button>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab14">
                                                <div class="qr-inner-title">
                                                    <h4>Select Shapes</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-shapes">
                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-3.png"
                                                            alt="custom-qr-3">
                                                        <span>Square</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-4.png"
                                                            alt="custom-qr-4">
                                                        <span>Dots</span>
                                                    </button>

                                                    <button class="btn-logos btn-shapes">
                                                        <img
                                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/custom-qr-5.png"
                                                            alt="custom-qr-5">
                                                        <span>Rounded</span>
                                                    </button>
                                                </div>

                                            </div>

                                            <div class="qr-inner-tab-pane" id="qr-inner-tab15">
                                                <div class="qr-inner-title">
                                                    <h4>Select Color</h4>
                                                </div>
                                                <div class="qr-tab-logos qr-tab-color">

                                                    <div class="qr-color-picker">
                                                        <input type="color" id="colorpicker" name="color" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000">

                                                        <input type="text" pattern="^#+([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$" value="#000000" id="hexcolor"></input>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                            <div class="qr-tab-download-col">
                                <div class="qr-code-image_wrapper qr-white-bg">
                                    <div class="qr-code-image-inner" id="qr-preview">
                                        <img
                                            src="<?php echo site_url(); ?>/wp-content/uploads/2025/08/scan-me-qr-code.png"
                                            alt="scan-me-qr-code">
                                    </div>

                                </div>

                                <div class="qr-formate_wrapper">
                                    <div class="qr-formate-inner">
                                        <div class="qr-inner-title">
                                            <h4>Download Format</h4>
                                        </div>

                                        <div class="qr-formate-select_wrapper">
                                            <div class="qr-formate-select-inner">
                                                <ul>
                                                    <li>
                                                        <a href="javascript:;">.PNG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.SVG</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.PDF</a>
                                                    </li>

                                                    <li>
                                                        <a href="javascript:;">.EPS</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="qr-download_wrapper">
                                            <button type="button" class="btn-qr-primary">Download Now</button>
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
</section>
<div class="qr-loader" style="display:none;">
    <div class="spinner"></div>
</div>
<style>
    #qr-logo {
        display: none;
    }

    .qr-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .spinner {
        border: 8px solid #f3f3f3;
        border-top: 8px solid #3498db;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const {
            jsPDF
        } = window.jspdf;

        // --- Helper: Upload PDF to server ---
        async function uploadPdfAndGetUrl(file) {
            const formData = new FormData();
            formData.append("action", "upload_pdf_and_return_url");
            formData.append("pdf_file", file);

            const response = await fetch(qrAjax.ajax_url, {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.success) return result.data.url;
            throw new Error(result.data?.message || "PDF upload failed");
        }

        function isValidURL(str) {
            try {
                const url = new URL(str);
                return url.protocol === "http:" || url.protocol === "https:";
            } catch (_) {
                return false;
            }
        }

        function isValidEmail(str) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
        }

        function isValidPhone(str) {
            return /^[0-9+\-\s]{7,15}$/.test(str); // basic validation
        }
        // --- Init QR for each tab ---
        function initQRCode(tabSelector, previewSelector, type) {
            const tab = document.querySelector(tabSelector);
            if (!tab) return;

            let currentQRData = ""; // ✅ persist QR data
            let selectedLogo = "";
            let lastLogo = "";
            let selectedShape = "square";
            let selectedFormat = "png";

            const qrCode = new QRCodeStyling({
                width: 300,
                height: 300,
                margin: 10,
                type: "canvas",
                image: "",
                dotsOptions: {
                    color: "#000000",
                    type: "square"
                },
                backgroundOptions: {
                    color: "#ffffff"
                },
                imageOptions: {
                    crossOrigin: "anonymous",
                    margin: 5,
                    imageSize: 0.1
                }
            });

            const previewTarget = tab.querySelector(previewSelector);
            if (previewTarget) qrCode.append(previewTarget);

            // --- Elements ---
            const generateBtn = tab.querySelector(".static-qr-wrap");
            const logoBtns = tab.querySelectorAll(".social-logo");
            const uploadLogoInp = tab.querySelector("#qr-logo");
            const logoBtn = tab.querySelector(".upload_logo_wrapper .btn-upload");
            const logoFileName = tab.querySelector(".upload_logo_wrapper .file-name");

            const uploadPdfInp = tab.querySelector(".qr-upload-pdf_wrapper input[type='file']");
            const pdfBtn = tab.querySelector(".qr-upload-pdf_wrapper .btn-upload");
            const pdfFileName = tab.querySelector(".qr-upload-pdf_wrapper .file-name");

            const resetLogoBtn = tab.querySelector(".reset-button");
            const shapeBtns = tab.querySelectorAll(".btn-shapes");
            const colorInput = tab.querySelector("#colorpicker");
            const hexInput = tab.querySelector("#hexcolor");
            const downloadBtn = tab.querySelector(".qr-download_wrapper .btn-qr-primary");
            const formatLinks = document.querySelectorAll(".qr-formate-select-inner ul li a");

            // --- Logo buttons ---
            if (logoBtns?.length) {
                logoBtns.forEach(btn => {
                    btn.addEventListener("click", () => {
                        const img = btn.querySelector("img");
                        if (!img) return;
                        selectedLogo = img.src;
                        lastLogo = selectedLogo;
                        if (logoFileName) logoFileName.textContent = "";
                        if (logoBtn) logoBtn.textContent = "Upload Logo";

                        qrCode.update({
                            image: selectedLogo,
                            imageOptions: {
                                crossOrigin: "anonymous",
                                margin: 5,
                                imageSize: 0.1
                            }
                        });
                    });
                });
            }

            // --- Upload Logo ---
            if (uploadLogoInp && logoBtn) {
                logoBtn.addEventListener("click", e => {
                    e.preventDefault();
                    if (logoBtn.classList.contains("has-file")) {
                        uploadLogoInp.value = "";
                        if (logoFileName) logoFileName.textContent = "";
                        logoBtn.textContent = "Upload Logo";
                        logoBtn.classList.remove("has-file");
                        selectedLogo = "";
                        lastLogo = "";
                        qrCode.update({
                            image: ""
                        });
                    } else {
                        uploadLogoInp.click();
                    }
                });

                uploadLogoInp.addEventListener("change", e => {
                    const file = e.target.files[0];
                    if (!file) return;
                    if (logoFileName) logoFileName.textContent = file.name;
                    logoBtn.textContent = "Remove Logo";
                    logoBtn.classList.add("has-file");

                    if (file.type.startsWith("image/")) {
                        selectedLogo = URL.createObjectURL(file);
                        lastLogo = selectedLogo;
                        qrCode.update({
                            image: selectedLogo,
                            imageOptions: {
                                crossOrigin: "anonymous",
                                margin: 5,
                                imageSize: 0.1
                            }
                        });
                    }
                });
            }

            // --- Upload PDF ---
            if (uploadPdfInp && pdfBtn) {
                pdfBtn.addEventListener("click", e => {
                    e.preventDefault();
                    if (pdfBtn.classList.contains("has-file")) {
                        uploadPdfInp.value = "";
                        if (pdfFileName) pdfFileName.textContent = "";
                        pdfBtn.textContent = "Upload PDF";
                        pdfBtn.classList.remove("has-file");
                        qrCode.update({
                            data: ""
                        });
                    } else {
                        uploadPdfInp.click();
                    }
                });

                uploadPdfInp.addEventListener("change", e => {
                    const file = e.target.files[0];
                    if (!file) return;
                    if (pdfFileName) pdfFileName.textContent = file.name;
                    pdfBtn.textContent = "Remove PDF";
                    pdfBtn.classList.add("has-file");
                });
            }

            // --- Reset logo ---
            if (resetLogoBtn) {
                resetLogoBtn.addEventListener("click", () => {
                    selectedLogo = "";
                    lastLogo = "";
                    if (uploadLogoInp) uploadLogoInp.value = "";
                    if (logoFileName) logoFileName.textContent = "";
                    if (logoBtn) logoBtn.textContent = "Upload Logo";
                    qrCode.update({
                        image: ""
                    });
                });
            }

            // --- Shape handling ---
            if (shapeBtns?.length) {
                shapeBtns.forEach(btn => {
                    btn.addEventListener("click", () => {
                        shapeBtns.forEach(b => b.classList.remove("active"));
                        btn.classList.add("active");

                        const shapeName = btn.querySelector("span")?.innerText.toLowerCase();
                        selectedShape = (shapeName === "dots") ? "dots" : (shapeName === "rounded") ? "rounded" : "square";

                        if (currentQRData) {
                            qrCode.update({
                                data: currentQRData, // ✅ always use saved QR data
                                dotsOptions: {
                                    color: colorInput?.value || "#000000",
                                    type: selectedShape
                                },
                                cornerSquareOptions: {
                                    type: selectedShape,
                                    color: colorInput?.value || "#000000"
                                },
                                cornerDotOptions: {
                                    type: "dot",
                                    color: colorInput?.value || "#000000"
                                },
                                image: selectedLogo || lastLogo
                            });
                        }
                    });
                });
            }

            // --- Color handling ---
            if (colorInput && hexInput) {
                const updateColor = () => {
                    hexInput.value = colorInput.value;
                    if (!currentQRData) return;
                    qrCode.update({
                        data: currentQRData,
                        dotsOptions: {
                            color: colorInput.value,
                            type: selectedShape
                        },
                        cornerSquareOptions: {
                            type: selectedShape,
                            color: colorInput.value
                        },
                        cornerDotOptions: {
                            type: "dot",
                            color: colorInput.value
                        },
                        image: selectedLogo || lastLogo
                    });
                };
                const updateHex = () => {
                    colorInput.value = hexInput.value;
                    if (!currentQRData) return;
                    qrCode.update({
                        data: currentQRData,
                        dotsOptions: {
                            color: hexInput.value,
                            type: selectedShape
                        },
                        cornerSquareOptions: {
                            type: selectedShape,
                            color: hexInput.value
                        },
                        cornerDotOptions: {
                            type: "dot",
                            color: hexInput.value
                        },
                        image: selectedLogo || lastLogo
                    });
                };
                colorInput.addEventListener("input", updateColor);
                hexInput.addEventListener("input", updateHex);
            }

            // --- Build QR Data ---
            function buildQRData() {
                // returns { ok: boolean, data: string|File|null, message: string }
                if (type === "url") {
                    const url = tab.querySelector("#qr_url_wrap")?.value.trim();
                    if (!url) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please fill required fields!"
                        };
                    }
                    if (!isValidURL(url)) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please enter a valid URL with http:// or https://"
                        };
                    }
                    return {
                        ok: true,
                        data: url,
                        message: ""
                    };
                } else if (type === "pdf") {
                    const url = tab.querySelector("#qr_url_wrap")?.value.trim();
                    const file = uploadPdfInp?.files[0] || null;
                    if (url && file) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please provide either a PDF URL OR upload a file, not both."
                        };
                    }
                    if (!url && !file) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please fill required fields!"
                        };
                    }
                    if (url && !isValidURL(url)) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please enter a valid PDF URL (http/https)."
                        };
                    }
                    return {
                        ok: true,
                        data: url || file,
                        message: ""
                    };
                } else if (type === "email") {
                    const email = tab.querySelector('input[type="email"]')?.value.trim() || "";
                    const subject = tab.querySelector('input[placeholder="Enter subtext"]')?.value.trim() || "";
                    const body = tab.querySelector('input[placeholder="Enter text"]')?.value.trim() || "";
                    if (!email) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please fill required fields!"
                        };
                    }
                    if (!isValidEmail(email)) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please enter a valid email address!"
                        };
                    }
                    return {
                        ok: true,
                        data: `https://mail.google.com/mail/?view=cm&fs=1&to=${encodeURIComponent(email)}&su=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`,
                        message: ""
                    };
                } else if (type === "sms") {
                    const phone = tab.querySelector('input[placeholder="Enter recipient\\\'s Phone Number"]')?.value.trim() || "";
                    const message = tab.querySelector('input[placeholder="Enter message"]')?.value.trim() || "";
                    if (!phone) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please fill required fields!"
                        };
                    }
                    if (!isValidPhone(phone)) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please enter a valid phone number!"
                        };
                    }
                    return {
                        ok: true,
                        data: `SMSTO:${phone}:${message}`,
                        message: ""
                    };
                } else if (type === "social") {
                    const inputs = tab.querySelectorAll(".qr-social-feild input[type='url']");
                    const links = [];
                    for (const inp of inputs) {
                        const v = inp.value.trim();
                        if (!v) continue;
                        if (!isValidURL(v)) {
                            return {
                                ok: false,
                                data: null,
                                message: "Please enter a valid social profile URL (http/https)."
                            };
                        }
                        links.push(v);
                    }
                    if (links.length === 0) {
                        return {
                            ok: false,
                            data: null,
                            message: "Please fill required fields!"
                        };
                    }
                    return {
                        ok: true,
                        data: (links.length === 1 ? links[0] : JSON.stringify({
                            links
                        })),
                        message: ""
                    };
                }

                return {
                    ok: false,
                    data: null,
                    message: "Please fill required fields!"
                };
            }


            // --- Generate QR ---
            if (generateBtn) {
                generateBtn.addEventListener("click", async () => {
                    const res = buildQRData();
                    if (!res.ok) {
                        alert(res.message);
                        return;
                    }
                    const qrData = res.data; // use this from here on

                    if (qrData instanceof File) {
                        try {
                            const pdfUrl = await uploadPdfAndGetUrl(qrData);
                            currentQRData = pdfUrl; // ✅ save
                            qrCode.update({
                                data: currentQRData
                            });
                        } catch (err) {
                            console.error(err);
                            alert("Error uploading PDF.");
                        }
                    } else {
                        currentQRData = qrData; // ✅ save
                        qrCode.update({
                            data: currentQRData
                        });
                    }
                });
            }

            // --- Format selection ---
            formatLinks.forEach(link => {
                link.addEventListener("click", e => {
                    e.preventDefault();
                    formatLinks.forEach(l => l.classList.remove("active"));
                    link.classList.add("active");
                    selectedFormat = link.textContent.replace(".", "").trim().toLowerCase();
                });
            });

            // --- Download QR ---
            if (downloadBtn) {
                downloadBtn.addEventListener("click", async () => {
                    try {
                        if (selectedFormat === "pdf") {
                            const blob = await qrCode.getRawData("png");
                            const dataUrl = await new Promise(resolve => {
                                const reader = new FileReader();
                                reader.onload = () => resolve(reader.result);
                                reader.readAsDataURL(blob);
                            });
                            const pdf = new jsPDF();
                            pdf.addImage(dataUrl, "PNG", 10, 10, 100, 100);
                            pdf.save("qr-code.pdf");
                        } else if (selectedFormat === "eps") {
                            const svgData = await qrCode.getRawData("svg");
                            const blob = new Blob([svgData], {
                                type: "application/postscript"
                            });
                            const link = document.createElement("a");
                            link.href = URL.createObjectURL(blob);
                            link.download = "qr-code.eps";
                            link.click();
                        } else {
                            qrCode.download({
                                name: "qr-code",
                                extension: selectedFormat
                            });
                        }
                    } catch (error) {
                        console.error("Download failed:", error);
                        alert("Unable to download QR Code!");
                    }
                });
            }
        }

        // --- Init all tabs ---
        initQRCode("#tab1", "#qr-preview", "url");
        initQRCode("#tab2", "#qr-preview", "pdf");
        initQRCode("#tab3", "#qr-preview", "email");
        initQRCode("#tab4", "#qr-preview", "sms");
        initQRCode("#tab5", "#qr-preview", "social");
    });
</script>