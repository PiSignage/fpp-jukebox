#!/bin/bash

set -e

PLUGIN_NAME="fpp-plugin-jukebox"

. "${FPPDIR}/scripts/common"

CONFIG_FILE="${FPPDIR}/config/plugin.${PLUGIN_NAME}"

LOG_FILE="${LOGDIR}/plugin-${PLUGIN_NAME}.log"


echo "$(date '+%Y-%m-%d %H:%M:%S') Removing ${PLUGIN_NAME}" \
    >> "${LOG_FILE}"


if [ -f "${CONFIG_FILE}" ]; then
    rm -f "${CONFIG_FILE}"
fi


echo "$(date '+%Y-%m-%d %H:%M:%S') ${PLUGIN_NAME} removed" \
    >> "${LOG_FILE}"

exit 0