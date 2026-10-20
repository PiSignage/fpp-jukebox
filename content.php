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
    'queueEnabled' => false,
    'queueLimit' => 5,
    'allowDuplicateQueueSongs' => false,
    'contentType' => "sequence",
    'backgroundType' => "sequence",
    'backgroundSequence' => "",
    'clockFormat' => "24",
    'showSongProgress' => true,
    'qrcode' => [
        'enabled' => false,
        'buttonText' => 'Support Our Charity',
        'title' => 'Support Our Charity',
        'url' => '',
        'message' => 'Scan the QR code with your phone to visit our page.',
    ],
    'theme' => [
        'preset' => 'default',
        'background' => '#111111',
        'cardBackground' => '#222222',
        'panelBackground' => '#252525',
        'text' => '#ffffff',
        'accent' => '#ffffff',
        'accentText' => '#000000',
    ],
    'sequences' => array(),
);

$themePresets = [
    'default' => [
        'name' => 'Default Dark',
        'background' => '#111111',
        'cardBackground' => '#222222',
        'text' => '#ffffff',
        'accent' => '#ffffff',
        'accentText' => '#000000'
    ],

    'christmas' => [
        'name' => 'Christmas',
        'background' => '#10251a',
        'cardBackground' => '#214331',
        'text' => '#ffffff',
        'accent' => '#e53935',
        'accentText' => '#ffffff'
    ],

    'ocean' => [
        'name' => 'Ocean Blue',
        'background' => '#0b192c',
        'cardBackground' => '#193653',
        'text' => '#ffffff',
        'accent' => '#38bdf8',
        'accentText' => '#000000'
    ],

    'purple' => [
        'name' => 'Purple',
        'background' => '#1e1429',
        'cardBackground' => '#39264a',
        'text' => '#ffffff',
        'accent' => '#c084fc',
        'accentText' => '#000000'
    ],
    'halloween' => [
        'name' => 'Halloween',
        'background' => '#160D20',
        'cardBackground' => '#2D193D',
        'text' => '#FFFFFF',
        'accent' => '#FF7518',
        'accentText' => '#000000'
    ],
];

function validateThemeColour($value, $default)
{
    if (
        is_string($value) &&
        preg_match('/^#[0-9a-fA-F]{6}$/', $value)
    ) {
        return strtolower($value);
    }

    return $default;
}

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

$contentType =
    $config['contentType']
    ?? 'sequence';

$contentLabel =
    $contentType === 'video'
    ? 'Videos'
    : 'Sequences';

$contentLabelSingular =
    $contentType === 'video'
    ? 'Video'
    : 'Sequence';

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

    // Theme configuration //
    // Selected theme preset
    $selectedPreset = $_POST['themePreset'] ?? 'default';

    // Available presets
    $allowedPresets = array(
        'default',
        'christmas',
        'halloween',
        'ocean',
        'purple',
        'custom'
    );

    if (
        !is_string($selectedPreset) ||
        !in_array($selectedPreset, $allowedPresets, true)
    ) {
        $selectedPreset = 'default';
    }

    // Default custom colours
    $defaultThemeColours = array(
        'background' => '#111111',
        'cardBackground' => '#222222',
        'text' => '#ffffff',
        'accent' => '#ffffff',
        'accentText' => '#000000'
    );

    // Preserve existing custom colours.
    $existingTheme = $config['theme'] ?? array();

    if (!is_array($existingTheme)) {
        $existingTheme = array();
    }

    // Read submitted custom colours.
    $submittedTheme = $_POST['theme'] ?? array();

    if (!is_array($submittedTheme)) {
        $submittedTheme = array();
    }

    $themeColours = array();

    foreach ($defaultThemeColours as $key => $defaultColour) {
        // Previous saved colour, or default
        $previousColour = $existingTheme[$key] ?? $defaultColour;

        if (
            !is_string($previousColour) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/', $previousColour)
        ) {
            $previousColour = $defaultColour;
        }

        // New submitted colour
        $colour = $submittedTheme[$key] ?? $previousColour;

        if (
            !is_string($colour) ||
            !preg_match('/^#[0-9a-fA-F]{6}$/', $colour)
        ) {
            $colour = $previousColour;
        }

        $themeColours[$key] = strtolower($colour);
    }

    // Build nested theme configuration.
    $themeConfig = array_merge(
        array(
            'preset' => $selectedPreset
        ),
        $themeColours
    );

    $qrcodeConfig = array(
        'enabled' => isset($_POST['qrEnabled']),
        'buttonText' => trim($_POST['qrButtonText'] ?? 'Support Our Charity'),
        'title' => trim($_POST['qrTitle'] ?? 'Support Our Charity'),
        'url' => trim($_POST['qrUrl'] ?? ''),
        'message' => trim(
            $_POST['qrMessage']
                ?? 'Scan the QR code with your phone to visit our page.'
        )
    );

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
        'queueEnabled' => isset($_POST['queueEnabled']),
        'queueLimit' => max(
            1,
            (int)($_POST['queueLimit'] ?? 5)
        ),
        'allowDuplicateQueueSongs' => isset($_POST['allowDuplicateQueueSongs']),
        'lockoutStarts' => 'play',
        'contentType' => $_POST['contentType'] ?? 'sequence',
        'backgroundType' => $_POST['backgroundType'] ?? 'sequence',
        'backgroundSequence' => $_POST['backgroundSequence'] ?? '',
        'clockFormat' =>
        in_array(
            $_POST['clockFormat'] ?? '24',
            array('12', '24'),
            true
        )
            ? $_POST['clockFormat']
            : '24',
        'showSongProgress' => isset($_POST['showSongProgress']),
        'qrcode' => $qrcodeConfig,
        'theme' => $themeConfig,
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

