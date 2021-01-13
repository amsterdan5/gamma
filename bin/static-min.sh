#!/bin/bash

### 项目自动初始化 ###
root_dir=$(cd "$(dirname "$0")"; cd ..; pwd)
uglifyjs=$root_dir"/node_modules/uglify-js/bin/uglifyjs"
uglifycss=$root_dir"/node_modules/uglifycss/uglifycss"

# 原文件
static="$root_dir/public/static"
js_src="$root_dir/public/static/js"
css_src="$root_dir/public/static/css"

### 环境检测 ###
node=`which node`
npm=`which npm`
if [[ ! $node && ! $npm ]]; then
    echo "请先安装 node"
    exit;
fi

if [[ ! -f $uglifyjs ]]; then
    echo "开始安装 uglifyjs"
    echo "npm install uglify-js"
    success=$(npm install uglify-js)
fi

if [[ ! -f $uglifycss ]]; then
    echo "开始安装 uglifycss"
    echo "npm install uglifycss"
    success=$(npm install uglifycss)
fi
### 环境检测 ###

min() {
    for f in `ls $1`; do
        filename=$1"/"$f
        if [[ -d $filename ]]; then
            min $filename
        elif [[ -f $filename ]]; then
            min_file=${filename/"static/"/""}
            dir=$(dirname "$min_file")

            if [[ ! -d "$dir" ]]; then
                mkdir -p $dir
                chown -R www:www $dir
                chmod -R 755 $dir
            fi

            ext=${filename##*.}
            # js 进行压缩
            if [[ $ext == "js" ]]; then
                $uglifyjs $filename -o $min_file
                echo $min_file
            elif [[ $ext == "css" ]]; then
                $uglifycss $filename --output $min_file
                echo $min_file
            else
            # 其他文件直接拷贝
                cp $filename $min_file
                echo $min_file
            fi
        fi
    done
}

min $static