#!/usr/bin/env bash

set -euo pipefail

SOURCE_DIR="${1:-../wordpress.local}"
TARGET_DIR="$(pwd)"

if [ ! -d "$SOURCE_DIR" ]; then
  echo "Source directory not found: $SOURCE_DIR" >&2
  exit 1
fi

SOURCE_DIR="$(cd "$SOURCE_DIR" && pwd)"
TARGET_DIR="$(cd "$TARGET_DIR" && pwd)"

if [ "$SOURCE_DIR" = "$TARGET_DIR" ]; then
  echo "Source and target directories must be different." >&2
  exit 1
fi

echo "Source: $SOURCE_DIR"
echo "Target: $TARGET_DIR"

find "$TARGET_DIR" -mindepth 1 -maxdepth 1 \
  ! -name '.git' \
  ! -name '.gitignore' \
  ! -name '.gitmodules' \
  ! -name 'source' \
  -exec rm -rf {} +

find "$SOURCE_DIR" -mindepth 1 -maxdepth 1 \
  ! -name '.git' \
  ! -name 'source' \
  -exec cp -a {} "$TARGET_DIR"/ \;

echo "Docker setup copied successfully."
