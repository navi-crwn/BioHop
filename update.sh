#!/usr/bin/env bash
# BioHop: update. Run it in the folder where BioHop is installed. --help lists the options.
APP='BioHop'; SLUG='biohop'; REPO='BioHop'; PORT_DEFAULT=8002
# ---------------------------------------------------------------------------
# Shared helpers (the same in every HOP installer). Plain bash 3.2+, no extras.
# ---------------------------------------------------------------------------
set -eu
set -o pipefail

if [ -t 1 ]; then
  B=$'\033[1m'; D=$'\033[2m'; R=$'\033[31m'; G=$'\033[32m'; Y=$'\033[33m'; C=$'\033[36m'; Z=$'\033[0m'
else
  B=''; D=''; R=''; G=''; Y=''; C=''; Z=''
fi
say()  { printf '%s\n' "$*"; }
hdr()  { printf '\n%s== %s ==%s\n' "$B" "$*" "$Z"; }
ok()   { printf ' %s✓%s %s\n' "$G" "$Z" "$*"; }
warn() { printf ' %s!%s %s\n' "$Y" "$Z" "$*" >&2; }
bad()  { printf ' %s✗%s %s\n' "$R" "$Z" "$*" >&2; }
die()  { bad "$*"; exit 1; }
have() { command -v "$1" >/dev/null 2>&1; }
# PHP_BIN: the interpreter for php, artisan and composer. Set it in the environment to override;
# otherwise php8.3 when it is installed (the version production runs), else php.
if [ -z "${PHP_BIN:-}" ]; then if have php8.3; then PHP_BIN=php8.3; else PHP_BIN=php; fi; fi
# composer_run ARGS...: composer under PHP_BIN (a phar or a script with a php shebang); other launchers run as they are.
composer_run() {
  local c; c="$(command -v composer)" || return 127
  if head -n 1 "$c" 2>/dev/null | grep -q php; then "$PHP_BIN" "$c" "$@"; else "$c" "$@"; fi
}

YES=0; DRY=0; STAR=ask; INTERACTIVE=0
[ -t 0 ] && INTERACTIVE=1
# Run through "curl ... | bash": stdin is the script, so ask on the terminal instead.
claim_tty() {
  [ "$INTERACTIVE" = 1 ] && return 0
  case " $* " in *" --yes "*|*" -y "*) return 0 ;; esac
  if (: </dev/tty) 2>/dev/null; then exec </dev/tty; INTERACTIVE=1; fi
}

