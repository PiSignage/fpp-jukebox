<div class="row">
    <div class="col-4">
        <img src="/api/file/Images/<?php echo $pluginJson['locked_show_logo'] ?>" alt="" class="img-fluid">
    </div>
    <div class="col-8">
        <h1 class="cover-heading">Welcome To <?php echo $pluginJson['locked_show_name'] ?? 'NOTHING SET'; ?></h1>
        <p class="lead"><?php echo $pluginJson['locked_additional_info'] ?? 'NOTHING SET'; ?></p>
        <?php if ($pluginJson['qr_code'] != '') { ?>
            <p class="lead">
                <a href="plugin.php?_menu=status&plugin=fpp-jukebox&page=donate.php&nopage=1"
                    class="btn btn-lg btn-secondary">Donation Information</a>
            </p>
        <?php } ?>
    </div>
</div>
<h3 id="clock"></h3>