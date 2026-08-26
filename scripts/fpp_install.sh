#!/bin/bash
# fpp_install.sh — Jukebox plugin installer
# Called by FPP when the plugin is installed or updated.

PLUGIN_DIR="$(dirname "$0")"

# Resolve FPP's logs directory the documented way (supports a relocated
# media directory) rather than hard-coding /home/fpp/media/logs, and use
# the single FPP-conformant log file (plugin-<repoName>.log) for both this
# install script and the daemon, per the plugin guidelines' logging rules.
: "${FPPDIR:=/opt/fpp}"
. "${FPPDIR}/scripts/common" 2>/dev/null || true
LOGDIR="$(getSetting logDirectory 2>/dev/null)"
LOGDIR="${LOGDIR:-/home/fpp/media/logs}"
LOGFILE="${LOGDIR}/plugin-fpp-jukebox.log"

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $*"
    mkdir -p "$LOGDIR" 2>/dev/null || true
    echo "$msg" >> "$LOGFILE" 2>/dev/null || echo "$msg"
}

log "=== Jukebox install started (user=$(whoami), uid=$(id -u)) ==="

# ── Create media directories ─────────────────────────────────────
# (log() already mkdir -p's $LOGDIR on every call)
# Do this FIRST so the media log path is available.
mkdir -p /home/fpp/media/config
mkdir -p /home/fpp/media/images

# ── Make scripts executable ──────────────────────────────────────
log "Setting script permissions..."
chmod +x "${PLUGIN_DIR}/scripts/"*.sh 2>/dev/null || true

# ── Write default config if none exists ─────────────────────────
CONFIG="/home/fpp/media/config/plugin.fpp-jukebox.json"
if [[ ! -f "$CONFIG" ]]; then
	log "Writing default config to $CONFIG"
  cp "${PLUGIN_DIR}/config/fpp-jukebox.json.example" "$CONFIG" 2>/dev/null || \
  cat > "$CONFIG" <<'JSONEOF'
{
	"remote_ip": "",
    "static_sequence": "",
    "ticker_other_info": "",
    "ticker_other_info_location": "before",
    "font": "Comic-Queens",
    "hide_images": "no",
    "button_timeout": "",
    "show_logo": "",
    "logo_location": "left",
    "show_name": "",
    "additional_info": "",
    "start_time": "",
    "end_time": "",
    "items": []
}
JSONEOF
fi

# ── Change logfile owner to fpp ──────────────────────────────────
log "=== Jukebox config and log file owner to fpp insted of root ==="
chown fpp:fpp "${LOGFILE}"
chown fpp:fpp "${CONFIG}"

PLACEHOLDERIMAGE=/home/fpp/media/images/placeholder.jpg
if [ -f "$PLACEHOLDERIMAGE" ]
then
	echo "Placehoolder image found"
else
	echo "Placehoolder image not found, Copy placeholder image to images folder"
	cp "${PLUGIN_DIR}/img/placeholder.jpg" "${PLACEHOLDERIMAGE}"
	chown fpp:fpp "${PLACEHOLDERIMAGE}"
fi

log "=== Jukebox install complete ==="

source ${FPPDIR}/scripts/common; setSetting restartFlag 1
exit 0