// Get available Jukebox content
$fppSequences = array();

$contentType =
    $config['contentType']
    ?? 'sequence';

if ($contentType === 'video') {
    // Load FPP videos
    $videoResponse = @file_get_contents(
        'http://127.0.0.1/api/files/videos'
    );

    if ($videoResponse !== false) {
        $decoded =
            json_decode(
                $videoResponse,
                true
            );

        if (
            is_array($decoded) &&
            ($decoded['status'] ?? '') === 'ok' &&
            isset($decoded['files']) &&
            is_array($decoded['files'])
        ) {
            foreach ($decoded['files'] as $video) {
                if (empty($video['name'])) {
                    continue;
                }

                $fppSequences[] = $video['name'];
            }
        }
    }
} else {
    // Load FPP sequences.
    $sequenceResponse = @file_get_contents(
        'http://127.0.0.1/api/sequence'
    );

    if ($sequenceResponse !== false) {
        $decoded = json_decode(
            $sequenceResponse,
            true
        );

        if (is_array($decoded)) {
            $fppSequences = $decoded;
        }
    }
}

$playlistResponse = @file_get_contents(
    'http://127.0.0.1/api/playlists'
);

$fppPlaylists = array();
if ($playlistResponse !== false) {
    $decoded = json_decode(
        $playlistResponse,
        true
    );

    if (is_array($decoded)) {
        $fppPlaylists = $decoded;
    }
}

/*
 * --------------------------------------------------------------------------
 * Get available FPP sequences
 * --------------------------------------------------------------------------
 */

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

