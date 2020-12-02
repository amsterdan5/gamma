#!/bin/bash

### 项目自动初始化 ###
root_dir=$(cd "$(dirname "$0")"; cd ..; pwd)

chown -R www.www $0
chmod -R +x $root_dir/bin/*

# 清空目录
rm_files=("data/cache/*" "data/metadata/*" "data/volt/*")
for file in ${rm_files[@]}; do
    file=$root_dir/$file
    rm -rf $file
    echo "Remove Files: $file"
done

# 创建目录
mk_dirs=("data/cache" "data/metadata" "data/volt" "data/logs" "data/dbbackup" "public/uploads")
for dir in ${mk_dirs[@]}; do
    dir=$root_dir/$dir
    if [ ! -d $dir ]; then
        mkdir -p $dir
        echo "Create Directory: $dir"
    fi
    chown -R www.www $dir
done