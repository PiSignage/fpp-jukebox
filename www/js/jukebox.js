const API_BASE = '/api/plugin/fpp-jukebox';

let lockoutSeconds = 30;

let statusTimer = null;
let countdownTimer = null;
let availabilityTimer = null;
let queueTimer = null;
let lockoutRemaining = 0;
let jukeboxWasAvailable = null;
let queueMessageTimer = null;

let currentSequence = null;
let currentPlaylist = null;

let playbackStarted = false;
let lockoutActive = false;

let playbackStartTime = null;
const PLAYBACK_START_TIMEOUT = 10000;
let playingFromQueue = false;

let statusFailureCount = 0;
const MAX_STATUS_FAILURES = 3;
let queueEnabled = false;

// Initialise
document.addEventListener(
    'DOMContentLoaded',
    initialise
);

async function initialise() {
    try {
        const enabled = await loadConfiguration();

        if (!enabled) {
            showDisabledScreen();
        } else {
            await loadSequences();
            showSelectionScreen();
        }

        // Check whether the jukebox is currently available
        await checkAvailability();
        // Start monitoring the schedule
        startAvailabilityMonitor();
    } catch (error) {
        console.error(
            'Jukebox initialisation failed:',
            error
        );

        showError(
            'Unable to load the jukebox.'
        );
    }
}

// Configuration
async function loadConfiguration() {
    const response = await fetch(
        API_BASE + '/config',
        {
            cache: 'no-store'
        }
    );

    const data = await response.json();

    if (!data.success) {
        throw new Error(
            'Unable to load configuration.'
        );
    }

    lockoutSeconds =
        parseInt(
            data.config.lockoutSeconds,
            10
        ) || 30;

    queueEnabled =
        data.config.queueEnabled === true;

    const title = document.getElementById(
        'jukeboxTitle'
    );

    if (title) {
        title.textContent =
            data.config.title ||
            'Jukebox';
    }

    return data.config.enabled !== false;
}

// Load sequences
async function loadSequences() {
    const response = await fetch(
        API_BASE + '/sequences',
        {
            cache: 'no-store'
        }
    );

    const data = await response.json();

    if (!data.success) {
        throw new Error(
            'Unable to load sequences.'
        );
    }

    renderSequences(
        data.sequences
    );
}

// Render sequences
function renderSequences(sequences) {
    const container =
        document.getElementById(
            'sequenceGrid'
        );

    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (
        !sequences ||
        sequences.length === 0
    ) {
        container.innerHTML = `
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
        `;

        return;
    }

    sequences.forEach(
        function (sequence) {
            const button =
                document.createElement(
                    'button'
                );

            button.type = 'button';

            button.className =
                'jukebox-sequence';

            // Artwork
            if (sequence.artwork) {
                button.style.backgroundImage =
                    `url("${sequence.artwork}")`;
            }

            // Card content
            button.innerHTML = `
                <div class="jukebox-sequence-overlay">
                    <div class="jukebox-sequence-title">
                        ${escapeHtml(sequence.title)}
                    </div>
                </div>
            `;

            // Selection
            button.addEventListener(
                'click',
                function () {
                    playSequence(
                        sequence
                    );
                }
            );

            container.appendChild(
                button
            );
        }
    );
}

