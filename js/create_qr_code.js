let qrCode = null;
// ========== STEP NAVIGATION SETUP ==========
jQuery(document).ready(function($) {

    // When static QR card clicked
    jQuery(document).on("click", ".static_qr_item .card", function () {
    if (window.qrEditMode) return; //  block in edit mode
        window.isStaticQR = 1;
    });

    jQuery(document).on("click", ".dynamic_qr_item .card", function () {
        if (window.qrEditMode) return; //  block in edit mode
        window.isStaticQR = 0;
    });


    //  Handle clicks on social icons (delegated)
    $(document).on("click", ".qr-social_wrapper ul li", function () {
        const targetId = $(this).data("tab");
        const $field = $("#" + targetId);

        // Toggle active states (independent)
        $(this).toggleClass("qr-active-social");
        $field.toggleClass("qr-active-social");
    });

    //  Handle clicks on close icons (delegated)
    $(document).on("click", ".socail-close-icon", function (e) {
        e.stopPropagation(); // Don’t trigger parent click

        const $parentField = $(this).closest(".qr-social-feild");
        const fieldId = $parentField.attr("id");

        // Remove active class from both field and matching tab
        $parentField.removeClass("qr-active-social");
        $('.qr-social_wrapper ul li[data-tab="' + fieldId + '"]').removeClass("qr-active-social");
    });


    // ========== STEP FORM NAVIGATION ==========
    const $tabs = jQuery("#createQRtabs .nav-link");
    const $step1Cards = jQuery(".step-1 .card a");

    // Disable all tabs except Step 1
    $tabs.each(function() {
        const $tab = jQuery(this);
        if ($tab.attr("id") !== "step1-tab") {
            $tab.addClass("disabled");
        }
    });

    // Prevent disabled tabs from being clicked
    $tabs.on("click", function(e) {
        const $tab = jQuery(this);
        if ($tab.hasClass("disabled")) {
            e.preventDefault();
        }
    });

    // Step 1 -> select type
    $step1Cards.on("click", function() {
    const selectedType = jQuery(this).closest(".card").data("qr-type");
 console.log('selectedType : '+ selectedType);
        // Hide all Step 2 sections
        jQuery("#step2 .inner-section-setup").hide();

        // Show selected section
        const $targetSection = jQuery(`#step2 .inner-section-setup.${selectedType}`);
        console.log('targetSection : '+ $targetSection);
        if ($targetSection.length) {
            $targetSection.show();
        }

        // Enable Step 2 tab & switch to it
        const $step2Tab = jQuery("#step2-tab");
        $step2Tab.removeClass("disabled");
        new bootstrap.Tab($step2Tab[0]).show();

        //  Preview logic based on selection
        if (selectedType === "social-media") {
            jQuery(".preview-qr").hide();
            jQuery(".social-media-preview").show();
        } else {
            jQuery(".preview-qr").show();
            jQuery(".social-media-preview").hide();
        }
    });
    // ======================================================
    //               EDIT MODE: AUTO PREFILL
    // ======================================================

  /* ============================================================
   🔒 EDIT MODE — FULL SAFE RESTORE (STATIC / DYNAMIC LOCKED)
    ============================================================ */
    if (window.qrEditMode) {

        /* --------------------------------------------------------
        1️⃣ LOCK STATIC / DYNAMIC FOREVER
        -------------------------------------------------------- */
        window.isStaticQR = window.qrEditMode.is_static === "1" ? 1 : 0;
        console.log("🔒 Locked isStaticQR:", window.isStaticQR);

        if (window.isStaticQR === 1) {
            jQuery(".dynamic_qr_item").remove();
            jQuery(".dynamic_qr_title").hide();
            jQuery(".static_qr_title").show();
        } else {
            jQuery(".static_qr_item").remove();
            jQuery(".static_qr_title").hide();
            jQuery(".dynamic_qr_title").show();
        }

        /* --------------------------------------------------------
        2️⃣ RESTORE CORE DATA
        -------------------------------------------------------- */
        const {
            id,
            type: TYPE,
            value: VALUE,
            name: NAME,
            color: COLOR,
            shape: SHAPE,
            logo: LOGO
        } = window.qrEditMode;

        console.log("✔ Edit Mode Data:", window.qrEditMode);
        /* --------------------------------------------------------
        2️⃣A PREFILL STEP-2 INPUTS (CRITICAL)
        -------------------------------------------------------- */
       
        if (
            window.qrEditMode.type === "social-media" &&
            window.qrEditMode.social_preview_id
        ) {
            console.log(
                "🔄 Loading social preview:",
                window.qrEditMode.social_preview_id
            );

            setTimeout(() => {
                loadSocialPreviewData(window.qrEditMode.social_preview_id);
            }, 600);
        }

        function restoreStep2Inputs() {

            switch (TYPE) {

                case "web":
                    jQuery("#webForm input[type='url']").val(VALUE);
                    break;

                case "pdf":
                    jQuery("#pdfForm input[type='url']").val(VALUE);
                    break;

                case "mail": {
                    const mail = new URL(VALUE);
                    jQuery("#emailForm input[type='email']").val(mail.pathname.replace("/", ""));
                    const params = new URLSearchParams(mail.search);
                    jQuery("#emailForm input[placeholder='Subject']").val(params.get("subject") || "");
                    jQuery("#emailForm textarea").val(params.get("body") || "");
                    break;
                }

                case "sms": {
                    const sms = VALUE.replace("sms:", "");
                    const [num, query] = sms.split("?");
                    jQuery("#smsForm input[type='number']").val(num);
                    if (query) {
                        const params = new URLSearchParams(query);
                        jQuery("#smsForm textarea").val(params.get("body") || "");
                    }
                    break;
                }

                case "linkpage": {
                    const slug = VALUE.split("/link-page/")[1]?.replaceAll("/", "");
                    if (slug) {
                        jQuery("#linkpageSelect").val(slug).trigger("change");
                    }
                    break;
                }
            }
        }

        /* --------------------------------------------------------
        3️⃣ FORCE QR DATA FIRST (CRITICAL)
        -------------------------------------------------------- */
        jQuery(window).data("lastQRData", VALUE);

        const ensureQRReady = () => {
            return typeof qrCode !== "undefined" &&
                qrCode.update &&
                jQuery(window).data("lastQRData");
        };

        /* --------------------------------------------------------
        4️⃣ SAFE STEP-3 RESTORE (ORDER GUARANTEED)
        -------------------------------------------------------- */
        function restoreStep3Safely() {

            if (!ensureQRReady()) return;

            console.log("🔥 Restoring Step 3");

            /* ---- QR DATA ---- */
            qrCode.update({ data: VALUE });
            if (TYPE === "linkpage") {
                updateStep4Preview(VALUE);
            }

            /* ---- COLOR ---- */
            if (COLOR) {
                jQuery("#hexcolor").val(COLOR);
                jQuery("#colorpicker").val(COLOR);

                qrCode.update({
                    dotsOptions: { color: COLOR }
                });
            }

            /* ---- SHAPE ---- */
            if (SHAPE) {
                jQuery(".dynamic-btn-shape").removeClass("active");

                jQuery(".dynamic-btn-shape").each(function () {
                    const txt = jQuery(this).find("span").text().trim().toLowerCase();
                    if (
                        (txt === "square" && SHAPE === "square") ||
                        (txt === "squre" && SHAPE === "square") ||
                        (txt === "dots" && SHAPE === "dots") ||
                        (txt === "rounded" && SHAPE === "rounded")
                    ) {
                        jQuery(this).addClass("active");
                         jQuery(this).addClass("active-logo");
                    }
                });

                qrCode.update({
                    dotsOptions: { type: SHAPE }
                });
            }

            /* ---- LOGO (UPLOADED > PRESET) ---- */
            restoreLogoSafely();

            /* ---- FINAL APPLY ---- */
            setTimeout(applyQRStyles, 120);
        }

        /* --------------------------------------------------------
        5️⃣ LOGO RESTORE — SINGLE SOURCE
        -------------------------------------------------------- */
        function restoreLogoSafely() {

            if (!LOGO) return;

            // reset UI
            jQuery(".dynamic-social-logo").removeClass("active active-logo");
            jQuery("#uploaded-logo-preview").remove();
            jQuery("#dynamic-qr-logo").val("").data("uploadedLogo", "");
            jQuery(".upload_logo_wrapper .file-name").text("");

            /* ---- UPLOADED LOGO ---- */
            if (LOGO.startsWith("data:image") || LOGO.startsWith("http")) {

                jQuery("#dynamic-qr-logo").data("uploadedLogo", LOGO);
                jQuery(".upload_logo_wrapper").append(
                    `<img id="uploaded-logo-preview" src="${LOGO}" style="max-width:70px;margin-top:8px;">`
                );

                qrCode.update({
                    image: LOGO,
                    imageOptions: {
                        crossOrigin: "anonymous",
                        margin: 5,
                        imageSize: 0.25
                    }
                });

                console.log("✔ Uploaded logo restored");
                return;
            }

            /* ---- PRESET LOGO ---- */
            const logoFile = LOGO.split("/").pop().toLowerCase();

            jQuery(".dynamic-social-logo img").each(function () {
                const preset = jQuery(this).attr("src").split("/").pop().toLowerCase();
                if (preset === logoFile) {
                    const btn = jQuery(this).closest(".dynamic-social-logo");
                    btn.addClass("active active-logo");

                    qrCode.update({
                        image: jQuery(this).attr("src"),
                        imageOptions: {
                            crossOrigin: "anonymous",
                            margin: 5,
                            imageSize: 0.25
                        }
                    });

                    console.log("✔ Preset logo restored");
                }
            });
        }

        /* --------------------------------------------------------
        6️⃣ WAIT FOR STEP-3 RENDER (NO RACE)
        -------------------------------------------------------- */
        function waitForStep3ThenRestore() {
            let tries = 0;
            const timer = setInterval(() => {
                tries++;

                if (
                    document.querySelector("#step3 .qr-inner-tab_wrapper") &&
                    ensureQRReady()
                ) {
                    clearInterval(timer);
                    restoreStep3Safely();
                }

                if (tries > 40) {
                    clearInterval(timer);
                    console.warn("⚠ Step-3 restore timeout");
                }
            }, 120);
        }

        /* --------------------------------------------------------
       /* --------------------------------------------------------
        7️⃣ AUTO NAVIGATION
        -------------------------------------------------------- */
        setTimeout(() => {
        document
            .querySelector(`.step-1 .card[data-qr-type="${TYPE}"] a`)
            ?.click();
        }, 300);

        /* --------------------------------------------------------
        🔁 LOAD SOCIAL PREVIEW — AFTER STEP-2 IS VISIBLE
        -------------------------------------------------------- */
        if (
            TYPE === "social-media" &&
            window.qrEditMode.social_preview_id
        ) {
            setTimeout(() => {
                console.log(
                    "✅ Loading social preview AFTER step-2 render:",
                    window.qrEditMode.social_preview_id
                );

                loadSocialPreviewData(window.qrEditMode.social_preview_id);
            }, 900); // 👈 must be AFTER step-1 click
        }

        setTimeout(() => {
            restoreStep2Inputs();   // ✅ Step-2 prefill
        }, 700);

        setTimeout(() => {
            const nameField = document.querySelector(".name_qr_wrapper");
            if (nameField) nameField.value = NAME || "";
        }, 900);

        /* --------------------------------------------------------
        7️⃣B AUTO STEP-3 RESTORE (EDIT MODE)
        -------------------------------------------------------- */
        setTimeout(() => {
            waitForStep3ThenRestore();   // ✅ THIS IS THE LINE
        }, 1200);

        /* --------------------------------------------------------
        7️⃣A LINKPAGE EDIT MODE RESTORE
        -------------------------------------------------------- */
        if (window.qrEditMode.type === "linkpage") {

            const linkUrl = window.qrEditMode.value || "";
            const slug = linkUrl.split("/link-page/")[1]?.replace("/", "");

            // setTimeout(() => {
            //     document.querySelector(".card.Linkpages a")?.click();
            // }, 300);

            setTimeout(() => {
                const select = document.getElementById("linkpageSelect");
                if (select && slug) {
                    select.value = slug;
                    select.dispatchEvent(new Event("change"));
                }
            }, 600);
        }

        /* --------------------------------------------------------
        8️⃣ TRIGGER RESTORE ON NEXT → STEP-3
        -------------------------------------------------------- */
        document.addEventListener("click", function (e) {
            if (e.target.closest("#step2 .btn.btn-primary")) {
                waitForStep3ThenRestore();
            }
        });

        /* --------------------------------------------------------
        9️⃣ LOCK STEP-1
        -------------------------------------------------------- */
        setTimeout(() => {
            document.querySelector("#step1-tab")?.classList.add("disabled");
            document.querySelectorAll(".step-1 .card").forEach(c => {
                c.style.pointerEvents = "none";
                c.style.opacity = "0.5";
            });
        }, 300);
    }



    // Step 2 forms -> on submit enable Step 3
    jQuery("#step2 form").on("submit", function(e) {
        e.preventDefault(); // Prevent real submit for demo

        // Enable Step 3
        const $step3Tab = jQuery("#step3-tab");
        $step3Tab.removeClass("disabled");
        new bootstrap.Tab($step3Tab[0]).show();
    });

    // Step 3 forms -> on submit enable Step 4
    jQuery("#step3 form").on("submit", function(e) {
        e.preventDefault();

        // Enable Step 4
        const $step4Tab = jQuery("#step4-tab");
        $step4Tab.removeClass("disabled");
        new bootstrap.Tab($step4Tab[0]).show();
    });

   
   // ========== STEP FORM ==========

    // Cached form inputs
    const urlInput = jQuery("#webForm input[type='url']");
    const pdfUrlInput = jQuery("#pdfForm input[type='url']");
    const pdfFileInput = jQuery("#pdfForm input[type='file']");
    const emailInput = jQuery("#emailForm input[type='email']");
    const phoneInput = jQuery("#smsForm input[type='number']");
    const socialForm = jQuery("#socialMediaForm");
    const linkpagesForm = jQuery("#linkpagesForm");


    // ========== WEB ==========
    if (urlInput.length) {
        const btn = jQuery("#webForm button.btn-primary");
        console.log(btn);

        // Optional input event
        urlInput.on("input", function() {
            // Add validation logic if needed
        });

        jQuery(document).on("click", "#webForm button.btn-primary", function(e) {
            e.preventDefault();

            let url = jQuery("#webForm input[type='url']").val().trim(); // use let

            if (!url) {
                alert("Please enter a URL");
                return;
            }

            // ⭐ AUTO-FIX: add https:// if missing
            if (!/^https?:\/\//i.test(url)) {
                url = "https://" + url;
            }

            // Validate final URL
            try {
                new URL(url);
            } catch {
                alert("Enter a valid URL.");
                return;
            }
            console.log("✅ Web QR generated for:", url);   
            previewQRCode(url);
            goToStep("step3");
        });


    }


    // ========== PDF ==========
    if (pdfUrlInput.length || pdfFileInput.length) {
        jQuery(document).on("click", "#pdfForm button.btn-primary", function(e) {
            e.preventDefault();

            const pdfFileEl = jQuery("#pdfForm input[type='file']")[0];
            let pdfUrlVal = jQuery("#pdfForm input[type='url'], #pdfForm input[type='text']").val().trim(); // <-- use let instead of const

            console.log('PDF File Element:', pdfFileEl);
            console.log('PDF URL Value:', pdfUrlVal);

            if (pdfFileEl && pdfFileEl.files.length > 0) {
                const formData = new FormData();
                formData.append("action", "upload_pdf_file");
                formData.append("pdf_file", pdfFileEl.files[0]);

                fetch(qrAjax.ajax_url, {
                    method: "POST",
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        previewQRCode(data.url);
                        goToStep("step3");
                    } else {
                        alert(data.message);
                    }
                });

            } else if (pdfUrlVal) {

                // 🔹 Normalize www. URLs
                if (/^www\./i.test(pdfUrlVal)) {
                    pdfUrlVal = "https://" + pdfUrlVal; // safe now, since pdfUrlVal is a let
                }

                // 🔹 Validate URL and ensure it ends with .pdf
                if (!/^(https?:\/\/)/i.test(pdfUrlVal) || !pdfUrlVal.toLowerCase().endsWith(".pdf")) {
                    alert("Enter a valid PDF URL starting with http://, https://, or www. and ending with .pdf");
                    return;
                }

                previewQRCode(pdfUrlVal);
                goToStep("step3");

            } else {
                alert("Upload a PDF or provide a URL");
            }
        });
    }



   // ========== EMAIL ==========
    if (emailInput.length) {

        // Enable or disable button dynamically
        jQuery(document).on("input", "#emailForm input[type='email']", function() {
            const val = jQuery(this).val().trim();
            jQuery("#emailForm button.btn-primary").prop("disabled", !val);
        });

        // ✅ Delegated click binding
        jQuery(document).on("click", "#emailForm button.btn-primary", function(e) {
            e.preventDefault();

            // Always select inside handler (for dynamic forms)
            const emailVal   = jQuery("#emailForm input[type='email']").val().trim();
            const subjectVal = jQuery("#emailForm input[placeholder='Subject']").val().trim();
            const bodyVal    = jQuery("#emailForm textarea").val().trim();

            if (!emailVal) {
                alert("Please enter an email address.");
                return;
            }

            // Build the mailto link
            let mail = `mailto:${encodeURIComponent(emailVal)}`;
            const params = [];
            if (subjectVal) params.push(`subject=${encodeURIComponent(subjectVal)}`);
            if (bodyVal) params.push(`body=${encodeURIComponent(bodyVal)}`);
            if (params.length) mail += "?" + params.join("&");

            console.log("✅ Email QR generated for:", mail);
            previewQRCode(mail);
            goToStep("step3");
        });
    }



    // ========== SMS ==========
    if (phoneInput.length) {

        // Enable or disable button dynamically
        jQuery(document).on("input", "#smsForm input[type='number']", function() {
            const val = jQuery(this).val().trim();
            jQuery("#smsForm button.btn-primary").prop("disabled", !val);
        });

        // ✅ Delegated click binding
        jQuery(document).on("click", "#smsForm button.btn-primary", function(e) {
            e.preventDefault();

            const phoneVal = jQuery("#smsForm input[type='number']").val().trim();
            const msgVal = jQuery("#smsForm textarea").val().trim();

            if (!phoneVal) {
                alert("Please enter a phone number.");
                return;
            }

            // Build the SMS URI
            let sms = "sms:" + encodeURIComponent(phoneVal);
            if (msgVal) {
                // Encode newlines properly in the message
                const encodedMsg = encodeURIComponent(msgVal.replace(/\r?\n/g, "%0D%0A"));
                sms += "?body=" + encodedMsg;
            }

            console.log("✅ SMS QR generated for:", sms);
            previewQRCode(sms);
            goToStep("step3");
        });
    }


    // ========== SOCIAL ==========
    // ========== SOCIAL ==========
    // if (socialForm.length) {

        // ✅ Delegated click binding
        // ========== SOCIAL ==========
        if (socialForm.length) {

            jQuery(document).on("click", "#socialMediaForm button.btn-primary", async function (e) {
                e.preventDefault();

                const socialForm = jQuery("#socialMediaForm");

                const title = socialForm.find(".header_title_wrapper input").val().trim() || "Title";
                const message = socialForm.find(".body_message_wrapper input").val().trim() || "Message";
                const subtext = socialForm.find(".body_subtext_wrapper input").val().trim() || "Subtext";

                const logoInput = socialForm.find(".header_logo_wrapper input[type='file']")[0];
                const bodyImageInput = socialForm.find(".body_image_wrapper input[type='file']")[0];

                // Collect social links
                const links = [];
                socialForm.find("input[type='url']").each(function () {
                    const inp = jQuery(this);
                    const platform = inp.attr("placeholder").replace(" URL", "").trim();
                    if (inp.val().trim()) {
                        links.push({
                            platform: platform,
                            url: inp.val().trim()
                        });
                    }
                });

                if (!links.length) {
                    alert("Provide at least one social link");
                    return;
                }

                // Social data object
                const socialData = {
                    title,
                    message,
                    subtext,
                    titleColor: document.getElementById("hexcolor-title")?.value || "#000000",
                    bgColor: document.getElementById("hexcolor-background")?.value || "#ffffff",
                    logo: logoInput?.files[0] ? URL.createObjectURL(logoInput.files[0]) : "",
                    bodyImage: bodyImageInput?.files[0] ? URL.createObjectURL(bodyImageInput.files[0]) : "",
                    links
                };

                const formData = new FormData();
                formData.append("action", "create_social_preview");
                formData.append("data", JSON.stringify(socialData));

                // ⭐ IMPORTANT — INCLUDE preview_id in edit mode
                formData.append("preview_id", window.qrEditMode?.social_preview_id || "");

               // In edit mode, preserve old images if user did NOT upload new ones
                formData.append("existing_logo", window.qrEditMode?.existing_logo || "");
                formData.append("existing_bodyImage", window.qrEditMode?.existing_bodyImage || "");

                if (logoInput?.files.length > 0) {
                    formData.append("logo", logoInput.files[0]);
                }

                if (bodyImageInput?.files.length > 0) {
                    formData.append("bodyImage", bodyImageInput.files[0]);
                }


                // AJAX call
                try {
                    const res = await fetch(qrAjax.ajax_url, {
                        method: "POST",
                        body: formData
                    });
                    const dataRes = await res.json();

                    if (dataRes.success && dataRes.data?.url) {

                        // Update step 2 + step 4 preview
                        updateStep4SocialPreview({
                            ...socialData,
                            logo: dataRes.data.logo_url || socialData.logo,
                            bodyImage: dataRes.data.bodyImageUrl || socialData.bodyImage
                        });

                        previewQRCode(dataRes.data.url);
                        updateStep4Preview(dataRes.data.url);

                        goToStep("step3");

                        console.log("UPDATED Social Preview:", dataRes.data.url);
                    } else {
                        alert(dataRes.message || "Error creating/updating social preview");
                    }

                } catch (err) {
                    console.error(err);
                    alert("Error generating social preview");
                }
            });
        }   
        if (linkpagesForm.length) {
            // STEP 2 — LINKPAGE SELECT → IFRAME PREVIEW
            jQuery(document).on("change", "#linkpageSelect", function () {
                var slug = jQuery(this).val();
                var $iframe = jQuery("#linkpagePreview");

                if (!$iframe.length) {
                    console.warn("linkpagePreview iframe not found");
                    return;
                }

                if (!slug) {
                    $iframe.attr("src", "");
                    return;
                }

                var url = "https://simplqr.com/link-page/" + slug + "/";

                console.log("🔗 Loading Linkpage preview:", url);
                $iframe.attr("src", url);
            });
            // ========== LINKPAGE — NEXT BUTTON ==========
            jQuery(document).on("click", "#linkpagesForm .linkapge_next.btn-primary", function (e) {
                e.preventDefault();

                const slug = jQuery("#linkpageSelect").val();

                if (!slug) {
                    alert("Please select a Linkpage");
                    return;
                }

                const linkpageUrl = "https://simplqr.com/link-page/" + slug + "/";

                console.log("✅ Linkpage QR generated for:", linkpageUrl);

                previewQRCode(linkpageUrl);
                goToStep("step3");
            });


        }
        
    setTimeout(() => {
        
   
    // ========== LOGO UPLOAD ==========
    const $logoInput = jQuery("#socialMediaForm .header_logo_wrapper input[type='file']");
    const $logoPreview = jQuery(".preview .logo-img");

    $logoInput.on("change", function (e) {
        const file = e.target.files[0];
        if (file) {
            $logoPreview.attr("src", URL.createObjectURL(file));
        }
    });

    // ========== TITLE + COLOR ==========
    const $titleInput = jQuery("#socialMediaForm .header_title_wrapper input");
    const $titlePreview = jQuery(".preview .header h5");
    const $titleColorInput = jQuery("#socialMediaForm .header_title_color_wrapper input[type='color']");

    $titleInput.on("input", function () {
        $titlePreview.text(jQuery(this).val() || "Default Title");
    });

    $titleColorInput.on("input", function () {
        $titlePreview.css("color", jQuery(this).val());
    });

    // ========== BODY IMAGE ==========
    const $bodyImgInput = jQuery("#socialMediaForm .body_image_wrapper input[type='file']");
    const $bodyImgPreview = jQuery(".preview .cube-image");

    $bodyImgInput.on("change", function (e) {
        const file = e.target.files[0];
        if (file) {
            $bodyImgPreview.attr("src", URL.createObjectURL(file));
        }
    });

    // ========== MESSAGE & SUBTEXT ==========
    const $msgInput = jQuery("#socialMediaForm .body_message_wrapper input");
    const $subtextInput = jQuery("#socialMediaForm .body_subtext_wrapper input");
    const $msgPreview = jQuery(".preview .content h5");
    const $subPreview = jQuery(".preview .content p");

    $msgInput.on("input", function () {
        $msgPreview.text(jQuery(this).val() || "Default Message");
    });

    $subtextInput.on("input", function () {
        $subPreview.text(jQuery(this).val() || "Default Subtext");
    });

    // ========== BACKGROUND COLOR ==========
    const $bgColorInput = jQuery("#socialMediaForm .body_bgcolor_wrapper input[type='color']");
    const $cardPreview = jQuery(".preview.card .body-content-wrapper");

    $bgColorInput.on("input", function () {
        $cardPreview.css("background-color", jQuery(this).val());
    });

    // ========== SOCIAL LINKS (Add / Remove Buttons) ==========
    const $socialFields = jQuery(".qr-social-feild");
    const $footerPreview = jQuery(".preview .footer");

    $socialFields.each(function () {
        const $field = jQuery(this);
        const $input = $field.find("input[type='url']");
        const $closeBtn = $field.find(".socail-close-icon span");
        const $icon = $field.find("img");
        const iconSrc = $icon.length ? $icon.attr("src") : "";

        // Add or update button
        $input.on("input", function () {
            const val = jQuery(this).val().trim();
            let $existingBtn = $footerPreview.find(`[data-id="${$field.attr("id")}"]`);

            if (val) {
                if (!$existingBtn.length) {
                    const $newBtn = jQuery("<a></a>")
                        .attr({
                            href: val,
                            target: "_blank",
                            "data-id": $field.attr("id")
                        })
                        .addClass("social-button")
                        .html(`<img src="${iconSrc}" alt="Social Icon"/>`);
                    $footerPreview.append($newBtn);
                } else {
                    $existingBtn.attr("href", val);
                }
            } else if ($existingBtn.length) {
                $existingBtn.remove();
            }
        });

        // Close button clears field + preview
        $closeBtn.on("click", function () {
            $input.val("");
            const $existingBtn = $footerPreview.find(`[data-id="${$field.attr("id")}"]`);
            if ($existingBtn.length) $existingBtn.remove();
        });
    });
    // Sync color pickers
    function bindColorPicker(picker, input) {
        if (!picker || !input) return;
        picker.addEventListener("input", function() {
            input.value = this.value;
            applyQRStyles();
        });
        input.addEventListener("input", function() {
            if (/^#([0-9A-F]{3}){1,2}$/i.test(this.value)) {
                picker.value = this.value;
                applyQRStyles();
            }
        });
    }
    bindColorPicker(document.getElementById("colorpicker"), document.getElementById("hexcolor"));
    bindColorPicker(document.getElementById("colorpicker-title"), document.getElementById("hexcolor-title"));
    bindColorPicker(document.getElementById("colorpicker-background"), document.getElementById("hexcolor-background"));
     }, 5000);

    // ========== GLOBAL STATE ==========
   // ========== GLOBAL STATE ==========
    if (!window.qrEditMode) {
        jQuery(window).data("lastQRData", "");
    }
    jQuery(window).data("createdQRPostId", 0);

    // ========== TAB SWITCHING ==========
    jQuery(".social_tab").on("click", function () {
        const targetId = jQuery(this).data("tab");

        jQuery(".social_tab").removeClass("active");
        jQuery(".qr-tab-pane").removeClass("active");

        jQuery(this).addClass("active");
        jQuery(`#${targetId}`).addClass("active");
    });

    jQuery("#goToPreviewTab").on("click", function () {
        jQuery('[data-tab="tab2"]').trigger("click");
    });

     // Handle "Next" buttons inside .preview-qr-next
    jQuery(".preview-qr-next button.btn").on("click", function(e) {
        e.preventDefault();

        const $step4Tab = jQuery("#step4-tab");
        $step4Tab.removeClass("disabled");
        new bootstrap.Tab($step4Tab[0]).show();

        // Keep Step 3 enabled for back navigation
        jQuery("#step3-tab").removeClass("disabled");
    });


 initQRCode("qrPreviewContainer");
    // ========== REPLACE DIVS WITH FORMS ==========
    function replaceDivsWithForms() {
        const $divs = jQuery(".form-wrapper");

        if ($divs.length > 0) {
            $divs.each(function() {
                const $div = jQuery(this);
                const $form = jQuery("<form></form>")
                    .addClass($div.attr("class"))
                    .attr("id", $div.attr("id") || "")
                    .html($div.html());

                $div.replaceWith($form);
            });

            clearInterval(checkInterval); // Stop checking once replaced
            console.log("✅ All .form-wrapper divs replaced with forms");
        }
    }

    // Keep checking until elements exist
    const checkInterval = setInterval(replaceDivsWithForms, 200);


    // ========== QR TAB BUTTON LOG ==========
    jQuery(".um-account-tab-create_qr_code button").on("click", function(e) {
        e.preventDefault(); // prevent unwanted reloads or submits
        console.log("QR Code tab button clicked 🚀");
    });
     jQuery(".um-account-tab-analytics button").on("click", function(e) {
        e.preventDefault(); // prevent unwanted reloads or submits
        console.log("QR Code tab button clicked 🚀");
    });
     jQuery(".um-account-tab-qr_code button").on("click", function(e) {
        e.preventDefault(); // prevent unwanted reloads or submits
        console.log("QR Code tab button clicked 🚀");
    });
    // ========== RESET BUTTON ==========
    jQuery(".reset-button .btn-upload").on("click", function () {

        // Remove preset logos
        jQuery(".dynamic-social-logo").removeClass("active active-logo");

        // Reset upload input fully
        const $uploadInput = jQuery("#dynamic-qr-logo");
        if ($uploadInput.length) {
            $uploadInput.val("");
            $uploadInput.data("uploadedLogo", "");
        }

        // Clear edit-mode logo
        window.qrEditMode = window.qrEditMode || {};
        window.qrEditMode.logo = "";

        // Remove uploaded preview
        jQuery("#uploaded-logo-preview").remove();
        jQuery(".upload_logo_wrapper .file-name").text("");


        // Remove image from QR
        qrCode.update({ image: "" });

        updateStep4Preview();

        console.log("✔ Full logo reset (preset + uploaded + editMode cleared)");
    });



    // ========== BIND STYLE BUTTONS ==========
    jQuery(".dynamic-btn-shape").on("click", function () {
        jQuery(".dynamic-btn-shape").removeClass("active");
        jQuery(this).addClass("active");
        applyQRStyles();
    });


    // ========== SOCIAL LOGO BUTTONS ==========
    jQuery(".dynamic-social-logo").on("click", function () {

        // 1. Activate selected preset logo
        jQuery(".dynamic-social-logo").removeClass("active active-logo");
        jQuery(this).addClass("active active-logo");

        // 2. Clear upload input
        const $uploadInput = jQuery("#dynamic-qr-logo");
        if ($uploadInput.length) {
            $uploadInput.val("");
            $uploadInput.data("uploadedLogo", "");
        }

        // 3. Remove uploaded preview image
        jQuery("#uploaded-logo-preview").remove();

        // 4. MOST IMPORTANT → prevent edit-mode logo from restoring
        window.qrEditMode = window.qrEditMode || {};
        window.qrEditMode.logo = "";

        // 5. Remove filename display if any
        jQuery(".upload_logo_wrapper .file-name").text("");

        // 6. Update QR preview
        applyQRStyles();

        console.log("✔ Preset logo selected → uploaded & edit-mode logo cleared");
    });



    // ========== UPLOAD LOGO WITH PREVIEW ==========
    jQuery("#dynamic-qr-logo").on("change", function () {
        const input = this;

        if (input.files && input.files[0]) {
            const reader = new FileReader();

            reader.onload = function (e) {
                const logoBase64 = e.target.result;

                // Save uploaded logo in data attribute
                jQuery(input).data("uploadedLogo", logoBase64);

                // Disable preset logos
                jQuery(".dynamic-social-logo").removeClass("active active-logo");

                // ----- SHOW PREVIEW -----
                if (!jQuery("#uploaded-logo-preview").length) {
                    jQuery(".upload_logo_wrapper").append(
                        `<img id="uploaded-logo-preview" src="${logoBase64}" style="max-width:70px;margin-top:8px;">`
                    );
                } else {
                    jQuery("#uploaded-logo-preview").attr("src", logoBase64).show();
                }

                // Clear filename display (optional)
                jQuery(".upload_logo_wrapper .file-name").text("");

                // Update QR styles
                applyQRStyles();
            };

            reader.readAsDataURL(input.files[0]);
        }
    });




// =========================================================
//                 FINISH BUTTON — CREATE OR UPDATE
// =========================================================
jQuery(document).on("click", ".finishQRBtn", async function (e) {
    e.preventDefault();

    const qrName = jQuery(".name_qr_wrapper").val()?.trim();
    if (!qrName) return alert("Please enter a name for your QR code");

    // Detect edit mode (existing QR)
    const editingId = window.qrEditMode?.id || 0;

    // Determine QR type + value
    let qrType = "";
    let qrValue = "";

    // WEB
    // WEB
    let urlVal = jQuery("#webForm input[type='url']").val()?.trim();
    if (urlVal) {

        // ⭐ AUTO-FIX: Add https:// if missing
        if (!/^https?:\/\//i.test(urlVal)) {
            urlVal = "https://" + urlVal;
        }

        qrType = "web";
        qrValue = urlVal;
    }
    else if (jQuery("#pdfForm input[type='file']")[0]?.files.length ||
            jQuery("#pdfForm input[type='url']").val()?.trim()) {

        qrType = "pdf";
        qrValue = jQuery(window).data("lastQRData") || jQuery("#pdfForm input[type='url']").val()?.trim();

    // EMAIL
    } else if (jQuery("#emailForm input[type='email']").val()?.trim()) {
        qrType = "mail";
        const to = encodeURIComponent(jQuery("#emailForm input[type='email']").val()?.trim());
        const subject = encodeURIComponent(jQuery("#emailForm input[placeholder='Subject']").val()?.trim());
        const body = encodeURIComponent(jQuery("#emailForm textarea").val()?.trim());
        let params = [];
        if (subject) params.push("subject=" + subject);
        if (body) params.push("body=" + body);
        qrValue = "mailto:" + to + (params.length ? "?" + params.join("&") : "");

    // SMS
    } else if (jQuery("#smsForm input[type='number']").val()?.trim()) {
        qrType = "sms";
        const num = encodeURIComponent(jQuery("#smsForm input[type='number']").val()?.trim());
        const text = encodeURIComponent(jQuery("#smsForm textarea").val()?.trim());
        qrValue = "sms:" + num + (text ? "?body=" + text : "");

    // SOCIAL
   // LINKPAGE ✅ BEFORE SOCIAL
    }else if (jQuery("#linkpageSelect").val()) {

        qrType = "linkpage";
        const slug = jQuery("#linkpageSelect").val();
        qrValue = "https://simplqr.com/link-page/" + slug + "/";
    }

    // SOCIAL (LAST)
    else if (
        jQuery(window).data("lastQRData") &&
        jQuery(window).data("lastQRData").includes("/social-preview/")
    ) {
        qrType = "social-media";
        qrValue = jQuery(window).data("lastQRData");
    }


    if (!qrType || !qrValue) {
        return alert("Please provide valid QR input.");
    }

    // Build form data
    const formData = new FormData();

    if (editingId) {
        // UPDATE existing QR code
        formData.append("action", "update_qr_code_post");
        formData.append("post_id", editingId);
    } else {
        // CREATE new QR code
        formData.append("action", "create_qr_code_post");
        formData.append("user_id", qrAjax.user_id || 0);
        formData.append("user_name", qrAjax.user_name || "");
    }
    if (!editingId) {
    // CREATE ONLY
        formData.append("is_static", window.isStaticQR ? "1" : "0");
    }
    formData.append("qr_name", qrName);
    formData.append("qr_type", qrType);
    formData.append("qr_value", qrValue);
    formData.append("qr_data", jQuery(window).data("lastQRData") || "");
    formData.append("qr_color", jQuery("#hexcolor").val() || "#000000");
    formData.append("qr_shape", jQuery(".dynamic-btn-shape.active span").text().toLowerCase() || "square");

    // Get selected pre-made logo (if any)
    let presetLogo = jQuery(".dynamic-social-logo.active img").attr("src") || "";

    // Get uploaded logo (if any)
    let uploadedLogo = jQuery("#dynamic-qr-logo").data("uploadedLogo") || "";

    // Pick uploaded logo → otherwise pre-made logo
    let finalLogo = uploadedLogo !== "" ? uploadedLogo : presetLogo;

    // Save it
    formData.append("qr_logo", finalLogo);



    try {
        // SAVE base QR data (create or update)
        const res = await fetch(qrAjax.ajax_url, {
            method: "POST",
            body: formData
        });

        const data = await res.json();
        if (!data.success) {
            console.error("SERVER ERROR:", data);
            return alert(data.message || "Failed saving QR Code");
        }

        const finalPostId = editingId || data.data.post_id;
        const finalQRUrl = data.data.qr_url;

        // Update local state
        jQuery(window).data("createdQRPostId", finalPostId);
        jQuery(window).data("lastQRData", finalQRUrl);

        // Regenerate styled QR image
        qrCode.update({ data: finalQRUrl });

        qrCode
            .getRawData("png", { width: 1000, height: 1000 }) // HD export
            .then(blob => {
                const reader = new FileReader();
                reader.onload = () => {

                    const imgForm = new FormData();
                    imgForm.append("action", "save_styled_qr_image");
                    imgForm.append("post_id", finalPostId);
                    imgForm.append("qr_image", reader.result);

                    fetch(qrAjax.ajax_url, {
                        method: "POST",
                        body: imgForm
                    })
                    .then(r => r.json())
                    .then(saveRes => {
                        if (!saveRes.success) {
                            console.error("Failed saving styled image:", saveRes);
                        }

                        setTimeout(() => {
                            location.href = "/membership-account/qr_code";
                        }, 800);
                    });
                };
                reader.readAsDataURL(blob);
            });


    } catch (err) {
        console.error("FINISH ERROR:", err);
        alert("Error saving QR Code.");
    }
});



    // startScanCountAutoRefresh();
    // =======================
    // Update Step 4 on tab click
    // =======================
    jQuery(document).on("click", "#step4-tab", function () {
        updateStep4SocialPreview();
    });

      // Handle tab clicks
    jQuery(".dashboard_inner_wrap ul li").on("click", function () {
        const targetId = jQuery(this).attr("data-tab");

        if (!jQuery(this).hasClass("qr-active-social")) {
            jQuery(this).addClass("qr-active-social");
        }

        const $targetField = jQuery(`#${targetId}`);
        if ($targetField.length && !$targetField.hasClass("qr-active-social")) {
            $targetField.addClass("qr-active-social");
        }
    });

    // Handle close icon clicks
    jQuery(".socail-close-icon").on("click", function (e) {
        e.stopPropagation();

        const $parentField = jQuery(this).closest(".qr-social-feild");
        if ($parentField.length) {
            $parentField.removeClass("qr-active-social");

            const fieldId = $parentField.attr("id");
            const $matchingTab = jQuery(`.dashboard_inner_wrap ul li[data-tab='${fieldId}']`);
            if ($matchingTab.length) {
                $matchingTab.removeClass("qr-active-social");
            }
        }
    });

});
     // ========== INIT QR CODE ==========
    function initQRCode(containerId) {
        // Initialize the QRCodeStyling instance
        qrCode = new QRCodeStyling({
            width: 250,
            height: 250,
            data: "",
            type: "canvas",
            dotsOptions: {
                color: "#000000",
                type: "square"
            },
            backgroundOptions: {
                color: "#ffffff"
            }
        });

        // Use jQuery to handle the container
        const $container = jQuery(`#${containerId}`);
        if ($container.length) {
            $container.empty(); // clear existing content
            qrCode.append($container[0]); // pass DOM element to QRCodeStyling
        }
    }
