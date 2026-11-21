<?php
$ci = get_instance();
$rem_local_now = get_my_local_time("Y-m-d H:i:s");
$rem_default_ts = strtotime($rem_local_now) + (15 * 60);
$rem_default_date = date("Y-m-d", $rem_default_ts);
$rem_default_time = date("H:i", $rem_default_ts);
$task_reminders = $ci->Reminders_model->get_details(array(
    "created_by" => $ci->login_user->id,
    "task_id" => $task_id,
    "status" => "active",
    "limit" => 15
))->result();
?>
<div class="col-md-12 mb15 b-t pt15 rise4-task-reminders-box" data-task-id="<?php echo (int) $task_id; ?>">
    <div class="clearfix mb10">
        <strong class="pull-left"><i class="fa fa-clock-o mr5"></i> Reminders</strong>
        <a href="javascript:void(0);" class="pull-right rise4-task-rem-toggle-btn">+ Add</a>
    </div>

    <div class="rise4-task-rem-form-wrap hide mb10">
        <input type="text" class="form-control mb5 rise4-task-rem-title" placeholder="Reminder title..." value="<?php echo htmlspecialchars($task_title, ENT_QUOTES, 'UTF-8'); ?>" />
        <div class="rise4-rem-datetime-row">
            <input type="date" class="form-control rise4-task-rem-date" value="<?php echo $rem_default_date; ?>" />
            <input type="time" class="form-control rise4-task-rem-time" value="<?php echo $rem_default_time; ?>" />
            <button type="button" class="btn btn-primary rise4-task-rem-save-btn"><i class="fa fa-check"></i></button>
        </div>
    </div>

    <div class="rise4-task-rem-list" id="task-reminders-list-<?php echo (int) $task_id; ?>">
        <?php $this->load->view("reminders/list_items", array("reminders" => $task_reminders, "local_now" => $rem_local_now, "status" => "active", "compact" => true)); ?>
    </div>
</div>
