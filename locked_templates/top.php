<div class="px-4 py-5 my-5 text-center">
    <div class="container px-5">
        <img class="img-fluid mb-4"
            src="<?php echo $base_url; ?>/api/file/Images/<?php echo $pluginJson['locked_show_logo'] ?>" alt="Show Logo"
            loading="lazy">
    </div>
    <h1 class="display-5 fw-bold text-body-emphasis">Welcome To
        <?php echo $pluginJson['locked_show_name'] ?? 'NOTHING SET'; ?>
    </h1>
    <div class="col-lg-12 mx-auto">
        <p class="lead mb-4"><?php echo $pluginJson['locked_additional_info'] ?? 'NOTHING SET'; ?></p>
        <?php if ($pluginJson['qr_code'] != '') { ?>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mb-4">
                <a href="plugin.php?_menu=status&plugin=fpp-jukebox&page=donate.php&nopage=1"
                    class="btn btn-outline-secondary btn-lg px-4">Donation Information</a>
            </div>
        <?php } ?>
        <p class="lead" id="clock"></p>
    </div>
</div>