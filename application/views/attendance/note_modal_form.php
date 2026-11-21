<?php
$url = "attendance/save_note";

if ($clock_out == "1") {
    $url = "attendance/log_time/$user_id";
}

echo form_open(get_uri($url), array("id" => "attendance-note-form", "class" => "general-form", "role" => "form"));
?>
<div class="modal-body clearfix">
    <input type="hidden" name="id" value="<?php echo $model_info->id; ?>" />

    <div class="form-group mb0">
        <label for="note" class="col-md-12 text-slate-700"><?php echo lang('note'); ?></label>
        <div class="col-md-12">
            <?php
            echo form_textarea(array(
                "id" => "note",
                "name" => "note",
                "class" => "form-control",
                "placeholder" => lang('note'),
                "value" => $model_info->note,
                "style" => "display: none;"
            ));
            ?>
        </div>
        <input name="clock_out" type="hidden" value="<?php echo $clock_out; ?>" />
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal"><span class="fa fa-close"></span> <?php echo lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><span class="fa fa-check-circle"></span> <?php echo lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        var $note = $("#note");

        function initClockoutEditor() {
            if (typeof $.fn.summernote !== "undefined" && !$note.data("summernote-active")) {
                $note.data("summernote-active", true);
                $note.summernote({
                    height: 130,
                    focus: true,
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol']]
                    ],
                    disableDragAndDrop: true,
                    placeholder: "<?php echo lang('note'); ?>"
                });

                var $editor = $note.next('.note-editor');
                var $placeholder = $editor.find('.note-placeholder');
                var $editable = $editor.find('.note-editable');

                function syncPlaceholder() {
                    if ($note.summernote('isEmpty')) {
                        $placeholder.show();
                    } else {
                        $placeholder.hide();
                    }
                }

                // Immediately hide placeholder on any typing keydown with zero delay
                $editable.on('keydown', function (e) {
                    var keyCode = e.keyCode || e.which;
                    var isModifierOrNav = (e.ctrlKey || e.altKey || e.metaKey || 
                        keyCode === 16 || keyCode === 17 || keyCode === 18 || 
                        keyCode === 20 || keyCode === 27 || 
                        (keyCode >= 37 && keyCode <= 40));
                    if (!isModifierOrNav) {
                        $placeholder.hide();
                    }
                });

                // Check on input, keyup, paste, change, blur
                $editable.on('input keyup paste change blur', function () {
                    setTimeout(syncPlaceholder, 0);
                });

                syncPlaceholder();
            } else if (typeof $.fn.summernote === "undefined") {
                $note.show();
            }
        }

        // Initialize immediately without delay to prevent raw HTML flash
        initClockoutEditor();

        $("#attendance-note-form").appForm({
            beforeAjaxSubmit: function (data, self, options) {
                if (typeof $.fn.summernote !== "undefined" && $note.data("summernote-active")) {
                    var noteHtml = $note.summernote('code');
                    if (noteHtml === '<p><br></p>' || noteHtml === '<br>') {
                        noteHtml = '';
                    }
                    $note.val(noteHtml);
                    for (var i = 0; i < data.length; i++) {
                        if (data[i].name === "note") {
                            data[i].value = noteHtml;
                            break;
                        }
                    }
                }
            },
            onSuccess: function (result) {
                if (typeof $.fn.summernote !== "undefined" && $note.data("summernote-active")) {
                    $note.summernote('destroy');
                }
                if (result.clock_widget) {
                    $("#timecard-clock-out").closest("#js-clock-in-out").replaceWith(result.clock_widget);
                } else {
                    if (result.isUpdate) {
                        $(".dataTable:visible").appTable({newData: result.data, dataId: result.id});
                    } else {
                        $(".dataTable:visible").appTable({reload: true});
                    }
                }
            }
        });
    });
</script>