<style>
    .sled-btn {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .35rem .8rem;
        font-size: .875rem;
        font-weight: 500;
        line-height: 1.5;
        text-align: center;
        white-space: nowrap;
        cursor: pointer;
        border: 1px solid #3a7fc1;
        border-radius: .3rem;
        text-decoration: none !important;
        background-color: #1a6eb5;
        color: #fff !important;
        transition: background-color .15s ease-in-out, border-color .15s;
        vertical-align: middle;
    }

    .sled-btn:hover,
    .sled-btn:focus {
        background-color: #155a94;
        border-color: #0e4370;
        color: #fff !important;
        text-decoration: none !important;
    }

    .sled-btn:disabled,
    .sled-btn.disabled {
        opacity: .55;
        cursor: not-allowed;
        pointer-events: none;
    }

    .sled-btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }

    .sled-btn-secondary:hover,
    .sled-btn-secondary:focus {
        background-color: #5c636a;
        border-color: #565e64;
    }

    .sled-btn-sm {
        padding: .2rem .5rem;
        font-size: .8rem;
        border-radius: .25rem;
    }

    /* Inline add/remove row buttons — slightly muted */
    .sled-btn-row {
        background-color: #455a72;
        border-color: #556880;
    }

    .sled-btn-row:hover {
        background-color: #344657;
        border-color: #344657;
    }

    @media (max-width: 640px) {

        /* Header: wrap onto two lines — title+pill first, donate buttons second */
        .sled-page-header {
            flex-wrap: wrap !important;
            row-gap: .5rem;
        }

        .sled-page-header>div:first-child {
            flex: 1 1 100%;
            min-width: 0;
        }

        .sled-page-header h2 {
            font-size: 1.05rem;
        }

        .sled-donate-row {
            flex-wrap: wrap;
            width: 100%;
        }
    }

    .theme-preview {
        --preview-background: #111111;
        --preview-card: #222222;
        --preview-text: #ffffff;
        --preview-accent: #ffffff;
        --preview-accent-text: #000000;

        background: var(--preview-background);
        color: var(--preview-text);

        padding: 25px;
        border-radius: 15px;
        max-width: 600px;

        transition: background 0.2s ease;
    }

    .theme-preview-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        font-size: 1.4rem;
    }

    .theme-preview-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }

    .theme-preview-card {
        min-height: 85px;
        padding: 15px;
        display: flex;
        align-items: flex-end;

        background: var(--preview-card);
        color: var(--preview-text);
        border-radius: 12px;
        font-weight: 600;
    }

    .theme-preview-panel {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 15px;
        margin-bottom: 20px;
        border-radius: 12px;

        background: color-mix(in srgb,
                var(--preview-card) 35%,
                transparent);
    }

    .theme-preview-indicator {
        width: 10px;
        height: 10px;
        flex-shrink: 0;
        border-radius: 50%;
        background: var(--preview-accent);
    }

    .theme-preview-button {
        padding: 12px 20px;
        border-radius: 10px;
        text-align: center;
        font-weight: 700;

        background: var(--preview-accent);
        color: var(--preview-accent-text);
    }
