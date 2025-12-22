
                document.addEventListener("DOMContentLoaded", function() {
                    // =====================================================
                    // ========== SOCIAL PREVIEW (LIVE UPDATES) ============
                    // =====================================================

                    // Logo upload
                    const logoInput = document.querySelector("#socialMediaForm .header_logo_wrapper input[type='file']");
                    const logoPreview = document.querySelector(".preview .logo-img");
                    logoInput?.addEventListener("change", e => {
                        const file = e.target.files[0];
                        if (file) logoPreview.src = URL.createObjectURL(file);
                    });

                    // Title + color
                    const titleInput = document.querySelector("#socialMediaForm .header_title_wrapper input");
                    const titlePreview = document.querySelector(".preview .header h5");
                    titleInput?.addEventListener("input", function() {
                        titlePreview.textContent = this.value || "Default Title";
                    });
                    const titleColorInput = document.querySelector("#socialMediaForm .header_title_color_wrapper input[type='color']");
                    titleColorInput?.addEventListener("input", function() {
                        titlePreview.style.color = this.value;
                    });

                    // Body image
                    const bodyImgInput = document.querySelector("#socialMediaForm .body_image_wrapper input[type='file']");
                    const bodyImgPreview = document.querySelector(".preview .cube-image");
                    bodyImgInput?.addEventListener("change", e => {
                        const file = e.target.files[0];
                        if (file) bodyImgPreview.src = URL.createObjectURL(file);
                    });

                    // Message & subtext
                    const msgInput = document.querySelector("#socialMediaForm .body_message_wrapper input");
                    const subtextInput = document.querySelector("#socialMediaForm .body_subtext_wrapper input");
                    const msgPreview = document.querySelector(".preview .content h5");
                    const subPreview = document.querySelector(".preview .content p");
                    msgInput?.addEventListener("input", function() {
                        msgPreview.textContent = this.value || "Default Message";
                    });
                    subtextInput?.addEventListener("input", function() {
                        subPreview.textContent = this.value || "Default Subtext";
                    });

                    // Background color
                    const bgColorInput = document.querySelector("#socialMediaForm .body_bgcolor_wrapper input[type='color']");
                    const cardPreview = document.querySelector(".preview.card .body-content-wrapper");
                    bgColorInput?.addEventListener("input", function() {
                        cardPreview.style.backgroundColor = this.value;
                    });

                    // Social links (add/remove buttons in footer)
                    const socialFields = document.querySelectorAll(".qr-social-feild");
                    const footerPreview = document.querySelector(".preview .footer");

                    socialFields.forEach(field => {
                        const input = field.querySelector("input[type='url']");
                        const closeBtn = field.querySelector(".socail-close-icon span");
                        const icon = field.querySelector("img"); // platform icon
                        const iconSrc = icon ? icon.src : "";

                        // Add or update button
                        input?.addEventListener("input", function() {
                            let existingBtn = footerPreview.querySelector(`[data-id="${field.id}"]`);
                            if (this.value.trim()) {
                                if (!existingBtn) {
                                    let newBtn = document.createElement("a");
                                    newBtn.href = this.value.trim();
                                    newBtn.target = "_blank";
                                    newBtn.classList.add("social-button");
                                    newBtn.dataset.id = field.id;
                                    newBtn.innerHTML = `<img src="${iconSrc}" alt="Social Icon"/>`;
                                    footerPreview.appendChild(newBtn);
                                } else {
                                    existingBtn.href = this.value.trim();
                                }
                            } else if (existingBtn) {
                                existingBtn.remove();
                            }
                        });

                        // Close button clears field + preview
                        closeBtn?.addEventListener("click", function() {
                            input.value = "";
                            let existingBtn = footerPreview.querySelector(`[data-id="${field.id}"]`);
                            if (existingBtn) existingBtn.remove();
                        });
                    });
                    // ========== GLOBAL STATE ==========
                    window.lastQRData = "";
                    window.createdQRPostId = 0;
                    let qrCode;

                    // ========== TAB SWITCHING ==========
                    document.querySelectorAll(".social_tab").forEach(tab => {
                        tab.addEventListener("click", function() {
                            const targetId = this.dataset.tab;
                            document.querySelectorAll(".social_tab").forEach(t => t.classList.remove("active"));
                            document.querySelectorAll(".qr-tab-pane").forEach(p => p.classList.remove("active"));
                            this.classList.add("active");
                            document.getElementById(targetId)?.classList.add("active");
                        });
                    });
                    document.getElementById("goToPreviewTab")?.addEventListener("click", () => {
                        document.querySelector('[data-tab="tab2"]')?.click();
                    });

                    // ========== INIT QR CODE ==========
                    function initQRCode(containerId) {
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
                        const container = document.getElementById(containerId);
                        if (container) {
                            container.innerHTML = "";
                            qrCode.append(container);
                        }
                    }
                    initQRCode("qrPreviewContainer");

                    // ========== PREVIEW QR ==========
                    function previewQRCode(data) {
                        window.lastQRData = data;
                        qrCode.update({
                            data: data
                        });
                        updateStep4Preview();
                    }

                    function updateStep4Preview(url) {
                        const qrData = url || window.lastQRData;
                        if (!qrData) return;

                        function renderQR(targetId) {
                            const target = document.getElementById(targetId);
                            if (!target) return;
                            target.innerHTML = "";
                            const qrCopy = new QRCodeStyling({
                                width: 250,
                                height: 250,
                                data: qrData,
                                type: "canvas",
                                dotsOptions: {
                                    color: document.querySelector("#hexcolor")?.value || "#000000",
                                    type: document.querySelector(".dynamic-btn-shape.active span")?.innerText.toLowerCase() || "square"
                                },
                                backgroundOptions: {
                                    color: "#ffffff"
                                },
                                image: document.querySelector(".dynamic-social-logo.active img")?.src ||
                                    document.getElementById("dynamic-qr-logo")?.dataset.uploadedLogo || "",
                                imageOptions: {
                                    crossOrigin: "anonymous",
                                    margin: 5,
                                    imageSize: 0.2
                                }
                            });
                            qrCopy.append(target);
                        }

                        renderQR("qrFinalPreview");
                        renderQR("step3QrInsideStep4");
                    }

                    // ========== FETCH SCAN COUNT ==========
                    function fetchScanCount(postId, targetElementId) {
                        const formData = new FormData();
                        formData.append("action", "get_qr_scan_count");
                        formData.append("post_id", postId);

                        fetch(qrAjax.ajax_url, {
                                method: "POST",
                                body: formData,
                                credentials: "same-origin"
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById("scanCountDisplay").innerText = data.data.qr_scan_count;
                                    document.getElementById("scanDevice").innerText = data.data.last_device || "-";
                                    document.getElementById("scanTime").innerText = data.data.last_scan_time || "-";
                                    document.getElementById("scanLocation").innerText = data.data.last_location || "-";

                                    // Optional: render full log
                                    if (Array.isArray(data.data.scan_log)) {
                                        const tbody = document.getElementById("scanLogTableBody");
                                        if (tbody) {
                                            tbody.innerHTML = "";
                                            data.data.scan_log.slice().reverse().forEach(log => {
                                                const tr = document.createElement("tr");
                                                tr.innerHTML = `
                            <td>${log.device}</td>
                            <td>${log.time}</td>
                            <td>${log.ip}</td>
                            <td>${log.location || "-"}</td>
                        `;
                                                tbody.appendChild(tr);
                                            });
                                        }
                                    }
                                }
                            });
                    }

                    // auto refresh every 10s
                    setInterval(() => {
                        if (window.createdQRPostId) fetchScanCount(window.createdQRPostId);
                    }, 10000);


                    function startScanCountAutoRefresh() {
                        const qrPostId = window.createdQRPostId || qr_ajax.qrPostId || 0;

                        if (!qrPostId) return;

                        // Run once immediately
                        fetchScanCount(qrPostId, "scanCountDisplay");

                        // Refresh every 10 seconds
                        setInterval(() => {
                            fetchScanCount(qrPostId, "scanCountDisplay");
                        }, 10000);
                    }

                    document.addEventListener("DOMContentLoaded", startScanCountAutoRefresh);



                    // ========== APPLY STYLES ==========
                    function applyQRStyles() {
                        if (!window.lastQRData) return;
                        const color = document.querySelector("#hexcolor")?.value || "#000000";
                        const shape = document.querySelector(".dynamic-btn-shape.active span")?.innerText.toLowerCase() || "square";

                        qrCode.update({
                            data: window.lastQRData,
                            dotsOptions: {
                                color,
                                type: shape
                            },
                            backgroundOptions: {
                                color: "#ffffff"
                            }
                        });

                        let logo = document.querySelector(".dynamic-social-logo.active img")?.src ||
                            document.getElementById("dynamic-qr-logo")?.dataset.uploadedLogo || "";
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
                        updateStep4Preview();
                    }

                    // Reset button
                    document.querySelector(".reset-button .btn-upload")?.addEventListener("click", () => {
                        document.querySelectorAll(".dynamic-social-logo").forEach(b => b.classList.remove("active", "active-logo"));
                        const uploadInput = document.getElementById("dynamic-qr-logo");
                        if (uploadInput) {
                            uploadInput.value = "";
                            uploadInput.dataset.uploadedLogo = "";
                        }
                        qrCode.update({
                            image: ""
                        });
                        updateStep4Preview();
                    });

                    // Bind style buttons
                    document.querySelectorAll(".dynamic-btn-shape").forEach(btn => {
                        btn.addEventListener("click", function() {
                            document.querySelectorAll(".dynamic-btn-shape").forEach(b => b.classList.remove("active"));
                            this.classList.add("active");
                            applyQRStyles();
                        });
                    });
                    document.querySelectorAll(".dynamic-social-logo").forEach(btn => {
                        btn.addEventListener("click", function() {
                            document.querySelectorAll(".dynamic-social-logo").forEach(b => b.classList.remove("active"));
                            this.classList.add("active");
                            const uploadInput = document.getElementById("dynamic-qr-logo");
                            if (uploadInput) {
                                uploadInput.value = "";
                                uploadInput.dataset.uploadedLogo = "";
                            }
                            applyQRStyles();
                        });
                    });

                    // Upload logo
                    document.getElementById("dynamic-qr-logo")?.addEventListener("change", function() {
                        if (this.files && this.files[0]) {
                            const reader = new FileReader();
                            reader.onload = e => {
                                this.dataset.uploadedLogo = e.target.result;
                                document.querySelectorAll(".dynamic-social-logo").forEach(b => b.classList.remove("active"));
                                applyQRStyles();
                            };
                            reader.readAsDataURL(this.files[0]);
                        }
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

                    // ========== STEP NAVIGATION ==========
                    function goToStep(nextId) {
                        const tabTrigger = document.querySelector(`[data-bs-target="#${nextId}"]`);
                        if (tabTrigger) new bootstrap.Tab(tabTrigger).show();
                        if (nextId === "step1")["step2-tab", "step3-tab", "step4-tab"].forEach(id => document.getElementById(id)?.classList.add("disabled"));
                        if (nextId === "step4") {
                            setTimeout(() => {
                                ["qrFinalPreview", "tab1 #qrPreviewContainer"].forEach(sel => {
                                    const container = document.querySelector(sel);
                                    if (container) {
                                        container.innerHTML = "";
                                        qrCode.append(container);
                                    }
                                });
                            }, 100);
                        }
                    }

                    document.querySelectorAll(".finishQRBtn").forEach(btn => {
                        btn.addEventListener("click", async e => {
                            e.preventDefault();

                            const qrName = document.querySelector("#post_title_wrapper input.name_qr_wrapper")?.value.trim();
                            if (!qrName) return alert("Please enter a name for your QR code");

                            if (window.createdQRPostId) return alert("QR Code already created!");

                            // figure out final type + value
                            let qrType = "";
                            let qrValue = "";

                            // WEB
                            if (urlInput?.value.trim()) {
                                qrType = "web";
                                qrValue = urlInput.value.trim();

                                // PDF (prefer uploaded absolute URL used in preview; else typed URL)
                            } else if ((pdfFileInput?.files.length > 0 && window.lastQRData) || pdfUrlInput?.value.trim()) {
                                qrType = "pdf";
                                const typedUrl = (pdfUrlInput?.value || "").trim();
                                qrValue = (window.lastQRData && /\.pdf(\?|$)/i.test(window.lastQRData)) ? window.lastQRData : typedUrl;

                                // EMAIL (build full mailto:)
                            } else if (emailInput?.value.trim()) {
                                qrType = "email";
                                const to = encodeURIComponent(emailInput.value.trim());
                                const subject = document.querySelector("#emailForm input[placeholder='Subject']")?.value || "";
                                const body = document.querySelector("#emailForm textarea")?.value || "";
                                const params = [];
                                if (subject) params.push("subject=" + encodeURIComponent(subject));
                                if (body) params.push("body=" + encodeURIComponent(body));
                                qrValue = "mailto:" + to + (params.length ? "?" + params.join("&") : "");

                                // SMS (build full sms:)
                            } else if (phoneInput?.value.trim()) {
                                qrType = "sms";
                                const num = encodeURIComponent(phoneInput.value.trim());
                                const text = document.querySelector("#smsForm textarea")?.value || "";
                                qrValue = "sms:" + num + (text ? "?body=" + encodeURIComponent(text) : "");

                                // SOCIAL (your landing/preview URL)
                            } else if (socialForm && socialForm.querySelectorAll("input[type='url']").length > 0) {
                                qrType = "social";
                                qrValue = window.lastQRData || "";
                            }

                            if (!qrType || !qrValue) return alert("Please provide a valid destination.");

                            const formData = new FormData();
                            formData.append("action", "create_qr_code_post");
                            formData.append("qr_name", qrName);
                            formData.append("qr_data", window.lastQRData || "");
                            formData.append("user_id", qrAjax.user_id || 0);
                            formData.append("user_name", qrAjax.user_name || "");
                            formData.append("qr_type", qrType);
                            formData.append("qr_value", qrValue);

                            try {
                                const res = await fetch(qrAjax.ajax_url, {
                                    method: "POST",
                                    body: formData,
                                    credentials: "same-origin"
                                });
                                const data = await res.json();
                                if (!data.success) return alert(data.message || "Failed to save QR Code");

                                window.createdQRPostId = data.data.post_id;

                                // point the visible QR to the tracking URL
                                window.lastQRData = data.data.qr_url;
                                qrCode.update({
                                    data: data.data.qr_url
                                });
                                updateStep4Preview(data.data.qr_url);

                                // save styled PNG
                                qrCode.getRawData("png").then(blob => {
                                    const reader = new FileReader();
                                    reader.onload = () => {
                                        const styledData = reader.result;
                                        const imgForm = new FormData();
                                        imgForm.append("action", "save_styled_qr_image");
                                        imgForm.append("post_id", data.data.post_id);
                                        imgForm.append("qr_image", styledData);
                                        fetch(qrAjax.ajax_url, {
                                                method: "POST",
                                                body: imgForm,
                                                credentials: "same-origin"
                                            })
                                            .then(r => r.json())
                                            .then(imgRes => {
                                                if (!imgRes.success) console.error("❌ Failed saving styled QR:", imgRes.message);
                                            });
                                    };
                                    reader.readAsDataURL(blob);
                                });

                            } catch (err) {
                                console.error(err);
                                alert("Error saving QR Code");
                            }
                        });
                    });

                    // ========== FORMS ==========
                    const urlInput = document.querySelector("#webForm input[type='url']");
                    const pdfUrlInput = document.querySelector("#pdfForm input[type='url']");
                    const pdfFileInput = document.querySelector("#pdfForm input[type='file']");
                    const emailInput = document.querySelector("#emailForm input[type='email']");
                    const phoneInput = document.querySelector("#smsForm input[type='number']");
                    const socialForm = document.querySelector("#socialMediaForm");

                    // Web
                  if (urlInput) {
                        const btn = document.querySelector("#webForm button.btn-primary");
                        console.log(btn);
                        // Remove disabled logic completely
                        urlInput.addEventListener("input", () => {
                            // Optional: You can add validation logic here if needed
                        }); 

                        document.addEventListener("click", function(e) {
                            if (e.target.matches("#webForm button.btn-primary")) {
                                e.preventDefault();

                                const urlInput = document.querySelector("#webForm input[type='url']");
                                if (!urlInput) return;

                                const url = urlInput.value.trim();
                                if (!/^https?:\/\//i.test(url)) {
                                    alert("Enter a valid URL starting with http:// or https://");
                                    return;
                                }

                                previewQRCode(url);
                                goToStep("step3");
                            }
                        });

                        }
                    // PDF
                    if (pdfUrlInput || pdfFileInput) {
                        document.querySelector("#pdfForm button.btn-primary").addEventListener("click", e => {
                            e.preventDefault();
                            if (pdfFileInput?.files.length > 0) {
                                const formData = new FormData();
                                formData.append("action", "upload_pdf_file");
                                formData.append("pdf_file", pdfFileInput.files[0]);
                                fetch(qrAjax.ajax_url, {
                                        method: "POST",
                                        body: formData
                                    })
                                    .then(r => r.json()).then(data => {
                                        if (data.success) {
                                            previewQRCode(data.url);
                                            goToStep("step3");
                                        } else alert(data.message);
                                    });
                            } else if (pdfUrlInput?.value.trim()) {
                                if (!/^https?:\/\//i.test(pdfUrlInput.value) || !pdfUrlInput.value.toLowerCase().endsWith(".pdf")) return alert("Enter valid PDF URL");
                                previewQRCode(pdfUrlInput.value.trim());
                                goToStep("step3");
                            } else alert("Upload a PDF or provide a URL");
                        });
                    }
                    // Email
                    if (emailInput) {
                        const subjectInput = document.querySelector("#emailForm input[placeholder='Subject']");
                        const bodyInput = document.querySelector("#emailForm textarea");
                        const btn = document.querySelector("#emailForm button.btn-primary");
                        btn.disabled = true;
                        emailInput.addEventListener("input", () => {
                            btn.disabled = !emailInput.value.trim();
                        });
                        btn.addEventListener("click", e => {
                            e.preventDefault();
                            let mail = `mailto:${encodeURIComponent(emailInput.value.trim())}`;
                            let params = [];
                            if (subjectInput?.value) params.push(`subject=${encodeURIComponent(subjectInput.value)}`);
                            if (bodyInput?.value) params.push(`body=${encodeURIComponent(bodyInput.value)}`);
                            if (params.length) mail += "?" + params.join("&");
                            previewQRCode(mail);
                            goToStep("step3");
                        });
                    }
                    // SMS
                    if (phoneInput) {
                        const msgInput = document.querySelector("#smsForm textarea");
                        const btn = document.querySelector("#smsForm button.btn-primary");
                        btn.disabled = true;
                        phoneInput.addEventListener("input", () => {
                            btn.disabled = !phoneInput.value.trim();
                        });
                        btn.addEventListener("click", e => {
                            e.preventDefault();
                            let sms = `sms:${encodeURIComponent(phoneInput.value.trim())}`;
                            if (msgInput?.value) sms += `?body=${encodeURIComponent(msgInput.value)}`;
                            previewQRCode(sms);
                            goToStep("step3");
                        });
                    }
                    // Social
                    if (socialForm) {
                        const btn = socialForm.querySelector("button.btn-primary");
                        btn.addEventListener("click", async e => {
                            e.preventDefault();

                            const title = socialForm.querySelector(".header_title_wrapper input")?.value.trim() || "Title";
                            const message = socialForm.querySelector(".body_message_wrapper input")?.value.trim() || "Message";
                            const subtext = socialForm.querySelector(".body_subtext_wrapper input")?.value.trim() || "Subtext";
                            const logoInput = socialForm.querySelector(".header_logo_wrapper input[type='file']");
                            const bodyImageInput = socialForm.querySelector(".body_image_wrapper input[type='file']");

                            // Collect all URLs and platform names
                            const inputs = socialForm.querySelectorAll("input[type='url']");
                            const links = [];
                            inputs.forEach(inp => {
                                const platform = inp.placeholder.replace(" URL", "").trim();
                                if (inp.value.trim()) links.push({
                                    platform,
                                    url: inp.value.trim()
                                });
                            });

                            if (!links.length) return alert("Provide at least one social link");

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

                            // Send to server
                            const formData = new FormData();
                            formData.append("action", "create_social_preview");
                            formData.append("data", JSON.stringify(socialData));
                            if (logoInput?.files.length > 0) formData.append("logo", logoInput.files[0]);
                            if (bodyImageInput?.files.length > 0) formData.append("bodyImage", bodyImageInput.files[0]);

                            try {
                                const res = await fetch(qrAjax.ajax_url, {
                                    method: "POST",
                                    body: formData
                                });
                                const data = await res.json();
                                if (data.success && data.data?.url) {
                                    // Update Step 4 preview with all links
                                    updateStep4SocialPreview({
                                        ...socialData,
                                        logo: data.data.logo_url || socialData.logo,
                                        bodyImage: data.data.bodyImageUrl || socialData.bodyImage
                                    });
                                    goToStep("step3");
                                    previewQRCode(data.data.url);
                                    updateStep4Preview(data.data.url);
                                } else {
                                    alert(data.message || "Error creating social preview");
                                }
                            } catch (err) {
                                console.error(err);
                                alert("Error creating social preview");
                            }
                        });
                    }

                    // =======================
                    // Get all social links
                    // =======================
                    function getSocialLinks() {
                        const links = [];
                        const inputs = document.querySelectorAll("#socialMediaForm .qr-social-content_wrapper input[type='url']");

                        inputs.forEach((input, index) => {
                            const url = input.value.trim();
                            if (!url) return; // skip empty

                            // Get platform name from img alt or placeholder fallback
                            const platformImg = input.closest(".qr-social-feild")?.querySelector("img");
                            const platform = (platformImg?.alt || input.placeholder?.replace(" URL", "") || "Unknown").trim();

                            links.push({
                                platform,
                                url
                            });
                        });

                        return links;
                    }
                    // ========== SOCIAL PREVIEW UPDATE ==========
                    // ========== SOCIAL PREVIEW UPDATE ==========
                    function updateStep4SocialPreview(data) {
                        const container = document.getElementById("socialPreviewContainer");
                        if (!container) return;

                        // === Header & Body ===
                        const titleEl = container.querySelector(".social-title") || container.querySelector(".header h5");
                        if (titleEl) {
                            titleEl.textContent = data.title || "Social Media";
                            titleEl.style.color = data.titleColor || "#000000";
                        }

                        const messageEl = container.querySelector(".social-message") || container.querySelector(".content h5");
                        if (messageEl) messageEl.textContent = data.message || "Social Media";

                        const subtextEl = container.querySelector(".social-subtext") || container.querySelector(".content p");
                        if (subtextEl) subtextEl.textContent = data.subtext || "Social Media";

                        // === Images & Background ===
                        if (data.logo) {
                            const logoImg = container.querySelector(".logo-img");
                            if (logoImg) logoImg.src = data.logo;
                        }

                        if (data.bodyImage) {
                            const bodyImg = container.querySelector(".cube-image");
                            if (bodyImg) bodyImg.src = data.bodyImage;
                        }

                        if (data.bgColor) {
                            const bg = container.querySelector(".body-content-wrapper");
                            if (bg) bg.style.backgroundColor = data.bgColor;
                        }

                        // === Footer (Social Links) ===
                        let footer = container.querySelector(".footer");
                        if (!footer) {
                            footer = document.createElement("div");
                            footer.classList.add("footer");
                            container.appendChild(footer);
                        }

                        footer.innerHTML = ""; // clear old links

                        // Use provided links or fallback
                        const links = data.links && data.links.length ? data.links : getSocialLinks();

                        links.forEach((link, index) => {
                            const iconSrc =
                                (window.platformIcons && window.platformIcons[link.platform]) ||
                                "https://wordpress-1504858-5765338.cloudwaysapps.com/wp-content/uploads/2025/08/default-icon.png";

                            const a = document.createElement("a");
                            a.href = link.url || "#";
                            a.target = "_blank";
                            a.classList.add("social-button");
                            a.dataset.id = `qr-social-${index + 1}`;

                            const img = document.createElement("img");
                            img.src = iconSrc;
                            img.alt = "Social Icon";
                            img.decoding = "async";

                            a.appendChild(img);
                            footer.appendChild(a);
                        });

                        console.log("Rendered social links:", links);
                    }



                    // Update Step 4 when tab clicked
                    document.getElementById("step4-tab")?.addEventListener("click", () => {
                        updateStep4SocialPreview();
                    });
                });

                // Global icons
                window.platformIcons = {
                    Instagram: "https://wordpress-1504858-5765338.cloudwaysapps.com/wp-content/uploads/2025/08/Instagram.png",
                    Facebook: "https://wordpress-1504858-5765338.cloudwaysapps.com/wp-content/uploads/2025/08/Facebook.png",
                    Twitter: "https://wordpress-1504858-5765338.cloudwaysapps.com/wp-content/uploads/2025/08/Twitter.png",
                    Youtube: "https://wordpress-1504858-5765338.cloudwaysapps.com/wp-content/uploads/2025/08/Youtube.png"
                };

                document.addEventListener("DOMContentLoaded", function() {
                    const tabs = document.querySelectorAll(".dashboard_inner_wrap ul li");

                    tabs.forEach(tab => {
                        tab.addEventListener("click", function() {
                            const targetId = this.getAttribute("data-tab");

                            if (!this.classList.contains("qr-active-social")) {
                                this.classList.add("qr-active-social");
                            }

                            const targetField = document.getElementById(targetId);
                            if (targetField && !targetField.classList.contains("qr-active-social")) {
                                targetField.classList.add("qr-active-social");
                            }
                        });
                    });

                    document.querySelectorAll(".socail-close-icon").forEach(closeBtn => {
                        closeBtn.addEventListener("click", function(e) {
                            e.stopPropagation();

                            const parentField = this.closest(".qr-social-feild");
                            if (parentField) {
                                parentField.classList.remove("qr-active-social");

                                const fieldId = parentField.getAttribute("id");
                                const matchingTab = document.querySelector(`.dashboard_inner_wrap ul li[data-tab="${fieldId}"]`);
                                if (matchingTab) {
                                    matchingTab.classList.remove("qr-active-social");
                                }
                            }
                        });
                    });
                });

                document.addEventListener("DOMContentLoaded", function() {
                    const tabs = document.querySelectorAll("#createQRtabs .nav-link");
                    const step1Cards = document.querySelectorAll(".step-1 .card a");

                    // Disable all tabs except Step 1
                    tabs.forEach(tab => {
                        if (tab.id !== "step1-tab") {
                            tab.classList.add("disabled");
                        }
                    });

                    // Prevent disabled tabs from being clicked
                    tabs.forEach(tab => {
                        tab.addEventListener("click", function(e) {
                            if (tab.classList.contains("disabled")) {
                                e.preventDefault();
                            }
                        });
                    });

                    // Step 1 -> select type
                    step1Cards.forEach(card => {
                        card.addEventListener("click", function() {
                            const selectedType = this.closest(".card").classList[2]; // web/pdf/mail/sms/social-media

                            // Hide all Step 2 sections
                            document.querySelectorAll("#step2 .inner-section-setup").forEach(sec => {
                                sec.style.display = "none";
                            });

                            // Show selected section
                            const targetSection = document.querySelector("#step2 .inner-section-setup." + selectedType);
                            if (targetSection) targetSection.style.display = "block";

                            // Enable Step 2 tab & switch
                            const step2Tab = document.getElementById("step2-tab");
                            step2Tab.classList.remove("disabled");
                            new bootstrap.Tab(step2Tab).show();

                            // ✅ Preview logic based on click
                            if (selectedType === "social-media") {
                                document.querySelector(".preview-qr").style.display = "none";
                                document.querySelector(".social-media-preview").style.display = "block";
                            } else {
                                document.querySelector(".preview-qr").style.display = "block";
                                document.querySelector(".social-media-preview").style.display = "none";
                            }
                        });
                    });


                    // Step 2 forms -> on submit enable Step 3
                    document.querySelectorAll("#step2 form").forEach(form => {
                        form.addEventListener("submit", function(e) {
                            e.preventDefault(); // prevent real submit for demo

                            // ✅ here you can add validation / ajax if needed

                            // Enable Step 3
                            const step3Tab = document.getElementById("step3-tab");
                            step3Tab.classList.remove("disabled");
                            new bootstrap.Tab(step3Tab).show();
                        });
                    });

                    // Step 3 forms -> on submit enable Step 4
                    document.querySelectorAll("#step3 form").forEach(form => {
                        form.addEventListener("submit", function(e) {
                            e.preventDefault();

                            // ✅ add validation / ajax here

                            // Enable Step 4
                            const step4Tab = document.getElementById("step4-tab");
                            step4Tab.classList.remove("disabled");
                            new bootstrap.Tab(step4Tab).show();
                        });
                    });
                });

                // ✅ Step 4 direct from preview btn
                document.querySelectorAll(".preview-qr-next button.btn").forEach(btn => {
                    btn.addEventListener("click", function(e) {
                        e.preventDefault();

                        const step4Tab = document.getElementById("step4-tab");
                        step4Tab.classList.remove("disabled");
                        new bootstrap.Tab(step4Tab).show();

                        // Keep Step 3 enabled for back navigation
                        const step3Tab = document.getElementById("step3-tab");
                        step3Tab.classList.remove("disabled");
                    });
                });


                function replaceDivsWithForms() {
                    const divs = document.querySelectorAll(".form-wrapper");
                    if (divs.length > 0) {
                        divs.forEach(div => {
                            const form = document.createElement("form");
                            form.className = div.className; // keep same classes
                            form.id = div.id || ""; // copy id if exists
                            form.innerHTML = div.innerHTML; // copy inner content
                            div.replaceWith(form); // replace in DOM
                        });

                        clearInterval(checkInterval); // stop after replacement
                        console.log("✅ All .form-wrapper divs replaced with forms");
                    }
                }

                // keep checking until elements exist
                const checkInterval = setInterval(replaceDivsWithForms, 200);


              document.querySelectorAll(".um-account-tab-create_qr_code button").forEach(btn => {
                btn.addEventListener("click", function (e) {
                    e.preventDefault(); // stop form submission or page reload
                    console.log("QR Code tab button clicked 🚀");
                });
            });