# ask VAR "Question" "default": skips the question when VAR is already set
# (by a flag or the environment), and takes the default with --yes.
ask() {
  local var="$1" q="$2" def="${3-}" cur reply
  eval "cur=\${$var-}"
  [ -n "$cur" ] && return 0
  if [ "$YES" = 1 ] || [ "$INTERACTIVE" = 0 ]; then eval "$var=\$def"; return 0; fi
  if [ -n "$def" ]; then printf ' %s?%s %s %s[%s]%s ' "$C" "$Z" "$q" "$D" "$def" "$Z"; else printf ' %s?%s %s ' "$C" "$Z" "$q"; fi
  IFS= read -r reply || reply=''
  eval "$var=\${reply:-\$def}"
}
# ask_secret VAR "Question": no echo; empty means "generate" or "skip" for the caller.
ask_secret() {
  local var="$1" q="$2" cur reply
  eval "cur=\${$var-}"
  [ -n "$cur" ] && return 0
  if [ "$YES" = 1 ] || [ "$INTERACTIVE" = 0 ]; then eval "$var=''"; return 0; fi
  printf ' %s?%s %s ' "$C" "$Z" "$q"
  IFS= read -rs reply || reply=''; printf '\n'
  eval "$var=\$reply"
}
# choose VAR "Question" default opt...: the answer is one of the options.
choose() {
  local var="$1" q="$2" def="$3" cur a ok_=0; shift 3
  eval "cur=\${$var-}"
  if [ -n "$cur" ]; then for a in "$@"; do [ "$a" = "$cur" ] && ok_=1; done; [ $ok_ = 1 ] && return 0; die "--$var: '$cur' is not one of: $*"; fi
  if [ "$YES" = 1 ] || [ "$INTERACTIVE" = 0 ]; then eval "$var=\$def"; return 0; fi
  while :; do
    ask _pick "$q ($*)" "$def"; a="$_pick"; _pick=''
    for ok_ in "$@"; do [ "$ok_" = "$a" ] && { eval "$var=\$a"; return 0; }; done
    warn "Please type one of: $*"
  done
}
# confirm "Question" y|n: true when the answer is yes.
confirm() {
  local q="$1" def="${2:-y}" reply hint='[Y/n]'
  [ "$def" = n ] && hint='[y/N]'
  if [ "$YES" = 1 ] || [ "$INTERACTIVE" = 0 ]; then [ "$def" = y ]; return; fi
  printf ' %s?%s %s %s%s%s ' "$C" "$Z" "$q" "$D" "$hint" "$Z"
  IFS= read -r reply || reply=''
  case "${reply:-$def}" in y|Y|yes|YES|Yes) return 0 ;; *) return 1 ;; esac
}
# proceed "Question": like confirm (default no), but --yes means yes. For steps the person asked for by name.
proceed() { [ "$YES" = 1 ] && return 0; confirm "$1" n; }
rand_hex() { if have openssl; then openssl rand -hex "$1"; else LC_ALL=C tr -dc 'a-f0-9' </dev/urandom | head -c $(( $1 * 2 )); fi; }

