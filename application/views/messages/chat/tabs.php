<div class="rise-chat-header box">
    <!--    <div class="box-content chat-back">
            <i class="fa fa-chevron-left"></i>
        </div>-->

    <div class="box-content">
        <ul class="chat-tab p0 m0 nav nav-tabs box"  data-toggle="ajax-tab" role="tablist">

            <li class="box-content" id="chat-inbox-tab-button">
                <a role="presentation" href="#" data-target="#chat-inbox-tab"><i class="fa fa-comments"></i></a>
            </li>

            <?php if ($show_users_list) { ?>
                <li class="box-content" id="chat-users-tab-button">
                    <a role="presentation"  href="<?php echo_uri("messages/users_list/staff"); ?>" data-target="#chat-users-tab"> <i class="fa fa-user  "></i></a>
                </li>
            <?php } ?>
                
            <?php if ($show_clients_list) { ?>
                <li class="box-content" id="chat-clients-tab-button">
                    <a role="presentation"  href="<?php echo_uri("messages/users_list/client"); ?>" data-target="#chat-clients-tab"><i class="fa fa-briefcase  "></i></a>
                </li>
            <?php } ?>

        </ul>
    </div>

</div>


<div class="rise-chat-search-box">
    <i class="ti ti-search rise-chat-search-icon"></i>
    <input type="text" id="rise-chat-search-input" class="rise-chat-search-input" placeholder="Cari karyawan atau chat..." autocomplete="off" />
    <button type="button" id="rise-chat-search-clear" class="rise-chat-search-clear hide" title="Clear">&times;</button>
</div>

<div class="rise-chat-body long clearfix">
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane fade" id="chat-inbox-tab">
            <?php $this->load->view("messages/chat/chat_list", array("messages" => $messages)); ?>
        </div>
        <div role="tabpanel" class="tab-pane fade" id="chat-users-tab"></div>
        <div role="tabpanel" class="tab-pane fade" id="chat-clients-tab"></div>
    </div>
    <div id="rise-chat-search-empty" class="chat-no-messages text-off text-center hide">
        <i class="ti ti-search-off" style="font-size:28px;display:block;margin-bottom:6px;opacity:.6;"></i>
        Tidak ditemukan
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        function filterRiseChatList() {
            var $input = $("#rise-chat-search-input");
            var q = $.trim(($input.val() || "").toLowerCase());
            var $clearBtn = $("#rise-chat-search-clear");
            var $empty = $("#rise-chat-search-empty");

            if (q.length > 0) {
                $clearBtn.removeClass("hide");
            } else {
                $clearBtn.addClass("hide");
            }

            var $activePane = $(".rise-chat-body .tab-pane.active");
            var $rows = $activePane.find(".message-row");

            if (!$rows.length) {
                $empty.addClass("hide");
                return;
            }

            var visibleCount = 0;
            $rows.each(function () {
                var text = ($(this).text() || "").toLowerCase();
                if (!q || text.indexOf(q) !== -1) {
                    $(this).show();
                    visibleCount++;
                } else {
                    $(this).hide();
                }
            });

            if (q && visibleCount === 0) {
                $empty.removeClass("hide");
            } else {
                $empty.addClass("hide");
            }
        }

        $("#rise-chat-search-input").on("input keyup", filterRiseChatList);

        $("#rise-chat-search-clear").on("click", function () {
            $("#rise-chat-search-input").val("").focus();
            filterRiseChatList();
        });

        $(".chat-tab a").on("click", function () {
            setTimeout(filterRiseChatList, 180);
            setTimeout(filterRiseChatList, 500);
        });
    });
</script>