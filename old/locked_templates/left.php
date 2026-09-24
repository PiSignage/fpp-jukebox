<div class="container col-xxl-8 px-4 py-5">
    <div class="row align-items-center g-5 py-5">
        <div class="col-10 col-sm-8 col-lg-6"> <img
                src="<?php echo $base_url; ?>/api/file/Images/<?php echo $pluginJson['locked_show_logo'] ?>"
                class="d-block mx-lg-auto img-fluid" alt="Show Logo" loading="lazy">
        </div>
        <div class="col-lg-6">
            <h1 class="display-5 fw-bold text-body-emphasis lh-1 mb-3">Welcome To
                <?php echo $pluginJson['locked_show_name'] ?? 'NOTHING SET'; ?>
            </h1>
            <p class="lead"><?php echo $pluginJson['locked_additional_info'] ?? 'NOTHING SET'; ?></p>
            <?php if ($pluginJson['qr_code'] != '') { ?>
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mb-4">
                    <a href="plugin.php?_menu=status&plugin=fpp-jukebox&page=donate.php&nopage=1"
                        class="btn btn-outline-secondary btn-lg px-4">Donation Information</a>
                </div>
            <?php } ?>
            <p class="lead" id="clock"></p>
        </div>
    </div>
</div>