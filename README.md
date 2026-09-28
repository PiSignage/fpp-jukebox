# FPP Jukebox

FPP Jukebox is a Falcon Player (FPP) plugin that provides a touchscreen-friendly jukebox interface, allowing guests to select individual FPP sequences while FPP continues to manage the main display.

The plugin is designed for interactive displays such as Christmas light shows, events, and other FPP installations where visitors can choose sequences without needing access to the FPP administration interface.

## Features

- Touchscreen guest interface
- Individual FPP sequence selection
- Enable/disable individual sequences
- Custom sequence titles
- Custom sequence ordering
- Artwork support using the FPP media images directory
- Configurable jukebox title
- Manual jukebox enable/disable control
- Optional scheduled availability with multiple time windows
- Configurable selection lockout with refresh persistence
- Live FPP playback status monitoring
- Automatic playback recovery after a touchscreen refresh
- Background Sequence and Background Playlist support
- Immediate interruption of a background playlist for guest selections
- Automatic return to background playback
- Optional song queue with configurable limit
- Optional duplicate songs in the queue
- Queue position and status shown on the touchscreen
- Admin queue management
- Automatic queue clearing when the jukebox becomes unavailable
- Per-sequence play statistics
- Statistics reset
- Live admin status page
- Handling of temporary FPP/API communication failures

## Requirements

- Falcon Player (FPP) 8 or later
- One or more FPP sequences
- A touchscreen, tablet, browser, or other device for the guest interface
- Artwork files are optional

## Installation

Install the plugin through the FPP Plugin Manager or clone the repository into the appropriate FPP plugin directory.

The plugin installation process creates the required plugin data directories and default configuration.

After installation, open the plugin configuration page from the FPP Plugin Manager.

## Guest Interface

The guest interface is provided by the plugin and communicates with the plugin API.

It is designed to run full-screen on a touchscreen or other browser-based kiosk device.

Guests can:

- View available songs/sequences
- View sequence artwork
- Select a sequence
- See the currently playing jukebox song
- See lockout information
- See queued songs when the queue is enabled
- See when the queue is full
- See when the jukebox is unavailable

The interface continuously monitors FPP playback so it can react when a selected sequence starts and finishes.

## Configuration

Sequences are configured from the plugin administration page.

Each jukebox entry can have:

- FPP sequence
- Display title
- Artwork
- Enabled/disabled state
- Display order

Only enabled sequences are available to guests.

Sequence files are provided by FPP and are not uploaded through the jukebox plugin.

## Artwork

Artwork is stored in the existing FPP media images directory and should be uploaded using the normal FPP File Manager.

The plugin allows an image to be associated with each configured jukebox sequence.

## Jukebox Title

A custom title can be configured for the touchscreen interface.

For example:

`Jones Family Lights`

## Enable / Disable

The jukebox has a manual enabled setting.

When disabled:

- Guests cannot start new jukebox songs
- The touchscreen shows the unavailable state
- Waiting queue entries are not started

Normal FPP background playback remains under FPP control.

## Scheduled Availability

The jukebox can optionally be restricted to configured time windows.

Multiple schedule windows can be configured, for example:

- 17:00 - 19:00
- 20:00 - 22:00

When scheduling is enabled, the jukebox is available only while the current time falls within one of the configured windows.

If availability ends while a jukebox song is already playing, the current song is allowed to finish. Songs still waiting in the queue are discarded, and normal background playback can then continue.

## Selection Lockout

A configurable lockout prevents repeated immediate selections from the touchscreen.

The lockout begins when a jukebox selection is started. Its state is stored by the touchscreen so refreshing the page does not bypass it.

If the selected song is still playing when the lockout expires, the selection interface becomes available again.

If the song finishes before the lockout expires, the lockout screen remains until the remaining lockout time has expired.

## Background Playback

FPP Jukebox supports two background playback modes:

- Sequence
- Playlist

The background remains controlled by FPP. The jukebox interrupts it only when required for a guest selection.

The configured background is also used to distinguish normal background playback from an active jukebox selection.

### Background Sequence

When Background Type is set to `Sequence`, the configured FPP sequence is treated as the normal background playback.

Guest selections use the plugin's standard FPP sequence playback behaviour.

When jukebox playback finishes, normal FPP background behaviour can continue.

### Background Playlist

When Background Type is set to `Playlist`, select the FPP playlist that normally runs in the background.

Guest songs are started using FPP's `Insert Playlist Immediate` command.

This interrupts the running background playlist. When the inserted jukebox sequence finishes, FPP resumes the background playlist from the point at which it was interrupted.

```text
Background Playlist
        |
        v
Guest selects Song A
        |
        v
Song A interrupts Background
        |
        v
Song A finishes
        |
        v
Background Playlist resumes
```

If the queue is disabled and another song is selected while a jukebox song is already playing, the current jukebox song is stopped and the new selection is inserted immediately.

```text
Background Playlist
        |
        v
Song A
        |
        | New selection
        v
Song B
        |
        v
Background Playlist resumes
```

## Queue

The jukebox queue is optional.

When the queue is disabled, guest selections are played immediately according to the configured background playback mode.

When the queue is enabled:

1. If only the configured background is playing, the selected song starts immediately.
2. If a jukebox song is already playing, additional selections are added to the queue.
3. When the current jukebox song finishes, the next queued song starts automatically.
4. This continues until the queue is empty.
5. When no queued songs remain, normal FPP background playback continues.

With a background playlist, queued songs use `Insert Playlist Immediate`, allowing the background playlist to resume and then be interrupted by the next queued jukebox song.