// Play sequence
async function playSequence(sequence) {
    /*
     * If there is no current sequence, the lockout
     * prevents a new selection.
     *
     * If something is already playing, however,
     * we are allowed to send the selection to the
     * API so it can be queued.
     */
    if (
        lockoutActive &&
        currentSequence === null
    ) {
        return;
    }

    // Remember whether something was already playing.
    const alreadyPlaying =
        currentSequence !== null &&
        playbackStarted;

    /*
     * Only clear the playback monitor when
     * starting a completely new sequence.
     *
     * If something is already playing, we MUST
     * keep statusTimer running so we can detect
     * when the current song finishes.
     */
    if (!alreadyPlaying) {
        clearInterval(
            statusTimer
        );
    }

    /*
     * Only set currentSequence immediately when
     * nothing is currently playing.
     *
     * If something is playing, the selected sequence
     * may only be going into the queue.
     */
    if (!alreadyPlaying) {
        // Remember what FPP is playing before
        // the jukebox sequence starts.

        try {
            const statusResponse =
                await fetch(
                    API_BASE + '/status',
                    {
                        cache: 'no-store'
                    }
                );

            const statusData = await statusResponse.json();

            if (statusData.success) {
                currentPlaylist = statusData.playlist || null;
                console.log(
                    'Playlist before jukebox:',
                    currentPlaylist
                );
            }
        } catch (error) {
            console.warn(
                'Unable to get current playlist:',
                error
            );

            currentPlaylist = null;
        }

        currentSequence = sequence;
        playbackStarted = false;
    }

    try {
        const response =
            await fetch(
                API_BASE + '/play',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/json'
                    },
                    body: JSON.stringify({
                        sequence: sequence.id
                    }),
                    cache: 'no-store'
                }
            );

        const data = await response.json();

        // FPP rejected the request.
        if (!data.success) {
            throw new Error(
                data.message ||
                'Unable to start sequence.'
            );
        }

        // Sequence was added to the queue.
        if (data.queued) {
            console.log(
                'Sequence added to queue:',
                data.title
            );

            showQueueAddedMessage(
                data.title,
                data.position
            );

            // Refresh the queue immediately.
            loadQueue();

            /*
             * Keep the existing Now Playing
             * sequence exactly as it is.
             *
             * Most importantly, statusTimer is
             * still running because another song
             * is currently playing.
             */
            return;
        }

        // This was a new sequnce that fpp
        // actually stated
        currentSequence = sequence;

        playbackStarted = false;

        playbackStartTime = Date.now();

        playingFromQueue = false;

        // Immediately show Now Playing.
        showPlayingScreen(
            sequence
        );

        // Start the lockout as soon as playback starts
        startLockout()

        // The sequence has been accepted by FPP.
        // Now monitor playback.
        monitorPlayback();
    } catch (error) {
        console.error(
            'Unable to play sequence:',
            error
        );

        /*
        * If this was a queue attempt while another
        * sequence is playing, leave the current
        * Now Playing state completely untouched.
        */
        if (alreadyPlaying) {
            showError(
                error.message ||
                'Unable to add song to queue.'
            );
            return;
        }

        /*
         * Only clear currentSequence if this
         * was a new playback attempt.
         *
         * Don't destroy the existing Now Playing
         * sequence because a queue request failed.
         */
        if (!alreadyPlaying) {
            currentSequence = null;
            playbackStarted = false;
        }

        showSelectionScreen();

        showError(
            error.message ||
            'Unable to start sequence.'
        );
    }
}

// Monitor FPP playback
function monitorPlayback() {
    clearInterval(
        statusTimer
    );

    // Check immediately.
    checkPlaybackStatus();

    // Then continue checking every second.
    statusTimer =
        setInterval(
            checkPlaybackStatus,
            1000
        );
}