</style>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-2 sled-page-header">
        <h2 class="mb-0">
            Jukebox
        </h2>
        <div class="d-flex align-items-center gap-2 sled-donate-row">
            <a href="https://fpp-zettle.co.uk/docs/Jukebox" target="_blank" rel="noopener noreferrer"
                class="sled-btn">
                <i class="fas fa-fw fa-tower-broadcast"></i> Online Docs
            </a>
            <a href="https://github.com/PiSignage/fpp-jukebox" target="_blank" rel="noopener noreferrer"
                class="sled-btn">
                <i class="fas fa-fw fa-code-branch"></i> GitHub Repo
            </a>
            <a href="https://github.com/PiSignage/fpp-jukebox/issues" target="_blank" rel="noopener noreferrer"
                class="sled-btn sled-btn-secondary">
                <i class="fas fa-fw fa-bug"></i> Report an Issue
            </a>
        </div>
    </div>

    <?php if (!empty($savedMessage)): ?>

        <div class="alert alert-success">
            <?= htmlspecialchars($savedMessage) ?>
        </div>

    <?php endif; ?>

    <p class="text-muted">
        Upload your images via <strong>Content Setup → File Manager</strong>, then select them below.
    </p>

    <p>
        Quick Jumps: <a href="#theme">Theme</a> - <a href="#current-queue">Current Queue</a> - <a href="#statistics">Statistics</a>
    </p>

    <p>
        Interface Url: <a href="plugin.php?plugin=fpp-jukebox&page=www/index.php&nopage=1" target="_blank">plugin.php?plugin=fpp-jukebox&page=www/index.php&nopage=1</a>
    </p>

    <form method="post">

        <div class="card mb-3">

            <div class="card-header">
                General Settings
            </div>

            <div class="card-body">

                <!-- Enable Jukebox -->
                <div class="form-check mb-3">

                    <input
                        class="form-check-input mr-2"
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
                    <div class="form-check mb-3">

                        <input
                            class="form-check-input mr-2"
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
                <div class="form-group">

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
                        Guests cannot select another
                        <?= strtolower(
                            htmlspecialchars(
                                $contentLabelSingular
                            )
                        ) ?>
                        until this time has elapsed after playback.
                    </div>

                </div>

                <!-- Content Type -->
                <div class="form-group">
                    <label for="contentType">
                        Content Type
                    </label>

                    <select
                        name="contentType"
                        id="contentType"
                        class="form-control">

                        <option value="sequence"
                            <?= ($config['contentType'] ?? 'sequence') === 'sequence'
                                ? 'selected'
                                : '' ?>>
                            Sequences
                        </option>

                        <option value="video"
                            <?= ($config['contentType'] ?? 'video') === 'video'
                                ? 'selected'
                                : '' ?>>
                            Videos
                        </option>

                    </select>
                </div>

                <!-- Background Type -->
                <div class="form-group">
                    <label for="backgroundType">
                        Background Type
                    </label>

                    <select
                        id="backgroundType"
                        name="backgroundType"
                        class=" form-control">
                        <option
                            value="sequence"
                            <?= ($config['backgroundType'] ?? 'sequence') === 'sequence'
                                ? 'selected'
                                : '' ?>>
                            Sequence
                        </option>

                        <option
                            value="playlist"
                            <?= ($config['backgroundType'] ?? 'playlist') === 'playlist'
                                ? 'selected'
                                : '' ?>>
                            Playlist
                        </option>

                        <option
                            value="video"
                            <?= ($config['backgroundType'] ?? 'video') === 'video'
                                ? 'selected'
                                : '' ?>>
                            Video
                        </option>
                    </select>
                </div>

                <!-- Background Item -->
                <div class="form-group">
                    <label for="backgroundSequence">
                        Background
                    </label>

                    <select
                        id="backgroundSequence"
                        name="backgroundSequence"
                        class="form-control">
                        <option value="">
                            None
                        </option>
                    </select>

                    <small class="form-text text-muted">
                        Select the sequence/playlist controlled by FPP when the jukebox
                        is not playing a guest selection.
                    </small>
                </div>

                <!-- Enable Queuing -->
                <div class="mb-3">
                    <div class="form-check">
                        <input
                            class="form-check-input mr-2"
                            type="checkbox"
                            name="queueEnabled"
                            id="queueEnabled"
                            <?= !empty($config['queueEnabled']) ? 'checked' : '' ?>>

                        <label
                            class="form-check-label"
                            for="queueEnabled">
                            Enable Queue
                        </label>
                    </div>

                    <div class="form-text">
                        Allow guests to select additional
                        <?= strtolower(
                            htmlspecialchars(
                                $contentLabel
                            )
                        ) ?>
                        while another
                        <?= strtolower(
                            htmlspecialchars(
                                $contentLabelSingular
                            )
                        ) ?>
                        is playing.
                    </div>
                </div>

                <!-- Queue Limit -->
                <div class="mb-3">
                    <label for="queueLimit" class="form-label">
                        Queue Limit
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="queueLimit"
                        name="queueLimit"
                        min="1"
                        max="100"
                        value="<?= htmlspecialchars(
                                    $config['queueLimit'] ?? 'Jukebox'
                                ) ?>">

                    <div class="form-text">
                        Maximum number of sequences that can wait
                        in the queue.
                    </div>
                </div>

                <!-- allowDuplicateQueueSongs -->
                <div class="mb-3">
                    <div class="form-check">
                        <input
                            class="form-check-input mr-2"
                            type="checkbox"
                            name="allowDuplicateQueueSongs"
                            id="allowDuplicateQueueSongs"
                            value="1"
                            <?= !empty($config['allowDuplicateQueueSongs']) ? 'checked' : '' ?>>

                        <label
                            class="form-check-label"
                            for="allowDuplicateQueueSongs">
                            Allow duplicate songs in queue
                        </label>
                    </div>

                    <div class="form-text">
                        Allow the same sequence to be added to the queue more than once.
                    </div>
                </div>

                <!-- Clock format -->
                <div class="form-group">
                    <label for="clockFormat">
                        Clock Format
                    </label>

                    <select
                        name="clockFormat"
                        id="clockFormat"
                        class="form-control">

                        <option
                            value="24"
                            <?= ($config['clockFormat'] ?? '24') === '24'
                                ? 'selected'
                                : '' ?>>
                            24-hour (14:35)
                        </option>

                        <option
                            value="12"
                            <?= ($config['clockFormat'] ?? '24') === '12'
                                ? 'selected'
                                : '' ?>>
                            12-hour (2:35 PM)
                        </option>

                    </select>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input
                            class="form-check-input mr-2"
                            type="checkbox"
                            name="showSongProgress"
                            id="showSongProgress"
                            <?= !empty($config['showSongProgress']) ? 'checked' : '' ?>>

                        <label
                            class="form-check-label"
                            for="showSongProgress">
                            Show Song Progress
                        </label>
                    </div>

                    <div class="form-text">
                        Show progress bar and elapsed/total time.
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                QR Code
            </div>

            <div class="card-body">
                <div class="form-check mb-3">
                    <input
                        class="form-check-input mr-2"
                        type="checkbox"
                        name="qrEnabled"
                        id="qrEnabled"
                        <?= !empty($config['qrcode']['enabled'])
                            ? 'checked'
                            : '' ?>>

                    <label
                        class="form-check-label"
                        for="qrEnabled">
                        Enable QR Code
                    </label>
                </div>

                <div class="mb-3">
                    <label
                        for="qrButtonText"
                        class="form-label">
                        Button Text
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="qrButtonText"
                        name="qrButtonText"
                        value="<?= htmlspecialchars(
                                    $config['qrcode']['buttonText']
                                        ?? 'Support Our Charity'
                                ) ?>">
                </div>

                <div class="mb-3">
                    <label
                        for="qrTitle"
                        class="form-label">
                        Modal Title
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="qrTitle"
                        name="qrTitle"
                        value="<?= htmlspecialchars(
                                    $config['qrcode']['title']
                                        ?? 'Support Our Charity'
                                ) ?>">
                </div>

                <div class="mb-3">
                    <label
                        for="qrUrl"
                        class="form-label">
                        QR Code URL
                    </label>

                    <input
                        type="url"
                        class="form-control"
                        id="qrUrl"
                        name="qrUrl"
                        placeholder="https://www.justgiving.com/..."
                        value="<?= htmlspecialchars(
                                    $config['qrcode']['url']
                                        ?? ''
                                ) ?>">
                </div>

                <div class="mb-3">
                    <label
                        for="qrMessage"
                        class="form-label">
                        Message
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="qrMessage"
                        name="qrMessage"
                        value="<?= htmlspecialchars(
                                    $config['qrcode']['message']
                                        ?? 'Scan the QR code with your phone to visit our page.'
                                ) ?>">
                </div>
            </div>
        </div>

        <div id="theme" class="card mb-3">
            <div class="card-header">
                Theme
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label for="themePreset">Jukebox Theme</label>

                            <select
                                name="themePreset"
                                id="themePreset"
                                class="form-control">
                                <?php foreach ($themePresets as $key => $theme): ?>
                                    <option
                                        value="<?= htmlspecialchars($key) ?>"
                                        <?= ($config['theme']['preset'] ?? 'default') === $key
                                            ? 'selected'
                                            : '' ?>>
                                        <?= htmlspecialchars($theme['name']) ?>
                                    </option>
                                <?php endforeach; ?>

                                <option
                                    value="custom"
                                    <?= ($config['theme']['preset'] ?? 'default') === 'custom'
                                        ? 'selected'
                                        : '' ?>>
                                    Custom Colours
                                </option>
                            </select>

                            <small class="form-text text-muted">
                                Choose a preset theme or customise the jukebox colours.
                            </small>
                        </div>

                        <div
                            id="customThemeColours"
                            style="<?= ($config['theme']['preset'] ?? 'default') === 'custom'
                                        ? ''
                                        : 'display: none;' ?>">
                            <h4>Custom Theme Colours</h4>

                            <?php
                            $themeColourFields = [
                                'background' => 'Background Colour',
                                'cardBackground' => 'Card Background',
                                'text' => 'Text Colour',
                                'accent' => 'Accent Colour',
                                'accentText' => 'Accent Text Colour',
                            ];
                            ?>

                            <?php foreach ($themeColourFields as $key => $label): ?>

                                <div class="form-group">
                                    <label for="theme_<?= $key ?>">
                                        <?= htmlspecialchars($label) ?>
                                    </label>

                                    <input
                                        type="color"
                                        class="form-control"
                                        id="theme_<?= $key ?>"
                                        name="theme[<?= $key ?>]"
                                        value="<?= htmlspecialchars(
                                                    $config['theme'][$key] ?? '#ffffff',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                        style="width: 100px; height: 45px; padding: 5px; border: 1px solid black;">
                                </div>

                            <?php endforeach; ?>

                            <div class="form-group mt-3">
                                <button
                                    type="button"
                                    id="resetCustomThemeColours"
                                    class="btn btn-secondary">
                                    Reset Custom Colours
                                </button>

                                <small class="form-text text-muted">
                                    Restore the default colours.
                                    Changes are only saved when you click Save Settings.
                                </small>
                            </div>

                        </div>
                    </div>

                    <div class="col-6">
                        <div class="form-group">
                            <label>Theme Preview</label>

                            <div id="themePreview" class="theme-preview">

                                <div class="theme-preview-header">
                                    <strong>Jukebox</strong>
                                    <span>14:35</span>
                                </div>

                                <div class="theme-preview-grid">
                                    <div class="theme-preview-card">Song One</div>
                                    <div class="theme-preview-card">Song Two</div>
                                    <div class="theme-preview-card">Song Three</div>
                                    <div class="theme-preview-card">Song Four</div>
                                </div>

                                <div class="theme-preview-panel">
                                    <span class="theme-preview-indicator"></span>
                                    <span>Now Playing: Song One</span>
                                </div>

                                <div class="theme-preview-button">
                                    Select Song
                                </div>

                            </div>

                            <small class="form-text text-muted">
                                Preview updates automatically. Save settings to apply
                                the theme to the jukebox.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span>
                    Jukebox <?= htmlspecialchars(
                                $contentLabel
                            ) ?>
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
                                        <label class="form-check-label">
                                            Enabled
                                        </label>

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

        <!-- Current Queue -->
        <div id="current-queue" class="card mt-3">
            <div class="card-header">
                <div
                    class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Current Queue</strong>
                        <div class="text-muted">
                            <span id="adminQueueCount">0</span>
                            songs waiting
                        </div>
                    </div>

                    <button
                        type="button"
                        id="clearQueueButton"
                        class="btn btn-outline-danger btn-sm">
                        Clear Queue
                    </button>

                </div>
            </div>

            <div class="card-body">
                <div
                    id="adminQueueEmpty"
                    class="text-muted">
                    The queue is currently empty.
                </div>

                <div
                    id="adminQueueContainer"
                    class="d-none">

                    <div
                        id="adminQueueList"
                        class="list-group">
                    </div>

                </div>

            </div>

        </div>
    </form>