```text
Background Playlist
        |
        v
Song A
        |
        +---- Song B queued
        |
        +---- Song C queued
        |
        v
Song B
        |
        v
Song C
        |
        v
Background Playlist resumes
```

### Queue Limit

A maximum queue length can be configured.

When the queue reaches the configured limit:

- Additional songs cannot be added
- The touchscreen shows that the queue is full
- Song selection availability is updated accordingly

### Duplicate Queue Songs

Duplicate songs can optionally be allowed in the queue.

When duplicate queue entries are disabled, a song already waiting in the queue cannot be added again.

### Queue Administration

Administrators can:

- View queued songs
- View the current queue length
- Remove individual queue entries
- Clear the queue

## Playback Monitoring

The touchscreen monitors FPP playback status to detect:

- Background playback
- A jukebox song starting
- A jukebox song finishing
- Transition to the next queued song
- Return to background playback

The plugin uses FPP's current playlist information to distinguish the configured background from jukebox playback.

Temporary status request failures are tolerated before the touchscreen reports a playback error.

## Page Refresh Recovery

If the touchscreen page is refreshed while a jukebox song is playing, the interface checks the current FPP state.

It can identify whether FPP is currently:

- Idle
- Playing the configured background
- Playing a configured jukebox sequence
- Playing another item

If a jukebox song is detected, the touchscreen restores the Now Playing state and continues monitoring playback.

Any active lockout is also restored.

## Play Statistics

The plugin records play statistics for configured jukebox sequences.

Plays are recorded when a song actually starts, including songs started from the queue.

The administration interface can display these statistics and provides an option to reset them.

## Admin Status

The plugin includes a live administration status page showing information such as:

- Jukebox availability
- Manual enabled state
- Schedule state
- Current FPP playback
- Background Sequence or Background Playlist state
- Jukebox playback
- Queue information
- Current/Now Playing information
- Play statistics

The status page understands both configured background types and distinguishes background playback from guest jukebox playback.

## API

The plugin exposes API endpoints used by the administration and touchscreen interfaces.

The API base path is:

```text
/api/plugin/fpp-jukebox
```

### Sequences

```text
GET /sequences
GET /fpp-sequences
```

`/sequences` returns configured guest jukebox sequences.

`/fpp-sequences` returns sequences available from FPP.

### Playlists

```text
GET /fpp-playlists
```

Returns playlists available from FPP.

### Artwork

```text
GET /artwork
```

Returns available artwork.

### Status

```text
GET /status
```

Returns current jukebox availability and FPP playback status, including enabled state, scheduled state, availability, playing state, FPP status, current playlist, and lockout duration.

### Play

```text
POST /play
```

Starts a selected jukebox sequence or adds it to the queue when appropriate.

### Configuration

```text
GET /config
```

Returns configuration required by the touchscreen interface.

### Queue

```text
GET /queue
POST /queue/next
POST /queue/remove
POST /queue/clear
```

These endpoints retrieve the queue, start the next queued song, remove an item, and clear the queue.

### Statistics

```text
GET /statistics
POST /statistics/reset
```

Returns or resets jukebox play statistics.

## FPP Integration

The plugin uses FPP's local APIs to retrieve playback information and control sequences.

Important FPP APIs and commands used by the plugin include:

```text
/api/fppd/status
/api/sequence
/api/playlists
/api/playlist/{sequence}.fseq/start
/api/playlists/stop
```

For background playlists, guest songs are inserted using:

```text
/api/command/Insert%20Playlist%20Immediate/{sequence}.fseq
```

`Insert Playlist Immediate` is important because FPP suspends the background playlist, plays the inserted sequence, and then resumes the background playlist.

## Playback Behaviour Summary

### Queue Disabled + Background Playlist

```text
Background
   |
Song A selected
   |
Song A plays
   |
Lockout expires
   |
Song B selected
   |
Song A is stopped
   |
Song B plays
   |
Background resumes
```

### Queue Enabled + Background Playlist

```text
Background
   |
Song A selected and played
   |
Song B selected -> queued
Song C selected -> queued
   |
Song A finishes
   |
Song B plays
   |
Song C plays
   |
Background resumes
```

### Jukebox Becomes Unavailable

```text
Current jukebox song continues
        |
Waiting queue is discarded
        |
Current song finishes
        |
Background playback continues
```

## Troubleshooting

### First song is added to the queue instead of playing

Check that the configured Background Type and Background Sequence/Playlist match what FPP is currently playing.

The configured background is deliberately treated differently from a jukebox song. A selection should interrupt the background rather than being queued behind it.

### Background playlist does not resume

Confirm that Background Type is set to `Playlist` and that the correct FPP playlist is selected.

Background playlist playback relies on FPP's `Insert Playlist Immediate` behaviour.

### Queue does not advance

Check the plugin status page and confirm that the current jukebox song has actually finished.

The next queue item is started only after the plugin detects that current jukebox playback has ended.

### Touchscreen shows a playback error

Check that FPP is running and that the local FPP API is responding.

The touchscreen tolerates temporary status failures, but repeated failures cause the current playback state to be treated as unknown.

### Artwork is missing

Confirm that the image exists in the FPP media images directory and that the correct artwork has been selected for the sequence.

## Notes

FPP remains responsible for the main show schedule and background playback.

The jukebox is designed to temporarily take control when a guest makes a selection and then return control to the normal FPP playback flow.

For background playlists, the plugin intentionally uses FPP's insertion behaviour rather than restarting the background playlist. This preserves the background playlist position when guest songs interrupt it.

## License

GPLv2