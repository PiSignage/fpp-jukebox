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
        href="/plugin.php?plugin=fpp-jukebox&file=www/css/jukebox.css&nopage=1">

    <style>
        .selection-status {
            position: sticky;
            top: 0;
            z-index: 100;
            background:
                var(--jukebox-background);
            padding-top: 10px;
            padding-bottom: 10px;

            display: flex;
            flex-direction: column;
            gap: 10px;
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
                </div>
            </div>

            <div
                id="selectionQueue"
                class="selection-queue d-none">
                <div class="selection-queue-header">
                    <span>Queue</span>

                    <span
                        id="selectionQueueCount"
                        class="selection-queue-count">
                        0 / 5
                    </span>
                </div>

                <div
                    id="selectionQueueList"
                    class="selection-queue-list">
                </div>

                <div
                    id="selectionQueueFull"
                    class="selection-queue-full d-none">
                    Queue Full — please wait for a next song to start.
                </div>

            </div>
        </div>

        <div
            id="sequenceGrid"
            class="jukebox-grid"></div>

    </section>


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

    <script
        src="/plugin.php?plugin=fpp-jukebox&file=www/js/jukebox.js&nopage=1"></script>

</body>

</html>