#!/bin/bash
# fpp-install.sh - Jukebox plugin installer
# Called by FPP when the plugin is installed or updated.

set -e

PLUGIN_DIR="$(dirname "$0")"
PLUGIN_NAME="fpp-jukebox"

# Resolve FPP's logs directory the documented way (supports a relocated
# media directory) rather than hard-coding /home/fpp/media/logs, and use
# the single FPP-conformant log file (plugin-<repoName>.log) for both this
# install script and the daemon, per the plugin guidelines' logging rules.
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common" 2>/dev/null || true
LOGDIR="$(getSetting logDirectory 2>/dev/null)"
LOGDIR="${LOGDIR:-/home/fpp/media/logs}"
LOGFILE="${LOGDIR}/plugin-${PLUGIN_NAME}.log"

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
    mkdir -p "$LOGDIR" 2>/dev/null || true
    echo "$msg" >> "$LOGFILE" 2>/dev/null || echo "$msg"
}

log "=== Jukebox install started (user=$(whoami), , uid=$(id -u))"

# ── Create media directories ──
# (log() already mkdir -p's $LOGDIR on every call)
# Do this FIRST so the media log path is available.
mkdir -p /home/fpp/media/config
mkdir -p /home/fpp/media/images

# ── Make scripts executable ──
log "Setting script permissions..."
chmod +x "${PLUGIN_DIR}/scripts/"*.sh 2>/dev/null || true

# ── Change logfile owner to fpp ──
chown fpp:fpp "${LOGFILE}"

# ── Write default config if none exists ── 
CONFIG="${FPPDIR}/config/plugin.${PLUGIN_NAME}.json"
if [[ ! -f "$CONFIG" ]]; then
    log "Write default config to $CONFIG"
    cp "${PLUGIN_DIR}/config/${PLUGIN_NAME}.json.exmple" "$CONFIG" 2>/dev/null || \
cat > "$CONFIG" <<'JSONEOF'
{
    enabled": true,
    "scheduleEnabled": false,
    "schedules": [
        [
            "start": "17:00",
            "end": "21:00"
        ]
    ],
    "lockoutSeconds": 30,
    "lockoutStarts": "play",
    "backgroundSequence": "",
    "sequences": []	
}
JSONEOF
fi

# ── Write default stats if none exists ──
STATS="${FPPDIR}/config/plugin.${PLUGIN_NAME}-stats.json"
if [[ ! -f "$STATS" ]]; then
    log "Write default stats to $STATS"
    cp "${PLUGIN_DIR}/config/${PLUGIN_NAME}-stats.json.exmple" "$CONFIG" 2>/dev/null || \
cat > "$STATS" <<'JSONEOF'
{
    "totalPlays" => 0,
    "sequences" => []
}
JSONEOF
fi

PLACEHOLDERIMAGE=/home/fpp/media/images/placeholder.jpg
if [[ ! -f "$PLACEHOLDERIMAGE" ]]; then
	log "=== Jukebox Placehoolder image not found, Copy placeholder image to images folder ==="
	cp "/home/fpp/media/plugins/fpp-jukebox/img/assets/placeholder.jpg" "${PLACEHOLDERIMAGE}"
	chown fpp:fpp "${PLACEHOLDERIMAGE}"
else
    log "=== Jukebox Place holder image found ==="
fi

log "=== Jukebox install complete ==="

exit 0