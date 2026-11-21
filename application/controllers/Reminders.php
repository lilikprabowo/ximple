<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Reminders extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model("Reminders_model");
    }

    private function _validate_owner($reminder_info) {
        if (!$reminder_info || !$reminder_info->id || (int) $reminder_info->created_by !== (int) $this->login_user->id) {
            redirect("forbidden");
        }
    }

    private function _get_local_now() {
        return get_my_local_time("Y-m-d H:i:s");
    }

    function load_topbar_dropdown() {
        $local_now = $this->_get_local_now();
        $view_data["reminders"] = $this->Reminders_model->get_details(array(
            "created_by" => $this->login_user->id,
            "status" => "active",
            "limit" => 30
        ))->result();
        $view_data["local_now"] = $local_now;
        $view_data["due_count"] = $this->Reminders_model->count_due($this->login_user->id, $local_now);
        $view_data["status"] = "active";

        // Default date & time (+15 minutes)
        $default_ts = strtotime($local_now) + (15 * 60);
        $view_data["default_date"] = date("Y-m-d", $default_ts);
        $view_data["default_time"] = date("H:i", $default_ts);

        $this->load->view("reminders/topbar_dropdown", $view_data);
    }

    function list_items() {
        $status = $this->input->post("status") === "done" ? "done" : "active";
        $task_id = (int) $this->input->post("task_id");
        $local_now = $this->_get_local_now();

        $options = array(
            "created_by" => $this->login_user->id,
            "status" => $status,
            "limit" => 30
        );
        if ($task_id > 0) {
            $options["task_id"] = $task_id;
        }

        $view_data["reminders"] = $this->Reminders_model->get_details($options)->result();
        $view_data["local_now"] = $local_now;
        $view_data["status"] = $status;
        $view_data["compact"] = $task_id > 0;

        $html = $this->load->view("reminders/list_items", $view_data, true);
        echo json_encode(array(
            "success" => true,
            "html" => $html,
            "due_count" => $this->Reminders_model->count_due($this->login_user->id, $local_now)
        ));
    }

    function save() {
        $title = trim((string) $this->input->post("title"));
        if ($title === "") {
            echo json_encode(array("success" => false, "message" => lang("field_required")));
            return;
        }

        $local_now = $this->_get_local_now();
        $default_ts = strtotime($local_now) + (15 * 60);

        $start_date = trim((string) $this->input->post("start_date"));
        $start_time = trim((string) $this->input->post("start_time"));
        $task_id = (int) $this->input->post("task_id");

        if (!$start_date) {
            $start_date = date("Y-m-d", $default_ts);
        }
        if (!$start_time) {
            $start_time = date("H:i:00", $default_ts);
        } else if (strlen($start_time) === 5) {
            $start_time .= ":00";
        }

        $data = array(
            "title" => $title,
            "start_date" => $start_date,
            "start_time" => $start_time,
            "reminder_status" => "new",
            "task_id" => $task_id,
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time()
        );
        $data = clean_data($data);

        $save_id = $this->Reminders_model->save($data);
        if ($save_id) {
            echo json_encode(array(
                "success" => true,
                "id" => $save_id,
                "due_count" => $this->Reminders_model->count_due($this->login_user->id, $local_now),
                "message" => lang("record_saved")
            ));
        } else {
            echo json_encode(array("success" => false, "message" => lang("error_occurred")));
        }
    }

    function mark_done($id = 0) {
        $id = (int) $id;
        $info = $this->Reminders_model->get_one($id);
        $this->_validate_owner($info);

        $undo = $this->input->post("undo") ? true : false;
        $new_status = $undo ? "new" : "done";

        $saved = $this->Reminders_model->save(array("reminder_status" => $new_status), $id);
        if ($saved) {
            $local_now = $this->_get_local_now();
            echo json_encode(array(
                "success" => true,
                "due_count" => $this->Reminders_model->count_due($this->login_user->id, $local_now)
            ));
        } else {
            echo json_encode(array("success" => false, "message" => lang("error_occurred")));
        }
    }

    function snooze($id = 0) {
        $id = (int) $id;
        $info = $this->Reminders_model->get_one($id);
        $this->_validate_owner($info);

        $mode = $this->input->post("mode");
        $local_now = $this->_get_local_now();
        $now_ts = strtotime($local_now);

        if ($mode === "1d") {
            $target_ts = strtotime("+1 day", $now_ts);
            $new_date = date("Y-m-d", $target_ts);
            $new_time = "09:00:00";
        } else {
            $target_ts = $now_ts + (30 * 60);
            $new_date = date("Y-m-d", $target_ts);
            $new_time = date("H:i:00", $target_ts);
        }

        $saved = $this->Reminders_model->save(array(
            "start_date" => $new_date,
            "start_time" => $new_time,
            "reminder_status" => "new"
        ), $id);

        if ($saved) {
            echo json_encode(array(
                "success" => true,
                "due_count" => $this->Reminders_model->count_due($this->login_user->id, $local_now),
                "message" => "Reminder snoozed to " . $new_date . " " . substr($new_time, 0, 5)
            ));
        } else {
            echo json_encode(array("success" => false, "message" => lang("error_occurred")));
        }
    }

    function delete($id = 0) {
        $id = (int) $id;
        $info = $this->Reminders_model->get_one($id);
        $this->_validate_owner($info);

        if ($this->Reminders_model->delete($id)) {
            $local_now = $this->_get_local_now();
            echo json_encode(array(
                "success" => true,
                "due_count" => $this->Reminders_model->count_due($this->login_user->id, $local_now)
            ));
        } else {
            echo json_encode(array("success" => false, "message" => lang("error_occurred")));
        }
    }

    function check_due() {
        $local_now = $this->_get_local_now();

        $newly_due_rows = $this->Reminders_model->get_details(array(
            "created_by" => $this->login_user->id,
            "status" => "new",
            "due_before" => $local_now,
            "limit" => 5
        ))->result();

        $newly_due = array();
        foreach ($newly_due_rows as $row) {
            // Mark as shown so toast doesn't spam on every 60s poll
            $this->Reminders_model->save(array("reminder_status" => "shown"), $row->id);
            $newly_due[] = array(
                "id" => (int) $row->id,
                "title" => $row->title,
                "start_date" => $row->start_date,
                "start_time" => substr($row->start_time, 0, 5),
                "task_id" => (int) $row->task_id,
                "task_title" => $row->task_title ? $row->task_title : ""
            );
        }

        $due_count = $this->Reminders_model->count_due($this->login_user->id, $local_now);

        echo json_encode(array(
            "success" => true,
            "due_count" => $due_count,
            "newly_due" => $newly_due
        ));
    }

}
