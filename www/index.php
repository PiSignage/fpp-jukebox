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

        <div
            id="selectionNowPlaying"
            class="selection-now-playing"
            style="display: none;">

            <img
                id="selectionNowPlayingArtwork"
                src=""
                alt="">

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
                Queue Full — Please wait for a song to finish.
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
                alt="">

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

        <div class="jukebox-title" id="disabledJukeboxTitle">
            Jukebox
        </div>

        <div class="jukebox-disabled">

            <h1>Jukebox Unavailable</h1>

            <p>
                The jukebox is currently unavailable.
            </p>

        </div>

    </section>

    <script
        src="/plugin.php?plugin=fpp-jukebox&file=www/js/jukebox.js&nopage=1"></script>

</body>

</html>