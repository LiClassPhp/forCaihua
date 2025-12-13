#!/bin/bash

# 用法说明
# /data/work/kch/svn.sh -s 2025-07-01 -e 2025-08-01 -p /data/work/kch/svnlog

# svnlog.sh -s 2025-07-01 -e 2025-08-01 -p /Users/liclass/Documents/财华维权文件/svn/svn_shell -r Users/liclass/Documents/wwwroot


usage() {
    echo "用法: $0 -s 开始日期 -e 结束日期 -p 日志路径 -r SVN根目录"
    echo "日期格式: YYYY-MM-DD"
    echo "示例: $0 -s 2023-01-01 -e 2023-01-31 -p /tmp/svn_logs -r /data/wwwroot"
    exit 1
}


# 检查是否安装了svn
if ! command -v svn &> /dev/null; then
    echo "错误: svn命令未找到，请先安装Subversion"
    exit 1
fi

# 默认SVN仓库根目录
# SVN_ROOT="D:/phpstudy_pro/WWW/root"
username="ClassLi"
passwd="lisai19940204"

# 设置默认日期范围（昨天作为开始，今天作为结束）
set_default_dates() {
    # 判断系统类型以使用正确的date命令
    if date -v -1d >/dev/null 2>&1; then
      echo "为Mac系统"
        # macOS/BSD系统
        start_date=$(date -v -1d +%Y-%m-%d)
        end_date=$(date +%Y-%m-%d)
    else
        # Linux/GNU系统
        start_date=$(date -d "yesterday" +%Y-%m-%d)
        end_date=$(date +%Y-%m-%d)
    fi
}


# 解析参数
while getopts "s:e:p:r:" opt; do
    case $opt in
        s) start_date="$OPTARG" ;;
        e) end_date="$OPTARG" ;;
        p) base_path="$OPTARG" ;;
        r) SVN_ROOT="$OPTARG" ;;
        *) usage ;;
    esac
done

# 如果没有提供日期参数，则设置默认值
if [ -z "$start_date" ] || [ -z "$end_date" ]; then
    set_default_dates
fi

# 检查必要参数
if [ -z "$base_path" ]; then
    usage
fi


# SVN仓库路径（基于SVN_ROOT）
SVN_REPOS=(
    "$SVN_ROOT/syslib"
    "$SVN_ROOT/72mao"
#    "$SVN_ROOT/private-scm"
)

# 验证日期格式和目录存在性
#validate_date() {
#    if ! date -d "$1" >/dev/null 2>&1; then
#        echo "错误: 无效日期格式 '$1'，请使用 YYYY-MM-DD"
#        exit 1
#    fi
#}
#
#validate_date "$start_date"
#validate_date "$end_date"
#
## 确保结束日期不小于开始日期
#if [ $(date -d "$start_date" +%s) -gt $(date -d "$end_date" +%s) ]; then
#    echo "错误: 结束日期不能早于开始日期"
#    exit 1
#fi

# 检查SVN根目录是否存在
if [ ! -d "$SVN_ROOT" ]; then
    echo "错误: SVN根目录不存在: $SVN_ROOT"
    exit 1
fi

# 更新所有SVN仓库
echo "开始更新SVN仓库..."
for repo in "${SVN_REPOS[@]}"; do
    if [ -d "$repo" ]; then
        echo "正在更新: $repo"
        svn update --username "$username" --password "$passwd" "$repo" --accept postpone > /dev/null
        if [ $? -ne 0 ]; then
            echo "警告: $repo 更新失败"
        else
            echo "$repo 更新完成"
        fi
    else
        echo "警告: SVN仓库 $repo 不存在，跳过更新"
    fi
done
echo "SVN仓库更新完成"

# 处理日期范围
#current_date=$(date -d "$start_date" +%Y-%m-%d)
#end_date_ts=$(date -d "$end_date" +%s)

current_date="$start_date"
end_date_ts=$(date -j -f "%Y-%m-%d" "$end_date" +%s)

while [ "$(date -j -f "%Y-%m-%d" "$current_date" +%s)" -le "$end_date_ts" ]; do
	year=$(date -j -f "%Y-%m-%d" "$current_date" +%Y)
    date_dir="$base_path/$year/$current_date"
    
    echo "处理日期: $current_date"
    
    # 初始化变量
    latest_time="00:00:00"
    has_late_commit=false
    
    
