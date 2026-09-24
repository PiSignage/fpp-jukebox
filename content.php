<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'common.php';

$pluginName = "fpp-jukebox";

$configFile = $settings['configDirectory']
    . '/plugin.' . $pluginName . '.json';

$config = array(
    'enabled' => true,
    'title' => 'Jukebox',
    'scheduleEnabled' => false,
    'schedules' => [
        [
            'start' => '18:00',
            'end' => '20:00'
        ]
    ],
    'lockoutSeconds' => 30,
    'lockoutStarts' => 'play',
    'backgroundSequence' => "",
    'sequences' => array()
);

if (file_exists($configFile)) {

    $saved = json_decode(
        file_get_contents($configFile),
        true
    );

    if (is_array($saved)) {
        $config = array_replace_recursive(
            $config,
            $saved
        );
    }
}


/*
 * --------------------------------------------------------------------------
 * Save configuration
 * --------------------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['saveJukebox'])
) {

    // Build schedules
    $schedules = array();

    $scheduleStarts =
        $_POST['scheduleStart'] ?? array();

    $scheduleEnds =
        $_POST['scheduleEnd'] ?? array();

    foreach (
        $scheduleStarts as $index => $start
    ) {
        $start = trim($start);
        $end = trim(
            $scheduleEnds[$index] ?? ''
        );

        // Ignore incomplete rows.
        if ($start === '' || $end === '') {
            continue;
        }

        // Basic time validation.
        $startValid =
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $start
            );

        $endValid =
            preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $end
            );

        if (!$startValid || !$endValid) {
            continue;
        }

        $schedules[] = array(
            'start' => $start,
            'end' => $end
        );
    }

    /*
     * If scheduling is enabled but no valid
     * schedules exist, create one default row.
     */

    if (
        isset($_POST['scheduleEnabled']) &&
        empty($schedules)
    ) {

        $schedules[] = array(
            'start' => '18:00',
            'end' => '23:00'
        );
    }

    $newConfig = array(
        'enabled' => isset($_POST['enabled']),
        'title' => trim(
            $_POST['jukeboxTitle'] ?? 'Jukebox'
        ),
        'scheduleEnabled' => isset($_POST['scheduleEnabled']),
        'schedules' => $schedules,
        'lockoutSeconds' => max(
            0,
            (int)($_POST['lockoutSeconds'] ?? 30)
        ),
        'lockoutStarts' => 'play',
        'backgroundSequence' => $_POST['backgroundSequence'],
        'sequences' => array()
    );

    $sequences = $_POST['sequence'] ?? array();
    $titles = $_POST['title'] ?? array();
    $enabled = $_POST['sequenceEnabled'] ?? array();
    $artwork = $_POST['artwork'] ?? array();
    $order = $_POST['order'] ?? array();

    foreach ($sequences as $index => $sequenceName) {

        $sequenceName = basename(
            trim($sequenceName)
        );

        if ($sequenceName === '') {
            continue;
        }

        $sequenceArtwork = '';

        if (
            isset($artwork[$index]) &&
            $artwork[$index] !== ''
        ) {

            $candidate = str_replace(
                '\\',
                '/',
                $artwork[$index]
            );

            /*
             * Artwork must remain inside jukebox/.
             */

            if (
                str_starts_with(
                    $candidate,
                    'images/'
                ) &&
                strpos($candidate, '..') === false
            ) {
                $sequenceArtwork = $candidate;
            }
        }

        $newConfig['sequences'][] = array(
            'sequence' => $sequenceName,
            'title' => trim(
                $titles[$index] ?? pathinfo(
                    $sequenceName,
                    PATHINFO_FILENAME
                )
            ),
            'enabled' => isset(
                $enabled[$index]
            ),
            'order' => count(
                $newConfig['sequences']
            ),
            'artwork' => $sequenceArtwork
        );
    }

    file_put_contents(
        $configFile,
        json_encode(
            $newConfig,
            JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES
        )
    );

    $config = $newConfig;

    $savedMessage = 'Jukebox settings saved.';
}


/*
 * --------------------------------------------------------------------------
 * Get available FPP sequences
 * --------------------------------------------------------------------------
 */