// Check playback status
async function checkPlaybackStatus() {
    // Nothing to monitor.
    if (currentSequence === null) {
        clearInterval(
            statusTimer
        );
        return;
    }

    try {
        const response =
            await fetch(
                API_BASE + '/status',
                {
                    cache: 'no-store'
                }
            );

        if (!response.ok) {
            // console.warn(
            //     'FPP status request failed:',
            //     response.status
            // );
            // return;
            throw new Error(
                `Status API returned HTTP ${response.status}`
            );
        }

        const data =
            await response.json();

        if (!data.success) {
            // console.warn(
            //     'Invalid FPP status response.'
            // );
            // return;
            throw new Error(
                data.message ||
                'Status API returned ar error.'
            );
        }

        // Status request succeeded
        statusFailureCount = 0;

        console.log(
            'Jukebox playback status:',
            data.playing,
            'Playlist:',
            data.playlist
        );

        // FPP is not playing
        if (!data.playing) {
            if (playbackStarted) {
                clearInterval(
                    statusTimer
                );
                handlePlaybackFinished();
                return;
            }
        }

        // We are waiting for the jukebox song to take over.
        if (!playbackStarted) {
            /*
             * The playlist has changed from whatever was
             * playing before the jukebox song started.
             *
             * Therefore the jukebox song has now taken over.
             */
            if (
                currentPlaylist !== null &&
                data.playlist !== currentPlaylist
            ) {
                console.log(
                    'Jukebox sequence has taken over:',
                    data.playlist
                );

                currentPlaylist = data.playlist;

                playbackStarted = true;

                playbackStartTime = null;

                updateSelectionNowPlaying();

                return;
            }

            /*
            * FPP accepted the play request, but the
            * selected sequence never took over.
            */
            if (
                !playbackStarted &&
                playbackStartTime !== null &&
                Date.now() - playbackStartTime >
                PLAYBACK_START_TIMEOUT
            ) {
                clearInterval(statusTimer);

                playbackStartTime = null;
                playbackStarted = false;

                console.error(
                    'Jukebox sequence failed to start:',
                    currentSequence
                        ? currentSequence.title
                        : 'Unknown'
                );

                /*
                * If this sequence came from the queue,
                * skip it and attempt to start the next
                * queued sequence.
                */
                if (playingFromQueue) {
                    console.log(
                        'Queued sequence failed. Trying next queued sequence.'
                    );

                    /*
                    * Keep currentPlaylist unchanged.
                    *
                    * It should still represent the
                    * background sequence and is needed
                    * to detect the next queued song
                    * taking over.
                    */
                    currentSequence = null;

                    await handlePlaybackFinished();

                    return;
                }

                /*
                * A song selected directly by the guest
                * failed to start.
                */
                currentSequence = null;
                playingFromQueue = false;

                showPlaybackError();

                return;
            }

            // Still playing the Background Sequence.
            // Do NOT mark the jukebox song as playing.
            return;
        }

        /*
         * ---------------------------------------------------------------
         * Jukebox song is playing.
         * ---------------------------------------------------------------
         *
         * If FPP changes playlist, the jukebox song
         * has finished and the Background Sequence
         * or another playlist has taken over.
         */
        if (
            data.playlist !== currentPlaylist
        ) {
            console.log(
                'Jukebox sequence finished.'
            );

            console.log(
                'Previous playlist:',
                currentPlaylist
            );

            console.log(
                'New playlist:',
                data.playlist
            );

            clearInterval(
                statusTimer
            );

            handlePlaybackFinished();

            return;
        }

        // Our jukebox song is still playing.
        updateSelectionNowPlaying();
    } catch (error) {
        statusFailureCount++;

        console.warn(
            `Playback status check failed (${statusFailureCount}/${MAX_STATUS_FAILURES}):`,
            error
        );

        /*
        * A single failed request should not
        * interrupt playback monitoring.
        */
        if (
            statusFailureCount <
            MAX_STATUS_FAILURES
        ) {
            return;
        }

        console.error(
            'Unable to monitor jukebox playback after repeated failures.'
        );

        /*
        * Stop this monitoring interval.
        */
        clearInterval(statusTimer);

        statusTimer = null;

        statusFailureCount = 0;

        playbackStartTime = null;

        /*
        * Do not try to stop anything in FPP.
        *
        * We don't actually know whether the
        * sequence is still playing because the
        * status API is unavailable.
        */
        playbackStarted = false;
        currentSequence = null;
        playingFromQueue = false;

        showPlaybackError();
    }
}

// Playback finished
async function handlePlaybackFinished() {
    console.log(
        'Jukebox sequence finished.'
    );

    playbackStarted = false;
    playbackStartTime = null;

    // If queueing is enabled, try to start
    // the next queued sequence.
    try {
        const configResponse =
            await fetch(
                API_BASE + '/config',
                {
                    cache: 'no-store'
                }
            );

        const configData =
            await configResponse.json();

        if (
            configData.success &&
            configData.config.queueEnabled
        ) {
            const response =
                await fetch(
                    API_BASE + '/queue/next',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        cache: 'no-store'
                    }
                );

            const data = await response.json();

            // A queued sequence was started
            if (
                data.success &&
                data.queued
            ) {
                console.log(
                    'Started queued sequence:',
                    data.title
                );

                // Keep the current sequence object
                // representing the newly started item.
                currentSequence = {
                    id: data.sequence,
                    title: data.title,
                    artwork: data.artwork,
                };

                // We have not yet seen FPP report
                // that the new sequence is playing.
                playbackStarted = false;

                /*
                * Start the same timeout used when a
                * guest manually selects a sequence.
                */
                playbackStartTime = Date.now();

                playingFromQueue = true;

                showSelectionScreen();

                // Start monitoring the new sequence
                monitorPlayback();

                return;
            }
        }
    } catch (error) {
        console.error(
            'Unable to start next queued sequence:',
            error
        );
    }

    /*
     * No queued sequence was started.
     *
     * We are no longer processing a song
     * that came from the queue.
     */
    playingFromQueue = false;

    // Lockout is still active
    if (lockoutRemaining > 0) {
        showLockoutScreen(
            lockoutRemaining
        );
        return;
    }

    // Lockout has already expired.
    // Return directly to the selection screen
    finishLockout();
}

