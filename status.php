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
<style>
    #adminUpNext div:last-child {
        border-bottom: none !important;
    }
</style>

<div class="container-fluid">

    <!-- <h2 class="mb-4">
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

    </div> -->

    <!-- Jukebox Status -->
    <div class="card mb-4">
        <div class="card-header">
            <strong>Jukebox Status</strong>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <strong>Status</strong>
                    <div id="adminJukeboxStatus">
                        Loading...
                    </div>
                </div>

                <div class="col-md-3">
                    <strong>Fpp Playback</strong>
                    <div id="adminPlaybackStatus">
                        Loading...
                    </div>
                </div>

                <div class="col-md-3">
                    <strong>Current Playlist</strong>
                    <div id="adminCurrentPlaylist">
                        -
                    </div>
                </div>

                <div class="col-md-3">
                    <strong>Queue</strong>
                    <div id="adminStatusQueue">
                        0 songs
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">

        <!-- Now Playing -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Now Playing</strong>
                </div>

                <div class="card-body">
                    <div id="adminNowPlaying">
                        <span class="text-muted">
                            Nothing playing
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Up Next -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <strong>Up Next</strong>
                </div>

                <div class="card-body">
                    <div id="adminUpNext">
                        <span class="text-muted">
                            Queue is empty
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
    const API_BASE = '/api/plugin/fpp-jukebox';
    document.addEventListener(
        'DOMContentLoaded',
        function() {

            loadAdminStatus();

            setInterval(
                loadAdminStatus,
                2000
            );

            async function loadAdminStatus() {
                try {
                    const [
                        statusResponse,
                        queueResponse,
                        configResponse,
                        sequencesResponse
                    ] = await Promise.all([
                        fetch(
                            API_BASE + '/status', {
                                cache: 'no-store'
                            }
                        ),
                        fetch(
                            API_BASE + '/queue', {
                                cache: 'no-store'
                            }
                        ),
                        fetch(
                            API_BASE + '/config', {
                                cache: 'no-store'
                            }
                        ),
                        fetch(
                            API_BASE + '/sequences', {
                                cache: 'no-store'
                            }
                        )
                    ]);

                    const statusData =
                        await statusResponse.json();

                    const queueData =
                        await queueResponse.json();

                    const configData =
                        await configResponse.json();

                    const sequencesData =
                        await sequencesResponse.json();

                    if (!statusData.success) {
                        return;
                    }

                    // Jukebox availability
                    const jukeboxStatus =
                        document.getElementById(
                            'adminJukeboxStatus'
                        );

                    if (jukeboxStatus) {
                        jukeboxStatus.innerHTML =
                            statusData.available ?
                            '<span class="badge bg-success">Available</span>' :
                            '<span class="badge bg-secondary">Unavailable</span>';
                    }

                    // FPP playback
                    const playbackStatus =
                        document.getElementById(
                            'adminPlaybackStatus'
                        );

                    if (playbackStatus) {
                        let playbackLabel = 'Idle';
                        let playbackClass = 'bg-secondary';

                        if (statusData.playing) {
                            const backgroundSequence =
                                configData.success ?
                                configData.config.backgroundSequence :
                                null;

                            const currentPlaylist =
                                statusData.playlist || '';

                            // FPP may return the sequence with
                            // or without the .fseq extension
                            const normalisedCurrent =
                                currentPlaylist.replace(
                                    /\.fseq$/i,
                                    ''
                                );

                            const normalisedBackground =
                                (backgroundSequence || '').replace(
                                    /\.fseq$/i,
                                    ''
                                );

                            if (
                                normalisedBackground &&
                                normalisedCurrent === normalisedBackground
                            ) {
                                playbackLabel = 'Background Playing';
                                playbackClass = 'bg-info';
                            } else {
                                playbackLabel = 'Jukebox Playing';
                                playbackClass = 'bg-success';
                            }
                        }

                        playbackStatus.innerHTML = `
                            <span class="badge ${playbackClass}">
                                ${playbackLabel}
                            </span>
                        `;
                    }

                    /*
                     * Current FPP playlist / sequence
                     */
                    const currentPlaylist =
                        document.getElementById(
                            'adminCurrentPlaylist'
                        );

                    if (currentPlaylist) {
                        currentPlaylist.textContent =
                            statusData.playlist || '-';
                    }

                    /*
                     * Queue
                     */
                    const queueStatus =
                        document.getElementById(
                            'adminStatusQueue'
                        );

                    if (
                        queueStatus &&
                        queueData.success
                    ) {
                        const queueLength =
                            queueData.queueLength || 0;

                        const queueLimit =
                            queueData.queueLimit || 0;

                        queueStatus.textContent =
                            `${queueLength} / ${queueLimit} songs`;
                    }

                    const upNext =
                        document.getElementById(
                            'adminUpNext'
                        );

                    if (
                        upNext &&
                        queueData.success
                    ) {
                        const queue =
                            queueData.queue || [];

                        if (queue.length === 0) {
                            upNext.innerHTML = `
                                <span class="text-muted">
                                    Queue is empty
                                </span>
                            `;
                        } else {
                            upNext.innerHTML = '';

                            queue.forEach(
                                function(
                                    item,
                                    index
                                ) {

                                    const row =
                                        document.createElement(
                                            'div'
                                        );


                                    row.className =
                                        'd-flex align-items-center gap-3 py-2 border-bottom';


                                    /*
                                     * Queue artwork is stored as the media
                                     * path from the jukebox configuration.
                                     */
                                    let artworkHtml = '';

                                    if (item.artwork) {
                                        let artworkUrl = item.artwork;

                                        /*
                                         * Convert the stored media path into
                                         * the FPP file API URL if required.
                                         */
                                        if (
                                            !artworkUrl.startsWith(
                                                '/api/'
                                            )
                                        ) {
                                            artworkUrl =
                                                '/api/file/' +
                                                artworkUrl
                                                .split('/')
                                                .map(
                                                    encodeURIComponent
                                                )
                                                .join('/');
                                        }

                                        artworkHtml = `
                                            <img
                                                src="${artworkUrl}"
                                                alt=""
                                                style="
                                                    width: 50px;
                                                    height: 50px;
                                                    object-fit: cover;
                                                    border-radius: 5px;
                                                "
                                            >
                                        `;
                                    }

                                    row.innerHTML = `
                                        <span class="badge bg-secondary">
                                            ${index + 1}
                                        </span>

                                        ${artworkHtml}

                                        <div class="flex-grow-1">
                                            <strong>
                                                ${escapeHtml(item.title)}
                                            </strong>
                                        </div>
                                    `;

                                    upNext.appendChild(
                                        row
                                    );
                                }
                            );
                        }
                    }

                    const nowPlaying =
                        document.getElementById(
                            'adminNowPlaying'
                        );

                    if (nowPlaying) {
                        if (!statusData.playing) {
                            nowPlaying.innerHTML = `
                                <span class="text-muted">
                                    Nothing playing
                                </span>
                            `;
                        } else {
                            const backgroundSequence =
                                configData.success ?
                                configData.config.backgroundSequence :
                                '';

                            const current =
                                (statusData.playlist || '')
                                .replace(
                                    /\.fseq$/i,
                                    ''
                                );

                            const background =
                                (backgroundSequence || '')
                                .replace(
                                    /\.fseq$/i,
                                    ''
                                );

                            const isBackground =
                                background !== '' &&
                                current === background;

                            /*
                             * Find the configured jukebox sequence
                             * matching the currently playing FPP
                             * sequence.
                             */
                            let sequence = null;

                            if (
                                !isBackground &&
                                sequencesData.success
                            ) {
                                sequence =
                                    (sequencesData.sequences || [])
                                    .find(
                                        function(item) {
                                            const id =
                                                (item.id || '')
                                                .replace(
                                                    /\.fseq$/i,
                                                    ''
                                                );

                                            return id === current;
                                        }
                                    );
                            }

                            // Use the configured title when we have
                            // a matching jukebox sequence.
                            const displayTitle =
                                sequence ?
                                sequence.title :
                                statusData.playlist || 'Unknown';

                            // Build the artwork
                            const artworkHtml =
                                sequence &&
                                sequence.artwork ?
                                `<img 
                                    src="${sequence.artwork}" 
                                    alt="" 
                                    style="
                                        width: 80px;
                                        height: 80px;
                                        object-fit: cover;
                                        border-radius: 6px;
                                    "
                                >` : '';

                            nowPlaying.innerHTML = `
                                <div class="d-flex align-items-center gap-3">
                                    ${artworkHtml}
                                    <div>
                                        <div class="mb-1">
                                            <strong>${escapeHtml(displayTitle)}</strong>
                                        </div>

                                        <span class="badge ${
                                            isBackground
                                                ? 'bg-info'
                                                : 'bg-success'
                                        }">
                                            ${
                                                isBackground
                                                    ? 'Background Sequence'
                                                    : 'Jukebox Song'
                                            }
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    }

                } catch (error) {
                    console.error(
                        'Unable to load jukebox status:',
                        error
                    );
                }
            }

            function escapeHtml(value) {
                const div =
                    document.createElement(
                        'div'
                    );

                div.textContent =
                    value ?? '';

                return div.innerHTML;
            }

        });
</script>