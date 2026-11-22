<div id="kanban-wrapper">
    <?php
    $columns_data = array();

    foreach ($tasks as $task) {

        $exising_items = get_array_value($columns_data, $task->status_id);
        if (!$exising_items) {
            $exising_items = "";
        }

        $task_labels = make_labels_view_data($task->labels_list);
        $task_priority = (isset($task->priority) && $task->priority) ? get_task_priority_badge($task->priority) : "";
        $sub_task_icon = "";
        if ($task->parent_task_id) {
            $sub_task_icon = "<span class='sub-task-icon mr5' title='" . lang("sub_task") . "'><i class='fa fa-code-fork'></i></span>";
        }

        $meta_html = trim($task_priority . " " . $task_labels);
        if ($meta_html) {
            $task_labels = "<div class='meta'>$meta_html</div>";
        } else {
            $task_labels = "";
        }

        $unread_comments_class = "";
        if (isset($task->unread) && $task->unread && $task->unread != "0") {
            $unread_comments_class = "unread-comments-of-kanban unread";
        }

        $batch_operation_checkbox = "";
        if ($this->login_user->user_type == "staff" && $can_edit_tasks && $project_id) {
            $batch_operation_checkbox = "<span data-act='batch-operation-task-checkbox' title='" . lang("batch_update") . "' class='checkbox-blank-sm pull-right invisible'></span>";
        }

        $disable_dragging = can_edit_this_task_status($task->assigned_to) ? "" : "disable-dragging";

        // RISE 4.x Kanban Card Metadata Footer (Deadline, Checklist, Sub-tasks, Comments)
        $footer_items = "";
        $today_ymd = get_my_local_time("Y-m-d");
        $is_done_task = (isset($task->status_key_name) && $task->status_key_name === "done");

        if (!empty($task->deadline) && is_date_exists($task->deadline)) {
            $dl_class = "";
            if (!$is_done_task && $today_ymd > $task->deadline) {
                $dl_class = "is-overdue";
            } else if (!$is_done_task && $today_ymd == $task->deadline) {
                $dl_class = "is-today";
            }
            $footer_items .= "<span class='kmeta-chip kmeta-deadline $dl_class' data-deadline='" . htmlspecialchars($task->deadline, ENT_QUOTES, 'UTF-8') . "' title='" . lang("deadline") . "'><i class='fa fa-calendar-o'></i> " . format_to_date($task->deadline, false) . "</span>";
        }

        $total_cl = isset($task->total_checklist) ? (int) $task->total_checklist : 0;
        $checked_cl = isset($task->checked_checklist) ? (int) $task->checked_checklist : 0;
        if ($total_cl > 0) {
            $cl_class = ($checked_cl >= $total_cl) ? "is-complete" : "";
            $footer_items .= "<span class='kmeta-chip kmeta-checklist $cl_class' title='" . lang("checklist") . "'><i class='fa fa-check-square-o'></i> " . $checked_cl . "/" . $total_cl . "</span>";
        }

        $total_st = isset($task->total_sub_tasks) ? (int) $task->total_sub_tasks : 0;
        if ($total_st > 0) {
            $footer_items .= "<span class='kmeta-chip kmeta-subtasks' title='" . lang("sub_tasks") . "'><i class='fa fa-code-fork'></i> " . $total_st . "</span>";
        }

        $total_cm = isset($task->total_comments) ? (int) $task->total_comments : 0;
        if ($total_cm > 0) {
            $footer_items .= "<span class='kmeta-chip kmeta-comments' title='" . lang("comments") . "'><i class='fa fa-comment-o'></i> " . $total_cm . "</span>";
        }

        $kanban_footer = $footer_items ? "<div class='rise4-kanban-footer'>" . $footer_items . "</div>" : "";

        $item = $exising_items . modal_anchor(get_uri("projects/task_view"), "<span class='avatar'>" .
                        "<img src='" . get_avatar($task->assigned_to_avatar) . "'>" .
                        "</span>" . $sub_task_icon . $task->id . ". " . $task->title . $batch_operation_checkbox .
                        $task_labels . $kanban_footer, array("class" => "kanban-item $disable_dragging $unread_comments_class", "data-id" => $task->id, "data-project_id" => $task->project_id, "data-sort" => $task->new_sort, "data-post-id" => $task->id, "title" => lang('task_info') . " #$task->id", "data-modal-lg" => "1"));

        $columns_data[$task->status_id] = $item;
    }
    ?>

    <ul id="kanban-container" class="kanban-container clearfix">

        <?php foreach ($columns as $column) { ?>
            <li class="kanban-col" >
                <div class="kanban-col-title" style="background: <?php echo $column->color ? $column->color : "#2e4053"; ?>;"> <?php echo $column->key_name ? lang($column->key_name) : $column->title; ?> </div>

                <div class="kanban-input general-form hide">
                    <?php
                    echo form_input(array(
                        "id" => "title",
                        "name" => "title",
                        "value" => "",
                        "class" => "form-control",
                        "placeholder" => lang('add_a_task')
                    ));
                    ?>
                </div>

                <div  id="kanban-item-list-<?php echo $column->id; ?>" class="kanban-item-list" data-status_id="<?php echo $column->id; ?>" data-status_key="<?php echo $column->key_name; ?>">
                    <?php echo get_array_value($columns_data, $column->id); ?>
                </div>
            </li>
        <?php } ?>

    </ul>
</div>

