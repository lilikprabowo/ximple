<?php load_js(array("assets/js/push_notification/pusher/pusher.min.js")); ?>

<nav class="navbar navbar-default navbar-fixed-top" role="navigation" id="default-navbar">
    <div class="navbar-header">
        <button id="sidebar-toggle" type="button" class="navbar-toggle"  data-target="#sidebar">
            <span class="sr-only">Toggle navigation</span>
            <span class="fa fa-bars"></span>
        </button>

        <?php
        $user = $this->login_user->id;
        $dashboard_link = get_uri("dashboard");
        $user_dashboard = get_setting("user_" . $user . "_dashboard");
        if ($user_dashboard) {
            $dashboard_link = get_uri("dashboard/view/" . $user_dashboard);
        }
        ?>

        <a class="navbar-brand" href="<?php echo $dashboard_link; ?>">
            <img class="dashboard-image dashboard-logo-full logo-light-mode" src="<?php echo get_logo_url(); ?>" alt="logo" />
            <img class="dashboard-image dashboard-logo-full logo-dark-mode" src="<?php echo base_url('files/system/site-logo-dark.png?v=4'); ?>" alt="logo dark" />
            <img class="dashboard-image dashboard-logo-icon" src="<?php echo get_logo_icon_url(); ?>" alt="icon" />
        </a>


    </div>

    <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav navbar-left inline-block hidden-xs">
            <li class="hidden-xs pl15 pr15  b-l">
                <button class="hidden-xs" id="sidebar-toggle-md">
                    <span class="fa fa-dedent"></span>
                </button>
            </li>

            <?php
            //get the array of hidden topbar menus
            $hidden_topbar_menus = explode(",", get_setting("user_" . $user . "_hidden_topbar_menus"));

            if (!in_array("to_do", $hidden_topbar_menus)) {
                $this->load->view("todo/topbar_icon");
            }
            if (!in_array("favorite_projects", $hidden_topbar_menus) && !(get_setting("disable_access_favorite_project_option_for_clients") && $this->login_user->user_type == "client")) {
                $this->load->view("projects/star/topbar_icon");
            }
            if (!in_array("favorite_clients", $hidden_topbar_menus)) {
                $this->load->view("clients/star/topbar_icon");
            }
            if (!in_array("dashboard_customization", $hidden_topbar_menus) && (get_setting("disable_new_dashboard_icon") != 1)) {
                $this->load->view("dashboards/list/topbar_icon");
            }
            ?>

            <?php
            if (has_my_open_timers()) {
                $this->load->view("projects/open_timers_topbar_icon");
            }
            ?>
        </ul>
        <ul class="nav navbar-nav navbar-right inline-block">

            <?php
            if ($this->login_user->user_type == "staff") {
                load_js(array("assets/js/awesomplete/awesomplete.min.js"));
                ?>
                <li class="hidden-xs" title="<?php echo lang('search') . ' (Ctrl+K)'; ?>" data-toggle="tooltip" data-placement="bottom">
                    <a href="javascript:void(0);" class="pull-left" id="global-search-btn" aria-label="Command Palette">
                        <i class="fa fa-search"></i>
                    </a>
                </li>
            <?php } ?>

            <?php
            if (!in_array("quick_add", $hidden_topbar_menus)) {
                $this->load->view("settings/topbar_parts/quick_add");
            }
            ?>

            <?php if (!in_array("language", $hidden_topbar_menus) && (($this->login_user->user_type == "staff" && !get_setting("disable_language_selector_for_team_members")) || ($this->login_user->user_type == "client" && !get_setting("disable_language_selector_for_clients")))) { ?>

                <li class="user-language-option">
                    <?php echo js_anchor("<i class='fa fa-globe'></i>", array("id" => "personal-language-icon", "class" => "dropdown-toggle", "data-toggle" => "dropdown")); ?>

                    <ul class="dropdown-menu p0" style="height: 300px; overflow-y: scroll; min-width: 170px;">
                        <li>
                            <?php
                            $user_language = get_setting("user_" . $this->login_user->id . "_personal_language");
                            $system_language = get_setting("language");

                            foreach (get_language_list() as $language) {
                                $language_status = "";
                                $language_text = $language;

                                if ($user_language == strtolower($language) || (!$user_language && $system_language == strtolower($language))) {
                                    $language_status = "<span class='pull-right checkbox-checked m0'></span>";
                                    $language_text = "<strong class='pull-left'>" . $language . "</strong>";
                                }

                                if ($this->login_user->user_type == "staff") {
                                    echo ajax_anchor(get_uri("team_members/save_personal_language/$language"), $language_text . $language_status, array("class" => "clearfix", "data-reload-on-success" => "1"));
                                } else {
                                    echo ajax_anchor(get_uri("clients/save_personal_language/$language"), $language_text . $language_status, array("class" => "clearfix", "data-reload-on-success" => "1"));
                                }
                            }
                            ?>
                        </li>
                    </ul>
                </li>

            <?php } ?>

            <li class="">
                <?php echo js_anchor("<i class='fa fa-bell-o'></i>", array("id" => "web-notification-icon", "class" => "dropdown-toggle", "data-toggle" => "dropdown")); ?>
                <div class="dropdown-menu aside-xl m0 p0 font-100p" style="min-width: 400px;">
                    <div class="dropdown-details panel bg-white m0">
                        <div class="list-group">
                            <span class="list-group-item inline-loader p10"></span>                          
                        </div>
                    </div>
                    <div class="panel-footer text-sm text-center">
                        <?php echo anchor("notifications", lang('see_all')); ?>
                    </div>
                </div>
            </li>

            <?php if (get_setting("module_message") && can_access_messages_module()) { ?>
                <li class=" <?php echo ($this->login_user->user_type === "client" && !get_setting("client_message_users")) ? "hide" : ""; ?>">
                    <?php echo js_anchor("<i class='fa fa-envelope-o'></i>", array("id" => "message-notification-icon", "class" => "dropdown-toggle", "data-toggle" => "dropdown")); ?>
                    <div class="dropdown-menu aside-xl m0 p0 w300 font-100p">
                        <div class="dropdown-details panel bg-white m0">
                            <div class="list-group">
                                <span class="list-group-item inline-loader p10"></span>                          
                            </div>
                        </div>
                        <div class="panel-footer text-sm text-center">
                            <?php echo anchor("messages", lang('see_all')); ?>
                        </div>
                    </div>
                </li>
            <?php } ?>

            <?php
            $initial_rem_due = 0;
            if (isset($this->Reminders_model) && isset($this->login_user->id)) {
                $initial_rem_due = $this->Reminders_model->count_due($this->login_user->id, get_my_local_time("Y-m-d H:i:s"));
            }
            ?>
            <li class="reminder-topbar-item dropdown">
                <a href="javascript:void(0);" id="personal-reminder-icon" class="dropdown-toggle" data-toggle="dropdown" title="Personal Reminders" aria-label="Personal Reminders">
                    <i class="fa fa-clock-o"></i>
                    <span id="reminder-due-badge" class="badge bg-danger <?php echo $initial_rem_due > 0 ? '' : 'hide'; ?>"><?php echo $initial_rem_due > 99 ? '99+' : $initial_rem_due; ?></span>
                </a>
                <div id="reminders-dropdown-menu" class="dropdown-menu aside-xl m0 p0 font-100p rise4-reminders-dropdown">
                    <div class="rise4-rem-loading p15 text-center">
                        <span class="inline-loader"></span>
                    </div>
                </div>
            </li>

            <li class="theme-mode-item" title="Toggle Dark / Light Mode" data-toggle="tooltip" data-placement="bottom">
                <a href="javascript:void(0);" id="theme-mode-toggle" class="dropdown-toggle" aria-label="Toggle Dark Mode">
                    <i class="fa fa-moon-o theme-icon-dark"></i>
                    <i class="fa fa-sun-o theme-icon-light"></i>
                </a>
            </li>

            <li class="dropdown pr15 dropdown-user">
                <a id="user-dropdown-icon" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                    <span class="avatar-xs avatar pull-left mt-5 mr10" >
                        <img alt="..." src="<?php echo get_avatar($this->login_user->image); ?>">
                    </span><span class="topbar-user-name"><?php echo $this->login_user->first_name . " " . $this->login_user->last_name; ?></span> <span class="caret"></span></a>
                <ul class="dropdown-menu p0" role="menu">
                    <?php if ($this->login_user->user_type == "client") { ?>
                        <li><?php echo get_client_contact_profile_link($this->login_user->id . '/general', "<i class='fa fa-user mr10'></i>" . lang('my_profile')); ?></li>
                        <li><?php echo get_client_contact_profile_link($this->login_user->id . '/account', "<i class='fa fa-key mr10'></i>" . lang('change_password')); ?></li>
                        <li><?php echo get_client_contact_profile_link($this->login_user->id . '/my_preferences', "<i class='fa fa-cog mr10'></i>" . lang('my_preferences')); ?></li>
                    <?php } else { ?>
                        <li><?php echo get_team_member_profile_link($this->login_user->id . '/general', "<i class='fa fa-user mr10'></i>" . lang('my_profile')); ?></li>
                        <li><?php echo get_team_member_profile_link($this->login_user->id . '/account', "<i class='fa fa-key mr10'></i>" . lang('change_password')); ?></li>
                        <li><?php echo get_team_member_profile_link($this->login_user->id . '/my_preferences', "<i class='fa fa-cog mr10'></i>" . lang('my_preferences')); ?></li>
                    <?php } ?>

                    <?php if (get_setting("show_theme_color_changer") === "yes") { ?>

                        <li class="divider theme-changer-devider"></li>    
                        <li class="pl10 ml10  mt10 theme-changer">

                            <?php
                            echo get_custom_theme_color_list();
                            ?>

                        </li>

                    <?php } ?>

                    <li class="divider"></li>
                    <li><a href="<?php echo_uri('signin/sign_out'); ?>"><i class="fa fa-power-off mr10"></i> <?php echo lang('sign_out'); ?></a></li>
                </ul>
            </li>
        </ul>
    </div><!--/.nav-collapse -->
