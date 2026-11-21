<?php

class Reminders_model extends Crud_model {

    private $table = null;
    private static $table_checked = false;

    function __construct() {
        $this->table = 'reminders';
        parent::__construct($this->table);

        if (!self::$table_checked) {
            self::$table_checked = true;
            $reminders_table = $this->db->dbprefix('reminders');
            $this->db->query("CREATE TABLE IF NOT EXISTS `$reminders_table` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `title` TEXT NOT NULL,
                `start_date` DATE NOT NULL,
                `start_time` TIME NOT NULL,
                `reminder_status` ENUM('new','shown','done') NOT NULL DEFAULT 'new',
                `task_id` INT(11) NOT NULL DEFAULT 0,
                `created_by` INT(11) NOT NULL,
                `created_at` DATETIME NOT NULL,
                `deleted` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_reminders_user_status` (`created_by`, `deleted`, `reminder_status`, `start_date`, `start_time`),
                KEY `idx_reminders_task` (`task_id`, `deleted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        }
    }

    function get_details($options = array()) {
        $reminders_table = $this->db->dbprefix('reminders');
        $tasks_table = $this->db->dbprefix('tasks');

        $where = "";

        $id = get_array_value($options, "id");
        if ($id) {
            $id = (int) $id;
            $where .= " AND $reminders_table.id=$id";
        }

        $created_by = get_array_value($options, "created_by");
        if ($created_by) {
            $created_by = (int) $created_by;
            $where .= " AND $reminders_table.created_by=$created_by";
        }

        $task_id = get_array_value($options, "task_id");
        if ($task_id) {
            $task_id = (int) $task_id;
            $where .= " AND $reminders_table.task_id=$task_id";
        }

        $status = get_array_value($options, "status");
        if ($status === "done") {
            $where .= " AND $reminders_table.reminder_status='done'";
        } else if ($status === "active") {
            $where .= " AND $reminders_table.reminder_status IN ('new', 'shown')";
        } else if ($status === "new") {
            $where .= " AND $reminders_table.reminder_status='new'";
        }

        $due_before = get_array_value($options, "due_before");
        if ($due_before) {
            $due_before = $this->db->escape_str($due_before);
            $where .= " AND CONCAT($reminders_table.start_date, ' ', $reminders_table.start_time) <= '$due_before'";
        }

        $order = ($status === "done")
            ? "ORDER BY $reminders_table.start_date DESC, $reminders_table.start_time DESC"
            : "ORDER BY $reminders_table.start_date ASC, $reminders_table.start_time ASC";

        $limit = (int) get_array_value($options, "limit");
        $limit_sql = $limit > 0 ? "LIMIT $limit" : "";

        $sql = "SELECT $reminders_table.*, $tasks_table.title AS task_title
                FROM $reminders_table
                LEFT JOIN $tasks_table ON $tasks_table.id = $reminders_table.task_id AND $tasks_table.deleted = 0
                WHERE $reminders_table.deleted = 0 $where
                $order $limit_sql";

        return $this->db->query($sql);
    }

    function count_due($user_id, $local_now) {
        $reminders_table = $this->db->dbprefix('reminders');
        $user_id = (int) $user_id;
        $local_now = $this->db->escape_str($local_now);

        $sql = "SELECT COUNT($reminders_table.id) AS total
                FROM $reminders_table
                WHERE $reminders_table.deleted = 0
                  AND $reminders_table.created_by = $user_id
                  AND $reminders_table.reminder_status IN ('new', 'shown')
                  AND CONCAT($reminders_table.start_date, ' ', $reminders_table.start_time) <= '$local_now'";

        $row = $this->db->query($sql)->row();
        return $row ? (int) $row->total : 0;
    }

}
