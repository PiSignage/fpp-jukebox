<?php

define('JUKEBOX_PLUGIN', 'fpp-jukebox');

define(
    'JUKEBOX_CONFIG_FILE',
    __DIR__ . '/../../config/plugin.fpp-jukebox.json'
);

define(
    'JUKEBOX_STATS_FILE',
    __DIR__ . '/../../config/plugin.fpp-jukebox-stats.json'
);

define(
    'JUKEBOX_QUEUE_FILE',
    __DIR__ . '/../../config/plugin.fpp-jukebox-queue'
);

function getEndpointsfppJukebox()
{
    return array(
        array(
            'method' => 'GET',
            'endpoint' => 'sequences',
            'callback' => 'jukeboxGetSequences'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'fpp-sequences',
            'callback' => 'jukeboxGetFppSequences'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'artwork',
            'callback' => 'jukeboxGetArtwork'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'status',
            'callback' => 'jukeboxGetStatus'
        ),
        array(
            'method' => 'POST',
            'endpoint' => 'play',
            'callback' => 'jukeboxPlay'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'config',
            'callback' => 'jukeboxGetConfig'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'statistics',
            'callback' => 'jukeboxGetStatistics'
        ),
        array(
            'method' => 'POST',
            'endpoint' => 'statistics/reset',
            'callback' => 'jukeboxResetStatistics'
        ),
        array(
            'method' => 'POST',
            'endpoint' => 'queue/next',
            'callback' => 'jukeboxPlayNextQueued'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'queue',
            'callback' => 'jukeboxGetQueue'
        )
    );
}


/*
 * --------------------------------------------------------------------------
 * Default configuration
 * --------------------------------------------------------------------------
 */

function jukeboxDefaultConfig()
{
    return array(
        'enabled' => true,
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
        'backgroundSequence' => '',
        'sequences' => array()
    );
}

// Load configuration
function jukeboxLoadConfig()
{
    $config = jukeboxDefaultConfig();

    if (!file_exists(JUKEBOX_CONFIG_FILE)) {
        return $config;
    }

    $contents = file_get_contents(JUKEBOX_CONFIG_FILE);

    if ($contents === false) {
        return $config;
    }

    $saved = json_decode($contents, true);

    if (!is_array($saved)) {
        return $config;
    }

    return array_replace_recursive($config, $saved);
}


// Get enabled sequences for guest interface
function jukeboxGetSequences()
{
    $config = jukeboxLoadConfig();

    $sequences = array();

    // Background sequence is controlled by FPP
    // It should not appear in the guest jukebox
    $backgroundSequence =
        $config['backgroundSequence'] ?? '';

    foreach ($config['sequences'] as $sequence) {

        if (empty($sequence['enabled'])) {
            continue;
        }

        /*
         * Skip the FPP-controlled background sequence.
         */

        if (
            $backgroundSequence !== '' &&
            $sequence['sequence'] === $backgroundSequence
        ) {
            continue;
        }

        $artwork = null;

        if (!empty($sequence['artwork'])) {
            $artwork = jukeboxMediaUrl($sequence['artwork']);
        }

        $sequences[] = array(
            'id' => $sequence['sequence'],
            'title' => $sequence['title'],
            'artwork' => $artwork,
            'order' => (int)$sequence['order']
        );
    }

    usort(
        $sequences,
        function ($a, $b) {
            return $a['order'] <=> $b['order'];
        }
    );

    return json(array(
        'success' => true,
        'sequences' => $sequences
    ));
}

// Get sequences available in FPP
function jukeboxGetFppSequences()
{
    $url =
        'http://127.0.0.1/api/sequence';

    $context =
        stream_context_create(
            array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => 5,
                    'ignore_errors' => true
                )
            )
        );


    $response =
        @file_get_contents(
            $url,
            false,
            $context
        );


    if ($response === false) {

        return jukeboxError(
            'Unable to retrieve sequences from FPP.'
        );
    }


    $data =
        json_decode(
            $response,
            true
        );


    if (!is_array($data)) {

        return jukeboxError(
            'Invalid sequence response from FPP.'
        );
    }


    return json(
        array(
            'success' => true,
            'sequences' => $data
        )
    );
}