// Lockout
function startLockout() {
    clearInterval(
        countdownTimer
    );

    lockoutActive = true;

    lockoutRemaining =
        lockoutSeconds;

    /*
     * Store when the lockout expires.
     *
     * This allows us to restore the remaining
     * lockout time if the page is refreshed.
     */
    const lockoutExpiresAt =
        Date.now() +
        (lockoutSeconds * 1000);

    sessionStorage.setItem(
        'jukeboxLockoutExpiresAt',
        lockoutExpiresAt.toString()
    );

    updatePlayingLockout(
        lockoutRemaining
    );

    // If timeout is zero, there is no lockout.
    if (lockoutRemaining <= 0) {
        countdownTimer = null;
        lockoutActive = false;
        sessionStorage.removeItem(
            'jukeboxLockoutExpiresAt'
        );
        hidePlayingLockout();
        return;
    }

    /*
     * Start the lockout countdown.
     *
     * We DO NOT show the lockout screen here.
     * The guest should remain on the Now Playing screen.
     */
    countdownTimer =
        setInterval(
            function () {

                lockoutRemaining--;

                // updateCountdown(
                //     lockoutRemaining
                // );
                updatePlayingLockout(
                    lockoutRemaining
                );

                if (lockoutRemaining <= 0) {

                    lockoutRemaining = 0;

                    clearInterval(
                        countdownTimer
                    );

                    countdownTimer = null;

                    lockoutActive = false;

                    /*
                     * The lockout has expired,
                     * so the stored expiry is no
                     * longer required.
                     */
                    sessionStorage.removeItem(
                        'jukeboxLockoutExpiresAt'
                    );

                    // The lockout has expired
                    hidePlayingLockout();

                    /*
                     * If the song has already finished,
                     * we can now return to the selection screen.
                     */
                    if (!playbackStarted) {
                        finishLockout();
                    }

                    return;
                }

            },
            1000
        );
}

// Finish lockout
async function finishLockout() {
    clearInterval(
        countdownTimer
    );

    lockoutActive = false;

    if (!playbackStarted) {
        currentSequence = null;
    }

    /*
     * Refresh the sequence list in case the
     * administrator has changed something.
     */
    try {
        await loadSequences();
    } catch (error) {
        console.error(
            'Unable to refresh sequences:',
            error
        );
    }

    showSelectionScreen();
}

// Screens
function showSelectionScreen() {
    updateSelectionNowPlaying();

    showScreen(
        'selectionScreen'
    );

    clearInterval(
        queueTimer
    );
    queueTimer = null;

    if (queueEnabled) {
        loadQueue();
        queueTimer =
            setInterval(
                loadQueue,
                1000
            );
    } else {
        hideQueue();
    }
}

function showLoadingScreen() {
    showScreen(
        'loadingScreen'
    );
}

function showPlayingScreen(sequence) {
    showScreen(
        'playingScreen'
    );

    // Title
    const title =
        document.getElementById(
            'playingTitle'
        );

    if (title) {
        title.textContent = sequence.title;
    }

    // Artwork
    const artwork =
        document.getElementById(
            'playingArtwork'
        );

    if (artwork) {
        if (sequence.artwork) {
            artwork.src = sequence.artwork;
            artwork.style.display = 'block';
        } else {
            artwork.removeAttribute(
                'src'
            );
            artwork.style.display =
                'none';
        }
    }
}


function showLockoutScreen(seconds) {
    showScreen(
        'lockoutScreen'
    );

    updateCountdown(
        seconds
    );
}

function showScreen(id) {
    document
        .querySelectorAll(
            '.jukebox-screen'
        )
        .forEach(
            function (screen) {
                screen.classList.remove(
                    'active'
                );
            }
        );

    if (
        id !== 'selectionScreen'
    ) {
        clearInterval(
            queueTimer
        );
    }

    const screen =
        document.getElementById(
            id
        );

    if (screen) {
        screen.classList.add(
            'active'
        );
    }
}

// Countdown
function updateCountdown(seconds) {
    const element =
        document.getElementById(
            'lockoutCountdown'
        );

    if (element) {
        element.textContent =
            Math.max(
                0,
                seconds
            );
    }
}

// Error
function showError(message) {
    const element =
        document.getElementById(
            'jukeboxError'
        );

    if (!element) {
        return;
    }

    element.textContent = message;

    element.classList.remove(
        'd-none'
    );

    setTimeout(
        function () {
            element.classList.add(
                'd-none'
            );
        },
        5000
    );
}

