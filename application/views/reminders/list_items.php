<?php if (empty($reminders)) { ?>
    <div class="rise4-rem-empty">
        <i class="fa fa-check-circle-o"></i>
        <span><?php echo ($status === "done") ? "No completed reminders yet." : "You're all caught up! No active reminders."; ?></span>
    </div>
<?php } else { ?>
    <?php foreach ($reminders as $rem) {
        $due_dt = $rem->start_date . " " . $rem->start_time;
        $is_done = ($rem->reminder_status === "done");
        $is_overdue = (!$is_done && $due_dt <= $local_now);
        $time_short = substr($rem->start_time, 0, 5);
        $date_label = format_to_date($rem->start_date, false) . " · " . $time_short;
    ?>
        <div class="rise4-rem-item <?php echo $is_overdue ? 'is-overdue' : ''; ?> <?php echo $is_done ? 'is-done' : ''; ?>" data-id="<?php echo $rem->id; ?>">
            <button type="button" class="rise4-rem-check <?php echo $is_done ? 'checked' : ''; ?>" data-id="<?php echo $rem->id; ?>" data-undo="<?php echo $is_done ? '1' : '0'; ?>" title="<?php echo $is_done ? 'Mark as active' : 'Mark as done'; ?>">
                <i class="fa fa-check"></i>
            </button>
            <div class="rise4-rem-body">
                <div class="rise4-rem-text"><?php echo htmlspecialchars($rem->title, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="rise4-rem-meta">
                    <span class="rise4-rem-time <?php echo $is_overdue ? 'overdue' : ''; ?>">
                        <i class="fa fa-clock-o"></i> <?php echo $date_label; ?>
                        <?php if ($is_overdue) { ?><strong class="rise4-rem-due-tag">Due</strong><?php } ?>
                    </span>
                    <?php if (empty($compact) && !empty($rem->task_id) && $rem->task_id > 0) { ?>
                        <?php echo modal_anchor(get_uri("projects/task_view"), "<i class='fa fa-check-square-o'></i> #" . (int) $rem->task_id, array("class" => "rise4-rem-task-link", "title" => lang('task_info') . " #" . (int) $rem->task_id, "data-post-id" => (int) $rem->task_id, "data-modal-lg" => "1")); ?>
                    <?php } ?>
                </div>
            </div>
            <div class="rise4-rem-actions">
                <?php if (!$is_done) { ?>
                    <button type="button" class="rise4-rem-act-btn rise4-rem-snooze" data-id="<?php echo $rem->id; ?>" data-mode="30m" title="Snooze +30 min">
                        <i class="fa fa-history"></i>
                    </button>
                    <button type="button" class="rise4-rem-act-btn rise4-rem-snooze" data-id="<?php echo $rem->id; ?>" data-mode="1d" title="Snooze to Tomorrow 09:00">
                        <i class="fa fa-calendar-plus-o"></i>
                    </button>
                <?php } ?>
                <button type="button" class="rise4-rem-act-btn rise4-rem-delete" data-id="<?php echo $rem->id; ?>" title="Delete reminder">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    <?php } ?>
<?php } ?>