// Get artwork from FPP media
function jukeboxGetArtwork()
{
    global $settings;

    $mediaDirectory = rtrim(
        $settings['mediaDirectory'],
        '/'
    );

    /*
     * Artwork is expected to live in:
     *
     * media/jukebox/
     *
     * We deliberately restrict the artwork browser to this folder.
     */

    $artworkDirectory = $mediaDirectory . '/images';

    if (!is_dir($artworkDirectory)) {
        return json(array(
            'success' => true,
            'artwork' => array()
        ));
    }

    $allowedExtensions = array(
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp'
    );

    $artwork = array();

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

        if (!in_array($extension, $allowedExtensions)) {
            continue;
        }

        $absolutePath = $file->getPathname();

        /*
         * Convert absolute media path into a path relative
         * to the FPP media directory.
         */

        $relativePath = substr(
            $absolutePath,
            strlen($mediaDirectory) + 1
        );

        $relativePath = str_replace(
            DIRECTORY_SEPARATOR,
            '/',
            $relativePath
        );

        $artwork[] = array(
            'path' => $relativePath,
            'name' => $file->getFilename(),
            'url' => jukeboxMediaUrl($relativePath)
        );
    }

    usort(
        $artwork,
        function ($a, $b) {
            return strcasecmp(
                $a['path'],
                $b['path']
            );
        }
    );

    return json(array(
        'success' => true,
        'artwork' => $artwork
    ));
}


// Convert media path into browser URL
function jukeboxMediaUrl($relativePath)
{
    $relativePath = ltrim(
        str_replace('\\', '/', $relativePath),
        '/'
    );

    $parts = explode('/', $relativePath);

    $encodedParts = array();

    foreach ($parts as $part) {
        $encodedParts[] = rawurlencode($part);
    }

    /*
     * FPP serves files from its media root at /media/
     */

    return '/api/file/' . implode('/', $encodedParts);
}

/*
 * --------------------------------------------------------------------------
 * Check whether FPP is currently playing
 * --------------------------------------------------------------------------
 */

function jukeboxIsPlaying()
{
    $url = 'http://127.0.0.1/api/fppd/status';

    $context =
        stream_context_create(
            array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => 2,
                    'ignore_errors' => true
                )
            )
        );

    $response =
        @file_get_contents(
            $url,
            false,
            $context
        );

    if ($response === false) {
        return false;
    }

    $status =
        json_decode(
            $response,
            true
        );

    if (!is_array($status)) {
        return false;
    }

    return (
        isset($status['status_name']) &&
        $status['status_name'] !== 'idle'
    );
}

// Get playback status
function jukeboxGetStatus()
{
    $config = jukeboxLoadConfig();

    // Manule enable jukebox switch
    $enabled =
        !empty($config['enabled']);

    // Check the configured schedule
    $scheduled  =
        jukeboxIsWithinSchedule($config);

    // Jukebox is available only when both conditions are satisfied.
    $available =
        $enabled &&
        $scheduled;

    /*
     * Existing playback status.
     *
     * Keep your existing code here that
     * determines whether FPP is playing.
     */
    $url = 'http://127.0.0.1/api/fppd/status';

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 2,
            'ignore_errors' => true
        )
    ));

    $response = @file_get_contents(
        $url,
        false,
        $context
    );

    $playing = false;

    if ($response !== false) {

        $status = json_decode(
            $response,
            true
        );

        if (is_array($status)) {

            if (
                isset($status['status_name']) &&
                $status['status_name'] !== 'idle'
            ) {
                $playing = true;
            }
        }
    }

    return json(array(
        'success' => true,
        'enabled' => $enabled,
        'scheduled' => $scheduled,
        'available' => $available,
        'playing' => $playing,
        'lockoutSeconds' => (int)$config['lockoutSeconds']
    ));
}


