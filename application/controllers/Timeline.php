<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Timeline extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->access_only_team_members();
    }

    /* load timeline view */

    function index() {
        $this->check_module_availability("module_timeline");

        $view_data['team_members'] = "";
        $this->init_permission_checker("message_permission");
        if (get_array_value($this->login_user->permissions, "message_permission") !== "no") {
            $view_data['team_members'] = $this->Messages_model->get_users_for_messaging($this->get_user_options_for_query("staff"))->result();
        }

        $this->template->rander("timeline/index", $view_data);
    }

    /* save a post */

    function save() {
        validate_submitted_data(array(
            "description" => "required"
        ));

        $id = $this->input->post('id');

        $post_id = $this->input->post('post_id');

        $target_path = get_setting("timeline_file_path");

        $files_data = move_files_from_temp_dir_to_permanent_dir($target_path, "timeline_post");

        $data = array(
            "created_by" => $this->login_user->id,
            "created_at" => get_current_utc_time(),
            "post_id" => $post_id,
            "description" => $this->input->post('description'),
            "share_with" => ""
        );

        $data = clean_data($data);
        $data["files"] = $files_data; //don't clean serilized data

        $save_id = $this->Posts_model->save($data, $id);
        if ($save_id) {
            $data = "";
            if ($this->input->post("reload_list")) {
                $options = array("id" => $save_id);
                $view_data['posts'] = $this->Posts_model->get_details($options)->result;
                $view_data['result_remaining'] = 0;
                $view_data['is_first_load'] = false;
                $view_data['single_post'] = '';
                $data = $this->load->view("timeline/post_list", $view_data, true);
            }
            echo json_encode(array("success" => true, "data" => $data, 'message' => lang('comment_submited')));

            if ($post_id == 0) {
                log_notification("created_a_new_post", array("post_id" => $save_id));
            } else {
                log_notification("timeline_post_commented", array("post_id" => $save_id));
            }
        } else {
            echo json_encode(array("success" => false, 'message' => lang('error_occurred')));
        }
    }

    function delete($id = 0) {

        if (!$id) {
            exit();
        }

        $post_info = $this->Posts_model->get_one($id);

        //only admin and creator can delete the post
        if (!($this->login_user->is_admin || $post_info->created_by == $this->login_user->id)) {
            redirect("forbidden");
        }


        //delete the post and files
        if ($this->Posts_model->delete($id) && $post_info->files) {

            //delete the files
            $timeline_file_path = get_setting("timeline_file_path");
            $files = unserialize($post_info->files);

            delete_app_files($timeline_file_path, $files);
        }
    }

    /* load all replies of a post */

    function view_post_replies($post_id) {
        $view_data['reply_list'] = $this->Posts_model->get_details(array("post_id" => $post_id))->result;
        $this->load->view("timeline/reply_list", $view_data);
    }

    /* show post reply form */

    function post_reply_form($post_id) {
        $view_data['post_id'] = $post_id;
        $this->load->view("timeline/reply_form", $view_data);
    }

    /* upload a post file */

    function upload_file() {
        upload_file_to_temp();
    }

    /* check valid file for post */

    function validate_post_file() {
        return validate_post_file($this->input->post("file_name"));
    }

    function download_files($id) {
        $files = $this->Posts_model->get_one($id)->files;
        download_app_files(get_setting("timeline_file_path"), $files);
    }

    /* load more posts */

    function load_more_posts($offset = 0) {
        $params = array("limit" => 20, "offset" => (int) $offset);
        if ($this->input->get("search")) {
            $params["search"] = $this->input->get("search");
        }
        if ($this->input->get("month")) {
            $params["month"] = (int) $this->input->get("month");
        }
        if ($this->input->get("year")) {
            $params["year"] = (int) $this->input->get("year");
        }
        if ($this->input->get("user_id")) {
            $params["user_id"] = (int) $this->input->get("user_id");
        }
        timeline_widget($params);
    }

    /* search & filter timeline posts by keyword, month, year, and user */

    function search_posts() {
        $search = trim((string) $this->input->post("search"));
        $month = (int) $this->input->post("month");
        $year = (int) $this->input->post("year");
        $user_id = (int) $this->input->post("user_id");

        $params = array(
            "limit" => 50,
            "offset" => 0,
            "is_first_load" => true,
            "search" => $search,
            "month" => $month,
            "year" => $year,
            "user_id" => $user_id
        );

        $logs = $this->Posts_model->get_details($params);
        if (!count($logs->result)) {
            echo '<div id="timeline"><div class="rise4-timeline-empty"><i class="ti ti-search-off"></i><div>Tidak ada post timeline yang cocok dengan pencarian/filter.</div></div></div>';
            return;
        }

        $view_data["posts"] = $logs->result;
        $view_data["single_post"] = "";
        $view_data["is_first_load"] = true;
        $view_data["result_remaining"] = $logs->found_rows - 50;
        $view_data["next_page_offset"] = 50;
        $this->load->view("timeline/post_list", $view_data);
    }

    /* post page for notification */

    function post($post_id) {
        //check if it's a post's comment
        $original_post_info = $this->Posts_model->get_one($post_id);
        if ($original_post_info->post_id) {
            $post_id = $original_post_info->post_id;
        }

        $post = $this->Posts_model->get_details(array("id" => $post_id));
        $view_data["posts"] = $post->result;
        $view_data['is_first_load'] = true;
        $view_data['result_remaining'] = 0;
        $view_data['single_post'] = 'single_post';
        $this->template->rander("timeline/post_list", $view_data);
    }

    /* open edit modal for timeline post */

    function edit_post_modal_form($id = 0) {
        if (!$id) {
            show_404();
        }

        $post_info = $this->Posts_model->get_one($id);
        if (!$post_info || !$post_info->id) {
            show_404();
        }

        //only admin and creator can edit the post
        if (!($this->login_user->is_admin || $post_info->created_by == $this->login_user->id)) {
            redirect("forbidden");
        }

        $view_data["model_info"] = $post_info;
        $this->load->view("timeline/edit_post_modal_form", $view_data);
    }

    /* save edited timeline post */

    function save_edited_post() {
        validate_submitted_data(array(
            "id" => "required|numeric",
            "description" => "required"
        ));

        $id = $this->input->post("id");
        $post_info = $this->Posts_model->get_one($id);
        if (!$post_info || !$post_info->id) {
            echo json_encode(array("success" => false, "message" => lang("error_occurred")));
            return;
        }

        //only admin and creator can edit the post
        if (!($this->login_user->is_admin || $post_info->created_by == $this->login_user->id)) {
            redirect("forbidden");
        }

        $data = array(
            "description" => $this->input->post("description")
        );
        $data = clean_data($data);

        $save_id = $this->Posts_model->save($data, $id);
        if ($save_id) {
            echo json_encode(array(
                "success" => true,
                "id" => $id,
                "html" => nl2br(link_it($data["description"])),
                "message" => lang("record_saved")
            ));
        } else {
            echo json_encode(array("success" => false, "message" => lang("error_occurred")));
        }
    }

}

/* End of file timeline.php */
    /* Location: ./application/controllers/timeline.php */