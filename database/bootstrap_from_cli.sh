#!/usr/bin/env bash
# Import HostelEase schema + run CLI seeds from your machine.
#
# IMPORTANT (Railway from your Mac):
# - Use the PUBLIC MySQL host (e.g. from Railway → MySQL → Variables / Connect).
#   Hostnames like mysql.railway.internal only work inside Railway, not on your laptop.
# - mysql password: use --password=...  OR  -pPASSWORD  with NO space after -p.
#   Wrong:  -p "secret"   (mysql prints help / mis-parses args)
#   Right:  --password="secret"   or   -psecret
#
# Examples (from the hostelease/ directory):
#   export DB_HOST="containers-us-west-xxx.railway.app"   # your public proxy host
#   export DB_PORT="3306"
#   export DB_USER="root"
#   export DB_PASS="your-password"
#   export DB_NAME="railway"
#   export DB_SSL="true"    # if your provider requires TLS
#   ./database/bootstrap_from_cli.sh all
#
# Railway CLI (optional): brew install railway  OR  npm i -g @railway/cli
# Then: railway run -- php setup_db.php   (uses service env; runs in Railway context)

set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

usage() {
  cat <<'EOF'
Import schema + seed from your machine. Railway: use PUBLIC MySQL host, not *.railway.internal.
mysql password must be --password="..." or -psecret (no space after -p).

Usage: bootstrap_from_cli.sh import-sql | seed-admin | all

Export DB_HOST DB_PORT DB_USER DB_PASS DB_NAME (and DB_SSL=true if required) first.
EOF
  exit 1
}

require_db_env() {
  : "${DB_HOST:?Set DB_HOST (public host, not *.railway.internal from your Mac)}"
  : "${DB_USER:?Set DB_USER}"
  : "${DB_PASS:?Set DB_PASS}"
  : "${DB_NAME:?Set DB_NAME}"
  DB_PORT="${DB_PORT:-3306}"
}

import_sql() {
  require_db_env
  local -a ssl=( )
  if [[ "${DB_SSL:-false}" == "true" ]]; then
    ssl=( --ssl-mode=REQUIRED )
  fi
  echo "Importing database/hostelease.sql into ${DB_HOST}:${DB_PORT}/${DB_NAME} ..."
  mysql "${ssl[@]}" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" --password="$DB_PASS" "$DB_NAME" < "$ROOT/database/hostelease.sql"
  echo "Schema import finished."
}

seed_admin() {
  require_db_env
  if ! command -v php >/dev/null 2>&1; then
    echo "php not found in PATH; install PHP or run seeds on a machine with PHP."
    exit 1
  fi
  export DB_HOST DB_PORT DB_USER DB_PASS DB_NAME
  export DB_SSL="${DB_SSL:-false}"
  php "$ROOT/database/seeds/admin_seed.php"
}

cmd="${1:-}"
case "$cmd" in
  import-sql) import_sql ;;
  seed-admin) seed_admin ;;
  all)
    import_sql
    seed_admin
    echo ""
    echo "Optional demo data (see README):"
    echo "  php database/migrations/migrate_user_notifications.php"
    echo "  php database/migrations/migrate_students_registration_profile.php"
    echo "  php database/seeds/bangladesh_demo_seed.php"
    ;;
  *) usage ;;
esac