$sequenceResponse = @file_get_contents(
    'http://127.0.0.1/api/sequence'
);

$fppSequences = array();

if ($sequenceResponse !== false) {

    $decoded = json_decode(
        $sequenceResponse,
        true
    );

    if (is_array($decoded)) {
        $fppSequences = $decoded;
    }
}


/*
 * --------------------------------------------------------------------------
 * Get artwork from media/jukebox
 * --------------------------------------------------------------------------
 */

$artworkFiles = array();

$artworkDirectory =
    rtrim(
        $settings['mediaDirectory'],
        '/'
    ) . '/images';

if (is_dir($artworkDirectory)) {

    $allowedExtensions = array(
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp'
    );

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $artworkDirectory,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $file) {

        if (!$file->isFile()) {
            continue;
        }

        $extension = strtolower(
            $file->getExtension()
        );

        if (
            !in_array(
                $extension,
                $allowedExtensions
            )
        ) {
            continue;
        }

        $relative = substr(
            $file->getPathname(),
            strlen(
                rtrim(
                    $settings['mediaDirectory'],
                    '/'
                )
            ) + 1
        );

        $relative = str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            $relative
        );

        $artworkFiles[] = $relative;
    }
}

sort($artworkFiles);


/*
 * --------------------------------------------------------------------------
 * Merge FPP sequences with saved configuration
 * --------------------------------------------------------------------------
 */

$savedSequences = array();

foreach ($config['sequences'] as $sequence) {

    $savedSequences[$sequence['sequence']] = $sequence;
}

$sequenceRows = array();

$order = 0;

foreach ($fppSequences as $sequence) {

    /*
     * FPP may return sequence information in different formats.
     * We only need the filename/name here.
     */

    if (is_string($sequence)) {
        $sequenceName = basename($sequence);
    } else {
        $sequenceName =
            $sequence['sequence'] ??
            $sequence['name'] ??
            $sequence['file'] ??
            '';

        $sequenceName = basename(
            $sequenceName
        );
    }

    if ($sequenceName === '') {
        continue;
    }

    if (
        isset(
            $savedSequences[$sequenceName]
        )
    ) {

        $saved = $savedSequences[$sequenceName];

        $sequenceRows[] = array(
            'sequence' => $sequenceName,
            'title' => $saved['title'],
            'enabled' => !empty($saved['enabled']),
            'order' => (int)$saved['order'],
            'artwork' => $saved['artwork'] ?? ''
        );
    } else {

        $sequenceRows[] = array(
            'sequence' => $sequenceName,
            'title' => pathinfo(
                $sequenceName,
                PATHINFO_FILENAME
            ),
            'enabled' => false,
            'order' => 999999 + $order,
            'artwork' => ''
        );

        $order++;
    }
}


/*
 * Sort saved order.
 */

usort(
    $sequenceRows,
    function ($a, $b) {
        return $a['order'] <=> $b['order'];
    }
);

?>

