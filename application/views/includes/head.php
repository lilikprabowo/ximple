<head>
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <?php $this->load->view('includes/meta'); ?>
    <?php $this->load->view('includes/helper_js'); ?>
    <?php $this->load->view('includes/plugin_language_js'); ?>

    <?php
    $css_files = array(
        "assets/bootstrap/css/bootstrap.min.css",
        "assets/js/font-awesome/css/font-awesome.min.css",
        "assets/js/datatable/css/jquery.dataTables.min.css",
        "assets/js/select2/select2.css",
        "assets/js/select2/select2-bootstrap.min.css",
        "assets/css/app.all.css",
        "assets/js/font-awesome-free-7/css/all.min.css",
        "assets/js/font-awesome-free-7/css/v4-shims.min.css",
        "assets/js/tabler-icons/tabler-icons.min.css",
        "assets/js/tabler-icons/fontawesome-compat.css"
    );

    if (get_setting("rtl")) {
        array_push($css_files, "assets/css/rtl.css");
    }

    load_css($css_files);

    ?>
    <?php
    $app_js_v = file_exists(FCPATH . 'assets/js/app.all.js') ? filemtime(FCPATH . 'assets/js/app.all.js') : '4.1';
    $custom_css_v = file_exists(FCPATH . 'assets/css/custom-style.css') ? filemtime(FCPATH . 'assets/css/custom-style.css') : '4.1';
    ?>
    <script type="text/javascript" src="<?php echo base_url('assets/js/app.all.js?v=' . $app_js_v); ?>"></script>
    <!-- Panggil custom-style.css dengan cache busting berbasis filemtime -->
    <link rel="stylesheet" href="<?php echo base_url('assets/css/custom-style.css?v=' . $custom_css_v); ?>">
    <script type="text/javascript">
        window.getRiseAutoThemeState = function () {
            var now = new Date();
            var hour = now.getHours();
            var isNight = (hour >= 18 || hour < 6); // 18:00 - 05:59 = Dark Mode, 06:00 - 17:59 = Light Mode
            var refDate = hour < 6 ? new Date(now.getTime() - 86400000) : now;
            var y = refDate.getFullYear();
            var m = ("0" + (refDate.getMonth() + 1)).slice(-2);
            var d = ("0" + refDate.getDate()).slice(-2);
            var periodKey = y + "-" + m + "-" + d + "-" + (isNight ? "night" : "day");
            var autoMode = isNight ? "dark" : "light";
            var manualPeriod = null;
            var savedMode = null;
            try {
                manualPeriod = localStorage.getItem("rise_theme_manual_period");
                savedMode = localStorage.getItem("rise_theme_mode");
            } catch (e) {}
            var effectiveMode = (manualPeriod === periodKey && (savedMode === "dark" || savedMode === "light"))
                ? savedMode
                : autoMode;
            return { mode: effectiveMode, periodKey: periodKey, autoMode: autoMode };
        };
        (function () {
            try {
                var state = window.getRiseAutoThemeState();
                if (state.mode === "dark") {
                    document.documentElement.setAttribute("data-theme", "dark");
                    document.documentElement.classList.add("dark-mode");
                }
            } catch (e) {}
        })();
    </script>

    <?php $this->load->view("includes/csrf_ajax"); ?>
    <?php $this->load->view("includes/custom_head"); ?>