# set_env FILE KEY VALUE: replace "KEY=" or "#KEY=", or append. The new content is written to a
# temporary file next to FILE (same owner and mode, never wider than FILE) and renamed over it, so
# a full disk or an interrupted run cannot leave a cut-off .env behind.
set_env() {
  local f="$1" k="$2" v="$3" tmp
  case "$v" in *[[:space:]\#\"\$\\]*|'') v="\"$(printf '%s' "$v" | sed 's/[\\"$]/\\&/g')\"" ;; esac
  tmp="$(umask 077; mktemp "$f.XXXXXX")" || return 1
  trap 'rm -f "$tmp"; trap - RETURN' RETURN
  [ ! -e "$f" ] || cp -p "$f" "$tmp" || return 1
  if grep -qE "^#?[[:space:]]*${k}=" "$f" 2>/dev/null; then
    K="$k" V="$v" awk 'BEGIN{d=0; k=ENVIRON["K"]; v=ENVIRON["V"]} { if (!d && $0 ~ "^#?[ \t]*" k "=") { print k "=" v; d=1 } else print }' "$f" >"$tmp" || return 1
  else
    { cat "$f" 2>/dev/null || true; printf '%s=%s\n' "$k" "$v"; } >"$tmp" || return 1
  fi
  mv -f "$tmp" "$f"
}
# get_env FILE KEY: the value, without the quotes and escapes set_env adds.
get_env() {
  local raw; raw="$(sed -n "s/^$2=//p" "$1" 2>/dev/null | head -1)"
  case "$raw" in \"*\") raw="${raw#\"}"; raw="${raw%\"}"; raw="$(printf '%s' "$raw" | sed 's/\\\([\\"$]\)/\1/g')" ;; esac
  printf '%s\n' "$raw"
}

port_free() {
  if have lsof; then ! lsof -nP -iTCP:"$1" -sTCP:LISTEN >/dev/null 2>&1
  elif have ss; then ! ss -ltn "sport = :$1" 2>/dev/null | grep -q LISTEN
  else ! (exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null; fi
}
free_port() { local p="$1"; while ! port_free "$p"; do p=$((p + 1)); done; echo "$p"; }
disk_free_mb() { df -Pm "${1:-.}" 2>/dev/null | awk 'NR==2{print $4}'; }
version_ge() { [ "$(printf '%s\n%s\n' "$2" "$1" | sort -V | head -1)" = "$2" ]; }  # version_ge HAVE NEED
open_url() { if have open; then open "$1" >/dev/null 2>&1 || true; elif have xdg-open; then xdg-open "$1" >/dev/null 2>&1 || true; else say "   $1"; fi; }

# After a good install: one question, never silent, never repeated, nothing sent anywhere.
star_prompt() {
  local url="https://github.com/navi-crwn/$REPO"
  [ "$STAR" = no ] && { say "   ${D}If you like $APP: $url${Z}"; return 0; }
  if [ "$STAR" = yes ] || confirm "Open the GitHub page of $APP so you can star it?" y; then
    if have gh && gh auth status >/dev/null 2>&1 && confirm "Star it right now with your GitHub login (gh)?" n; then
      gh api -X PUT "user/starred/navi-crwn/$REPO" >/dev/null 2>&1 && ok "Starred. Thank you!" || { warn "Could not star it; opening the page instead."; open_url "$url"; }
    else
      open_url "$url"
    fi
  fi
  say "   ${D}GitHub: $url${Z}"
}

# Run a step, show it, stop with a clear message when it fails.
step() { local msg="$1"; shift; printf ' %s…%s %s\n' "$D" "$Z" "$msg"; if [ "$DRY" = 1 ]; then say "   (dry run) $*"; return 0; fi; "$@" || die "Step failed: $msg"; }

common_usage() {
cat <<USAGE
Options:
  -y, --yes             No questions: use the defaults and any flags below
      --dir DIR         Install into DIR (default: this folder, or ~/$SLUG when run from a download)
      --name NAME       Name of your site, shown in the pages
      --url URL         Public address (default http://localhost:PORT)
      --port N          Port for the built-in server (default $PORT_DEFAULT)
      --run MODE        $RUN_MODES
      --hub MODE        existing | new | standalone$HUB_NOTE
      --hub-url URL     Address of the HOP ID hub (with --hub existing)
      --hub-secret S    Shared app secret (or set HOP_SSO_SECRET in the environment)
      --star / --no-star
      --dry-run         Show the steps without running them
  -h, --help
USAGE
}
main() {
# ---------------------------------------------------------------------------
# Update BioHop (Laravel): backup, new code, packages, front end, migrations.
# If a step fails before the database changes, the previous version is put back;
# from the database step on, the site stays in maintenance mode and the way back is printed.
# ---------------------------------------------------------------------------
FORCE=0; NO_BACKUP=0
while [ $# -gt 0 ]; do
  case "$1" in
    -y|--yes) YES=1 ;; --dry-run) DRY=1 ;; --force) FORCE=1 ;; --no-backup) NO_BACKUP=1 ;;
    -h|--help) say "Usage: ./update.sh [--yes] [--force] [--no-backup] [--dry-run]"; say "  --force      update even if you changed tracked files (your changes may conflict)"; say "  --no-backup  do not copy the database first (with --yes a missing copy is an error unless you pass this)"; exit 0 ;;
    *) bad "Unknown option: $1"; exit 2 ;;
  esac; shift
done
SELF_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-$0}")" && pwd)"
cd "$SELF_DIR"
[ -f artisan ] || die "Run this from the BioHop folder (artisan not found here)."
[ -f .env ] || die "No .env here: BioHop is not installed in this folder. Run ./install.sh first."
have composer && have npm && have "$PHP_BIN" || die "php, composer and npm are needed (run ./install.sh to see what is missing)."
have git && [ -d .git ] || die "This folder is not a git clone, so it cannot update itself. Download the new version and run ./install.sh again."

hdr "Updating BioHop"
BEFORE="$(git describe --tags --always 2>/dev/null || git rev-parse --short HEAD)"
BEFORE_SHA="$(git rev-parse HEAD)"
LOCAL_CHANGES=0
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
  if [ "$FORCE" = 0 ]; then
    git status --short --untracked-files=no | head -10
    die "You changed files here (listed above). Commit or stash them, or run ./update.sh --force."
  fi
  LOCAL_CHANGES=1
