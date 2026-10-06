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
            'endpoint' => 'fpp-playlists',
            'callback' => 'jukeboxGetFppPlaylists'
        ),
        array(
            'method' => 'GET',
            'endpoint' => 'fpp-videos',
            'callback' => 'jukeboxGetFppVideos'
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
            'method' => 'GET',
            'endpoint' => 'queue',
            'callback' => 'jukeboxGetQueue'
        ),
        array(
            'method' => 'POST',
            'endpoint' => 'queue/next',
            'callback' => 'jukeboxPlayNextQueued'
        ),
        array(
            'method' => 'POST',
            'endpoint' => 'queue/remove',
            'callback' => 'jukeboxRemoveQueueItem'
        ),
        array(
            'method' => 'POST',
            'endpoint' => 'queue/clear',
            'callback' => 'jukeboxClearQueueApi'
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

function jukeboxGetFppPlaylists()
{
    $url =
        'http://127.0.0.1/api/playlists';

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
            'Unable to retrieve playlists from FPP.'
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
            'playlists' => $data
        )
    );
}

function jukeboxGetFppVideos()
{
    $url =
        'http://127.0.0.1/api/files/videos';

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
            'Unable to retrieve videos from FPP.'
        );
    }

    $data =
        json_decode(
            $response,
            true
        );

    if (
        !is_array($data) ||
        ($data['status'] ?? '') !== 'ok' ||
        !isset($data['files']) ||
        !is_array($data['files'])
    ) {
        return jukeboxError(
            'Invalid sequence response from FPP.'
        );
    }

    $videos = array();

    foreach ($data['files'] as $file) {
        if (
            empty($file['name'])
        ) {
            continue;
        }

        $videos[] =
            $file['name'];
    }

    natcasesort(
        $videos
    );

    $videos =
        array_values(
            $videos
        );

    return json(
        array(
            'success' => true,
            'videos' => $videos
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
    $statusName = 'idle';
    $playlist = null;

    if ($response !== false) {

        $status = json_decode(
            $response,
            true
        );

        if (is_array($status)) {

            if (isset($status['status_name'])) {
                $statusName = $status['status_name'];
                $playing = $statusName !== 'idle';
                $playlist = $status['current_playlist']['playlist'];
            }
        }
    }

    return json(array(
        'success' => true,
        'enabled' => $enabled,
        'scheduled' => $scheduled,
        'available' => $available,
        'playing' => $playing,
        'statusName' => $statusName,
        'playlist' => $playlist,
        // 'fppStatus' => $status,
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
        jukeboxIsPlaying() &&
        !jukeboxIsBackgroundPlaying()
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
                    $queuedItem['sequence'] === $selected['sequence']
                ) {
                    return jukeboxError(
                        $selected['title'] . ' is already in the queue.'
                    );
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

    $contentType =
        $config['contentType']
        ?? 'sequence';

    // Start jukebox sequence
    $backgroundType =
        $config['backgroundType']
        ?? 'sequence';

    /*
    * Visitor-selected video.
    *
    * Videos are inserted directly using
    * FPP's Insert Playlist Immediate command.
    */
    if ($contentType === 'video') {
        /*
        * If queueing is disabled and another
        * jukebox item is currently playing,
        * stop it before inserting the new video.
        *
        * Do not stop the configured background.
        */
        if (
            empty($config['queueEnabled']) &&
            jukeboxIsPlaying() &&
            !jukeboxIsBackgroundPlaying()
        ) {
            $stopUrl =
                'http://127.0.0.1/api/playlists/stop';

            $stopContext =
                stream_context_create(
                    array(
                        'http' => array(
                            'method' => 'GET',
                            'timeout' => 5,
                            'ignore_errors' => true
                        )
                    )
                );

            $stopResponse =
                @file_get_contents(
                    $stopUrl,
                    false,
                    $stopContext
                );

            if ($stopResponse === false) {
                return jukeboxError(
                    'FPP could not stop the current video.'
                );
            }

            usleep(100000);
        }

        $url =
            'http://127.0.0.1/api/command/' .
            rawurlencode(
                'Insert Playlist Immediate'
            ) .
            '/' .
            rawurlencode(
                $requestedSequence
            );

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
                'FPP could not insert the video.'
            );
        }
    } elseif ($backgroundType === 'playlist') {
        /*
        * Background is an FPP playlist.
        *
        * Use Insert Playlist Immediate so FPP
        * temporarily interrupts the background
        * playlist and resumes it afterwards.
        */

        /*
        * If queueing is disabled and a jukebox
        * song is currently playing, stop it before
        * inserting the newly selected song.
        *
        * Do not stop anything if the background
        * playlist itself is currently playing.
        */
        if (
            empty($config['queueEnabled']) &&
            jukeboxIsPlaying() &&
            !jukeboxIsBackgroundPlaying()
        ) {
            // Stop current inserted jukebox song
            $stopUrl = 'http://127.0.0.1/api/playlists/stop';

            $stopContext =
                stream_context_create(
                    array(
                        'http' => array(
                            'method' => 'GET',
                            'timeout' => 5,
                            'ignore_errors' => true
                        )
                    )
                );

            $stopResponse =
                @file_get_contents(
                    $stopUrl,
                    false,
                    $stopContext
                );


            if ($stopResponse === false) {
                return jukeboxError(
                    'FPP could not stop the current sequence.'
                );
            }

            // Give FPP a short moment to return
            usleep(100000);
        }


        $sequenceFile = $requestedSequence;

        if (!str_ends_with(
            strtolower($sequenceFile),
            '.fseq'
        )) {
            $sequenceFile .= '.fseq';
        }

        $url =
            'http://127.0.0.1/api/command/' .
            rawurlencode(
                'Insert Playlist Immediate'
            ) .
            '/' .
            rawurlencode(
                $sequenceFile
            );

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
                'FPP could not insert the sequence.'
            );
        }
    } else {
        // Background is a sequence.

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
        // Example:
        // 18:00 -> 22:00
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
        // Example:
        // 18:00 -> 01:00
        if (
            $currentTime >= $start ||
            $currentTime < $end
        ) {
            return true;
        }
    }

    // Current time isn't inside any configured schedule
    return false;
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
    $config = jukeboxLoadConfig();

    /*
     * Do not start another queued song if
     * the jukebox is no longer available.
     *
     * Clear anything still waiting so the
     * Background Sequence can take over.
     */
    if (
        empty($config['enabled']) ||
        !jukeboxIsWithinSchedule($config)
    ) {
        jukeboxClearQueue();

        return json(array(
            'success' => true,
            'queued' => false,
            'message' => 'Jukebox is no longer available.'
        ));
    }

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
    if (
        jukeboxIsPlaying() &&
        !jukeboxIsBackgroundPlaying()
    ) {

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

    /*
     * Determine the type of visitor-selectable
     * content and how the Background has been
     * configured.
     */
    $contentType =
        $config['contentType']
        ?? 'sequence';

    $backgroundType =
        $config['backgroundType']
        ?? 'sequence';

    /*
    * --------------------------------------------------
    * Video Content / Background Playlist
    * --------------------------------------------------
    *
    * Visitor-selected videos always use
    * Insert Playlist Immediate.
    *
    * Sequences also use Insert Playlist Immediate
    * when the Background is an FPP playlist.
    */

    if (
        $contentType === 'video' ||
        $backgroundType === 'playlist'
    ) {
        $contentFile = $next['sequence'];

        /*
        * Sequence content requires .fseq.
        *
        * Video content already contains its
        * media extension, such as .mp4.
        */
        if (
            $contentType === 'sequence' &&
            !str_ends_with(
                strtolower($contentFile),
                '.fseq'
            )
        ) {
            $contentFile .= '.fseq';
        }

        $url =
            'http://127.0.0.1/api/command/' .
            rawurlencode(
                'Insert Playlist Immediate'
            ) .
            '/' .
            rawurlencode(
                $contentFile
            );

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
    } else {
        // Sequence content with a Background Sequence.
        $sequenceName =
            rawurlencode(
                $next['sequence']
            ) .
            '.fseq';

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
    }

    /*
     * --------------------------------------------------
     * Background Playlist
     * --------------------------------------------------
     *
     * FPP may have already resumed the Background
     * Playlist after the previous jukebox song.
     *
     * Insert Playlist Immediate interrupts it again
     * with the next queued song.
     */
    // if ($backgroundType === 'playlist') {
    //     $sequenceFile = $next['sequence'];

    //     // Make sure the filename includes
    //     // the .fseq extension.
    //     if (
    //         !str_ends_with(
    //             strtolower($sequenceFile),
    //             '.fseq'
    //         )
    //     ) {
    //         $sequenceFile .= '.fseq';
    //     }

    //     $url =
    //         'http://127.0.0.1/api/command/' .
    //         rawurlencode(
    //             'Insert Playlist Immediate'
    //         ) .
    //         '/' .
    //         rawurlencode(
    //             $sequenceFile
    //         );

    //     $context =
    //         stream_context_create(
    //             array(
    //                 'http' => array(
    //                     'method' => 'GET',
    //                     'timeout' => 5,
    //                     'ignore_errors' => true
    //                 )
    //             )
    //         );

    //     $response =
    //         @file_get_contents(
    //             $url,
    //             false,
    //             $context
    //         );
    // } else {
    //     // Background Sequence
    //     // Start the sequence in FPP.
    //     $sequenceName =
    //         rawurlencode(
    //             $next['sequence']
    //         ) . '.fseq';

    //     $url =
    //         'http://127.0.0.1/api/playlist/' .
    //         $sequenceName .
    //         '/start';

    //     $context =
    //         stream_context_create(
    //             array(
    //                 'http' => array(
    //                     'method' => 'GET',
    //                     'timeout' => 5,
    //                     'ignore_errors' => true
    //                 )
    //             )
    //         );

    //     $response =
    //         @file_get_contents(
    //             $url,
    //             false,
    //             $context
    //         );
    // }

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
            $contentType === 'video'
                ? 'Unable to start the queued video.'
                : 'Unable to start the queued sequence.'
        );
    }

    // Record the queued song as played
    jukeboxRecordPlay(
        $next['sequence']
    );

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

