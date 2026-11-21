<?php
$status_dropdown_for_update = array();
foreach ($statuses as $status) {
    $status_dropdown_for_update[] = array("id" => $status->id, "text" => $status->key_name ? lang($status->key_name) : $status->title);
}

$points_dropdown_for_update = array();
foreach ($points_dropdown as $key => $value) {
    $points_dropdown_for_update[] = array("id" => $key, "text" => $value);
}

$priority_dropdown_for_update = array();
foreach (get_task_priority_options(true) as $key => $value) {
    $priority_dropdown_for_update[] = array("id" => $key, "text" => $value);
}
?>


<script type="text/javascript">
    $(document).ready(function () {
        $('body').on('click', '[data-act=update-task-info]', function (e) {
            var $instance = $(this),
                    type = $(this).attr('data-act-type'),
                    source = "",
                    select2Option = {},
                    showbuttons = false,
                    placement = "bottom",
                    editableType = "select2",
                    datepicker = {};

            if (type === "status_id") {
                source = <?php echo json_encode($status_dropdown_for_update); ?>;
                select2Option = {data: source};
            } else if (type === "milestone_id") {
                source = <?php echo json_encode($milestones_dropdown); ?>;
                select2Option = {data: source};
            } else if (type === "assigned_to") {
                source = <?php echo json_encode($assign_to_dropdown); ?>;
                select2Option = {data: source};
            } else if (type === "points") {
                source = <?php echo json_encode($points_dropdown_for_update); ?>;
                select2Option = {data: source};
            } else if (type === "priority") {
                source = <?php echo json_encode($priority_dropdown_for_update); ?>;
                select2Option = {data: source};
            } else if (type === "collaborators") {
                e.stopPropagation();
                e.preventDefault();

                showbuttons = true;
                source = <?php echo json_encode($collaborators_dropdown); ?>;
                select2Option = {data: source, multiple: true};
            } else if (type === "labels") {
                e.stopPropagation();
                e.preventDefault();

                source = <?php echo json_encode($label_suggestions); ?>;
                showbuttons = true;
                select2Option = {data: source, multiple: true};
                placement = "bottom";
            } else if (type === "start_date" || type === "deadline") {
                editableType = "date";

                if (type === "deadline") {
                    datepicker["endDate"] = "<?php echo $project_deadline; ?>";

                    //don't show dates before start date
<?php if (is_date_exists($model_info->start_date)) { ?>
                        datepicker["startDate"] = "<?php echo format_to_date($model_info->start_date); ?>";
<?php } ?>
                }
            }

            $(this).appModifier({
                actionType: editableType,
                value: $(this).attr('data-value'),
                actionUrl: '<?php echo_uri("projects/update_task_info") ?>/' + $(this).attr('data-id') + '/' + $(this).attr('data-act-type'),
                showbuttons: showbuttons,
                datepicker: datepicker,
                select2Option: select2Option,
                placement: placement,
                onSuccess: function (response, newValue) {
                    if (response.success) {
                        if (type === "assigned_to" && response.assigned_to_avatar) {
                            $("#task-assigned-to-avatar").attr("src", response.assigned_to_avatar);

                            if (response.assigned_to_id === "0") {
                                setTimeout(function () {
                                    $instance.html("<span class='text-off'><?php echo lang("add") . " " . lang("assignee"); ?><span>");
                                }, 50);
                            }
                        }

                        if (type === "status_id" && response.status_color) {
                            $instance.closest("span").css("background-color", response.status_color);
                        }

                        if (type === "milestone_id" && response.milestone_id === "0") {
                            setTimeout(function () {
                                $instance.html("<span class='text-off'><?php echo lang("add") . " " . lang("milestone"); ?><span>");
                            }, 50);
                        }

                        if (type === "points" && response.points) {
                            setTimeout(function () {
                                $instance.html(response.points);
                            }, 50);
                        }

                        if (type === "priority" && response.priority_badge !== undefined) {
                            setTimeout(function () {
                                $instance.html(response.priority_badge);
                            }, 50);

                            // Live-update Kanban card if currently on Kanban view
                            var $kanbanCard = $(".kanban-item[data-id='" + response.id + "']");
                            if ($kanbanCard.length) {
                                var $meta = $kanbanCard.find(".meta");
                                if ($meta.length) {
                                    $meta.find(".task-priority-badge").remove();
                                }
                                if (response.priority_badge.indexOf("task-priority-badge") !== -1) {
                                    if (!$meta.length) {
                                        $meta = $("<div class='meta'></div>").appendTo($kanbanCard);
                                    }
                                    $meta.prepend(response.priority_badge + " ");
                                } else if ($meta.length && $.trim($meta.html()) === "") {
                                    $meta.remove();
                                }
                            }
                        }

                        if (type === "labels" && response.labels) {
                            setTimeout(function () {
                                $instance.html(response.labels);
                            }, 50);
                        }

                        if (type === "collaborators" && response.collaborators) {
                            setTimeout(function () {
                                $instance.html(response.collaborators);
                            }, 50);
                        }

                        if ((type === "start_date" || type === "deadline") && response.date) {
                            setTimeout(function () {
                                $instance.html(response.date);

                                if (type === "deadline") {
                                    $(".task-deadline-milestone-tooltip").remove();
                                }
                            }, 50);
                        }

                        $("#task-table").appTable({newData: response.data, dataId: response.id});

                        appLoader.hide();

                        if (typeof window.reloadGantt === "function") {
                            window.reloadGantt(true);
                        }
                    }
                }
            });

            return false;
        });
    });
</script>