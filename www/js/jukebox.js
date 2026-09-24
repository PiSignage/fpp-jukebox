const API_BASE = '/api/plugin/fpp-plugin-jukebox';

let lockoutSeconds = 30;

let statusTimer = null;
let countdownTimer = null;
let availabilityTimer = null;
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
    if (lockoutActive) {
        return;
    }

    // Clear any old timers.
    clearInterval(
        statusTimer
    );

    clearInterval(
        countdownTimer
    );

    // Remember selected sequence.
    currentSequence = sequence;

    // Reset playback state.
    playbackStarted = false;

    // Immediately show Now Playing.
    showPlayingScreen(
        sequence
    );

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

        currentSequence = null;

        playbackStarted = false;

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
function handlePlaybackFinished() {
    console.log(
        'Jukebox sequence finished.'
    );

    playbackStarted = false;

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
    showScreen(
        'selectionScreen'
    );
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