// Escape HTML
function escapeHtml(value) {
    const div =
        document.createElement(
            'div'
        );

    div.textContent = value ?? '';

    return div.innerHTML;
}

function updatePlayingLockout(seconds) {
    const container =
        document.getElementById(
            'playingLockout'
        );

    const countdown =
        document.getElementById(
            'playingLockoutCountdown'
        );

    if (!container || !countdown) {
        return;
    }

    const newValue =
        Math.max(
            0,
            seconds
        );

    // Update the number
    countdown.textContent = newValue;

    countdown.classList.remove(
        'animate'
    );

    void countdown.offsetWidth;

    countdown.classList.add(
        'animate'
    );

    container.style.display =
        'block';
}

function hidePlayingLockout() {
    showSelectionScreen();
}

function updateSelectionNowPlaying() {
    const container =
        document.getElementById(
            'selectionNowPlaying'
        );

    const artwork =
        document.getElementById(
            'selectionNowPlayingArtwork'
        );

    const title =
        document.getElementById(
            'selectionNowPlayingTitle'
        );

    if (
        !container ||
        !artwork ||
        !title
    ) {
        return;
    }

    // Nothing currently playing.
    if (
        currentSequence === null ||
        !playbackStarted
    ) {
        container.style.display =
            'none';

        return;
    }

    // Show current sequence.
    title.textContent = currentSequence.title;

    if (currentSequence.artwork) {
        artwork.src = currentSequence.artwork;
        artwork.style.display = 'block';
    } else {
        artwork.removeAttribute(
            'src'
        );

        artwork.style.display =
            'none';
    }

    container.style.display = 'flex';
}

function showDisabledScreen() {
    const title =
        document.getElementById(
            'disabledJukeboxTitle'
        );

    if (title) {
        title.textContent =
            document.getElementById(
                'jukeboxTitle'
            )?.textContent ||
            'Jukebox';
    }

    showScreen(
        'disabledScreen'
    );
}

// Availability Monitor
function startAvailabilityMonitor() {
    clearInterval(
        availabilityTimer
    );

    // Check immediately.
    checkAvailability();

    // Then check every 30 seconds.
    availabilityTimer =
        setInterval(
            checkAvailability,
            30000
        );
}

async function checkAvailability() {
    try {
        const response =
            await fetch(
                API_BASE + '/status',
                {
                    cache: 'no-store'
                }
            );

        if (!response.ok) {
            console.warn(
                'Availability check failed:',
                response.status
            );

            return;
        }

        const data =
            await response.json();

        if (!data.success) {
            console.warn(
                'Invalid jukebox status response.'
            );
            return;
        }

        console.log(
            'Jukebox availability:',
            data.available
        );

        /*
        * Jukebox has just changed from
        * available -> unavailable.
        *
        * Clear any songs that are still waiting
        * in the queue, but do not stop the
        * currently playing sequence.
        */
        if (
            jukeboxWasAvailable === true &&
            !data.available
        ) {
            await clearQueueOnUnavailable();
        }

        // Remember the current availability
        // for the next check.
        jukeboxWasAvailable = data.available;

        // Jukebox is unavailable.
        if (!data.available) {
            updateDisabledScreen(data);
            handleJukeboxUnavailable();
            return;
        }

        // Jukebox is available.
        handleJukeboxAvailable();
    } catch (error) {
        console.error(
            'Availability check error:',
            error
        );
    }
}

function handleJukeboxUnavailable() {
    // Don't interrupt a sequence that is already playing.
    if (currentSequence !== null) {
        return;
    }

    // Don't repeatedly redraw the disabled screen.
    const disabledScreen =
        document.getElementById(
            'disabledScreen'
        );

    if (
        disabledScreen &&
        disabledScreen.classList.contains('active')
    ) {
        return;
    }

    // Stop any timers assciated with
    // the selection/playback screen
    clearInterval(
        statusTimer
    );

    clearInterval(
        countdownTimer
    );

    showDisabledScreen();
}

async function handleJukeboxAvailable() {
    /*
     * If we're already showing the selection screen,
     * there is nothing to do.
     */
    const selectionScreen =
        document.getElementById(
            'selectionScreen'
        );

    if (
        selectionScreen &&
        selectionScreen.classList.contains('active')
    ) {
        return;
    }

    /*
     * Don't change screens while something is playing
     * or while the lockout is active.
     */
    if (currentSequence !== null) {
        return;
    }

    try {
        await loadSequences();
    } catch (error) {
        console.error(
            'Unable to refresh sequences:',
            error
        );

        return;
    }

    showSelectionScreen();
}

