#!/bin/bash
# ~/projects/flatsome-local/src/wp-content/plugins/administrator-z/svn-checkout.sh
# lên https://login.wordpress.org/ để lấy login account
set -e

# ==============================
# 1. Khai báo biến
# ==============================
project_folder=$(basename "$(pwd)")
plugin_source_path="$(pwd)"
svn_folder="svn"
projects_path="$HOME/projects"
svn_project_path="$projects_path/$svn_folder/$project_folder"

echo ">>> Project: $project_folder"
echo ">>> Source: $plugin_source_path"
echo ">>> SVN Target: $svn_project_path"

# ==============================
# 2. Kiểm tra/Cài SVN
# ==============================
if ! command -v svn >/dev/null 2>&1; then
    echo ">>> Installing subversion..."
    sudo apt update && sudo apt install subversion -y
fi

# ==============================
# 3. Chuẩn bị thư mục SVN
# ==============================
mkdir -p "$projects_path/$svn_folder"
cd "$projects_path/$svn_folder"

if [ ! -d "$project_folder" ]; then
    echo ">>> Checking out plugin from WordPress.org..."
    svn checkout "https://plugins.svn.wordpress.org/$project_folder"
else
    echo ">>> SVN folder already exists, updating..."
    cd "$project_folder"
    svn update
    cd ..
fi

# ==============================
# 4. Deploy code vào trunk
# ==============================
echo ">>> Syncing files to trunk..."
if [ ! -d "$svn_project_path/trunk" ]; then
    echo "ERROR: Trunk directory not found in SVN target: $svn_project_path/trunk"
    echo "Please check if the plugin slug '$project_folder' is correct on WordPress.org."
    exit 1
fi

cd "$svn_project_path/trunk"
# Xóa các file cũ (trừ .svn)
find . -mindepth 1 -maxdepth 1 ! -name '.svn' -exec rm -rf {} +
# Copy code mới
cp -r "$plugin_source_path"/* .

# ==============================
# 5. Add / Delete / Commit
# ==============================
cd "$svn_project_path"
echo ">>> Checking SVN status..."
svn status

echo ">>> Adding new files..."
svn add --force . --auto-props --parents --depth infinity -q

echo ">>> Removing deleted files..."
# Xử lý các file đã bị xóa thủ công hoặc bằng lệnh rm ở trên
svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm % 2>/dev/null || true

echo ">>> Committing to WordPress.org..."
commit_message="$project_folder release date $(date '+%Y-%m-%d %H:%M:%S')"
# Commit các thay đổi. Nếu không có gì thay đổi, lệnh này có thể báo lỗi nhẹ, ta dùng || true để script không dừng.
svn commit -m "$commit_message" || echo "Nothing to commit or SVN error (check your credentials)."

echo ">>> DONE: Deploy completed."

