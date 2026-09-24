<?php

$configFile =
    __DIR__ . '/../../config/plugin.fpp-jukebox.json';

$config = array(
    'enabled' => true,
    'lockoutSeconds' => 30,
    'sequences' => array()
);

if (
    file_exists($configFile)
) {

    $saved =
        json_decode(
            file_get_contents($configFile),
            true
        );

    if (
        is_array($saved)
    ) {

        $config =
            array_replace_recursive(
                $config,
                $saved
            );
    }
}

?>

<div class="container-fluid">

    <h2 class="mb-4">
        Jukebox Status
    </h2>


    <div class="row g-3">


        <div class="col-md-4">

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title">
                        Status
                    </h5>

                    <p class="mb-0">

                        <?php if (
                            $config['enabled']
                        ): ?>

                            <span
                                class="badge bg-success">
                                Enabled
                            </span>

                        <?php else: ?>

                            <span
                                class="badge bg-secondary">
                                Disabled
                            </span>

                        <?php endif; ?>

                    </p>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title">
                        Available Sequences
                    </h5>

                    <div
                        class="display-6">

                        <?php

                        $enabled =
                            array_filter(
                                $config['sequences'],
                                function ($sequence) {
                                    return !empty($sequence['enabled']);
                                }
                            );

                        echo count($enabled);

                        ?>

                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title">
                        Lockout
                    </h5>

                    <div
                        class="display-6">
                        <?= (int)
                        $config['lockoutSeconds'] ?>
                        <small class="fs-6">
                            seconds
                        </small>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>