#    next_date=$(date -d "$current_date + 1 day" +%Y-%m-%d)
    next_date=$(date -j -v+1d -f "%Y-%m-%d" "$current_date" +%Y-%m-%d)

    for repo in "${SVN_REPOS[@]}"; do
        repo_name=$(basename "$repo")
        log_file="$date_dir/${repo_name}_log.txt"
        changed_files_file="$date_dir/${repo_name}_changed_files.txt"
        
        echo "正在获取 $repo_name 的提交日志..."
        
        if [ ! -d "$repo" ]; then
            echo "警告: SVN仓库 $repo 不存在，跳过"
            continue
        fi
        
        # 获取原始日志输出
        log_output=$(svn log --username "$username" --password "$passwd" -r "{$current_date}:{$next_date}" -v --search "ClassLi" "$repo" 2>&1)
        
        if [[ -n "$log_output" ]] && echo "$log_output" | grep -q "^r[0-9]"; then
            cleaned_output="$log_output"
            # 过滤非当前日期的记录
            filtered_output=""
            current_block=""
            processing_block=false
            block_date=""
            block_has_current_date=false
            
            while IFS= read -r line; do
                # 检测新的提交块开始
                if [[ "$line" =~ ^(r[0-9]+)\ \|\ .+\ \|\ ([0-9]{4}-[0-9]{2}-[0-9]{2})\ ([0-9]{2}:[0-9]{2}:[0-9]{2}) ]]; then
                    # 处理前一个块
                    if $processing_block && $block_has_current_date; then
                        filtered_output+="$current_block"
                    fi
                    
                    # 开始新的块
                    processing_block=true
                    current_block="$line"$'\n'
                    block_date="${BASH_REMATCH[2]}"
                    commit_time="${BASH_REMATCH[3]}"
                    
                    # 检查是否属于当前日期
                    if [[ "$block_date" == "$current_date" ]]; then
                        block_has_current_date=true
                        
                        # 检查是否超过18:30并更新最晚时间
                        hour=$((10#${commit_time:0:2}))
                        minute=$((10#${commit_time:3:2}))
                        
                        if [[ $hour -gt 18 || ($hour -eq 18 && $minute -ge 30) ]]; then
                            has_late_commit=true
                            if [[ "$commit_time" > "$latest_time" ]]; then
                                latest_time="$commit_time"
                                echo "更新最晚时间: $latest_time (来自 $repo_name)"
                            fi
                        fi
                    else
                        block_has_current_date=false
                    fi
                    
                elif $processing_block; then
                    # 继续收集当前块
                    current_block+="$line"$'\n'
                fi
            done <<< "$cleaned_output"
            
            # 处理最后一个块
            if $processing_block && $block_has_current_date; then
                filtered_output+="$current_block"
            fi
            
            # 更新cleaned_output
            cleaned_output=$(echo "$filtered_output" | sed -e '/^$/d')
            
            # 只有 cleaned_output 有内容时才写入文件
            if [[ -n "$cleaned_output" ]]; then
    		# 先创建基础目录
		if [[ ! -d "$dir_path" ]]; then
			mkdir -p "$date_dir" 2>/dev/null || { echo "错误：无法创建目录 $date_dir"; exit 1; }
		fi
                echo "$cleaned_output" > "$log_file"
                echo "日志已保存至: $log_file"
                
                # 如果不是 private-scm 仓库，则处理变更文件
                if [[ "$repo_name" != "private-scm" ]]; then
                    # 清空变更文件
                    : > "$changed_files_file"
                    
                    # 初始化变量
                    current_rev=""
                    current_rev_line=""
                    current_files=()
                    current_commit=""
                    processing_files=false

                    while IFS= read -r line; do
                        # 移除Windows换行符
                        line=${line%$'\r'}

                        # 1. 处理版本行
                        if [[ "$line" =~ ^(r[0-9]+)\ \|\ (.+) ]]; then
                            # 如果有待处理的文件，先输出
                            if [[ ${#current_files[@]} -gt 0 && -n "$current_commit" ]]; then
                                for file_info in "${current_files[@]}"; do
                                    IFS='|' read -r action file <<< "$file_info"
                                    echo "$current_rev_line" >> "$changed_files_file"
                                    echo "commit:$current_commit" >> "$changed_files_file"
                                    
                                    case "$action" in
                                        M|A|D)
                                            svn diff --username "$username" --password "$passwd" -c "$current_rev" "${repo}/${file}" >> "$changed_files_file"
                                            ;;
                                        R)
                                            old=${file%% *}
                                            new=${file##* }
                                            svn diff --username "$username" --password "$passwd" -c "$current_rev" "${repo}/${old}" "${repo}/${new}" >> "$changed_files_file"
                                            ;;
                                    esac
                                    echo >> "$changed_files_file"  # 添加空行分隔
                                done
                            fi
                            
                            # 重置状态
                            current_rev=${BASH_REMATCH[1]#r}
                            current_rev_line="$line"
                            current_files=()
                            current_commit=""
                            processing_files=false
                            continue
                        fi

                        # 2. 处理"改变的路径"行
                        if [[ "$line" =~ "改变的路径" ]]; then
                            processing_files=true
                            continue
                        fi

                        # 3. 收集变更文件行
                        if [[ "$processing_files" == true && "$line" =~ ^[[:space:]]+([A-Z])[[:space:]]+(.+)$ ]]; then
                            action="${BASH_REMATCH[1]}"
                            file="${BASH_REMATCH[2]#/}"
                            current_files+=("${action}|${file}")
                            continue
                        fi

                        # 4. 捕获commit信息（在变更文件后的第一行非空文本）
                        if [[ ${#current_files[@]} -gt 0 && -z "$current_commit" && \
                              ! "$line" =~ ^[[:space:]]*$ && \
                              ! "$line" =~ "改变的路径" && \
                              ! "$line" =~ ^[[:space:]]+[A-Z][[:space:]] ]]; then
                            current_commit="$line"
                            
                            # 立即输出收集到的文件和commit
                            for file_info in "${current_files[@]}"; do
                                IFS='|' read -r action file <<< "$file_info"
                                echo "$current_rev_line" >> "$changed_files_file"
                                echo "commit:$current_commit" >> "$changed_files_file"
                                
                                case "$action" in
                                    M|A|D)
                                        svn diff --username "$username" --password "$passwd" -c "$current_rev" "${repo}/${file}" >> "$changed_files_file"
                                        ;;
                                    R)
                                        old=${file%% *}
                                        new=${file##* }
                                        svn diff --username "$username" --password "$passwd" -c "$current_rev" "${repo}/${old}" "${repo}/${new}" >> "$changed_files_file"
                                        ;;
                                esac
                                echo >> "$changed_files_file"  # 添加空行分隔
                            done
                            
                            # 重置文件列表，保留commit用于可能的多组变更
                            current_files=()
                            continue
                        fi
                    done <<< "$cleaned_output"

                    # 处理最后一批文件
                    if [[ ${#current_files[@]} -gt 0 && -n "$current_commit" ]]; then
                        for file_info in "${current_files[@]}"; do
                            IFS='|' read -r action file <<< "$file_info"
                            echo "$current_rev_line" >> "$changed_files_file"
                            echo "commit:$current_commit" >> "$changed_files_file"
                            
                            case "$action" in
                                M|A|D)
                                    svn diff --username "$username" --password "$passwd" -c "$current_rev" "${repo}/${file}" >> "$changed_files_file"
                                    ;;
                                R)
                                    old=${file%% *}
                                    new=${file##* }
                                    svn diff --username "$username" --password "$passwd" -c "$current_rev" "${repo}/${old}" "${repo}/${new}" >> "$changed_files_file"
                                    ;;
                            esac
                            echo >> "$changed_files_file"  # 添加空行分隔
                        done
                    fi
                        
                    echo "生成变更文件: $changed_files_file"
                else
                    echo "跳过 private-scm 仓库的变更文件生成"
                fi
            else
                echo "过滤后：无有效提交记录"
            fi
        else
            echo "无有效提交记录"
        fi
    done
    
    # 所有仓库处理完成后，根据最晚时间重命名目录
    if $has_late_commit; then
        new_dir="${date_dir}*${latest_time}"
        if mv "$date_dir" "$new_dir" 2>/dev/null; then
            echo "已创建加班目录: $new_dir (最晚提交时间: $latest_time)"
        else
            echo "警告: 目录重命名失败，保持原目录: $date_dir"
        fi
    else
        echo "xxx当天无加班提交xxx"
    fi
    
#    current_date=$(date -d "$current_date + 1 day" +%Y-%m-%d)
    current_date=$(date -j -v+1d -f "%Y-%m-%d" "$current_date" +%Y-%m-%d)

done

echo "完成！日志已保存到 $base_path"
# cd /data/work/.kch/svnlog && git add . && git commit -m "$current_date日志" .  && git push


#  ./svnlog.sh -s 2023-04-01 -e 2025-11-13 -p /Users/liclass/Documents/财华维权文件/svn/svn_shell -r /Users/liclass/Documents/wwwroot

#  ./html/caihua/code/svnlog.sh -s 2025-11-27 -e 2025-12-10 -p /Users/liclass/Documents/财华维权文件/svn/svn_shell -r /Users/liclass/Documents/wwwroot
