<div class="box">
    <div class="box-content">
        <div id="timeline-content" class="clearfix p15 mb20">
            <?php $this->load->view("timeline/post_form"); ?>

            <div id="rise4-timeline-filter-bar" class="rise4-timeline-filter-bar">
                <div class="rise4-tl-search-wrap">
                    <i class="ti ti-search"></i>
                    <input type="text" id="rise4-tl-search" class="form-control" placeholder="Cari isi timeline (misal: Bank, Defect, Update)..." autocomplete="off" />
                </div>
                <select id="rise4-tl-month" class="form-control rise4-tl-select">
                    <option value="">Semua Bulan</option>
                    <option value="1">Januari</option>
                    <option value="2">Februari</option>
                    <option value="3">Maret</option>
                    <option value="4">April</option>
                    <option value="5">Mei</option>
                    <option value="6">Juni</option>
                    <option value="7">Juli</option>
                    <option value="8">Agustus</option>
                    <option value="9">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
                <select id="rise4-tl-year" class="form-control rise4-tl-select">
                    <option value="">Semua Tahun</option>
                    <?php
                    $cur_y = (int) date("Y");
                    for ($y = $cur_y; $y >= $cur_y - 6; $y--) {
                        echo "<option value='$y'>$y</option>";
                    }
                    ?>
                </select>
                <select id="rise4-tl-user" class="form-control rise4-tl-select">
                    <option value="">Semua Karyawan</option>
                    <option value="<?php echo $this->login_user->id; ?>"><?php echo htmlspecialchars($this->login_user->first_name . " " . $this->login_user->last_name); ?> (Saya)</option>
                    <?php if ($team_members) { ?>
                        <?php foreach ($team_members as $m) { ?>
                            <?php if ($m->id != $this->login_user->id) { ?>
                                <option value="<?php echo $m->id; ?>"><?php echo htmlspecialchars($m->first_name . " " . $m->last_name); ?></option>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                </select>
                <button type="button" id="rise4-tl-search-btn" class="btn btn-primary btn-sm"><i class="ti ti-search"></i> Cari</button>
                <button type="button" id="rise4-tl-reset-btn" class="btn btn-default btn-sm hide"><i class="ti ti-rotate-2"></i> Reset</button>
            </div>

            <div id="rise4-timeline-results">
                <?php timeline_widget(array("limit" => 20, "offset" => 0, "is_first_load" => true)); ?>
            </div>
        </div>
    </div>

    <?php if ($team_members) { ?>
        <div class="hidden-xs box-content bg-white" style="width: 250px; min-height: 100%;">
            <div id="user-list-container" >
                <div class="p15">
                    <?php
                    foreach ($team_members as $member) {
                        ?>
                        <div class="media">
                            <div class="media-left">
                                <span class="avatar avatar-xs">
                                    <img src="<?php echo get_avatar($member->image); ?>" alt="..." />
                                </span>
                            </div>
                            <div class="media-body clearfix w100p">
                                <div class="media-heading pull-left m0">
                                    <div class="w150"><?php echo get_team_member_profile_link($member->id, $member->first_name . " " . $member->last_name, array("class" => "dark")); ?></div>
                                    <small class="text-off w150 block"><?php echo $member->job_title; ?></small>
                                </div>
                                <div class="pull-right">
                                    <?php
                                    if (get_setting("module_message")) {
                                        echo modal_anchor(get_uri("messages/modal_form/" . $member->id), "<i class='fa fa-envelope-o'></i>", array("class" => "btn btn-default btn-xs round", "title" => lang('send_message')));
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php }
                    ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {


        setTimelineScrollable()
        $(window).resize(function () {
            setTimelineScrollable();
        });

        function runTimelineSearch() {
            var search = $.trim($("#rise4-tl-search").val() || "");
            var month = $("#rise4-tl-month").val() || "";
            var year = $("#rise4-tl-year").val() || "";
            var userId = $("#rise4-tl-user").length ? ($("#rise4-tl-user").val() || "") : "";

            if (search || month || year || userId) {
                $("#rise4-tl-reset-btn").removeClass("hide");
            } else {
                $("#rise4-tl-reset-btn").addClass("hide");
            }

            appLoader.show({container: "#rise4-timeline-results"});
            $.ajax({
                url: "<?php echo get_uri('timeline/search_posts'); ?>",
                type: "POST",
                data: {
                    search: search,
                    month: month,
                    year: year,
                    user_id: userId
                },
                success: function (html) {
                    $("#rise4-timeline-results").html(html);
                    appLoader.hide();
                },
                error: function () {
                    appLoader.hide();
                }
            });
        }

        $("#rise4-tl-month, #rise4-tl-year, #rise4-tl-user").select2();

        $("#rise4-tl-search-btn").on("click", runTimelineSearch);
        $("#rise4-tl-search").on("keypress", function (e) {
            if (e.which === 13) {
                e.preventDefault();
                runTimelineSearch();
            }
        });
        $("#rise4-tl-month, #rise4-tl-year, #rise4-tl-user").on("change", runTimelineSearch);
        $("#rise4-tl-reset-btn").on("click", function () {
            $("#rise4-tl-search").val("");
            $("#rise4-tl-month").select2("val", "");
            $("#rise4-tl-year").select2("val", "");
            if ($("#rise4-tl-user").length) $("#rise4-tl-user").select2("val", "");
            runTimelineSearch();
        });
    });


    setTimelineScrollable = function () {
        var options = {
            setHeight: $(window).height() - 65
        };

        initScrollbar('#user-list-container', options);

        //don't apply scrollbar for mobile devices
        if ($(window).width() <= 640) {
            $('#timeline-content').css({"overflow": "auto"});
        } else {
            options.callbacks = {
                onTotalScroll: function () {
                    if (!$(".load-more").hasClass("inline-loading")) {
                        $(".load-more").trigger("click"); //auto load more data
                    }
                }
            };
            initScrollbar('#timeline-content', options);
        }

    };
</script>