</div>


<script>
    var pluginName = '<?php echo $pluginName; ?>';
    const API_BASE = '/api/plugin/fpp-jukebox';
    const savedBackgroundItem =
        <?= json_encode(
            $config['backgroundSequence'] ?? ''
        ) ?>;
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

            async function loadAdminQueue() {
                const emptyMessage =
                    document.getElementById(
                        'adminQueueEmpty'
                    );

                const container =
                    document.getElementById(
                        'adminQueueContainer'
                    );

                const list =
                    document.getElementById(
                        'adminQueueList'
                    );

                const count =
                    document.getElementById(
                        'adminQueueCount'
                    );

                const clearQueueButton =
                    document.getElementById(
                        'clearQueueButton'
                    );

                if (
                    !emptyMessage ||
                    !container ||
                    !list ||
                    !count ||
                    !clearQueueButton
                ) {
                    return;
                }

                try {
                    const response =
                        await fetch(
                            API_BASE + '/queue', {
                                cache: 'no-store'
                            }
                        );

                    const data =
                        await response.json();

                    if (!data.success) {
                        throw new Error(
                            data.message ||
                            'Unable to load queue.'
                        );
                    }

                    const queue =
                        data.queue || [];

                    const queueLength =
                        data.queueLength || 0;

                    const queueLimit =
                        data.queueLimit || 0;

                    count.textContent =
                        `${queueLength} / ${queueLimit}`;

                    // Queue empty.
                    if (queueLength === 0) {
                        emptyMessage.classList.remove(
                            'd-none'
                        );

                        container.classList.add(
                            'd-none'
                        );

                        list.innerHTML = '';

                        clearQueueButton.disabled = true;
                        return;
                    }

                    // Queue contains songs.
                    emptyMessage.classList.add(
                        'd-none'
                    );

                    container.classList.remove(
                        'd-none'
                    );

                    list.innerHTML = '';

                    queue.forEach(function(item, index) {
                        const row =
                            document.createElement(
                                'div'
                            );

                        row.className =
                            'list-group-item d-flex align-items-center';


                        const position =
                            document.createElement(
                                'div'
                            );

                        position.className = 'fw-bold me-3';
                        position.style.width = '30px';
                        position.textContent = index + 1;

                        const title =
                            document.createElement(
                                'div'
                            );

                        title.className = 'flex-grow-1';
                        title.textContent = item.title;

                        const removeButton =
                            document.createElement(
                                'button'
                            );

                        removeButton.type = 'button';
                        removeButton.className =
                            'btn btn-sm btn-outline-danger';
                        removeButton.textContent = 'Remove';
                        removeButton.addEventListener(
                            'click',
                            function() {
                                removeAdminQueueItem(index);
                            }
                        );

                        row.appendChild(position);
                        row.appendChild(title);
                        row.appendChild(removeButton);

                        list.appendChild(row);
                    });
                } catch (error) {
                    console.error(
                        'Unable to load admin queue:',
                        error
                    );
                }
            }
            loadAdminQueue();
            setInterval(
                loadAdminQueue,
                2000
            );

            async function removeAdminQueueItem(index) {
                try {
                    const response =
                        await fetch(
                            API_BASE + '/queue/remove', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    index: index
                                }),
                                cache: 'no-store'
                            }
                        );

                    const data =
                        await response.json();

                    if (!data.success) {
                        throw new Error(
                            data.message ||
                            'Unable to remove queue item.'
                        );
                    }

                    // Refresh the queue immediately.
                    await loadAdminQueue();
                } catch (error) {
                    console.error(
                        'Unable to remove queue item:',
                        error
                    );

                    /*
                     * Use your existing admin error/alert
                     * mechanism here if you have one.
                     */
                    alert(
                        error.message ||
                        'Unable to remove queue item.'
                    );
                }
            }

            async function clearAdminQueue() {
                try {
                    const response =
                        await fetch(
                            API_BASE + '/queue/clear', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                cache: 'no-store'
                            }
                        );

                    const data =
                        await response.json();

                    if (!data.success) {
                        throw new Error(
                            data.message ||
                            'Unable to clear queue.'
                        );
                    }

                    // Immediately refresh the admin queue.
                    await loadAdminQueue();

                } catch (error) {
                    console.error(
                        'Unable to clear jukebox queue:',
                        error
                    );

                    alert(
                        error.message ||
                        'Unable to clear the jukebox queue.'
                    );
                }
            }

            const clearQueueButton =
                document.getElementById(
                    'clearQueueButton'
                );

            if (clearQueueButton) {
                clearQueueButton.addEventListener(
                    'click',
                    clearAdminQueue
                );
            }

            async function loadBackgroundOptions(
                selectedValue = ''
            ) {

                const backgroundType =
                    document.getElementById(
                        'backgroundType'
                    ).value;

                const backgroundSelect =
                    document.getElementById(
                        'backgroundSequence'
                    );

                backgroundSelect.innerHTML =
                    '<option value="">None</option>';

                try {
                    /*
                     * Load playlists
                     */
                    if (backgroundType === 'playlist') {

                        const response =
                            await fetch(
                                API_BASE + '/fpp-playlists', {
                                    cache: 'no-store'
                                }
                            );

                        const data =
                            await response.json();

                        if (!data.success) {
                            throw new Error(
                                'Unable to load playlists.'
                            );
                        }

                        data.playlists.forEach(
                            function(playlist) {

                                const option =
                                    document.createElement(
                                        'option'
                                    );

                                option.value =
                                    playlist;

                                option.textContent =
                                    playlist;

                                backgroundSelect.appendChild(
                                    option
                                );
                            }
                        );

                        if (selectedValue) {
                            backgroundSelect.value = selectedValue;
                        }

                        return;
                    }

                    if (backgroundType == 'video') {
                        const response =
                            await fetch(
                                API_BASE + '/fpp-videos', {
                                    cache: 'no-store'
                                }
                            );

                        const data =
                            await response.json();

                        if (!data.success) {
                            throw new Error(
                                'Unable to load videos.'
                            );
                        }

                        data.videos.forEach(
                            function(video) {

                                const option =
                                    document.createElement(
                                        'option'
                                    );

                                option.value =
                                    video;

                                option.textContent =
                                    video;

                                backgroundSelect.appendChild(
                                    option
                                );
                            }
                        );

                        if (selectedValue) {
                            backgroundSelect.value = selectedValue;
                        }

                        return;
                    }

                    /*
                     * Load sequences
                     */
                    const response =
                        await fetch(
                            API_BASE + '/fpp-sequences', {
                                cache: 'no-store'
                            }
                        );

                    const data =
                        await response.json();

                    if (!data.success) {
                        throw new Error(
                            'Unable to load sequences.'
                        );
                    }

                    /*
                     * Your existing fpp-sequences endpoint
                     * should provide the available sequence
                     * filenames here.
                     */
                    data.sequences.forEach(
                        function(sequence) {

                            const option =
                                document.createElement(
                                    'option'
                                );

                            option.value =
                                sequence;

                            option.textContent =
                                sequence.replace(
                                    /\.fseq$/i,
                                    ''
                                );

                            backgroundSelect.appendChild(
                                option
                            );
                        }
                    );

                    if (selectedValue) {
                        backgroundSelect.value = selectedValue;
                    }

                    return;

                } catch (error) {
                    console.error(
                        'Unable to load background options:',
                        error
                    );
                }
            }

            loadBackgroundOptions(
                savedBackgroundItem
            );

            document
                .getElementById(
                    'backgroundType'
                )
                .addEventListener(
                    'change',
                    function() {
                        loadBackgroundOptions();
                    }
                );
        }
    );
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const themePreset = document.getElementById('themePreset');
        const customThemeColours = document.getElementById('customThemeColours');

        if (!themePreset || !customThemeColours) {
            return;
        }

        function updateThemeColourVisibility() {

            customThemeColours.style.display =
                themePreset.value === 'custom' ?
                'block' :
                'none';
        }

        themePreset.addEventListener('change', updateThemeColourVisibility);

        updateThemeColourVisibility();

    });

    document.addEventListener('DOMContentLoaded', function() {

        const themePreset = document.getElementById('themePreset');
        const themePreview = document.getElementById('themePreview');

        if (!themePreset || !themePreview) {
            return;
        }

        const themePresets = <?= json_encode(
                                    $themePresets,
                                    JSON_HEX_TAG |
                                        JSON_HEX_AMP |
                                        JSON_HEX_APOS |
                                        JSON_HEX_QUOT
                                ) ?>;

        const colourFields = [
            'background',
            'cardBackground',
            'text',
            'accent',
            'accentText'
        ];

        const cssVariables = {
            background: '--preview-background',
            cardBackground: '--preview-card',
            text: '--preview-text',
            accent: '--preview-accent',
            accentText: '--preview-accent-text'
        };

        function getCustomThemeColours() {

            const colours = {};

            colourFields.forEach(function(field) {

                const input = document.getElementById(
                    'theme_' + field
                );

                if (input) {
                    colours[field] = input.value;
                }

            });

            return colours;
        }

        function updateThemePreview() {

            const selectedPreset = themePreset.value;

            let colours;

            if (selectedPreset === 'custom') {
                colours = getCustomThemeColours();
            } else {
                colours = themePresets[selectedPreset] ||
                    themePresets.default;
            }

            Object.entries(cssVariables).forEach(function([key, variable]) {

                if (colours[key]) {
                    themePreview.style.setProperty(
                        variable,
                        colours[key]
                    );
                }

            });
        }

        // Update when the preset changes
        themePreset.addEventListener(
            'change',
            updateThemePreview
        );

        // Update when a custom colour changes
        colourFields.forEach(function(field) {

            const input = document.getElementById(
                'theme_' + field
            );

            if (input) {
                input.addEventListener(
                    'input',
                    updateThemePreview
                );
            }

        });

        // Apply the current saved theme on page load
        updateThemePreview();

        const resetCustomThemeColours = document.getElementById(
            'resetCustomThemeColours'
        );

        function resetThemeColours() {
            const defaultColours = themePresets.default;

            colourFields.forEach(function(field) {

                const input = document.getElementById(
                    'theme_' + field
                );

                if (input && defaultColours[field]) {
                    input.value = defaultColours[field];
                }

            });

            // Switch to custom colours if needed
            themePreset.value = 'custom';

            // Trigger the existing preset change handler
            // to show the custom colour pickers
            themePreset.dispatchEvent(
                new Event('change')
            );

            // Refresh the live preview
            updateThemePreview();
        }

        if (resetCustomThemeColours) {
            resetCustomThemeColours.addEventListener(
                'click',
                resetThemeColours
            );
        }

    });
</script>