// Start sequence
function jukeboxPlay()
{
    $config = jukeboxLoadConfig();
    $queue = jukeboxLoadQueue();

    if (!$config['enabled']) {
        return jukeboxError(
            'The jukebox is currently disabled.'
        );
    }

    if (!jukeboxIsWithinSchedule($config)) {
        return jukeboxError(
            'The jukebox is currently outside its scheduled hours.'
        );
    }

    $raw = file_get_contents(
        'php://input'
    );

    $data = json_decode(
        $raw,
        true
    );

    if (!is_array($data)) {
        return jukeboxError(
            'Invalid request.'
        );
    }

    if (empty($data['sequence'])) {
        return jukeboxError(
            'No sequence specified.'
        );
    }

    $requestedSequence = basename(
        $data['sequence']
    );

    $selected = null;

    foreach ($config['sequences'] as $sequence) {

        if (
            $sequence['sequence'] === $requestedSequence &&
            !empty($sequence['enabled'])
        ) {
            $selected = $sequence;
            break;
        }
    }

    if ($selected === null) {
        return jukeboxError(
            'Sequence is not available.'
        );
    }

    // Queue selected sequence if something is already playing.
    if (
        !empty($config['queueEnabled']) &&
        jukeboxIsPlaying()
    ) {
        $queueLimit =
            max(
                1,
                (int)(
                    $config['queueLimit']
                    ?? 5
                )
            );

        // Queue is full.
        if (
            count($queue) >= $queueLimit
        ) {
            return jukeboxError(
                'The jukebox queue is full.'
            );
        }

        // Check whether duplicate queue entries are allowed.
        if (
            empty($config['allowDuplicateQueueSongs']) &&
            !empty($queue)
        ) {
            foreach ($queue as $queuedItem) {
                if (
                    isset($queuedItem['sequence']) &&
                    $queuedItem['sequence'] === $sequence
                ) {
                    return jukeboxError(
                        'This song is already in the queue.'
                    );
                    // return json(array(
                    //     'success' => false,
                    //     'message' => 'This song is already in the queue.'
                    // ));
                }
            }
        }

        // Add the selected sequence.
        $queue[] = array(
            'sequence' => $selected['sequence'],
            'title' => $selected['title'],
            'artwork' => $selected['artwork'],
        );

        // Save the queue.
        if (!jukeboxSaveQueue($queue)) {
            return jukeboxError(
                'Unable to add sequence to the queue.'
            );
        }

        /*
        * Tell the touchscreen that the
        * sequence has been queued.
        */
        return json(array(
            'success' => true,
            'queued' => true,
            'position' => count($queue),
            'queueLength' => count($queue),
            'queueLimit' => $queueLimit,
            'sequence' => $selected['sequence'],
            'title' => $selected['title']
        ));
    }

    // Start sequence using FPP's API.
    $sequenceName = rawurlencode(
        $requestedSequence
    );

    $url =
        'http://127.0.0.1/api/playlist/' .
        $sequenceName . '.fseq' .
        '/start';

    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        )
    ));

    $response = @file_get_contents(
        $url,
        false,
        $context
    );

    if ($response === false) {
        return jukeboxError(
            'FPP could not start the sequence.'
        );
    }

    // Record the jukebox play
    jukeboxRecordPlay($selected['sequence']);

    return json(array(
        'success' => true,
        'sequence' => $selected['sequence'],
        'title' => $selected['title']
    ));
}


// Get plugin configuration
function jukeboxGetConfig()
{
    return json(array(
        'success' => true,
        'config' => jukeboxLoadConfig()
    ));
}


/**
 * Error response
 * 
 * @param string $message Error message
 * 
 * @return array
 */

function jukeboxError($message)
{
    http_response_code(400);

    return json(array(
        'success' => false,
        'message' => $message
    ));
}

/**
 * Check whether the jukebox is currently
 * within one of the configured schedule periods.
 *
 * Supports normal schedules:
 * 18:00 -> 23:00
 *
 * And overnight schedules:
 * 22:00 -> 02:00
 *
 * @param array $config Jukebox configuration.
 *
 * @return bool
 */
