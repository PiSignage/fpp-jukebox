<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Jukebox</title>

    <link
        rel="stylesheet"
        href="/plugin.php?plugin=fpp-jukebox&file=www/css/jukebox.css&nopage=1&v=<?= filemtime(__DIR__ . '/js/jukebox.js') ?>">

    <style>
        .selection-status {
            position: sticky;
            top: -15px;
            z-index: 100;
            background: var(--jukebox-background);
            /* padding-top: 10px;
            padding-bottom: 10px; */
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 0px -20px;
            padding: 10px 20px;
        }

        .selection-status:not( :has(#selectionNowPlaying:not([style*="display: none"]))):not( :has(#selectionQueue:not(.d-none))) {
            display: none;
        }
    </style>

</head>


<body>
    <div
        id="jukeboxError"
        class="jukebox-error d-none"></div>

    <div
        id="jukeboxQueueMessage"
        class="jukebox-queue-message d-none"></div>

    <!-- Selection -->
    <section
        id="selectionScreen"
        class="jukebox-screen active">

        <!-- Jukebox Clock -->
        <div id="jukeboxClock" class="jukebox-clock">
            --:--
        </div>

        <div class="jukebox-title" id="jukeboxTitle">
            Jukebox
        </div>

        <div class="jukebox-header">
            <h1>Choose a Song</h1>
            <p>Select a sequence to play</p>
        </div>

        <div class="selection-status">
            <div
                id="selectionNowPlaying"
                class="selection-now-playing"
                style="display: none;">

                <img
                    id="selectionNowPlayingArtwork"
                    src=""
                    alt=""
                    onerror="handleArtworkError(this)">

                <div class="selection-now-playing-info">
                    <div class="selection-now-playing-label">
                        NOW PLAYING
                    </div>

                    <div
                        id="selectionNowPlayingTitle"
                        class="selection-now-playing-title">
                    </div>

                    <div id="selectionSongProgress" class="jukebox-song-progress d-none">
                        <div class="jukebox-song-progress-times">
                            <span id="selectionSongElapsed">00:00</span>
                            <span id="selectionSongDuration">00:00</span>
                        </div>

                        <div class="jukebox-song-progress-track">
                            <div
                                id="selectionSongProgressBar"
                                class="jukebox-song-progress-bar">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Queue Button -->
            <div id="selectionQueue" class="d-none">

                <button
                    type="button"
                    id="queueViewButton"
                    class="jukebox-queue-view-button">

                    <span class="jukebox-queue-view-label">
                        ♪ View Song Queue
                    </span>

                    <span
                        id="selectionQueueCount"
                        class="selection-queue-count">
                        0 / 5
                    </span>

                </button>

            </div>
        </div>

        <div
            id="sequenceGrid"
            class="jukebox-grid"></div>

        <div
            id="qrButtonContainer"
            class="jukebox-qr-button-container d-none">

            <button
                type="button"
                id="qrButton"
                class="jukebox-qr-button">

                <span class="jukebox-qr-icon">
                    ▦
                </span>

                <span id="qrButtonText">
                    Support Our Charity
                </span>

            </button>

        </div>

    </section>

    <!-- Jukebox Attract Screen -->
    <div
        id="attractScreen"
        class="jukebox-attract-screen"
        aria-hidden="true">

        <div class="jukebox-attract-content">

            <div class="jukebox-attract-icon">
                ♪
            </div>

            <h1
                id="attractJukeboxTitle"
                class="jukebox-attract-title">
                Jukebox
            </h1>

            <p
                id="attractMessage"
                class="jukebox-attract-message">
                Touch Anywhere to Choose a Song
            </p>

            <div class="jukebox-attract-touch">
                Touch Screen to Start
            </div>

        </div>

    </div>

    <!-- Loading -->

    <section
        id="loadingScreen"
        class="jukebox-screen">

        <div class="jukebox-centre">

            <div class="jukebox-spinner"></div>

            <h2>
                Starting...
            </h2>

        </div>

    </section>


    <!-- Playing -->

    <section
        id="playingScreen"
        class="jukebox-screen">

        <div class="jukebox-centre">

            <img
                id="playingArtwork"
                class="playing-artwork"
                src=""
                alt=""
                onerror="handleArtworkError(this)">

            <h1 id="playingTitle"></h1>

            <p>Now Playing</p>

            <div
                id="playingLockout"
                class="playing-lockout">

                <div
                    id="playingLockoutCountdown"
                    class=" playing-lockout-countdown">
                    30
                </div>
            </div>

            <p class="playing-lockout-label">
                seconds until another song can be selected
            </p>

        </div>

    </section>


    <!-- Lockout -->

    <section
        id="lockoutScreen"
        class="jukebox-screen">

        <div class="jukebox-centre">

            <h1>
                Thank You
            </h1>

            <p>
                Another song can be selected in
            </p>

            <div
                id="lockoutCountdown"
                class="lockout-countdown">
                30
            </div>

            <p>
                seconds
            </p>

        </div>

    </section>

    <!-- Disabled -->
    <section
        id="disabledScreen"
        class="jukebox-screen">

        <div
            class="jukebox-title"
            id="disabledJukeboxTitle">
            Jukebox
        </div>

        <div class="jukebox-disabled">

            <div class="jukebox-disabled-icon">
                ♪
            </div>

            <h1 id="disabledTitle">
                Jukebox Unavailable
            </h1>

            <p id="disabledMessage">
                Song selection isn't available
                at the moment.
            </p>

            <div
                class="jukebox-disabled-footer"
                id="disabledFooter">
                Please check back later.
            </div>

        </div>

    </section>

    <template id="loadingSongsTemplate">
        <div class="jukebox-loading">
            <div class="jukebox-loading-spinner">
            </div>

            <div class="jukebox-loading-title">
                Loading songs
            </div>

            <div class="jukebox-loading-message">
                Please wait...
            </div>
        </div>
    </template>

    <template id="songsErrorTemplate">
        <div class="jukebox-loading">
            <div class="jukebox-loading-title">
                Unable to load songs
            </div>

            <div class="jukebox-loading-message">
                Please try again shortly.
            </div>
        </div>
    </template>

    <template id="noSequencesTemplate">
        <div class="jukebox-no-sequences">
            <div class="jukebox-no-sequences-icon">
                ♪
            </div>

            <div class="jukebox-no-sequences-title">
                No songs available
            </div>

            <div class="jukebox-no-sequences-message">
                Please check back shortly.
            </div>
        </div>
    </template>

    <template id="queueItemTemplate">
        <div class="selection-queue-item">
            <div class="selection-queue-position">
                1
            </div>

            <div class="selection-queue-title">
                Song Name
            </div>
        </div>
    </template>

    <!-- Queue Modal -->
    <div
        id="queueModal"
        class="jukebox-queue-modal"
        aria-hidden="true">

        <div class="jukebox-queue-modal-backdrop"></div>

        <div
            class="jukebox-queue-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="queueModalTitle">
            <div class="jukebox-queue-modal-content">
                <h2
                    id="queueModalTitle"
                    class="jukebox-queue-modal-title">
                    Upcoming Songs
                </h2>

                <div
                    id="selectionQueueList"
                    class="selection-queue-list">
                </div>

                <div
                    id="selectionQueueFull"
                    class="selection-queue-full d-none">
                    Queue Full — please wait for the next song to start.
                </div>

                <button
                    type="button"
                    id="queueModalClose"
                    class="jukebox-queue-modal-close">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div
        id="qrModal"
        class="jukebox-qr-modal"
        aria-hidden="true">

        <div class="jukebox-qr-modal-backdrop"></div>

        <div
            class="jukebox-qr-modal-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="qrModalTitle">
            <div class="jukebox-qr-modal-content">
                <h2
                    id="qrModalTitle"
                    class="jukebox-qr-modal-title">
                    Support Our Charity
                </h2>

                <div
                    id="qrCode"
                    class="jukebox-qr-code">
                </div>

                <p
                    id="qrModalMessage"
                    class="jukebox-qr-modal-message">
                    Scan the QR code with your phone
                    to visit our page.
                </p>

                <button
                    type="button"
                    id="qrModalClose"
                    class="jukebox-qr-modal-close">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Jukebox Connection Lost Overlay -->
    <div id="connectionLostOverlay"
        class="jukebox-connection-overlay d-none"
        role="alert"
        aria-live="assertive">

        <div class="jukebox-connection-content">
            <div class="jukebox-connection-spinner"></div>

            <h2>Connection Lost</h2>

            <p>
                Unable to connect to the jukebox.
                Please wait while we reconnect.
            </p>

            <div class="jukebox-connection-status">
                Attempting to reconnect...
            </div>
        </div>
    </div>

    <script src="/plugin.php?plugin=fpp-jukebox&file=www/js/qrcode.min.js&nopage=1"></script>
    <script
        src="/plugin.php?plugin=fpp-jukebox&file=www/js/jukebox.js&nopage=1&v=<?= filemtime(__DIR__ . '/js/jukebox.js') ?>"></script>

</body>

</html>