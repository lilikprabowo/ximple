<?php
$current_user_id = isset($this->login_user->id) ? (int) $this->login_user->id : 0;
?>
<div id="rise4-saved-filters-bar" class="rise4-saved-filters-bar" data-user-id="<?php echo $current_user_id; ?>">
    <div class="rise4-sf-group rise4-sf-builtins">
        <span class="rise4-sf-label"><i class="fa fa-filter"></i> Presets:</span>
        <button type="button" class="rise4-sf-pill" data-preset="all">
            <i class="fa fa-list-ul"></i> All
        </button>
        <button type="button" class="rise4-sf-pill active" data-preset="my_tasks">
            <i class="fa fa-user"></i> My Tasks
        </button>
        <button type="button" class="rise4-sf-pill pill-urgent" data-preset="urgent">
            <i class="fa fa-angle-double-up"></i> Urgent
        </button>
        <button type="button" class="rise4-sf-pill pill-high" data-preset="high">
            <i class="fa fa-angle-up"></i> High
        </button>
        <button type="button" class="rise4-sf-pill pill-today" data-preset="due_today">
            <i class="fa fa-calendar-check-o"></i> Due Today
        </button>
        <button type="button" class="rise4-sf-pill pill-overdue" data-preset="overdue">
            <i class="fa fa-exclamation-triangle"></i> Overdue
        </button>
    </div>

    <div class="rise4-sf-divider"></div>

    <div class="rise4-sf-group rise4-sf-customs" id="rise4-sf-custom-list"></div>

    <div class="rise4-sf-save-wrap">
        <button type="button" class="rise4-sf-save-trigger" id="rise4-sf-save-trigger" title="Save current filter combination">
            <i class="fa fa-plus"></i> Save Filter
        </button>
        <div class="rise4-sf-save-inline hide" id="rise4-sf-save-inline">
            <input type="text" id="rise4-sf-name-input" class="form-control" placeholder="Filter name..." maxlength="32" />
            <button type="button" class="btn btn-primary btn-xs rise4-sf-confirm-save" id="rise4-sf-confirm-save"><i class="fa fa-check"></i></button>
            <button type="button" class="btn btn-default btn-xs rise4-sf-cancel-save" id="rise4-sf-cancel-save"><i class="fa fa-times"></i></button>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var $bar = $("#rise4-saved-filters-bar");
        if (!$bar.length) return;

        var userId = $bar.attr("data-user-id") || "0";
        var storageKey = "rise4_task_saved_filters_u_" + userId;

        function getSavedPresets() {
            try {
                var raw = localStorage.getItem(storageKey);
                return raw ? JSON.parse(raw) : [];
            } catch (e) {
                return [];
            }
        }

        function setSavedPresets(list) {
            try {
                localStorage.setItem(storageKey, JSON.stringify(list || []));
            } catch (e) {}
        }

        function getCurrentDeadlineValue() {
            var $btn = $("button[name='deadline'].datepicker-custom-selector");
            if (!$btn.length) return "";
            var txt = $.trim($btn.text()).toLowerCase();
            if (txt.indexOf("expired") !== -1 || txt.indexOf("kadaluarsa") !== -1) return "expired";
            if (txt.indexOf("today") !== -1 || txt.indexOf("hari ini") !== -1) return "TODAY";
            if (txt.indexOf("tomorrow") !== -1 || txt.indexOf("besok") !== -1) return "TOMORROW";
            return "";
        }

        function setSelect2Filter(name, value) {
            var $el = $("input[name='" + name + "'], select[name='" + name + "']").filter(function () {
                return $(this).closest(".modal").length === 0;
            }).first();
            if (!$el.length) return false;
            value = (value === undefined || value === null) ? "" : String(value);
            var currentVal = String($el.val() || "");
            if (currentVal !== value) {
                if ($el.data("select2")) {
                    $el.select2("val", value);
                } else {
                    $el.val(value);
                }
                $el.trigger("change");
                return true;
            }
            return false;
        }

        function setDeadlineFilter(value) {
            var $btn = $("button[name='deadline'].datepicker-custom-selector").first();
            if (!$btn.length) return;

            var targetVal = value || "";
            if (targetVal === "TODAY" && typeof moment !== "undefined") {
                targetVal = moment().format("YYYY-MM-DD");
            } else if (targetVal === "TOMORROW" && typeof moment !== "undefined") {
                targetVal = moment().add(1, "days").format("YYYY-MM-DD");
            }

            try {
                $btn.datepicker("show");
                var $item = $(".datepicker-custom-list .list-group-item[data-value='" + targetVal + "']").first();
                if ($item.length) {
                    $item.trigger("click");
                } else {
                    $btn.datepicker("hide");
                }
            } catch (e) {}
        }

        function applyFilterSet(filters) {
            setSelect2Filter("specific_user_id", filters.specific_user_id !== undefined ? filters.specific_user_id : "");
            setSelect2Filter("project_id", filters.project_id !== undefined ? filters.project_id : "");
            setSelect2Filter("priority", filters.priority !== undefined ? filters.priority : "");
            setSelect2Filter("quick_filter", filters.quick_filter !== undefined ? filters.quick_filter : "");
            setTimeout(function () {
                setDeadlineFilter(filters.deadline !== undefined ? filters.deadline : "");
            }, 60);
        }

        function renderCustomPresets() {
            var list = getSavedPresets();
            var $container = $("#rise4-sf-custom-list");
            $container.empty();
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                var safeName = $("<div>").text(item.name).html();
                var $pill = $(
                    "<span class='rise4-sf-custom-wrap'>" +
                        "<button type='button' class='rise4-sf-pill pill-custom' data-custom-idx='" + i + "'>" +
                            "<i class='fa fa-star'></i> " + safeName +
                        "</button>" +
                        "<button type='button' class='rise4-sf-del' data-del-idx='" + i + "' title='Remove saved filter'>&times;</button>" +
                    "</span>"
                );
                $container.append($pill);
            }
        }

        // Built-in preset clicks
        $bar.on("click", ".rise4-sf-pill[data-preset]", function () {
            var preset = $(this).attr("data-preset");
            $bar.find(".rise4-sf-pill").removeClass("active");
            $(this).addClass("active");

            if (preset === "all") {
                applyFilterSet({specific_user_id: "", project_id: "", priority: "", deadline: "", quick_filter: ""});
            } else if (preset === "my_tasks") {
                applyFilterSet({specific_user_id: userId, project_id: "", priority: "", deadline: "", quick_filter: ""});
            } else if (preset === "urgent") {
                applyFilterSet({specific_user_id: "", project_id: "", priority: "urgent", deadline: "", quick_filter: ""});
            } else if (preset === "high") {
                applyFilterSet({specific_user_id: "", project_id: "", priority: "high", deadline: "", quick_filter: ""});
            } else if (preset === "due_today") {
                applyFilterSet({specific_user_id: "", project_id: "", priority: "", deadline: "TODAY", quick_filter: ""});
            } else if (preset === "overdue") {
                applyFilterSet({specific_user_id: "", project_id: "", priority: "", deadline: "expired", quick_filter: ""});
            }
        });

        // Custom preset click
        $bar.on("click", ".rise4-sf-pill[data-custom-idx]", function () {
            var idx = parseInt($(this).attr("data-custom-idx"), 10);
            var list = getSavedPresets();
            if (!list[idx]) return;
            $bar.find(".rise4-sf-pill").removeClass("active");
            $(this).addClass("active");
            applyFilterSet(list[idx].filters || {});
        });

        // Delete custom preset
        $bar.on("click", ".rise4-sf-del", function (e) {
            e.stopPropagation();
            var idx = parseInt($(this).attr("data-del-idx"), 10);
            var list = getSavedPresets();
            list.splice(idx, 1);
            setSavedPresets(list);
            renderCustomPresets();
        });

        // Save current filter UI
        $("#rise4-sf-save-trigger").on("click", function () {
            $(this).addClass("hide");
            $("#rise4-sf-save-inline").removeClass("hide");
            $("#rise4-sf-name-input").val("").focus();
        });

        $("#rise4-sf-cancel-save").on("click", function () {
            $("#rise4-sf-save-inline").addClass("hide");
            $("#rise4-sf-save-trigger").removeClass("hide");
        });

        function saveCurrentFilter() {
            var name = $.trim($("#rise4-sf-name-input").val());
            if (!name) {
                $("#rise4-sf-name-input").focus();
                return;
            }
            var getVal = function (n) {
                var $el = $("input[name='" + n + "'], select[name='" + n + "']").filter(function () {
                    return $(this).closest(".modal").length === 0;
                }).first();
                return $el.length ? String($el.val() || "") : "";
            };

            var list = getSavedPresets();
            list.push({
                name: name,
                filters: {
                    specific_user_id: getVal("specific_user_id"),
                    project_id: getVal("project_id"),
                    priority: getVal("priority"),
                    quick_filter: getVal("quick_filter"),
                    deadline: getCurrentDeadlineValue()
                }
            });
            setSavedPresets(list);
            renderCustomPresets();

            $("#rise4-sf-save-inline").addClass("hide");
            $("#rise4-sf-save-trigger").removeClass("hide");
            if (typeof appAlert !== "undefined") {
                appAlert.success("Filter preset \"" + name + "\" saved!", {duration: 3000});
            }
        }

        $("#rise4-sf-confirm-save").on("click", saveCurrentFilter);
        $("#rise4-sf-name-input").on("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault();
                saveCurrentFilter();
            } else if (e.key === "Escape") {
                $("#rise4-sf-cancel-save").trigger("click");
            }
        });

        renderCustomPresets();
    });
</script>
