const API_BASE = '/api/plugin/fpp-jukebox';

let lockoutSeconds = 30;

let statusTimer = null;
let countdownTimer = null;
let availabilityTimer = null;
let queueTimer = null;
let lockoutRemaining = 0;

let currentSequence = null;

let playbackStarted = false;
let lockoutActive = false;

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
                No songs are currently available.
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
            console.warn(
                'FPP status request failed:',
                response.status
            );
            return;
        }

        const data =
            await response.json();

        if (!data.success) {
            console.warn(
                'Invalid FPP status response.'
            );
            return;
        }

        console.log(
            'Jukebox playback status:',
            data.playing
        );

        // Playback has started
        if (data.playing) {
            playbackStarted = true;
            updateSelectionNowPlaying();
            return;
        }

        /*
         * ---------------------------------------------------------------
         * Playback has finished
         * ---------------------------------------------------------------
         *
         * We ONLY consider playback finished if we previously
         * saw FPP report that something was playing.
         */

        if (
            playbackStarted &&
            !data.playing
        ) {
            clearInterval(
                statusTimer
            );

            handlePlaybackFinished();
        }
    } catch (error) {
        console.error(
            'Playback status error:',
            error
        );
    }
}

// Playback finished
async function handlePlaybackFinished() {
    console.log(
        'Jukebox sequence finished.'
    );

    playbackStarted = false;

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

    updatePlayingLockout(
        lockoutRemaining
    );

    // If timeout is zero, there is no lockout.
    if (lockoutRemaining <= 0) {
        countdownTimer = null;
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

    loadQueue();

    clearInterval(
        queueTimer
    );

    queueTimer =
        setInterval(
            loadQueue,
            1000
        );
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

        // Jukebox is unavailable.
        if (!data.available) {
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
        <strong>
            ${escapeHtml(title)}
        </strong>
        added to the queue
        <span>
            Position ${position}
        </span>
    `;

    message.classList.remove(
        'd-none'
    );

    // Automatically hide the message.
    setTimeout(
        function () {
            message.classList.add(
                'd-none'
            );
        },
        3000
    );
}