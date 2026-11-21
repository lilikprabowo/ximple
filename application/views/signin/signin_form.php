<div class="panel panel-default signin-panel mb15">
    <div class="panel-heading signin-panel-heading text-center">
        <?php if (get_setting("show_logo_in_signin_page") === "yes") { ?>
            <img class="signin-logo mw100p" src="<?php echo get_logo_url(); ?>" alt="logo" />
        <?php } ?>
        <h4 class="signin-title"><?php echo lang('signin'); ?></h4>
        <p class="signin-subtitle">Silakan masuk ke akun Anda untuk melanjutkan</p>
    </div>
    <div class="panel-body">
        <?php echo form_open("signin", array("id" => "signin-form", "class" => "general-form", "role" => "form")); ?>

        <?php if (validation_errors()) { ?>
            <div class="alert alert-danger" role="alert">
                <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
                <?php echo validation_errors(); ?>
            </div>
        <?php } ?>
        <div class="form-group signin-input-group">
            <span class="signin-input-icon fa fa-envelope-o"></span>
            <?php
            echo form_input(array(
                "id" => "email",
                "name" => "email",
                "class" => "form-control signin-input",
                "placeholder" => lang('email'),
                "autofocus" => true,
                "data-rule-required" => true,
                "data-msg-required" => lang("field_required"),
                "data-rule-email" => true,
                "data-msg-email" => lang("enter_valid_email")
            ));
            ?>
        </div>
        <div class="form-group signin-input-group">
            <span class="signin-input-icon fa fa-lock"></span>
            <?php
            echo form_password(array(
                "id" => "password",
                "name" => "password",
                "class" => "form-control signin-input",
                "placeholder" => lang('password'),
                "data-rule-required" => true,
                "data-msg-required" => lang("field_required")
            ));
            ?>
            <button type="button" class="btn-toggle-password" id="toggle-password" tabindex="-1" title="Lihat password">
                <i class="fa fa-eye"></i>
            </button>
        </div>
        <input type="hidden" name="redirect" value="<?php
        if (isset($redirect)) {
            echo $redirect;
        }
        ?>" />

        <div class="signin-options clearfix mb15">
            <div class="pull-right">
                <?php echo anchor("signin/request_reset_password", lang("forgot_password"), array("class" => "signin-forgot-link")); ?>
            </div>
        </div>

        <?php $this->load->view("signin/re_captcha"); ?>

        <div class="form-group mb0">
            <button class="btn btn-lg btn-primary btn-block btn-signin-submit" type="submit"><?php echo lang('signin'); ?></button>
        </div>
        <?php echo form_close(); ?>

        <?php if (!get_setting("disable_client_signup")) { ?>
            <div class="signin-signup-note text-center mt20">
                <?php echo lang("you_dont_have_an_account") ?> &nbsp; <?php echo anchor("signup", lang("signup"), array("class" => "font-bold")); ?>
            </div>
        <?php } ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#signin-form").appForm({ajaxSubmit: false, isModal: false});

        $("#toggle-password").click(function () {
            var $pwd = $("#password");
            var $icon = $(this).find("i");
            if ($pwd.attr("type") === "password") {
                $pwd.attr("type", "text");
                $icon.removeClass("fa-eye").addClass("fa-eye-slash");
            } else {
                $pwd.attr("type", "password");
                $icon.removeClass("fa-eye-slash").addClass("fa-eye");
            }
        });
    });
</script>    