// =======================
// Global icons
// =======================
window.platformIcons = {
    Instagram: "https://simplqr.com/wp-content/uploads/2025/08/Instagram.png",
    Facebook: "https://simplqr.com/wp-content/uploads/2025/08/Facebook.png",
    Twitter: "https://simplqr.com/wp-content/uploads/2025/08/Twitter.png",
    Youtube: "https://simplqr.com/wp-content/uploads/2025/08/Youtube.png"
};
// =======================
// Get all social links
// =======================
function getSocialLinks() {
    const links = [];

    jQuery("#socialMediaForm .qr-social-content_wrapper input[type='url']").each(function () {
        const url = jQuery(this).val().trim();
        if (!url) return; // skip empty

        // Get platform name from img alt or placeholder fallback
        const $field = jQuery(this).closest(".qr-social-feild");
        const $platformImg = $field.find("img");
        const platform =
            ($platformImg.attr("alt") || jQuery(this).attr("placeholder")?.replace(" URL", "") || "Unknown").trim();

        links.push({
            platform: platform,
            url: url
        });
    });

    return links;
}
function loadSocialPreviewData(previewId) {
    
    jQuery.ajax({
        url: qrAjax.ajax_url,
        type: "POST",
        data: {
            action: "load_social_preview_data",
            id: previewId
        },
        success: function (response) {
            if (!response.success) {
                console.error("Failed to load social preview");
                return;
            }

            let data = response.data;
            window.qrEditMode.existing_logo = data.logo || "";
            window.qrEditMode.existing_bodyImage = data.bodyImage || "";

            // Now prefill the UI
            prefillSocialFields(data);
            updateStep4_SocialPreview(data);  // Step 4

        }
    });
}
function updateStep4_SocialPreview(data) {

    const box = document.querySelector("#socialPreviewContainer");
    if (!box) return;

    // Header logo
    box.querySelector(".header-logo-link img").src =
        data.logo || "/wp-content/uploads/default-logo.png";

    // Title
    box.querySelector(".header h5").textContent = data.title || "";
    box.querySelector(".header h5").style.color = data.titleColor || "#000000";

    // Body Image
    box.querySelector(".cube-image").src =
        data.bodyImage || "/wp-content/uploads/default-banner.jpg";

    // Message + Subtext
    box.querySelector(".content h5").textContent = data.message || "";
    box.querySelector(".content p").textContent = data.subtext || "";

    // Background Color
    box.querySelector(".body-content-wrapper").style.backgroundColor =
        data.bgColor || "#ffffff";

    // Links Footer
    const footer = box.querySelector(".footer.social-links");
    footer.innerHTML = "";

    data.links.forEach(link => {

        const domId =
            link.platform === "Instagram" ? "qr-socail-1" :
            link.platform === "Youtube"   ? "qr-socail-2" :
            link.platform === "Twitter"   ? "qr-socail-3" :
            link.platform === "Facebook"  ? "qr-socail-4" : null;

        const icon = platformIcons[link.platform] ||
            "/wp-content/uploads/default-icon.png";

        const a = document.createElement("a");
        a.href = link.url;
        a.target = "_blank";
        a.dataset.id = domId;
        a.classList.add("social-button");
        a.innerHTML = `<img src="${icon}" alt="Social Icon">`;

        footer.appendChild(a);
    });
}

