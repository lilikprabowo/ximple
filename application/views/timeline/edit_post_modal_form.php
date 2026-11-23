<?php echo form_open(get_uri("timeline/save_edited_post"), array("id" => "edit-timeline-post-form", "class" => "general-form", "role" => "form")); ?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />
    <div class="form-group">
        <div class="col-md-12">
            <?php
            echo form_textarea(array(
                "id" => "edit_post_description",
                "name" => "description",
                "value" => $model_info->description,
                "class" => "form-control",
                "placeholder" => lang('post_placeholder_text'),
                "data-rule-required" => true,
                "data-msg-required" => lang("field_required")
            ));
            ?>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><span class="fa fa-close"></span> <?php echo lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span class="fa fa-check-circle"></span> <?php echo lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        function initEditTimelineEditor() {
            var $desc = $("#edit_post_description");
            if (!$desc.length || $desc.next(".note-editor").length) {
                return;
            }
            if (typeof initWYSIWYGEditor === "function" && $.fn && $.fn.summernote) {
                initWYSIWYGEditor("#edit_post_description", {
                    height: 240,
                    placeholder: $desc.attr("placeholder") || "",
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['table', ['table']],
                        ['insert', ['link']]
                    ],
                    lang: "<?php echo lang('language_locale_long'); ?>"
                });
            }
        }

        setTimeout(initEditTimelineEditor, 60);

        $("#edit-timeline-post-form").appForm({
            beforeAjaxSubmit: function (data) {
                if ($("#edit_post_description").next(".note-editor").length) {
                    var html = $("#edit_post_description").summernote("code");
                    $("#edit_post_description").val(html);
                    $.each(data, function (i, field) {
                        if (field.name === "description") {
                            field.value = html;
                        }
                    });
                }
            },
            onSuccess: function (result) {
                if (result && result.id) {
                    $("#timeline-post-desc-" + result.id).html(result.html);
                }
            }
        });
    });
</script>