/* ==========================================================================
   Load Queue
   ========================================================================== */
async function loadQueue() {
    if (!queueEnabled) {
        return;
    }

    const queueContainer =
        document.getElementById(
            'selectionQueue'
        );

    const queueList =
        document.getElementById(
            'selectionQueueList'
        );

    const queueCount =
        document.getElementById(
            'selectionQueueCount'
        );

    const queueFullMessage =
        document.getElementById(
            'selectionQueueFull'
        );

    if (
        !queueContainer ||
        !queueList ||
        !queueCount
    ) {
        return;
    }

    try {

        const response =
            await fetch(
                API_BASE + '/queue',
                {
                    cache: 'no-store'
                }
            );

        if (!response.ok) {
            return;
        }

        const data =
            await response.json();

        if (!data.success) {
            return;
        }

        const queue =
            data.queue || [];

        const queueLength =
            data.queueLength || 0;

        const queueLimit =
            data.queueLimit || 0;

        const queueFull =
            queueLength >= queueLimit;

        if (queueFullMessage) {
            queueFullMessage.classList.toggle(
                'd-none',
                !queueFull
            );
        }

        // Update count.
        queueCount.textContent =
            `${queueLength} / ${queueLimit}`;

        updateQueueAvailability(
            queueFull
        );

        // Nothing queued.
        if (queueLength === 0) {
            queueContainer.classList.add(
                'd-none'
            );

            queueList.innerHTML = '';
            return;
        }

        // Show queue.
        queueContainer.classList.remove(
            'd-none'
        );

        queueList.innerHTML = '';

        queue.forEach(
            function (
                item,
                index
            ) {
                const element =
                    document.createElement(
                        'div'
                    );

                element.className = 'selection-queue-item';

                element.innerHTML = `
                    <div class="selection-queue-position">
                        ${index + 1}
                    </div>

                    <div class="selection-queue-title">
                        ${escapeHtml(item.title)}
                    </div>
                `;

                queueList.appendChild(
                    element
                );
            }
        );

    } catch (error) {
        console.error(
            'Unable to load jukebox queue:',
            error
        );
    }
}

/* ==========================================================================
   Queue Availability
   ========================================================================== */
function updateQueueAvailability(queueFull) {
    const buttons =
        document.querySelectorAll(
            '.jukebox-sequence'
        );

    // If the queue isn't full, selections
    // are always allowed.

    if (!queueFull) {
        buttons.forEach(
            function (button) {
                button.disabled = false;

                button.classList.remove(
                    'queue-full'
                );
            }
        );

        return
    }

    // Queue is full.
    if (!currentSequence) {
        return;
    }

    buttons.forEach(
        function (button) {
            button.disabled = true;
            button.classList.add('queue-full');
        }
    );
}

// Queue Added Message
function showQueueAddedMessage(
    title,
    position
) {
    const message =
        document.getElementById(
            'jukeboxQueueMessage'
        );

    if (!message) {
        return;
    }

    message.innerHTML = `
        <div class="queue-message-title">
            Added to the queue
        </div>

        <div class="queue-message-song">
            ${escapeHtml(title)}
        </div>

        <div class="queue-message-position">
            You're number
            <strong>${position}</strong>
            in the queue
        </div>
    `;

    message.classList.remove(
        'd-none'
    );

    clearInterval(
        queueMessageTimer
    );

    // Automatically hide the message.
    queueMessageTimer =
        setTimeout(
            function () {
                message.classList.add(
                    'd-none'
                );
            },
            3000
        );
}

async function clearQueueOnUnavailable() {
    try {
        const response =
            await fetch(
                API_BASE + '/queue/clear',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':
                            'application/json'
                    },
                    cache: 'no-store'
                }
            );

        const data =
            await response.json();

        if (!data.success) {
            console.warn(
                'Unable to clear queue when jukebox became unavailable.'
            );

            return;
        }

        console.log(
            'Jukebox unavailable - queue cleared.'
        );

        // Refresh the touchscreen queue display.
        loadQueue();

    } catch (error) {
        console.error(
            'Unable to clear queue:',
            error
        );
    }
}

function showPlaybackError() {
    const message =
        document.getElementById(
            'jukeboxError'
        );

    showSelectionScreen();

    if (!message) {
        return;
    }

    message.textContent =
        'Sorry, this song could not be started. Please choose another song.';

    message.classList.remove(
        'd-none'
    );

    setTimeout(
        function () {
            message.classList.add(
                'd-none'
            );
        },
        5000
    );
}

