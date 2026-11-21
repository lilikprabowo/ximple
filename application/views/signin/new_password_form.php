<div class="panel panel-default signin-panel mb15">
    <div class="panel-heading signin-panel-heading text-center">
        <?php if (get_setting("show_logo_in_signin_page") === "yes") { ?>
            <img class="signin-logo mw100p" src="<?php echo get_logo_url(); ?>" alt="logo" />
        <?php } ?>
        <h4 class="signin-title"><?php echo lang('reset_password'); ?></h4>
        <p class="signin-subtitle">Masukkan kata sandi baru untuk akun Anda</p>
    </div>
    <div class="panel-body">
        <?php echo form_open("signin/do_reset_password", array("id" => "reset-password-form", "class" => "general-form", "role" => "form")); ?>
        <input type="hidden" name="key" value="<?php echo isset($key) ? $key : ''; ?>" />
        
        <div class="form-group signin-input-group">
            <span class="signin-input-icon fa fa-lock"></span>
            <?php
            echo form_password(array(
                "id" => "password",
                "name" => "password",
                "class" => "form-control signin-input",
                "placeholder" => lang('password'),
                "data-rule-required" => true,
                "data-rule-minlength" => 6,
                "data-msg-minlength" => lang("enter_minimum_6_characters"),
                "autocomplete" => "off"
            ));
            ?>
            <button type="button" class="btn-toggle-password" data-target="#password" tabindex="-1" title="Lihat password">
                <i class="fa fa-eye"></i>
            </button>
        </div>
        <div class="form-group signin-input-group">
            <span class="signin-input-icon fa fa-lock"></span>
            <?php
            echo form_password(array(
                "id" => "retype_password",
                "name" => "retype_password",
                "class" => "form-control signin-input",
                "placeholder" => lang('retype_password'),
                "autocomplete" => "off",
                "data-rule-equalTo" => "#password",
                "data-msg-equalTo" => lang("enter_same_value")
            ));
            ?>
            <button type="button" class="btn-toggle-password" data-target="#retype_password" tabindex="-1" title="Lihat password">
                <i class="fa fa-eye"></i>
            </button>
        </div>
        <div class="form-group mb15">
            <button class="btn btn-lg btn-primary btn-block btn-signin-submit" type="submit"><?php echo lang('reset_password'); ?></button>
        </div>
        <?php echo form_close(); ?>
        <div class="text-center mt15">
            <a href="<?php echo_uri('signin'); ?>" class="signin-back-link"><i class="fa fa-arrow-left"></i> <?php echo lang("signin"); ?></a>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#reset-password-form").appForm({
            isModal: false,
            onSubmit: function () {
                appLoader.show();
            },
            onSuccess: function (result) {
                appLoader.hide();
                appAlert.success(result.message, {container: '.panel-body', animate: false});
                $("#reset-password-form").remove();
            },
            onError: function (result) {
                appLoader.hide();
                appAlert.error(result.message, {container: '.panel-body', animate: false});
                return false;
            }
        });

        $(".btn-toggle-password").click(function () {
            var target = $(this).attr("data-target");
            var $pwd = $(target);
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