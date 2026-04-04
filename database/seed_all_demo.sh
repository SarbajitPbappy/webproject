#!/usr/bin/env bash
# Same as: php database/run_full_demo_seed.php
# Optional args are passed to bangladesh_demo_seed.php (e.g. --force)

set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
exec php database/run_full_demo_seed.php "$@"