function jukeboxIsWithinSchedule($config)
{
    /*
     * Scheduling disabled.
     *
     * The normal Enable Jukebox setting controls access.
     */
    if (empty($config['scheduleEnabled'])) {
        return true;
    }

    // Get configured schedules
    $schedules =
        $config['schedules']
        ?? array();

    // No schedules configured.
    if (empty($schedules)) {
        return false;
    }

    // Current FPP/Raspberry Pi time
    $currentTime = date('H:i');

    // Check every schedule
    foreach ($schedules as $schedule) {
        $start = $schedule['start'] ?? '';
        $end = $schedule['end'] ?? '';

        // Ignore invalid schedule rows
        if (
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $start
            )
        ) {
            continue;
        }

        if (
            !preg_match(
                '/^(?:[01]\d|2[0-3]):[0-5]\d$/',
                $end
            )
        ) {
            continue;
        }

        // Same start/end means available all day
        if ($start === $end) {
            return true;
        }

        // Normal schedule.
        if ($start < $end) {
            if (
                $currentTime >= $start &&
                $currentTime < $end
            ) {
                return true;
            }
            continue;
        }

        // Overnight schedule
        if (
            $currentTime >= $start ||
            $currentTime < $end
        ) {
            return true;
        }

        // Current time isn't inside any configured schedule
        return false;
    }

    $start = $config['scheduleStart'] ?? '18:00';

    $end = $config['scheduleEnd'] ?? '23:00';

    $current =
        date('H:i');

    /*
     * Same start/end means the schedule is effectively
     * available all day.
     */
    if ($start === $end) {
        return true;
    }

    /*
     * Normal schedule.
     *
     * Example:
     * 18:00 → 23:00
     */
    if ($start < $end) {
        return (
            $current >= $start &&
            $current < $end
        );
    }

    /*
     * Overnight schedule.
     *
     * Example:
     * 22:00 → 02:00
     */
    return (
        $current >= $start ||
        $current < $end
    );
}

/**
 * Load jukebox statistics.
 *
 * Creates the statistics file if it does not
 * already exist.
 *
 * @return array
 */
function jukeboxLoadStats()
{
    // Default statistics structure.
    $defaultStats = array(
        'totalPlays' => 0,
        'sequences' => array()
    );

    // Statistics file doesn't exist yet.
    if (!file_exists(JUKEBOX_STATS_FILE)) {
        file_put_contents(
            JUKEBOX_STATS_FILE,
            json_encode(
                $defaultStats,
                JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_SLASHES
            )
        );

        return $defaultStats;
    }

    // Read existing statistics.
    $stats =
        json_decode(
            file_get_contents(
                JUKEBOX_STATS_FILE
            ),
            true
        );

    // Handle an invalid or empty file.
    if (!is_array($stats)) {
        return $defaultStats;
    }

    // Make sure the expected fields exist.
    if (
        !isset(
            $stats['totalPlays']
        )
    ) {
        $stats['totalPlays'] = 0;
    }

    if (
        !isset(
            $stats['sequences']
        ) ||
        !is_array(
            $stats['sequences']
        )
    ) {
        $stats['sequences'] = array();
    }

    return $stats;
}

/**
 * Save jukebox statistics.
 *
 * @param array $stats Statistics data.
 *
 * @return bool
 */
function jukeboxSaveStats($stats)
{
    return (
        file_put_contents(
            JUKEBOX_STATS_FILE,
            json_encode(
                $stats,
                JSON_PRETTY_PRINT |
                    JSON_UNESCAPED_SLASHES
            )
        ) !== false
    );
}

/**
 * Record a jukebox sequence play.
 *
 * This increments both the overall play count
 * and the individual sequence count.
 *
 * @param string $sequence Sequence filename.
 *
 * @return bool
 */
function jukeboxRecordPlay($sequence)
{
    if (empty($sequence)) {
        return false;
    }

    $stats = jukeboxLoadStats();

    // Overall play count.
    $stats['totalPlays']++;

    // Individual sequence count.
    if (
        !isset(
            $stats['sequences'][$sequence]
        )
    ) {
        $stats['sequences'][$sequence] =
            0;
    }

    $stats['sequences'][$sequence]++;

    // Save statistics.
    return jukeboxSaveStats(
        $stats
    );
}

/*
 * --------------------------------------------------------------------------
 * Get jukebox statistics
 * --------------------------------------------------------------------------
 */