fi
confirm "Update now? (a copy of the database is saved first)" y || { say "Nothing was changed."; exit 0; }

# 1. A copy of the database, in backups/ (private: only you can read it).
BACKUP_FILE=''; CNF=''; TMPDUMP=''
BACKUP_WHY=''
backup_sqlite() {
  local src="$1" dest="$2" out
  case "$dest" in *\'*|*\"*) BACKUP_WHY="the folder name has a quote in it"; return 1 ;; esac
  if have sqlite3 && ( umask 077; sqlite3 "$src" ".timeout 10000" ".backup \"$dest\"" ) 2>/dev/null; then
    out="$(sqlite3 "$dest" 'PRAGMA integrity_check;' 2>/dev/null | head -1)"
    [ "$out" = ok ] && return 0
    rm -f "$dest"; BACKUP_WHY="the copy failed its integrity check"; return 1
  fi
  # No sqlite3 command: let PHP make the copy (VACUUM INTO is consistent while the site is in use).
  if ( umask 077; D="$dest" "$PHP_BIN" -r '$p = new PDO("sqlite:".$argv[1]); $p->exec("VACUUM INTO ".$p->quote(getenv("D")));' "$src" ) 2>/dev/null && [ -s "$dest" ]; then
    return 0
  fi
  rm -f "$dest"
  # Last resort: a plain file copy. Only good when nothing writes to the database right now.
  if cp "$src" "$dest" && chmod 600 "$dest"; then
    [ -f "$src-wal" ] && { cp "$src-wal" "$dest-wal" && chmod 600 "$dest-wal"; }
    warn "No sqlite3 command and no PHP copy: the database was copied as a plain file. Install sqlite3 for a consistent copy."
    return 0
  fi
  BACKUP_WHY="the file could not be copied"; return 1
}
backup_mysql() {
  local host port name user pass dest="$1" dumper pw
  host="$(get_env .env DB_HOST)"; port="$(get_env .env DB_PORT)"; name="$(get_env .env DB_DATABASE)"
  user="$(get_env .env DB_USERNAME)"; pass="$(get_env .env DB_PASSWORD)"
  case "$name" in ''|*[!A-Za-z0-9_]*) BACKUP_WHY="the database name in .env has characters other than letters, digits and _"; return 1 ;; esac
  case "$host$port$user$pass" in *$'\n'*|*$'\r'*) BACKUP_WHY="a database setting in .env has a line break in it"; return 1 ;; esac
  if have mysqldump; then dumper=mysqldump; elif have mariadb-dump; then dumper=mariadb-dump; else BACKUP_WHY="mysqldump is not installed"; return 1; fi
  CNF="$(umask 077; mktemp "${TMPDIR:-/tmp}/biohop-dump.XXXXXX")" || { BACKUP_WHY="no temporary file"; return 1; }
  pw="$(printf '%s' "$pass" | sed 's/[\\"]/\\&/g')"
  { printf '[client]\nuser="%s"\npassword="%s"\nhost="%s"\n' "$user" "$pw" "${host:-127.0.0.1}"; [ -n "$port" ] && printf 'port=%s\n' "$port"; } >"$CNF"
  TMPDUMP="$dest.part"
  if ! ( umask 077; "$dumper" --defaults-extra-file="$CNF" --single-transaction --quick --routines "$name" >"$TMPDUMP" ) 2>/dev/null; then
    # MySQL 8 refuses to dump tablespaces without the PROCESS privilege; MariaDB does not know the option.
    ( umask 077; "$dumper" --defaults-extra-file="$CNF" --single-transaction --quick --routines --no-tablespaces "$name" >"$TMPDUMP" ) 2>/dev/null || { BACKUP_WHY="$dumper failed (wrong user or password, or the server is not running)"; return 1; }
  fi
  if ! tail -n 1 "$TMPDUMP" | grep -q 'Dump completed'; then BACKUP_WHY="the dump is incomplete"; return 1; fi
  mv -f "$TMPDUMP" "$dest" && TMPDUMP=''; chmod 600 "$dest"
}
make_backup() {
  local dbc dbf stamp
  dbc="$(get_env .env DB_CONNECTION)"; stamp="$(date +%Y%m%d-%H%M%S)"
  if [ "$DRY" = 1 ]; then say "   (dry run) copy the database to backups/ ($dbc)"; return 0; fi
  ( umask 077; mkdir -p backups )
  case "$dbc" in
    sqlite)
      dbf="$(get_env .env DB_DATABASE)"; [ -n "$dbf" ] || dbf="$SELF_DIR/database/database.sqlite"
      case "$dbf" in /*) ;; *) dbf="$SELF_DIR/$dbf" ;; esac
      [ -f "$dbf" ] || { say "   ${D}No SQLite file at $dbf yet: nothing to copy.${Z}"; return 0; }
      BACKUP_FILE="backups/database-$stamp.sqlite"
      backup_sqlite "$dbf" "$BACKUP_FILE" || { BACKUP_FILE=''; return 1; } ;;
    mysql|mariadb)
      BACKUP_FILE="backups/$(get_env .env DB_DATABASE)-$stamp.sql"
      backup_mysql "$BACKUP_FILE" || { BACKUP_FILE=''; return 1; } ;;
    *) BACKUP_WHY="a '$dbc' database cannot be copied by this script"; return 1 ;;
  esac
  ok "Database copied to $BACKUP_FILE"
}
if [ "$NO_BACKUP" = 1 ]; then
  warn "--no-backup: the database is not copied first."
elif ! make_backup; then
  rm -f "$CNF"; CNF=''; [ -n "$TMPDUMP" ] && rm -f "$TMPDUMP"; TMPDUMP=''
  warn "No copy of the database was made: ${BACKUP_WHY:-unknown reason}."
  if [ "$YES" = 1 ] || [ "$INTERACTIVE" = 0 ]; then
    die "Stopped, nothing was changed. Make a copy yourself and run ./update.sh --yes --no-backup, or fix the problem above."
  fi
  confirm "Continue without a copy?" n || { say "Nothing was changed."; exit 0; }
fi
[ -n "$CNF" ] && rm -f "$CNF"; CNF=''

# 2. Code, packages, front end, migrations.
# STAGE says how far the update got: 0 not started, 1 site down, 2 fetching the code,
# 3 code changed (packages and front end), 4 database step reached.
STAGE=0
recovery_steps() {
  say ""
  if [ "$STAGE" -ge 4 ]; then
    say "  ${B}The update stopped at or after the database step.${Z} The site was left in maintenance mode on purpose:"
    say "  the database may already have changed, so nothing was undone automatically."
  else
    say "  ${B}The previous version could not be put back automatically.${Z} The database was not touched yet."
  fi
  say "  To go back to the version before ($BEFORE):"
  if [ "$STAGE" -lt 4 ]; then
    say "    1. Nothing to restore: the database was not changed."
  elif [ -n "$BACKUP_FILE" ]; then
    case "$BACKUP_FILE" in
      *.sqlite) say "    1. Put the database copy back:   cp $SELF_DIR/$BACKUP_FILE <the SQLite file named in .env>" ;;
      *) say "    1. Restore the database copy:    mysql -u <user> -p <database> < $SELF_DIR/$BACKUP_FILE" ;;
    esac
  else
    say "    1. No database copy was made this time. Restore one of your own, if you need the old data."
  fi
  say "    2. cd $SELF_DIR && git reset --hard $BEFORE_SHA"
  say "    3. $PHP_BIN \$(command -v composer) install --no-dev --optimize-autoloader && npm ci && npm run build"
  say "    4. $PHP_BIN artisan optimize:clear && $PHP_BIN artisan up"
  [ "$STAGE" -ge 4 ] && say "  To keep the new version instead: fix what is shown above, run the failed step or ./update.sh again, then:  $PHP_BIN artisan up"
  return 0
}
restore_previous() {
  say ""
  warn "Something failed before the database was touched: putting the previous version ($BEFORE) back."
  if [ "$(git rev-parse HEAD 2>/dev/null)" != "$BEFORE_SHA" ]; then
    # With --force and edits of your own, --keep leaves those edits alone (and refuses if they are in the way).
    if [ "$LOCAL_CHANGES" = 1 ]; then git reset --keep "$BEFORE_SHA" >/dev/null || return 1; else git reset --hard "$BEFORE_SHA" >/dev/null || return 1; fi
  fi
  if [ "$STAGE" -ge 3 ]; then
    composer_run install --no-dev --optimize-autoloader --no-interaction --no-progress || return 1
    npm ci --no-audit --no-fund --loglevel=error || return 1
    npm run build --silent || return 1
  fi
  "$PHP_BIN" artisan optimize:clear >/dev/null 2>&1 || true
  "$PHP_BIN" artisan optimize >/dev/null 2>&1 || true
}
finish() {
  local rc=$?
  trap - EXIT INT TERM
  set +e
  [ -n "$CNF" ] && rm -f "$CNF"
  [ -n "$TMPDUMP" ] && rm -f "$TMPDUMP"
  [ "$DRY" = 1 ] && exit "$rc"
  if [ "$rc" = 0 ]; then
    "$PHP_BIN" artisan up >/dev/null 2>&1 || { bad "The update worked, but the site could not leave maintenance mode. Run:  $PHP_BIN artisan up"; exit 1; }
    exit 0
  fi
  if [ "$STAGE" -le 0 ]; then exit "$rc"; fi
  if [ "$STAGE" -ge 4 ]; then recovery_steps; exit "$rc"; fi
  if [ "$STAGE" -ge 2 ] && ! { [ "$STAGE" -eq 2 ] && [ "$(git rev-parse HEAD 2>/dev/null)" = "$BEFORE_SHA" ]; }; then
    if restore_previous; then
      "$PHP_BIN" artisan up >/dev/null 2>&1
      bad "The update failed and the previous version is back and online. Read the error above, fix it and run ./update.sh again."
    else
      bad "The update failed and the previous version could not be restored automatically. The site stays in maintenance mode."
      recovery_steps
    fi
    exit "$rc"
  fi
  "$PHP_BIN" artisan up >/dev/null 2>&1   # failed before any change to the code
  bad "The update failed before anything changed; the site is back online."
  exit "$rc"
}
trap 'exit 130' INT
trap 'exit 143' TERM
trap finish EXIT
git symbolic-ref -q HEAD >/dev/null || die "This checkout is not on a branch. Run:  git checkout main  and try again."
[ "$DRY" = 1 ] || { "$PHP_BIN" artisan down --retry=30 >/dev/null 2>&1 || true; }
STAGE=1
STAGE=2; step "Fetching the new version" git pull --ff-only
STAGE=3; step "PHP packages (composer)" composer_run install --no-dev --optimize-autoloader --no-interaction --no-progress
step "Front end (npm)" npm ci --no-audit --no-fund --loglevel=error
step "Building the front end" npm run build --silent
STAGE=4; step "Updating the database tables" "$PHP_BIN" artisan migrate --force
step "Linking the storage folder" "$PHP_BIN" artisan storage:link --force
step "Clearing and warming caches" "$PHP_BIN" artisan optimize:clear
step "Warming caches" "$PHP_BIN" artisan optimize
step "Telling queue workers to restart" "$PHP_BIN" artisan queue:restart
AFTER="$(git describe --tags --always 2>/dev/null || git rev-parse --short HEAD)"
hdr "Done"
ok "BioHop updated: $BEFORE → $AFTER"
say "  If it runs as a service (systemd, supervisor), restart it. With ./start.sh, stop it (Ctrl+C) and start it again."

}
main "$@"; exit $?
