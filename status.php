<style>
    #adminUpNext div:last-child {
        border-bottom: none !important;
    }
</style>

<div class="container-fluid">
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

    <!-- Jukebox Statistics -->
    <div id="Statistics" class="card">
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
            <!-- Stat cards -->
            <div class="row g-3 mb-4">

                <!-- Played Today -->
                <div class="col-6 col-lg">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="text-muted small">
                                Played Today
                            </div>
                            <div id="jukeboxPlayedToday"
                                class="fs-2 fw-semibold">
                                0
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Played Yesterday -->
                <div class="col-6 col-lg">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="text-muted small">
                                Played Yesterday
                            </div>
                            <div id="jukeboxPlayedYesterday"
                                class="fs-2 fw-semibold">
                                0
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last 7 Days -->
                <div class="col-6 col-lg">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="text-muted small">
                                Last 7 Days
                            </div>
                            <div id="jukeboxLast7Days"
                                class="fs-2 fw-semibold">
                                0
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Last 30 Days -->
                <div class="col-6 col-lg">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="text-muted small">
                                Last 30 Days
                            </div>
                            <div id="jukeboxLast30Days"
                                class="fs-2 fw-semibold">
                                0
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Plays -->
                <div class="col-6 col-lg">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <div class="text-muted small">
                                Total Plays
                            </div>
                            <div id="jukeboxTotalPlays"
                                class="fs-2 fw-semibold">
                                0
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Most Popular Today/Yesterday -->
            <div class="row g-3 mb-4">
                <!-- Most Popular Today -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0">
                                    Most Popular Today
                                </h6>

                                <span
                                    id="jukeboxPopularTodayTie"
                                    class="badge bg-secondary d-none">
                                    Joint 1st
                                </span>
                            </div>

                            <h5
                                id="jukeboxPopularToday"
                                class="mb-2">
                                No plays yet
                            </h5>

                            <div
                                id="jukeboxPopularTodayCount"
                                class="text-muted small">
                                0 plays
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Most Popular Yesterday -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-muted mb-0">
                                    Most Popular Yesterday
                                </h6>

                                <span
                                    id="jukeboxPopularYesterdayTie"
                                    class="badge bg-secondary d-none">
                                    Joint 1st
                                </span>
                            </div>

                            <h5
                                id="jukeboxPopularYesterday"
                                class="mb-2">
                                No plays recorded
                            </h5>

                            <div
                                id="jukeboxPopularYesterdayCount"
                                class="text-muted small">
                                0 plays
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Daily Plays</span>

                    <select
                        id="jukeboxChartPeriod"
                        class="form-select form-select-sm"
                        style="width: auto;">
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                    </select>
                </div>

                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="jukeboxDailyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">

                <h5 class="mb-0">
                    Most Played Songs
                </h5>

                <select
                    id="jukeboxSongsPeriod"
                    class="form-select form-select-sm"
                    style="width: auto;">

                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7">Last 7 Days</option>
                    <option value="30">Last 30 Days</option>
                    <option value="all" selected>All Time</option>

                </select>

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

</div>

