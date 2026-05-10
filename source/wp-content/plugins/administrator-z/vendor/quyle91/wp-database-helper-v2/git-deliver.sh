#!/bin/bash

# Lấy tên thư mục hiện tại
FOLDER_NAME=$(basename "$(pwd)")
SOURCE_FOLDER=$(pwd)
GIT_FOLDER=~/projects/git/$FOLDER_NAME
REPO_URL="git@github.com:quyle91/$FOLDER_NAME.git"
SCRIPT_NAME=$(basename "$0")

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}::: Folder Name: ${FOLDER_NAME}${NC}"
echo -e "${YELLOW}::: Source Folder: ${SOURCE_FOLDER}${NC}"
echo -e "${YELLOW}::: Git Folder: ${GIT_FOLDER}${NC}"
echo -e "${YELLOW}::: Repo URL: ${REPO_URL}${NC}"
echo ""

# Kiểm tra Git folder có tồn tại không thì clone
if [ ! -d "$GIT_FOLDER" ]; then
    echo -e "${YELLOW}::: Git folder not found. Cloning...${NC}"
    mkdir -p ~/projects/git
    cd ~/projects/git || exit 1
    git clone "$REPO_URL"
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}::: Clone failed! Creating new repo...${NC}"
        mkdir -p "$GIT_FOLDER"
        cd "$GIT_FOLDER" || exit 1
        git init
        git remote add origin "$REPO_URL"
    fi
fi

# Xóa toàn bộ trong Git folder (trừ .git)
echo -e "${GREEN}::: Cleaning Git folder...${NC}"
cd "$GIT_FOLDER" || exit 1
find . -mindepth 1 -not -path "./.git*" -delete

# Copy tất cả từ source sang Git
echo -e "${GREEN}::: Copying files from source...${NC}"
cd "$SOURCE_FOLDER" || exit 1

# Copy tất cả files và folders (kể cả ẩn) - CÁCH 1: Dùng rsync nếu có
if command -v rsync &> /dev/null; then
    rsync -av --exclude=".git" ./ "$GIT_FOLDER/"
else
    # CÁCH 2: Dùng cp với tham số -r để copy cả thư mục
    # Copy từng item, giữ nguyên cấu trúc
    for item in * .[^.]*; do
        if [ "$item" != "$SCRIPT_NAME" ] && [ "$item" != "." ] && [ "$item" != ".." ] && [ "$item" != ".git" ]; then
            cp -rf "$item" "$GIT_FOLDER/" 2>/dev/null || true
        fi
    done
fi

# Vào Git folder và commit
cd "$GIT_FOLDER" || exit 1

echo -e "${GREEN}::: Git status before commit:${NC}"
git status --short

# Add và commit
git add .

if git diff --cached --quiet; then
    echo -e "${YELLOW}::: No changes to commit${NC}"
else
    git commit -m "$FOLDER_NAME update $(date '+%Y-%m-%d %H:%M:%S')"
    echo -e "${GREEN}::: Pushing to $REPO_URL...${NC}"
    git push origin main || git push origin master
fi

echo -e "${GREEN}::: Done!${NC}"