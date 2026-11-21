<div class="rise4-reminders-panel">
    <div class="rise4-reminders-header">
        <div class="rise4-reminders-title">
            <i class="fa fa-clock-o"></i>
            <span>Reminders</span>
            <?php if ($due_count > 0) { ?>
                <span class="rise4-rem-count-pill"><?php echo $due_count; ?> due</span>
            <?php } ?>
        </div>
        <div class="rise4-reminders-tabs">
            <button type="button" class="rise4-rem-tab active" data-status="active">Active</button>
            <button type="button" class="rise4-rem-tab" data-status="done">Completed</button>
        </div>
    </div>

    <form id="topbar-reminder-form" class="rise4-reminders-form" autocomplete="off">
        <div class="rise4-rem-input-row">
            <input type="text" name="title" id="topbar-reminder-title" class="form-control" placeholder="Add a reminder..." required />
        </div>
        <div class="rise4-rem-datetime-row">
            <input type="date" name="start_date" id="topbar-reminder-date" class="form-control" value="<?php echo $default_date; ?>" required />
            <input type="time" name="start_time" id="topbar-reminder-time" class="form-control" value="<?php echo $default_time; ?>" required />
            <button type="submit" class="btn btn-primary rise4-rem-add-btn">
                <i class="fa fa-plus"></i> Add
            </button>
        </div>
    </form>

    <div id="reminders-dropdown-list" class="rise4-reminders-list" data-status="active">
        <?php $this->load->view("reminders/list_items", array("reminders" => $reminders, "local_now" => $local_now, "status" => "active", "compact" => false)); ?>
    </div>
</div>