function prefillSocialFields(data) {

    /* ============================================================
       1️⃣  PREFILL FORM FIELDS (your working part #1)
    ============================================================ */

    // Header fields
    document.querySelector('#socialMediaForm .header_title_wrapper input').value =
        data.title || "";

    document.querySelector('#hexcolor-title').value =
        data.titleColor || "#000000";

    document.querySelector('#colorpicker-title').value =
        data.titleColor || "#000000";

    // Body fields
    document.querySelector('#socialMediaForm .body_message_wrapper input').value =
        data.message || "";

    document.querySelector('#socialMediaForm .body_subtext_wrapper input').value =
        data.subtext || "";

    document.querySelector('#hexcolor-background').value =
        data.bgColor || "#ffffff";

    document.querySelector('#colorpicker-background').value =
        data.bgColor || "#ffffff";

    // Social Links
    if (Array.isArray(data.links)) {
        data.links.forEach(link => {

            let platform = link.platform.toLowerCase();

            let domId =
                platform === "instagram" ? "qr-socail-1" :
                platform === "youtube"   ? "qr-socail-2" :
                platform === "twitter"   ? "qr-socail-3" :
                platform === "facebook"  ? "qr-socail-4" :
                null;

            if (!domId) return;

            let field = document.querySelector(`#${domId} input[type="url"]`);
            if (field) field.value = link.url;

            document.querySelector(`li[data-tab="${domId}"]`)
                ?.classList.add("qr-active-social");

            document.getElementById(domId)
                ?.classList.add("qr-active-social");
        });
    }

    /* ============================================================
       2️⃣  UPDATE PREVIEW (your working part #2)
    ============================================================ */

    // Logo
    const logoImg = document.querySelector(".social-media-preview_wrapper .header-logo-link img");
    if (logoImg) {
        logoImg.src = data.logo || "/wp-content/uploads/default-logo.png";
    }

    // Title
    const titleEl = document.querySelector(".social-media-preview_wrapper .header h5");
    if (titleEl) {
        titleEl.textContent = data.title || "";
        titleEl.style.color = data.titleColor || "#000000";
    }

    // Body image
    const bodyImg = document.querySelector(".social-media-preview_wrapper .cube-image");
    if (bodyImg) {
        bodyImg.src = data.bodyImage || "/wp-content/uploads/default-banner.jpg";
    }

    // Message
    const messageEl = document.querySelector(".social-media-preview_wrapper .content h5");
    if (messageEl) {
        messageEl.textContent = data.message || "";
    }

    // Subtext
    const subtextEl = document.querySelector(".social-media-preview_wrapper .content p");
    if (subtextEl) {
        subtextEl.textContent = data.subtext || "";
    }

    // Background color
    const bgEl = document.querySelector(".social-media-preview_wrapper .body-content-wrapper");
    if (bgEl) {
        bgEl.style.backgroundColor = data.bgColor || "#ffffff";
    }

    // Social link icons
    const footer = document.querySelector(".social-media-preview_wrapper .footer");
    if (footer) {
        footer.innerHTML = "";

        data.links.forEach(link => {

            const platform = link.platform.toLowerCase();

            // Choose correct icon path
            const iconSrc =
                platformIcons[link.platform] ||
                "/wp-content/uploads/default-icon.png";

            // Map platform → correct domId (your spelling system)
            const domId =
                platform === "instagram" ? "qr-socail-1" :
                platform === "youtube"   ? "qr-socail-2" :
                platform === "twitter"   ? "qr-socail-3" :
                platform === "facebook"  ? "qr-socail-4" :
                null;

            if (!domId) return;

            // Create tag exactly like requested
            const a = document.createElement("a");
            a.href = link.url;
            a.target = "_blank";
            a.dataset.id = domId;
            a.classList.add("social-button");

            a.innerHTML = `
                <img src="${iconSrc}" alt="Social Icon">
            `;

            footer.appendChild(a);
        });
    }

}


