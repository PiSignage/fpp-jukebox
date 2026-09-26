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

log "=== Remove placeholder image ==="
rm /home/fpp/media/images/placeholder.jpg

log "=== Jukebox uninstall complete. Config and media left in place. ==="