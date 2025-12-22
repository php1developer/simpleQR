jQuery(document).ready(function ($) {
    // =====================================================================
    // SAFETY CHECK – bail out if PHP didn't localize data
    // =====================================================================
    if (typeof qrAnalyticsData === "undefined") {
        return;
    }

    const { chart_data, labels, values, unique_values, chart_id, ajax_url } = qrAnalyticsData;

    // =====================================================================
    // GLOBAL STATE
    // =====================================================================
    let scansChart   = null;
    let uniqueChart  = null;
    let typeChart    = null;
    let deviceChart  = null;
    let heatmapChart = null;

    let currentPage   = 1;
    let currentFilter = "city";
    let totalPages    = 1;
    let currentRange  = parseInt($("select[id^='qrRangeSelect_']").first().val(), 10) || 7;

    const $tableBody    = $("#scanTableBody");
    const $filterSelect = $("#scanFilter");
    const $pageInfo     = $("#pageInfo");

    // =====================================================================
    // GENERIC HELPERS
    // =====================================================================

    function sum(arr) {
        return (arr || []).reduce(function (acc, v) {
            const n = Number(v) || 0;
            return acc + n;
        }, 0);
    }

    function forceCanvasSize(canvasId) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        canvas.style.display   = "block";
        canvas.style.width     = "100%";
        canvas.style.minHeight = "260px";

        const wrapper = canvas.closest(".qr-chart-canvas");
        if (wrapper) {
            wrapper.style.display   = "block";
            wrapper.style.minHeight = "280px";
            wrapper.style.position  = "relative";
        }
    }

    /**
     * Show / hide a generic "no data" message around a canvas.
     * The message is shown inside the .qr-chart-box /.card-body wrapper.
     */
    function toggleNoDataBox(canvasId, hasData, message) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const $box = $(canvas).closest(".qr-chart-box, .card-body");
        if (!$box.length) return;

        $box.find(".no-data-box").remove();

        if (!hasData) {
            $(canvas).hide();
            $box.append(
                '<div class="no-data-box" style="width:100%;text-align:center;padding:40px 0;">' +
                    '<p style="color:#6b7280;">' + message + "</p>" +
                "</div>"
            );
        } else {
            $(canvas).show();
            forceCanvasSize(canvasId);
        }
    }

    /**
     * Hide / show any wrapper (table columns, stats panels, etc.)
     * and optionally append a message element *after* it.
     */
    function hideSectionWhenNoData($wrapper, hasData, message) {
        if (!$wrapper || !$wrapper.length) return;

        // Remove old messages next to this wrapper
        $wrapper.next(".no-data-box").remove();

        if (hasData) {
            $wrapper.show();
        } else {
            $wrapper.hide();
            if (message) {
                $wrapper.after(
                    '<div class="no-data-box" style="width:100%;text-align:center;padding:40px 0;">' +
                        '<p style="color:#6b7280;">' + message + "</p>" +
                    "</div>"
                );
            }
        }
    }

    function destroyChart(chartRef) {
        if (chartRef && typeof chartRef.destroy === "function") {
            chartRef.destroy();
        }
        return null;
    }

    // =====================================================================
    // LOCATION TABLE (CITY / STATE / COUNTRY)
    // =====================================================================
    function updateTableColumns(filter) {

        // Show everything first
        $(".col-city, .col-state, .col-country").show();

        if (filter === "state") {
            $(".col-city").hide();               // Hide city column
        }

        if (filter === "country") {
            $(".col-city, .col-state").hide();  // Hide both city + state
        }
    }

    function loadLocationData(page, filter, range) {

    $tableBody.html(`<tr><td colspan="5" style="text-align:center;">Loading...</td></tr>`);

    $.ajax({
        url: qrAnalyticsData.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_scan_location_data',
            page: page,
            filter: filter,
            range: range
        },
        success: function (res) {

            if (!res.success || !res.data || res.data.rows.length === 0) {
                $tableBody.html(
                    `<tr><td colspan="5" style="text-align:center;color:#999;">No data found.</td></tr>`
                );
                updateTableColumns(filter); // still update visibility
                return;
            }

            const rows = res.data.rows;
            totalPages = Math.ceil(res.data.total / res.data.per_page);
            currentPage = res.data.page;
            currentFilter = res.data.filter;

            let rowsHTML = '';

            $.each(rows, function (i, row) {

                if (filter === 'city') {
                    rowsHTML += `
                        <tr>
                            <td class="col-city">${row.city}</td>
                            <td class="col-state">${row.state}</td>
                            <td class="col-country">${row.country}</td>
                            <td>${row.scans}</td>
                            <td>${row.percent}%</td>
                        </tr>`;
                }

                else if (filter === 'state') {
                    rowsHTML += `
                        <tr>
                            <td class="col-state">${row.state}</td>
                            <td class="col-country">${row.country}</td>
                            <td>${row.scans}</td>
                            <td>${row.percent}%</td>
                        </tr>`;
                }

                else { // country
                    rowsHTML += `
                        <tr>
                            <td class="col-country">${row.country}</td>
                            <td>${row.scans}</td>
                            <td>${row.percent}%</td>
                        </tr>`;
                }
            });

            $tableBody.html(rowsHTML);

            // ⬅️ APPLY COLUMN VISIBILITY RULES
            updateTableColumns(filter);

            $pageInfo.text(`Page ${currentPage} of ${totalPages}`);
            $('#prevPage').prop('disabled', currentPage <= 1);
            $('#nextPage').prop('disabled', currentPage >= totalPages);

            // renderHeader(filter);
        },

        error: function (xhr, status, error) {
            console.error('AJAX Error:', status, error);
            $tableBody.html(`<tr><td colspan="5" style="text-align:center;color:red;">Error loading data.</td></tr>`);
        }
    });
}

    $("#prevPage").on("click", function () {
        if (currentPage > 1) {
            loadLocationData(currentPage - 1, currentFilter, currentRange);
        }
    });

    $("#nextPage").on("click", function () {
        if (currentPage < totalPages) {
            loadLocationData(currentPage + 1, currentFilter, currentRange);
        }
    });

    if ($filterSelect.length) {
        $filterSelect.on("change", function () {
            currentFilter = $(this).val() || "city";
            currentPage   = 1;
            loadLocationData(1, currentFilter, currentRange);
        });
    }

    // =====================================================================
    // EXPORT PDF
    // =====================================================================

    $(document).on("click", ".analyics_qr_export_wrap", async function (e) {
        e.preventDefault();

        const element = document.querySelector(".um-field .analytics_dashboard_content");
        if (!element) {
            alert("Analytics content not found.");
            return;
        }

        const $btn = $(this);
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

            const fileName = "QR_Analytics_Report_" + new Date().toISOString().slice(0, 10) + ".pdf";
            pdf.save(fileName);
        } catch (err) {
            console.error("Export failed:", err);
            alert("Failed to generate PDF. See console for details.");
        }

        $btn.text("Export");
        $btn.prop("disabled", false);
    });

    // =====================================================================
    // CHART BUILDERS
    // =====================================================================

    // -------- Line Charts (Scans / Unique) --------

    function makeLineChart(canvasId, color, labelText) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        forceCanvasSize(canvasId);

        const existing = Chart.getChart(canvasId);
        if (existing) existing.destroy();

        return new Chart(canvas, {
            type: "line",
            data: {
                labels: [],
                datasets: [{
                    label: labelText,
                    data: [],
                    borderColor: color,
                    backgroundColor: color + "25",
                    borderWidth: 2,
                    tension: 0.35,
                    pointRadius: 3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: "#6b7280", maxTicksLimit: 8 } },
                    y: { beginAtZero: true, ticks: { color: "#6b7280" } }
                }
            }
        });
    }

    function updateLineChartData(chart, labelsAll, dataAll, $cur, $prev, $growth) {
        if (!chart) return;

        const cleanData = (dataAll || []).map(function (v) { return Number(v) || 0; });
        const labelList = labelsAll || [];

        chart.data.labels = labelList;
        chart.data.datasets[0].data = cleanData;
        chart.update();

        const curSum  = sum(cleanData);
        const prevSum = 0; // placeholder, you can add real "previous period" calc later

        if ($cur && $cur.length)  $cur.text(curSum);
        if ($prev && $prev.length) $prev.text(prevSum);

        if ($growth && $growth.length) {
            let growth = 0;
            if (prevSum > 0) {
                growth = ((curSum - prevSum) / prevSum) * 100;
            } else if (curSum > 0) {
                growth = 999;
            }
            const color = growth > 0 ? "#16a34a" : growth < 0 ? "#dc2626" : "#6b7280";
            const arrow = growth > 0 ? "↑ " : growth < 0 ? "↓ " : "";
            $growth.css("color", color).text(arrow + Math.round(Math.abs(growth)) + "%");
        }
    }

    /**
     * Destroy + recreate a line chart, and also control its stats panel.
     * This function is very defensive to avoid any stale data.
     */
    function rebuildLineChart(existingChart, options) {
        const cleanData = (options.dataAll || []).map(function (v) { return Number(v) || 0; });
        const hasData   = cleanData.some(function (v) { return v > 0; });

        // Show / hide the chart + "No data" message
        toggleNoDataBox(options.canvasId, hasData, options.emptyMsg);

        // Show / hide the stats box (the right-side summary)
        if (options.$statsWrap && options.$statsWrap.length) {
            if (hasData) {
                options.$statsWrap.show();
            } else {
                options.$statsWrap.hide();
            }
        }

        // When there is NO data – hard reset everything and destroy old chart
        if (!hasData) {
            if (options.$cur && options.$cur.length)   options.$cur.text(0);
            if (options.$prev && options.$prev.length) options.$prev.text(0);
            if (options.$growth && options.$growth.length) {
                options.$growth.css("color", "#6b7280").text("0%");
            }

            // Hide canvas itself as well
            $("#" + options.canvasId).hide();

            return destroyChart(existingChart);
        }

        // If we reach here: we have data, so we need a new chart instance
        existingChart = destroyChart(existingChart);

        const chart = makeLineChart(options.canvasId, options.color, options.labelText);
        if (!chart) return null;

        updateLineChartData(chart, options.labelsAll, cleanData, options.$cur, options.$prev, options.$growth);
        return chart;
    }

    // -------- Donut Charts (Type / Device) --------

    function buildDonutChart(canvasId, dataObj, colors, emptyMessage) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return null;

        const values = Object.values(dataObj || {}).map(function (v) { return Number(v) || 0; });
        const total  = sum(values);
        const hasData = total > 0;

        toggleNoDataBox(canvasId, hasData, emptyMessage);
        if (!hasData) {
            const existing = Chart.getChart(canvasId);
            if (existing) existing.destroy();
            return null;
        }

        forceCanvasSize(canvasId);

        const existing = Chart.getChart(canvasId);
        if (existing) existing.destroy();

        return new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: Object.keys(dataObj || {}),
                datasets: [{
                    data: values,
                    backgroundColor: colors
                }]
            },
            options: {
                plugins: { legend: { position: "bottom" } },
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    // -------- Heatmap (Time-of-Day) --------

    function buildHeatmap(heatData) {
        const canvasId = "qrHeatmap";
        const canvas   = document.getElementById(canvasId);
        if (!canvas) return null;

        const days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        const hours = [
            "12am","1am","2am","3am","4am","5am","6am",
            "7am","8am","9am","10am","11am",
            "12pm","1pm","2pm","3pm","4pm","5pm","6pm",
            "7pm","8pm","9pm","10pm","11pm"
        ];

        const dataPoints = [];
        let hasData = false;

        $.each(hours, function (_, h) {
            $.each(days, function (_, d) {
                const v = heatData && heatData[d] && heatData[d][h] ? Number(heatData[d][h]) || 0 : 0;
                if (v > 0) hasData = true;
                dataPoints.push({ x: d, y: h, v: v });
            });
        });

        const $container = $(canvas).closest(".card-body");
        $container.find(".no-data-box").remove();

        // Legend elements
        const $legendMax = $("#legend-max");
        const $legendMin = $("#legend-min");

        if (!hasData) {
            $(canvas).hide();
            $container.append(
                '<div class="no-data-box" style="width:100%;text-align:center;padding:40px 0;">' +
                    '<p style="color:#6b7280;">No time-of-day scan data found.</p>' +
                "</div>"
            );
            $legendMax.text("0");
            $legendMin.text("0");

            const existingEmpty = Chart.getChart(canvasId);
            if (existingEmpty) existingEmpty.destroy();

            return null;
        }

        $(canvas).show();
        forceCanvasSize(canvasId);

        const maxScan = Math.max.apply(null, dataPoints.map(function (d) { return d.v; }));
        $legendMax.text(maxScan || 0);
        $legendMin.text(0);

        const existing = Chart.getChart(canvasId);
        if (existing) existing.destroy();

        return new Chart(canvas, {
            type: "matrix",
            data: {
                datasets: [{
                    label: "Scans",
                    data: dataPoints,
                    borderWidth: 1,
                    borderColor: "rgba(255,255,255,0.5)",
                    backgroundColor: function (ctx) {
                        const value = ctx.dataset.data[ctx.dataIndex].v;
                        if (!value) return "rgba(37,99,235,0.05)";
                        const alpha = 0.2 + (value / (maxScan || 1)) * 0.8;
                        return "rgba(37,99,235," + alpha + ")";
                    },
                    width: function (ctx) {
                        const area = ctx.chart.chartArea || {};
                        return area.width / (days.length + 1);
                    },
                    height: function (ctx) {
                        const area = ctx.chart.chartArea || {};
                        return area.height / (hours.length + 2);
                    }
                }]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                layout: { padding: { right: 50 } },
                plugins: {
                    tooltip: {
                        backgroundColor: "#fff",
                        titleColor: "#111",
                        bodyColor: "#111",
                        borderColor: "#2563eb",
                        borderWidth: 1,
                        displayColors: false,
                        callbacks: {
                            title: function (ctx) {
                                return ctx[0].raw.y.toUpperCase() + ", " + ctx[0].raw.x.toUpperCase();
                            },
                            label: function (ctx) {
                                return "Total scans: " + ctx.raw.v;
                            }
                        }
                    },
                    legend: { display: false }
                },
                scales: {
                    x: { type: "category", labels: days, grid: { display: false } },
                    y: { type: "category", labels: hours.slice().reverse(), grid: { display: false } }
                }
            }
        });
    }

    // =====================================================================
    // RANGE FILTER – MAIN AJAX REFRESH
    // =====================================================================

    $(document).on("change", "select[id^='qrRangeSelect_']", function () {
        currentPage = 1;

        const range       = parseInt($(this).val(), 10) || 7;
        currentRange      = range;
        const chartIdDom  = $(this).attr("id").replace("qrRangeSelect_", "");
        const $rangeLabel = $("#dateRangeText_" + chartIdDom);

        $rangeLabel.text("Refreshing...");

        // Refresh location table for this range
        loadLocationData(1, currentFilter, currentRange);

        $.ajax({
            url: ajax_url,
            type: "POST",
            dataType: "json",
            data: {
                action: "get_filtered_analytics",
                range: range
            },
            success: function (res) {
                if (!res.success || !res.data) {
                    alert("No data found.");
                    return;
                }

                const d = res.data || {};

                // ----- Summary cards -----
                $(".total_scan_card_wrap .count").text(d.total_scans || 0);
                $(".card:contains('Unique Scanners') .count").first().text(d.unique_scanners || 0);
                $(".card:contains('Scanned Codes') .count").first().text(d.qr_count || 0);

                // ----- Top QR table -----
                const $topQrTbody = $("#topQrTableBody");
                $topQrTbody.empty();

                if (d.top_qr && d.top_qr.length) {
                    $.each(d.top_qr, function (i, qr) {
                        $topQrTbody.append(
                            "<tr>" +
                                "<td>" + (i + 1) + "</td>" +
                                "<td>" + (qr.name || "") + "</td>" +
                                "<td>" + (qr.scans || 0) + "</td>" +
                                "<td>" +
                                    '<div style="background:#e5e7eb;border-radius:4px;overflow:hidden;width:100%;height:10px;">' +
                                        '<div style="background:#2563eb;width:' + (qr.percent || 0) + '%;height:10px;"></div>' +
                                    "</div>" +
                                    '<span style="font-size:12px;">' + (qr.percent || 0) + "%</span>" +
                                "</td>" +
                            "</tr>"
                        );
                    });
                } else {
                    $topQrTbody.html(
                        '<tr><td colspan="4" style="text-align:center;color:#6b7280;">No QR data for this range.</td></tr>'
                    );
                }

                // ----- Type table -----
                const $typeTableBody = $("#typeTableBody");
                $typeTableBody.empty();

                const typeData    = d.type || {};
                const typeQrCount = d.type_qr_count || {};
                const totalType   = sum(Object.values(typeData));

                hideSectionWhenNoData(
                    $(".qr_code_scan_wrap .table-col"),
                    totalType > 0,
                    "No QR code type data in this range."
                );

                if (totalType > 0) {
                    $.each(typeData, function (type, scanCount) {
                        const qrCount = typeQrCount[type] || 0;
                        const percent = totalType ? ((scanCount / totalType) * 100).toFixed(1) : "0.0";
                        $typeTableBody.append(
                            "<tr>" +
                                "<td>" + type + "</td>" +
                                "<td>" + qrCount + "</td>" +
                                "<td>" + scanCount + "</td>" +
                                "<td>" + percent + "%</td>" +
                            "</tr>"
                        );
                    });
                }

                // ----- Device table -----
                const $deviceTableBody = $("#deviceTableBody");
                $deviceTableBody.empty();

                const deviceData       = d.device || {};
                const totalDeviceScans = sum(Object.values(deviceData));

                hideSectionWhenNoData(
                    $(".device_scans_card .table-col"),
                    totalDeviceScans > 0,
                    "No device data in this range."
                );

                if (totalDeviceScans > 0) {
                    $.each(deviceData, function (device, scanCount) {
                        const percent = totalDeviceScans ? ((scanCount / totalDeviceScans) * 100).toFixed(1) : "0.0";
                        $deviceTableBody.append(
                            "<tr>" +
                                "<td>" + device + "</td>" +
                                "<td>" + scanCount + "</td>" +
                                "<td>" + percent + "%</td>" +
                            "</tr>"
                        );
                    });
                }

                // ----- Donut charts -----
                typeChart = buildDonutChart(
                    "qrTypeChart",
                    typeData,
                    ["#2563eb", "#60a5fa", "#93c5fd", "#bfdbfe"],
                    "No QR code type data for this range."
                );

                deviceChart = buildDonutChart(
                    "qrDeviceChart",
                    deviceData,
                    ["#10b981", "#34d399", "#6ee7b7", "#a7f3d0"],
                    "No device data for this range."
                );

                // ----- Heatmap -----
                heatmapChart = buildHeatmap(d.heatmap || {});

                // ----- Line charts (Scans + Unique) -----
                const uniqueArr = (d.daily_labels || []).map(function (dt) {
                    return d.unique_daily && d.unique_daily[dt] ? d.unique_daily[dt] : 0;
                });

                const $scanStatsWrap   = $("#qrPerfChart_" + chartIdDom).closest(".qr-card").find(".qr-stats").first();
                const $uniqueStatsWrap = $("#qrUniqueChart_" + chartIdDom).closest(".qr-card").find(".qr-stats").first();

                scansChart = rebuildLineChart(scansChart, {
                    canvasId:  "qrPerfChart_" + chartIdDom,
                    color:     "#2563eb",
                    labelText: "Scans",
                    labelsAll: d.daily_labels || [],
                    dataAll:   d.daily_values || [],
                    $cur:      $("#curTotal_" + chartIdDom),
                    $prev:     $("#prevTotal_" + chartIdDom),
                    $growth:   $("#growthText_" + chartIdDom),
                    $statsWrap: $scanStatsWrap,
                    emptyMsg:  "No scan data in this range."
                });

                uniqueChart = rebuildLineChart(uniqueChart, {
                    canvasId:  "qrUniqueChart_" + chartIdDom,
                    color:     "#16a34a",
                    labelText: "Unique Scanners",
                    labelsAll: d.daily_labels || [],
                    dataAll:   uniqueArr,
                    $cur:      $("#curUniqueTotal_" + chartIdDom),
                    $prev:     $("#prevUniqueTotal_" + chartIdDom),
                    $growth:   $("#growthUniqueText_" + chartIdDom),
                    $statsWrap: $uniqueStatsWrap,
                    emptyMsg:  "No unique scanner data in this range."
                });

                // ----- Date range label -----
                const today     = new Date();
                const startDate = new Date();
                startDate.setDate(today.getDate() - (range - 1));

                const options = { month: "short", day: "2-digit", year: "numeric" };
                const startText = startDate.toLocaleDateString("en-US", options);
                const endText   = today.toLocaleDateString("en-US", options);

                $rangeLabel.text(startText + " to " + endText);
            },
            error: function (xhr, status, err) {
                console.error("Error refreshing analytics:", err);
                alert("Failed to refresh analytics.");
            }
        });
    });

    // =====================================================================
    // INITIAL RENDER – using localized PHP data (default last 7 days)
    // =====================================================================

    // Donut charts
    typeChart = buildDonutChart(
        "qrTypeChart",
        chart_data.type || {},
        ["#2563eb", "#60a5fa", "#93c5fd", "#bfdbfe"],
        "No QR code type data in the last 7 days."
    );

    deviceChart = buildDonutChart(
        "qrDeviceChart",
        chart_data.device || {},
        ["#10b981", "#34d399", "#6ee7b7", "#a7f3d0"],
        "No device data in the last 7 days."
    );

    // Heatmap
    heatmapChart = buildHeatmap(chart_data.heatmap || {});

    // Line charts
    const $initialScanStats   = $("#qrPerfChart_" + chart_id).closest(".qr-card").find(".qr-stats").first();
    const $initialUniqueStats = $("#qrUniqueChart_" + chart_id).closest(".qr-card").find(".qr-stats").first();

    scansChart = rebuildLineChart(scansChart, {
        canvasId:  "qrPerfChart_" + chart_id,
        color:     "#2563eb",
        labelText: "Scans",
        labelsAll: labels || [],
        dataAll:   values || [],
        $cur:      $("#curTotal_" + chart_id),
        $prev:     $("#prevTotal_" + chart_id),
        $growth:   $("#growthText_" + chart_id),
        $statsWrap: $initialScanStats,
        emptyMsg:  "No scan data in this range."
    });

    uniqueChart = rebuildLineChart(uniqueChart, {
        canvasId:  "qrUniqueChart_" + chart_id,
        color:     "#16a34a",
        labelText: "Unique Scanners",
        labelsAll: labels || [],
        dataAll:   unique_values || [],
        $cur:      $("#curUniqueTotal_" + chart_id),
        $prev:     $("#prevUniqueTotal_" + chart_id),
        $growth:   $("#growthUniqueText_" + chart_id),
        $statsWrap: $initialUniqueStats,
        emptyMsg:  "No unique scanner data in this range."
    });

    // Initial location table
    loadLocationData(1, "city", currentRange);
});
