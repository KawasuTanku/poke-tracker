#!/usr/bin/env bash
#
# deploy.sh — pull the latest PokeTracker app into the Caddy doc root.
#
# On the Warpstrand server, Caddy + PHP-FPM both run as www-data, so this
# script deploys files owned by www-data. Run as root or via sudo.
#
# Env overrides:
#   POKE_DEST   default /opt/caddy/poke.warpstrand.com
#   POKE_REMOTE default https://github.com/KawasuTanku/poke-tracker.git
#   POKE_BRANCH default main
#   COMPOSER     default: COMPOSER env, then /opt/caddy/bin/composer.phar, then `composer` on PATH

if [[ -n "${COMPOSER:-}" ]]; then
    COMPOSER_BIN="$COMPOSER"
elif [[ -x /opt/caddy/bin/composer.phar ]]; then
    COMPOSER_BIN="/opt/caddy/bin/composer.phar"
elif [[ -x /opt/caddy/composer.phar ]]; then
    COMPOSER_BIN="/opt/caddy/composer.phar"
elif command -v composer >/dev/null 2>&1; then
    COMPOSER_BIN="$(command -v composer)"
else
    echo "==> Composer not found — downloading to /opt/caddy/bin/composer.phar"
    mkdir -p /opt/caddy/bin
    php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    php /tmp/composer-setup.php --install-dir=/opt/caddy/bin --filename=composer.phar
    rm -f /tmp/composer-setup.php
    if [[ -x /opt/caddy/bin/composer.phar ]]; then
        COMPOSER_BIN="/opt/caddy/bin/composer.phar"
    else
        echo "ERROR: Failed to install composer. Install manually or set COMPOSER env var."
        exit 1
    fi
fi

echo "==> Using composer: $COMPOSER_BIN"

REMOTE="${POKE_REMOTE:-https://github.com/KawasuTanku/poke-tracker.git}"
DEST="${POKE_DEST:-/opt/caddy/poke.warpstrand.com}"
OWNER="www-data:www-data"
BRANCH="${POKE_BRANCH:-main}"

# Git operations need write access to the parent dir — run as root or current user.
# Only composer install and file ownership need www-data.
git_as() {
    if [[ "$(id -u)" -eq 0 ]]; then
        git config --global --add safe.directory "$DEST" 2>/dev/null || true
        bash -c "$1"
    else
        bash -c "$1"
    fi
}
run_as() { if [[ "$(id -u)" -eq 0 ]]; then sudo -u www-data bash -c "$1"; else bash -c "$1"; fi; }

echo "==> Deploying poke-tracker to $DEST"

# Ensure parent dir exists with correct ownership
if [[ "$(id -u)" -eq 0 ]]; then
    mkdir -p "$(dirname "$DEST")"
    chown www-data:www-data "$(dirname "$DEST")"
fi

DATA_BAK="$(mktemp -d "${TMPDIR:-/tmp}/poke-data.XXXXXX")"
if [[ -e "$DEST/data" ]]; then
    echo "==> Snapshotting live data/ before sync"
    cp -a "$DEST/data" "$DATA_BAK/data" 2>/dev/null || echo "  (warn: could not snapshot data/)"
fi

if [[ -d "$DEST/.git" ]]; then
    echo "==> Existing repo found — force-syncing to $REMOTE ($BRANCH)"
    git_as "cd '$DEST' && (git remote set-url origin '$REMOTE' 2>/dev/null || git remote add origin '$REMOTE') && git fetch -q origin '$BRANCH' && git checkout -q -f -B '$BRANCH' origin/'$BRANCH'"
elif [[ -d "$DEST" ]]; then
    echo "==> Path exists without a repo — initialising git and checking out $BRANCH"
    git_as "cd '$DEST' && git init -q && git remote add -f origin '$REMOTE' && git fetch -q origin '$BRANCH' && git checkout -q -f -B '$BRANCH' origin/'$BRANCH'"
else
    echo "==> Fresh deploy — cloning repo"
    git_as "git clone --branch '$BRANCH' '$REMOTE' '$DEST'"
fi

if [[ -e "$DATA_BAK/data" ]]; then
    echo "==> Restoring live data/ after sync"
    rm -rf "$DEST/data"
    cp -a "$DATA_BAK/data" "$DEST/data"
fi
rm -rf "$DATA_BAK"

echo "==> Installing composer dependencies"
sudo -u www-data bash -c "cd '$DEST' && $COMPOSER_BIN install --no-dev --no-interaction --optimize-autoloader"

echo "==> Preparing data directory"
mkdir -p "$DEST/data" && chmod 750 "$DEST/data"

echo "==> Fixing ownership"
chown -R "$OWNER" "$DEST"

if command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files caddy.service >/dev/null 2>&1; then
    echo "==> Restarting caddy to clear opcode cache"
    systemctl restart caddy.service || echo "  (warn: caddy restart failed; restart manually)"
fi

echo "==> Done. Visit https://poke.warpstrand.com/setup and complete first-run setup."