function jukeboxGetStatistics()
{
    $stats = jukeboxLoadStats();

    return json(array(
        'success' => true,
        'totalPlays' => (int)$stats['totalPlays'],
        'sequences' => $stats['sequences']
    ));
}

/*
 * --------------------------------------------------------------------------
 * Reset jukebox statistics
 * --------------------------------------------------------------------------
 */

function jukeboxResetStatistics()
{
    $stats = array(
        'totalPlays' => 0,
        'sequences' => array()
    );

    if (!jukeboxSaveStats($stats)) {
        return jukeboxError(
            'Unable to reset jukebox statistics.'
        );
    }

    return json(array(
        'success' => true
    ));
}

/*
 * --------------------------------------------------------------------------
 * Load queue
 * --------------------------------------------------------------------------
 */

function jukeboxLoadQueue()
{
    if (!file_exists(JUKEBOX_QUEUE_FILE)) {
        return array();
    }

    $contents =
        file_get_contents(
            JUKEBOX_QUEUE_FILE
        );

    if ($contents === false) {
        return array();
    }

    $queue =
        json_decode(
            $contents,
            true
        );

    if (!is_array($queue)) {
        return array();
    }

    return $queue;
}

/*
 * --------------------------------------------------------------------------
 * Save queue
 * --------------------------------------------------------------------------
 */
function jukeboxSaveQueue($queue)
{
    return file_put_contents(
        JUKEBOX_QUEUE_FILE,
        json_encode(
            $queue,
            JSON_PRETTY_PRINT |
                JSON_UNESCAPED_SLASHES
        )
    ) !== false;
}

/*
 * --------------------------------------------------------------------------
 * Clear queue
 * --------------------------------------------------------------------------
 */
function jukeboxClearQueue()
{
    return jukeboxSaveQueue(
        array()
    );
}

/*
 * --------------------------------------------------------------------------
 * Play next queued sequence
 * --------------------------------------------------------------------------
 */

function jukeboxPlayNextQueued()
{
    $queue = jukeboxLoadQueue();

    // Nothing waiting.
    if (empty($queue)) {
        return json(array(
            'success' => true,
            'queued' => false,
            'message' => 'Queue is empty.'
        ));
    }

    /*
     * Check that FPP is actually idle before
     * starting another queued sequence.
     */
    if (jukeboxIsPlaying()) {

        return jukeboxError(
            'FPP is still playing.'
        );
    }

    // Get the first item in the queue.
    $next = array_shift(
        $queue
    );

    /*
     * Save the queue immediately.
     *
     * The item has now been removed from the queue.
     */
    if (
        !jukeboxSaveQueue(
            $queue
        )
    ) {
        return jukeboxError(
            'Unable to update the jukebox queue.'
        );
    }

    // Start the sequence in FPP.
    $sequenceName =
        rawurlencode(
            $next['sequence']
        ) . '.fseq';

    $url =
        'http://127.0.0.1/api/playlist/' .
        $sequenceName .
        '/start';

    $context =
        stream_context_create(
            array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => 5,
                    'ignore_errors' => true
                )
            )
        );

    $response =
        @file_get_contents(
            $url,
            false,
            $context
        );

    // FPP failed to start the sequence
    if ($response === false) {
        // Put it back at the front of the queue.
        array_unshift(
            $queue,
            $next
        );

        jukeboxSaveQueue(
            $queue
        );

        return jukeboxError(
            'Unable to start the queued sequence.'
        );
    }

    return json(array(
        'success' => true,
        'queued' => true,
        'sequence' => $next['sequence'],
        'title' => $next['title'],
        'artwork' => 'api/file/' . $next['artwork'],
        'queueLength' => count($queue)
    ));
}

// Get jukebox queue
function jukeboxGetQueue()
{
    $queue = jukeboxLoadQueue();

    $config = jukeboxLoadConfig();

    $queueLimit =
        max(
            1,
            (int)(
                $config['queueLimit']
                ?? 5
            )
        );

    return json(array(
        'success' => true,
        'queue' => $queue,
        'queueLength' => count($queue),
        'queueLimit' => $queueLimit
    ));
}
