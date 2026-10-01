#!/bin/bash
# fpp-uninstall.sh - Jukebox plugin uninstaller
# Called by FPP when the plugin is removed. Mirrors fpp_install.sh's

set -e

PLUGIN_DIR="$(dirname "$0")"
PLUGIN_NAME="fpp-jukebox"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

log "=== Jukebox uninstall started ==="

JUKEBOX_IMG_PLACEHOLDER="/home/fpp/media/images/placeholder.jpg"

if [[ -f "$JUKEBOX_IMG_PLACEHOLDER" ]]; then
    log "Removing jukebox placeholder image..."
    rm -f /home/fpp/media/images/placeholder.jpg
fi

log "=== Jukebox placeholder image removed ==="

# ── Remove Jukebox shortcuts ──
JUKEBOX_SHORTCUT="${FPPDIR:-/opt/fpp}/www/jukebox.php"

if [[ -f "$JUKEBOX_SHORTCUT" ]]; then
    log "Removing /jukebox.php shortcut..."
    rm -f "$JUKEBOX_SHORTCUT"
fi

log "=== Jukebox shortcut removed ==="

log "=== Jukebox uninstall complete. Config and media left in place. ==="