jQuery(document).ready(function ($) {
    let singleDownloadItem = null;


       // Handle pagination click (for both Grid & List)
    $(document).on('click', '.ajax-pagination a', function (e) {
        e.preventDefault();
         

        const href = $(this).attr('href');
        const match = href.match(/qr_page=(\d+)/);
        const page = match ? match[1] : 1;

        // Detect which tab is active
        const activeLayout = $('.nav-link.active').attr('id') === 'list-tab' ? 'list' : 'grid';

        $.ajax({
            url: qr_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_qr_pagination',
                page: page,
                layout: activeLayout
            },
            beforeSend: function () {
                if (activeLayout === 'grid') {
                    $('.gird-layout').html('<p class="loading">Loading...</p>');
                } else {
                    $('.list-layout tbody').html('<tr><td colspan="7" class="text-center">Loading...</td></tr>');
                }
            },
            success: function (response) {
                if (response.success) {
                    
                    if (activeLayout === 'grid') {
                        $('.gird-layout').replaceWith(response.data.html);
                        // jQuery(document).find('.qr-format-select, .qr-format-download, .qr-format-cancel').css('display', 'none');
                    } else {
                        $('.list-layout').replaceWith(response.data.html);
                        // jQuery(document).find('.qr-format-select, .qr-format-download, .qr-format-cancel').css('display', 'none');
                    }
                    $('html, body').animate({
                        scrollTop: $('.toggle-layouts').offset().top - 50
                    }, 300);
                } else {
                    alert('Error loading QR codes.');
                }
            },
            error: function () {
                alert('AJAX request failed.');
            }
        });
    });


    let currentRow = null;
// Update count on tab switch
$('.qr_layout_wrapper button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
    updateSelectedCount();
});
    // Helper: get currently active tab rows
    function getActiveRows() {
        if ($('#grid').hasClass('show')) {
            return $('#grid .grid_layout_wrapper');
        } else {
            return $('#list tbody tr');
        }
    }

    function updateSelectedCount() {
        let $activeTab = $('.tab-pane.active'); // only the currently active tab
        let count = $activeTab.find('input[type="checkbox"]:checked').filter(function() {
            // exclude the first "select all" checkbox if present in header
            return !$(this).closest('thead').length;
        }).length;

        $('.selected-items .count').text(count);
    }


    function getSelectedIds() {
        let ids = [];
        getActiveRows().each(function () {
            let row = $(this);
            if (row.find('input[type="checkbox"]').is(':checked')) {
                ids.push(row.data('id'));
            }
        });
        return ids;
    }
    async function generateQRBlob(item, format) {

        let qrHD = new QRCodeStyling({
            width: 2000,
            height: 2000,
            data: item.qr,
            type: "canvas",
            dotsOptions: { color: item.color, type: item.shape },
            backgroundOptions: { color: "#ffffff" },
            image: item.logo,
            imageOptions: { crossOrigin: "anonymous", margin: 10, imageSize: 0.25 }
        });

        if (format === "pdf") {
            return qrHD.getRawData("png", { width: 2000, height: 2000 })
                .then(blob => new Promise(resolve => {
                    const reader = new FileReader();
                    reader.onload = function () {
                        const { jsPDF } = window.jspdf;
                        const pdf = new jsPDF({
                            orientation: "portrait",
                            unit: "pt",
                            format: [2000, 2000]
                        });
                        pdf.addImage(reader.result, "PNG", 0, 0, 2000, 2000);
                        resolve(pdf.output("blob"));
                    };
                    reader.readAsDataURL(blob);
                }));
        }

        if (format === "eps") {
            return qrHD.getRawData("svg").then(svgBlob =>
                new Promise(resolve => {
                    const reader = new FileReader();
                    reader.onload = function () {
                        let epsData =
                            "%!PS-Adobe-3.0 EPSF-3.0\n%%BoundingBox: 0 0 2000 2000\n" +
                            reader.result;
                        resolve(new Blob([epsData], { type: "application/postscript" }));
                    };
                    reader.readAsText(svgBlob);
                })
            );
        }

        return qrHD.getRawData(format === "svg" ? "svg" : "png", {
            width: 2000,
            height: 2000
        });
    }

    /** ------------------------
     *  Bulk Select / Deselect
     * ------------------------ */
    $('.select_all_wrap').on('click', function () {
        getActiveRows().find('input[type="checkbox"]').prop('checked', true);
        updateSelectedCount();
    });

    $('.deselect_all_wrap').on('click', function () {
        getActiveRows().find('input[type="checkbox"]').prop('checked', false);
        updateSelectedCount();
    });

    /** ------------------------
     *  Delete Selected
     * ------------------------ */
    $('.delete_all_wrap').on('click', function () {
        let ids = getSelectedIds();
        if (ids.length === 0) return alert('Select QR codes first');
        if (!confirm('Delete selected QR codes?')) return;

        $.post(qr_ajax.ajax_url, {
            action: 'delete_qr_codes',
            qr_ids: ids,
            _wpnonce: qr_ajax.nonce
        }, function (res) {
            if (res.success) location.reload();
        });
    });

    /** ------------------------
     *  Individual Delete
     * ------------------------ */
    $(document).on('click', '.grid_layout_wrapper .delete, tbody tr .table-action-icon.delete', function () {
        let $row = $(this).closest('.grid_layout_wrapper, tr');
        let postId = $row.data('id');
        if (!confirm('Delete this QR code?')) return;

        $.post(qr_ajax.ajax_url, {
            action: 'delete_qr_codes',
            qr_ids: [postId],
            _wpnonce: qr_ajax.nonce
        }, function (res) {
            if (res.success) location.reload();
        });
    });

    /** ------------------------
     *  Export Selected
     * ------------------------ */
    $('.export_all_btn').on('click', function () {
        let ids = getSelectedIds();
        if (ids.length === 0) return alert('Select QR codes first');

        $.post(qr_ajax.ajax_url, {
            action: 'export_qr_codes',
            qr_ids: ids,
            _wpnonce: qr_ajax.nonce
        }, function (res) {
            if (res.success && res.data.csv) {
                let blob = new Blob([res.data.csv], { type: 'text/csv' }); 
                let url = URL.createObjectURL(blob);
                let a = document.createElement('a');
                a.href = url;
                a.download = 'qr-codes.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            } else {
                alert('Failed to export QR codes');
            }
        });
    });

    /** ------------------------
     *  Bulk Download
     * ------------------------ */
       $('.all_qr_download_btn').on('click', function() {
        let selected = getSelectedQRData();
        if (!selected.length) return alert('Select QR codes first');

        $('#qr-count').text(selected.length);
        $('#qrExportModal').fadeIn();
    });


   $('#qr-export-cancel').on('click', function () {
        singleDownloadItem = null;
        $('#qrExportModal').fadeOut();
    });


    // $('#qr-export-confirm').on('click', function() {
    //     let selectedImages = getSelectedQRImages();
    //     if (!selectedImages.length) return alert('Select QR codes first');

    //     let format = $('#qr-file-type').val(); // png, jpeg, svg

    //     // Prepare a form to POST the images & format
    //     let form = $('<form method="POST" action="' + qr_ajax.ajax_url + '"></form>');
    //     form.append('<input type="hidden" name="action" value="download_qr_codes">');
    //     form.append('<input type="hidden" name="_wpnonce" value="' + qr_ajax.nonce + '">');
    //     form.append('<input type="hidden" name="format" value="' + format + '">');

    //     selectedImages.forEach((img, i) => {
    //         form.append('<input type="hidden" name="images[' + i + '][id]" value="' + img.id + '">');
    //         form.append('<input type="hidden" name="images[' + i + '][url]" value="' + img.url + '">');
    //     });

    //     $('body').append(form);
    //     form.submit();
    //     form.remove();
    //     $('#qrExportModal').fadeOut();
    // });
    function cleanFileName(name) {
        return name
            .replace(/[\/\\:*?"<>|]/g, "_") // remove invalid filename characters
            .replace(/\s+/g, "_")           // replace spaces with underscore
            .trim();
    }

    $('#qr-export-confirm').on('click', async function () {

    let format = $('#qr-file-type').val();

    // ----------------------------------
    // SINGLE DOWNLOAD (FROM CARD)
    // ----------------------------------
    if (singleDownloadItem) {

        let item = singleDownloadItem;
        let blob = await generateQRBlob(item, format);

        let ext = format === "jpeg" ? "jpg" : format;
        let safeName = cleanFileName(item.title);

        let a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = `${safeName}.${ext}`;
        document.body.appendChild(a);
        a.click();
        a.remove();

        singleDownloadItem = null;
        $('#qrExportModal').fadeOut();
        return;
    }

    // ----------------------------------
    // BULK DOWNLOAD
    // ----------------------------------
    let selected = getSelectedQRData();
    if (!selected.length) return alert("Select QR codes first");

    if (selected.length === 1) {
        let item = selected[0];
        let blob = await generateQRBlob(item, format);

        let ext = format === "jpeg" ? "jpg" : format;
        let safeName = cleanFileName(item.title);

        let a = document.createElement("a");
        a.href = URL.createObjectURL(blob);
        a.download = `${safeName}.${ext}`;
        document.body.appendChild(a);
        a.click();
        a.remove();

        $('#qrExportModal').fadeOut();
        return;
    }

    let zip = new JSZip();

    for (let item of selected) {
        let blob = await generateQRBlob(item, format);
        let ext = format === "jpeg" ? "jpg" : format;
        let safeName = cleanFileName(item.title);
        zip.file(`${safeName}.${ext}`, blob);
    }

    zip.generateAsync({ type: "blob" }).then(content => {
        saveAs(content, "QR-Codes.zip");
    });

    $('#qrExportModal').fadeOut();
    });





    /** ------------------------
     *  Individual Download
     * ------------------------ */
    $(document).on('click', '.download_qr_btn', function () {

        let row = $(this).closest('.grid_layout_wrapper, tr');

        singleDownloadItem = {
            qr: row.data('url'),
            color: row.data('color') || "#000000",
            shape: row.data('shape') || "square",
            logo: row.data('logo') || "",
            title: row.find('strong').text().trim() || "qr_code"
        };

        // Update modal UI
        $('#qr-count').text(1);
        $('#qrExportModal').fadeIn();
    });

    //  $(document).on('click', '.download_qr_btn', function () {
    //     currentRow = $(this).closest('.grid_layout_wrapper, tr');
    //     // currentRow.find('.qr-format-select, .qr-format-download, .qr-format-cancel').addClass('show-inline');
    // });

    $('.qr-format-cancel').on('click', function () {
        if (!currentRow) return;
        //  currentRow.find('.qr-format-select, .qr-format-download, .qr-format-cancel').removeClass('show-inline');
        // currentRow.find('.qr-format-select, .qr-format-download, .qr-format-cancel').hide();
        currentRow = null;
    });
// $(document).on("click", ".qr-format-download", async function () {

//     let row = currentRow;

//     let qrValue = row.data("url");
//     console.log("Encoding scan URL:", qrValue); // ← test

//     let color = row.data("color") || "#000000";
//     let shape = row.data("shape") || "square";
//     let logo = row.data("logo") || "";
//     let title = row.find("strong").text().trim() || "qr_code";
//     let format = row.find(".qr-format-select").val();

//     let qrHD = new QRCodeStyling({
//         width: 2000,
//         height: 2000,
//         data: qrValue,
//         type: "canvas",
//         dotsOptions: { color, type: shape },
//         backgroundOptions: { color: "#ffffff" },
//         image: logo,
//         imageOptions: { crossOrigin: "anonymous", margin: 10, imageSize: 0.25 }
//     });

//     // FIRST export SVG (for everything)
//     const svgBlob = await qrHD.getRawData("svg", { width: 2000, height: 2000 });

//     // ========== PDF EXPORT ==========
//     if (format === "pdf") {

//         qrHD.getRawData("png", { width: 2000, height: 2000 }).then(blob => {

//             const reader = new FileReader();
//             reader.onload = function () {
//                 const { jsPDF } = window.jspdf;

//                 // Create PDF
//                 const pdf = new jsPDF({
//                     orientation: "portrait",
//                     unit: "pt",
//                     format: [2000, 2000] 
//                 });

//                 // Add image to PDF
//                 pdf.addImage(reader.result, "PNG", 0, 0, 2000, 2000);

//                 pdf.save(title + ".pdf");
//             };

//             reader.readAsDataURL(blob);
//         });

//         return; // stop other formats
//     }


//     // ========== EPS EXPORT ==========
//     if (format === "eps") {
//         qrHD.getRawData("svg").then(svgBlob => {
//             const reader = new FileReader();
//             reader.onload = function () {

//                 let svgText = reader.result;

//                 // simple conversion header for EPS
//                 let epsData =
//                     "%!PS-Adobe-3.0 EPSF-3.0\n" +
//                     "%%BoundingBox: 0 0 2000 2000\n" +
//                     svgText;

//                 const blob = new Blob([epsData], { type: "application/postscript" });

//                 const a = document.createElement("a");
//                 a.href = URL.createObjectURL(blob);
//                 a.download = title + ".eps";
//                 document.body.appendChild(a);
//                 a.click();
//                 a.remove();
//             };
//             reader.readAsText(svgBlob);
//         });

//         return;
//     }


//     // ========== SVG EXPORT ==========
//     if (format === "svg") {
//         const a = document.createElement("a");
//         a.href = URL.createObjectURL(svgBlob);
//         a.download = title + ".svg";
//         document.body.appendChild(a);
//         a.click();
//         a.remove();
//         return;
//     }

//     // ========== PNG / JPEG EXPORT ==========
//     const pngBlob = await qrHD.getRawData("png", {
//         width: 2000,
//         height: 2000
//     });

//     let ext = format === "jpeg" ? "jpg" : format;

//     const a = document.createElement("a");
//     a.href = URL.createObjectURL(pngBlob);
//     a.download = `${title}.${ext}`;
//     document.body.appendChild(a);
//     a.click();
//     a.remove();
// });



//   $(document).on('click', '.qr-format-download', async function() {
//     if (!currentRow) return;

//     let img = currentRow.find('.list_qr_image_wrapper');
//     let title = currentRow.find('strong').text().trim() || 'qr_code';
//     let format = currentRow.find('.qr-format-select').val();

//     try {
//         if (format === 'svg') {
//             const res = await fetch(img.attr('src'));
//             const svgText = await res.text();
//             const blob = new Blob([svgText], { type: 'image/svg+xml' });
//             const url = URL.createObjectURL(blob);

//             const a = document.createElement('a');
//             a.href = url;
//             a.download = title + '.svg';
//             document.body.appendChild(a);
//             a.click();
//             document.body.removeChild(a);
//             URL.revokeObjectURL(url);
//         }else if (format === 'pdf') {
//             // Convert PNG/JPEG to PDF
//             const res = await fetch(img.attr('src'));
//             const blob = await res.blob();
//             const reader = new FileReader();
//             reader.onload = function() {
//                 const { jsPDF } = window.jspdf;
//                 const pdf = new jsPDF();
//                 pdf.addImage(reader.result, "PNG", 10, 10, 100, 100);
//                 pdf.save(title + '.pdf'); 
//             };
//             reader.readAsDataURL(blob);
//         } else {
//             // PNG / JPEG
//             const res = await fetch(img.attr('src'));
//             const blob = await res.blob();
//             let ext = format === 'jpeg' ? 'jpg' : format;
//             const a = document.createElement('a');
//             a.href = URL.createObjectURL(blob);
//             a.download = title + '.' + ext;
//             document.body.appendChild(a);
//             a.click();
//             document.body.removeChild(a);
//             URL.revokeObjectURL(a.href);
//         }
//     } catch (err) {
//         console.error('Failed to fetch image', err);
//         alert('Failed to download QR code');
//     }
//     currentRow.find('.qr-format-select, .qr-format-download, .qr-format-cancel').removeClass('show-inline');
//     currentRow = null;
// });


    // Hide format options initially
    //     $('.qr-format-select, .qr-format-download, .qr-format-cancel').removeClass('show-inline');

    // $('.qr-format-select, .qr-format-download, .qr-format-cancel').hide();

    // Update count on checkbox change
    $(document).on('change', 'input[type="checkbox"]', updateSelectedCount);
        function getSelectedQRData() {
            let data = [];
            let rows = getActiveRows();

            rows.each(function () {
                let row = $(this);
                if (row.find('input[type="checkbox"]').is(':checked')) {
                    data.push({
                        qr: row.data('url'),   // <-- SCAN URL
                        color: row.data('color') || "#000000",
                        shape: row.data('shape') || "square",
                        logo: row.data('logo') || "",
                        title: row.find('strong').text().trim() || "qr_code"
                    });
                }
            });

            return data;
        }   
    });
