#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

step() {
  printf '\n==== %s ====\n' "$1"
}

step "Running PHP checks"
if command -v composer >/dev/null 2>&1; then
  composer ci
else
  echo "composer is not installed; skipping PHP checks" >&2
fi

step "Building frontend assets"
if command -v npm >/dev/null 2>&1; then
  TEMP_BUILD_DIR="$(mktemp -d)"
  if npm run build -- --outDir "$TEMP_BUILD_DIR" --emptyOutDir >/dev/null 2>&1; then
    echo "Frontend build succeeded (artifacts written to $TEMP_BUILD_DIR)"
    rm -rf "$TEMP_BUILD_DIR"
  else
    echo "Frontend build failed" >&2
    rm -rf "$TEMP_BUILD_DIR"
    exit 1
  fi
else
  echo "npm is not installed; skipping frontend build" >&2
fi

step "Git status"
git status -sb