<img id="move-icon" class="hide" src="<?php echo get_file_uri("assets/images/move.png"); ?>" alt="..." />

<script type="text/javascript">
    var kanbanContainerWidth = "";

    adjustViewHeightWidth = function () {

        if (!$("#kanban-container").length) {
            return false;
        }


        var totalColumns = "<?php echo $total_columns ?>";
        var columnWidth = (335 * totalColumns) + 5;

        if (columnWidth > kanbanContainerWidth) {
            $("#kanban-container").css({width: columnWidth + "px"});
        } else {
            $("#kanban-container").css({width: "100%"});
        }


        //set wrapper scroll
        if ($("#kanban-wrapper")[0].offsetWidth < $("#kanban-wrapper")[0].scrollWidth) {
            $("#kanban-wrapper").css("overflow-x", "scroll");
        } else {
            $("#kanban-wrapper").css("overflow-x", "hidden");
        }


        //set column scroll

        var columnHeight = $(window).height() - $(".kanban-item-list").offset().top - 30;
        if (isMobile()) {
            columnHeight = $(window).height() - 30;
        }

        $(".kanban-item-list").height(columnHeight);

        $(".kanban-item-list").each(function (index) {

            //set scrollbar on column... if requred
            if ($(this)[0].offsetHeight < $(this)[0].scrollHeight) {
                $(this).css("overflow-y", "scroll");
            } else {
                $(this).css("overflow-y", "hidden");
            }

        });
    };


    saveStatusAndSort = function ($item, status) {
        appLoader.show();
        adjustViewHeightWidth();

        var $prev = $item.prev(),
                $next = $item.next(),
                prevSort = 0, nextSort = 0, newSort = 0,
                step = 100000, stepDiff = 500,
                id = $item.attr("data-id"),
                project_id = $item.attr("data-project_id");

        var hasPrev = $prev.length && $prev.attr("data-sort") !== undefined;
        var hasNext = $next.length && $next.attr("data-sort") !== undefined;

        if (hasPrev) {
            prevSort = $prev.attr("data-sort") * 1;
        }

        if (hasNext) {
            nextSort = $next.attr("data-sort") * 1;
        }

        if (!hasPrev && hasNext) {
            //item moved at the top
            newSort = nextSort - stepDiff;
        } else if (!hasNext && hasPrev) {
            //item moved at the bottom
            newSort = prevSort + step;
        } else if (hasPrev && hasNext) {
            //item moved inside two items
            newSort = (prevSort + nextSort) / 2;
        } else if (!hasPrev && !hasNext) {
            //It's the first item of this column
            newSort = step * 100; //set a big value for 1st item
        }

        $item.attr("data-sort", newSort);

        // Live-update deadline chip color immediately on drag between columns
        if (status) {
            var $dlChip = $item.find(".kmeta-deadline");
            if ($dlChip.length) {
                var targetStatusKey = $item.closest(".kanban-item-list").attr("data-status_key") || "";
                var dlDate = $dlChip.attr("data-deadline") || "";
                $dlChip.removeClass("is-overdue is-today");
                if (targetStatusKey !== "done" && dlDate && typeof moment !== "undefined") {
                    var todayYmd = moment().format("YYYY-MM-DD");
                    if (todayYmd > dlDate) {
                        $dlChip.addClass("is-overdue");
                    } else if (todayYmd === dlDate) {
                        $dlChip.addClass("is-today");
                    }
                }
            }
        }


        $.ajax({
            url: '<?php echo_uri("projects/save_task_sort_and_status") ?>',
            type: "POST",
            data: {id: id, sort: newSort, status_id: status, project_id: project_id},
            success: function () {
                appLoader.hide();

                if (isMobile()) {
                    adjustViewHeightWidth();
                }
            }
        });

    };



    $(document).ready(function () {
        kanbanContainerWidth = $("#kanban-container").width();

        if (isMobile() && window.scrollToKanbanContent) {
            window.scrollTo(0, 220); //scroll to the content for mobile devices
            window.scrollToKanbanContent = false;
        }

<?php if ($this->login_user->user_type == "staff" || ($this->login_user->user_type == "client" && $can_edit_tasks)) { ?>
            $(".kanban-item-list").each(function (index) {
                var id = this.id;

                var options = {
                    animation: 150,
                    group: "kanban-item-list",
                    filter: ".disable-dragging",
                    cancel: ".disable-dragging",
                    ghostClass: "kanban-sortable-ghost",
                    chosenClass: "kanban-sortable-chosen",
                    setData: function (dataTransfer, dragEl) {
                        var img = document.createElement("img");
                        img.src = $("#move-icon").attr("src");
                        img.style.opacity = 1;
                        if (dataTransfer && dataTransfer.setDragImage) {
                            dataTransfer.setDragImage(img, 5, 10);
                        }
                    },
                    onAdd: function (e) {
                        //moved to another column. update both sort and status
                        saveStatusAndSort($(e.item), $(e.item).closest(".kanban-item-list").attr("data-status_id"));
                    },
                    onUpdate: function (e) {
                        //updated sort
                        saveStatusAndSort($(e.item));
                    }
                };

                Sortable.create($("#" + id)[0], options);
            });
<?php } ?>


        adjustViewHeightWidth();



    });

    $(window).resize(function () {
        adjustViewHeightWidth();
    });

</script>

<?php $this->load->view("projects/tasks/update_task_read_comments_status_script"); ?>