// =======================
// SOCIAL PREVIEW UPDATE
// =======================
function updateStep4SocialPreview(data = {}) {
    const $container = jQuery("#socialPreviewContainer");
    if (!$container.length) return;

    // === Header & Body ===
    const $titleEl = $container.find(".social-title, .header h5").first();
    if ($titleEl.length) {
        $titleEl.text(data.title || "Social Media");
        $titleEl.css("color", data.titleColor || "#000000");
    }

    const $messageEl = $container.find(".social-message, .content h5").first();
    if ($messageEl.length) {
        $messageEl.text(data.message || "Social Media");
    }

    const $subtextEl = $container.find(".social-subtext, .content p").first();
    if ($subtextEl.length) {
        $subtextEl.text(data.subtext || "Social Media");
    }

    // === Images & Background ===
    if (data.logo) {
        const $logoImg = $container.find(".logo-img");
        if ($logoImg.length) $logoImg.attr("src", data.logo);
    }

    if (data.bodyImage) {
        const $bodyImg = $container.find(".cube-image");
        if ($bodyImg.length) $bodyImg.attr("src", data.bodyImage);
    }

    if (data.bgColor) {
        const $bg = $container.find(".body-content-wrapper");
        if ($bg.length) $bg.css("background-color", data.bgColor);
    }

    // === Footer (Social Links) ===
    let $footer = $container.find(".footer");
    if (!$footer.length) {
        $footer = jQuery("<div></div>").addClass("footer");
        $container.append($footer);
    }

    $footer.empty(); // clear old links

    // Use provided links or fallback
    const links = data.links && data.links.length ? data.links : getSocialLinks();

    jQuery.each(links, function (index, link) {
        const iconSrc =
            (window.platformIcons && window.platformIcons[link.platform]) ||
            "https://wordpress-1504858-5765338.cloudwaysapps.com/wp-content/uploads/2025/08/default-icon.png";

        const $a = jQuery("<a></a>")
            .attr({
                href: link.url || "#",
                target: "_blank",
                "data-id": `qr-social-${index + 1}`
            })
            .addClass("social-button");

        const $img = jQuery("<img>")
            .attr({
                src: iconSrc,
                alt: "Social Icon",
                decoding: "async"
            });

        $a.append($img);
        $footer.append($a);
    });

    console.log("Rendered social links:", links);
}

