<?php if (isset($this->login_user->user_type) && $this->login_user->user_type === "staff") { ?>
<div id="rise4-cmd-backdrop" class="rise4-cmd-backdrop hide" aria-hidden="true">
    <div class="rise4-cmd-modal" role="dialog" aria-modal="true" aria-label="Command Palette">
        <div class="rise4-cmd-header">
            <i class="fa fa-search rise4-cmd-search-icon"></i>
            <input type="text" id="rise4-cmd-input" class="rise4-cmd-input" placeholder="Search tasks, projects, clients, or jump to a menu..." autocomplete="off" />
            <span class="rise4-cmd-kbd">ESC</span>
        </div>
        <div id="rise4-cmd-results" class="rise4-cmd-results"></div>
        <div class="rise4-cmd-footer">
            <span><kbd>&uarr;</kbd> <kbd>&darr;</kbd> Navigate</span>
            <span><kbd>&crarr;</kbd> Open</span>
            <span><kbd>Ctrl</kbd>+<kbd>K</kbd> Toggle</span>
        </div>
    </div>
</div>

<!-- Hidden modal triggers for Quick Actions & Live Task/Todo Opening -->
<div class="hide">
    <?php
    echo modal_anchor(get_uri("projects/task_modal_form"), "", array("id" => "cmd-trigger-add-task", "title" => lang("add_task")));
    echo modal_anchor(get_uri("projects/modal_form"), "", array("id" => "cmd-trigger-add-project", "title" => lang("add_project")));
    echo modal_anchor(get_uri("projects/task_view"), "", array("id" => "cmd-trigger-open-task", "title" => lang("task_info"), "data-modal-lg" => "1"));
    echo modal_anchor(get_uri("todo/view"), "", array("id" => "cmd-trigger-open-todo", "title" => lang("todo")));
    ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        var $backdrop = $("#rise4-cmd-backdrop");
        var $input = $("#rise4-cmd-input");
        var $results = $("#rise4-cmd-results");
        var searchUrl = "<?php echo_uri('search/command_palette_search'); ?>";
        var searchTimer = null;
        var currentItems = [];
        var activeIndex = 0;

        var staticCommands = [
            // Quick Actions
            { group: "Quick Actions", type: "action", action: "add_task", title: "Add New Task", keywords: "create task new tambah", icon: "fa-plus-circle" },
            { group: "Quick Actions", type: "action", action: "add_project", title: "Add New Project", keywords: "create project new tambah", icon: "fa-folder-open-o" },
            { group: "Quick Actions", type: "action", action: "reminders", title: "Open Personal Reminders", keywords: "alarm clock pengingat reminder", icon: "fa-clock-o" },
            { group: "Quick Actions", type: "action", action: "toggle_theme", title: "Toggle Dark / Light Mode", keywords: "theme dark light malam siang", icon: "fa-moon-o" },

            // Navigation
            { group: "Navigation", type: "nav", url: "<?php echo_uri('dashboard'); ?>", title: "Dashboard", keywords: "home beranda overview", icon: "fa-desktop" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('projects/all_tasks'); ?>", title: "All Tasks (List)", keywords: "tasks tugas list", icon: "fa-tasks" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('projects/all_tasks_kanban'); ?>", title: "Tasks Kanban Board", keywords: "kanban board tasks", icon: "fa-columns" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('projects/all_projects'); ?>", title: "All Projects", keywords: "projects proyek", icon: "fa-th-large" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('clients'); ?>", title: "Clients", keywords: "customers klien", icon: "fa-briefcase" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('tickets'); ?>", title: "Tickets", keywords: "support tiket", icon: "fa-life-ring" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('timeline'); ?>", title: "Timeline", keywords: "posts feed social", icon: "fa-comments-o" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('events'); ?>", title: "Events & Calendar", keywords: "calendar kalender jadwal", icon: "fa-calendar" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('notes'); ?>", title: "Notes", keywords: "catatan memo", icon: "fa-book" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('team_members'); ?>", title: "Team Members", keywords: "staff karyawan users", icon: "fa-users" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('attendance'); ?>", title: "Time Cards / Attendance", keywords: "absensi clock in out", icon: "fa-clock-o" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('leaves'); ?>", title: "Leave / Cuti", keywords: "cuti sakit izin", icon: "fa-sign-out" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('announcements'); ?>", title: "Announcements", keywords: "pengumuman", icon: "fa-bullhorn" },
            { group: "Navigation", type: "nav", url: "<?php echo_uri('help'); ?>", title: "Help & Knowledge Base", keywords: "help docs artikel", icon: "fa-question-circle" }
        ];

        function escapeHtml(str) {
            return $("<div>").text(str || "").html();
        }

        function renderList(items, isLoading) {
            currentItems = items || [];
            activeIndex = 0;
            $results.empty();

            if (!currentItems.length && !isLoading) {
                $results.html("<div class='rise4-cmd-empty'><i class='fa fa-search'></i><span>No matching commands or records found.</span></div>");
                return;
            }

            var grouped = {};
            var order = [];
            for (var i = 0; i < currentItems.length; i++) {
                var g = currentItems[i].group || "Results";
                if (!grouped[g]) {
                    grouped[g] = [];
                    order.push(g);
                }
                grouped[g].push({ item: currentItems[i], idx: i });
            }

            for (var gIdx = 0; gIdx < order.length; gIdx++) {
                var gName = order[gIdx];
                $results.append("<div class='rise4-cmd-group-title'>" + escapeHtml(gName) + "</div>");
                var list = grouped[gName];
                for (var j = 0; j < list.length; j++) {
                    var entry = list[j];
                    var it = entry.item;
                    var badge = (it.type === "action") ? "<span class='rise4-cmd-tag'>Action</span>" : (it.type === "nav" ? "<span class='rise4-cmd-tag'>Jump</span>" : "");
                    var $row = $(
                        "<button type='button' class='rise4-cmd-item" + (entry.idx === 0 ? " active" : "") + "' data-idx='" + entry.idx + "'>" +
                            "<span class='rise4-cmd-item-icon'><i class='fa " + escapeHtml(it.icon || "fa-angle-right") + "'></i></span>" +
                            "<span class='rise4-cmd-item-title'>" + escapeHtml(it.title) + "</span>" +
                            badge +
                        "</button>"
                    );
                    $results.append($row);
                }
            }

            if (isLoading) {
                $results.append("<div class='rise4-cmd-loading'><i class='fa fa-spinner fa-spin'></i> Searching database...</div>");
            }
        }

        function filterStatic(q) {
            if (!q) return staticCommands.slice(0, 12);
            var low = q.toLowerCase();
            return staticCommands.filter(function (c) {
                return (c.title.toLowerCase().indexOf(low) !== -1) ||
                       ((c.keywords || "").toLowerCase().indexOf(low) !== -1);
            });
        }

        function openPalette() {
            $backdrop.removeClass("hide").attr("aria-hidden", "false");
            $input.val("");
            renderList(filterStatic(""), false);
            setTimeout(function () { $input.focus(); }, 20);
        }

        function closePalette() {
            $backdrop.addClass("hide").attr("aria-hidden", "true");
        }

        function executeItem(it) {
            if (!it) return;
            closePalette();

            if ((it.type === "nav" || it.type === "project" || it.type === "client" || it.type === "article" || it.type === "ticket" || it.type === "note") && it.url) {
                window.location.href = it.url;
            } else if (it.type === "task") {
                var $t = $("#cmd-trigger-open-task");
                $t.attr("data-post-id", it.id).attr("data-title", "Task #" + it.id).trigger("click");
            } else if (it.type === "todo") {
                var $td = $("#cmd-trigger-open-todo");
                $td.attr("data-post-id", it.id).trigger("click");
            } else if (it.type === "action") {
                if (it.action === "add_task") {
                    $("#cmd-trigger-add-task").trigger("click");
                } else if (it.action === "add_project") {
                    $("#cmd-trigger-add-project").trigger("click");
                } else if (it.action === "toggle_theme") {
                    $("#theme-mode-toggle").trigger("click");
                } else if (it.action === "reminders") {
                    $("#personal-reminder-icon").trigger("click");
                }
            }
        }

        function updateActive(newIdx) {
            if (!currentItems.length) return;
            if (newIdx < 0) newIdx = currentItems.length - 1;
            if (newIdx >= currentItems.length) newIdx = 0;
            activeIndex = newIdx;
            $results.find(".rise4-cmd-item").removeClass("active");
            var $active = $results.find(".rise4-cmd-item[data-idx='" + activeIndex + "']").addClass("active");
            if ($active.length && $active[0].scrollIntoView) {
                $active[0].scrollIntoView({ block: "nearest" });
            }
        }

        // Global shortcuts: Ctrl+K / Cmd+K (and "/" when not typing in an input)
        $(document).on("keydown", function (e) {
            if ((e.ctrlKey || e.metaKey) && String(e.key || "").toLowerCase() === "k") {
                e.preventDefault();
                if ($backdrop.hasClass("hide")) {
                    openPalette();
                } else {
                    closePalette();
                }
            } else if (e.key === "/" && $backdrop.hasClass("hide")) {
                var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : "";
                var isEditable = tag === "input" || tag === "textarea" || tag === "select" || (e.target && e.target.isContentEditable);
                if (!isEditable) {
                    e.preventDefault();
                    openPalette();
                }
            } else if (e.key === "Escape" && !$backdrop.hasClass("hide")) {
                e.preventDefault();
                closePalette();
            }
        });

        // Also hook Topbar Global Search button to open Command Palette directly
        $("body").on("click", "#global-search-btn", function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            openPalette();
            return false;
        });

        // Close on backdrop click
        $backdrop.on("click", function (e) {
            if (e.target === this) {
                closePalette();
            }
        });

        // Keyboard navigation inside input
        $input.on("keydown", function (e) {
            if (e.key === "ArrowDown") {
                e.preventDefault();
                updateActive(activeIndex + 1);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                updateActive(activeIndex - 1);
            } else if (e.key === "Enter") {
                e.preventDefault();
                executeItem(currentItems[activeIndex]);
            }
        });

        // Live filtering + debounced server search
        $input.on("input", function () {
            var q = $.trim($(this).val());
            var localMatches = filterStatic(q);

            if (searchTimer) clearTimeout(searchTimer);

            if (q.length < 2) {
                renderList(localMatches, false);
                return;
            }

            renderList(localMatches, true);
            searchTimer = setTimeout(function () {
                $.ajax({
                    url: searchUrl,
                    type: "POST",
                    dataType: "json",
                    data: { q: q },
                    success: function (res) {
                        if ($.trim($input.val()) !== q) return;
                        var remoteItems = (res && res.success && res.items) ? res.items : [];
                        renderList(remoteItems.concat(localMatches), false);
                    },
                    error: function () {
                        renderList(localMatches, false);
                    }
                });
            }, 220);
        });

        // Click & hover on result items
        $results.on("mouseenter", ".rise4-cmd-item", function () {
            var idx = parseInt($(this).attr("data-idx"), 10);
            if (!isNaN(idx)) {
                activeIndex = idx;
                $results.find(".rise4-cmd-item").removeClass("active");
                $(this).addClass("active");
            }
        });

        $results.on("click", ".rise4-cmd-item", function () {
            var idx = parseInt($(this).attr("data-idx"), 10);
            executeItem(currentItems[idx]);
        });
    });
</script>
<?php } ?>