<script type="text/javascript">
$(document).ready(function () {
    // --- RISE 4.0 Dark / Light Theme Switcher + Modern Animated Donut & Flot Charts ---
    function renderModernDonutChart(placeholder, data, options) {
        var $el = $(placeholder);
        if (!$el.length) return null;

        var isDark = document.documentElement.getAttribute("data-theme") === "dark" ||
                     document.documentElement.classList.contains("dark-mode");

        // Map legacy colors to modern RISE 4.0 palette
        var modernColorMap = {
            "#f0ad4e": "#f59e0b", // Amber 500
            "#f06c71": "#ef4444", // Rose/Red 500
            "#00b393": "#10b981", // Emerald 500
            "#d9534f": "#ef4444",
            "#5bc0de": "#0ea5e9",
            "#337ab7": "#3b82f6",
            "#9b59b6": "#8b5cf6"
        };
        var fallbackPalette = ["#3b82f6", "#f59e0b", "#10b981", "#ef4444", "#8b5cf6", "#06b6d4", "#ec4899"];

        var items = [];
        var total = 0;
        for (var i = 0; i < (data || []).length; i++) {
            var d = data[i] || {};
            var val = parseFloat(d.data) || 0;
            var rawColor = String(d.color || "").trim();
            var mappedColor = modernColorMap[rawColor.toLowerCase()] || rawColor || fallbackPalette[i % fallbackPalette.length];
            // Improve dull grey "To do" slice to modern Slate-400/Blue-Slate
            if (rawColor.toLowerCase() === "#aab7b7" || rawColor.toLowerCase() === "#bdc3c7" || rawColor.toLowerCase() === "#ccc") {
                mappedColor = isDark ? "#64748b" : "#94a3b8";
            }
            total += val;
            items.push({
                label: $.trim(d.label || ("Item " + (i + 1))),
                value: val,
                color: mappedColor
            });
        }

        $el.empty().addClass("rise4-modern-donut-host");

        var wrapHtml =
            '<div class="rise4-donut-wrap">' +
                '<div class="rise4-donut-svg-box">' +
                    '<svg class="rise4-donut-svg" viewBox="0 0 220 220"></svg>' +
                    '<div class="rise4-donut-center">' +
                        '<div class="ribase4-donut-center-val">' + total + '</div>' +
                        '<div class="rise4-donut-center-lbl">Total</div>' +
                    '</div>' +
                '</div>' +
                '<div class="rise4-donut-legend"></div>' +
                '<div class="rise4-donut-tooltip"></div>' +
            '</div>';

        $el.html(wrapHtml);

        var $svg = $el.find(".rise4-donut-svg");
        var $legend = $el.find(".rise4-donut-legend");
        var $centerVal = $el.find(".rise4-donut-center-val");
        var $centerLbl = $el.find(".rise4-donut-center-lbl");
        var $tooltip = $el.find(".rise4-donut-tooltip");

        var cx = 110, cy = 110, r = 80;
        var circumference = 2 * Math.PI * r;
        var svgNS = "http://www.w3.org/2000/svg";

        // Background track ring
        var bgCircle = document.createElementNS(svgNS, "circle");
        bgCircle.setAttribute("cx", cx);
        bgCircle.setAttribute("cy", cy);
        bgCircle.setAttribute("r", r);
        bgCircle.setAttribute("fill", "none");
        bgCircle.setAttribute("stroke", isDark ? "#162032" : "#f1f5f9");
        bgCircle.setAttribute("stroke-width", "26");
        $svg[0].appendChild(bgCircle);

        var nonZeroCount = items.filter(function (it) { return it.value > 0; }).length;
        var gapLen = nonZeroCount > 1 ? 3.5 : 0;
        var accumulatedAngle = -90; // Start at 12 o'clock

        items.forEach(function (item, idx) {
            var pct = total > 0 ? (item.value / total) * 100 : 0;
            var pctRounded = Math.round(pct);

            // Legend item
            var $legItem = $(
                '<div class="rise4-donut-legend-item" data-idx="' + idx + '">' +
                    '<span class="rise4-donut-dot" style="background-color:' + item.color + ';"></span>' +
                    '<span class="rise4-donut-leg-text">' + item.label + '</span>' +
                    '<span class="rise4-donut-leg-val">' + item.value + ' <small>(' + pctRounded + '%)</small></span>' +
                '</div>'
            );
            $legend.append($legItem);

            if (item.value <= 0 || total <= 0) return;

            var segLen = Math.max(0, (item.value / total) * circumference - gapLen);
            var startAngle = accumulatedAngle;
            accumulatedAngle += (item.value / total) * 360;

            var circle = document.createElementNS(svgNS, "circle");
            circle.setAttribute("cx", cx);
            circle.setAttribute("cy", cy);
            circle.setAttribute("r", r);
            circle.setAttribute("fill", "none");
            circle.setAttribute("stroke", item.color);
            circle.setAttribute("stroke-width", "26");
            circle.setAttribute("stroke-linecap", "butt");
            circle.setAttribute("class", "rise4-donut-segment");
            circle.setAttribute("data-idx", idx);
            circle.setAttribute("transform", "rotate(" + startAngle + " " + cx + " " + cy + ")");
            // Start at 0 length for entry sweep animation
            circle.setAttribute("stroke-dasharray", "0 " + circumference);
            $svg[0].appendChild(circle);

            // Trigger smooth entry sweep animation
            setTimeout(function () {
                circle.style.transition = "stroke-dasharray 0.85s cubic-bezier(0.22, 1, 0.36, 1), stroke-width 0.2s ease, opacity 0.2s ease";
                circle.setAttribute("stroke-dasharray", segLen + " " + circumference);
            }, 40 + idx * 90);

            function highlightSegment(active) {
                if (active) {
                    circle.setAttribute("stroke-width", "31");
                    $centerVal.text(item.value).css("color", item.color);
                    $centerLbl.text(item.label + " (" + pctRounded + "%)");
                    $legItem.addClass("active");
                } else {
                    circle.setAttribute("stroke-width", "26");
                    $centerVal.text(total).css("color", "");
                    $centerLbl.text("Total");
                    $legItem.removeClass("active");
                    $tooltip.hide();
                }
            }

            $(circle).on("mouseenter", function () {
                highlightSegment(true);
            }).on("mousemove", function (e) {
                var offset = $el.offset();
                $tooltip.html(
                    '<span class="tt-dot" style="background:' + item.color + '"></span>' +
                    '<strong>' + item.label + ':</strong> ' + item.value + ' (' + pctRounded + '%)'
                ).css({
                    left: (e.pageX - offset.left + 14) + "px",
                    top: (e.pageY - offset.top - 18) + "px",
                    display: "inline-flex"
                });
            }).on("mouseleave", function () {
                highlightSegment(false);
            });

            $legItem.on("mouseenter", function () {
                highlightSegment(true);
            }).on("mouseleave", function () {
                highlightSegment(false);
            });
        });

        try {
            $el.data("rise_flot_args", { data: data, options: options, isPie: true });
        } catch (e) {}

        return { placeholder: placeholder };
    }

    if ($ && $.plot && !$.plot._darkWrapped) {
        var origPlot = $.plot;
        var wrappedPlot = function (placeholder, data, options) {
            try {
                $(placeholder).data("rise_flot_args", { data: data, options: options });
            } catch (e) {}

            // Intercept Pie / Donut charts for crisp SVG RISE 4.0 rendering + animation
            if (options && options.series && options.series.pie && options.series.pie.show) {
                return renderModernDonutChart(placeholder, data, options);
            }

            var isDark = document.documentElement.getAttribute("data-theme") === "dark" ||
                         document.documentElement.classList.contains("dark-mode");
            var plotOpts = $.extend(true, {}, options || {});
            if (isDark) {
                plotOpts.grid = plotOpts.grid || {};
                if (plotOpts.grid.backgroundColor === "#FFF" || plotOpts.grid.backgroundColor === "#fff" || plotOpts.grid.backgroundColor === "#ffffff") {
                    plotOpts.grid.backgroundColor = "transparent";
                }
                if (!plotOpts.grid.borderColor || plotOpts.grid.color === "#bbb" || plotOpts.grid.color === "#ccc" || plotOpts.grid.color === "#eee") {
                    plotOpts.grid.color = "#334155";
                    plotOpts.grid.borderColor = "#334155";
                    plotOpts.grid.tickColor = "rgba(51, 65, 85, 0.5)";
                }
                if (plotOpts.xaxis) {
                    plotOpts.xaxis.font = plotOpts.xaxis.font || { color: "#94a3b8" };
                    plotOpts.xaxis.tickColor = "rgba(51, 65, 85, 0.4)";
                }
                if (plotOpts.yaxis) {
                    plotOpts.yaxis.font = plotOpts.yaxis.font || { color: "#94a3b8" };
                    plotOpts.yaxis.tickColor = "rgba(51, 65, 85, 0.4)";
                }
                if (plotOpts.legend) {
                    plotOpts.legend.backgroundColor = "transparent";
                }
            }

            var $host = $(placeholder);
            $host.addClass("rise4-chart-Result-enter");
            setTimeout(function () {
                $host.removeClass("rise4-chart-Result-enter");
            }, 700);

            return origPlot.call(this, placeholder, data, plotOpts);
        };
        $.extend(wrappedPlot, origPlot);
        wrappedPlot._darkWrapped = true;
        $.plot = wrappedPlot;
    }

    function applyThemeMode(mode, replotCharts, isManual) {
        if (mode === "dark") {
            $("html").attr("data-theme", "dark").addClass("dark-mode");
            $("body").addClass("dark-mode");
        } else {
            $("html").attr("data-theme", "light").removeClass("dark-mode");
            $("body").removeClass("dark-mode");
        }
        try {
            localStorage.setItem("rise_theme_mode", mode);
            if (isManual && typeof window.getRiseAutoThemeState === "function") {
                localStorage.setItem("rise_theme_manual_period", window.getRiseAutoThemeState().periodKey);
            }
        } catch (e) {}

        if (replotCharts && $ && $.plot) {
            $("[id*='flotchart'], [id*='chart'], [id*='status-pai'], #income-expense").each(function () {
                var saved = $(this).data("rise_flot_args");
                if (saved && saved.data) {
                    $.plot(this, saved.data, saved.options);
                }
            });
        }
    }

    var initialState = (typeof window.getRiseAutoThemeState === "function")
        ? window.getRiseAutoThemeState()
        : { mode: "light" };
    applyThemeMode(initialState.mode, false, false);

    $(document).on("click", "#theme-mode-toggle", function (e) {
        e.preventDefault();
        var isDark = $("html").attr("data-theme") === "dark" || $("body").hasClass("dark-mode");
        applyThemeMode(isDark ? "light" : "dark", true, true);
    });

    // Auto-switch theme live when crossing 18:00 (Dark) or 06:00 (Light)
    setInterval(function () {
        if (typeof window.getRiseAutoThemeState !== "function") return;
        var st = window.getRiseAutoThemeState();
        var curIsDark = $("html").attr("data-theme") === "dark" || $("body").hasClass("dark-mode");
        var curMode = curIsDark ? "dark" : "light";
        if (st.mode !== curMode) {
            applyThemeMode(st.mode, true, false);
        }
    }, 60000);

    function syncSidebarCollapsed() {
        var isCollapsed = $("#sidebar").hasClass("collapsed");
        $("body").toggleClass("sidebar-collapsed", isCollapsed);
        if (isCollapsed) {
            $(".navbar-brand").addClass("collapsed-brand");
        } else {
            $(".navbar-brand").removeClass("collapsed-brand");
        }
    }
    syncSidebarCollapsed();
    $(document).on("click", "#sidebar-toggle-md, #sidebar-toggle, #sidebar-collapse", function () {
        setTimeout(syncSidebarCollapsed, 0);
    });

    if ($("#rise4-floating-card").length === 0) {
        $("body").append('<div id="rise4-floating-card"></div>');
    }
    var $floatCard = $("#rise4-floating-card");
    var hideTimeout = null;

    $(document).on("mouseenter", "#sidebar-menu > li", function () {
        if (!$("#sidebar").hasClass("collapsed")) return;

        clearTimeout(hideTimeout);
        var $li = $(this);
        var $a = $li.children("a");
        var aRect = $a[0].getBoundingClientRect();
        var sidebarRect = $("#sidebar")[0].getBoundingClientRect();

        var isActive = $li.hasClass("active");
        var iconClass = $a.find("i").attr("class") || "fa fa-circle-o";

        var $clone = $a.clone();
        $clone.find("i, .badge, .label").remove();
        var titleText = $.trim($clone.text());

        var badgeHtml = $a.find(".badge, .label").length ? $a.find(".badge, .label")[0].outerHTML : "";
        var href = $a.attr("href") || "#";

        var $subUl = $li.children("ul");
        var subMenuHtml = "";
        if ($subUl.length > 0) {
            subMenuHtml += '<div class="rise4-float-submenu">';
            $subUl.find("li > a").each(function () {
                var subHref = $(this).attr("href") || "#";
                var subText = $.trim($(this).text());
                var subActive = $(this).parent().hasClass("active") ? "active" : "";
                subMenuHtml += '<a href="' + subHref + '" class="rise4-sub-item ' + subActive + '"><span class="sub-dash">-</span> ' + subText + '</a>';
            });
            subMenuHtml += '</div>';
        }

        var cardHtml = '<a href="' + href + '" class="rise4-float-header ' + (isActive ? 'active' : '') + '">' +
                           '<i class="' + iconClass + '"></i>' +
                           '<span class="rise4-float-title">' + titleText + '</span>' +
                           badgeHtml +
                       '</a>' + subMenuHtml;

        $floatCard.html(cardHtml).css({
            top: aRect.top + "px",
            left: sidebarRect.right + "px",
            display: "block"
        });
    });

    $(document).on("mouseleave", "#sidebar-menu > li", function () {
        hideTimeout = setTimeout(function () { $floatCard.hide(); }, 100);
    });
    $floatCard.on("mouseenter", function () { clearTimeout(hideTimeout); })
              .on("mouseleave", function () { $floatCard.hide(); });
    $(document).on("click", "#rise4-floating-card a", function () { $floatCard.hide(); });

    // --- Total Hours Worked Number Scramble Animation ---
    var scrambleDigits = "0123456789";

    function scrambleElement($el) {
        if (!$el || !$el.length) return;
        var rawTarget = $el.attr("data-value");
        if (typeof rawTarget === "undefined" || rawTarget === null || rawTarget === "") {
            rawTarget = $.trim($el.text());
            $el.attr("data-value", rawTarget);
        }
        var targetStr = String(rawTarget).trim();
        if (!targetStr) return;

        // Prevent overlapping animations on same element
        if ($el.data("scrambling")) return;
        $el.data("scrambling", true);
        $el.addClass("scramble-active");

        var length = targetStr.length;
        var duration = Math.max(800, Math.min(1400, 300 + length * 180));
        var scramblePhase = Math.min(350, duration * 0.28);
        var fps = 30;
        var intervalTime = 1000 / fps;
        var startTime = performance.now();

        var timer = setInterval(function () {
            var elapsed = performance.now() - startTime;
            var progress = Math.min(elapsed / duration, 1);

            var lockedCount = 0;
            if (elapsed >= scramblePhase) {
                var lockProgress = (elapsed - scramblePhase) / (duration - scramblePhase);
                lockedCount = Math.floor(lockProgress * (length + 1));
            }

            var output = "";
            for (var i = 0; i < length; i++) {
                var targetChar = targetStr[i];
                if (!/\d/.test(targetChar)) {
                    output += targetChar;
                } else if (i < lockedCount || progress >= 1) {
                    output += targetChar;
                } else {
                    output += scrambleDigits[Math.floor(Math.random() * scrambleDigits.length)];
                }
            }

            $el.text(output);

            if (progress >= 1) {
                clearInterval(timer);
                $el.text(targetStr);
                $el.data("scrambling", false);
                $el.removeClass("scramble-active").addClass("scramble-complete");
                setTimeout(function () {
                    $el.removeClass("scramble-complete");
                }, 500);
            }
        }, intervalTime);
    }

    function initTotalHoursScramble() {
        // Auto-detect widget container if class was not directly present
        $(".panel-body, .widget-details, .box-content").each(function () {
            var $container = $(this);
            var text = $container.text().toLowerCase();
            if (text.indexOf("total hours worked") !== -1 || text.indexOf("total jam kerja") !== -1) {
                var $h1 = $container.find("h1");
                if ($h1.length && !$h1.hasClass("total-hours-scramble")) {
                    $h1.addClass("total-hours-scramble");
                    if (!$h1.attr("data-value")) {
                        $h1.attr("data-value", $.trim($h1.text()));
                    }
                }
            }
        });

        $(".total-hours-scramble").each(function () {
            scrambleElement($(this));
        });
    }

    // Trigger on page load with slight delay for smooth visual experience
    setTimeout(initTotalHoursScramble, 120);

    // Also trigger if widgets are reloaded via AJAX
    $(document).ajaxComplete(function () {
        setTimeout(initTotalHoursScramble, 150);
    });

    // Re-trigger on click for interactive delight
    $(document).off("click.scramble", ".total-hours-scramble").on("click.scramble", ".total-hours-scramble", function () {
        scrambleElement($(this));
    });
});
</script>
</head>