<div class="container-fluid">

    <h2>Jukebox</h2>

    <?php if (!empty($savedMessage)): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($savedMessage) ?>
        </div>

    <?php endif; ?>


    <form method="post">

        <div class="card mb-3">

            <div class="card-header">
                General Settings
            </div>

            <div class="card-body">

                <!-- Enable Jukebox -->
                <div class="form-check form-switch mb-3">

                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="enabled"
                        id="jukeboxEnabled"
                        <?= !empty($config['enabled']) ? 'checked' : '' ?>>

                    <label
                        class="form-check-label"
                        for="jukeboxEnabled">
                        Enable Jukebox
                    </label>

                </div>

                <!-- Jukebox Title -->
                <div class="mb-3">

                    <label
                        for="jukeboxTitle"
                        class="form-label">
                        Jukebox Title
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="jukeboxTitle"
                        name="jukeboxTitle"
                        value="<?= htmlspecialchars(
                                    $config['title'] ?? 'Jukebox'
                                ) ?>"
                        maxlength="100">

                    <div class="form-text">
                        This title will be displayed at the top of the
                        jukebox touchscreen.
                    </div>

                </div>

                <!-- Schedule -->
                <div class="border rounded p-3 mb-3">
                    <div class="form-check form-switch mb-3">

                        <input
                            class="form-check-input"
                            type="checkbox"
                            name="scheduleEnabled"
                            id="scheduleEnabled"
                            <?= !empty($config['scheduleEnabled'])
                                ? 'checked'
                                : '' ?>>

                        <label
                            class="form-check-label"
                            for="scheduleEnabled">
                            Enable Schedule
                        </label>

                    </div>

                    <div id="scheduleContainer">
                        <?php
                        $schedules =
                            $config['schedules']
                            ?? array();

                        if (empty($schedules)) {
                            $schedules[] =
                                array(
                                    'start' => '18:00',
                                    'end' => '23:00'
                                );
                        }
                        ?>

                        <?php foreach (
                            $schedules
                            as $index => $schedule
                        ): ?>

                            <div class="schedule-row row g-2 align-items-end mb-2">
                                <div class="col-md-5">
                                    <label class="form-label">
                                        Start Time
                                    </label>

                                    <input
                                        type="time"
                                        class="form-control"
                                        name="scheduleStart[]"
                                        value="<?= htmlspecialchars(
                                                    $schedule['start']
                                                        ?? '18:00'
                                                ) ?>">
                                </div>

                                <div class="col-md-5">
                                    <label class="form-label">
                                        End Time
                                    </label>

                                    <input
                                        type="time"
                                        class="form-control"
                                        name="scheduleEnd[]"
                                        value="<?= htmlspecialchars(
                                                    $schedule['end']
                                                        ?? '23:00'
                                                ) ?>">
                                </div>

                                <div class="col-md-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-danger w-100 remove-schedule">
                                        Remove
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button
                        type="button"
                        class="btn btn-outline-primary mt-2"
                        id="addSchedule">
                        + Add Schedule
                    </button>

                    <div class="form-text mt-2">
                        The jukebox will be available
                        during any of the configured
                        time periods.
                    </div>
                </div>

                <!-- Lockout -->
                <div class="mb-3">

                    <label
                        for="lockoutSeconds"
                        class="form-label">
                        Lockout after sequence
                    </label>

                    <div class="input-group">

                        <input
                            type="number"
                            min="0"
                            class="form-control"
                            id="lockoutSeconds"
                            name="lockoutSeconds"
                            value="<?= htmlspecialchars(
                                        $config['lockoutSeconds']
                                    ) ?>">

                        <span class="input-group-text">
                            seconds
                        </span>

                    </div>

                    <div class="form-text">
                        Guests cannot select another sequence
                        until this time has elapsed after playback.
                    </div>

                </div>

                <!-- Background Sequence -->
                <div class="form-group">
                    <label for="backgroundSequence">
                        Background Sequence
                    </label>

                    <select
                        class="form-control"
                        id="backgroundSequence"
                        name="backgroundSequence">
                        <option value="">
                            None
                        </option>

                        <?php foreach ($fppSequences as $sequence): ?>

                            <option
                                value="<?= htmlspecialchars($sequence) ?>"
                                <?= (
                                    ($config['backgroundSequence'] ?? '') ===
                                    $sequence
                                ) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sequence) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                    <small class="form-text text-muted">
                        Select the sequence controlled by FPP when the jukebox
                        is not playing a guest selection.
                    </small>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>
                    Jukebox Sequences
                </span>
                <span class="text-muted">
                    Drag to reorder
                </span>
            </div>

            <div class="card-body">
                <div id="jukeboxSequenceList">

                    <?php foreach (
                        $sequenceRows as $index => $sequence
                    ): ?>

                        <div
                            class="jukebox-sequence-row card mb-2"
                            data-index="<?= $index ?>">

                            <input
                                type="hidden"
                                name="order[]"
                                value="<?= $index ?>"
                                class="sequence-order">

                            <input
                                type="hidden"
                                name="sequence[]"
                                value="<?= htmlspecialchars(
                                            $sequence['sequence']
                                        ) ?>">


                            <div class="card-body">

                                <div class="row align-items-center g-3">


                                    <div class="col-auto">

                                        <span
                                            class="jukebox-drag-handle"
                                            style="cursor:grab;font-size:24px;">
                                            ☰
                                        </span>

                                    </div>


                                    <div class="col-auto">

                                        <div
                                            class="jukebox-artwork-preview"
                                            style="
                                                width:80px;
                                                height:80px;
                                                overflow:hidden;
                                                border-radius:6px;
                                                background:#eee;
                                            ">

                                            <?php if (
                                                !empty($sequence['artwork'])
                                            ): ?>

                                                <img
                                                    src="/api/file/<?= htmlspecialchars(
                                                                        implode(
                                                                            '/',
                                                                            array_map(
                                                                                'rawurlencode',
                                                                                explode(
                                                                                    '/',
                                                                                    $sequence['artwork']
                                                                                )
                                                                            )
                                                                        )
                                                                    ) ?>"
                                                    style="
                                                        width:100%;
                                                        height:100%;
                                                        object-fit:cover;
                                                    ">

                                            <?php endif; ?>

                                        </div>

                                    </div>


                                    <div class="col-md-3">

                                        <strong>
                                            <?= htmlspecialchars(
                                                $sequence['sequence']
                                            ) ?>
                                        </strong>

                                        <input
                                            type="text"
                                            class="form-control mt-2"
                                            name="title[]"
                                            value="<?= htmlspecialchars(
                                                        $sequence['title']
                                                    ) ?>"
                                            placeholder="Display title">

                                    </div>


                                    <div class="col-md-4">

                                        <label class="form-label">
                                            Artwork
                                        </label>

                                        <select
                                            name="artwork[]"
                                            class="form-select artwork-select">

                                            <option value="">
                                                No artwork
                                            </option>

                                            <?php foreach (
                                                $artworkFiles as $artwork
                                            ): ?>

                                                <option
                                                    value="<?= htmlspecialchars(
                                                                $artwork
                                                            ) ?>"
                                                    <?= (
                                                        $sequence['artwork'] ===
                                                        $artwork
                                                    )
                                                        ? 'selected'
                                                        : ''
                                                    ?>>
                                                    <?= htmlspecialchars(
                                                        $artwork
                                                    ) ?>
                                                </option>

                                            <?php endforeach; ?>

                                        </select>

                                    </div>


                                    <div class="col-auto">

                                        <div class="form-check">

                                            <input
                                                type="checkbox"
                                                class="form-check-input"
                                                name="sequenceEnabled[<?= $index ?>]"
                                                value="1"
                                                <?= !empty($sequence['enabled'])
                                                    ? 'checked'
                                                    : ''
                                                ?>>

                                            <label class="form-check-label">
                                                Enabled
                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>


                <?php if (empty($artworkFiles)): ?>

                    <div class="alert alert-info mt-3">

                        No artwork found.

                        Upload your artwork using the
                        <strong>FPP File Manager</strong>
                        into:

                        <code>media/images/</code>

                    </div>

                <?php endif; ?>

            </div>

        </div>

        <div class="mt-3">
            <button
                type="submit"
                name="saveJukebox"
                class="btn btn-primary">
                Save Jukebox Settings
            </button>
        </div>

        <!-- Jukebox Statistics -->
        <div class="card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    Jukebox Statistics
                </span>

                <button
                    type="button"
                    class="btn btn-outline-danger btn-sm"
                    id="resetJukeboxStatistics">
                    Reset Statistics
                </button>
            </div>

            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <div
                                    class="text-muted"
                                    style="font-size:14px;">
                                    Total Plays
                                </div>

                                <div
                                    id="jukeboxTotalPlays"
                                    style="
                                font-size:32px;
                                font-weight:600;
                            ">
                                    0
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>
                                    Sequence
                                </th>

                                <th
                                    class="text-end">
                                    Plays
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            id="jukeboxStatisticsTable">
                            <tr>
                                <td colspan="2">
                                    Loading statistics...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>