// ========== APPLY QR STYLES (jQuery Version) ==========
function applyQRStyles() {
    // Check if QR data exists
    const lastQRData = jQuery(window).data("lastQRData");
    if (!lastQRData) return;

    // Get color and shape values
    const color = jQuery("#hexcolor").val() || "#000000";
    const shape = jQuery(".dynamic-btn-shape.active span").text().toLowerCase() || "square";

    // Update QR code style
    qrCode.update({
        data: lastQRData,
        dotsOptions: {
            color: color,
            type: shape
        },
        backgroundOptions: {
            color: "#ffffff"
        }
    });

    // Handle logo updates
    const logo =
        jQuery(".dynamic-social-logo.active img").attr("src") ||
        jQuery("#dynamic-qr-logo").data("uploadedLogo") ||
        "";

    if (logo) {
        qrCode.update({
            image: logo,
            imageOptions: {
                crossOrigin: "anonymous",
                margin: 5,
                imageSize: 0.2
            }
        });
    }

    // Refresh Step 4 preview
    updateStep4Preview();
}

// ========== FETCH SCAN COUNT (jQuery Version) ==========
function fetchScanCount(postId, targetElementId) {
    const formData = new FormData();
    formData.append("action", "get_qr_scan_count");
    formData.append("post_id", postId);

    jQuery.ajax({
        url: qrAjax.ajax_url,
        method: "POST",
        data: formData,
        processData: false,
        contentType: false,
        xhrFields: {
            withCredentials: true
        },
        success: function (data) {
            if (data.success) {
                jQuery("#scanCountDisplay").text(data.data.qr_scan_count);
                jQuery("#scanDevice").text(data.data.last_device || "-");
                jQuery("#scanTime").text(data.data.last_scan_time || "-");
                jQuery("#scanLocation").text(data.data.last_location || "-");

                // Optional: render full log
                if (Array.isArray(data.data.scan_log)) {
                    const $tbody = jQuery("#scanLogTableBody");
                    if ($tbody.length) {
                        $tbody.empty();
                        jQuery(data.data.scan_log.slice().reverse()).each(function (_, log) {
                            const $tr = jQuery("<tr></tr>").html(`
                                <td>${log.device}</td>
                                <td>${log.time}</td>
                                <td>${log.ip}</td>
                                <td>${log.location || "-"}</td>
                            `);
                            $tbody.append($tr);
                        });
                    }
                }
            }
        }
    });
}


  // ========== PREVIEW QR ==========
