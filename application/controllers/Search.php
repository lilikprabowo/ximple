<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Search extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->access_only_team_members();
    }

    public function index() {
        
    }

    private function can_access_clients() {
        $permissions = $this->login_user->permissions;
        if ($this->login_user->is_admin || get_array_value($permissions, "client")) {
            return true;
        }
    }

    function search_modal_form() {
        $search_fields = array(
            "task",
            "project"
        );

        if ($this->can_access_clients()) {
            $search_fields[] = "client";
        }

        if (get_setting("module_todo")) {
            $search_fields[] = "todo";
        }

        $search_fields_dropdown = array();
        foreach ($search_fields as $search_field) {
            $search_fields_dropdown[] = array("id" => $search_field, "text" => lang($search_field));
        }

        $view_data['search_fields_dropdown'] = json_encode($search_fields_dropdown);

        $this->load->view("search/modal_form", $view_data);
    }

    function get_search_suggestion() {
        $search = $this->input->post("search");
        $search_field = $this->input->post("search_field");

        if ($search && $search_field) {
            $options = array();
            $result = array();

            if ($search_field == "task") { //task
                $options["show_assigned_tasks_only_user_id"] = $this->show_assigned_tasks_only_user_id();
                $result = $this->Tasks_model->get_search_suggestion($search, $options)->result();
            } else if ($search_field == "project") { //project
                if (!$this->can_manage_all_projects()) {
                    $options["user_id"] = $this->login_user->id;
                }
                $result = $this->Projects_model->get_search_suggestion($search, $options)->result();
            } else if ($search_field == "client") { //client
                if (!$this->can_access_clients()) {
                    redirect("forbidden");
                }
                $options["show_own_clients_only_user_id"] = $this->show_own_clients_only_user_id();
                $result = $this->Clients_model->get_search_suggestion($search, $options)->result();
            } else if ($search_field == "todo" && get_setting("module_todo")) { //todo
                $result = $this->Todo_model->get_search_suggestion($search, $this->login_user->id)->result();
            }

            $result_array = array();
            foreach ($result as $value) {
                if ($search_field == "task") {
                    $result_array[] = array("value" => $value->id, "label" => lang("task") . " $value->id: " . $value->title);
                } else {
                    $result_array[] = array("value" => $value->id, "label" => $value->title);
                }
            }

            echo json_encode($result_array);
        }
    }

    function command_palette_search() {
        $q = trim((string) $this->input->post("q"));
        if (strlen($q) < 2) {
            echo json_encode(array("success" => true, "items" => array()));
            return;
        }

        $items = array();

        // 1. Tasks
        $task_options = array("show_assigned_tasks_only_user_id" => $this->show_assigned_tasks_only_user_id());
        $tasks = $this->Tasks_model->get_search_suggestion($q, $task_options)->result();
        $count = 0;
        foreach ($tasks as $t) {
            if (++$count > 5) break;
            $items[] = array(
                "group" => "Tasks",
                "type" => "task",
                "id" => (int) $t->id,
                "title" => "#" . $t->id . " " . $t->title,
                "icon" => "fa-check-square-o"
            );
        }

        // 2. Projects
        $proj_options = array();
        if (!$this->can_manage_all_projects()) {
            $proj_options["user_id"] = $this->login_user->id;
        }
        $projects = $this->Projects_model->get_search_suggestion($q, $proj_options)->result();
        $count = 0;
        foreach ($projects as $p) {
            if (++$count > 5) break;
            $items[] = array(
                "group" => "Projects",
                "type" => "project",
                "id" => (int) $p->id,
                "title" => $p->title,
                "url" => get_uri("projects/view/" . $p->id),
                "icon" => "fa-th-large"
            );
        }

        // 3. Clients
        if ($this->can_access_clients()) {
            $client_options = array("show_own_clients_only_user_id" => $this->show_own_clients_only_user_id());
            $clients = $this->Clients_model->get_search_suggestion($q, $client_options)->result();
            $count = 0;
            foreach ($clients as $c) {
                if (++$count > 5) break;
                $items[] = array(
                    "group" => "Clients",
                    "type" => "client",
                    "id" => (int) $c->id,
                    "title" => $c->title,
                    "url" => get_uri("clients/view/" . $c->id),
                    "icon" => "fa-briefcase"
                );
            }
        }

        // 4. Help & Knowledge Base Articles
        $q_esc = $this->db->escape_str($q);
        $articles_table = $this->db->dbprefix('help_articles');
        $categories_table = $this->db->dbprefix('help_categories');
        $article_sql = "SELECT $articles_table.id, $articles_table.title, $categories_table.type, $categories_table.title AS category_title
                        FROM $articles_table
                        LEFT JOIN $categories_table ON $categories_table.id = $articles_table.category_id
                        WHERE $articles_table.deleted=0 AND $categories_table.deleted=0
                          AND ($articles_table.title LIKE '%$q_esc%' OR $categories_table.title LIKE '%$q_esc%')
                        ORDER BY $articles_table.title ASC
                        LIMIT 5";
        $articles = $this->db->query($article_sql)->result();
        foreach ($articles as $art) {
            $art_type = ($art->type === "knowledge_base") ? "knowledge_base" : "help";
            $items[] = array(
                "group" => "Articles",
                "type" => "article",
                "id" => (int) $art->id,
                "title" => $art->title . ($art->category_title ? " (" . $art->category_title . ")" : ""),
                "url" => get_uri($art_type . "/view/" . $art->id),
                "icon" => "fa-book"
            );
        }

        // 5. Tickets
        if (get_setting("module_ticket") && ($this->login_user->is_admin || get_array_value($this->login_user->permissions, "ticket"))) {
            $tickets_table = $this->db->dbprefix('tickets');
            $ticket_sql = "SELECT id, title FROM $tickets_table
                           WHERE deleted=0 AND (title LIKE '%$q_esc%' OR id='$q_esc')
                           ORDER BY id DESC
                           LIMIT 5";
            $tickets = $this->db->query($ticket_sql)->result();
            foreach ($tickets as $tk) {
                $items[] = array(
                    "group" => "Tickets",
                    "type" => "ticket",
                    "id" => (int) $tk->id,
                    "title" => "#" . $tk->id . " " . $tk->title,
                    "url" => get_uri("tickets/view/" . $tk->id),
                    "icon" => "fa-life-ring"
                );
            }
        }

        // 6. Personal Notes
        if (get_setting("module_note")) {
            $notes_table = $this->db->dbprefix('notes');
            $uid = (int) $this->login_user->id;
            $note_sql = "SELECT id, title FROM $notes_table
                         WHERE deleted=0 AND created_by=$uid AND title LIKE '%$q_esc%'
                         ORDER BY id DESC
                         LIMIT 4";
            $notes = $this->db->query($note_sql)->result();
            foreach ($notes as $nt) {
                $items[] = array(
                    "group" => "Notes",
                    "type" => "note",
                    "id" => (int) $nt->id,
                    "title" => $nt->title,
                    "url" => get_uri("notes"),
                    "icon" => "fa-sticky-note-o"
                );
            }
        }

        // 7. To-do
        if (get_setting("module_todo")) {
            $todos = $this->Todo_model->get_search_suggestion($q, $this->login_user->id)->result();
            $count = 0;
            foreach ($todos as $td) {
                if (++$count > 4) break;
                $items[] = array(
                    "group" => "To-do",
                    "type" => "todo",
                    "id" => (int) $td->id,
                    "title" => $td->title,
                    "icon" => "fa-list-ul"
                );
            }
        }

        echo json_encode(array("success" => true, "items" => $items));
    }

}

/* End of file Search.php */
/* Location: ./application/controllers/Search.php */