<script src="/plugin.php?plugin=fpp-jukebox&file=js/vendor/chart.umd.min.js&nopage=1"></script>
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
                            const isBackground =
                                configData.success &&
                                isBackgroundPlaying(
                                    statusData.playlist,
                                    configData.config
                                );

                            if (isBackground) {
                                playbackLabel =
                                    'Background Playing';

                                playbackClass =
                                    'bg-info';
                            } else {
                                playbackLabel =
                                    'Jukebox Playing';

                                playbackClass =
                                    'bg-success';
                            }
                        }

                        playbackStatus.innerHTML = `
                            <span class="badge ${playbackClass}">
                                ${playbackLabel}
                            </span>
                        `;
                    }

                    // Current FPP playlist / sequence
                    const currentPlaylist =
                        document.getElementById(
                            'adminCurrentPlaylist'
                        );

                    if (currentPlaylist) {
                        currentPlaylist.textContent =
                            statusData.playlist || '-';
                    }

                    // Queue
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

                    // Up Next
                    const upNext =
                        document.getElementById(
                            'adminUpNext'
                        );

                    if (
                        upNext &&
                        queueData.success
                    ) {
                        const queue = queueData.queue || [];

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

                    // Now Playing
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
                            /*
                             * Normalised version is still needed below
                             * when matching jukebox sequences.
                             */
                            const current =
                                (statusData.playlist || '')
                                .replace(
                                    /\.fseq$/i,
                                    ''
                                );

                            /*
                             * Determine whether the current FPP item is
                             * the configured background.
                             */
                            const isBackground =
                                configData.success &&
                                isBackgroundPlaying(
                                    statusData.playlist,
                                    configData.config
                                );

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
                                    (
                                        sequencesData.sequences || []
                                    ).find(
                                        function(item) {

                                            const id =
                                                (
                                                    item.id || ''
                                                ).replace(
                                                    /\.fseq$/i,
                                                    ''
                                                );

                                            return (
                                                id === current
                                            );
                                        }
                                    );
                            }

                            /*
                             * Use the configured title when we have
                             * a matching jukebox sequence.
                             */
                            const displayTitle =
                                sequence ?
                                sequence.title :
                                statusData.playlist ||
                                'Unknown';

                            // Build the artwork.
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
                                >` :
                                '';

                            // Background badge text.
                            let playbackType = 'Jukebox Song';

                            if (isBackground) {
                                playbackType =
                                    configData.config
                                    .backgroundType ===
                                    'playlist' ?
                                    'Background Playlist' :
                                    'Background Sequence';
                            }

                            nowPlaying.innerHTML = `
                                <div class="d-flex align-items-center gap-3">
                                    ${artworkHtml}
                                    <div>
                                        <div class="mb-1">
                                            <strong>
                                                ${escapeHtml(displayTitle)}
                                            </strong>
                                        </div>

                                        <span class="badge ${
                                            isBackground
                                                ? 'bg-info'
                                                : 'bg-success'
                                        }">
                                            ${playbackType}
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

            function isBackgroundPlaying(
                currentItem,
                config
            ) {

                if (
                    !currentItem ||
                    !config
                ) {
                    return false;
                }

                const backgroundType =
                    config.backgroundType ||
                    'sequence';

                const backgroundItem =
                    config.backgroundSequence ||
                    '';

                if (!backgroundItem) {
                    return false;
                }

                /*
                 * Background playlist
                 */
                if (
                    backgroundType ===
                    'playlist'
                ) {
                    return (
                        currentItem ===
                        backgroundItem
                    );
                }

                /*
                 * Background video
                 *
                 * FPP reports the video filename
                 * directly, including the extension.
                 */
                if (
                    backgroundType ===
                    'video'
                ) {
                    return (
                        currentItem ===
                        backgroundItem
                    );
                }

                /*
                 * Background sequence
                 *
                 * FPP may report the sequence with
                 * or without the .fseq extension.
                 */
                const currentSequence =
                    currentItem.replace(
                        /\.fseq$/i,
                        ''
                    );

                const backgroundSequence =
                    backgroundItem.replace(
                        /\.fseq$/i,
                        ''
                    );

                return (
                    currentSequence ===
                    backgroundSequence
                );
            }

            function escapeHtml(value) {

                if (
                    value === null ||
                    value === undefined
                ) {
                    return '';
                }

                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            let jukeboxDailyChart = null;
            let jukeboxDailyHistory = [];
            let jukeboxSongPeriods = {};
            let jukeboxSequenceTitles = {};
            let jukeboxStatisticsLoading = false;
            let jukeboxStatisticsPromise = null;
            let jukeboxLastChartData = '';

            function loadJukeboxStatistics() {
                if (jukeboxStatisticsPromise) {
                    return jukeboxStatisticsPromise;
                }

                jukeboxStatisticsPromise = (async function() {
                    try {
                        await fetchAndRenderJukeboxStatistics();
                    } finally {
                        jukeboxStatisticsPromise = null;
                    }
                })();

                return jukeboxStatisticsPromise;
            }

            async function fetchAndRenderJukeboxStatistics() {
                const totalPlays = document.getElementById(
                    'jukeboxTotalPlays'
                );

                const table = document.getElementById(
                    'jukeboxStatisticsTable'
                );

                if (!totalPlays || !table) {
                    return;
                }

                try {
                    // Load statistics and sequence titles.
                    const [statsResponse, sequencesResponse] =
                    await Promise.all([
                        fetch(
                            `${API_BASE}/statistics`, {
                                cache: 'no-store'
                            }
                        ),
                        fetch(
                            `${API_BASE}/sequences`, {
                                cache: 'no-store'
                            }
                        )
                    ]);

                    if (!statsResponse.ok || !sequencesResponse.ok) {
                        throw new Error('Unable to load statistics.');
                    }

                    const stats = await statsResponse.json();
                    const sequenceData = await sequencesResponse.json();

                    if (!stats.success || !sequenceData.success) {
                        throw new Error('Invalid statistics response.');
                    }

                    // Update statistics cards.
                    const statisticsCards = {
                        jukeboxPlayedToday: stats.playedToday,
                        jukeboxPlayedYesterday: stats.playedYesterday,
                        jukeboxLast7Days: stats.last7Days,
                        jukeboxLast30Days: stats.last30Days,
                        jukeboxTotalPlays: stats.totalPlays
                    };

                    Object.entries(statisticsCards).forEach(
                        function([elementId, value]) {
                            const element = document.getElementById(elementId);

                            if (element) {
                                element.textContent =
                                    (Number(value) || 0).toLocaleString('en-GB');
                            }
                        }
                    );

                    // Store daily statistics for the chart.
                    jukeboxDailyHistory = Array.isArray(stats.dailyHistory) ?
                        stats.dailyHistory : [];

                    // Render the daily plays chart.
                    renderJukeboxDailyChart();

                    // Store sequence titles for the table.
                    jukeboxSequenceTitles = {};

                    (sequenceData.sequences || []).forEach(function(sequence) {
                        jukeboxSequenceTitles[sequence.id] = sequence.title;
                    });

                    // Store the reporting periods.
                    jukeboxSongPeriods = stats.songPeriods || {};

                    // Render the selected period.
                    renderJukeboxSongsTable();

                    // Update today's and yesterday's popular songs.
                    renderJukeboxPopularSongs();
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

            function renderJukeboxSongsTable() {
                const table = document.getElementById(
                    'jukeboxStatisticsTable'
                );

                const periodSelect = document.getElementById(
                    'jukeboxSongsPeriod'
                );

                if (!table || !periodSelect) {
                    return;
                }

                const period = periodSelect.value;

                // Get songs for the selected period.
                const sequences = jukeboxSongPeriods[period] || {};

                // Sort by most played.
                const entries = Object.entries(sequences);

                entries.sort(function(a, b) {
                    return Number(b[1]) - Number(a[1]);
                });

                // Clear the table.
                table.replaceChildren();

                // No plays for this period.
                if (entries.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');

                    cell.colSpan = 2;
                    cell.textContent = 'No sequences played during this period.';

                    row.appendChild(cell);
                    table.appendChild(row);

                    return;
                }

                // Display each sequence.
                entries.forEach(function([sequenceName, plays]) {
                    const row = document.createElement('tr');

                    const titleCell = document.createElement('td');

                    titleCell.textContent =
                        jukeboxSequenceTitles[sequenceName] || sequenceName;

                    const playsCell = document.createElement('td');

                    playsCell.className = 'text-end';
                    playsCell.textContent =
                        (Number(plays) || 0).toLocaleString('en-GB');

                    row.appendChild(titleCell);
                    row.appendChild(playsCell);

                    table.appendChild(row);
                });
            }

            function renderJukeboxPopularSongs() {
                const periods = [{
                        key: 'today',
                        titleId: 'jukeboxPopularToday',
                        countId: 'jukeboxPopularTodayCount',
                        tieId: 'jukeboxPopularTodayTie'
                    },
                    {
                        key: 'yesterday',
                        titleId: 'jukeboxPopularYesterday',
                        countId: 'jukeboxPopularYesterdayCount',
                        tieId: 'jukeboxPopularYesterdayTie'
                    }
                ];

                periods.forEach(function(period) {
                    const titleElement = document.getElementById(
                        period.titleId
                    );

                    const countElement = document.getElementById(
                        period.countId
                    );

                    const tieElement = document.getElementById(
                        period.tieId
                    );

                    if (!titleElement || !countElement || !tieElement) {
                        return;
                    }

                    // Get the song statistics for this period.
                    const sequences =
                        jukeboxSongPeriods[period.key] || {};

                    // Sort songs by number of plays.
                    const entries = Object.entries(sequences)
                        .map(function([name, plays]) {
                            return {
                                name: name,
                                plays: Number(plays) || 0
                            };
                        })
                        .filter(function(song) {
                            return song.plays > 0;
                        });

                    // Reset the badge.
                    tieElement.classList.add('d-none');

                    // No songs played during this period.
                    if (entries.length === 0) {
                        titleElement.textContent = 'No plays recorded';
                        countElement.textContent = '0 plays';
                        return;
                    }

                    // Find the highest play count.
                    const highestPlays = Math.max(
                        ...entries.map(function(song) {
                            return song.plays;
                        })
                    );

                    // Find all songs sharing first place.
                    const winners = entries.filter(function(song) {
                        return song.plays === highestPlays;
                    });

                    // Resolve configured titles.
                    const titles = winners.map(function(song) {
                        return jukeboxSequenceTitles[song.name] ||
                            song.name;
                    }).sort(function(a, b) {
                        return a.localeCompare(b);
                    });

                    // Display all winning songs on separate lines.
                    titleElement.replaceChildren();

                    titles.forEach(function(title, index) {
                        if (index > 0) {
                            titleElement.appendChild(
                                document.createElement('br')
                            );
                        }

                        titleElement.appendChild(
                            document.createTextNode(title)
                        );
                    });

                    const formattedPlays =
                        highestPlays.toLocaleString('en-GB');

                    if (winners.length > 1) {
                        // Show joint first badge.
                        tieElement.classList.remove('d-none');

                        countElement.textContent =
                            formattedPlays +
                            (highestPlays === 1 ? ' play' : ' plays') +
                            ' each · ' +
                            winners.length +
                            ' songs tied';
                    } else {
                        // Single winner.
                        countElement.textContent =
                            formattedPlays +
                            (highestPlays === 1 ? ' play' : ' plays');
                    }
                });
            }

            loadJukeboxStatistics();
            // Refresh statistics every 30 seconds.
            setInterval(function() {
                loadJukeboxStatistics();
            }, 30000);

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
                                    `${API_BASE}/statistics/reset`, {
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

                            // Wait for any existing statistics request to finish.
                            if (jukeboxStatisticsPromise) {
                                await jukeboxStatisticsPromise;
                            }

                            // Refresh after the reset has completed.
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

            function renderJukeboxDailyChart() {
                const canvas = document.getElementById(
                    'jukeboxDailyChart'
                );

                const periodSelect = document.getElementById(
                    'jukeboxChartPeriod'
                );

                if (!canvas || !periodSelect || typeof Chart === 'undefined') {
                    return;
                }

                const days = Number(periodSelect.value) || 7;

                // Select the requested reporting period.
                const history = jukeboxDailyHistory.slice(-days);

                // Format the dates for display.
                const labels = history.map(function(day) {

                    const date = new Date(day.date + 'T12:00:00');

                    return date.toLocaleDateString('en-GB', {
                        day: 'numeric',
                        month: 'short'
                    });
                });

                const plays = history.map(function(day) {
                    return Number(day.plays) || 0;
                });

                // Check whether the chart data has changed.
                const chartDataKey = JSON.stringify({
                    days: days,
                    labels: labels,
                    plays: plays
                });

                if (
                    jukeboxDailyChart &&
                    jukeboxLastChartData === chartDataKey
                ) {
                    return;
                }

                jukeboxLastChartData = chartDataKey;

                // Remove the previous chart before rebuilding.
                if (jukeboxDailyChart) {
                    jukeboxDailyChart.destroy();
                    jukeboxDailyChart = null;
                }

                jukeboxDailyChart = new Chart(canvas, {
                    type: 'bar',

                    data: {
                        labels: labels,

                        datasets: [{
                            label: 'Plays',
                            data: plays,
                            backgroundColor: '#0d6efd',
                            borderRadius: 4,
                            maxBarThickness: 40
                        }]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                display: false
                            },

                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return 'Plays: ' + context.parsed.y;
                                    }
                                }
                            }
                        },

                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },

                                ticks: {
                                    maxRotation: 0,
                                    autoSkip: true,
                                    maxTicksLimit: days === 7 ? 7 : 10
                                }
                            },

                            y: {
                                beginAtZero: true,

                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            }

            const jukeboxChartPeriod = document.getElementById(
                'jukeboxChartPeriod'
            );

            if (jukeboxChartPeriod) {
                jukeboxChartPeriod.addEventListener(
                    'change',
                    function() {
                        renderJukeboxDailyChart();
                    }
                );
            }

            const jukeboxSongsPeriod = document.getElementById(
                'jukeboxSongsPeriod'
            );

            if (jukeboxSongsPeriod) {
                jukeboxSongsPeriod.addEventListener(
                    'change',
                    function() {
                        renderJukeboxSongsTable();
                    }
                );
            }
        });
</script>