async function detectInitialPlayback() {
    try {
        const [
            statusResponse,
            configResponse,
            sequencesResponse
        ] = await Promise.all([
            fetch(
                API_BASE + '/status',
                {
                    cache: 'no-store'
                }
            ),

            fetch(
                API_BASE + '/config',
                {
                    cache: 'no-store'
                }
            ),

            fetch(
                API_BASE + '/sequences',
                {
                    cache: 'no-store'
                }
            )
        ]);

        if (
            !statusResponse.ok ||
            !configResponse.ok ||
            !sequencesResponse.ok
        ) {
            throw new Error(
                'Unable to retrieve initial playback state.'
            );
        }

        const statusData =
            await statusResponse.json();

        const configData =
            await configResponse.json();

        const sequencesData =
            await sequencesResponse.json();

        if (
            !statusData.success ||
            !configData.success ||
            !sequencesData.success
        ) {
            throw new Error(
                'Invalid initial playback response.'
            );
        }

        // Nothing is currently playing.
        if (
            !statusData.playing ||
            !statusData.playlist
        ) {

            console.log(
                'Initial playback: idle'
            );

            return {
                type: 'idle',
                playlist: null,
                sequence: null
            };
        }

        // Current item reported by FPP.
        const currentPlaylist = statusData.playlist;

        // Configured background.
        const backgroundType =
            configData.config.backgroundType || 'sequence';

        const backgroundItem =
            configData.config
                .backgroundSequence ||
            '';

        /*
         * Determine whether FPP is currently
         * playing the configured background.
         */
        let backgroundPlaying = false;

        /*
         * Background playlist.
         *
         * FPP reports the playlist name directly,
         * so compare it without modifying it.
         */
        if (
            backgroundType === 'playlist' &&
            backgroundItem
        ) {
            backgroundPlaying =
                currentPlaylist === backgroundItem;
        }

        /*
         * Background sequence.
         *
         * FPP may report the sequence with or
         * without the .fseq extension.
         */
        if (
            backgroundType === 'sequence' &&
            backgroundItem
        ) {
            const normalisedCurrent =
                currentPlaylist.replace(
                    /\.fseq$/i,
                    ''
                );

            const normalisedBackground =
                backgroundItem.replace(
                    /\.fseq$/i,
                    ''
                );

            backgroundPlaying =
                normalisedCurrent === normalisedBackground;
        }

        // Background is playing
        if (backgroundPlaying) {
            console.log(
                'Initial playback: background',
                statusData.playlist
            );

            return {
                type: 'background',
                playlist: statusData.playlist,
                sequence: null
            };
        }

        /*
         * From this point on we are checking
         * jukebox sequences.
         *
         * Normalise the current FPP item so
         * filename.fseq and filename match.
         */
        const normalisedCurrentPlaylist =
            currentPlaylist.replace(
                /\.fseq$/i,
                ''
            );

        /*
         * See whether the currently playing
         * sequence belongs to the jukebox.
         */
        const sequence =
            (
                sequencesData.sequences ||
                []
            ).find(
                function (item) {

                    const sequenceId =
                        (
                            item.id || ''
                        ).replace(
                            /\.fseq$/i,
                            ''
                        );

                    return (
                        sequenceId ===
                        normalisedCurrentPlaylist
                    );
                }
            );

        if (sequence) {
            console.log(
                'Initial playback: jukebox',
                sequence.title
            );

            return {
                type: 'jukebox',
                playlist: statusData.playlist,
                sequence: sequence
            };
        }

        /*
         * FPP is playing something, but it
         * isn't the configured background or
         * one of our jukebox sequences.
         */
        console.log(
            'Initial playback: other',
            statusData.playlist
        );

        return {
            type: 'other',
            playlist: statusData.playlist,
            sequence: null
        };

    } catch (error) {
        console.error(
            'Unable to detect initial playback:',
            error
        );

        return {
            type: 'unknown',
            playlist: null,
            sequence: null
        };
    }
}