</nav>

<script type="text/javascript">
    var notificationOptions = {};

    $(document).ready(function () {
        //load message notifications
        var messageOptions = {},
                $messageIcon = $("#message-notification-icon"),
                $notificationIcon = $("#web-notification-icon");

        //check message notifications
        messageOptions.notificationUrl = "<?php echo_uri('messages/get_notifications'); ?>";
        messageOptions.notificationStatusUpdateUrl = "<?php echo_uri('messages/update_notification_checking_status'); ?>";
        messageOptions.checkNotificationAfterEvery = "<?php echo get_setting('check_notification_after_every'); ?>";
        messageOptions.icon = "fa-envelope-o";
        messageOptions.notificationSelector = $messageIcon;
        messageOptions.isMessageNotification = true;

        checkNotifications(messageOptions);

        window.updateLastMessageCheckingStatus = function () {
            checkNotifications(messageOptions, true);
        };

        $messageIcon.click(function () {
            checkNotifications(messageOptions, true);
        });




        //check web notifications
        notificationOptions.notificationUrl = "<?php echo_uri('notifications/count_notifications'); ?>";
        notificationOptions.notificationStatusUpdateUrl = "<?php echo_uri('notifications/update_notification_checking_status'); ?>";
        notificationOptions.checkNotificationAfterEvery = "<?php echo get_setting('check_notification_after_every'); ?>";
        notificationOptions.icon = "fa-bell-o";
        notificationOptions.notificationSelector = $notificationIcon;
        notificationOptions.notificationType = "web";
        notificationOptions.pushNotification = "<?php echo get_setting("enable_push_notification") && $this->login_user->enable_web_notification && !get_setting('user_' . $this->login_user->id . '_disable_push_notification') ? true : false ?>";

        checkNotifications(notificationOptions); //start checking notification after starting the message checking 

        if (isMobile()) {
            //for mobile devices, load the notifications list with the page load
            notificationOptions.notificationUrlForMobile = "<?php echo_uri('notifications/get_notifications'); ?>";
            checkNotifications(notificationOptions);
        }

        $notificationIcon.click(function () {
            notificationOptions.notificationUrl = "<?php echo_uri('notifications/get_notifications'); ?>";
            checkNotifications(notificationOptions, true);
        });

        $('[data-toggle="tooltip"]').tooltip();

        /* ==============================================================
           RISE 4.0 PERSONAL REMINDERS ENGINE (TOPBAR + TASK + TOASTS)
           ============================================================== */
        var reminderBaseUrl = "<?php echo_uri('reminders'); ?>";
        var taskViewUrl = "<?php echo_uri('projects/task_view'); ?>";

        function updateReminderBadge(count) {
            var $badge = $("#reminder-due-badge");
            count = parseInt(count, 10) || 0;
            if (count > 0) {
                $badge.text(count > 99 ? "99+" : count).removeClass("hide");
            } else {
                $badge.text("0").addClass("hide");
            }
            var $pill = $(".rise4-rem-count-pill");
            if ($pill.length) {
                if (count > 0) {
                    $pill.text(count + " due").show();
                } else {
                    $pill.hide();
                }
            }
        }

        function loadTopbarRemindersDropdown() {
            var $menu = $("#reminders-dropdown-menu");
            $menu.html("<div class='rise4-rem-loading p15 text-center'><span class='inline-loader'></span></div>");
            $.ajax({
                url: reminderBaseUrl + "/load_topbar_dropdown",
                type: "GET",
                success: function (html) {
                    $menu.html(html);
                }
            });
        }

        function refreshReminderList(status, taskId) {
            status = status || $("#reminders-dropdown-list").attr("data-status") || "active";
            $.ajax({
                url: reminderBaseUrl + "/list_items",
                type: "POST",
                dataType: "json",
                data: {status: status, task_id: taskId || 0},
                success: function (res) {
                    if (res && res.success) {
                        if (taskId) {
                            $("#task-reminders-list-" + taskId).html(res.html);
                        } else {
                            $("#reminders-dropdown-list").attr("data-status", status).html(res.html);
                        }
                        updateReminderBadge(res.due_count);
                    }
                }
            });
        }

        function showReminderToast(rem) {
            var $container = $("#rise4-reminder-toast-stack");
            if (!$container.length) {
                $container = $("<div id='rise4-reminder-toast-stack'></div>").appendTo("body");
            }
            var safeTitle = $("<div>").text(rem.title).html();
            var taskBtn = "";
            if (rem.task_id && rem.task_id > 0) {
                taskBtn = "<a href='#' class='rise4-toast-btn rise4-toast-task' data-act='ajax-modal' data-title='Task #" + rem.task_id + "' data-action-url='" + taskViewUrl + "' data-post-id='" + rem.task_id + "' data-modal-lg='1'><i class='fa fa-external-link'></i> Task #" + rem.task_id + "</a>";
            }
            var $toast = $(
                "<div class='rise4-reminder-toast' data-id='" + rem.id + "'>" +
                    "<div class='rise4-toast-icon'><i class='fa fa-clock-o'></i></div>" +
                    "<div class='rise4-toast-content'>" +
                        "<div class='rise4-toast-heading'>Reminder Due · " + rem.start_time + "</div>" +
                        "<div class='rise4-toast-title'>" + safeTitle + "</div>" +
                        "<div class='rise4-toast-actions'>" +
                            "<button type='button' class='rise4-toast-btn primary rise4-toast-done' data-id='" + rem.id + "'><i class='fa fa-check'></i> Done</button>" +
                            "<button type='button' class='rise4-toast-btn rise4-toast-snooze' data-id='" + rem.id + "' data-mode='30m'><i class='fa fa-history'></i> +30m</button>" +
                            taskBtn +
                        "</div>" +
                    "</div>" +
                    "<button type='button' class='rise4-toast-close' aria-label='Close'>&times;</button>" +
                "</div>"
            );
            $container.append($toast);
            setTimeout(function () { $toast.addClass("show"); }, 20);
        }

        function checkDueReminders() {
            $.ajax({
                url: reminderBaseUrl + "/check_due",
                type: "GET",
                cache: false,
                dataType: "json",
                success: function (res) {
                    if (res && res.success) {
                        updateReminderBadge(res.due_count);
                        if (res.newly_due && res.newly_due.length) {
                            for (var i = 0; i < res.newly_due.length; i++) {
                                showReminderToast(res.newly_due[i]);
                            }
                        }
                    }
                }
            });
        }

        // Keep dropdown open when clicking inside
        $("body").on("click", "#reminders-dropdown-menu", function (e) {
            if (!$(e.target).closest(".rise4-rem-task-link").length) {
                e.stopPropagation();
            }
        });

        // Load dropdown content when topbar icon clicked
        $("#personal-reminder-icon").on("click", function () {
            loadTopbarRemindersDropdown();
        });

        // Switch Active / Completed tabs
        $("body").on("click", ".rise4-rem-tab", function () {
            var status = $(this).attr("data-status");
            $(".rise4-rem-tab").removeClass("active");
            $(this).addClass("active");
            refreshReminderList(status, 0);
        });

        // Save new reminder from Topbar Quick-Add
        $("body").on("submit", "#topbar-reminder-form", function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find(".rise4-rem-add-btn");
            $btn.prop("disabled", true);
            $.ajax({
                url: reminderBaseUrl + "/save",
                type: "POST",
                dataType: "json",
                data: $form.serialize(),
                success: function (res) {
                    $btn.prop("disabled", false);
                    if (res && res.success) {
                        $("#topbar-reminder-title").val("").focus();
                        $(".rise4-rem-tab").removeClass("active");
                        $(".rise4-rem-tab[data-status='active']").addClass("active");
                        refreshReminderList("active", 0);
                    }
                },
                error: function () {
                    $btn.prop("disabled", false);
                }
            });
        });

        // Mark Done / Undo (works in Topbar, Task Modal, and Toast)
        $("body").on("click", ".rise4-rem-check, .rise4-toast-done", function () {
            var $el = $(this);
            var id = $el.attr("data-id");
            var undo = $el.attr("data-undo") === "1" ? 1 : 0;
            var $taskBox = $el.closest(".rise4-task-reminders-box");
            var taskId = $taskBox.length ? parseInt($taskBox.attr("data-task-id"), 10) : 0;

            $.ajax({
                url: reminderBaseUrl + "/mark_done/" + id,
                type: "POST",
                dataType: "json",
                data: {undo: undo},
                success: function (res) {
                    if (res && res.success) {
                        updateReminderBadge(res.due_count);
                        $el.closest(".rise4-reminder-toast").remove();
                        if (taskId) {
                            refreshReminderList("active", taskId);
                        }
                        if ($("#reminders-dropdown-list").length) {
                            refreshReminderList($("#reminders-dropdown-list").attr("data-status"), 0);
                        }
                    }
                }
            });
        });

        // Snooze +30m or Tomorrow 09:00
        $("body").on("click", ".rise4-rem-snooze, .rise4-toast-snooze", function () {
            var $el = $(this);
            var id = $el.attr("data-id");
            var mode = $el.attr("data-mode") || "30m";
            var $taskBox = $el.closest(".rise4-task-reminders-box");
            var taskId = $taskBox.length ? parseInt($taskBox.attr("data-task-id"), 10) : 0;

            $.ajax({
                url: reminderBaseUrl + "/snooze/" + id,
                type: "POST",
                dataType: "json",
                data: {mode: mode},
                success: function (res) {
                    if (res && res.success) {
                        updateReminderBadge(res.due_count);
                        $el.closest(".rise4-reminder-toast").remove();
                        if (res.message && typeof appAlert !== "undefined") {
                            appAlert.success(res.message, {duration: 4000});
                        }
                        if (taskId) {
                            refreshReminderList("active", taskId);
                        }
                        if ($("#reminders-dropdown-list").length) {
                            refreshReminderList($("#reminders-dropdown-list").attr("data-status"), 0);
                        }
                    }
                }
            });
        });

        // Delete reminder
        $("body").on("click", ".rise4-rem-delete", function () {
            var $el = $(this);
            var id = $el.attr("data-id");
            var $taskBox = $el.closest(".rise4-task-reminders-box");
            var taskId = $taskBox.length ? parseInt($taskBox.attr("data-task-id"), 10) : 0;

            $.ajax({
                url: reminderBaseUrl + "/delete/" + id,
                type: "POST",
                dataType: "json",
                success: function (res) {
                    if (res && res.success) {
                        updateReminderBadge(res.due_count);
                        if (taskId) {
                            refreshReminderList("active", taskId);
                        }
                        if ($("#reminders-dropdown-list").length) {
                            refreshReminderList($("#reminders-dropdown-list").attr("data-status"), 0);
                        }
                    }
                }
            });
        });

        // Close floating toast
        $("body").on("click", ".rise4-toast-close", function () {
            $(this).closest(".rise4-reminder-toast").remove();
        });

        // Task Modal: Toggle & Save Task Reminder
        $("body").on("click", ".rise4-task-rem-toggle-btn", function () {
            var $box = $(this).closest(".rise4-task-reminders-box");
            $box.find(".rise4-task-rem-form-wrap").toggleClass("hide");
            $box.find(".rise4-task-rem-title").focus();
        });

        $("body").on("click", ".rise4-task-rem-save-btn", function () {
            var $btn = $(this);
            var $box = $btn.closest(".rise4-task-reminders-box");
            var taskId = parseInt($box.attr("data-task-id"), 10);
            var title = $.trim($box.find(".rise4-task-rem-title").val());
            var startDate = $box.find(".rise4-task-rem-date").val();
            var startTime = $box.find(".rise4-task-rem-time").val();
            if (!title) {
                $box.find(".rise4-task-rem-title").focus();
                return;
            }
            $btn.prop("disabled", true);
            $.ajax({
                url: reminderBaseUrl + "/save",
                type: "POST",
                dataType: "json",
                data: {title: title, start_date: startDate, start_time: startTime, task_id: taskId},
                success: function (res) {
                    $btn.prop("disabled", false);
                    if (res && res.success) {
                        $box.find(".rise4-task-rem-form-wrap").addClass("hide");
                        refreshReminderList("active", taskId);
                        if ($("#reminders-dropdown-list").length) {
                            refreshReminderList("active", 0);
                        }
                    }
                },
                error: function () {
                    $btn.prop("disabled", false);
                }
            });
        });

        // Initial check after 2s, then every 60s
        setTimeout(checkDueReminders, 2000);
        setInterval(checkDueReminders, 60000);
    });

</script>