function previewQRCode(data) {
    // Store last QR data using jQuery data API
    jQuery(window).data("lastQRData", data);

    // Update the QR code (same logic)
    if (typeof qrCode !== "undefined" && qrCode.update) {
        qrCode.update({ data: data });
    }

    // Update Step 4 Preview
    updateStep4Preview();
}

// ========== UPDATE STEP 4 PREVIEW ==========
function updateStep4Preview(url) {
    const qrData = url || jQuery(window).data("lastQRData");
    if (!qrData) return;

    function renderQR(targetId) {
        const $target = jQuery(`#${targetId}`);
        if (!$target.length) return;

        // Clear target content
        $target.empty();

        // Build new QR code
        const qrCopy = new QRCodeStyling({
            width: 250,
            height: 250,
            data: qrData,
            type: "canvas",
            dotsOptions: {
                color: jQuery("#hexcolor").val() || "#000000",
                type: jQuery(".dynamic-btn-shape.active span").text().toLowerCase() || "square"
            },
            backgroundOptions: {
                color: "#ffffff"
            },
            image: jQuery(".dynamic-social-logo.active img").attr("src") ||
                   jQuery("#dynamic-qr-logo").data("uploadedLogo") || "",
            imageOptions: {
                crossOrigin: "anonymous",
                margin: 5,
                imageSize: 0.2
            }
        });

        // Append QR to target
        qrCopy.append($target[0]);
    }

    // Render to both preview targets
    renderQR("qrFinalPreview");
    renderQR("step3QrInsideStep4");
}
function decodeHtmlEntities(str) {
    let txt = document.createElement("textarea");
    txt.innerHTML = str;
    return txt.value;
}

// ========== STEP NAVIGATION ==========
function goToStep(nextId) {
    const $tabTrigger = jQuery(`[data-bs-target="#${nextId}"]`);

    // Show the Bootstrap tab
    if ($tabTrigger.length) {
        new bootstrap.Tab($tabTrigger[0]).show();
    }

    // Disable other tabs when going back to step 1
    if (nextId === "step1") {
        jQuery(["#step2-tab", "#step3-tab", "#step4-tab"]).each(function(_, id) {
            jQuery(id).addClass("disabled");
        });
    }

    // Handle step 4 QR rendering
    if (nextId === "step4") {
        setTimeout(function() {
            jQuery(["#qrFinalPreview", "#tab1 #qrPreviewContainer"]).each(function(_, sel) {
                const $container = jQuery(sel);
                if ($container.length) {
                    $container.empty();
                    qrCode.append($container[0]); // append() from QRCodeStyling needs a DOM node
                }
            });
        }, 100);
    }
}