async function restoreInitialPlayback() {
    const state = await detectInitialPlayback();

    /*
     * For now we only need to recover when
     * a jukebox sequence is already playing.
     */
    if (
        state.type !== 'jukebox' ||
        !state.sequence
    ) {
        return;
    }

    console.log(
        'Restoring jukebox playback:',
        state.sequence.title
    );

    // Restore our local playback state.
    currentSequence = state.sequence;

    currentPlaylist = state.playlist;

    playbackStarted = true;

    playbackStartTime = null;

    /*
     * We cannot reliably know whether the
     * current song originally came from the
     * queue after a browser reload.
     *
     * This does not matter while the song is
     * playing because handlePlaybackFinished()
     * will still check the queue when it ends.
     */
    playingFromQueue = false;

    /*
     * Show the normal selection screen.
     *
     * Guests can continue browsing/selecting
     * songs while the restored song plays.
     */
    showSelectionScreen();

    /*
     * Update any "Now Playing" indication
     * already used by the selection screen.
     */
    updateSelectionNowPlaying();

    /*
    * Restore any lockout that was active
    * before the page was refreshed.
    */
    restoreLockout();

    /*
     * Start watching for the current sequence
     * to finish.
     */
    monitorPlayback();
}

restoreInitialPlayback();

function restoreLockout() {
    const storedExpiry =
        sessionStorage.getItem(
            'jukeboxLockoutExpiresAt'
        );

    // No stored lockout.
    if (!storedExpiry) {
        return false;
    }

    const expiresAt =
        parseInt(
            storedExpiry,
            10
        );

    // Invalid stored value.
    if (isNaN(expiresAt)) {

        sessionStorage.removeItem(
            'jukeboxLockoutExpiresAt'
        );

        return false;
    }

    // Calculate how many seconds remain.
    lockoutRemaining =
        Math.ceil(
            (expiresAt - Date.now()) /
            1000
        );

    /*
     * Lockout already expired while the
     * page was reloading.
     */
    if (lockoutRemaining <= 0) {
        lockoutRemaining = 0;
        lockoutActive = false;

        sessionStorage.removeItem(
            'jukeboxLockoutExpiresAt'
        );

        hidePlayingLockout();

        return false;
    }

    console.log(
        'Restoring lockout:',
        lockoutRemaining,
        'seconds remaining'
    );

    lockoutActive = true;

    updatePlayingLockout(
        lockoutRemaining
    );

    // Make sure an old timer isn't running.
    clearInterval(
        countdownTimer
    );

    /*
     * Continue counting down from the
     * remaining time.
     */
    countdownTimer =
        setInterval(
            function () {
                lockoutRemaining--;

                updatePlayingLockout(
                    lockoutRemaining
                );

                if (lockoutRemaining <= 0) {
                    lockoutRemaining = 0;

                    clearInterval(
                        countdownTimer
                    );

                    countdownTimer = null;

                    lockoutActive = false;

                    sessionStorage.removeItem(
                        'jukeboxLockoutExpiresAt'
                    );

                    hidePlayingLockout();

                    /*
                     * If playback has also finished,
                     * return to normal selection.
                     */
                    if (!playbackStarted) {
                        finishLockout();
                    }
                }
            },
            1000
        );

    return true;
}

function hideQueue() {
    const queueContainer =
        document.getElementById(
            'selectionQueue'
        );

    const queueList =
        document.getElementById(
            'selectionQueueList'
        );

    const queueFullMessage =
        document.getElementById(
            'selectionQueueFull'
        );

    if (queueContainer) {
        queueContainer.classList.add(
            'd-none'
        );
    }

    if (queueList) {
        queueList.innerHTML = '';
    }

    if (queueFullMessage) {
        queueFullMessage.classList.add(
            'd-none'
        );
    }
}

function updateDisabledScreen(status) {
    const title =
        document.getElementById(
            'disabledTitle'
        );

    const message =
        document.getElementById(
            'disabledMessage'
        );

    const footer =
        document.getElementById(
            'disabledFooter'
        );

    if (
        !title ||
        !message ||
        !footer
    ) {
        return;
    }

    // Jukebox has been manually disabled.
    if (status.enabled === false) {
        title.textContent =
            'Jukebox Unavailable';

        message.textContent =
            'Song selection is currently unavailable.';

        footer.textContent =
            'Please check back later.';

        return;
    }

    // Jukebox is enabled but outside
    // its scheduled operating hours.
    if (status.scheduled === false) {
        title.textContent =
            'Jukebox Closed';

        message.textContent =
            'Song selection is not available at the moment.';

        footer.textContent =
            'Please check back later.';

        return;
    }

    // Fallback unavailable state.
    title.textContent =
        'Jukebox Unavailable';

    message.textContent =
        'Song selection is currently unavailable.';

    footer.textContent =
        'Please check back later.';
}