<div class="panel panel-default signin-panel mb15">
    <div class="panel-heading signin-panel-heading text-center">
        <?php if (get_setting("show_logo_in_signin_page") === "yes") { ?>
            <img class="signin-logo mw100p" src="<?php echo get_logo_url(); ?>" alt="logo" />
        <?php } ?>
        <h4 class="signin-title"><?php echo lang('forgot_password'); ?></h4>
        <p class="signin-subtitle"><?php echo lang("input_email_to_reset_password"); ?></p>
    </div>
    <div class="panel-body">
        <?php echo form_open("signin/send_reset_password_mail", array("id" => "request-password-form", "class" => "general-form", "role" => "form")); ?>

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

        <?php $this->load->view("signin/re_captcha"); ?>

        <div class="form-group mb15">
            <button class="btn btn-lg btn-primary btn-block btn-signin-submit" type="submit"><?php echo lang('send'); ?></button>
        </div>
        <?php echo form_close(); ?>
        <div class="text-center mt15">
            <a href="<?php echo_uri('signin'); ?>" class="signin-back-link"><i class="fa fa-arrow-left"></i> <?php echo lang("signin"); ?></a>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#request-password-form").appForm({
            isModal: false,
            onSubmit: function () {
                appLoader.show();
                $("#request-password-form").find('[type="submit"]').attr('disabled', 'disabled');
            },
            onSuccess: function (result) {
                appLoader.hide();
                appAlert.success(result.message, {container: '.panel-body', animate: false});
                $("#request-password-form").remove();
            },
            onError: function (result) {
                $("#request-password-form").find('[type="submit"]').removeAttr('disabled');
                appLoader.hide();
                appAlert.error(result.message, {container: '.panel-body', animate: false});
                return false;
            }
        });
    });
</script>    