// Remove queue item
function jukeboxRemoveQueueItem()
{
    $input =
        json_decode(
            file_get_contents('php://input'),
            true
        );

    $index =
        isset($input['index'])
        ? (int) $input['index']
        : -1;

    $queue = jukeboxLoadQueue();

    // Make sure the requested item exists.
    if (
        $index < 0 ||
        !isset($queue[$index])
    ) {
        return jukeboxError(
            'Queue item not found.'
        );
    }

    // Remove the item.
    array_splice(
        $queue,
        $index,
        1
    );

    // Save the updated queue.
    if (
        !jukeboxSaveQueue(
            $queue
        )
    ) {
        return jukeboxError(
            'Unable to update the queue.'
        );
    }

    return json(array(
        'success' => true,
        'queueLength' => count($queue)
    ));
}

/**
 * Check whether FPP is currently playing the
 * configured Background Sequence.
 *
 * @return bool
 */
function jukeboxIsBackgroundPlaying()
{
    $config = jukeboxLoadConfig();

    $backgroundType =
        $config['backgroundType']
        ?? 'sequence';

    $backgroundItem =
        $config['backgroundSequence'] ?? '';

    if ($backgroundItem === '') {
        return false;
    }

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

    $currentPlaylist =
        $status['current_playlist']['playlist']
        ?? '';

    if ($currentPlaylist === '') {
        return false;
    }

    /*
     * Background video
     *
     * FPP reports standalone video playback as:
     *
     * current_playlist.type = media
     * current_song = filename.mp4
     * media_playing = true
     */
    if ($backgroundType === 'video') {
        $currentType =
            $status['current_playlist']['type']
            ?? '';

        $currentVideo =
            $status['current_song']
            ?? '';

        $mediaPlaying =
            $status['media_playing']
            ?? false;

        return (
            $currentType === 'media' &&
            $mediaPlaying === true &&
            $currentVideo === $backgroundItem
        );
    }

    /*
     * Background playlist
     *
     * FPP reports the playlist name in
     * current_playlist.playlist.
     */
    if ($backgroundType === 'playlist') {
        return (
            $currentPlaylist ===
            $backgroundItem
        );
    }

    /*
     * Background sequence
     * 
     * FPP may report the sequence with or
     * without the .fseq extension.
     */
    $currentSequence =
        preg_replace(
            '/\.fseq$/i',
            '',
            $currentPlaylist
        );

    $backgroundSequence =
        preg_replace(
            '/\.fseq$/i',
            '',
            $backgroundItem
        );

    return (
        $currentSequence ===
        $backgroundSequence
    );
}

/**
 * Clear the jukebox queue via the API.
 *
 * This does not stop the sequence that is
 * currently playing. It only removes songs
 * waiting in the queue.
 */
function jukeboxClearQueueApi()
{
    if (!jukeboxClearQueue()) {
        return jukeboxError(
            'Unable to clear the jukebox queue.'
        );
    }

    return json(array(
        'success' => true,
        'queueLength' => 0
    ));
}