<script>
    var pluginName = '<?php echo $pluginName; ?>';
    document.addEventListener(
        'DOMContentLoaded',
        function() {

            // Schedule controls
            const scheduleContainer =
                document.getElementById(
                    'scheduleContainer'
                );

            const addSchedule =
                document.getElementById(
                    'addSchedule'
                );

            const scheduleEnabled =
                document.getElementById(
                    'scheduleEnabled'
                );

            function updateScheduleVisibility() {
                if (
                    !scheduleContainer ||
                    !scheduleEnabled ||
                    !addSchedule
                ) {
                    return;
                }

                scheduleContainer.style.display =
                    scheduleEnabled.checked ? '' : 'none';

                addSchedule.disabled = !scheduleEnabled.checked;
            }

            updateScheduleVisibility();

            if (scheduleEnabled) {
                scheduleEnabled.addEventListener(
                    'change',
                    updateScheduleVisibility
                );
            }

            // Add schedule
            if (addSchedule) {
                addSchedule.addEventListener(
                    'click',
                    function() {
                        const row =
                            document.createElement('div');

                        row.className =
                            'schedule-row row g-2 align-items-end mb-2';

                        row.innerHTML = `
                            <div class="col-md-5">
                                <label class="form-label">
                                    Start Time
                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    name="scheduleStart[]"
                                    value="18:00"
                                >
                            </div>

                            <div class="col-md-5">
                                <label class="form-label">
                                    End Time
                                </label>

                                <input
                                    type="time"
                                    class="form-control"
                                    name="scheduleEnd[]"
                                    value="23:00"
                                >
                            </div>

                            <div class="col-md-2">
                                <button
                                    type="button"
                                    class="btn btn-outline-danger w-100 remove-schedule"
                                >
                                    Remove
                                </button>
                            </div>
                        `;

                        scheduleContainer.appendChild(row);
                        attachRemoveButton(row);
                    }
                );
            }

            // Remove schedule
            function attachRemoveButton(row) {
                const button =
                    row.querySelector(
                        '.remove-schedule'
                    );

                if (!button) {
                    return;
                }

                button.addEventListener(
                    'click',
                    function() {
                        const rows =
                            scheduleContainer.querySelectorAll(
                                '.schedule-row'
                            );

                        // Always keep at least one schedule row
                        if (rows.length <= 1) {
                            return;
                        }

                        row.remove();
                    }
                );
            }

            document.querySelectorAll(
                    '.schedule-row'
                )
                .forEach(
                    attachRemoveButton
                );

            // Sequence sorting
            const list =
                document.getElementById(
                    'jukeboxSequenceList'
                );

            if (!list) {
                return;
            }

            let draggedRow = null;

            const rows = list.querySelectorAll(
                '.jukebox-sequence-row'
            );

            rows.forEach(
                function(row) {
                    row.setAttribute(
                        'draggable',
                        'true'
                    );

                    // Start dragging
                    row.addEventListener(
                        'dragstart',
                        function(event) {
                            draggedRow = row;

                            row.classList.add(
                                'jukebox-dragging'
                            );

                            event.dataTransfer.effectAllowed =
                                'move';
                        }
                    );

                    // Stop dragging
                    row.addEventListener(
                        'dragend',
                        function() {
                            row.classList.remove(
                                'jukebox-dragging'
                            );

                            draggedRow = null;

                            updateSequenceOrder();
                        }
                    );

                    // Allow dropping
                    row.addEventListener(
                        'dragover',
                        function(event) {
                            event.preventDefault();
                            if (
                                !draggedRow ||
                                draggedRow === row
                            ) {
                                return;
                            }

                            const rect =
                                row.getBoundingClientRect();

                            const middle =
                                rect.top +
                                (
                                    rect.height / 2
                                );

                            if (
                                event.clientY <
                                middle
                            ) {
                                list.insertBefore(
                                    draggedRow,
                                    row
                                );
                            } else {
                                list.insertBefore(
                                    draggedRow,
                                    row.nextSibling
                                );
                            }
                        }
                    );
                }
            );

            // Update sequence
            function updateSequenceOrder() {
                const rows =
                    list.querySelectorAll(
                        '.jukebox-sequence-row'
                    );

                rows.forEach(
                    function(row, index) {

                        const order =
                            row.querySelector(
                                '.sequence-order'
                            );

                        if (order) {
                            order.value = index;
                        }

                    }
                );
            }

            // Artwork preview
            document
                .querySelectorAll(
                    '.artwork-select'
                )
                .forEach(
                    function(select) {

                        select.addEventListener(
                            'change',
                            function() {

                                const row =
                                    select.closest(
                                        '.jukebox-sequence-row'
                                    );

                                const preview =
                                    row.querySelector(
                                        '.jukebox-artwork-preview'
                                    );

                                const value =
                                    select.value;

                                if (!value) {

                                    preview.innerHTML = '';

                                    return;
                                }

                                const parts =
                                    value.split('/');

                                const encoded =
                                    parts
                                    .map(
                                        part =>
                                        encodeURIComponent(
                                            part
                                        )
                                    )
                                    .join('/');

                                preview.innerHTML =
                                    '<img src="/api/file/' +
                                    encoded +
                                    '" style="' +
                                    'width:100%;' +
                                    'height:100%;' +
                                    'object-fit:cover;' +
                                    '">';
                            }
                        );

                    }
                );

            // Jukebox Statistics
            loadJukeboxStatistics();
            async function loadJukeboxStatistics() {
                const totalPlays =
                    document.getElementById(
                        'jukeboxTotalPlays'
                    );

                const table =
                    document.getElementById(
                        'jukeboxStatisticsTable'
                    );

                if (!totalPlays || !table) {
                    return;
                }

                try {
                    const response =
                        await fetch(
                            `/api/plugin/${pluginName}/statistics`, {
                                cache: 'no-store'
                            }
                        );

                    if (!response.ok) {
                        throw new Error(
                            'Unable to load statistics.'
                        );
                    }

                    const data =
                        await response.json();

                    if (!data.success) {
                        throw new Error(
                            'Unable to load statistics.'
                        );
                    }


                    /*
                     * Total plays.
                     */

                    totalPlays.textContent =
                        data.totalPlays || 0;


                    /*
                     * Clear existing rows.
                     */

                    table.innerHTML = '';


                    const sequences =
                        data.sequences || {};


                    const entries =
                        Object.entries(
                            sequences
                        );

                    entries.sort(
                        function(a, b) {
                            return Number(b[1]) - Number(a[1]);
                        }
                    );


                    /*
                     * No plays yet.
                     */

                    if (entries.length === 0) {

                        table.innerHTML = `
                            <tr>
                                <td colspan="2">
                                    No sequences have been played yet.
                                </td>
                            </tr>
                        `;

                        return;
                    }


                    /*
                     * Match statistics against the
                     * configured sequence list.
                     */

                    entries.forEach(
                        function(
                            [sequenceName, plays]
                        ) {

                            const configuredSequence =
                                findConfiguredSequence(
                                    sequenceName
                                );


                            const title =
                                configuredSequence ?
                                configuredSequence.title :
                                sequenceName;


                            const row =
                                document.createElement(
                                    'tr'
                                );


                            row.innerHTML = `
                    <td>
                        ${escapeHtml(title)}
                    </td>

                    <td class="text-end">
                        ${Number(plays) || 0}
                    </td>
                `;


                            table.appendChild(
                                row
                            );

                        }
                    );
                } catch (error) {
                    console.error(
                        'Unable to load jukebox statistics:',
                        error
                    );

                    table.innerHTML = `
                        <tr>
                            <td colspan="2" class="text-danger">
                                Unable to load statistics.
                            </td>
                        </tr>
                    `;
                }
            }

            const resetStatisticsButton =
                document.getElementById(
                    'resetJukeboxStatistics'
                );

            if (resetStatisticsButton) {
                resetStatisticsButton.addEventListener(
                    'click',
                    async function() {
                        const confirmed =
                            confirm(
                                "Are your sure you want to reset all jukebox statistics?"
                            );

                        if (!confirmed) {
                            return;
                        }

                        resetStatisticsButton.disabled = true;

                        try {
                            const response =
                                await fetch(
                                    `/api/plugin/${pluginName}/statistics/reset`, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        cache: 'no-store'
                                    }
                                );

                            const data = await response.json();

                            if (!response.ok || !data.success) {
                                throw new Error(
                                    data.message ||
                                    'Unable to reset statistics.'
                                );
                            }

                            // Refresh the statistics display
                            await loadJukeboxStatistics();
                        } catch (error) {
                            console.error(
                                'Unable to reset jukebox statistics:',
                                error
                            );

                            alert(
                                error.message ||
                                'Unable to reset statistics.'
                            );
                        } finally {
                            resetStatisticsButton.disabled = false;
                        }
                    }
                );
            }
        }
    );
</script>