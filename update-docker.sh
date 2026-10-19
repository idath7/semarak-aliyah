#!/usr/bin/env bash
# ==============================================================================
# ALIAS WRAPPER: Menjalankan docker-update.sh
# ==============================================================================
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" >/dev/null 2>&1 && pwd)"
bash "${DIR}/docker-update.sh" "$@"
